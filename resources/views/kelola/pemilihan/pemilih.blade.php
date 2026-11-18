@extends('layouts.pemilihan')
@section('judul', 'Daftar Pemilih')
@section('isi')
@php $id = $pemilihan->id_pemilihan; $draf = $pemilihan->status === 'draf'; @endphp

<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>Daftar Pemilih</h1>
        <p class="subjudul">{{ $pemilihan->judul }} — <span class="badge {{ $pemilihan->status }}">{{ $pemilihan->status }}</span></p>
    </div>
</div>

@if (! $draf)
    <div class="err">
        Pemilihan sudah dibuka. Daftar pemilih terkunci agar hasil pemungutan tetap sah.
    </div>
@endif

@if ($draf)
    <div class="kartu">
        <h2>Daftarkan per Kelas</h2>
        <p class="kecil" style="margin:-6px 0 14px">
            Semua siswa berstatus <strong>aktif</strong> di kelas yang dipilih akan otomatis menjadi pemilih.
            Siswa yang sudah terdaftar dilewati, jadi aman dijalankan berulang.
        </p>
        <form method="POST" action="{{ route('kelola.pemilihan.pemilih.daftarkan', $id) }}">
            @csrf
            <div style="margin-bottom:14px">
                <label for="id_kelas">Kelas</label>
                <select id="id_kelas" name="id_kelas[]" multiple size="6" required>
                    @foreach ($kelas as $k)
                        <option value="{{ $k->id_kelas }}">{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
                <div class="kecil" style="margin-top:5px">Tahan Ctrl (Windows) atau Cmd (Mac) untuk memilih beberapa kelas.</div>
            </div>
            <button class="btn" type="submit">Daftarkan</button>
        </form>
    </div>
@endif

<div class="kartu">
    <h2>Pemilih Terdaftar ({{ $pemilih->total() }})</h2>
    @if ($pemilih->isEmpty())
        <div class="kosong">Belum ada pemilih terdaftar.</div>
    @else
        <table>
            <thead>
            <tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Sudah Memilih</th><th>Waktu</th>@if ($draf)<th>Aksi</th>@endif</tr>
            </thead>
            <tbody>
            @foreach ($pemilih as $p)
                <tr>
                    <td>{{ $p->nis }}</td>
                    <td>{{ $p->nama_siswa }}</td>
                    <td>{{ $p->nama_kelas ?? '—' }}</td>
                    <td>{!! $p->sudah_memilih ? '<span class="badge berlangsung">✓ sudah</span>' : '<span class="badge draf">belum</span>' !!}</td>
                    <td class="kecil">{{ $p->waktu_memilih ?? '—' }}</td>
                    @if ($draf)
                        <td>
                            <form method="POST" action="{{ route('kelola.pemilihan.pemilih.hapus', [$id, $p->id_siswa]) }}"
                                  onsubmit="return confirm('Hapus pemilih ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn merah" type="submit">Hapus</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $pemilih->links() }}</div>
    @endif
</div>
@endsection
