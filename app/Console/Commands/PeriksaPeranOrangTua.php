<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Melaporkan akun orang tua yang berperan ganda (ayah DAN ibu sekaligus).
 *
 * Sejak aturan "satu akun = satu peran" diberlakukan, data baru tidak bisa lagi
 * dibuat seperti ini. Tetapi data lama tetap ada, dan TIDAK diubah otomatis:
 * mana yang salah input hanya bisa diputuskan manusia. Perintah ini hanya
 * menunjukkan mana yang perlu diperiksa.
 */
class PeriksaPeranOrangTua extends Command
{
    protected $signature = 'orang-tua:periksa-peran';

    protected $description = 'Laporkan akun orang tua yang tercatat sebagai ayah dan ibu sekaligus';

    public function handle(): int
    {
        $akun = DB::table('orang_tua_siswa as os')
            ->join('orang_tua as o', 'o.id_orang_tua', '=', 'os.id_orang_tua')
            ->select('o.id_orang_tua', 'o.nama')
            ->groupBy('o.id_orang_tua', 'o.nama')
            ->havingRaw("SUM(os.hubungan = 'ayah') > 0 AND SUM(os.hubungan = 'ibu') > 0")
            ->get();

        if ($akun->isEmpty()) {
            $this->info('Tidak ada akun orang tua dengan peran ganda.');

            return self::SUCCESS;
        }

        $this->warn("Ditemukan {$akun->count()} akun dengan peran ganda (ayah dan ibu):");

        foreach ($akun as $a) {
            $this->line('');
            $this->line("  {$a->nama} (id {$a->id_orang_tua})");

            $relasi = DB::table('orang_tua_siswa as os')
                ->join('siswa as s', 's.id_siswa', '=', 'os.id_siswa')
                ->where('os.id_orang_tua', $a->id_orang_tua)
                ->orderBy('s.nama_siswa')
                ->get(['s.nama_siswa', 'os.hubungan']);

            foreach ($relasi as $r) {
                $this->line("    - {$r->nama_siswa}: {$r->hubungan}");
            }
        }

        $this->line('');
        $this->comment('Perbaiki manual lewat menu Data Master > Orang Tua > Kelola.');
        $this->comment('Data tidak diubah otomatis supaya tidak ada perbaikan yang salah.');

        return self::SUCCESS;
    }
}
