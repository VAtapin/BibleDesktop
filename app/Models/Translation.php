<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Translation extends Model
{
    protected $fillable = [
        'module_id',
        'language_id',
        'canon_id',
        'code',
        'name',
        'short_name',
        'copyright',
        'license',
        'source',
        'has_old_testament',
        'has_new_testament',
        'has_apocrypha',
        'has_strong',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'has_old_testament' => 'boolean',
            'has_new_testament' => 'boolean',
            'has_apocrypha' => 'boolean',
            'has_strong' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(BibleModule::class, 'module_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function canon(): BelongsTo
    {
        return $this->belongsTo(Canon::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(ModuleBook::class);
    }
}
