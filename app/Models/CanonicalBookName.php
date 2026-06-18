<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonicalBookName extends Model
{
    protected $fillable = [
        'canonical_book_id',
        'language_id',
        'name',
        'short_name',
        'aliases_json',
    ];

    protected function casts(): array
    {
        return [
            'aliases_json' => 'array',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(CanonicalBook::class, 'canonical_book_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
