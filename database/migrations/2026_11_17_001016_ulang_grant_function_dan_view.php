<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Migration 000033 memberi GRANT EXECUTE untuk stored function. Karena urutan migrasi,
// sebagian fungsi (mis. fn_jumlah_presensi) belum ada saat grant dijalankan sehingga
// grant terlewat diam-diam (ditangkap try/catch). Akibatnya portal orang tua gagal:
// "execute command denied to user 'ortu_app' for routine fn_jumlah_presensi".
//
// Migration ini mengulang grant function/view setelah semua objek pasti ada.
return new class extends Migration
{
    private function mysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    /** @return array<int, string> */
    private function accounts(): array
    {
        return [
            env('DB_USERNAME_GURU', 'guru_app'),
            env('DB_USERNAME_SISWA', 'siswa_app'),
            env('DB_USERNAME_ORTU', 'ortu_app'),
        ];
    }

    /** @return array<int, string> */
    private function functions(): array
    {
        return [
            'fn_jumlah_presensi',
            'fn_persentase_kehadiran',
            'fn_token_valid',
            'fn_boleh_memilih',
            'fn_total_suara',
        ];
    }

    /**
     * View yang boleh dibaca semua role hanya bila memang tidak memuat data sensitif.
     * CATATAN: v_kandidat_pemilihan_hasil / v_hasil_pemilihan TIDAK dimasukkan —
     * view itu berisi perolehan suara dan hanya untuk yang berhak (lihat GrantTest).
     *
     * @return array<int, string>
     */
    private function views(): array
    {
        return [
            'v_rekap_presensi_siswa',
            'v_rekap_sesi_presensi',
            'v_pemilihan_pengelola',
        ];
    }

    public function up(): void
    {
        if (! $this->mysql()) {
            return;
        }

        $db = config('database.connections.mysql.database');
        $host = 'localhost';

        foreach ($this->accounts() as $user) {
            foreach ($this->functions() as $fn) {
                $ada = DB::selectOne(
                    'SELECT COUNT(*) AS n FROM information_schema.routines
                     WHERE routine_schema = ? AND routine_name = ?',
                    [$db, $fn],
                );

                if ((int) ($ada->n ?? 0) === 0) {
                    continue;
                }

                try {
                    DB::unprepared("GRANT EXECUTE ON FUNCTION `{$db}`.`{$fn}` TO '{$user}'@'{$host}'");
                } catch (\Throwable $e) {
                    Log::warning("Lewati GRANT EXECUTE {$fn} untuk {$user}: ".$e->getMessage());
                }
            }

            foreach ($this->views() as $view) {
                $ada = DB::selectOne(
                    'SELECT COUNT(*) AS n FROM information_schema.views
                     WHERE table_schema = ? AND table_name = ?',
                    [$db, $view],
                );

                if ((int) ($ada->n ?? 0) === 0) {
                    continue;
                }

                try {
                    DB::unprepared("GRANT SELECT ON `{$db}`.`{$view}` TO '{$user}'@'{$host}'");
                } catch (\Throwable $e) {
                    Log::warning("Lewati GRANT SELECT {$view} untuk {$user}: ".$e->getMessage());
                }
            }
        }

        DB::unprepared('FLUSH PRIVILEGES');
    }

    public function down(): void
    {
        // Grant tidak dicabut: objek masih dipakai aplikasi.
    }
};
