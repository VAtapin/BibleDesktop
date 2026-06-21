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

use App\Support\TskReferenceParser;
use PHPUnit\Framework\TestCase;

class TskReferenceParserTest extends TestCase
{
    public function test_it_parses_tsk_references_and_ranges(): void
    {
        $result = (new TskReferenceParser)->parseList('1Ch 16:26; Joh 1:1-3; 1 Pet 1:2');

        $this->assertSame(0, $result['skipped']);
        $this->assertSame([
            [
                'book_slug' => '1chron',
                'chapter' => 16,
                'verse_start' => 26,
                'verse_end' => 26,
                'raw' => '1Ch 16:26',
            ],
            [
                'book_slug' => 'john',
                'chapter' => 1,
                'verse_start' => 1,
                'verse_end' => 3,
                'raw' => 'Joh 1:1-3',
            ],
            [
                'book_slug' => '1peter',
                'chapter' => 1,
                'verse_start' => 2,
                'verse_end' => 2,
                'raw' => '1 Pet 1:2',
            ],
        ], $result['references']);
    }

    public function test_it_skips_unknown_or_ambiguous_references(): void
    {
        $result = (new TskReferenceParser)->parseList('Joh 1:3; 141 141:3; Nope 1:1');

        $this->assertSame(2, $result['skipped']);
        $this->assertCount(1, $result['references']);
        $this->assertSame('john', $result['references'][0]['book_slug']);
    }
}
