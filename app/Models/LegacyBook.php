<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyBook extends Model
{
    protected $fillable = [
        'legacy_id',
        'legacy_bible_id',
        'module_book_id',
        'canonical_book_id',
        'raw_json',
    ];

    protected function casts(): array
    {
        return [
            'raw_json' => 'array',
        ];
    }

    public function moduleBook(): BelongsTo
    {
        return $this->belongsTo(ModuleBook::class);
    }

    public function canonicalBook(): BelongsTo
    {
        return $this->belongsTo(CanonicalBook::class);
    }
}
