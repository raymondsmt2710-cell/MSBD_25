<x-app-layout judul="Beranda Guru">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="label-kapital">Beranda</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-sekolah-ink">Halo, {{ auth()->user()->username }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $guru?->nama_guru ?? 'Akun guru belum terhubung ke data guru.' }}</p>
            </div>
            <span class="rounded-xl bg-sekolah-teal/10 px-3 py-2 text-sm font-semibold text-sekolah-teal">Guru</span>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="kartu p-5">
            <p class="label-kapital">Jadwal Mengajar</p>
            <p class="mt-2 text-3xl font-extrabold text-sekolah-teal">{{ $jumlahJadwal }}</p>
            <p class="mt-1 text-xs text-gray-500">Total jadwal pelajaran</p>
        </div>
        <div class="kartu p-5">
            <p class="label-kapital">Kelas Terhubung</p>
            <p class="mt-2 text-3xl font-extrabold text-sekolah-ink">{{ $kelas->count() }}</p>
            <p class="mt-1 text-xs text-gray-500">Wali kelas atau pengampu</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <a href="{{ route('guru.presensi.index') }}" class="kartu p-5 transition hover:shadow-lembut">
            <p class="font-bold text-sekolah-ink">Jadwal Presensi</p>
            <p class="mt-1 text-xs text-gray-500">Buka sesi presensi dan tampilkan QR untuk siswa.</p>
            <span class="mt-3 inline-flex text-sm font-semibold text-sekolah-teal">Buka jadwal →</span>
        </a>
        <a href="{{ route('kas.index') }}" class="kartu p-5 transition hover:shadow-lembut">
            <p class="font-bold text-sekolah-ink">Kas Kelas</p>
            <p class="mt-1 text-xs text-gray-500">Pantau iuran dan keuangan kelas yang Anda ampu.</p>
            <span class="mt-3 inline-flex text-sm font-semibold text-sekolah-teal">Lihat kas →</span>
        </a>
    </div>

    @if ($kelas->isNotEmpty())
        <div class="kartu mt-5 overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-800">Kelas Anda</h2>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach ($kelas as $item)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="font-medium text-gray-800">{{ $item->nama_kelas }}</span>
                        <span class="text-xs text-gray-500">{{ $item->ruangan ?? '—' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-app-layout>
