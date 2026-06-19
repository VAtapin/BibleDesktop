<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramBotClient;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramUpdateHandler $handler, TelegramBotClient $client): JsonResponse
    {
        $secret = config('telegram.webhook_secret');

        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid Telegram webhook secret.');
        }

        $actions = $handler->handle($request->all());
        $sent = 0;

        if (config('telegram.send_responses')) {
            foreach ($actions as $action) {
                $client->send($action['method'], $action['payload']);
                $sent++;
            }
        }

        return response()->json([
            'ok' => true,
            'actions' => $actions,
            'sent' => $sent,
        ]);
    }
}
