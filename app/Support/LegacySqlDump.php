<?php

namespace App\Support;

use Generator;
use RuntimeException;
use SplFileObject;

class LegacySqlDump
{
    public function __construct(private readonly string $path) {}

    /**
     * @return Generator<int, array<string, mixed>>
     */
    public function rows(string $table): Generator
    {
        if (! is_file($this->path)) {
            throw new RuntimeException("Legacy SQL dump not found: {$this->path}");
        }

        $file = new SplFileObject($this->path, 'r');
        $readingInsert = false;
        $columns = [];

        while (! $file->eof()) {
            $line = rtrim((string) $file->fgets(), "\r\n");

            if (! $readingInsert) {
                if (str_starts_with($line, "INSERT INTO `{$table}`")) {
                    $columns = $this->extractColumns($line);
                    $readingInsert = true;
                }

                continue;
            }

            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            $isLastLine = str_ends_with($trimmed, ';');
            $tuple = rtrim($trimmed, ',;');

            if (str_starts_with($tuple, '(')) {
                $values = $this->parseTuple($tuple);

                if (count($columns) !== count($values)) {
                    throw new RuntimeException("Column/value mismatch while reading {$table}.");
                }

                yield array_combine($columns, $values);
            }

            if ($isLastLine) {
                $readingInsert = false;
                $columns = [];
            }
        }
    }

    /**
     * @return list<string>
     */
    private function extractColumns(string $line): array
    {
        if (! preg_match('/INSERT INTO `[^`]+` \((.+)\) VALUES/u', $line, $matches)) {
            throw new RuntimeException('Unable to parse INSERT columns.');
        }

        return array_map(
            fn (string $column) => trim($column, " `"),
            explode(',', $matches[1]),
        );
    }

    /**
     * @return list<mixed>
     */
    private function parseTuple(string $tuple): array
    {
        $body = trim($tuple);

        if ($body[0] === '(') {
            $body = substr($body, 1, -1);
        }

        $values = [];
        $token = '';
        $tokenWasString = false;
        $inString = false;
        $length = strlen($body);

        $push = function () use (&$values, &$token, &$tokenWasString): void {
            if ($tokenWasString) {
                $values[] = $token;
            } else {
                $trimmed = trim($token);
                $upper = strtoupper($trimmed);

                $values[] = match (true) {
                    $upper === 'NULL' => null,
                    preg_match('/^-?\d+$/', $trimmed) === 1 => (int) $trimmed,
                    is_numeric($trimmed) => (float) $trimmed,
                    default => $trimmed,
                };
            }

            $token = '';
            $tokenWasString = false;
        };

        for ($i = 0; $i < $length; $i++) {
            $char = $body[$i];

            if ($inString) {
                if ($char === '\\') {
                    $i++;
                    $token .= $this->unescapeChar($body[$i] ?? '');
                    continue;
                }

                if ($char === "'") {
                    if (($body[$i + 1] ?? '') === "'") {
                        $token .= "'";
                        $i++;
                        continue;
                    }

                    $inString = false;
                    continue;
                }

                $token .= $char;
                continue;
            }

            if ($char === "'") {
                $inString = true;
                $tokenWasString = true;

                if (trim($token) === '') {
                    $token = '';
                }

                continue;
            }

            if ($char === ',') {
                $push();
                continue;
            }

            $token .= $char;
        }

        $push();

        return $values;
    }

    private function unescapeChar(string $char): string
    {
        return match ($char) {
            '0' => "\0",
            'b' => "\b",
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'Z' => chr(26),
            default => $char,
        };
    }
}
