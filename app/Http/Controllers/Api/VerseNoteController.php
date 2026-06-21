<?php

/**
 * BibleDesktop - Bible study desktop and web application.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 *
 * @link https://bible-desktop.com/
 *
 * @copyright 2026 Atapin Vladimir / Bible Media
 *
 * @version 1.0.0
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerseNoteController extends Controller
{
    public function index(Request $request, int $verse): JsonResponse
    {
        $userId = $this->demoUserId($request);

        $notes = DB::table('notes')
            ->where('user_id', $userId)
            ->where('verse_id', $verse)
            ->where('visibility', 'private')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'body', 'visibility', 'created_at', 'updated_at'])
            ->map(fn ($note) => [
                'id' => (int) $note->id,
                'body' => (string) $note->body,
                'visibility' => (string) $note->visibility,
                'created_at' => (string) $note->created_at,
                'updated_at' => (string) $note->updated_at,
            ]);

        return response()->json(['data' => ['notes' => $notes]]);
    }

    public function store(Request $request, int $verse): JsonResponse
    {
        if (DB::table('verses')->where('id', $verse)->doesntExist()) {
            abort(404, 'Verse not found.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $now = now();
        $noteId = DB::table('notes')->insertGetId([
            'user_id' => $this->demoUserId($request),
            'verse_id' => $verse,
            'visibility' => 'private',
            'body' => trim((string) $validated['body']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $note = DB::table('notes')->where('id', $noteId)->first(['id', 'body', 'visibility', 'created_at', 'updated_at']);

        return response()->json([
            'data' => [
                'note' => [
                    'id' => (int) $note->id,
                    'body' => (string) $note->body,
                    'visibility' => (string) $note->visibility,
                    'created_at' => (string) $note->created_at,
                    'updated_at' => (string) $note->updated_at,
                ],
            ],
        ], 201);
    }

    private function demoUserId(Request $request): int
    {
        $headerUserId = (int) $request->header('X-Demo-User-Id', 0);

        if ($headerUserId > 0 && DB::table('users')->where('id', $headerUserId)->exists()) {
            return $headerUserId;
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'demo@bibledesktop.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('demo-user-disabled-password'),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return (int) DB::table('users')->where('email', 'demo@bibledesktop.local')->value('id');
    }
}
