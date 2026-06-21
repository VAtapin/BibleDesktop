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

namespace App\Support;

use App\Models\CmsPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Provides the shared footer page links for the reader and public pages.
 */
class FooterPages
{
    /**
     * @return Collection<int, array{title: string, url: string}>
     */
    public static function links(): Collection
    {
        if (! Schema::hasTable('cms_pages')) {
            return collect();
        }

        return CmsPage::query()
            ->where('is_published', true)
            ->where('menu_location', 'footer')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['title', 'slug', 'menu_label'])
            ->map(fn (CmsPage $page): array => [
                'title' => $page->menu_label ?: $page->title,
                'url' => route('pages.show', ['slug' => $page->slug]),
            ])
            ->values();
    }
}
