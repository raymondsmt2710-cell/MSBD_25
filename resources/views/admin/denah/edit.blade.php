@extends('layouts.admin')
@section('judul', 'Atur Kursi '.$kelas->nama_kelas)
@section('jalur', 'Pemantauan / Denah / '.$kelas->nama_kelas)
@push('gaya')
<style>
    .ruang{display:grid;gap:8px;margin-top:10px}
    .meja{background:#f8fafa;border:1px solid #e2e8ea;border-radius:12px;padding:6px}
    .meja .kepala-meja{display:flex;justify-content:space-between;align-items:center;padding:0 2px 4px}
    .meja .koordinat{font-size:10px;color:#9ca3af;font-weight:700}
    .meja .jumlah{font-size:10px;color:#cbd5e1}
    .sisi-meja{display:flex;gap:6px}
    .sisi{flex:1;border:1px dashed #d1d5db;border-radius:8px;padding:4px;background:#fff;min-width:0}
    .sisi.terisi{background:#ecfdf5;border-style:solid;border-color:#a7f3d0}
    .sisi.ganda{background:#fee2e2;border-color:#fca5a5}
    .sisi .label{font-size:9px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
    .sisi select{font-size:11px;padding:3px 4px;width:100%;min-width:0}
    .papan{background:#043d44;color:#fff;border-radius:10px;padding:8px;text-align:center;font-size:12px;font-weight:700;letter-spacing:.1em}
    .meja-guru{display:flex;margin-top:8px}
    .meja-guru span{background:#C97A14;color:#fff;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700}
    .pintu{display:flex;margin-top:10px}
    .pintu span{background:#111827;color:#fff;border-radius:6px;padding:3px 14px;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
</style>
@endpush
@section('isi')
<div class="kepala">
    <div>
        <h1>Atur kursi — {{ $kelas->nama_kelas }}</h1>
        <p class="subjudul">
            Denah {{ $denah->jumlah_baris }} × {{ $denah->jumlah_kolom }} · {{ $denah->jumlah_baris * $denah->jumlah_kolom * count($sisi) }} kursi
            · berlaku {{ $denah->berlaku_mulai->format('d M Y') }}
            @if ($denah->is_aktif)<span class="badge aktif">aktif</span>@else<span class="badge">tidak aktif</span>@endif
        </p>
    </div>
    <a class="btn garis" href="{{ route('admin.denah.kelas', $kelas->id_kelas) }}">Versi denah</a>
</div>

@if ($belumDuduk->isNotEmpty())
    <div class="info">Belum mendapat kursi: {{ $belumDuduk->pluck('nama_siswa')->implode(', ') }}</div>
@endif

<div class="kartu">
    <p class="kecil" style="margin:0 0 10px">
        Setiap meja berisi 2 murid: sisi <strong>kiri</strong> dan <strong>kanan</strong>.
        Satu siswa hanya boleh menempati satu kursi.
    </p>

    <div class="papan">PAPAN TULIS / DEPAN KELAS</div>
    @php $gayaGuru = ['depan_kiri' => 'flex-start', 'depan_tengah' => 'center', 'depan_kanan' => 'flex-end'][$denah->posisi_meja_guru] ?? 'center'; @endphp
    <div class="meja-guru" style="justify-content:{{ $gayaGuru }}"><span>Meja guru</span></div>

    <form method="POST" action="{{ route('admin.denah.posisi', $denah->id_denah) }}" id="form-denah">
        @csrf @method('PUT')
        <div class="ruang" style="grid-template-columns:repeat({{ $denah->jumlah_kolom }}, minmax(160px, 1fr))">
            @for ($b = 1; $b <= $denah->jumlah_baris; $b++)
                @for ($k = 1; $k <= $denah->jumlah_kolom; $k++)
                    <div class="meja">
                        <div class="kepala-meja">
                            <span class="koordinat">B{{ $b }} · K{{ $k }}</span>
                            <span class="jumlah">{{ collect($sisi)->filter(fn ($s) => ! empty(old("kursi.$b.$k.$s", $posisi["$b-$k-$s"] ?? null)))->count() }}/{{ count($sisi) }}</span>
                        </div>
                        <div class="sisi-meja">
                            @foreach ($sisi as $s)
                                @php $terpilih = (int) old("kursi.$b.$k.$s", $posisi["$b-$k-$s"] ?? 0); @endphp
                                <div class="sisi {{ $terpilih ? 'terisi' : '' }}">
                                    <div class="label">{{ $s }}</div>
                                    <select name="kursi[{{ $b }}][{{ $k }}][{{ $s }}]">
                                        <option value="">— kosong —</option>
                                        @foreach ($siswa as $sw)
                                            <option value="{{ $sw->id_siswa }}" @selected($terpilih === $sw->id_siswa)>{{ $sw->no_absen ? $sw->no_absen.'. ' : '' }}{{ $sw->nama_siswa }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endfor
            @endfor
        </div>

        @php $gayaPintu = ['kiri' => 'flex-start', 'kanan' => 'flex-end', 'belakang' => 'center'][$denah->posisi_pintu] ?? 'flex-end'; @endphp
        <div class="pintu" style="justify-content:{{ $gayaPintu }}"><span>Pintu</span></div>

        <div class="aksi-baris" style="margin-top:14px">
            <button class="btn" type="submit">Simpan posisi duduk</button>
            <span class="kecil" id="info-ganda"></span>
        </div>
    </form>
</div>
@endsection
@push('skrip')
<script>
    // Tandai sisi kosong/terisi dan siswa yang dipilih di lebih dari satu kursi sebelum dikirim.
    (function () {
        var form = document.getElementById('form-denah');
        function periksa() {
            var hitung = {};
            form.querySelectorAll('select').forEach(function (s) { if (s.value) hitung[s.value] = (hitung[s.value] || 0) + 1; });
            var ganda = 0;
            form.querySelectorAll('select').forEach(function (s) {
                var kotak = s.closest('.sisi');
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
