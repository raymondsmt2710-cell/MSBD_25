@extends('layouts.admin')
@section('judul', 'Keuangan Semua Kelas')
@section('jalur', 'Pemantauan / Keuangan')
@section('isi')
@php $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.'); @endphp
<h1>Keuangan Semua Kelas</h1>
<p class="subjudul">Pencatatan harian oleh bendahara kelas. Admin dapat membatalkan transaksi sebagai koreksi — transaksi tidak pernah dihapus, dan saldo tidak menghitung transaksi batal.</p>

<div class="kartu">
    <h2>Saldo per kelas</h2>
    @if ($saldo->isEmpty())
        <div class="kosong">Belum ada kelas pada tahun ajaran ini.</div>
    @else
        <table>
            <thead><tr><th>Kelas</th><th class="uang">Pemasukan</th><th class="uang">Pengeluaran</th><th class="uang">Saldo</th><th>Transaksi</th></tr></thead>
            <tbody>
            @foreach ($saldo as $s)
                @php $sisa = $s->pemasukan - $s->pengeluaran; @endphp
                <tr>
                    <td><a href="{{ request()->fullUrlWithQuery(['kelas' => $s->id_kelas, 'page' => null]) }}"><strong>{{ $s->nama_kelas }}</strong></a></td>
                    <td class="uang">{{ $rp($s->pemasukan) }}</td>
                    <td class="uang">{{ $rp($s->pengeluaran) }}</td>
                    <td class="uang" style="font-weight:700;color:{{ $sisa < 0 ? '#b91c1c' : '#166534' }}">{{ $rp($sisa) }}</td>
                    <td>{{ $s->jumlah }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total</strong></td>
                <td class="uang"><strong>{{ $rp($saldo->sum('pemasukan')) }}</strong></td>
                <td class="uang"><strong>{{ $rp($saldo->sum('pengeluaran')) }}</strong></td>
                <td class="uang"><strong>{{ $rp($saldo->sum('pemasukan') - $saldo->sum('pengeluaran')) }}</strong></td>
                <td>{{ $saldo->sum('jumlah') }}</td>
            </tr>
            </tbody>
        </table>
    @endif
</div>

<div class="kartu">
    <h2>Transaksi</h2>
    <form method="GET" class="filter">
        <select name="tahun" onchange="this.form.kelas.value=''">
            @foreach ($tahunAjaran as $t)
                <option value="{{ $t->id_tahun_ajaran }}" @selected($idTahun === $t->id_tahun_ajaran)>{{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
            @endforeach
        </select>
        <select name="kelas">
            <option value="">Semua kelas</option>
            @foreach ($saldo as $s)
                <option value="{{ $s->id_kelas }}" @selected((int) request('kelas') === $s->id_kelas)>{{ $s->nama_kelas }}</option>
            @endforeach
        </select>
        <select name="jenis">
            <option value="">Semua jenis</option>
            <option value="pemasukan" @selected(request('jenis') === 'pemasukan')>Pemasukan</option>
            <option value="pengeluaran" @selected(request('jenis') === 'pengeluaran')>Pengeluaran</option>
        </select>
        <select name="batal">
            <option value="semua" @selected($modeBatal === 'semua')>Termasuk batal</option>
            <option value="tanpa" @selected($modeBatal === 'tanpa')>Tanpa batal</option>
            <option value="hanya" @selected($modeBatal === 'hanya')>Hanya batal</option>
        </select>
        <div><label for="dari">Dari</label><input id="dari" type="date" name="dari" value="{{ request('dari') }}"></div>
        <div><label for="sampai">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ request('sampai') }}"></div>
        <button class="btn garis" type="submit">Terapkan</button>
    </form>

    @if ($transaksi->isEmpty())
        <div class="kosong">Tidak ada transaksi pada filter ini.</div>
    @else
        <table>
            <thead><tr><th>Tanggal</th><th>Kelas</th><th>Jenis / kategori</th><th>Keterangan</th><th class="uang">Nominal</th><th>Dicatat</th><th>Status / aksi</th></tr></thead>
            <tbody>
            @foreach ($transaksi as $t)
                <tr class="{{ $t->is_batal ? 'redup' : '' }}">
                    <td>{{ \Illuminate\Support\Carbon::parse($t->tanggal)->format('d M Y') }}</td>
                    <td>{{ $t->nama_kelas }}</td>
                    <td><span class="badge {{ $t->jenis }}">{{ $t->jenis }}</span><br><span class="kecil">{{ $t->kategori }}</span></td>
                    <td class="kecil">
                        {{ $t->keterangan }}{{ $t->nama_siswa ? ' · '.$t->nama_siswa : '' }}
                        @if ($t->bukti_url) · <a href="{{ str_starts_with($t->bukti_url, 'http') ? $t->bukti_url : asset('storage/'.$t->bukti_url) }}" target="_blank" rel="noopener">bukti</a>@endif
                    </td>
                    <td class="uang" style="{{ $t->is_batal ? 'text-decoration:line-through' : '' }}">{{ $rp($t->nominal) }}</td>
                    <td class="kecil">{{ $t->pencatat }}</td>
                    <td style="min-width:220px">
                        @if ($t->is_batal)
                            <span class="badge merah">batal</span>
                            <div class="kecil">{{ $t->pembatal ?? '?' }}{{ $t->dibatalkan_pada ? ' · '.\Illuminate\Support\Carbon::parse($t->dibatalkan_pada)->format('d M H:i') : '' }}<br>{{ $t->alasan_batal }}</div>
                        @else
                            <form method="POST" action="{{ route('admin.kas.batal', $t->id_transaksi) }}" class="aksi-baris"
                                  data-konfirmasi="Batalkan transaksi {{ $rp($t->nominal) }} di {{ $t->nama_kelas }}? Tindakan ini tercatat dan tidak dapat diurungkan.">
                                @csrf
                                <input name="alasan_batal" placeholder="Alasan pembatalan" required minlength="5" maxlength="255" style="flex:1;min-width:120px">
                                <button class="btn merah kecil" type="submit">Batalkan</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="paginasi">{{ $transaksi->links() }}</div>
    @endif
</div>
@endsection
