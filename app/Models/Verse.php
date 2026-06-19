<?php

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
