<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    public static function email(string $v): bool
    {
        return filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function username(string $v): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9_.]{3,24}$/', $v);
    }

    public static function lat(mixed $v): bool
    {
        if (!is_numeric($v)) return false;
        $f = (float)$v;
        return $f >= -90 && $f <= 90;
    }

    public static function lng(mixed $v): bool
    {
        if (!is_numeric($v)) return false;
        $f = (float)$v;
        return $f >= -180 && $f <= 180;
    }

    public static function phone(string $v): bool
    {
        if ($v === '') return true; // opsional
        return (bool)preg_match('/^[0-9+\-\s()]{8,18}$/', $v);
    }
}
