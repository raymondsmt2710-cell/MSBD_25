<x-app-layout judul="Sesi Presensi">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="label-kapital">Sesi Presensi</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-sekolah-ink">{{ $sesi->nama_mapel }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $sesi->nama_kelas }} · {{ $sesi->tanggal }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold
                         {{ $sesi->status === 'dibuka' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                <span class="h-2 w-2 rounded-full {{ $sesi->status === 'dibuka' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                {{ ucfirst($sesi->status) }}
            </span>
        </div>
    </x-slot>

    @if ($sesi->status === 'dibuka')
        <div class="grid gap-5 lg:grid-cols-[340px,1fr]">
            <section class="kartu p-5">
                <p class="label-kapital">Pindai untuk hadir</p>
                <div id="qr" class="mt-3 flex aspect-square w-full items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white text-sm text-gray-400">
                    Memuat QR…
                </div>
                <p class="mt-3 flex items-start gap-2 rounded-xl bg-amber-50 px-3 py-2 text-xs leading-relaxed text-sekolah-amber-dark">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    QR berganti otomatis dan hanya berlaku sekali. Minta siswa memindai dari layar ini.
                </p>
                <div class="mt-4 flex items-center justify-between rounded-xl bg-sekolah-teal/5 px-4 py-3">
                    <span class="text-sm font-medium text-gray-600">Sudah hadir</span>
                    <span class="text-3xl font-extrabold tabular-nums text-sekolah-teal"><span id="hadir">0</span></span>
                </div>
                <form method="POST" action="{{ route('guru.presensi.tutup', $sesi->id_sesi) }}" class="mt-4"
                      onsubmit="return confirm('Tutup sesi? Siswa yang belum tercatat akan otomatis izin/sakit/alpa.')">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Tutup Sesi
                    </button>
                </form>
            </section>

            <section class="kartu overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="font-semibold text-gray-800">Daftar Kehadiran</h2>
                    <span class="text-xs text-gray-500">{{ $siswa->count() }} siswa</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50/80">
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">NIS</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Metode</th>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Koreksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($siswa as $s)
                                @php
                                    $status = $s->status ?? 'belum';
                                    $warnaBadge = match ($status) {
                                        'hadir' => 'bg-emerald-50 text-emerald-700',
                                        'izin', 'sakit' => 'bg-amber-50 text-amber-700',
                                        'alpa' => 'bg-rose-50 text-rose-700',
                                        default => 'bg-gray-100 text-gray-500',
                                    };
                                @endphp
                                <tr class="bg-white">
                                    <td class="px-4 py-3 text-gray-500">{{ $s->no_absen }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $s->nis }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $s->nama_siswa }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize {{ $warnaBadge }}">{{ $status }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $s->metode ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $s->waktu_presensi ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('guru.presensi.manual', $sesi->id_sesi) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="id_siswa" value="{{ $s->id_siswa }}">
                                            <select name="status" class="rounded-lg border-gray-300 py-1.5 pl-2 pr-7 text-xs focus:border-sekolah-teal focus:ring-sekolah-teal">
                                                @foreach (['hadir', 'izin', 'sakit', 'alpa'] as $st)
                                                    <option value="{{ $st }}" @selected($status === $st)>{{ ucfirst($st) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:border-sekolah-teal hover:text-sekolah-teal">Simpan</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <script>
            const url = @json(route('guru.presensi.qr', $sesi->id_sesi));
            const kotakQr = document.getElementById('qr');
            const hadir = document.getElementById('hadir');
            let kodeTerakhir = null;

            async function muat() {
                try {
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    if (d.status === 'OK') {
                        if (d.kode !== kodeTerakhir) { kotakQr.innerHTML = d.svg; kodeTerakhir = d.kode; }
                        if (Number(hadir.textContent) !== d.hadir) { hadir.textContent = d.hadir; setTimeout(() => location.reload(), 1200); }
                    } else if (d.status === 'DITUTUP') {
                        location.reload();
                    }
                } catch (e) { /* coba lagi pada putaran berikutnya */ }
            }
            muat();
            setInterval(muat, 3000);
        </script>
    @else
        <div class="kartu overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-800">Daftar Kehadiran</h2>
                <span class="text-xs text-gray-500">{{ $siswa->count() }} siswa</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50/80">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">NIS</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Metode</th>
                            <th class="px-4 py-3">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($siswa as $s)
                            @php
                                $status = $s->status ?? 'belum';
                                $warnaBadge = match ($status) {
                                    'hadir' => 'bg-emerald-50 text-emerald-700',
                                    'izin', 'sakit' => 'bg-amber-50 text-amber-700',
                                    'alpa' => 'bg-rose-50 text-rose-700',
                                    default => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <tr class="bg-white">
                                <td class="px-4 py-3 text-gray-500">{{ $s->no_absen }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $s->nis }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $s->nama_siswa }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize {{ $warnaBadge }}">{{ $status }}</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ $s->metode ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $s->waktu_presensi ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
