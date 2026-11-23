<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

// Tujuan tombol "kembali".
//
// ATURAN: tombol kembali SELALU menuju dashboard peran pengguna
// (siswa.beranda / guru.beranda / admin.dashboard / ortu.index).
//
// Kenapa bukan riwayat browser, dan kenapa bukan hierarki halaman:
//
//   Dulu tombol ini menebak tujuan dari referer, lalu dari peta hierarki
//   halaman (anak -> induk). Hasilnya tidak bisa ditebak pengguna: tombol
//   "Kembali" di halaman presensi menuju menu presensi, di halaman sesi menuju
//   roster, di halaman riwayat menuju menu presensi, dan seterusnya. Pengguna
//   harus mengingat dari mana ia datang untuk tahu ke mana tombol akan membawa.
//
//   Sekarang satu tujuan untuk semua halaman: dashboard. Satu klik selalu
//   membawa pengguna ke tempat yang sama, dari halaman mana pun.
//
// Referer tidak lagi dipakai sama sekali, sehingga kasus-kasus yang dulu harus
// ditambal satu per satu tidak mungkin terjadi lagi:
//   - referer berupa URL POST setelah menyimpan form (dulu bisa 405)
//   - referer dari domain lain (dulu harus ditolak manual)
//   - referer sama dengan halaman ini setelah menekan menu dua kali
//   - referer anak/leluhur yang membuat tombol bolak-balik tanpa pernah naik
//   - halaman hasil redirect setelah aksi (pesan success/error di session)
final class TujuanKembali
{
    /**
     * URL tujuan tombol kembali, atau null bila tombolnya tidak perlu dirender.
     *
     * Null hanya untuk tamu dan untuk dashboard itu sendiri: di dashboard tidak
     * ada tempat untuk kembali, karena dashboard adalah puncak navigasi.
     */
    public static function untuk(Request $request, ?User $pengguna): ?string
    {
        if ($pengguna === null) {
            return null;
        }

        $beranda = route($pengguna->role->home());

        // Di dashboard, tombol kembali tidak ditampilkan. Perbandingan memakai
        // host request (bukan APP_URL) supaya aplikasi tetap dikenali baik saat
        // dibuka lewat localhost maupun 127.0.0.1.
        if (self::samaHalaman($request->fullUrl(), $beranda)) {
            return null;
        }

        return $beranda;
    }

    /**
     * Apakah dua URL menunjuk halaman yang sama, mengabaikan query dan garis
     * miring di akhir?
     *
     * Query diabaikan karena halaman memakai navigasi seperti ?pekan=... pada
     * path yang sama; itu tetap halaman yang sama.
     */
    private static function samaHalaman(string $a, string $b): bool
    {
        $pathA = rtrim(parse_url($a, PHP_URL_PATH) ?: '/', '/');
        $pathB = rtrim(parse_url($b, PHP_URL_PATH) ?: '/', '/');

        return $pathA === $pathB;
    }
}
