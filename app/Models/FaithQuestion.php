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

class FaithQuestion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'category',
        'question',
        'answer_html',
        'source_url',
        'sort_order',
        'is_public',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_public' => 'boolean',
        ];
    }
}
