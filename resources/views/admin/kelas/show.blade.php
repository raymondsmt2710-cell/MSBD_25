@extends('layouts.admin')
@section('judul', 'Roster '.$kelas->nama_kelas)
@section('jalur', 'Akademik / Kelas / '.$kelas->nama_kelas)
@section('isi')
<div class="kepala">
    <div>
        <h1>{{ $kelas->nama_kelas }}</h1>
        <p class="subjudul">
            {{ $tahun?->nama }} · Tingkat {{ $kelas->tingkat }}{{ $kelas->jurusan ? ' · '.$kelas->jurusan : '' }}{{ $kelas->ruangan ? ' · '.$kelas->ruangan : '' }}
            · Wali kelas: {{ $wali ?? 'belum ditentukan' }}
        </p>
    </div>
    <div class="aksi-baris">
        <a class="btn garis" href="{{ route('admin.jadwal.index', ['kelas' => $kelas->id_kelas]) }}">Jadwal</a>
        <a class="btn garis" href="{{ route('admin.pengurus.kelas', $kelas->id_kelas) }}">Pengurus</a>
        <a class="btn garis" href="{{ route('admin.denah.kelas', $kelas->id_kelas) }}">Denah</a>
        <a class="btn garis" href="{{ route('admin.kelas.edit', $kelas->id_kelas) }}">Ubah kelas</a>
    </div>
</div>

<div class="grid" style="grid-template-columns:minmax(0,3fr) minmax(260px,2fr);align-items:start">
    <div class="kartu">
        <div class="kepala">
            <h2>Roster ({{ $anggota->count() }} siswa)</h2>
            @if ($anggota->isNotEmpty())
                <form method="POST" action="{{ route('admin.kelas.anggota.urutkan', $kelas->id_kelas) }}"
                      data-konfirmasi="Nomor absen semua siswa akan diganti berurutan sesuai abjad nama. Lanjutkan?">
                    @csrf
                    <button class="btn garis kecil" type="submit">Urutkan absen (A–Z)</button>
                </form>
            @endif
        </div>

        @if ($anggota->isEmpty())
            <div class="kosong">Belum ada siswa di kelas ini.</div>
        @else
            {{-- Form nomor absen dipisah dari tombol keluarkan (form tidak boleh bersarang). --}}
            <form id="form-absen" method="POST" action="{{ route('admin.kelas.anggota.absen', $kelas->id_kelas) }}">
                @csrf @method('PUT')
            </form>
            <table>
                <thead><tr><th style="width:90px">No. absen</th><th>NIS</th><th>Nama</th><th>L/P</th><th></th></tr></thead>
                <tbody>
                @foreach ($anggota as $a)
                    <tr>
                        <td><input form="form-absen" type="number" min="1" max="255" name="no_absen[{{ $a->id_anggota }}]" value="{{ $a->no_absen }}" style="width:70px"></td>
                        <td class="kecil">{{ $a->nis }}</td>
                        <td>{{ $a->nama_siswa }} @if ($a->status !== 'aktif')<span class="badge {{ $a->status }}">{{ $a->status }}</span>@endif</td>
                        <td>{{ $a->jenis_kelamin }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.kelas.anggota.hapus', [$kelas->id_kelas, $a->id_anggota]) }}"
                                  data-konfirmasi="Keluarkan {{ $a->nama_siswa }} dari {{ $kelas->nama_kelas }}?">
                                @csrf @method('DELETE')
                                <button class="btn merah kecil" type="submit">Keluarkan</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button form="form-absen" class="btn" type="submit" style="margin-top:12px">Simpan nomor absen</button>
        @endif
    </div>

    <div class="kartu">
        <h2>Tempatkan siswa</h2>
        @if ($belumBerkelas->isEmpty())
            <div class="kosong">Semua siswa aktif sudah punya kelas pada tahun ajaran ini.</div>
        @else
            <form method="POST" action="{{ route('admin.kelas.anggota.simpan', $kelas->id_kelas) }}">
                @csrf
                <input type="search" placeholder="Saring nama / NIS…" style="margin-bottom:8px"
                       oninput="var q=this.value.toLowerCase();this.form.querySelectorAll('[data-cari]').forEach(function(el){el.style.display=el.dataset.cari.indexOf(q)>-1?'':'none'})">
                <div style="max-height:380px;overflow:auto;border:1px solid #eef0f2;border-radius:10px;padding:6px 10px">
                    @foreach ($belumBerkelas as $s)
                        <label data-cari="{{ strtolower($s->nama_siswa.' '.$s->nis) }}" style="display:flex;gap:8px;align-items:center;font-weight:400;padding:4px 0;margin:0">
                            <input type="checkbox" name="id_siswa[]" value="{{ $s->id_siswa }}">
                            {{ $s->nama_siswa }} <span class="kecil">{{ $s->nis }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="kecil">Hanya siswa aktif yang belum punya kelas di {{ $tahun?->nama }}. Nomor absen diberikan melanjutkan nomor terakhir.</p>
                <button class="btn" type="submit">Tempatkan</button>
            </form>
        @endif
    </div>
</div>
@endsection
