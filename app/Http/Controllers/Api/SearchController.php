<?php

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
        $result = $this->search->search($query, $translationCode, $limit);

        return response()->json([
            'data' => [
                'query' => $query,
                'mode' => $result['mode'],
                'translation_code' => $translationCode ?: null,
                'results' => $result['results'],
            ],
        ]);
    }
}
