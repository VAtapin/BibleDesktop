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

class VerseText extends Model
{
    protected $fillable = [
        'verse_id',
        'translation_id',
        'module_book_id',
        'module_chapter_id',
        'legacy_verse_id',
        'text',
        'has_strong_markup',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'has_strong_markup' => 'boolean',
            'metadata_json' => 'array',
        ];
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class);
    }

    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class);
    }

    public function moduleBook(): BelongsTo
    {
        return $this->belongsTo(ModuleBook::class);
    }

    public function moduleChapter(): BelongsTo
    {
        return $this->belongsTo(ModuleChapter::class);
    }
}
