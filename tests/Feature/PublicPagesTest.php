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
}
