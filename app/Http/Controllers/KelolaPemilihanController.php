<?php

namespace App\Http\Controllers;

use App\Models\Pemilihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KelolaPemilihanController extends Controller
{

    private function pastikanBoleh(int $idPemilihan): Pemilihan
    {
        $pemilihan = Pemilihan::findOrFail($idPemilihan);
        $this->authorize('kelola', $pemilihan);

        return $pemilihan;
    }


    private function sudahJadiKandidat(int $idPemilihan, int $idSiswa, $abaikanIdKandidat = null): bool
    {
        $query = DB::table('kandidat')
            ->where('id_pemilihan', $idPemilihan)
            ->where(function ($q) use ($idSiswa) {
                $q->where('id_siswa_ketua', $idSiswa)
                  ->orWhere('id_siswa_wakil', $idSiswa);
            });

        if ($abaikanIdKandidat) {
            $query->where('id_kandidat', '!=', (int) $abaikanIdKandidat);
        }

        return $query->exists();
    }


    private function call(string $sql, array $params): string
    {
        DB::statement($sql, $params);

        return DB::selectOne('SELECT @s AS s')->s;
    }

    private function pesan(string $status): string
    {
        return match ($status) {
            'BERHASIL'            => 'Perubahan berhasil disimpan.',
            'TIDAK_BERHAK'        => 'Anda tidak berhak mengelola pemilihan ini.',
            'STATUS_TIDAK_VALID'  => 'Pemilihan sudah dibuka, data tidak dapat diubah lagi.',
            'TIDAK_DITEMUKAN'     => 'Data tidak ditemukan.',
            'SUDAH_ADA_SUARA'     => 'Sudah ada suara masuk. Tindakan ini tidak diizinkan.',
            'SUDAH_MEMILIH'       => 'Pemilih tersebut sudah memberikan suara.',
            'BELUM_ADA_KANDIDAT'  => 'Tambahkan minimal satu kandidat sebelum membuka pemilihan.',
            'BELUM_ADA_PEMILIH'   => 'Daftarkan minimal satu pemilih sebelum membuka pemilihan.',
            'WAKTU_TIDAK_VALID'   => 'Waktu tidak valid: waktu selesai harus setelah waktu mulai dan belum lewat.',
            default               => 'Status tidak dikenal: '.$status,
        };
    }

    private function hasilAksi(string $status, string $sukses = null)
    {
        return $status === 'BERHASIL'
            ? back()->with('success', $sukses ?? $this->pesan($status))
            : back()->with('error', $this->pesan($status));
    }



    public function index()
    {
        $this->authorize('kelola', new Pemilihan);

        $query = DB::table('v_pemilihan_pengelola')
            ->orderByRaw("FIELD(status, 'berlangsung', 'draf', 'selesai')")
            ->orderByDesc('waktu_mulai');


        $idBoleh = (new \App\Policies\PemilihanPolicy)->idYangBoleh(Auth::user());

        if ($idBoleh !== null) {
            $query->whereIn('id_pemilihan', $idBoleh);
        }

        $pemilihan = $query->paginate(15);

        return view('kelola.pemilihan.index', compact('pemilihan'));
    }

    public function form(?int $id = null)
    {
        if ($id) {
            $pemilihan = $this->pastikanBoleh($id);
        } else {
            $this->authorize('kelola', new Pemilihan);
            $pemilihan = new Pemilihan(['status' => 'draf']);
        }

        $organisasi = DB::table('organisasi')->orderBy('nama_organisasi')->get();
        $tahunAjaran = DB::table('tahun_ajaran')->orderByDesc('nama')->get();

        return view('kelola.pemilihan.form', compact('pemilihan', 'organisasi', 'tahunAjaran'));
    }

    public function simpan(Request $request, ?int $id = null)
    {
        $data = $request->validate([
            'judul'           => 'required|string|max:100',
            'deskripsi'       => 'nullable|string',
            'id_organisasi'   => 'required|integer|exists:organisasi,id_organisasi',
            'id_tahun_ajaran' => 'required|integer|exists:tahun_ajaran,id_tahun_ajaran',
            'waktu_mulai'     => 'required|date',
            'waktu_selesai'   => 'required|date|after:waktu_mulai',
        ]);

        if ($id) {
            $this->pastikanBoleh($id);
        } else {
            $this->authorize('kelola', new Pemilihan);
        }

        $status = $this->call(
            'CALL sp_simpan_pemilihan(?,?,?,?,?,?,?,?,@s,@id)',
            [
                $id,
                Auth::id(),
                $data['judul'],
                $data['deskripsi'] ?? null,
                $data['id_organisasi'],
                $data['id_tahun_ajaran'],
                $data['waktu_mulai'],
                $data['waktu_selesai'],
            ]
        );

        return $this->hasilAksi($status, 'Pemilihan berhasil disimpan.');
    }



    public function kandidat(int $id)
    {
        $pemilihan = $this->pastikanBoleh($id);

        $kandidat = DB::table('v_kandidat_pemilihan')
            ->where('id_pemilihan', $id)
            ->orderBy('nomor_urut')
            ->get();

        $siswa = DB::table('siswa')
            ->where('status', 'aktif')
            ->orderBy('nama_siswa')
            ->select('id_siswa', 'nis', 'nama_siswa')
            ->get();



        $terpakai = $kandidat->pluck('nomor_urut')->map(fn ($n) => (int) $n)->all();
        $nomorBerikutnya = 1;
        while (in_array($nomorBerikutnya, $terpakai, true)) {
            $nomorBerikutnya++;
        }

        return view('kelola.pemilihan.kandidat', compact('pemilihan', 'kandidat', 'siswa', 'nomorBerikutnya'));
    }

    public function simpanKandidat(Request $request, int $id)
    {
        $this->pastikanBoleh($id);

        $data = $request->validate([
            'id_kandidat'    => 'nullable|integer',
            'nomor_urut'     => [
                'required', 'integer', 'min:1', 'max:99',

                Rule::unique('kandidat', 'nomor_urut')
                    ->where('id_pemilihan', $id)
                    ->ignore($request->input('id_kandidat'), 'id_kandidat'),
            ],
            'id_siswa_ketua' => [
                'required', 'integer', 'exists:siswa,id_siswa',



                function ($attribute, $value, $fail) use ($id, $request) {
                    if ($this->sudahJadiKandidat($id, (int) $value, $request->input('id_kandidat'))) {
                        $fail('Siswa ini sudah terdaftar sebagai ketua atau wakil pada kandidat lain.');
                    }
                },
            ],
            'id_siswa_wakil' => [
                'nullable', 'integer', 'exists:siswa,id_siswa', 'different:id_siswa_ketua',
                function ($attribute, $value, $fail) use ($id, $request) {
                    if ($value === null) {
                        return;
                    }
                    if ($this->sudahJadiKandidat($id, (int) $value, $request->input('id_kandidat'))) {
                        $fail('Siswa ini sudah terdaftar sebagai ketua atau wakil pada kandidat lain.');
                    }
                },
            ],
            'visi'           => 'nullable|string',
            'misi'           => 'nullable|string',
            'foto'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'nomor_urut.unique' => 'Nomor urut tersebut sudah dipakai kandidat lain.',
        ]);

        $fotoUrl = null;
        $fileBaru = null;

        if ($request->hasFile('foto')) {
            $fileBaru = $request->file('foto')->store('kandidat', 'public');
            $fotoUrl = $fileBaru;
        }

        $status = $this->call(
            'CALL sp_simpan_kandidat(?,?,?,?,?,?,?,?,?,@s,@id)',
            [
                $data['id_kandidat'] ?? null,
                Auth::id(),
                $id,
                $data['nomor_urut'],
                $data['id_siswa_ketua'],
                $data['id_siswa_wakil'] ?? null,
                $data['visi'] ?? null,
                $data['misi'] ?? null,
                $fotoUrl,
            ]
        );


        if ($status !== 'BERHASIL' && $fileBaru) {
            Storage::disk('public')->delete($fileBaru);
        }

        return $this->hasilAksi($status, 'Kandidat berhasil disimpan.');
    }

    public function hapusKandidat(int $id, int $idKandidat)
    {
        $this->pastikanBoleh($id);

        $status = $this->call('CALL sp_hapus_kandidat(?,?,@s)', [$idKandidat, Auth::id()]);

        return $this->hasilAksi($status, 'Kandidat berhasil dihapus.');
    }



    public function pemilih(Request $request, int $id)
    {
        $pemilihan = $this->pastikanBoleh($id);

        $pemilih = DB::table('daftar_pemilih as dp')
            ->join('siswa as s', 's.id_siswa', '=', 'dp.id_siswa')
            ->leftJoin('anggota_kelas as ak', 'ak.id_siswa', '=', 's.id_siswa')
            ->leftJoin('kelas as k', 'k.id_kelas', '=', 'ak.id_kelas')
            ->where('dp.id_pemilihan', $id)
            ->orderBy('s.nama_siswa')
            ->select('s.id_siswa', 's.nis', 's.nama_siswa', 'k.nama_kelas', 'dp.sudah_memilih', 'dp.waktu_memilih')
            ->paginate(25);

        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

        return view('kelola.pemilihan.pemilih', compact('pemilihan', 'pemilih', 'kelas'));
    }

    public function daftarkanPemilih(Request $request, int $id)
    {
        $this->pastikanBoleh($id);

        $data = $request->validate([
            'id_kelas'   => 'required|array|min:1',
            'id_kelas.*' => 'integer|exists:kelas,id_kelas',
        ]);

        $total = 0;

        foreach ($data['id_kelas'] as $idKelas) {
            DB::statement('CALL sp_daftarkan_pemilih_kelas(?,?,?,@s,@n)', [$id, Auth::id(), $idKelas]);
            $hasil = DB::selectOne('SELECT @s AS s, @n AS n');

            if ($hasil->s !== 'BERHASIL') {
                return back()->with('error', $this->pesan($hasil->s));
            }

            $total += (int) $hasil->n;
        }

        return back()->with('success', "{$total} pemilih baru didaftarkan.");
    }

    public function hapusPemilih(int $id, int $idSiswa)
    {
        $this->pastikanBoleh($id);

        $status = $this->call('CALL sp_hapus_pemilih(?,?,?,@s)', [$id, Auth::id(), $idSiswa]);

        return $this->hasilAksi($status, 'Pemilih berhasil dihapus.');
    }



    public function panitia(int $id)
    {
        $pemilihan = $this->pastikanBoleh($id);
        $this->authorize('hanyaAdmin', $pemilihan);

        $panitia = DB::table('panitia_pemilihan as pp')
            ->join('users as u', 'u.id_user', '=', 'pp.id_user')
            ->leftJoin('guru as g', 'g.id_user', '=', 'u.id_user')
            ->where('pp.id_pemilihan', $id)
            ->select('u.id_user', 'u.username', 'g.nama_guru')
            ->get();

        $guru = DB::table('guru as g')
            ->join('users as u', 'u.id_user', '=', 'g.id_user')
            ->orderBy('g.nama_guru')
            ->select('u.id_user', 'u.username', 'g.nama_guru')
            ->get();

        return view('kelola.pemilihan.panitia', compact('pemilihan', 'panitia', 'guru'));
    }

    public function simpanPanitia(Request $request, int $id)
    {
        $pemilihan = $this->pastikanBoleh($id);
        $this->authorize('hanyaAdmin', $pemilihan);

        $data = $request->validate([
            'id_user' => 'required|integer|exists:users,id_user',
        ]);

        $status = $this->call('CALL sp_tunjuk_panitia(?,?,?,@s)', [$id, Auth::id(), $data['id_user']]);

        return $this->hasilAksi($status, 'Daftar panitia diperbarui.');
    }



    public function monitor(int $id)
    {
        $pemilihan = $this->pastikanBoleh($id);

        $kandidat = DB::table('v_kandidat_pemilihan_hasil')
            ->where('id_pemilihan', $id)
            ->orderBy('nomor_urut')
            ->get();

        $partisipasi = DB::table('v_partisipasi_pemilihan')
            ->where('id_pemilihan', $id)
            ->first();

        $waktuLewat = $pemilihan->status === 'berlangsung'
            && $pemilihan->waktu_selesai
            && $pemilihan->waktu_selesai->isPast();

        return view('kelola.pemilihan.monitor', compact('pemilihan', 'kandidat', 'partisipasi', 'waktuLewat'));
    }

    public function buka(int $id)
    {
        $this->pastikanBoleh($id);

        $status = $this->call('CALL sp_buka_pemilihan(?,?,@s)', [$id, Auth::id()]);

        return $this->hasilAksi($status, 'Pemilihan dibuka. Siswa sekarang dapat memilih.');
    }

    public function tutup(int $id)
    {
        $this->pastikanBoleh($id);

        DB::statement('CALL sp_tutup_pemilihan(?,?,@s,@n)', [$id, Auth::id()]);
        $hasil = DB::selectOne('SELECT @s AS s, @n AS n');

        return $hasil->s === 'BERHASIL'
            ? back()->with('success', "Pemilihan ditutup. {$hasil->n} suara tercatat.")
            : back()->with('error', $this->pesan($hasil->s));
    }

    public function kembalikan(int $id)
    {
        $this->pastikanBoleh($id);

        $status = $this->call('CALL sp_kembalikan_ke_draf(?,?,@s)', [$id, Auth::id()]);

        return $this->hasilAksi($status, 'Pemilihan dikembalikan ke draf.');
    }

    public function log(int $id)
    {
        $pemilihan = $this->pastikanBoleh($id);

        $log = DB::table('log_pemilihan as l')
            ->leftJoin('users as u', 'u.id_user', '=', 'l.id_user')
            ->where('l.id_pemilihan', $id)
            ->orderByDesc('l.dibuat_pada')
            ->select('l.*', 'u.username')
            ->paginate(30);

        return view('kelola.pemilihan.log', compact('pemilihan', 'log'));
    }


    public function hasil(int $id)
    {
        $pemilihan = $this->pastikanBoleh($id);

        $hasil = DB::table('v_hasil_pemilihan')
            ->where('id_pemilihan', $id)
            ->orderByDesc('jumlah_suara')
            ->orderBy('nomor_urut')
            ->get();

        $partisipasi = DB::table('v_partisipasi_pemilihan')
            ->where('id_pemilihan', $id)
            ->first();

        $tertinggi = $hasil->max('jumlah_suara');

        return view('siswa.pemilihan.hasil', [
            'pemilihan' => $pemilihan,
            'hasil' => $hasil,
            'partisipasi' => $partisipasi,
            'tertinggi' => $tertinggi,
            'dariKelola' => true,
        ]);
    }
}
