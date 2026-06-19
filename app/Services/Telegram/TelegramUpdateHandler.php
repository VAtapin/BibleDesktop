<?php

namespace App\Services\Telegram;

use App\Services\Calendar\OrthodoxCalendarService;
use Illuminate\Support\Facades\DB;

class TelegramUpdateHandler
{
    public function __construct(private readonly OrthodoxCalendarService $calendar) {}

    /**
     * @param array<string, mixed> $update
     * @return array<int, array{method: string, payload: array<string, mixed>}>
     */
    public function handle(array $update): array
    {
        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return [];
        }

        $chat = $message['chat'] ?? null;
        $chatId = is_array($chat) ? ($chat['id'] ?? null) : null;

        if (! $chatId) {
            return [];
        }

        $text = trim((string) ($message['text'] ?? ''));
        $command = mb_strtolower(strtok($text, ' ') ?: '');

        return [[
            'method' => 'sendMessage',
            'payload' => [
                'chat_id' => $chatId,
                'text' => match ($command) {
                    '/start' => $this->startText(),
                    '/help' => $this->helpText(),
                    '/search' => $this->searchText($text),
                    '/today', '/calendar' => $this->calendarText(),
                    '/gospel' => $this->readingPlaceholderText('Евангелие'),
                    '/apostle' => $this->readingPlaceholderText('Апостол'),
                    '/fasting' => $this->fastingText(),
                    '/settings' => $this->settingsText(),
                    '/random' => $this->randomVerseText(),
                    default => $this->helpText(),
                },
            ],
        ]];
    }

    private function startText(): string
    {
        return "Bible Desktop\n\nДоступные команды: /help, /random";
    }

    private function helpText(): string
    {
        return "Команды:\n/start - начать\n/help - помощь\n/random - случайный стих\n/search текст - поиск\n/today - чтения дня\n/settings - настройки";
    }

    private function randomVerseText(): string
    {
        $translationCode = (string) config('telegram.default_translation', 'L1_RST');
        $verse = DB::table('verse_texts')
            ->join('translations', 'translations.id', '=', 'verse_texts.translation_id')
            ->join('verses', 'verses.id', '=', 'verse_texts.verse_id')
            ->where('translations.code', $translationCode)
            ->inRandomOrder()
            ->first([
                'verses.osis_ref',
                'verse_texts.text',
            ]);

        if (! $verse) {
            return 'Стихи ещё не импортированы.';
        }

        return trim("{$verse->osis_ref}\n{$verse->text}");
    }

    private function searchText(string $text): string
    {
        $query = trim(mb_substr($text, mb_strlen('/search')));

        if (mb_strlen($query) < 2) {
            return 'Напишите запрос после команды: /search сотворил';
        }

        $translationCode = (string) config('telegram.default_translation', 'L1_RST');
        $results = DB::table('verse_texts')
            ->join('translations', 'translations.id', '=', 'verse_texts.translation_id')
            ->join('verses', 'verses.id', '=', 'verse_texts.verse_id')
            ->where('translations.code', $translationCode)
            ->where('verse_texts.text_plain', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%')
            ->orderBy('verses.id')
            ->limit(5)
            ->get(['verses.osis_ref', 'verse_texts.text']);

        if ($results->isEmpty()) {
            return "Ничего не найдено: {$query}";
        }

        return $results
            ->map(fn ($row) => "{$row->osis_ref} {$row->text}")
            ->implode("\n\n");
    }

    private function calendarText(): string
    {
        $day = $this->calendar->day(now()->toDateString());

        if ($day['events']->isEmpty()) {
            return 'Календарные события ещё не импортированы.';
        }

        $events = $day['events']
            ->take(8)
            ->map(fn (array $event) => '- '.$event['name'])
            ->implode("\n");

        return "Календарь на {$day['date']}\n{$events}";
    }

    private function fastingText(): string
    {
        $day = $this->calendar->day(now()->toDateString());

        if ($day['fasting_events']->isEmpty()) {
            return 'Постных правил на сегодня не найдено.';
        }

        $events = $day['fasting_events']
            ->take(8)
            ->map(fn (array $event) => '- '.$event['name'])
            ->implode("\n");

        return "Пост на {$day['date']}\n{$events}";
    }

    private function readingPlaceholderText(string $readingName): string
    {
        return "Чтение дня ({$readingName}) ещё не импортировано: в legacy-дампе не найден отдельный источник чтений.";
    }

    private function settingsText(): string
    {
        return 'Настройки пока доступны в следующем этапе: язык, перевод, уведомления.';
    }
}
