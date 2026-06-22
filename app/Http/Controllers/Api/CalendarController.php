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
use App\Services\Bible\PassageTextService;
use App\Services\Calendar\OrthodoxCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function day(Request $request, OrthodoxCalendarService $calendar, PassageTextService $passages): JsonResponse
    {
        $date = (string) $request->query('date', now()->toDateString());
        $translationCode = trim((string) $request->query('translation', ''));
        $day = $calendar->day($date);

        if ($translationCode !== '') {
            $day['readings'] = $day['readings']
                ->map(fn (array $reading): array => $reading + [
                    'text' => $passages->bodyText($reading['passage_ref'], $translationCode, 80),
                ]);
        }

        return response()->json(['data' => $day]);
    }
}
