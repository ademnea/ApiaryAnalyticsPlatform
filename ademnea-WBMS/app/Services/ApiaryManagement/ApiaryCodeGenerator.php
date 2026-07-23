<?php

namespace App\Services\ApiaryManagement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiaryCodeGenerator
{
    public static function generate(string $name, string $country): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $words = array_filter($words, fn ($w) => $w !== '');

        if (count($words) >= 2) {
            $initials = collect($words)
                ->map(fn ($w) => strtoupper(Str::substr($w, 0, 1)))
                ->implode('');
            $base = Str::substr($initials, 0, 3);
        } else {
            $base = strtoupper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));
        }

        $base = $base !== '' ? $base : strtoupper(Str::substr($country, 0, 2)).'X';

        $candidate = $base;
        $suffix = 1;

        while (DB::table('apiaries')->where('apiary_code', $candidate)->exists()) {
            $candidate = $base.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
