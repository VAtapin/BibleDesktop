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
            ->limit(50)
            ->get([
                'notes.id',
                'notes.body',
                'notes.visibility',
                'notes.created_at',
                'canonical_books.slug as book_slug',
                'canonical_books.osis_code',
                'verses.chapter_number',
                'verses.verse_number',
            ]);

        $bookmarks = DB::table('bookmarks')
            ->leftJoin('verses', 'verses.id', '=', 'bookmarks.verse_id')
            ->leftJoin('canonical_books', 'canonical_books.id', '=', 'verses.canonical_book_id')
            ->where('bookmarks.user_id', $userId)
            ->orderByDesc('bookmarks.created_at')
            ->limit(50)
            ->get([
                'bookmarks.id',
                'bookmarks.title',
                'bookmarks.description',
                'bookmarks.created_at',
                'canonical_books.slug as book_slug',
                'canonical_books.osis_code',
                'verses.chapter_number',
                'verses.verse_number',
            ]);

        $posts = DB::table('social_posts')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'body', 'visibility', 'created_at']);

        return view('dashboard', [
            'notes' => $notes,
            'bookmarks' => $bookmarks,
            'posts' => $posts,
        ]);
    }
}
