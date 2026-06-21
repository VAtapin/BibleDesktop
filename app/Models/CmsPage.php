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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    private const ALLOWED_HTML_TAGS = '<p><br><strong><b><em><i><u><a><ul><ol><li><blockquote><h2><h3><h4><hr>';

    protected $fillable = [
        'title',
        'slug',
        'menu_label',
        'menu_location',
        'excerpt',
        'content',
        'sort_order',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Render trusted admin-authored page content with a small HTML allow-list.
     */
    public function renderedContent(): string
    {
        $content = (string) ($this->content ?? '');
        $content = strip_tags($content, self::ALLOWED_HTML_TAGS);
        $content = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/iu', '', $content) ?? $content;
        $content = preg_replace('/href\s*=\s*(["\'])\s*javascript:.*?\1/iu', 'href="#"', $content) ?? $content;

        return $content;
    }
}
