{{-- 
/**
 * BibleDesktop - Bible study desktop and web application.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 * @link https://bible-desktop.com/
 * @copyright 2026 Atapin Vladimir / Bible Media
 * @version 1.0.0
 */
--}}

<div class="bd-telegram-dialog">
    @forelse ($messages as $message)
        <article @class([
            'bd-telegram-dialog-message',
            'bd-telegram-dialog-message-outbound' => $message->direction === 'outbound',
        ])>
            <div>
                <strong>
                    {{ $message->direction === 'outbound' ? 'Администратор' : ($message->telegram_username ?: $message->telegram_id) }}
                </strong>
                <span>{{ optional($message->created_at)->format('d.m.Y H:i') }}</span>
            </div>
            <p>{{ $message->body }}</p>
            @if ($message->admin_reply)
                <div class="bd-telegram-dialog-reply">
                    <strong>Ответ администратора</strong>
                    <p>{{ $message->admin_reply }}</p>
                </div>
            @endif
        </article>
    @empty
        <p>Сообщений пока нет.</p>
    @endforelse
</div>

<style>
    .bd-telegram-dialog {
        display: grid;
        gap: 12px;
        max-height: 55vh;
        overflow: auto;
        padding-right: 4px;
    }

    .bd-telegram-dialog-message {
        width: min(88%, 720px);
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 12px;
        background: rgba(30, 41, 59, 0.7);
        padding: 12px 14px;
    }

    .bd-telegram-dialog-message-outbound {
        justify-self: end;
        background: rgba(146, 64, 14, 0.45);
    }

    .bd-telegram-dialog-message div {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        color: rgb(203, 213, 225);
        font-size: 12px;
    }

    .bd-telegram-dialog-message p {
        margin: 8px 0 0;
        white-space: pre-wrap;
    }

    .bd-telegram-dialog-reply {
        margin-top: 12px;
        border-top: 1px solid rgba(148, 163, 184, 0.28);
        padding-top: 10px;
    }
</style>
