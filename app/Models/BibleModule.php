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

class BibleModule extends Model
{
    protected $table = 'modules';

    protected $fillable = [
        'language_id',
        'type',
        'code',
        'name',
        'short_name',
        'description',
        'version',
        'metadata_json',
        'is_active',
        'is_public',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'module_id');
    }

    public function books(): HasMany
    {
        return $this->hasMany(ModuleBook::class, 'module_id');
    }
}
