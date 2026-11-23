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

abstract class AdminController extends Controller
{

    private const FK_DIRUJUK = [1451, 1217];


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


    protected function akunTanpaProfil(string $role, string $tabelProfil): Collection
    {
        return DB::table('users as u')
            ->where('u.role', $role)
            ->whereNotExists(fn ($q) => $q->from($tabelProfil.' as p')->whereColumn('p.id_user', 'u.id_user'))
            ->orderBy('u.username')
            ->get(['u.id_user', 'u.username', 'u.is_active']);
    }


    protected function aturanIdUserProfil(string $role, string $tabelProfil): array
    {
        return [
            'required', 'integer',
            Rule::exists('users', 'id_user')->where('role', $role),
            Rule::unique($tabelProfil, 'id_user'),
        ];
    }


    protected function idTahunAjaranTerpilih(?string $dariRequest): ?int
    {
        if ($dariRequest !== null && ctype_digit($dariRequest)) {
            return (int) $dariRequest;
        }

        return TahunAjaran::aktif()?->id_tahun_ajaran;
    }
}
