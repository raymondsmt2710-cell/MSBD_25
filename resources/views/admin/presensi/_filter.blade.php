<form method="GET" class="filter">
    <select name="tahun" onchange="this.form.kelas.value=''">
        @foreach ($tahunAjaran as $t)
            <option value="{{ $t->id_tahun_ajaran }}" @selected($idTahun === $t->id_tahun_ajaran)>{{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
        @endforeach
    </select>
    <select name="kelas">
        <option value="">Semua kelas</option>
        @foreach ($daftarKelas as $k)
            <option value="{{ $k->id_kelas }}" @selected((int) request('kelas') === $k->id_kelas)>{{ $k->nama_kelas }}</option>
        @endforeach
    </select>
    @isset($denganStatus)
        <select name="status">
            <option value="">Semua status</option>
            @foreach (['menunggu', 'disetujui', 'ditolak'] as $st)
                <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
            @endforeach
        </select>
    @endisset
    <div><label for="dari">Dari</label><input id="dari" type="date" name="dari" value="{{ $dari }}"></div>
    <div><label for="sampai">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ $sampai }}"></div>
    <button class="btn garis" type="submit">Terapkan</button>
</form>
