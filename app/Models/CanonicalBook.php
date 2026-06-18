<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanonicalBook extends Model
{
    protected $fillable = [
        'canon_id',
        'slug',
        'osis_code',
        'testament',
        'canonical_order',
        'default_chapters_count',
        'is_deuterocanonical',
    ];

    protected function casts(): array
    {
        return [
            'is_deuterocanonical' => 'boolean',
        ];
    }

    public function canon(): BelongsTo
    {
        return $this->belongsTo(Canon::class);
    }

    public function names(): HasMany
    {
        return $this->hasMany(CanonicalBookName::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(CanonicalChapter::class)->orderBy('number');
    }
}
