<div class="bidang">
    <label for="id_user">Akun {{ $role }}</label>
    @if ($akun->isEmpty())
        <div class="info" style="margin:0">
            Semua akun {{ $role }} sudah memiliki profil.
            <a href="{{ route('admin.akun.create') }}"><strong>Buat akun baru</strong></a> terlebih dahulu.
        </div>
    @else
        <select id="id_user" name="id_user" required>
            <option value="">— pilih akun —</option>
            @foreach ($akun as $a)
                <option value="{{ $a->id_user }}" @selected((int) old('id_user', $terpilih) === $a->id_user)>
                    {{ $a->username }}{{ $a->is_active ? '' : ' (nonaktif)' }}
                </option>
            @endforeach
        </select>
        <span class="kecil">Hanya akun ber-peran {{ $role }} yang belum memiliki profil.</span>
    @endif
</div>
