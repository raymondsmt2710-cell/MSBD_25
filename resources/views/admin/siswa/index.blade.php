@extends('layouts.admin')
@section('judul', 'Data Siswa')
@section('jalur', 'Data Master / Siswa')
@section('isi')
<div class="kepala">
    <div>
        <h1>Data Siswa</h1>
        <p class="subjudul">Kelas ditampilkan untuk tahun ajaran aktif{{ $tahun ? ' ('.$tahun->nama.')' : '' }}.</p>
    </div>
    <a class="btn" href="{{ route('admin.siswa.create') }}">+ Profil Siswa</a>
</div>

<div class="kartu">
    <form method="GET" class="filter">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nama / NIS / NISN">
        <select name="kelas">
            <option value="">Semua kelas</option>
            @foreach ($kelas as $k)
                <option value="{{ $k->id_kelas }}" @selected((int) request('kelas') === $k->id_kelas)>{{ $k->nama_kelas }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">Semua status</option>
            @foreach ($statusList as $st)
                <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
            @endforeach
        </select>
        <button class="btn garis" type="submit">Terapkan</button>
    </form>

    @if ($siswa->isEmpty())
        <div class="kosong">Belum ada data siswa yang cocok.</div>
    @else
        <table>
            <thead><tr><th>NIS / NISN</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>Status</th><th>Ubah status</th><th></th></tr></thead>
            <tbody>
            @foreach ($siswa as $s)
                <tr>
                    <td class="kecil">{{ $s->nis }}<br>{{ $s->nisn }}</td>
                    <td>
                        <strong>{{ $s->nama_siswa }}</strong><br>
                        <span class="kecil">{{ $s->username }}{{ $s->is_active ? '' : ' · akun nonaktif' }}</span>
                    </td>
                    <td>{{ $s->jenis_kelamin }}</td>
                    <td>{{ $s->nama_kelas ?? '—' }}{{ $s->no_absen ? ' · '.$s->no_absen : '' }}</td>
                    <td><span class="badge {{ $s->status }}">{{ $s->status }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.siswa.status', $s->id_siswa) }}" class="aksi-baris">
                            @csrf
                            <select name="status" style="width:auto">
                                @foreach ($statusList as $st)
                                    <option value="{{ $st }}" @selected($s->status === $st)>{{ $st }}</option>
                                @endforeach
                            </select>
                            <label class="kecil" style="display:flex;gap:4px;align-items:center;margin:0;font-weight:500" title="Berlaku bila status bukan aktif">
                                <input type="checkbox" name="nonaktifkan_akun" value="1" checked> nonaktifkan akun
                            </label>
                            <button class="btn garis kecil" type="submit">Simpan</button>
                        </form>
                    </td>
                    <td><a class="btn garis kecil" href="{{ route('admin.siswa.edit', $s->id_siswa) }}">Ubah</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $siswa->links() }}</div>
    @endif
</div>
@endsection
