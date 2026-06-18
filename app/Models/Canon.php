<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Canon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function books(): HasMany
    {
        return $this->hasMany(CanonicalBook::class)->orderBy('canonical_order');
    }
}
