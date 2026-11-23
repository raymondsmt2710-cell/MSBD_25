@extends('layouts.admin')
@section('judul', 'Denah '.$kelas->nama_kelas)
@section('jalur', 'Pemantauan / Denah / '.$kelas->nama_kelas)
@section('isi')
<div class="kepala">
    <div>
        <h1>Denah {{ $kelas->nama_kelas }}</h1>
        <p class="subjudul">{{ $jumlahSiswa }} siswa di kelas ini.</p>
    </div>
</div>

<div class="grid" style="grid-template-columns:minmax(0,3fr) minmax(260px,2fr);align-items:start">
    <div class="kartu">
        <h2>Versi denah</h2>
        @if ($denah->isEmpty())
            <div class="kosong">Belum ada denah untuk kelas ini.</div>
        @else
            <table>
                <thead><tr><th>Ukuran</th><th>Meja guru</th><th>Berlaku mulai</th><th>Terisi</th><th>Dibuat</th><th></th></tr></thead>
                <tbody>
                @foreach ($denah as $d)
                    <tr>
                        <td><strong>{{ $d->jumlah_baris }} × {{ $d->jumlah_kolom }}</strong> @if ($d->is_aktif)<span class="badge aktif">aktif</span>@endif</td>
                        <td class="kecil">{{ str_replace('_', ' ', $d->posisi_meja_guru) }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($d->berlaku_mulai)->format('d M Y') }}</td>
                        <td>{{ $d->terisi }}</td>
                        <td class="kecil">{{ $d->pembuat }}</td>
                        <td>
                            <div class="aksi-baris">
                                <a class="btn garis kecil" href="{{ route('admin.denah.edit', $d->id_denah) }}">Atur kursi</a>
                                @unless ($d->is_aktif)
                                    <form method="POST" action="{{ route('admin.denah.aktifkan', $d->id_denah) }}">
                                        @csrf
                                        <button class="btn kecil" type="submit">Aktifkan</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.denah.destroy', $d->id_denah) }}" data-konfirmasi="Hapus versi denah ini?">
                                        @csrf @method('DELETE')
                                        <button class="btn merah kecil" type="submit">Hapus</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="kartu">
        <h2>Buat denah baru</h2>
        <form method="POST" action="{{ route('admin.denah.store', $kelas->id_kelas) }}">
            @csrf
            <div class="form-grid">
                <div class="bidang">
                    <label for="jumlah_baris">Baris</label>
                    <input id="jumlah_baris" type="number" name="jumlah_baris" min="1" max="15" value="{{ old('jumlah_baris', 6) }}" required>
                </div>
                <div class="bidang">
                    <label for="jumlah_kolom">Kolom</label>
                    <input id="jumlah_kolom" type="number" name="jumlah_kolom" min="1" max="15" value="{{ old('jumlah_kolom', 6) }}" required>
                </div>
            </div>
            <div class="bidang">
                <label for="posisi_meja_guru">Meja guru</label>
                <select id="posisi_meja_guru" name="posisi_meja_guru">
                    @foreach ($mejaGuru as $m)
                        <option value="{{ $m }}" @selected(old('posisi_meja_guru', 'depan_tengah') === $m)>{{ str_replace('_', ' ', $m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label for="posisi_pintu">Pintu</label>
                <select id="posisi_pintu" name="posisi_pintu">
                    @foreach (['kiri', 'kanan', 'belakang'] as $p)
                        <option value="{{ $p }}" @selected(old('posisi_pintu', 'kanan') === $p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bidang">
                <label for="berlaku_mulai">Berlaku mulai</label>
                <input id="berlaku_mulai" type="date" name="berlaku_mulai" value="{{ old('berlaku_mulai', today()->format('Y-m-d')) }}" required>
            </div>
            <div class="bidang">
                <label for="isi">Isi kursi</label>
                <select id="isi" name="isi">
                    <option value="absen">Urut nomor absen</option>
                    <option value="acak">Acak (rotasi)</option>
                    <option value="salin" @disabled($denah->where('is_aktif', true)->isEmpty())>Salin dari denah aktif</option>
                    <option value="kosong">Kosong, atur manual</option>
                </select>
            </div>
            <div class="bidang">
                <label style="display:flex;gap:6px;align-items:center;font-weight:500">
                    <input type="checkbox" name="aktifkan" value="1" checked> Langsung jadikan denah aktif
                </label>
            </div>
            <button class="btn" type="submit">Buat denah</button>
        </form>
    </div>
</div>
@endsection
