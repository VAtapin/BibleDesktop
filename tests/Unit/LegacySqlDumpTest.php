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

use App\Support\LegacySqlDump;
use PHPUnit\Framework\TestCase;

class LegacySqlDumpTest extends TestCase
{
    public function test_it_reads_insert_rows_with_escaped_values(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'legacy-sql-');

        file_put_contents($path, <<<'SQL'
CREATE TABLE `demo` (`id` int, `name` varchar(255), `note` text, `enabled` int);
INSERT INTO `demo` (`id`, `name`, `note`, `enabled`) VALUES
(1, 'Русский текст', 'line\nwith \'quote\'', 1),
(2, 'English', NULL, 0);
SQL);

        $rows = iterator_to_array((new LegacySqlDump($path))->rows('demo'));

        @unlink($path);

        $this->assertCount(2, $rows);
        $this->assertSame('Русский текст', $rows[0]['name']);
        $this->assertSame("line\nwith 'quote'", $rows[0]['note']);
        $this->assertSame(1, $rows[0]['enabled']);
        $this->assertNull($rows[1]['note']);
    }
}
