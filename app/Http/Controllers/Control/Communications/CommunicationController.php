<?php

namespace App\Http\Controllers\Control\Communications;

use App\DTOs\Control\Communications\CommunicationContext;
use App\Enums\CommunicationTemplate;
use App\Http\Controllers\Controller;
use App\Models\OperationalCommunicationLog;
use App\Services\Control\Communications\CommunicationService;
use App\Services\Email\EmailTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CommunicationController extends Controller
{
    public function __construct(
        private CommunicationService $communicationService
    ) {}

    public function send(Request $request)
    {
        $this->authorize('send', OperationalCommunicationLog::class);

        $validated = $request->validate([
            'recipient_uuid' => 'required|string',
            'recipient_type' => 'required|in:user,tenant',
            'recipient_email' => 'required|email',
            'recipient_name' => 'required|string',
            'template' => 'required|string',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:10000',
        ]);

        $template = CommunicationTemplate::tryFrom($validated['template']);
        
        if (!$template) {
            return response()->json(['message' => 'Invalid template selected.'], 422);
        }

        $context = new CommunicationContext(
            recipientUuid: $validated['recipient_uuid'],
            recipientType: $validated['recipient_type'],
            recipientEmail: $validated['recipient_email'],
            recipientName: $validated['recipient_name'],
            senderId: $request->user()->id,
            senderName: $request->user()->name,
            template: $template,
            subject: $validated['subject'],
            body: $validated['body']
        );

        try {
            $success = $this->communicationService->dispatch($context);

            if (!$success) {
                return response()->json([
                    'message' => 'Communication could not be sent (Rate limited or duplicate request).'
                ], 429);
            }

            return response()->json([
                'message' => 'Email queued successfully.'
            ]);
            
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch communication', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to queue email. Please try again later.'
            ], 500);
        }
    }

    public function preview(Request $request)
    {
        $this->authorize('send', OperationalCommunicationLog::class);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:10000',
        ]);

        $html = view('emails.control.communication', [
            'theme' => new EmailTheme(),
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'appName' => config('app.name', 'Trace.Mem'),
            'appUrl' => config('app.url', 'https://tracemem.one'),
            'support_email' => 'noreply@contact.tracemem.one',
            'currentYear' => date('Y'),
        ])->render();

        return response()->json([
            'html' => $html
        ]);
    }

    public function history(Request $request, string $type, string $id)
    {
        $this->authorize('view', OperationalCommunicationLog::class);

        if (!in_array($type, ['user', 'tenant'])) {
            abort(404);
        }

        $cacheKey = "control:communications:history:{$type}:{$id}";

        $history = Cache::remember($cacheKey, 60, function () use ($type, $id) {
            return OperationalCommunicationLog::where('recipient_type', $type)
                ->where('recipient_uuid', $id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'subject' => $log->rendered_subject,
                    'template' => CommunicationTemplate::tryFrom($log->template_name)?->label() ?? 'Custom',
                    'sender' => $log->sender_name ?? 'System',
                    'status' => $log->status,
                    'sent_at' => $log->sent_at ? $log->sent_at->diffForHumans() : ($log->created_at ? $log->created_at->diffForHumans() : 'Unknown'),
                    'body' => $log->rendered_body,
                ]);
        });

        return response()->json([
            'history' => $history
        ]);
    }
}
