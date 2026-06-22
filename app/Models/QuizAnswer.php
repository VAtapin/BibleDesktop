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

class QuizAnswer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'quiz_question_id',
        'answer',
        'is_correct',
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
            'is_correct' => 'boolean',
            'recommended_prayer_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    public function recommendedPrayer(): BelongsTo
    {
        return $this->belongsTo(Prayer::class, 'recommended_prayer_id');
    }
}
