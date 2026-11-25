<?php

namespace App\Services\Licensing;

use RuntimeException;

final class StrictJson
{
    public static function decodeObject(string $json): array
    {
        $index = 0;
        self::value($json, $index);
        self::space($json, $index);
        if ($index !== strlen($json)) throw new RuntimeException('JWS_JSON_TRAILING_BYTES');
        $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || array_is_list($decoded)) throw new RuntimeException('JWS_JSON_OBJECT_REQUIRED');
        return $decoded;
    }

    private static function value(string $json, int &$index): void
    {
        self::space($json, $index); $char = $json[$index] ?? '';
        if ($char === '{') { self::object($json, $index); return; }
        if ($char === '[') { self::array($json, $index); return; }
        if ($char === '"') { self::string($json, $index); return; }
        $start = $index;
        while ($index < strlen($json) && ! str_contains(" \t\r\n,]}", $json[$index])) $index++;
        if ($start === $index) throw new RuntimeException('JWS_JSON_VALUE_INVALID');
        $primitive = substr($json, $start, $index - $start);
        if (! in_array($primitive, ['true', 'false', 'null'], true) && ! preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?$/D', $primitive)) throw new RuntimeException('JWS_JSON_PRIMITIVE_INVALID');
    }

    private static function object(string $json, int &$index): void
    {
        $index++; self::space($json, $index); $keys = [];
        if (($json[$index] ?? '') === '}') { $index++; return; }
        while (true) {
            self::space($json, $index);
            if (($json[$index] ?? '') !== '"') throw new RuntimeException('JWS_JSON_KEY_INVALID');
            $key = self::string($json, $index);
            if (isset($keys[$key])) throw new RuntimeException('JWS_JSON_DUPLICATE_KEY');
            $keys[$key] = true; self::space($json, $index);
            if (($json[$index] ?? '') !== ':') throw new RuntimeException('JWS_JSON_COLON_MISSING');
            $index++; self::value($json, $index); self::space($json, $index);
            $next = $json[$index] ?? '';
            if ($next === '}') { $index++; return; }
            if ($next !== ',') throw new RuntimeException('JWS_JSON_OBJECT_SEPARATOR_INVALID');
            $index++;
        }
    }

    private static function array(string $json, int &$index): void
    {
        $index++; self::space($json, $index);
        if (($json[$index] ?? '') === ']') { $index++; return; }
        while (true) {
            self::value($json, $index); self::space($json, $index);
            $next = $json[$index] ?? '';
            if ($next === ']') { $index++; return; }
            if ($next !== ',') throw new RuntimeException('JWS_JSON_ARRAY_SEPARATOR_INVALID');
            $index++;
        }
    }

    private static function string(string $json, int &$index): string
    {
        $start = $index++; $escaped = false;
        while ($index < strlen($json)) {
            $char = $json[$index++];
            if ($escaped) { $escaped = false; continue; }
            if ($char === '\\') { $escaped = true; continue; }
            if ($char === '"') return json_decode(substr($json, $start, $index - $start), true, 8, JSON_THROW_ON_ERROR);
        }
        throw new RuntimeException('JWS_JSON_STRING_UNTERMINATED');
    }

    private static function space(string $json, int &$index): void
    {
        while (isset($json[$index]) && str_contains(" \t\r\n", $json[$index])) $index++;
    }
}
