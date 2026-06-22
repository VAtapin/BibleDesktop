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
            ->get(['id', 'language_code', 'category', 'liturgy_key', 'title', 'short_title', 'intro', 'body'])
            ->unique('id')
            ->values()
            ->map(fn ($prayer): array => [
                'id' => (int) $prayer->id,
                'language_code' => (string) $prayer->language_code,
                'category' => (string) $prayer->category,
                'liturgy_key' => $prayer->liturgy_key === null ? null : (string) $prayer->liturgy_key,
                'title' => (string) $prayer->title,
                'short_title' => $prayer->short_title === null ? null : (string) $prayer->short_title,
                'intro' => $prayer->intro === null ? null : (string) $prayer->intro,
                'excerpt' => str($prayer->intro ?: $prayer->body)->stripTags()->squish()->limit(80)->toString(),
            ]);

        return response()->json(['data' => $prayers]);
    }

    public function show(int $prayer): JsonResponse
    {
        $row = DB::table('prayers')
            ->where('is_public', true)
            ->where('id', $prayer)
            ->first(['id', 'language_code', 'category', 'liturgy_key', 'title', 'short_title', 'intro', 'body', 'source_url']);

        abort_if(! $row, 404);

        $sections = DB::table('prayer_sections')
            ->where('prayer_id', $prayer)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'sort_order'])
            ->map(fn ($section): array => [
                'id' => (int) $section->id,
                'title' => $section->title === null ? null : (string) $section->title,
                'sort_order' => (int) $section->sort_order,
            ]);

        return response()->json(['data' => [
            'id' => (int) $row->id,
            'language_code' => (string) $row->language_code,
            'category' => (string) $row->category,
            'liturgy_key' => $row->liturgy_key === null ? null : (string) $row->liturgy_key,
            'title' => (string) $row->title,
            'short_title' => $row->short_title === null ? null : (string) $row->short_title,
            'intro' => $row->intro === null ? null : (string) $row->intro,
            'body' => (string) $row->body,
            'source_url' => $row->source_url === null ? null : (string) $row->source_url,
            'sections' => $sections,
        ]]);
    }

    public function section(int $prayer, int $section): JsonResponse
    {
        $row = DB::table('prayer_sections')
            ->join('prayers', 'prayers.id', '=', 'prayer_sections.prayer_id')
            ->where('prayers.is_public', true)
            ->where('prayer_sections.prayer_id', $prayer)
            ->where('prayer_sections.id', $section)
            ->first([
                'prayer_sections.id',
                'prayer_sections.title',
                'prayer_sections.body',
                'prayer_sections.sort_order',
            ]);

        abort_if(! $row, 404);

        return response()->json(['data' => [
            'id' => (int) $row->id,
            'title' => $row->title === null ? null : (string) $row->title,
            'body' => (string) $row->body,
            'sort_order' => (int) $row->sort_order,
        ]]);
    }
}
