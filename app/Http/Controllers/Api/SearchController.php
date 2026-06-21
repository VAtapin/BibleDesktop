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
use App\Services\Bible\VerseSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly VerseSearchService $search) {}

    public function verses(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $translationCode = trim((string) $request->query('translation', ''));
        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        $scope = (string) $request->query('scope', 'all');
        $match = (string) $request->query('match', 'all_words');
        $match = in_array($match, ['all_words', 'phrase', 'partial', 'fuzzy'], true) ? $match : 'all_words';
        $result = $this->search->search($query, $translationCode, $limit, [
            'canonical_only' => $request->boolean('canonical'),
            'deuterocanonical_only' => $request->boolean('apocrypha'),
            'scope' => in_array($scope, ['all', 'old', 'new', 'psalms'], true) ? $scope : 'all',
            'match' => $match,
            'offset' => max(0, (int) $request->query('offset', 0)),
        ]);

        return response()->json([
            'data' => [
                'query' => $query,
                'mode' => $result['mode'],
                'translation_code' => $translationCode ?: null,
                'scope' => $scope,
                'match' => $match,
                'results' => $result['results'],
            ],
        ]);
    }
}
