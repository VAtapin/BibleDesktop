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

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSummaryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_summary_returns_lightweight_public_counters(): void
    {
        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'bibles',
                    'recipes',
                    'quizzes',
                    'tours',
                    'prayers',
                    'materials',
                    'faith_questions',
                ],
            ]);
    }
}
