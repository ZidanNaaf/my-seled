<?php
declare(strict_types=1);

namespace App\Services;

class GeoService
{
    // Client-side memakai Nominatim langsung dengan debounce.
    // Server hanya validasi rentang & normalisasi alamat (tidak menyimpan API key).
    public static function normalizeAddress(string $raw): string
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw));
        return mb_substr($raw, 0, 500);
    }
}
