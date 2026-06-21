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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyLibrary extends Model
{
    protected $fillable = [
        'legacy_id',
        'module_id',
        'translation_id',
        'raw_json',
    ];

    protected function casts(): array
    {
        return [
            'raw_json' => 'array',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(BibleModule::class, 'module_id');
    }

    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class);
    }
}
