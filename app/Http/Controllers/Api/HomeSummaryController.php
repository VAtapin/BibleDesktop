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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supplies the lightweight counters needed by the initial dashboard.
 */
class HomeSummaryController extends Controller
{
    /**
     * Return public content counters without loading the reader catalogue.
     */
    public function __invoke(): JsonResponse
    {
        $summary = Cache::remember('public-home-summary-v1', now()->addMinutes(5), fn (): array => [
            'bibles' => $this->countPublicRows('modules', [
                ['type', '=', 'bible'],
                ['is_active', '=', true],
                ['is_public', '=', true],
            ]),
            'recipes' => $this->countPublicRows('recipes', [
                ['status', '=', 'approved'],
                ['is_public', '=', true],
            ]),
            'quizzes' => $this->countPublicRows('quizzes', [['is_public', '=', true]]),
            'tours' => $this->countPublicRows('virtual_tours', [['is_public', '=', true]]),
            'prayers' => $this->countPublicRows('prayers', [['is_public', '=', true]]),
            'materials' => $this->countPublicRows('useful_links', [['is_public', '=', true]]),
            'faith_questions' => $this->countPublicRows('faith_questions', [['is_public', '=', true]]),
        ]);

        return response()
            ->json(['data' => $summary])
            ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=300');
    }

    /**
     * Count rows only when the optional content table and requested columns exist.
     *
     * @param  array<int, array{0: string, 1: string, 2: mixed}>  $conditions
     */
    private function countPublicRows(string $table, array $conditions): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        foreach ($conditions as [$column, $operator, $value]) {
            if (Schema::hasColumn($table, $column)) {
                $query->where($column, $operator, $value);
            }
        }

        return $query->count();
    }
}
