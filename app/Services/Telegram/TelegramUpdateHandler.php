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

namespace App\Services\Telegram;

use App\Services\Bible\PassageTextService;
use App\Services\Bible\VerseSearchService;
use App\Services\Calendar\OrthodoxCalendarService;
use App\Support\BibleReferenceFormatter;
use App\Support\StrongText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TelegramUpdateHandler
{
    private const SCOPES = [
        'all' => 'Вся каноническая Библия',
        'old' => 'Ветхий Завет',
        'new' => 'Новый Завет',
        'psalms' => 'Псалтирь',
    ];

    private const SEARCH_SCOPES = [
        'canonical' => 'Канонические книги',
        'old' => 'Ветхий Завет',
        'new' => 'Новый Завет',
        'psalms' => 'Псалтирь',
        'apocrypha' => 'Апокрифы',
    ];

    public function __construct(
        private readonly OrthodoxCalendarService $calendar,
        private readonly PassageTextService $passageText,
        private readonly VerseSearchService $verseSearch,
    ) {}

    /**
     * @param  array<string, mixed>  $update
     * @return array<int, array{method: string, payload: array<string, mixed>}>
     */
    public function handle(array $update): array
    {
        $callback = $update['callback_query'] ?? null;

        if (is_array($callback)) {
            return $this->handleCallback($callback);
        }

        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return [];
        }

        return $this->handleMessage($message);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<int, array{method: string, payload: array<string, mixed>}>
     */
    private function handleMessage(array $message): array
    {
        $chatId = $this->chatId($message);

        if (! $chatId) {
            return [];
        }

        $telegramId = $this->telegramUserId($message, $chatId);
        $settings = $this->settingsFor($telegramId, $message['from'] ?? []);
        $text = trim((string) ($message['text'] ?? ''));
        $command = $this->command($text);
        $isGroupChat = $this->isGroupChat($message);

        if ($command === '' && (bool) ($settings['awaiting_search'] ?? false)) {
            $settings['awaiting_search'] = false;
            $settings['last_search_query'] = $text;
            $settings['last_search_offset'] = 5;
            $this->saveSettings($telegramId, $settings);

            return [$this->messageAction($chatId, $this->searchText($text, $settings), [
                'reply_markup' => $this->searchMoreKeyboard(),
            ])];
        }

        if ($command === '' && (bool) ($settings['awaiting_contact'] ?? false)) {
            $settings['awaiting_contact'] = false;
            $this->saveSettings($telegramId, $settings);
            $this->storeTelegramMessage($telegramId, $chatId, $message, $text);

            return [$this->messageAction($chatId, 'Сообщение принято. Администратор сможет ответить вам здесь.')];
        }

        $action = match ($command) {
            '/start' => $this->startAction($chatId),
            '/help' => $this->messageAction($chatId, $this->helpText()),
            '/search' => $this->searchAction($chatId, $telegramId, $text, $settings),
            '/ask', '/contact', '/message' => $this->contactAction($chatId, $telegramId, $text, $settings, $message),
            '/today', '/calendar' => $this->calendarAction($chatId, $settings),
            '/gospel' => $this->messageAction($chatId, $this->readingText('gospel', 'Евангелие', $settings)),
            '/apostle' => $this->messageAction($chatId, $this->readingText('apostle', 'Апостол', $settings)),
            '/fasting' => $this->messageAction($chatId, $this->fastingText()),
            '/prayers', '/prayer' => $this->prayersAction($chatId),
            '/materials', '/apps' => $this->materialsAction($chatId),
            '/tests', '/quizzes' => $this->testsAction($chatId),
            '/settings' => $this->messageAction($chatId, $this->settingsText($settings), [
                'reply_markup' => $this->settingsKeyboard($settings),
            ]),
            '/random' => $this->messageAction($chatId, $this->randomVerseText($settings, $this->scopeFromRandomCommand($text))),
            default => $isGroupChat ? null : $this->messageAction($chatId, $this->helpText()),
        };

        return $action === null ? [] : [$action];
    }

    /**
     * @param  array<string, mixed>  $callback
     * @return array<int, array{method: string, payload: array<string, mixed>}>
     */
    private function handleCallback(array $callback): array
    {
        $message = is_array($callback['message'] ?? null) ? $callback['message'] : [];
        $chatId = $this->chatId($message);
        $telegramId = $this->telegramUserId($callback, $chatId ?: null);
        $settings = $this->settingsFor($telegramId, $callback['from'] ?? []);
        $data = (string) ($callback['data'] ?? '');
        $notice = 'Настройки обновлены.';
        $actions = [];

        if (str_starts_with($data, 'settings:translation:')) {
            $code = mb_substr($data, mb_strlen('settings:translation:'));

            if ($this->translationExists($code)) {
                $settings['translation_code'] = $code;
            }
        } elseif (str_starts_with($data, 'settings:scope:')) {
            $scope = mb_substr($data, mb_strlen('settings:scope:'));

            if (isset(self::SCOPES[$scope])) {
                $settings['random_scope'] = $scope;
            }
        } elseif (str_starts_with($data, 'settings:search_scope:')) {
            $scope = mb_substr($data, mb_strlen('settings:search_scope:'));

            if (isset(self::SEARCH_SCOPES[$scope])) {
                $settings['search_scope'] = $scope;
            }
        } elseif (str_starts_with($data, 'calendar:reading:')) {
            $readingId = (int) mb_substr($data, mb_strlen('calendar:reading:'));
            $text = $this->calendarReadingTextById($readingId, $settings);

            $actions[] = [
                'method' => 'answerCallbackQuery',
                'payload' => [
                    'callback_query_id' => $callback['id'] ?? '',
                    'text' => 'Открываю чтение',
                ],
            ];

            if ($chatId) {
                $actions[] = $this->messageAction($chatId, $text);
            }

            return $actions;
        } elseif (str_starts_with($data, 'prayer_full:')) {
            $prayerId = (int) mb_substr($data, mb_strlen('prayer_full:'));
            $texts = $this->prayerFullTextsById($prayerId);

            $actions[] = [
                'method' => 'answerCallbackQuery',
                'payload' => [
                    'callback_query_id' => $callback['id'] ?? '',
                    'text' => 'Открываю полный текст',
                ],
            ];

            if ($chatId) {
                foreach ($texts as $text) {
                    $actions[] = $this->messageAction($chatId, $text);
                }
            }

            return $actions;
        } elseif (str_starts_with($data, 'prayer:')) {
            $prayerId = (int) mb_substr($data, mb_strlen('prayer:'));
            $payload = $this->prayerIntroPayloadById($prayerId);

            $actions[] = [
                'method' => 'answerCallbackQuery',
                'payload' => [
                    'callback_query_id' => $callback['id'] ?? '',
                    'text' => 'Открываю молитву',
                ],
            ];

            if ($chatId) {
                $actions[] = $this->messageAction($chatId, $payload['text'], [
                    'reply_markup' => $payload['reply_markup'],
                ]);
            }

            return $actions;
        } elseif ($data === 'start:materials') {
            $actions[] = [
                'method' => 'answerCallbackQuery',
                'payload' => [
                    'callback_query_id' => $callback['id'] ?? '',
                    'text' => 'Полезные материалы',
                ],
            ];

            if ($chatId) {
                $actions[] = $this->materialsAction($chatId);
            }

            return $actions;
        } elseif ($data === 'start:tests') {
            $actions[] = [
                'method' => 'answerCallbackQuery',
                'payload' => [
                    'callback_query_id' => $callback['id'] ?? '',
                    'text' => 'Вопросы о вере',
                ],
            ];

            if ($chatId) {
                $actions[] = $this->testsAction($chatId);
            }

            return $actions;
        } elseif ($data === 'start:about') {
            $actions[] = [
                'method' => 'answerCallbackQuery',
                'payload' => [
                    'callback_query_id' => $callback['id'] ?? '',
                    'text' => 'О боте',
                ],
            ];

            if ($chatId) {
                $actions[] = $this->messageAction($chatId, "Бот «Библия» помогает читать Священное Писание, искать стихи, открывать календарь дня, молитвы и материалы Bible Desktop.\n\nПодробно: ".rtrim((string) config('app.url'), '/'));
            }

            return $actions;
        } elseif ($data === 'start:calendar') {
            $actions[] = [
                'method' => 'answerCallbackQuery',
                'payload' => [
                    'callback_query_id' => $callback['id'] ?? '',
                    'text' => 'Календарь дня',
                ],
            ];

            if ($chatId) {
                $actions[] = $this->calendarAction($chatId, $settings);
            }

            return $actions;
        } elseif ($data === 'start:prayers') {
            $actions[] = [
                'method' => 'answerCallbackQuery',
                'payload' => [
                    'callback_query_id' => $callback['id'] ?? '',
                    'text' => 'Молитвы',
                ],
            ];

            if ($chatId) {
                $actions[] = $this->prayersAction($chatId);
            }

            return $actions;
        } elseif ($data === 'search:more') {
            $query = (string) ($settings['last_search_query'] ?? '');
            $offset = (int) ($settings['last_search_offset'] ?? 0);

            if ($query !== '') {
                $text = $this->searchText($query, $settings, $offset);
                $settings['last_search_offset'] = $offset + 5;
                $this->saveSettings($telegramId, $settings);

                $actions[] = [
                    'method' => 'answerCallbackQuery',
                    'payload' => [
                        'callback_query_id' => $callback['id'] ?? '',
                        'text' => 'Ещё результаты',
                    ],
                ];

                if ($chatId) {
                    $actions[] = $this->messageAction($chatId, $text, [
                        'reply_markup' => $this->searchMoreKeyboard(),
                    ]);
                }

                return $actions;
            }
        } else {
            $notice = 'Неизвестная настройка.';
        }

        $this->saveSettings($telegramId, $settings);

        $actions[] = [
            'method' => 'answerCallbackQuery',
            'payload' => [
                'callback_query_id' => $callback['id'] ?? '',
                'text' => $notice,
            ],
        ];

        if ($chatId) {
            $actions[] = $this->messageAction($chatId, $this->settingsText($settings), [
                'reply_markup' => $this->settingsKeyboard($settings),
            ]);
        }

        return $actions;
    }

    /**
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function startAction(int|string $chatId): array
    {
        return [
            'method' => 'sendPhoto',
            'payload' => [
                'chat_id' => $chatId,
                'photo' => (string) config('telegram.start_image_url'),
                'caption' => "Добро пожаловать в бот «Библия»!\n\nЗдесь можно читать Писание, искать стихи, смотреть церковный календарь, открывать молитвы и сохранять важные места.\n\nВыберите нужный раздел:",
                'reply_markup' => $this->startKeyboard(),
            ],
        ];
    }

    private function helpText(): string
    {
        return "Команды:\n/start - начать\n/help - помощь\n/random - случайный стих\n/random old - Ветхий Завет\n/random new - Новый Завет\n/random psalms - Псалтирь\n/search - поиск следующим сообщением\n/search текст - поиск сразу\n/today - календарь дня\n/gospel - Евангелие дня\n/apostle - Апостол дня\n/fasting - посты дня\n/prayers - молитвы\n/tests - вопросы о вере\n/materials - полезные материалы\n/settings - язык, перевод и фильтры\n/ask - написать администратору";
    }

    /**
     * @return array<string, mixed>
     */
    private function startKeyboard(): array
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        return [
            'inline_keyboard' => [
                [
                    ['text' => '🗓 Церковный календарь', 'callback_data' => 'start:calendar'],
                    ['text' => '📖 Библия', 'url' => "{$appUrl}/embed?source=telegram"],
                ],
                [
                    ['text' => '🔖 Закладки', 'url' => "{$appUrl}/dashboard"],
                    ['text' => '🙏 Молитвы', 'callback_data' => 'start:prayers'],
                ],
                [
                    ['text' => '❓ Вопросы о вере', 'callback_data' => 'start:tests'],
                    ['text' => '✝ Материалы', 'callback_data' => 'start:materials'],
                ],
                [
                    ['text' => 'ℹ О боте', 'callback_data' => 'start:about'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function randomVerseText(array $settings, ?string $scopeOverride = null): string
    {
        $translationCode = $this->selectedTranslationCode($settings);
        $scope = $scopeOverride ?: (string) ($settings['random_scope'] ?? 'all');
        $scope = isset(self::SCOPES[$scope]) ? $scope : 'all';

        $verse = DB::table('verse_texts')
            ->join('translations', 'translations.id', '=', 'verse_texts.translation_id')
            ->join('module_books', 'module_books.id', '=', 'verse_texts.module_book_id')
            ->join('verses', 'verses.id', '=', 'verse_texts.verse_id')
            ->join('canonical_books', 'canonical_books.id', '=', 'verses.canonical_book_id')
            ->where('translations.code', $translationCode)
            ->where('canonical_books.is_deuterocanonical', false)
            ->when($scope === 'old', fn ($builder) => $builder->where('canonical_books.testament', 'old'))
            ->when($scope === 'new', fn ($builder) => $builder->where('canonical_books.testament', 'new'))
            ->when($scope === 'psalms', fn ($builder) => $builder->where('canonical_books.slug', 'psalms'))
            ->inRandomOrder()
            ->first([
                'verses.osis_ref',
                'canonical_books.osis_code',
                'verses.chapter_number',
                'verses.verse_number',
                'module_books.name as book_name',
                'verse_texts.text',
            ]);

        if (! $verse) {
            return 'Стихи для выбранного языка и раздела ещё не импортированы.';
        }

        $reference = BibleReferenceFormatter::format(
            $verse->book_name,
            $verse->osis_code,
            (int) $verse->chapter_number,
            (int) $verse->verse_number,
        );

        return $this->appendBotLink(trim("{$reference}\n{$this->telegramVerseText((string) $verse->text)}"));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function searchAction(int|string $chatId, string $telegramId, string $text, array $settings): array
    {
        $query = trim(mb_substr($text, mb_strlen('/search')));

        if (mb_strlen($query) < 2) {
            $settings['awaiting_search'] = true;
            $this->saveSettings($telegramId, $settings);

            return $this->messageAction($chatId, 'Напишите запрос следующим сообщением. Например: сотворил');
        }

        $settings['last_search_query'] = $query;
        $settings['last_search_offset'] = 5;
        $this->saveSettings($telegramId, $settings);

        return $this->messageAction($chatId, $this->searchText($query, $settings), [
            'reply_markup' => $this->searchMoreKeyboard(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $message
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function contactAction(int|string $chatId, string $telegramId, string $text, array $settings, array $message): array
    {
        $query = trim((string) preg_replace('/^\/(?:ask|contact|message)(?:@\w+)?/iu', '', $text));

        if (mb_strlen($query) < 2) {
            $settings['awaiting_contact'] = true;
            $this->saveSettings($telegramId, $settings);

            return $this->messageAction($chatId, 'Напишите ваш вопрос или сообщение следующим текстом.');
        }

        $this->storeTelegramMessage($telegramId, $chatId, $message, $query);

        return $this->messageAction($chatId, 'Сообщение принято. Администратор сможет ответить вам здесь.');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function searchText(string $query, array $settings, int $offset = 0): string
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return 'Запрос слишком короткий. Напишите минимум 2 символа.';
        }

        $translationCode = $this->selectedTranslationCode($settings);
        $searchScope = (string) ($settings['search_scope'] ?? 'canonical');
        $results = $this->verseSearch->search($query, $translationCode, 5, [
            'canonical_only' => $searchScope !== 'apocrypha',
            'deuterocanonical_only' => $searchScope === 'apocrypha',
            'scope' => in_array($searchScope, ['old', 'new', 'psalms'], true) ? $searchScope : 'all',
            'offset' => $offset,
        ])['results'];

        if ($results->isEmpty()) {
            return "Ничего не найдено: {$query}";
        }

        return $this->appendBotLink($results
            ->map(fn (array $row) => "{$row['reference']} {$this->telegramVerseText((string) $row['text'])}")
            ->implode("\n\n"));
    }

    /**
     * Keep Telegram Bible output readable by removing Strong numbers and imported markup.
     */
    private function telegramVerseText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = StrongText::textWithoutNumbersPreservingLines($text);
        $text = preg_replace('/\s+([,.;:!?»])/u', '$1', $text) ?? $text;
        $text = preg_replace('/([«])\s+/u', '$1', $text) ?? $text;

        return preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);
    }

    /**
     * Convert editor HTML into readable Telegram plain text with paragraphs preserved.
     */
    private function telegramHtmlText(string $html): string
    {
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<\s*br\s*\/?>/iu', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*(p|div|h[1-6]|blockquote|section|article|tr|table|ul|ol)\b[^>]*>/iu', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*\/\s*(p|div|h[1-6]|blockquote|section|article|tr|table|ul|ol)\s*>/iu', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\s*li[^>]*>/iu', "\n• ", $text) ?? $text;
        $text = preg_replace('/<\s*\/\s*li\s*>/iu', "\n", $text) ?? $text;
        $text = preg_replace_callback('/<\s*(strong|b)\b[^>]*>(.*?)<\s*\/\s*\1\s*>/isu', function (array $matches): string {
            $heading = trim(html_entity_decode(strip_tags((string) $matches[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($heading !== '' && mb_strlen($heading) <= 90) {
                return "\n\n{$heading}\n";
            }

            return $matches[0];
        }, $text) ?? $text;
        $text = preg_replace('/<\s*img[^>]*>/iu', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = StrongText::textWithoutNumbersPreservingLines($text);
        $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;
        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n[ \t]+/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+([,.;:!?»])/u', '$1', $text) ?? $text;
        $text = preg_replace('/([«])\s+/u', '$1', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array<string, mixed>
     */
    private function searchMoreKeyboard(): array
    {
        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'Показать ещё',
                        'callback_data' => 'search:more',
                    ],
                ],
                [
                    [
                        'text' => 'Фильтры поиска',
                        'callback_data' => 'settings:search_scope:canonical',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function calendarAction(int|string $chatId, array $settings): array
    {
        $day = $this->calendar->day(now()->toDateString());
        $keyboard = $this->calendarReadingsKeyboard($day);
        $extra = $keyboard['inline_keyboard'] === [] ? [] : ['reply_markup' => $keyboard];

        return $this->messageAction($chatId, $this->calendarText($day), $extra);
    }

    /**
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function prayersAction(int|string $chatId): array
    {
        if (! Schema::hasTable('prayers')) {
            return $this->messageAction($chatId, 'Молитвы пока не добавлены.');
        }

        $prayers = DB::table('prayers')
            ->where('is_public', true)
            ->where('language_code', 'ru')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(20)
            ->get(['id', 'title', 'short_title', 'category']);

        if ($prayers->isEmpty()) {
            return $this->messageAction($chatId, 'Молитвы пока не добавлены.');
        }

        $buttons = $prayers
            ->map(fn ($prayer): array => [[
                'text' => trim(($prayer->short_title ?: $prayer->title).' · '.$this->prayerCategoryLabel((string) $prayer->category)),
                'callback_data' => 'prayer:'.$prayer->id,
            ]])
            ->values()
            ->all();

        return $this->messageAction($chatId, 'Молитвы\nВыберите молитву:', [
            'reply_markup' => [
                'inline_keyboard' => $buttons,
            ],
        ]);
    }

    /**
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function materialsAction(int|string $chatId): array
    {
        if (! Schema::hasTable('useful_links')) {
            return $this->messageAction($chatId, 'Полезные материалы пока не добавлены.');
        }

        $links = DB::table('useful_links')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(10)
            ->get(['title', 'description', 'url']);

        if ($links->isEmpty()) {
            return $this->messageAction($chatId, 'Полезные материалы пока не добавлены.');
        }

        $lines = $links
            ->map(function ($link): string {
                $description = $link->description ? "\n".trim((string) $link->description) : '';

                return "• {$link->title}{$description}\n{$link->url}";
            })
            ->implode("\n\n");

        return $this->messageAction($chatId, $this->trimTelegramText("Полезные материалы и приложения\n{$lines}"));
    }

    /**
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function testsAction(int|string $chatId): array
    {
        if (! Schema::hasTable('quizzes')) {
            return $this->messageAction($chatId, 'Тесты пока не добавлены.');
        }

        $quizzes = DB::table('quizzes')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(10)
            ->get(['id', 'title', 'description']);

        if ($quizzes->isEmpty()) {
            return $this->messageAction($chatId, 'Тесты пока не добавлены.');
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $buttons = $quizzes
            ->map(fn ($quiz): array => [[
                'text' => (string) $quiz->title,
                'url' => "{$appUrl}/embed?source=telegram&panel=quizzes&quiz={$quiz->id}",
            ]])
            ->values()
            ->all();

        return $this->messageAction($chatId, 'Вопросы о вере и библейские тесты:', [
            'reply_markup' => [
                'inline_keyboard' => $buttons,
            ],
        ]);
    }

    /**
     * @return array{text: string, reply_markup: array<string, mixed>}
     */
    private function prayerIntroPayloadById(int $prayerId): array
    {
        $prayer = DB::table('prayers')
            ->where('is_public', true)
            ->where('id', $prayerId)
            ->first(['id', 'title', 'intro', 'body']);

        if (! $prayer) {
            return [
                'text' => 'Молитва не найдена.',
                'reply_markup' => ['inline_keyboard' => []],
            ];
        }

        $intro = trim($this->telegramHtmlText((string) ($prayer->intro ?: '')));

        if ($intro === '') {
            $intro = $this->limitTelegramPreview(
                $this->telegramHtmlText($this->firstPrayerBody((int) $prayer->id, (string) $prayer->body)),
                700,
            );
        }

        return [
            'text' => $this->trimTelegramText((string) $prayer->title."\n\n".$intro),
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Читать всю',
                            'callback_data' => 'prayer_full:'.$prayer->id,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function prayerFullTextsById(int $prayerId): array
    {
        $prayer = DB::table('prayers')
            ->where('is_public', true)
            ->where('id', $prayerId)
            ->first(['id', 'title', 'body']);

        if (! $prayer) {
            return ['Молитва не найдена.'];
        }

        $sections = DB::table('prayer_sections')
            ->where('prayer_id', $prayerId)
            ->orderBy('sort_order')
            ->get(['title', 'body']);

        $body = $sections->isEmpty()
            ? $this->telegramHtmlText((string) $prayer->body)
            : $sections
                ->map(function ($section): string {
                    $title = trim((string) ($section->title ?? ''));
                    $body = $this->telegramHtmlText((string) $section->body);

                    return trim(($title === '' ? '' : $title."\n").$body);
                })
                ->filter()
                ->implode("\n\n");

        return $this->splitTelegramText((string) $prayer->title."\n\n".$body);
    }

    private function firstPrayerBody(int $prayerId, string $fallback): string
    {
        $section = DB::table('prayer_sections')
            ->where('prayer_id', $prayerId)
            ->orderBy('sort_order')
            ->first(['body']);

        return $section ? (string) $section->body : $fallback;
    }

    private function prayerCategoryLabel(string $category): string
    {
        return match ($category) {
            'morning' => 'утро',
            'evening' => 'вечер',
            'communion_before' => 'ко Причастию',
            'communion_after' => 'после Причастия',
            default => 'молитвы',
        };
    }

    private function limitTelegramPreview(string $text, int $limit): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $slice = mb_substr($text, 0, $limit);
        $breakAt = max(
            mb_strrpos($slice, "\n\n") ?: 0,
            mb_strrpos($slice, "\n") ?: 0,
            mb_strrpos($slice, ' ') ?: 0,
        );

        if ($breakAt > 240) {
            $slice = mb_substr($slice, 0, $breakAt);
        }

        return rtrim($slice, " \t\n\r.,;:").'...';
    }

    /**
     * @param  array{
     *     date: string,
     *     events: Collection<int, array{id: int, name: string, legacy_type: int|null, date_rule_type: string, is_fasting: bool, type: array<string, mixed>|null}>,
     *     readings: Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, display_ref: string, date_rule_type: string}>,
     *     monastery_services: Collection<int, array{id: int, title: string, description: string|null, location: string|null, starts_at: string, ends_at: string|null, time_label: string, is_all_day: bool}>
     * }  $day
     */
    private function calendarText(array $day): string
    {
        $readings = $this->readingsSummary($day);

        if ($day['events']->isEmpty() && $readings === '') {
            return 'Календарные события и чтения дня ещё не импортированы.';
        }

        $events = $day['events']
            ->take(8)
            ->map(fn (array $event) => $this->calendarEventLine($event))
            ->implode("\n");

        if ($events === '') {
            $events = 'На этот день нет включённых событий календаря.';
        }

        $fasting = $day['fasting_events']->isEmpty()
            ? ''
            : "\n\nПост:\n".$day['fasting_events']
                ->take(4)
                ->map(fn (array $event) => $this->calendarEventLine($event))
                ->implode("\n");

        $services = $day['monastery_services']->isEmpty()
            ? ''
            : "\n\nБогослужения в монастыре:\n".$day['monastery_services']
                ->take(5)
                ->map(fn (array $service): string => trim($service['time_label'].' '.$service['title']))
                ->implode("\n");

        return $this->appendBotLink(trim("Календарь на {$day['date']}\n{$events}{$fasting}{$services}\n\n{$readings}"));
    }

    /**
     * @param  array{name: string, legacy_type: int|null, type: array<string, mixed>|null}  $event
     */
    private function calendarEventLine(array $event): string
    {
        $symbol = trim((string) ($event['type']['typicon_symbol'] ?? ''));

        if (preg_match('/^[1-5]$/', $symbol) === 1) {
            $symbol = match ($symbol) {
                '1' => '☦',
                '2', '3', '4', '5' => '✚',
            };
        }

        if ($symbol === '') {
            $symbol = match ((int) ($event['legacy_type'] ?? -1)) {
                0, 1, 2 => '☦',
                3, 4, 5, 6 => '✚',
                7 => '✶',
                default => '•',
            };
        }

        return "{$symbol} {$event['name']}";
    }

    /**
     * @param  array{
     *     readings: Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, display_ref: string, date_rule_type: string}>
     * }  $day
     * @return array<string, mixed>
     */
    private function calendarReadingsKeyboard(array $day): array
    {
        $buttons = $day['readings']
            ->filter(fn (array $reading): bool => in_array($reading['type'], ['apostle', 'gospel', 'psalm'], true))
            ->take(8)
            ->map(fn (array $reading): array => [[
                'text' => $this->calendarReadingButtonLabel($reading),
                'callback_data' => 'calendar:reading:'.$reading['id'],
            ]])
            ->values()
            ->all();

        return [
            'inline_keyboard' => $buttons,
        ];
    }

    /**
     * @param  array{id: int, type: string, title: string|null, passage_ref: string, display_ref: string, date_rule_type: string}  $reading
     */
    private function calendarReadingButtonLabel(array $reading): string
    {
        $prefix = match ($reading['type']) {
            'apostle' => 'Ап.',
            'gospel' => 'Ев.',
            'psalm' => 'Пс.',
            default => 'Чт.',
        };

        return trim($prefix.' '.($reading['display_ref'] ?: $reading['passage_ref']));
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function calendarReadingTextById(int $readingId, array $settings): string
    {
        $reading = DB::table('calendar_readings')
            ->where('id', $readingId)
            ->first(['id', 'reading_type', 'title', 'passage_ref', 'metadata_json']);

        if (! $reading) {
            return 'Чтение не найдено.';
        }

        $translationCode = $this->selectedTranslationCode($settings);
        $metadata = json_decode((string) ($reading->metadata_json ?? ''), true);
        $displayRef = is_array($metadata) && isset($metadata['raw_name'])
            ? (string) $metadata['raw_name']
            : (string) $reading->passage_ref;
        $title = $reading->title ? (string) $reading->title : $this->calendarReadingTypeName((string) $reading->reading_type);
        $text = $this->passageText->bodyText((string) $reading->passage_ref, $translationCode, 80);

        if ($text === '') {
            return "{$title}: {$displayRef}\nТекст не найден в выбранном переводе.";
        }

        return $this->trimTelegramText("{$title}: {$displayRef}\n{$text}");
    }

    private function calendarReadingTypeName(string $type): string
    {
        return match ($type) {
            'apostle' => 'Апостол',
            'gospel' => 'Евангелие',
            'psalm' => 'Псалтирь',
            default => 'Чтение',
        };
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

    /**
     * @param  array<string, mixed>  $settings
     */
    private function readingText(string $readingType, string $readingName, array $settings): string
    {
        $day = $this->calendar->day(now()->toDateString());
        $translationCode = $this->selectedTranslationCode($settings);
        $readings = $day['readings']
            ->filter(fn (array $reading): bool => $reading['type'] === $readingType)
            ->values();

        if ($readings->isEmpty()) {
            return "Чтение дня ({$readingName}) ещё не задано.";
        }

        $lines = $readings
            ->take(4)
            ->map(function (array $reading) use ($translationCode): string {
                $title = $reading['title'] ? "{$reading['title']}: " : '';
                $text = $this->passageText->bodyText($reading['passage_ref'], $translationCode, 35);

                $displayRef = $reading['display_ref'] ?? $reading['passage_ref'];

                if ($text === '') {
                    return "- {$title}{$displayRef}\nТекст не найден в выбранном переводе.";
                }

                return "{$title}{$displayRef}\n{$text}";
            })
            ->implode("\n");

        return $this->trimTelegramText("{$readingName} на {$day['date']}\n{$lines}");
    }

    /**
     * @param  array{readings: Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, display_ref?: string, date_rule_type: string}>}  $day
     */
    private function readingsSummary(array $day): string
    {
        $readings = $day['readings'];

        if ($readings->isEmpty()) {
            return '';
        }

        $apostle = $this->calendarReadingBlock($readings, 'apostle');
        $gospel = $this->calendarReadingBlock($readings, 'gospel');
        $psalms = $readings
            ->filter(fn (array $reading): bool => $reading['type'] === 'psalm')
            ->take(4)
            ->map(fn (array $reading): string => $reading['display_ref'] ?? $reading['passage_ref'])
            ->implode('; ');

        $lines = [];

        if ($apostle !== '' || $gospel !== '') {
            $lines[] = 'Евангелие и Апостол:';

            if ($apostle !== '') {
                $lines[] = 'Ап.: '.$apostle;
            }

            if ($gospel !== '') {
                $lines[] = 'Ев.: '.$gospel;
            }
        }

        if ($psalms !== '') {
            $lines[] = 'Псалтирь:';
            $lines[] = $psalms;
        }

        return $this->trimTelegramText(implode("\n", array_filter($lines)));
    }

    /**
     * @param  Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, display_ref?: string, date_rule_type: string}>  $readings
     */
    private function calendarReadingBlock(Collection $readings, string $type): string
    {
        return $readings
            ->filter(fn (array $reading): bool => $reading['type'] === $type)
            ->take(2)
            ->map(fn (array $reading): string => $reading['display_ref'] ?? $reading['passage_ref'])
            ->implode('; ');
    }

    private function trimTelegramText(string $text): string
    {
        $text = $this->appendBotLink($text);
        $text = trim($text);

        if (mb_strlen($text) <= 3900) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, 3850))."\n\nТекст сокращён, полный отрывок откройте на сайте.";
    }

    /**
     * @return list<string>
     */
    private function splitTelegramText(string $text): array
    {
        $text = trim($this->appendBotLink($text));

        if (mb_strlen($text) <= 3800) {
            return [$text];
        }

        $chunks = [];
        $remaining = $text;

        while (mb_strlen($remaining) > 3800) {
            $slice = mb_substr($remaining, 0, 3800);
            $breakAt = max(
                mb_strrpos($slice, "\n\n") ?: 0,
                mb_strrpos($slice, "\n") ?: 0,
                mb_strrpos($slice, ' ') ?: 0,
            );

            if ($breakAt < 1600) {
                $breakAt = 3800;
            }

            $chunks[] = trim(mb_substr($remaining, 0, $breakAt));
            $remaining = trim(mb_substr($remaining, $breakAt));
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return $chunks;
    }

    private function appendBotLink(string $text): string
    {
        $username = trim((string) config('telegram.bot_username', ''));

        if ($username === '' || str_contains($text, 't.me/'.$username)) {
            return $text;
        }

        return rtrim($text)."\n\nBible Desktop: https://t.me/{$username}";
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function settingsText(array $settings): string
    {
        $translation = $this->translationLabel($this->selectedTranslationCode($settings));
        $scope = (string) ($settings['random_scope'] ?? 'all');
        $scopeLabel = self::SCOPES[$scope] ?? self::SCOPES['all'];
        $searchScope = (string) ($settings['search_scope'] ?? 'canonical');
        $searchScopeLabel = self::SEARCH_SCOPES[$searchScope] ?? self::SEARCH_SCOPES['canonical'];

        return "Настройки\nПеревод: {$translation}\nПоиск: {$searchScopeLabel}\nСлучайный стих: {$scopeLabel}\n\nВыберите нужный вариант кнопками ниже.";
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function settingsKeyboard(array $settings): array
    {
        $selectedTranslation = $this->selectedTranslationCode($settings);
        $selectedScope = (string) ($settings['random_scope'] ?? 'all');
        $translations = DB::table('translations')
            ->join('languages', 'languages.id', '=', 'translations.language_id')
            ->join('modules', 'modules.id', '=', 'translations.module_id')
            ->where('modules.type', 'bible')
            ->where('modules.is_active', true)
            ->orderByDesc('translations.is_default')
            ->orderBy('languages.sort_order')
            ->orderBy('translations.name')
            ->limit(8)
            ->get([
                'translations.code',
                'translations.short_name',
                'languages.native_name',
            ]);

        $translationButtons = $translations
            ->map(fn ($translation): array => [
                'text' => ($translation->code === $selectedTranslation ? '✓ ' : '').trim(($translation->short_name ?: $translation->code).' · '.$translation->native_name),
                'callback_data' => 'settings:translation:'.$translation->code,
            ])
            ->chunk(2)
            ->map(fn ($row) => $row->values()->all())
            ->values()
            ->all();

        $scopeButtons = collect(self::SCOPES)
            ->map(fn (string $label, string $scope): array => [
                'text' => ($scope === $selectedScope ? '✓ ' : '').$label,
                'callback_data' => 'settings:scope:'.$scope,
            ])
            ->chunk(2)
            ->map(fn ($row) => $row->values()->all())
            ->values()
            ->all();

        $selectedSearchScope = (string) ($settings['search_scope'] ?? 'canonical');
        $searchScopeButtons = collect(self::SEARCH_SCOPES)
            ->map(fn (string $label, string $scope): array => [
                'text' => ($scope === $selectedSearchScope ? '✓ Поиск: ' : 'Поиск: ').$label,
                'callback_data' => 'settings:search_scope:'.$scope,
            ])
            ->chunk(1)
            ->map(fn ($row) => $row->values()->all())
            ->values()
            ->all();

        return [
            'inline_keyboard' => array_merge($translationButtons, $searchScopeButtons, $scopeButtons),
        ];
    }

    private function translationLabel(string $code): string
    {
        $translation = DB::table('translations')
            ->join('languages', 'languages.id', '=', 'translations.language_id')
            ->where('translations.code', $code)
            ->first([
                'translations.code',
                'translations.short_name',
                'languages.native_name',
            ]);

        if (! $translation) {
            return $code;
        }

        return trim(($translation->short_name ?: $translation->code).' · '.$translation->native_name);
    }

    private function translationExists(string $code): bool
    {
        return DB::table('translations')->where('code', $code)->exists();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function selectedTranslationCode(array $settings): string
    {
        $code = (string) ($settings['translation_code'] ?? '');

        if ($code !== '' && $this->translationExists($code)) {
            return $code;
        }

        return $this->defaultTranslationCode();
    }

    private function defaultTranslationCode(): string
    {
        $configured = (string) config('telegram.default_translation', 'BQ_RUSSIAN_RST');

        if ($configured !== '' && $this->translationExists($configured)) {
            return $configured;
        }

        $default = DB::table('translations')
            ->join('modules', 'modules.id', '=', 'translations.module_id')
            ->where('modules.type', 'bible')
            ->where('modules.is_active', true)
            ->orderByDesc('translations.is_default')
            ->orderBy('translations.name')
            ->value('translations.code');

        return (string) ($default ?: $configured);
    }

    /**
     * @param  array<string, mixed>  $from
     * @return array<string, mixed>
     */
    private function settingsFor(string $telegramId, mixed $from = []): array
    {
        $user = DB::table('users')->where('telegram_id', $telegramId)->first(['id', 'settings_json']);
        $settings = $this->defaultSettings();

        if (! $user) {
            DB::table('users')->insert([
                'name' => $this->telegramName(is_array($from) ? $from : [], $telegramId),
                'email' => "telegram-{$telegramId}@telegram.local",
                'password' => Hash::make(Str::random(40)),
                'telegram_id' => $telegramId,
                'telegram_username' => is_array($from) ? ($from['username'] ?? null) : null,
                'settings_json' => json_encode($settings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $settings;
        }

        if (is_string($user->settings_json) && trim($user->settings_json) !== '') {
            $decoded = json_decode($user->settings_json, true);

            if (is_array($decoded)) {
                $settings = array_merge($settings, $decoded);
            }
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function saveSettings(string $telegramId, array $settings): void
    {
        DB::table('users')
            ->where('telegram_id', $telegramId)
            ->update([
                'settings_json' => json_encode($settings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(): array
    {
        return [
            'translation_code' => $this->defaultTranslationCode(),
            'search_scope' => 'canonical',
            'random_scope' => 'all',
            'awaiting_search' => false,
            'awaiting_contact' => false,
            'last_search_query' => '',
            'last_search_offset' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function storeTelegramMessage(string $telegramId, int|string $chatId, array $message, string $body): void
    {
        $body = trim($body);

        if ($body === '') {
            return;
        }

        $user = DB::table('users')->where('telegram_id', $telegramId)->first(['id', 'telegram_username']);
        $from = is_array($message['from'] ?? null) ? $message['from'] : [];

        DB::table('telegram_messages')->insert([
            'user_id' => $user?->id,
            'telegram_id' => $telegramId,
            'telegram_username' => $user?->telegram_username ?: ($from['username'] ?? null),
            'chat_id' => (string) $chatId,
            'direction' => 'inbound',
            'status' => 'new',
            'body' => $body,
            'metadata_json' => json_encode([
                'message_id' => $message['message_id'] ?? null,
                'from' => $from,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function chatId(array $message): int|string|null
    {
        $chat = $message['chat'] ?? null;

        return is_array($chat) ? ($chat['id'] ?? null) : null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function isGroupChat(array $message): bool
    {
        $chat = $message['chat'] ?? null;
        $type = is_array($chat) ? (string) ($chat['type'] ?? '') : '';

        return in_array($type, ['group', 'supergroup'], true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function telegramUserId(array $payload, int|string|null $fallback): string
    {
        $from = $payload['from'] ?? null;
        $id = is_array($from) ? ($from['id'] ?? null) : null;

        return (string) ($id ?: $fallback ?: 'unknown');
    }

    private function command(string $text): string
    {
        $firstToken = trim(strtok($text, " \t\r\n") ?: '');

        if ($firstToken === '' || ! str_starts_with($firstToken, '/')) {
            return '';
        }

        return mb_strtolower(explode('@', $firstToken, 2)[0]);
    }

    private function scopeFromRandomCommand(string $text): ?string
    {
        $argument = trim(mb_substr($text, mb_strlen('/random')));
        $argument = mb_strtolower(str_replace('ё', 'е', $argument));

        return match ($argument) {
            'all', 'все', 'вся', 'библия' => 'all',
            'old', 'ot', 'ветхий', 'ветхий завет', 'вз' => 'old',
            'new', 'nt', 'новый', 'новый завет', 'нз' => 'new',
            'ps', 'psalms', 'псалом', 'псалмы', 'псалтирь' => 'psalms',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function messageAction(int|string $chatId, string $text, array $extra = []): array
    {
        return [
            'method' => 'sendMessage',
            'payload' => array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ], $extra),
        ];
    }

    /**
     * @param  array<string, mixed>  $from
     */
    private function telegramName(array $from, string $telegramId): string
    {
        $name = trim(implode(' ', array_filter([
            $from['first_name'] ?? null,
            $from['last_name'] ?? null,
        ], 'is_string')));

        if ($name !== '') {
            return $name;
        }

        if (isset($from['username']) && is_string($from['username'])) {
            return '@'.$from['username'];
        }

        return 'Telegram '.$telegramId;
    }
}
