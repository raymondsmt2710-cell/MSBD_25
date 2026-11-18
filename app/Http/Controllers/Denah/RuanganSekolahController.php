<?php

namespace App\Http\Controllers\Denah;

use App\Http\Controllers\Controller;
use App\Http\Requests\Denah\StatusRuanganRequest;
use App\Http\Requests\Denah\StoreRuanganRequest;
use App\Http\Requests\Denah\UpdateRuanganRequest;
use App\Models\RuanganSekolah;
use App\Services\DenahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RuanganSekolahController extends Controller
{
    public function __construct(private readonly DenahService $denah) {}

    public function data(Request $request): JsonResponse
    {
        $ruangan = collect($this->denah->ruangan())
            ->when($request->filled('gedung'), fn ($items, $gedung) => $items->where('gedung', $gedung))
            ->when($request->filled('lantai'), fn ($items, $lantai) => $items->where('lantai', (int) $lantai))
            ->when($request->filled('kategori'), fn ($items, $kategori) => $items->where('kategori', $kategori))
            ->when($request->filled('q'), fn ($items, $q) => $items->filter(
                fn ($item) => str_contains(strtolower($item['nama_ruangan']), strtolower((string) $q))
                    || str_contains(strtolower($item['kode_ruangan']), strtolower((string) $q)),
            ))
            ->values();

        return response()->json([
            'ruangan' => $ruangan,
            'gedung' => $ruangan->pluck('gedung')->unique()->values(),
            'lantai' => $ruangan->pluck('lantai')->unique()->sort()->values(),
        ]);
    }

    public function store(StoreRuanganRequest $request): JsonResponse
    {
        Gate::authorize('denah.kelola-ruangan');

        $ruangan = RuanganSekolah::create($request->validated());

        return response()->json([
            'message' => 'Ruangan berhasil ditambahkan.',
            'ruangan' => $ruangan,
        ], 201);
    }

    public function update(UpdateRuanganRequest $request, RuanganSekolah $ruangan): JsonResponse
    {
        Gate::authorize('denah.kelola-ruangan');

        $ruangan->update($request->validated());

        return response()->json([
            'message' => 'Ruangan berhasil diperbarui.',
            'ruangan' => $ruangan->fresh(),
        ]);
    }

    public function destroy(RuanganSekolah $ruangan): JsonResponse
    {
        Gate::authorize('denah.kelola-ruangan');

        $ruangan->delete();

        return response()->json(['message' => 'Ruangan dihapus.']);
    }

    public function status(StatusRuanganRequest $request, RuanganSekolah $ruangan): JsonResponse
    {
        Gate::authorize('denah.kelola-ruangan');

        $ruangan->update(['status' => $request->string('status')->toString()]);

        return response()->json([
            'message' => 'Status ruangan diperbarui.',
            'ruangan' => $ruangan->fresh(),
        ]);
    }
}
