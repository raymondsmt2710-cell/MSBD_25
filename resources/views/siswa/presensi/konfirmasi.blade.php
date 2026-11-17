<x-app-layout judul="Konfirmasi Presensi">
    <x-slot name="header">
        <div>
            <p class="label-kapital">Presensi</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-sekolah-ink">Konfirmasi Kehadiran</h1>
            <p class="mt-1 text-sm text-gray-500">Satu ketukan untuk mencatat kehadiran Anda pada sesi ini.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-md">
        <div class="kartu overflow-hidden p-7 text-center">
            @if ($valid)
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-sekolah-teal/10 text-sekolah-teal">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-xl font-extrabold text-sekolah-ink">QR Valid</h2>
                <p class="mt-2 text-sm leading-relaxed text-gray-500">
                    Tekan tombol di bawah untuk mencatat kehadiran Anda pada sesi ini. QR hanya dapat dipakai sekali.
                </p>
                <form method="POST" action="{{ route('siswa.presensi.scan', $kode) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="tombol-utama w-full py-3">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Saya Hadir
                    </button>
                </form>
            @else
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-sekolah-amber">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.2 14.2A1 1 0 003 19.5h18a1 1 0 00.87-1.5l-8.2-14.2a1 1 0 00-1.74 0z"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-xl font-extrabold text-sekolah-ink">QR Tidak Berlaku</h2>
                <p class="mt-2 text-sm leading-relaxed text-gray-500">
                    QR sudah kedaluwarsa, sudah dipakai, atau sesi sudah ditutup. Pindai QR yang baru di layar guru.
                </p>
                <a href="{{ route('siswa.presensi.riwayat') }}" class="tombol-garis mt-6 w-full py-3">Lihat Riwayat</a>
            @endif
        </div>
    </div>
</x-app-layout>
