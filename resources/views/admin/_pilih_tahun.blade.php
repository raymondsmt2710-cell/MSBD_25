{{-- Pemilih tahun ajaran sebagai filter GET. Variabel: $tahunAjaran, $idTahun. --}}
<form method="GET" class="filter">
    <select name="tahun" onchange="this.form.submit()">
        @foreach ($tahunAjaran as $t)
            <option value="{{ $t->id_tahun_ajaran }}" @selected($idTahun === $t->id_tahun_ajaran)>
                {{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}
            </option>
        @endforeach
    </select>
    <noscript><button class="btn garis" type="submit">Tampilkan</button></noscript>
</form>
