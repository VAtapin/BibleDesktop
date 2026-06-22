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

class QuizQuestion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'quiz_id',
        'question',
        'answer_type',
        'image_path',
        'explanation',
        'recommendation_type',
        'recommended_prayer_id',
        'recommended_passage_ref',
        'recommendation_text',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'recommended_prayer_id' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function recommendedPrayer(): BelongsTo
    {
        return $this->belongsTo(Prayer::class, 'recommended_prayer_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class)->orderBy('sort_order');
    }
}
