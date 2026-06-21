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

class ModuleChapter extends Model
{
    protected $fillable = [
        'module_book_id',
        'canonical_chapter_id',
        'legacy_chapter_id',
        'chapter_number',
        'title',
        'verses_count',
    ];

    public function moduleBook(): BelongsTo
    {
        return $this->belongsTo(ModuleBook::class);
    }

    public function canonicalChapter(): BelongsTo
    {
        return $this->belongsTo(CanonicalChapter::class);
    }
}
