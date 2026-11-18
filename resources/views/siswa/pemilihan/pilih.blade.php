@extends('layouts.pemilihan')
@section('judul', 'Pilih Kandidat')
@section('isi')
@php $id = $pemilihan->id_pemilihan; @endphp

<h1>{{ $pemilihan->judul }}</h1>
<p class="subjudul">
    Pilih satu kandidat. Suara Anda <strong>rahasia</strong> dan <strong>tidak dapat diubah</strong> setelah dikirim.
</p>

<div class="grid grid-3">
    @foreach ($kandidat as $k)
        <div class="kandidat-kartu">
            <div class="kandidat-foto">
                @if ($k->foto_url)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($k->foto_url) }}" alt="Foto kandidat {{ $k->nomor_urut }}">
                @else
                    {{ strtoupper(substr($k->nama_ketua ?? 'K', 0, 1)) }}
                @endif
            </div>
            <div class="kandidat-isi">
                <span class="nomor">No. {{ str_pad((string) $k->nomor_urut, 2, '0', STR_PAD_LEFT) }}</span>
                <div class="nama-pasangan">
                    {{ $k->nama_ketua }}@if ($k->nama_wakil) &amp; {{ $k->nama_wakil }}@endif
                </div>
                <div class="visi">{{ $k->visi ?: 'Belum ada visi.' }}</div>

                <details class="detail-kandidat">
                    <summary>Lihat detail</summary>
                    <div class="detail-isi">
                        <div><strong>Visi:</strong> {{ $k->visi ?: '—' }}</div>
                        <div><strong>Misi:</strong> {{ $k->misi ?: '—' }}</div>
                    </div>
                </details>

                <button class="btn" type="button" style="margin-top:4px"
                        onclick="bukaKonfirmasi({{ $k->id_kandidat }}, {{ $k->nomor_urut }}, @js($k->nama_ketua . ($k->nama_wakil ? ' & ' . $k->nama_wakil : '')) )">
                    Pilih
                </button>
            </div>
        </div>
    @endforeach
</div>

{{-- Modal konfirmasi: satu-satunya jalan mengirim suara. --}}
<div class="modal-latar" id="modal-konfirmasi">
    <div class="modal">
        <h3>Konfirmasi pilihan</h3>
        <p>
            Anda akan memilih <strong id="teks-kandidat"></strong>.<br>
            Suara bersifat rahasia dan <strong>tidak dapat diubah</strong> setelah dikirim.
        </p>
        <form method="POST" action="{{ route('siswa.pemilihan.simpan', $id) }}" id="form-pilih">
            @csrf
            <input type="hidden" name="id_kandidat" id="input-kandidat">
            <div class="modal-aksi">
                <button class="btn abu" type="button" onclick="tutupKonfirmasi()">Batal</button>
                <button class="btn" type="submit" id="tombol-kirim">Ya, Kirim Suara</button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaKonfirmasi(idKandidat, nomor, nama) {
        document.getElementById('input-kandidat').value = idKandidat;
        document.getElementById('teks-kandidat').textContent = 'kandidat No. ' + String(nomor).padStart(2, '0') + ' (' + nama + ')';
        document.getElementById('modal-konfirmasi').classList.add('buka');
        document.getElementById('tombol-kirim').disabled = false;
    }

    function tutupKonfirmasi() {
        document.getElementById('modal-konfirmasi').classList.remove('buka');
    }

    // Cegah dobel klik: server tetap menjadi penjaga terakhir lewat sp_rekam_suara.
    document.getElementById('form-pilih').addEventListener('submit', function () {
        document.getElementById('tombol-kirim').disabled = true;
        document.getElementById('tombol-kirim').textContent = 'Mengirim…';
    });

    document.getElementById('modal-konfirmasi').addEventListener('click', function (e) {
        if (e.target === this) tutupKonfirmasi();
    });
</script>
@endsection
