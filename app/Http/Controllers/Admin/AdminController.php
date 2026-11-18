<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Induk semua controller panel admin.
 *
 * Otorisasi role ditangani middleware `role:admin` di routes/admin.php;
 * kelas ini hanya menampung pembantu yang dipakai bersama.
 */
abstract class AdminController extends Controller
{
    /** Kode MySQL untuk pelanggaran foreign key saat DELETE (baris masih dirujuk). */
    private const FK_DIRUJUK = [1451, 1217];

    /**
     * Hapus baris, tetapi ubah pelanggaran foreign key (ON DELETE RESTRICT) menjadi
     * pesan yang bisa dibaca, bukan halaman 500.
     */
    protected function hapusAman(Model $model, string $label, string $alasanDirujuk): RedirectResponse
    {
        try {
            $model->delete();
        } catch (QueryException $e) {
            if (in_array((int) ($e->errorInfo[1] ?? 0), self::FK_DIRUJUK, true)) {
                return back()->with('error', "{$label} tidak dapat dihapus: {$alasanDirujuk}");
            }

            throw $e;
        }

        return back()->with('success', "{$label} berhasil dihapus.");
    }

    /**
     * Akun ber-role tertentu yang belum punya baris profil, untuk dipilih di form profil.
     * Termasuk akun $idUserTetap (dipakai saat mengedit profil yang sudah ada).
     */
    protected function akunTanpaProfil(string $role, string $tabelProfil): Collection
    {
        return DB::table('users as u')
            ->where('u.role', $role)
            ->whereNotExists(fn ($q) => $q->from($tabelProfil.' as p')->whereColumn('p.id_user', 'u.id_user'))
            ->orderBy('u.username')
            ->get(['u.id_user', 'u.username', 'u.is_active']);
    }

    /** Aturan validasi id_user untuk profil baru: akun ada, role cocok, belum dipakai profil lain. */
    protected function aturanIdUserProfil(string $role, string $tabelProfil): array
    {
        return [
            'required', 'integer',
            Rule::exists('users', 'id_user')->where('role', $role),
            Rule::unique($tabelProfil, 'id_user'),
        ];
    }

    /** Id tahun ajaran dari query string, jatuh ke tahun ajaran aktif bila kosong. */
    protected function idTahunAjaranTerpilih(?string $dariRequest): ?int
    {
        if ($dariRequest !== null && ctype_digit($dariRequest)) {
            return (int) $dariRequest;
        }

        return TahunAjaran::aktif()?->id_tahun_ajaran;
    }
}
