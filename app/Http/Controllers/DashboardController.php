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

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $userId = (int) $request->user()->id;

        $notes = DB::table('notes')
            ->leftJoin('verses', 'verses.id', '=', 'notes.verse_id')
            ->leftJoin('canonical_books', 'canonical_books.id', '=', 'verses.canonical_book_id')
            ->where('notes.user_id', $userId)
            ->orderByDesc('notes.created_at')
            ->paginate(20, [
                'notes.id',
                'notes.body',
                'notes.visibility',
                'notes.created_at',
                'canonical_books.slug as book_slug',
                'canonical_books.osis_code',
                'verses.chapter_number',
                'verses.verse_number',
            ], 'notes_page');

        $bookmarks = DB::table('bookmarks')
            ->leftJoin('verses', 'verses.id', '=', 'bookmarks.verse_id')
            ->leftJoin('canonical_books', 'canonical_books.id', '=', 'verses.canonical_book_id')
            ->where('bookmarks.user_id', $userId)
            ->orderByDesc('bookmarks.created_at')
            ->paginate(20, [
                'bookmarks.id',
                'bookmarks.title',
                'bookmarks.description',
                'bookmarks.created_at',
                'canonical_books.slug as book_slug',
                'canonical_books.osis_code',
                'verses.chapter_number',
                'verses.verse_number',
            ], 'bookmarks_page');

        $posts = DB::table('social_posts')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(20, ['id', 'body', 'visibility', 'created_at'], 'posts_page');

        $socialStats = [
            'followers' => DB::table('user_follows')->where('followed_id', $userId)->count(),
            'following' => DB::table('user_follows')->where('follower_id', $userId)->count(),
            'friends' => DB::table('user_friendships')
                ->where('status', 'accepted')
                ->where(fn ($query) => $query->where('requester_id', $userId)->orWhere('addressee_id', $userId))
                ->count(),
        ];

        return view('dashboard', [
            'notes' => $notes,
            'bookmarks' => $bookmarks,
            'posts' => $posts,
            'socialStats' => $socialStats,
        ]);
    }

    public function updateNote(Request $request, int $note): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        DB::table('notes')
            ->where('id', $note)
            ->where('user_id', $request->user()->id)
            ->update([
                'body' => trim((string) $validated['body']),
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Заметка обновлена.');
    }

    public function deleteNote(Request $request, int $note): \Illuminate\Http\RedirectResponse
    {
        DB::table('notes')
            ->where('id', $note)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('status', 'Заметка удалена.');
    }

    public function updateBookmark(Request $request, int $bookmark): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::table('bookmarks')
            ->where('id', $bookmark)
            ->where('user_id', $request->user()->id)
            ->update([
                'title' => trim((string) ($validated['title'] ?? '')) ?: null,
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Закладка обновлена.');
    }

    public function deleteBookmark(Request $request, int $bookmark): \Illuminate\Http\RedirectResponse
    {
        DB::table('bookmarks')
            ->where('id', $bookmark)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('status', 'Закладка удалена.');
    }

    public function updatePost(Request $request, int $post): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'visibility' => ['required', 'string', 'in:private,followers,friends,public'],
        ]);

        DB::table('social_posts')
            ->where('id', $post)
            ->where('user_id', $request->user()->id)
            ->update([
                'body' => trim((string) $validated['body']),
                'visibility' => $validated['visibility'],
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Публикация обновлена.');
    }

    public function deletePost(Request $request, int $post): \Illuminate\Http\RedirectResponse
    {
        DB::table('social_posts')
            ->where('id', $post)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('status', 'Публикация удалена.');
    }
}
