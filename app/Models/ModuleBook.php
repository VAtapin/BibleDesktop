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

class ModuleBook extends Model
{
    protected $fillable = [
        'module_id',
        'translation_id',
        'canonical_book_id',
        'legacy_book_id',
        'slug',
        'name',
        'short_name',
        'aliases_json',
        'path_name',
        'book_order',
        'chapters_count',
        'show_verse_numbers',
    ];

    protected function casts(): array
    {
        return [
            'aliases_json' => 'array',
            'show_verse_numbers' => 'boolean',
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

    public function canonicalBook(): BelongsTo
    {
        return $this->belongsTo(CanonicalBook::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(ModuleChapter::class)->orderBy('chapter_number');
    }
}
