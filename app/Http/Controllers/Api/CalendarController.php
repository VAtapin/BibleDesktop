<?php

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

        return response()->json(['data' => $calendar->day($date)]);
    }
}
