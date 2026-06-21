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

namespace Tests\Unit;

use App\Support\StrongText;
use PHPUnit\Framework\TestCase;

class StrongTextTest extends TestCase
{
    public function test_it_removes_strong_numbers_without_leaving_spaces_before_punctuation(): void
    {
        $this->assertSame(
            'Пророческое слово Господа к Израилю через Малахию.',
            StrongText::textWithoutNumbers('Пророческое слово Господа к Израилю через Малахию H4401.'),
        );
    }

    public function test_it_treats_bare_one_as_a_strong_number(): void
    {
        $this->assertSame(
            'Если Я Отец, то где почтение ко Мне?',
            StrongText::textWithoutNumbers('Если Я Отец 1, то где почтение ко Мне?'),
        );

        $this->assertSame(['1'], StrongText::numbers('Отец 1,', false));
    }
}
