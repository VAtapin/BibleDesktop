<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyChapter extends Model
{
    protected $fillable = [
        'legacy_id',
        'legacy_book_id',
        'legacy_bible_id',
        'module_chapter_id',
        'canonical_chapter_id',
        'raw_json',
    ];

    protected function casts(): array
    {
        return [
            'raw_json' => 'array',
        ];
    }

    public function moduleChapter(): BelongsTo
    {
        return $this->belongsTo(ModuleChapter::class);
    }

    public function canonicalChapter(): BelongsTo
    {
        return $this->belongsTo(CanonicalChapter::class);
    }
}
