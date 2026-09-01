<?php

declare(strict_types=1);

namespace Prescia\Services;

final class Sanitizer
{
    public static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function stripTags(string $value, bool $preserveLineBreaks = false): string
    {
        $result = strip_tags($value);
        if ($preserveLineBreaks) {
            $result = str_replace(["\r\n", "\r", "\n"], "<br/>\n", $result);
        }
        return $result;
    }

    public static function text(mixed $value): string
    {
        if (is_array($value) || is_object($value) || is_resource($value)) {
            return '';
        }
        $text = (string) $value;
        return function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')
            ? ''
            : trim($text);
    }
}
