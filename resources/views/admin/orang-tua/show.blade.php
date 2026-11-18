@extends('layouts.admin')
@section('judul', $orangTua->nama)
@section('jalur', 'Data Master / Orang Tua / '.$orangTua->nama)
@section('isi')
<div class="kepala">
    <div>
        <h1>{{ $orangTua->nama }}</h1>
        <p class="subjudul">
            Akun {{ $username }} · {{ $orangTua->no_hp ?? 'tanpa no. HP' }}{{ $orangTua->pekerjaan ? ' · '.$orangTua->pekerjaan : '' }}
        </p>
    </div>
    <div class="aksi-baris">
        <a class="btn garis" href="{{ route('admin.orang-tua.edit', $orangTua->id_orang_tua) }}">Ubah profil</a>
    </div>
</div>

<div class="kartu">
    <h2>Anak yang dapat dipantau ({{ $anak->count() }})</h2>
    @if ($anak->isEmpty())
        <div class="kosong">Belum terhubung ke siswa mana pun — portal orang tua akan kosong.</div>
    @else
        <table>
            <thead><tr><th>Siswa</th><th>Status</th><th>Hubungan & kontak utama</th><th></th></tr></thead>
            <tbody>
            @foreach ($anak as $a)
                <tr>
                    <td><strong>{{ $a->nama_siswa }}</strong><br><span class="kecil">NIS {{ $a->nis }}</span></td>
                    <td><span class="badge {{ $a->status }}">{{ $a->status }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.orang-tua.anak.ubah', [$orangTua->id_orang_tua, $a->id_siswa]) }}" class="aksi-baris">
                            @csrf @method('PUT')
                            <select name="hubungan" style="width:auto">
                                @foreach ($hubunganList as $h)
                                    <option value="{{ $h }}" @selected($a->hubungan === $h)>{{ $h }}</option>
                                @endforeach
                            </select>
                            <label class="kecil" style="display:flex;gap:4px;align-items:center;margin:0;font-weight:500">
                                <input type="checkbox" name="is_kontak_utama" value="1" @checked($a->is_kontak_utama)> kontak utama
                            </label>
                            <button class="btn garis kecil" type="submit">Simpan</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.orang-tua.anak.hapus', [$orangTua->id_orang_tua, $a->id_siswa]) }}"
                              data-konfirmasi="Putuskan relasi dengan {{ $a->nama_siswa }}?">
                            @csrf @method('DELETE')
                            <button class="btn merah kecil" type="submit">Putuskan</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="kartu" style="max-width:640px">
    <h2>Hubungkan ke siswa</h2>
    <form method="POST" action="{{ route('admin.orang-tua.anak.simpan', $orangTua->id_orang_tua) }}">
        @csrf
        <div class="bidang">
            <label for="id_siswa">Siswa aktif</label>
            <select id="id_siswa" name="id_siswa" required>
                <option value="">— pilih siswa —</option>
                @foreach ($siswa as $s)
                    <option value="{{ $s->id_siswa }}" @selected((int) old('id_siswa') === $s->id_siswa)>{{ $s->nama_siswa }} ({{ $s->nis }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-grid">
            <div class="bidang">
                <label for="hubungan">Hubungan</label>
                <select id="hubungan" name="hubungan" required>
                    @foreach ($hubunganList as $h)
                        <option value="{{ $h }}" @selected(old('hubungan') === $h)>{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label>&nbsp;</label>
                <label style="display:flex;gap:6px;align-items:center;font-weight:500">
                    <input type="checkbox" name="is_kontak_utama" value="1" @checked(old('is_kontak_utama'))> Jadikan kontak utama
                </label>
            </div>
        </div>
        <button class="btn" type="submit">Hubungkan</button>
    </form>
</div>
@endsection
