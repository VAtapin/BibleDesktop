<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_webhook_returns_help_action_for_start_command(): void
    {
        config(['telegram.webhook_secret' => 'secret']);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/start',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'secret',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('actions.0.method', 'sendMessage')
            ->assertJsonPath('actions.0.payload.chat_id', 123);
    }

    public function test_telegram_webhook_rejects_invalid_secret(): void
    {
        config(['telegram.webhook_secret' => 'secret']);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/help',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong',
        ])->assertForbidden();
    }

    public function test_telegram_webhook_can_send_actions_to_telegram_api(): void
    {
        config([
            'telegram.bot_token' => 'token',
            'telegram.webhook_secret' => 'secret',
            'telegram.send_responses' => true,
        ]);
        Http::fake([
            'https://api.telegram.org/bottoken/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/help',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'secret',
        ])
            ->assertOk()
            ->assertJsonPath('sent', 1);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottoken/sendMessage'
            && $request['chat_id'] === 123);
    }

    public function test_telegram_search_command_returns_matching_verses(): void
    {
        $this->createSearchFixture();

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/search сотворил',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('actions.0.payload.text', "Gen.1.1 В начале сотворил Бог небо и землю.");
    }

    public function test_telegram_search_command_accepts_verse_reference(): void
    {
        $this->createSearchFixture();

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/search Gen.1.1',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('actions.0.payload.text', "Gen.1.1 В начале сотворил Бог небо и землю.");
    }

    public function test_telegram_today_command_returns_calendar_events(): void
    {
        DB::table('calendar_events')->insert([
            'name' => 'Святое Богоявление',
            'legacy_type' => 1,
            'date_rule_type' => 'fixed',
            'start_month' => (int) now()->month,
            'start_day' => (int) now()->day,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/today',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('actions.0.payload.text', "Календарь на ".now()->toDateString()."\n- Святое Богоявление");
    }

    public function test_telegram_fasting_command_returns_fasting_events(): void
    {
        DB::table('calendar_events')->insert([
            'name' => 'Великий пост',
            'legacy_type' => 10,
            'date_rule_type' => 'fixed',
            'start_month' => (int) now()->month,
            'start_day' => (int) now()->day,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/fasting',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('actions.0.payload.text', "Пост на ".now()->toDateString()."\n- Великий пост");
    }

    public function test_telegram_gospel_command_reports_missing_reading(): void
    {
        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/gospel',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('actions.0.payload.text', 'Чтение дня (Евангелие) ещё не задано.');
    }

    public function test_telegram_gospel_command_returns_calendar_reading(): void
    {
        DB::table('calendar_readings')->insert([
            'date_rule_type' => 'fixed',
            'month' => (int) now()->month,
            'day' => (int) now()->day,
            'reading_type' => 'gospel',
            'title' => 'Евангелие дня',
            'passage_ref' => 'John.1.1-17',
            'sort_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/gospel',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('actions.0.payload.text', "Евангелие на ".now()->toDateString()."\n- Евангелие дня: John.1.1-17");
    }

    private function createSearchFixture(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $now = now();
        $languageId = DB::table('languages')->where('code', 'ru')->value('id');
        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');
        $bookId = DB::table('canonical_books')->where('slug', 'genesis')->value('id');
        $chapterId = DB::table('canonical_chapters')
            ->where('canonical_book_id', $bookId)
            ->where('number', 1)
            ->value('id');

        DB::table('modules')->insert([
            'language_id' => $languageId,
            'type' => 'bible',
            'code' => 'L1_RST',
            'name' => 'Russian Synodal Test',
            'short_name' => 'RST',
            'is_active' => true,
            'is_public' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleId = DB::table('modules')->where('code', 'L1_RST')->value('id');

        DB::table('translations')->insert([
            'module_id' => $moduleId,
            'language_id' => $languageId,
            'canon_id' => $canonId,
            'code' => 'L1_RST',
            'name' => 'Russian Synodal Test',
            'short_name' => 'RST',
            'has_old_testament' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $translationId = DB::table('translations')->where('code', 'L1_RST')->value('id');

        DB::table('module_books')->insert([
            'module_id' => $moduleId,
            'translation_id' => $translationId,
            'canonical_book_id' => $bookId,
            'slug' => 'genesis',
            'name' => 'Бытие',
            'short_name' => 'Быт.',
            'book_order' => 1,
            'chapters_count' => 50,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleBookId = DB::table('module_books')->where('slug', 'genesis')->value('id');

        DB::table('module_chapters')->insert([
            'module_book_id' => $moduleBookId,
            'canonical_chapter_id' => $chapterId,
            'chapter_number' => 1,
            'verses_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moduleChapterId = DB::table('module_chapters')->where('module_book_id', $moduleBookId)->value('id');

        DB::table('verses')->insert([
            'canonical_book_id' => $bookId,
            'canonical_chapter_id' => $chapterId,
            'chapter_number' => 1,
            'verse_number' => 1,
            'osis_ref' => 'Gen.1.1',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $verseId = DB::table('verses')->where('osis_ref', 'Gen.1.1')->value('id');

        DB::table('verse_texts')->insert([
            'verse_id' => $verseId,
            'translation_id' => $translationId,
            'module_book_id' => $moduleBookId,
            'module_chapter_id' => $moduleChapterId,
            'text' => 'В начале сотворил Бог небо и землю.',
            'text_plain' => 'В начале сотворил Бог небо и землю.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
