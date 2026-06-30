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

namespace Tests\Feature;

use App\Models\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_renders_allowed_html_and_shared_footer(): void
    {
        CmsPage::query()->updateOrCreate(['slug' => 'contacts'], [
            'title' => 'Contacts',
            'menu_label' => 'Контакты',
            'menu_location' => 'footer',
            'content' => '<p><strong>Напишите нам</strong></p><script>alert(1)</script>',
            'sort_order' => 10,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/pages/contacts')
            ->assertOk()
            ->assertSee('<strong>Напишите нам</strong>', false)
            ->assertDontSee('<script>', false)
            ->assertSee('Контакты');
    }

    public function test_public_storage_route_serves_uploaded_module_covers(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('module-covers/test.png', 'cover-bytes');

        $this->get('/storage/module-covers/test.png')
            ->assertOk();
    }

    public function test_standard_reader_allows_supported_frame_hosts_without_embed_mode(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeaderMissing('X-Frame-Options')
            ->assertHeader('Content-Security-Policy')
            ->assertSee('"embed":{"enabled":false,"source":null,"surface":"standard"}', false);
    }

    public function test_telegram_mini_app_has_a_dedicated_surface(): void
    {
        $this->get('/telegramm-mini-app')
            ->assertOk()
            ->assertSee('"embed":{"enabled":true,"source":"telegram","surface":"telegram"}', false)
            ->assertSee('https://telegram.org/js/telegram-web-app.js', false);
    }

    public function test_webview_has_a_dedicated_surface_without_telegram_sdk(): void
    {
        $this->get('/webview')
            ->assertOk()
            ->assertSee('"embed":{"enabled":true,"source":"webview","surface":"webview"}', false)
            ->assertDontSee('https://telegram.org/js/telegram-web-app.js', false);
    }

    public function test_removed_generic_mini_app_route_returns_not_found(): void
    {
        $this->get('/mini-app')->assertNotFound();
    }
}
