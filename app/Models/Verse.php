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
use Illuminate\Database\Eloquent\Relations\HasMany;

class Verse extends Model
{
    protected $fillable = [
        'canonical_book_id',
        'canonical_chapter_id',
        'chapter_number',
        'verse_number',
        'osis_ref',
    ];

    public function canonicalBook(): BelongsTo
    {
        return $this->belongsTo(CanonicalBook::class);
    }

    public function canonicalChapter(): BelongsTo
    {
        return $this->belongsTo(CanonicalChapter::class);
    }

    public function texts(): HasMany
    {
        return $this->hasMany(VerseText::class);
    }
}
