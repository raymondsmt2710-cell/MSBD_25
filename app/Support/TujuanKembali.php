<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class TujuanKembali
{

    public static function untuk(Request $request, ?User $pengguna): ?string
    {
        if ($pengguna === null) {
            return null;
        }

        $beranda = route($pengguna->role->home());




        if (self::samaHalaman($request->fullUrl(), $beranda)) {
            return null;
        }

        return $beranda;
    }


    private static function samaHalaman(string $a, string $b): bool
    {
        $pathA = rtrim(parse_url($a, PHP_URL_PATH) ?: '/', '/');
        $pathB = rtrim(parse_url($b, PHP_URL_PATH) ?: '/', '/');

        return $pathA === $pathB;
    }
}
