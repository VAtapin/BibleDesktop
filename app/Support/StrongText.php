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

namespace App\Support;

/**
 * Normalizes Bible verse text while keeping Strong numbers inside the verse string.
 */
class StrongText
{
    /**
     * Strip imported reader markup and preserve Strong numbers as plain inline text.
     */
    public static function cleanModuleText(string $value): string
    {
        $text = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<\s*font\b[^>]*color\s*=\s*["\']?darkred["\']?[^>]*>/iu', '[[BD_RED_OPEN]]', $text) ?? $text;
        $text = preg_replace('/<\s*\/\s*font\s*>/iu', '[[BD_RED_CLOSE]]', $text) ?? $text;
        $text = preg_replace('/<\s*(?:br|pb)\b[^>]*\/?\s*>/iu', ' ', $text) ?? $text;
        $text = preg_replace('/<\s*s\b[^>]*>\s*([GH]?\d{1,5})\s*<\s*\/\s*s\s*>/iu', ' $1 ', $text) ?? $text;
        $text = strip_tags($text);
        $text = str_replace(['[[BD_RED_OPEN]]', '[[BD_RED_CLOSE]]'], ['<font color="darkred">', '</font>'], $text);
        $text = preg_replace('/^\s*\d{1,3}\s+(?=\p{L})/u', '', $text) ?? $text;
        $text = preg_replace('/\s+([,.;:!?])/u', '$1', $text) ?? $text;
        $text = preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);

        return $text;
    }

    /**
     * Detect whether a verse already contains Strong-like markers.
     */
    public static function hasStrongNumbers(string $text): bool
    {
        return preg_match(self::numberPattern(), $text) === 1;
    }

    /**
     * @return list<string>
     */
    public static function numbers(string $text, bool $unique = true): array
    {
        if (! preg_match_all(self::numberPattern(), $text, $matches)) {
            return [];
        }

        $numbers = array_values($matches[0]);

        return $unique ? array_values(array_unique($numbers)) : $numbers;
    }

    /**
     * Remove Strong numbers for search snippets and ordinary reading text.
     */
    public static function textWithoutNumbers(string $text): string
    {
        $text = preg_replace('/\s*'.self::numberPatternBody().'/u', '', $text) ?? $text;
        $text = strip_tags($text);
        $text = preg_replace('/\s+([,.;:!?])/u', '$1', $text) ?? $text;

        return preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);
    }

    /**
     * @return list<array{id: int, strong_number: string, token_order: int, surface_text: null, grammar_code: null, entry: array{word: null, transliteration: null}}>
     */
    public static function tokenDtos(string $text): array
    {
        $numbers = self::numbers($text, false);

        return array_map(
            fn (string $number, int $index): array => [
                'id' => $index + 1,
                'strong_number' => $number,
                'token_order' => $index + 1,
                'surface_text' => null,
                'grammar_code' => null,
                'entry' => [
                    'word' => null,
                    'transliteration' => null,
                ],
            ],
            $numbers,
            array_keys($numbers),
        );
    }

    public static function numberPattern(): string
    {
        return '/'.self::numberPatternBody().'/u';
    }

    private static function numberPatternBody(): string
    {
        return '\b(?:[GH]\d{1,5}|\d{3,5})\b';
    }
}
