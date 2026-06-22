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

class CalendarEventType extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'legacy_type',
        'name',
        'typicon_symbol',
        'color',
        'is_fasting',
        'is_visible',
        'description',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'legacy_type' => 'integer',
            'is_fasting' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
