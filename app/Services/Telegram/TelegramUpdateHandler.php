<?php

namespace App\Services\Telegram;

use App\Services\Bible\VerseSearchService;
use App\Services\Calendar\OrthodoxCalendarService;
use App\Support\BibleReferenceFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        private readonly VerseSearchService $verseSearch,
    ) {}

    /**
     * @param array<string, mixed> $update
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
     * @param array<string, mixed> $message
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

        if ($command === '' && (bool) ($settings['awaiting_search'] ?? false)) {
            $settings['awaiting_search'] = false;
            $settings['last_search_query'] = $text;
            $settings['last_search_offset'] = 5;
            $this->saveSettings($telegramId, $settings);

            return [$this->messageAction($chatId, $this->searchText($text, $settings), [
                'reply_markup' => $this->searchMoreKeyboard(),
            ])];
        }

        $action = match ($command) {
            '/start' => $this->messageAction($chatId, $this->startText()),
            '/help' => $this->messageAction($chatId, $this->helpText()),
            '/search' => $this->searchAction($chatId, $telegramId, $text, $settings),
            '/today', '/calendar' => $this->messageAction($chatId, $this->calendarText()),
            '/gospel' => $this->messageAction($chatId, $this->readingText('gospel', 'Евангелие')),
            '/apostle' => $this->messageAction($chatId, $this->readingText('apostle', 'Апостол')),
            '/fasting' => $this->messageAction($chatId, $this->fastingText()),
            '/settings' => $this->messageAction($chatId, $this->settingsText($settings), [
                'reply_markup' => $this->settingsKeyboard($settings),
            ]),
            '/random' => $this->messageAction($chatId, $this->randomVerseText($settings, $this->scopeFromRandomCommand($text))),
            default => $this->messageAction($chatId, $this->helpText()),
        };

        return [$action];
    }

    /**
     * @param array<string, mixed> $callback
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

    private function startText(): string
    {
        return "Bible Desktop\n\nКоманды: /random, /search, /today, /settings, /help";
    }

    private function helpText(): string
    {
        return "Команды:\n/start - начать\n/help - помощь\n/random - случайный стих\n/random old - Ветхий Завет\n/random new - Новый Завет\n/random psalms - Псалтирь\n/search - поиск следующим сообщением\n/search текст - поиск сразу\n/today - календарь дня\n/gospel - Евангелие дня\n/apostle - Апостол дня\n/settings - язык, перевод и фильтры";
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function randomVerseText(array $settings, ?string $scopeOverride = null): string
    {
        $translationCode = (string) ($settings['translation_code'] ?? config('telegram.default_translation', 'L1_RST'));
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

        return trim("{$reference}\n{$verse->text}");
    }

    /**
     * @param array<string, mixed> $settings
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
     * @param array<string, mixed> $settings
     */
    private function searchText(string $query, array $settings, int $offset = 0): string
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return 'Запрос слишком короткий. Напишите минимум 2 символа.';
        }

        $translationCode = (string) ($settings['translation_code'] ?? config('telegram.default_translation', 'L1_RST'));
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

        return $results
            ->map(fn (array $row) => "{$row['reference']} {$row['text']}")
            ->implode("\n\n");
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

        $readings = $this->readingsSummary($day);

        return trim("Календарь на {$day['date']}\n{$events}\n\n{$readings}");
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

    private function readingText(string $readingType, string $readingName): string
    {
        $day = $this->calendar->day(now()->toDateString());
        $readings = $day['readings']
            ->filter(fn (array $reading): bool => $reading['type'] === $readingType)
            ->values();

        if ($readings->isEmpty()) {
            return "Чтение дня ({$readingName}) ещё не задано.";
        }

        $lines = $readings
            ->take(4)
            ->map(function (array $reading): string {
                $title = $reading['title'] ? "{$reading['title']}: " : '';

                return "- {$title}{$reading['passage_ref']}";
            })
            ->implode("\n");

        return "{$readingName} на {$day['date']}\n{$lines}";
    }

    /**
     * @param array{readings: \Illuminate\Support\Collection<int, array{id: int, type: string, title: string|null, passage_ref: string, date_rule_type: string}>} $day
     */
    private function readingsSummary(array $day): string
    {
        $readings = $day['readings'];

        if ($readings->isEmpty()) {
            return '';
        }

        $apostle = $readings
            ->filter(fn (array $reading): bool => $reading['type'] === 'apostle')
            ->take(2)
            ->map(fn (array $reading): string => $reading['passage_ref'])
            ->implode('; ');
        $gospel = $readings
            ->filter(fn (array $reading): bool => $reading['type'] === 'gospel')
            ->take(2)
            ->map(fn (array $reading): string => $reading['passage_ref'])
            ->implode('; ');
        $psalms = $readings
            ->filter(fn (array $reading): bool => $reading['type'] === 'psalm')
            ->take(4)
            ->map(fn (array $reading): string => $reading['passage_ref'])
            ->implode('; ');

        $lines = [];

        if ($apostle !== '' || $gospel !== '') {
            $lines[] = 'Евангелие и Апостол:';
            $lines[] = trim('Ап.: '.$apostle.' Ев.: '.$gospel);
        }

        if ($psalms !== '') {
            $lines[] = 'Псалтирь:';
            $lines[] = $psalms;
        }

        return implode("\n", array_filter($lines));
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function settingsText(array $settings): string
    {
        $translation = $this->translationLabel((string) ($settings['translation_code'] ?? config('telegram.default_translation', 'L1_RST')));
        $scope = (string) ($settings['random_scope'] ?? 'all');
        $scopeLabel = self::SCOPES[$scope] ?? self::SCOPES['all'];
        $searchScope = (string) ($settings['search_scope'] ?? 'canonical');
        $searchScopeLabel = self::SEARCH_SCOPES[$searchScope] ?? self::SEARCH_SCOPES['canonical'];

        return "Настройки\nПеревод: {$translation}\nПоиск: {$searchScopeLabel}\nСлучайный стих: {$scopeLabel}\n\nВыберите нужный вариант кнопками ниже.";
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function settingsKeyboard(array $settings): array
    {
        $selectedTranslation = (string) ($settings['translation_code'] ?? config('telegram.default_translation', 'L1_RST'));
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
     * @param array<string, mixed> $from
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
     * @param array<string, mixed> $settings
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
            'translation_code' => (string) config('telegram.default_translation', 'L1_RST'),
            'search_scope' => 'canonical',
            'random_scope' => 'all',
            'awaiting_search' => false,
            'last_search_query' => '',
            'last_search_offset' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $message
     */
    private function chatId(array $message): int|string|null
    {
        $chat = $message['chat'] ?? null;

        return is_array($chat) ? ($chat['id'] ?? null) : null;
    }

    /**
     * @param array<string, mixed> $payload
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
     * @param array<string, mixed> $extra
     * @return array{method: string, payload: array<string, mixed>}
     */
    private function messageAction(int|string $chatId, string $text, array $extra = []): array
    {
        return [
            'method' => 'sendMessage',
            'payload' => array_merge([
                'chat_id' => $chatId,
                'text' => $text,
            ], $extra),
        ];
    }

    /**
     * @param array<string, mixed> $from
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
