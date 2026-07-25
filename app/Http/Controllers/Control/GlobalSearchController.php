<?php

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Team;
use App\Models\Memory;
use App\Models\ApiKey;
use Illuminate\Support\Str;

class GlobalSearchController extends Controller
{
    /**
     * Perform global search securely.
     */
    public function search(Request $request)
    {
        // 1. Strict validation: prevent massive payloads
        $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        // 2. Sanitize to prevent any HTML/XSS inside the DB query logs or reflections
        $rawQuery = trim($request->input('q'));
        $query = strip_tags($rawQuery);
        $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

        if (empty($query)) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // 3. Eloquent uses Parameterized Queries securely (preventing SQL Injection)
        
        // Search Users
        $users = User::where('email', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at']);
            
        foreach ($users as $user) {
            $results[] = [
                'type' => 'User',
                'id' => $user->id,
                'title' => $user->name,
                'subtitle' => $user->email,
                'url' => "/users/{$user->id}",
            ];
        }

        // Search Teams (Workspaces)
        $teams = Team::where('name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name']);
            
        foreach ($teams as $team) {
            $results[] = [
                'type' => 'Workspace',
                'id' => $team->id,
                'title' => $team->name,
                'subtitle' => 'Workspace',
                'url' => "/tenants/{$team->id}",
            ];
        }

        // Search Memory (By exact ID or partial)
        // Only if it looks like an ID or numeric/uuid depending on memory ID type
        $memories = Memory::where('id', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'team_id']);
            
        foreach ($memories as $memory) {
            $results[] = [
                'type' => 'Memory',
                'id' => $memory->id,
                'title' => "Memory ID: {$memory->id}",
                'subtitle' => "Workspace: {$memory->team_id}",
                'url' => "/memories/{$memory->id}",
            ];
        }
        
        // Search API Keys (By name or prefix)
        $apiKeys = ApiKey::where('name', 'LIKE', "%{$query}%")
            ->orWhere('token_prefix', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'token_prefix']);
            
        foreach ($apiKeys as $key) {
            $results[] = [
                'type' => 'API Key',
                'id' => $key->id,
                'title' => $key->name,
                'subtitle' => "Prefix: {$key->token_prefix}",
                'url' => "/apikeys/{$key->id}",
            ];
        }

        // Return secure JSON
        return response()->json([
            'results' => $results,
            'query' => $query,
        ]);
    }
}
