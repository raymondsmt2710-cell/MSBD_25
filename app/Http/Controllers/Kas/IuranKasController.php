<?php

namespace App\Http\Controllers\Kas;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Enums\StatusIuran;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kas\BatalIuranRequest;
use App\Http\Requests\Kas\GenerateIuranRequest;
use App\Http\Requests\Kas\LaporIuranRequest;
use App\Http\Requests\Kas\VerifikasiIuranRequest;
use App\Models\IuranKas;
use App\Models\Kelas;
use App\Models\TransaksiKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class IuranKasController extends Controller
{
    public function generate(GenerateIuranRequest $request): JsonResponse
    {
        $kelas = Kelas::findOrFail($request->integer('id_kelas'));
        Gate::authorize('kas.kelola', $kelas);

        $periode = $request->string('periode')->toString();
        $nominal = (float) $request->input('nominal');
        $dibuat = 0;

        DB::transaction(function () use ($kelas, $periode, $nominal, &$dibuat) {
            $idSiswa = $kelas->anggotaKelas()->pluck('id_siswa');

            foreach ($idSiswa as $id) {
                $iuran = IuranKas::firstOrCreate(
                    [
                        'id_kelas' => $kelas->id_kelas,
                        'id_siswa' => $id,
                        'periode' => $periode,
                    ],
                    [
                        'nominal' => $nominal,
                        'status' => StatusIuran::BelumBayar,
                    ],
                );

                if ($iuran->wasRecentlyCreated) {
                    $dibuat++;
                }
            }
        });

        return response()->json([
            'message' => "{$dibuat} tagihan iuran dibuat.",
            'dibuat' => $dibuat,
        ]);
    }

    public function verifikasi(VerifikasiIuranRequest $request, IuranKas $iuran): JsonResponse
    {
        Gate::authorize('kas.kelola', $iuran->kelas);

        DB::transaction(function () use ($request, $iuran) {
            $iuran = IuranKas::query()
                ->whereKey($iuran->id_iuran)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $iuran->status === StatusIuran::Terverifikasi,
                422,
                'Iuran ini sudah terverifikasi.',
            );

            if ($request->string('status')->toString() === StatusIuran::Ditolak->value) {
                $iuran->update([
                    'status' => StatusIuran::Ditolak,
                    'catatan' => $request->input('catatan'),
                    'diverifikasi_oleh' => $request->user()->id_user,
                    'diverifikasi_pada' => now(),
                ]);

                return;
            }

            $transaksi = TransaksiKas::create([
                'id_kelas' => $iuran->id_kelas,
                'jenis' => JenisKas::Pemasukan->value,
                'kategori' => KategoriKas::IuranRutin->value,
                'nominal' => (float) $iuran->nominal,
                'tanggal' => now()->toDateString(),
                'keterangan' => "Iuran kas periode {$iuran->periode} - {$iuran->siswa?->nama_siswa}",
                'id_siswa' => $iuran->id_siswa,
                'dicatat_oleh' => $request->user()->id_user,
            ]);

            $iuran->update([
                'status' => StatusIuran::Terverifikasi,
                'id_transaksi' => $transaksi->id_transaksi,
                'diverifikasi_oleh' => $request->user()->id_user,
                'diverifikasi_pada' => now(),
                'catatan' => $request->input('catatan'),
            ]);
        });

        return response()->json([
            'message' => 'Status iuran diperbarui.',
            'iuran' => $iuran->fresh('transaksi'),
        ]);
    }

    /**
     * Orang tua melaporkan pembayaran iuran anaknya.
     *
     * Sengaja TIDAK memakai kas.kelola: uang belum masuk. Status hanya menjadi
     * "menunggu" dan transaksi kas baru dibuat saat bendahara memverifikasi.
     */
    public function lapor(LaporIuranRequest $request, IuranKas $iuran): JsonResponse
    {
        abort_if(
            $iuran->status === StatusIuran::Terverifikasi,
            422,
            'Iuran ini sudah diverifikasi bendahara.',
        );

        $data = $request->validated();

        $bukti = $request->file('bukti')->store('kas/bukti', 'public');

        // Ganti bukti lama bila ada, agar tidak menumpuk file yatim.
        if ($iuran->bukti_url && ! str_starts_with($iuran->bukti_url, 'http')) {
            Storage::disk('public')->delete($iuran->bukti_url);
        }

        $iuran->update([
            'status' => StatusIuran::Menunggu,
            'bukti_url' => $bukti,
            'catatan' => $data['catatan'] ?? null,
            // Nominal hanya boleh diusulkan lebih kecil/sama; nilai tagihan
            // tetap ditetapkan bendahara lewat generate().
            'nominal' => $data['nominal'] ?? $iuran->nominal,
        ]);

        return response()->json([
            'message' => 'Bukti pembayaran terkirim. Menunggu verifikasi bendahara.',
            'iuran' => $iuran->fresh(),
        ]);
    }

    public function batal(BatalIuranRequest $request, IuranKas $iuran): JsonResponse
    {
        Gate::authorize('kas.kelola', $iuran->kelas);

        abort_unless(
            $iuran->status === StatusIuran::Terverifikasi,
            422,
            'Hanya iuran terverifikasi yang dapat dibatalkan.',
        );

        DB::transaction(function () use ($request, $iuran) {
            // Lewati bila transaksinya sudah dibatalkan (mis. oleh admin) agar
            // siapa/kapan/alasan pembatalan pertama tidak tertimpa.
            if ($iuran->transaksi && ! $iuran->transaksi->is_batal) {
                $iuran->transaksi->update([
                    'is_batal' => true,
                    'alasan_batal' => 'Pembatalan verifikasi iuran',
                    'dibatalkan_oleh' => $request->user()->id_user,
                    'dibatalkan_pada' => now(),
                ]);
            }

            $iuran->update([
                'status' => StatusIuran::BelumBayar,
                'id_transaksi' => null,
                'diverifikasi_oleh' => null,
                'diverifikasi_pada' => null,
            ]);
        });

        return response()->json([
            'message' => 'Verifikasi iuran dibatalkan.',
            'iuran' => $iuran->fresh(),
        ]);
    }
}
