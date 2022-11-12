<?php
declare(strict_types=1);

namespace Rw4lll\ICal;

class StringHelper
{
    /**
     * Removes unprintable ASCII and UTF-8 characters
     *
     * @param string $data
     * @return string
     */
    public static function removeUnprintableChars(string $data): string
    {
        return preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $data);
    }

    /**
     * Replaces curly quotes and other special characters
     * with their standard equivalents
     *
     * @param string $data
     * @return string
     */
    public static function cleanData(string $data): string
    {
        $replacementChars = [
            "\xe2\x80\x98" => "'",   // ‘
            "\xe2\x80\x99" => "'",   // ’
            "\xe2\x80\x9a" => "'",   // ‚
            "\xe2\x80\x9b" => "'",   // ‛
            "\xe2\x80\x9c" => '"',   // “
            "\xe2\x80\x9d" => '"',   // ”
            "\xe2\x80\x9e" => '"',   // „
            "\xe2\x80\x9f" => '"',   // ‟
            "\xe2\x80\x93" => '-',   // –
            "\xe2\x80\x94" => '--',  // —
            "\xe2\x80\xa6" => '...', // …
            "\xc2\xa0" => ' ',
        ];
        // Replace UTF-8 characters
        $cleanedData = strtr($data, $replacementChars);

        // Replace Windows-1252 equivalents
        $charsToReplace = array_map(function ($code) {
            return mb_chr($code);
        }, [133, 145, 146, 147, 148, 150, 151, 194]);

        return static::mb_str_replace($charsToReplace, $replacementChars, $cleanedData);
    }

    /**
     * Replace all occurrences of the search string with the replacement string.
     * Multibyte safe.
     *
     * @param string|array $search
     * @param string|array $replace
     * @param string|array $subject
     * @param string|null $encoding
     * @param int $count
     * @return array|string
     */
    protected static function mb_str_replace(
        $search,
        $replace,
        $subject,
        string $encoding = null,
        int &$count = 0
    ) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (is_array($subject)) {
            // Call `mb_str_replace()` for each subject in the array, recursively
            foreach ($subject as $key => $value) {
                $subject[$key] = self::mb_str_replace($search, $replace, $value, $encoding, $count);
            }
        } else {
            // Normalize $search and $replace, so they are both arrays of the same length
            $searches = is_array($search) ? array_values($search) : [$search];
            $replacements = is_array($replace) ? array_values($replace) : [$replace];
            $replacements = array_pad($replacements, count($searches), '');

            foreach ($searches as $key => $search) {
                if (is_null($encoding)) {
                    $encoding = mb_detect_encoding($search, 'UTF-8', true);
                }

                $replace = $replacements[$key];
                $searchLen = mb_strlen($search, $encoding);

                $sb = [];
                while (($offset = mb_strpos($subject, $search, 0, $encoding)) !== false) {
                    $sb[] = mb_substr($subject, 0, $offset, $encoding);
                    $subject = mb_substr($subject, $offset + $searchLen, null, $encoding);
                    ++$count;
                }

                $sb[] = $subject;
                $subject = implode($replace, $sb);
            }
        }

        return $subject;
    }
}
