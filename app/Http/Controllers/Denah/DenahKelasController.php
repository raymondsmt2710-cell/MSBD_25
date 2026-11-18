<?php

namespace App\Http\Controllers\Denah;

use App\Http\Controllers\Controller;
use App\Http\Requests\Denah\PindahPosisiRequest;
use App\Http\Requests\Denah\SimpanDenahRequest;
use App\Http\Requests\Denah\SwapPosisiRequest;
use App\Http\Requests\Denah\TempatkanSiswaRequest;
use App\Models\DenahKelas;
use App\Models\Kelas;
use App\Models\PosisiDuduk;
use App\Services\DenahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DenahKelasController extends Controller
{
    public function __construct(private readonly DenahService $denah) {}

    public function data(Request $request, Kelas $kelas): JsonResponse
    {
        Gate::authorize('kas.lihat', $kelas);

        $bisaKelola = Gate::allows('denah.kelola-kursi', $kelas);
        $denah = $this->denah->denahAktif($kelas, $bisaKelola, $request->user()->id_user);

        return response()->json([
            'denah' => $denah,
            'kursi' => $denah ? $this->denah->kursi($denah, $kelas) : [],
            'siswa' => $this->denah->siswa($kelas),
            'bisa_kelola' => $bisaKelola,
        ]);
    }

    public function simpan(SimpanDenahRequest $request, Kelas $kelas): JsonResponse
    {
        Gate::authorize('denah.kelola-kursi', $kelas);

        $denah = DB::transaction(function () use ($request, $kelas) {
            $denah = DenahKelas::query()
                ->where('id_kelas', $kelas->id_kelas)
                ->where('is_aktif', true)
                ->latest('id_denah')
                ->first();

            if ($denah) {
                $denah->update($request->validated());
            } else {
                $denah = DenahKelas::create([
                    ...$request->validated(),
                    'id_kelas' => $kelas->id_kelas,
                    'berlaku_mulai' => now()->toDateString(),
                    'dibuat_oleh' => $request->user()->id_user,
                    'is_aktif' => true,
                ]);
            }

            PosisiDuduk::query()
                ->where('id_denah', $denah->id_denah)
                ->where(function ($query) use ($denah) {
                    $query->where('baris', '>', $denah->jumlah_baris)
                        ->orWhere('kolom', '>', $denah->jumlah_kolom);
                })
                ->delete();

            return $denah;
        });

        return response()->json([
            'message' => 'Denah berhasil disimpan.',
            'denah' => $denah->fresh(),
            'kursi' => $this->denah->kursi($denah, $kelas),
        ]);
    }

    public function pindah(PindahPosisiRequest $request, PosisiDuduk $posisi): JsonResponse
    {
        Gate::authorize('denah.kelola-kursi', $posisi->denah->kelas);

        $denah = $posisi->denah;
        $baris = $request->integer('baris');
        $kolom = $request->integer('kolom');
        $sisi = $request->input('sisi', $posisi->sisi ?: 'kiri');

        abort_unless(in_array($sisi, PosisiDuduk::SISI, true), 422, 'Sisi meja tidak dikenal.');

        abort_unless(
            $baris >= 1 && $baris <= $denah->jumlah_baris && $kolom >= 1 && $kolom <= $denah->jumlah_kolom,
            422,
            'Posisi berada di luar batas denah.',
        );

        // Meja hanya punya 2 tempat (kiri & kanan); yang sudah ditempati murid lain dianggap bentrok.
        $bentrok = PosisiDuduk::query()
            ->where('id_denah', $denah->id_denah)
            ->where('baris', $baris)
            ->where('kolom', $kolom)
            ->where('sisi', $sisi)
            ->where('id_posisi', '!=', $posisi->id_posisi)
            ->exists();

        abort_if($bentrok, 422, 'Kursi tujuan sudah terisi. Gunakan fitur tukar posisi.');

        $posisi->update(['baris' => $baris, 'kolom' => $kolom, 'sisi' => $sisi]);

        return response()->json([
            'message' => 'Posisi dipindahkan.',
            'posisi' => $posisi->fresh(),
        ]);
    }

    public function swap(SwapPosisiRequest $request, Kelas $kelas): JsonResponse
    {
        Gate::authorize('denah.kelola-kursi', $kelas);

        $denah = $this->denah->denahAktif($kelas);
        abort_if(! $denah, 422, 'Denah belum dibuat.');

        $a = PosisiDuduk::findOrFail($request->integer('posisi_a'));
        $b = PosisiDuduk::findOrFail($request->integer('posisi_b'));

        abort_unless(
            $a->id_denah === $denah->id_denah && $b->id_denah === $denah->id_denah,
            422,
            'Kedua posisi harus berada di denah aktif kelas ini.',
        );

        if ($a->id_posisi === $b->id_posisi) {
            return response()->json(['message' => 'Posisi sama, tidak ada perubahan.']);
        }

        $aBaris = $a->baris;
        $aKolom = $a->kolom;
        $aSisi = $a->sisi ?: 'kiri';
        $bBaris = $b->baris;
        $bKolom = $b->kolom;
        $bSisi = $b->sisi ?: 'kiri';

        DB::transaction(function () use ($a, $b, $aBaris, $aKolom, $aSisi, $bBaris, $bKolom, $bSisi) {
            // Parkir sementara di baris 0 (di luar grid) agar tidak menabrak
            // unique (id_denah, baris, kolom, sisi).
            $a->update(['baris' => 0, 'kolom' => 0]);
            $b->update(['baris' => $aBaris, 'kolom' => $aKolom, 'sisi' => $aSisi]);
            $a->update(['baris' => $bBaris, 'kolom' => $bKolom, 'sisi' => $bSisi]);
        });

        return response()->json([
            'message' => 'Posisi berhasil ditukar.',
            'kursi' => $this->denah->kursi($denah, $kelas),
        ]);
    }

    public function tempatkan(TempatkanSiswaRequest $request, Kelas $kelas): JsonResponse
    {
        Gate::authorize('denah.kelola-kursi', $kelas);

        $idSiswa = $request->integer('id_siswa');
        $baris = $request->integer('baris');
        $kolom = $request->integer('kolom');
        $sisi = $request->input('sisi', 'kiri');

        abort_unless(in_array($sisi, PosisiDuduk::SISI, true), 422, 'Sisi meja tidak dikenal.');

        abort_unless(
            $kelas->anggotaKelas()->where('id_siswa', $idSiswa)->exists(),
            422,
            'Siswa bukan anggota kelas ini.',
        );

        $denah = $this->denah->denahAktif($kelas, true, $request->user()->id_user);
        abort_if(! $denah, 422, 'Denah belum dibuat.');

        abort_unless(
            $baris >= 1 && $baris <= $denah->jumlah_baris && $kolom >= 1 && $kolom <= $denah->jumlah_kolom,
            422,
            'Posisi berada di luar batas denah.',
        );

        abort_if(
            PosisiDuduk::where('id_denah', $denah->id_denah)->where('id_siswa', $idSiswa)->exists(),
            422,
            'Siswa sudah memiliki kursi.',
        );

        // Satu meja hanya bisa menampung 2 murid: satu di kiri, satu di kanan.
        abort_if(
            PosisiDuduk::where('id_denah', $denah->id_denah)
                ->where('baris', $baris)
                ->where('kolom', $kolom)
                ->where('sisi', $sisi)
                ->exists(),
            422,
            'Kursi tujuan sudah terisi.',
        );

        PosisiDuduk::create([
            'id_denah' => $denah->id_denah,
            'id_siswa' => $idSiswa,
            'baris' => $baris,
            'kolom' => $kolom,
            'sisi' => $sisi,
        ]);

        return response()->json([
            'message' => 'Siswa ditempatkan.',
            'kursi' => $this->denah->kursi($denah, $kelas),
        ]);
    }

    public function acak(Request $request, Kelas $kelas): JsonResponse
    {
        Gate::authorize('denah.kelola-kursi', $kelas);

        $denah = $this->denah->denahAktif($kelas, true, $request->user()->id_user);
        abort_if(! $denah, 422, 'Denah belum dibuat.');

        $idSiswa = $kelas->anggotaKelas()->pluck('id_siswa')->shuffle()->values();

        // Satu meja = 2 murid, jadi kapasitas = baris × kolom × 2.
        $kapasitas = $denah->jumlah_baris * $denah->jumlah_kolom * count(PosisiDuduk::SISI);

        abort_if(
            $idSiswa->count() > $kapasitas,
            422,
            'Jumlah siswa melebihi kapasitas meja.',
        );

        DB::transaction(function () use ($denah, $idSiswa) {
            PosisiDuduk::where('id_denah', $denah->id_denah)->delete();

            $index = 0;
            for ($baris = 1; $baris <= $denah->jumlah_baris; $baris++) {
                for ($kolom = 1; $kolom <= $denah->jumlah_kolom; $kolom++) {
                    // Isi sisi kiri dulu, lalu sisi kanan meja yang sama.
                    foreach (PosisiDuduk::SISI as $sisi) {
                        if (! isset($idSiswa[$index])) {
                            return;
                        }

                        PosisiDuduk::create([
                            'id_denah' => $denah->id_denah,
                            'id_siswa' => $idSiswa[$index],
                            'baris' => $baris,
                            'kolom' => $kolom,
                            'sisi' => $sisi,
                        ]);

                        $index++;
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Posisi berhasil diacak.',
            'kursi' => $this->denah->kursi($denah, $kelas),
        ]);
    }
}
