<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramUpdateHandler $handler): JsonResponse
    {
        $secret = config('telegram.webhook_secret');

        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid Telegram webhook secret.');
        }

        return response()->json([
            'ok' => true,
            'actions' => $handler->handle($request->all()),
        ]);
    }
}
