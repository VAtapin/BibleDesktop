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

namespace App\Http\Controllers;

use App\Support\BibleReferenceFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReaderDataController extends Controller
{
    public function notes(Request $request, int $verse): JsonResponse
    {
        $userId = $this->userId($request);

        $notes = DB::table('notes')
            ->where('user_id', $userId)
            ->where('verse_id', $verse)
            ->where('visibility', 'private')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'body', 'visibility', 'created_at', 'updated_at'])
            ->map(fn ($note): array => [
                'id' => (int) $note->id,
                'body' => (string) $note->body,
                'visibility' => (string) $note->visibility,
                'created_at' => (string) $note->created_at,
                'updated_at' => (string) $note->updated_at,
            ]);

        return response()->json(['data' => ['notes' => $notes]]);
    }

    public function storeNote(Request $request, int $verse): JsonResponse
    {
        $this->ensureVerseExists($verse);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $now = now();
        $noteId = DB::table('notes')->insertGetId([
            'user_id' => $this->userId($request),
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

    public function bookmarks(Request $request): JsonResponse
    {
        $bookmarks = DB::table('bookmarks')
            ->leftJoin('verses', 'verses.id', '=', 'bookmarks.verse_id')
            ->leftJoin('canonical_books', 'canonical_books.id', '=', 'verses.canonical_book_id')
            ->where('bookmarks.user_id', $this->userId($request))
            ->orderByDesc('bookmarks.created_at')
            ->limit(100)
            ->get([
                'bookmarks.id',
                'bookmarks.title',
                'bookmarks.description',
                'bookmarks.created_at',
                'canonical_books.slug as book_slug',
                'canonical_books.osis_code',
                'verses.chapter_number',
                'verses.verse_number',
            ])
            ->map(fn ($bookmark): array => [
                'id' => (int) $bookmark->id,
                'title' => (string) ($bookmark->title ?? ''),
                'description' => (string) ($bookmark->description ?? ''),
                'reference' => BibleReferenceFormatter::format(
                    $bookmark->book_slug,
                    $bookmark->osis_code,
                    (int) $bookmark->chapter_number,
                    (int) $bookmark->verse_number,
                ),
                'created_at' => (string) $bookmark->created_at,
            ]);

        return response()->json(['data' => ['bookmarks' => $bookmarks]]);
    }

    public function storeBookmark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verse_id' => ['required', 'integer', 'exists:verses,id'],
            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $now = now();
        DB::table('bookmarks')->updateOrInsert(
            [
                'user_id' => $this->userId($request),
                'verse_id' => (int) $validated['verse_id'],
            ],
            [
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return response()->json(['data' => ['saved' => true]], 201);
    }

    public function feed(Request $request): JsonResponse
    {
        $userId = $this->userId($request);
        $bookSlug = trim((string) $request->query('book', ''));

        $posts = DB::table('social_posts')
            ->join('users', 'users.id', '=', 'social_posts.user_id')
            ->leftJoin('verses', 'verses.id', '=', 'social_posts.verse_id')
            ->leftJoin('canonical_books', 'canonical_books.id', '=', 'verses.canonical_book_id')
            ->where(function ($query) use ($userId): void {
                $query
                    ->where('social_posts.user_id', $userId)
                    ->orWhere('social_posts.visibility', 'public')
                    ->orWhere(function ($followersQuery) use ($userId): void {
                        $followersQuery
                            ->where('social_posts.visibility', 'followers')
                            ->whereExists(function ($exists) use ($userId): void {
                                $exists
                                    ->selectRaw('1')
                                    ->from('user_follows')
                                    ->whereColumn('user_follows.followed_id', 'social_posts.user_id')
                                    ->where('user_follows.follower_id', $userId);
                            });
                    })
                    ->orWhere(function ($friendsQuery) use ($userId): void {
                        $friendsQuery
                            ->where('social_posts.visibility', 'friends')
                            ->whereExists(function ($exists) use ($userId): void {
                                $exists
                                    ->selectRaw('1')
                                    ->from('user_friendships')
                                    ->where('user_friendships.status', 'accepted')
                                    ->where(function ($friendshipQuery) use ($userId): void {
                                        $friendshipQuery
                                            ->where(function ($pairQuery) use ($userId): void {
                                                $pairQuery
                                                    ->whereColumn('user_friendships.requester_id', 'social_posts.user_id')
                                                    ->where('user_friendships.addressee_id', $userId);
                                            })
                                            ->orWhere(function ($pairQuery) use ($userId): void {
                                                $pairQuery
                                                    ->whereColumn('user_friendships.addressee_id', 'social_posts.user_id')
                                                    ->where('user_friendships.requester_id', $userId);
                                            });
                                    });
                            });
                    });
            })
            ->when($bookSlug !== '', fn ($query) => $query->where('canonical_books.slug', $bookSlug))
            ->orderByDesc('social_posts.created_at')
            ->limit(30)
            ->get([
                'social_posts.id',
                'social_posts.verse_id',
                'social_posts.body',
                'social_posts.visibility',
                'social_posts.created_at',
                'users.name as user_name',
                'canonical_books.slug as book_slug',
                'canonical_books.osis_code',
                'verses.chapter_number',
                'verses.verse_number',
            ])
            ->map(fn ($post): array => [
                'id' => (int) $post->id,
                'author' => (string) $post->user_name,
                'body' => (string) $post->body,
                'visibility' => (string) $post->visibility,
                'verse_id' => $post->verse_id === null ? null : (int) $post->verse_id,
                'reference' => $post->chapter_number ? BibleReferenceFormatter::format(
                    $post->book_slug,
                    $post->osis_code,
                    (int) $post->chapter_number,
                    (int) $post->verse_number,
                ) : null,
                'book_slug' => $post->book_slug === null ? null : (string) $post->book_slug,
                'book_name' => null,
                'book_short_name' => null,
                'chapter_number' => $post->chapter_number === null ? null : (int) $post->chapter_number,
                'verse_number' => $post->verse_number === null ? null : (int) $post->verse_number,
                'created_at' => (string) $post->created_at,
            ]);

        return response()->json(['data' => ['posts' => $posts]]);
    }

    public function storeFeedPost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'verse_id' => ['nullable', 'integer', 'exists:verses,id'],
            'visibility' => ['nullable', 'string', 'in:followers,friends,public,private'],
        ]);

        DB::table('social_posts')->insert([
            'user_id' => $this->userId($request),
            'verse_id' => $validated['verse_id'] ?? null,
            'visibility' => $validated['visibility'] ?? 'followers',
            'body' => trim((string) $validated['body']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['saved' => true]], 201);
    }

    private function userId(Request $request): int
    {
        return (int) $request->user()?->id ?: abort(401, 'Authentication required.');
    }

    private function ensureVerseExists(int $verse): void
    {
        if (DB::table('verses')->where('id', $verse)->doesntExist()) {
            abort(404, 'Verse not found.');
        }
    }
}
