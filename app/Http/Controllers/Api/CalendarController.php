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
use App\Services\Calendar\OrthodoxCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function day(Request $request, OrthodoxCalendarService $calendar): JsonResponse
    {
        $date = (string) $request->query('date', now()->toDateString());
        $day = $calendar->day($date);

        return response()->json(['data' => $day]);
    }
}
