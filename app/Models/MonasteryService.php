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

class MonasteryService extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'external_uid',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'is_all_day',
        'source_url',
        'is_public',
        'imported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
            'is_public' => 'boolean',
            'imported_at' => 'datetime',
        ];
    }
}
