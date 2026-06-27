<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportMemoriesRequest;
use App\Models\Memory;
use App\Services\Memory\MemoryScoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemoryInspectorController extends Controller
{
    public function __construct(
        private readonly MemoryScoringService $scoring,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['period', 'type', 'page']);

        $query = Memory::where('user_id', $user->id)
            ->latest('created_at');

        // ── Period filter ──────────────────────────────────────
        $period = $filters['period'] ?? 'all';
        $now = now();

        match ($period) {
            '24h'  => $query->where('created_at', '>=', $now->copy()->subHours(24)),
            '7d'   => $query->where('created_at', '>=', $now->copy()->subDays(7)),
            '30d'  => $query->where('created_at', '>=', $now->copy()->subDays(30)),
            '90d'  => $query->where('created_at', '>=', $now->copy()->subDays(90)),
            default => null, // 'all' — no date constraint
        };

        // ── Type filter ────────────────────────────────────────
        if (! empty($filters['type']) && in_array($filters['type'], ['preference', 'fact', 'rule', 'skill'], true)) {
            $query->where('type', $filters['type']);
        }

        // ── Paginate ───────────────────────────────────────────
        $paginated = $query->paginate(30)->withQueryString();

        // ── Summary stats ──────────────────────────────────────
        $allMemories = Memory::where('user_id', $user->id);
        $summary = [
            'total'         => (clone $allMemories)->count(),
            'active'        => (clone $allMemories)->where('status', 'active')->count(),
            'stale'         => (clone $allMemories)->where('status', 'stale')->count(),
            'archived'      => (clone $allMemories)->whereNotNull('archived_at')->count(),
            'avg_confidence' => round((float) (clone $allMemories)->avg('confidence'), 4),
            'avg_importance' => round((float) (clone $allMemories)->avg('importance'), 4),
        ];

        // ── Enrich each memory with scoring data ───────────────
        $enriched = collect($paginated->items())->map(function (Memory $memory) {
            $meta = is_array($memory->metadata) ? $memory->metadata : [];

            // Recall score
            $recallScore = $this->scoring->recallScore($memory);

            // Current decay score (recomputed)
            $currentDecay = $this->scoring->decayScore($memory);

            // Creation reason
            $creationReason = $this->buildCreationReason($meta);

            // Recall eligibility
            $recallEligibility = $this->buildRecallEligibility($memory, $recallScore, $currentDecay);

            // Conflict info
            $conflictInfo = $this->buildConflictInfo($meta);

            return [
                'id'                => $memory->id,
                'type'              => $memory->type,
                'content'           => $memory->content,
                'normalized_content'=> $memory->normalized_content,
                'importance'        => (float) $memory->importance,
                'confidence'        => (float) $memory->confidence,
                'decay_score'       => (float) $memory->decay_score,
                'current_decay'     => $currentDecay,
                'recall_score'      => $recallScore,
                'status'            => $memory->status ?? 'active',
                'access_count'      => $memory->access_count ?? 0,
                'last_accessed_at'  => $memory->last_accessed_at?->toIso8601String(),
                'last_reinforced_at'=> $memory->last_reinforced_at?->toIso8601String(),
                'archived_at'       => $memory->archived_at?->toIso8601String(),
                'created_at'        => $memory->created_at->toIso8601String(),
                'updated_at'        => $memory->updated_at->toIso8601String(),
                'metadata'          => $meta,
                'creation_reason'   => $creationReason,
                'recall_eligibility'=> $recallEligibility,
                'conflict_info'     => $conflictInfo,
            ];
        })->all();

        return Inertia::render('app/MemoryInspector', [
            'memories'        => $enriched,
            'summary'         => $summary,
            'pagination'      => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
            ],
            'selectedFilters' => $filters,
        ]);
    }

    private function buildCreationReason(array $meta): array
    {
        $source = $meta['source'] ?? 'unknown';
        $sourceKind = $meta['source_kind'] ?? null;
        $explicitRemember = $meta['explicit_remember'] ?? false;
        $transient = $meta['transient'] ?? false;
        $subject = $meta['subject'] ?? 'self';

        $reason = match (true) {
            $explicitRemember && $sourceKind === 'code_snippet'
                => 'Explicitly remembered code snippet via /remember endpoint.',
            $explicitRemember
                => 'Explicitly stored via /remember endpoint with direct user intent.',
            $sourceKind === 'code_snippet'
                => 'Automatically extracted as a code snippet during AI-first ingestion.',
            $source === 'semantic_segmenter'
                => 'Created via semantic segmentation pipeline — content was split into atomic memory units.',
            $source === 'ai_extraction'
                => 'AI-powered extraction identified this as a distinct memory from user input.',
            default
                => 'Ingested through standard memory pipeline.',
        };

        $flags = [];
        if ($transient) $flags[] = 'Transient (session-scoped, capped confidence)';
        if ($subject === 'other') $flags[] = 'Third-party statement (about someone else)';
        if ($subject === 'general') $flags[] = 'General knowledge (not user-specific)';

        return [
            'summary' => $reason,
            'source'  => $source,
            'flags'   => $flags,
        ];
    }

    private function buildRecallEligibility(Memory $memory, float $recallScore, float $currentDecay): array
    {
        $eligible = true;
        $reasons = [];

        // Status check
        $status = $memory->status ?? 'active';
        if (! in_array($status, ['active', 'stale', null], true)) {
            $eligible = false;
            $reasons[] = "Memory status is \"{$status}\" — only active/stale memories are recalled.";
        }

        // Archived check
        if ($memory->archived_at) {
            $eligible = false;
            $reasons[] = 'Memory has been archived and is excluded from recall.';
        }

        // Context assembly threshold
        if ($recallScore < 0.25) {
            $reasons[] = "Recall score ({$recallScore}) is below the assembly threshold (0.25) — may be skipped during context assembly.";
        }

        // Decay warning
        if ($currentDecay < 0.3) {
            $reasons[] = "Decay score is very low ({$currentDecay}) — memory has not been accessed recently and is fading.";
        }

        // Confidence warning
        if ((float) $memory->confidence < 0.4) {
            $reasons[] = "Low confidence (" . number_format((float) $memory->confidence, 4) . ") — incurs a scoring penalty during recall.";
        }

        if ($eligible && empty($reasons)) {
            $reasons[] = 'Memory is fully eligible for recall with no scoring penalties.';
        } elseif ($eligible && ! empty($reasons)) {
            array_unshift($reasons, 'Memory is eligible for recall but has scoring adjustments:');
        }

        return [
            'eligible' => $eligible,
            'recall_score' => $recallScore,
            'reasons' => $reasons,
        ];
    }

    private function buildConflictInfo(array $meta): array
    {
        $conflictScore = (float) ($meta['conflict_score'] ?? 0);
        $supersededBy = $meta['superseded_by'] ?? null;
        $conflictResolution = $meta['conflict_resolution'] ?? null;

        $hasConflict = $conflictScore > 0 || $supersededBy !== null;

        $details = [];
        if ($conflictScore > 0) {
            $penalty = min(0.4, $conflictScore * 0.1);
            $details[] = "Conflict score: {$conflictScore} → applies a -{$penalty} penalty during context assembly.";
        }
        if ($supersededBy) {
            $details[] = "Superseded by memory #{$supersededBy} — a newer/more confident memory replaced this one.";
        }
        if ($conflictResolution) {
            $details[] = "Resolution: {$conflictResolution}";
        }
        if (! $hasConflict) {
            $details[] = 'No conflicts detected with other memories.';
        }

        return [
            'has_conflict'    => $hasConflict,
            'conflict_score'  => $conflictScore,
            'superseded_by'   => $supersededBy,
            'details'         => $details,
        ];
    }

    public function export(Request $request)
    {
        $user = $request->user();

        // Scope export strictly to this user AND their tenant — belt-and-suspenders.
        $memories = Memory::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_scope_id)
            ->get();

        $data = [
            'version' => '1.0',
            'export_date' => now()->toIso8601String(),
            'original_user_id' => $user->id,
            'original_tenant_scope_id' => $user->tenant_scope_id,
            'memories' => $memories->map(function ($memory) {
                $attributes = $memory->getAttributes();
                unset($attributes['id']);
                unset($attributes['tenant_id']);
                unset($attributes['user_id']);
                return $attributes;
            })->all(),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'memories_export_' . now()->format('Y_m_d_H_i_s') . '.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(ImportMemoriesRequest $request)
    {
        // All validation (size, UTF-8, depth, schema, duplicates) is handled
        // by ImportMemoriesRequest::passedValidation() before we reach here.
        // The decoded payload is attached to the request attributes.
        $data = $request->attributes->get('decoded_import');

        $user = $request->user();
        $tenantId = $user->tenant_scope_id;
        $userId = $user->id;

        $originalTenantId = $data['original_tenant_scope_id'] ?? null;
        $originalUserId = $data['original_user_id'] ?? null;
        $isCrossSystem = ($originalTenantId && $originalTenantId !== $tenantId) || ($originalUserId && $originalUserId !== $userId);

        $importedCount = 0;
        $conflicts = [];

        foreach ($data['memories'] as $memoryData) {
            if (empty($memoryData['content_hash']) || empty($memoryData['type'])) {
                continue;
            }

            // check if memory exists
            $existing = Memory::where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('type', $memoryData['type'])
                ->where('content_hash', $memoryData['content_hash'])
                ->first();

            $meta = is_string($memoryData['metadata']) ? json_decode($memoryData['metadata'], true) : ($memoryData['metadata'] ?? []);
            if (!is_array($meta)) $meta = [];

            if ($isCrossSystem) {
                $meta['migrated'] = true;
                $meta['migrated_from_tenant'] = $originalTenantId;
                $meta['migrated_from_user'] = $originalUserId;
            }

            $memoryData['metadata'] = $meta;

            if ($existing) {
                $conflicts[] = [
                    'imported_memory' => $memoryData,
                    'existing_memory' => $existing->toArray(),
                ];
            } else {
                $memoryData['tenant_id'] = $tenantId;
                $memoryData['user_id'] = $userId;
                
                // Remove timestamps if they are set so DB can handle or we respect them
                // Eloquent handles created_at, updated_at
                Memory::create($memoryData);
                $importedCount++;
            }
        }

        if (count($conflicts) > 0) {
            return response()->json([
                'status' => 'conflicts',
                'imported_count' => $importedCount,
                'conflicts' => $conflicts,
            ]);
        }

        return redirect()->back()->with('success', "Successfully imported {$importedCount} memories.");
    }

    public function resolveImport(Request $request)
    {
        $request->validate([
            'resolutions'          => ['required', 'array', 'max:500'],
            'resolutions.*.action' => ['required', 'in:overwrite,skip'],
            'resolutions.*.memory' => ['required', 'array'],
        ]);

        $user     = $request->user();
        $tenantId = $user->tenant_scope_id;
        $userId   = $user->id;

        // Strict allowlist — only these fields can be updated from an import.
        // Prevents mass-assignment of tenant_id, user_id, id, or any future
        // sensitive fields even if the client sends them.
        $allowedFields = [
            'content',
            'normalized_content',
            'content_hash',
            'type',
            'importance',
            'confidence',
            'decay_score',
            'status',
            'access_count',
            'last_accessed_at',
            'last_reinforced_at',
            'archived_at',
            'metadata',
        ];

        $resolvedCount = 0;

        foreach ($request->input('resolutions') as $res) {
            if ($res['action'] === 'skip') {
                continue;
            }

            $mem = $res['memory'];
            if (empty($mem['content_hash']) || empty($mem['type'])) {
                continue;
            }

            $existing = Memory::where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('type', $mem['type'])
                ->where('content_hash', $mem['content_hash'])
                ->first();

            if ($existing) {
                // Only pick whitelisted fields — discard everything else.
                $updateData = array_intersect_key($mem, array_flip($allowedFields));

                $meta = isset($updateData['metadata'])
                    ? (is_string($updateData['metadata'])
                        ? json_decode($updateData['metadata'], true)
                        : $updateData['metadata'])
                    : [];
                $updateData['metadata'] = is_array($meta) ? $meta : [];

                $existing->update($updateData);
                $resolvedCount++;
            }
        }

        return response()->json([
            'status'         => 'success',
            'resolved_count' => $resolvedCount,
        ]);
    }
}
