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

class CalendarEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'calendar_event_type_id',
        'name',
        'legacy_type',
        'date_rule_type',
        'start_year',
        'end_year',
        'start_month',
        'start_day',
        'start_offset',
        'end_month',
        'end_day',
        'end_offset',
        'metadata_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'legacy_type' => 'integer',
            'start_year' => 'integer',
            'end_year' => 'integer',
            'start_month' => 'integer',
            'start_day' => 'integer',
            'start_offset' => 'integer',
            'end_month' => 'integer',
            'end_day' => 'integer',
            'end_offset' => 'integer',
            'metadata_json' => 'array',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CalendarEventType::class, 'calendar_event_type_id');
    }
}
