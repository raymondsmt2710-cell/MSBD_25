<x-app-layout judul="Beranda Siswa">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="label-kapital">Beranda</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-sekolah-ink">Halo, {{ $siswa?->nama_siswa ?? auth()->user()->username }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $kelas?->nama_kelas ?? 'Belum terdaftar di kelas aktif' }}
                    @if ($siswa) · NIS {{ $siswa->nis }} @endif
                </p>
            </div>
            <span class="rounded-xl bg-sekolah-teal/10 px-3 py-2 text-sm font-semibold text-sekolah-teal">Siswa</span>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-3">
        <a href="{{ route('siswa.presensi.riwayat') }}" class="kartu p-5 transition hover:shadow-lembut">
            <p class="font-bold text-sekolah-ink">Riwayat Presensi</p>
            <p class="mt-1 text-xs text-gray-500">Lihat persentase kehadiran dan catatan per sesi.</p>
            <span class="mt-3 inline-flex text-sm font-semibold text-sekolah-teal">Lihat riwayat →</span>
        </a>
        <a href="{{ route('kas.index') }}" class="kartu p-5 transition hover:shadow-lembut">
            <p class="font-bold text-sekolah-ink">Kas Kelas</p>
            <p class="mt-1 text-xs text-gray-500">Cek status iuran dan saldo kelas.</p>
            <span class="mt-3 inline-flex text-sm font-semibold text-sekolah-teal">Lihat kas →</span>
        </a>
        <a href="{{ route('denah.index') }}" class="kartu p-5 transition hover:shadow-lembut">
            <p class="font-bold text-sekolah-ink">Denah Kelas</p>
            <p class="mt-1 text-xs text-gray-500">Temukan posisi kursi Anda.</p>
            <span class="mt-3 inline-flex text-sm font-semibold text-sekolah-teal">Lihat denah →</span>
        </a>
    </div>

    <div class="mt-5 rounded-2xl border border-teal-100 bg-teal-50/60 p-5 text-sm text-sekolah-teal-deep">
        <p class="font-semibold">Mau mencatat kehadiran?</p>
        <p class="mt-1 text-xs leading-relaxed">Pindai QR yang ditampilkan guru di depan kelas menggunakan kamera ponsel. Pastikan Anda sudah masuk ke akun ini.</p>
    </div>
</x-app-layout>
