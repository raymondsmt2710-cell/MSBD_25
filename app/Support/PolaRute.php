<?php

namespace App\Support;

// Pola pembatas parameter URL yang dipakai routes/web.php.
//
// Kenapa perlu: parameter controller bertipe `int` tidak bisa menampung nilai di luar
// rentang PHP_INT_MAX. Tanpa batas, PHP melempar TypeError dan pengguna melihat
// 500 + stack trace alih-alih 404. whereNumber() bawaan Laravel hanya memakai [0-9]+,
// sehingga angka sepanjang apa pun lolos.
final class PolaRute
{
    /** Id basis data: 1–10 digit. Menolak huruf, negatif, desimal, dan angka > PHP_INT_MAX. */
    public const ID = '[0-9]{1,10}';
}
