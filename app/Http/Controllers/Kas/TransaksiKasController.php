<?php

namespace App\Http\Controllers\Kas;

use App\Enums\StatusIuran;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kas\BatalTransaksiRequest;
use App\Http\Requests\Kas\StoreTransaksiKasRequest;
use App\Http\Requests\Kas\UpdateTransaksiKasRequest;
use App\Models\Kelas;
use App\Models\TransaksiKas;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TransaksiKasController extends Controller
{
    public function store(StoreTransaksiKasRequest $request): JsonResponse
    {
        $kelas = Kelas::findOrFail($request->integer('id_kelas'));
        Gate::authorize('kas.kelola', $kelas);

        $data = $request->validated();
        $data['dicatat_oleh'] = $request->user()->id_user;

        if ($request->hasFile('bukti')) {
            $data['bukti_url'] = $request->file('bukti')->store('kas/bukti', 'public');
        }
        unset($data['bukti']);

        $transaksi = DB::transaction(fn () => TransaksiKas::create($data));

        return response()->json([
            'message' => 'Transaksi berhasil dicatat.',
            'transaksi' => $transaksi->load('pencatat:id_user,username'),
        ], 201);
    }

    public function update(UpdateTransaksiKasRequest $request, TransaksiKas $transaksi): JsonResponse
    {
        Gate::authorize('kas.kelola', $transaksi->kelas);

        abort_if($transaksi->is_batal, 422, 'Transaksi yang dibatalkan tidak dapat diedit.');
        abort_if(
            $transaksi->iuran()->exists(),
            422,
            'Transaksi ini berasal dari verifikasi iuran. Batalkan verifikasi terlebih dahulu.',
        );

        $data = $request->validated();
        if ($request->hasFile('bukti')) {
            $data['bukti_url'] = $request->file('bukti')->store('kas/bukti', 'public');
        }
        unset($data['bukti'], $data['id_kelas']);

        $transaksi->update($data);

        return response()->json([
            'message' => 'Transaksi diperbarui.',
            'transaksi' => $transaksi->fresh(['pencatat:id_user,username']),
        ]);
    }

    public function batal(BatalTransaksiRequest $request, TransaksiKas $transaksi): JsonResponse
    {
        Gate::authorize('kas.kelola', $transaksi->kelas);

        abort_if($transaksi->is_batal, 422, 'Transaksi sudah dibatalkan.');

        DB::transaction(function () use ($request, $transaksi) {
            $transaksi->update([
                'is_batal' => true,
                'alasan_batal' => $request->string('alasan_batal')->toString(),
                'dibatalkan_oleh' => $request->user()->id_user,
            ]);

            if ($iuran = $transaksi->iuran) {
                $iuran->update([
                    'status' => StatusIuran::BelumBayar,
                    'id_transaksi' => null,
                    'diverifikasi_oleh' => null,
                    'diverifikasi_pada' => null,
                ]);
            }
        });

        return response()->json(['message' => 'Transaksi dibatalkan.']);
    }
}
