@extends('layouts.admin')
@section('judul', 'Atur Kursi '.$kelas->nama_kelas)
@section('jalur', 'Pemantauan / Denah / '.$kelas->nama_kelas)
@push('gaya')
<style>
    .ruang{display:grid;gap:8px;margin-top:10px}
    .kursi{background:#f8fafa;border:1px solid #e2e8ea;border-radius:10px;padding:6px}
    .kursi .koordinat{font-size:10px;color:#9ca3af;font-weight:700}
    .kursi select{font-size:12px;padding:5px 6px}
    .kursi.terisi{background:#ecfdf5;border-color:#a7f3d0}
    .kursi.ganda{background:#fee2e2;border-color:#fca5a5}
    .papan{background:#043d44;color:#fff;border-radius:10px;padding:8px;text-align:center;font-size:12px;font-weight:700;letter-spacing:.1em}
    .meja-guru{display:flex;margin-top:8px}
    .meja-guru span{background:#C97A14;color:#fff;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700}
</style>
@endpush
@section('isi')
<div class="kepala">
    <div>
        <h1>Atur kursi — {{ $kelas->nama_kelas }}</h1>
        <p class="subjudul">
            Denah {{ $denah->jumlah_baris }} × {{ $denah->jumlah_kolom }} · berlaku {{ $denah->berlaku_mulai->format('d M Y') }}
            @if ($denah->is_aktif)<span class="badge aktif">aktif</span>@else<span class="badge">tidak aktif</span>@endif
        </p>
    </div>
    <a class="btn garis" href="{{ route('admin.denah.kelas', $kelas->id_kelas) }}">Versi denah</a>
</div>

@if ($belumDuduk->isNotEmpty())
    <div class="info">Belum mendapat kursi: {{ $belumDuduk->pluck('nama_siswa')->implode(', ') }}</div>
@endif

<div class="kartu">
    <div class="papan">PAPAN TULIS / DEPAN KELAS</div>
    @php $sisi = ['depan_kiri' => 'flex-start', 'depan_tengah' => 'center', 'depan_kanan' => 'flex-end'][$denah->posisi_meja_guru]; @endphp
    <div class="meja-guru" style="justify-content:{{ $sisi }}"><span>Meja guru</span></div>

    <form method="POST" action="{{ route('admin.denah.posisi', $denah->id_denah) }}" id="form-denah">
        @csrf @method('PUT')
        <div class="ruang" style="grid-template-columns:repeat({{ $denah->jumlah_kolom }}, minmax(110px, 1fr))">
            @for ($b = 1; $b <= $denah->jumlah_baris; $b++)
                @for ($k = 1; $k <= $denah->jumlah_kolom; $k++)
                    @php $terpilih = (int) old("kursi.$b.$k", $posisi["$b-$k"] ?? 0); @endphp
                    <div class="kursi {{ $terpilih ? 'terisi' : '' }}">
                        <div class="koordinat">B{{ $b }} · K{{ $k }}</div>
                        <select name="kursi[{{ $b }}][{{ $k }}]">
                            <option value="">— kosong —</option>
                            @foreach ($siswa as $s)
                                <option value="{{ $s->id_siswa }}" @selected($terpilih === $s->id_siswa)>{{ $s->no_absen ? $s->no_absen.'. ' : '' }}{{ $s->nama_siswa }}</option>
                            @endforeach
                        </select>
                    </div>
                @endfor
            @endfor
        </div>
        <div class="aksi-baris" style="margin-top:14px">
            <button class="btn" type="submit">Simpan posisi duduk</button>
            <span class="kecil" id="info-ganda"></span>
        </div>
    </form>
</div>
@endsection
@push('skrip')
<script>
    // Tandai kursi kosong/terisi dan siswa yang dipilih di lebih dari satu kursi sebelum dikirim.
    (function () {
        var form = document.getElementById('form-denah');
        function periksa() {
            var hitung = {};
            form.querySelectorAll('select').forEach(function (s) { if (s.value) hitung[s.value] = (hitung[s.value] || 0) + 1; });
            var ganda = 0;
            form.querySelectorAll('select').forEach(function (s) {
                var kotak = s.closest('.kursi');
                kotak.classList.toggle('terisi', !!s.value);
                var dobel = s.value && hitung[s.value] > 1;
                kotak.classList.toggle('ganda', !!dobel);
                if (dobel) ganda++;
            });
            document.getElementById('info-ganda').textContent = ganda ? 'Ada siswa yang menempati lebih dari satu kursi.' : '';
        }
        form.addEventListener('change', periksa);
        periksa();
    })();
</script>
@endpush
