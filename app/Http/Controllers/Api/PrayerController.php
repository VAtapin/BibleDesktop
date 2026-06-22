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

class PrayerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $language = trim((string) $request->query('language', 'ru')) ?: 'ru';

        $prayers = DB::table('prayers')
            ->where('is_public', true)
            ->where(fn ($query) => $query
                ->where('language_code', $language)
                ->orWhere('language_code', 'ru'))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'language_code', 'category', 'liturgy_key', 'title', 'short_title', 'body'])
            ->unique('id')
            ->values()
            ->map(fn ($prayer): array => [
                'id' => (int) $prayer->id,
                'language_code' => (string) $prayer->language_code,
                'category' => (string) $prayer->category,
                'liturgy_key' => $prayer->liturgy_key === null ? null : (string) $prayer->liturgy_key,
                'title' => (string) $prayer->title,
                'short_title' => $prayer->short_title === null ? null : (string) $prayer->short_title,
                'excerpt' => str($prayer->body)->stripTags()->squish()->limit(120)->toString(),
            ]);

        return response()->json(['data' => $prayers]);
    }

    public function show(int $prayer): JsonResponse
    {
        $row = DB::table('prayers')
            ->where('is_public', true)
            ->where('id', $prayer)
            ->first(['id', 'language_code', 'category', 'liturgy_key', 'title', 'short_title', 'body']);

        abort_if(! $row, 404);

        return response()->json(['data' => [
            'id' => (int) $row->id,
            'language_code' => (string) $row->language_code,
            'category' => (string) $row->category,
            'liturgy_key' => $row->liturgy_key === null ? null : (string) $row->liturgy_key,
            'title' => (string) $row->title,
            'short_title' => $row->short_title === null ? null : (string) $row->short_title,
            'body' => (string) $row->body,
        ]]);
    }
}
