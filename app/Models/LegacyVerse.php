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

class LegacyVerse extends Model
{
    protected $fillable = [
        'legacy_id',
        'legacy_book_id',
        'legacy_chapter_id',
        'legacy_bible_id',
        'verse_id',
        'verse_text_id',
        'raw_json',
    ];

    protected function casts(): array
    {
        return [
            'raw_json' => 'array',
        ];
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class);
    }

    public function verseText(): BelongsTo
    {
        return $this->belongsTo(VerseText::class);
    }
}
