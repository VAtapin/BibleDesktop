<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonicalChapter extends Model
{
    protected $fillable = [
        'canonical_book_id',
        'number',
        'verses_count',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(CanonicalBook::class, 'canonical_book_id');
    }
}
