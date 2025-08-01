<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;

/**
 * Search helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Search
{
    /**
     * Normalizes search keywords.
     *
     * @param string|string[] $str The dirty keywords
     * @param array $ignore Ignore words to strip out
     * @param bool $processCharMap Whether to remove punctuation and diacritics (default is true)
     * @param string|null $language The language that the character map should be based on, if `$processCharMap` is `true`.
     * @return string The cleansed keywords.
     */
    public static function normalizeKeywords(array|string $str, array $ignore = [], bool $processCharMap = true, ?string $language = null): string
    {
        // Flatten
        if (is_array($str)) {
            $str = StringHelper::toString($str, ' ');
        }

        // Get rid of tags
        $str = strip_tags(preg_replace(['/<br\s*\/?>/i', '/<\/\w+>/'], [' ', ' $1'], $str));

        // Convert non-breaking spaces entities to regular ones
        $str = str_replace(['&nbsp;', '&#160;', '&#xa0;'], ' ', $str);

        // Get rid of entities
        $str = preg_replace('/&#?[a-z0-9]{2,8};/i', '', $str);

        // Get rid of emoji
        $str = StringHelper::replaceMb4($str, '');

        // Normalize to lowercase
        $str = mb_strtolower($str);

        if ($processCharMap) {
            $str = strtr($str, StringHelper::asciiCharMap(true, $language ?? Craft::$app->language));

            $str = preg_replace(self::_getElisionsRegex(), '', $str);

            // Remove punctuation and diacritics
            $punctuation = self::_getPunctuation();
            $str = str_replace(array_keys($punctuation), $punctuation, $str);
        }

        // Remove ignore-words?
        if (!empty($ignore)) {
            foreach ($ignore as $word) {
                $word = preg_quote(static::normalizeKeywords($word, [], true, $language), '/');
                $str = preg_replace("/\b$word\b/u", '', $str);
            }
        }

        // Get rid of invisible Unicode special characters
        // (see https://github.com/craftcms/cms/issues/16430)
        $str = preg_replace(StringHelper::invisibleCharsRegex(), '', $str);

        // Strip out new lines and superfluous spaces
        return trim(preg_replace(['/[\n\r]+/u', '/\s{2,}/u'], ' ', $str));
    }

    /**
     * Returns a regex pattern for elisions.
     *
     * @return string
     */
    private static function _getElisionsRegex(): string
    {
        static $elisions = null;

        if (!$elisions) {
            $elisionsArr = [
                'l',
                'm',
                't',
                'qu',
                'n',
                's',
                'j',
                'd',
                'c',
                'jusqu',
                'quoiqu',
                'lorsqu',
                'puisqu',
            ];
            $elisions = sprintf('/\b(%s)\'/', implode('|', $elisionsArr));
        }

        return $elisions;
    }

    /**
     * Returns the asciiPunctuation array.
     *
     * @return array
     */
    private static function _getPunctuation(): array
    {
        // Keep local copy
        static $asciiPunctuation = [];

        if (empty($asciiPunctuation)) {
            $asciiPunctuation = [
                '!' => ' ',
                '"' => ' ',
                '#' => ' ',
                '&' => ' ',
                "'" => '',
                '(' => ' ',
                ')' => ' ',
                '*' => ' ',
                '+' => ' ',
                ',' => ' ',
                '-' => ' ',
                '.' => ' ',
                '/' => ' ',
                ':' => ' ',
                ';' => ' ',
                '<' => ' ',
                '>' => ' ',
                '?' => ' ',
                '@' => ' ',
                '[' => ' ',
                '\\' => ' ',
                ']' => ' ',
                '^' => ' ',
                '{' => ' ',
                '|' => ' ',
                '}' => ' ',
                '~' => ' ',
                '¡' => ' ',
                '¢' => ' ',
                '£' => ' ',
                '¤' => ' ',
                '¥' => ' ',
                '¦' => ' ',
                '§' => ' ',
                '¨' => ' ',
                '©' => ' ',
                'ª' => ' ',
                '«' => ' ',
                '¬' => ' ',
                '®' => ' ',
                '¯' => ' ',
                '°' => ' ',
                '±' => ' ',
                '²' => ' ',
                '³' => ' ',
                '´' => ' ',
                'µ' => ' ',
                '¶' => ' ',
                '·' => ' ',
                '¸' => ' ',
                '¹' => ' ',
                'º' => ' ',
                '»' => ' ',
                '¼' => ' ',
                '½' => ' ',
                '¾' => ' ',
                '¿' => ' ',
                '×' => ' ',
                'ƒ' => ' ',
                'ˆ' => ' ',
                '˜' => ' ',
                '–' => ' ',
                '—' => ' ',
                '―' => ' ',
                '_' => ' ',
                '‘' => '',
                '’' => '',
                '‚' => ' ',
                '“' => ' ',
                '”' => ' ',
                '„' => ' ',
                '†' => ' ',
                '‡' => ' ',
                '•' => ' ',
                '‣' => ' ',
                '…' => ' ',
                '‰' => ' ',
                '′' => ' ',
                '″' => ' ',
                '‹' => ' ',
                '›' => ' ',
                '‼' => ' ',
                '‾' => ' ',
                '⁄' => ' ',
                '€' => ' ',
                '™' => ' ',
                '←' => ' ',
                '↑' => ' ',
                '→' => ' ',
                '↓' => ' ',
                '↔' => ' ',
                '↵' => ' ',
                '⇐' => ' ',
                '⇑' => ' ',
                '⇒' => ' ',
                '⇓' => ' ',
                '⇔' => ' ',
                '∀' => ' ',
                '∂' => ' ',
                '∃' => ' ',
                '∅' => ' ',
                '∇' => ' ',
                '∈' => ' ',
                '∉' => ' ',
                '∋' => ' ',
                '∏' => ' ',
                '∑' => ' ',
                '−' => ' ',
                '∗' => ' ',
                '√' => ' ',
                '∝' => ' ',
                '∞' => ' ',
                '∠' => ' ',
                '∧' => ' ',
                '∨' => ' ',
                '∩' => ' ',
                '∪' => ' ',
                '∫' => ' ',
                '∴' => ' ',
                '∼' => ' ',
                '≅' => ' ',
                '≈' => ' ',
                '≠' => ' ',
                '≡' => ' ',
                '≤' => ' ',
                '≥' => ' ',
                '⊂' => ' ',
                '⊃' => ' ',
                '⊄' => ' ',
                '⊆' => ' ',
                '⊇' => ' ',
                '⊕' => ' ',
                '⊗' => ' ',
                '⊥' => ' ',
                '⋅' => ' ',
                '⌈' => ' ',
                '⌉' => ' ',
                '⌊' => ' ',
                '⌋' => ' ',
                '〈' => ' ',
                '〉' => ' ',
                '◊' => ' ',
                '♠' => ' ',
                '♣' => ' ',
                '♥' => ' ',
                '♦' => ' ',
            ];
        }

        return $asciiPunctuation;
    }
}
