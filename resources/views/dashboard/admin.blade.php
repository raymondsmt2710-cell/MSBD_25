<x-app-layout judul="Beranda Admin">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="label-kapital">Beranda</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-sekolah-ink">Halo, {{ auth()->user()->username }}</h1>
                <p class="mt-1 text-sm text-gray-500">Ringkasan data sekolah dan pintasan pengelolaan.</p>
            </div>
            <span class="rounded-xl bg-sekolah-teal/10 px-3 py-2 text-sm font-semibold text-sekolah-teal">Administrator</span>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="kartu p-5">
            <p class="label-kapital">Kelas Aktif</p>
            <p class="mt-2 text-3xl font-extrabold text-sekolah-teal">{{ $totalKelas }}</p>
            <p class="mt-1 text-xs text-gray-500">Pada tahun ajaran aktif</p>
        </div>
        <div class="kartu p-5">
            <p class="label-kapital">Siswa Aktif</p>
            <p class="mt-2 text-3xl font-extrabold text-sekolah-ink">{{ $totalSiswa }}</p>
            <p class="mt-1 text-xs text-gray-500">Seluruh data siswa</p>
        </div>
        <div class="kartu p-5">
            <p class="label-kapital">Modul</p>
            <p class="mt-2 text-3xl font-extrabold text-sekolah-amber">3</p>
            <p class="mt-1 text-xs text-gray-500">Presensi · Kas · Denah</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <a href="{{ route('kas.index') }}" class="kartu flex items-center justify-between p-5 transition hover:shadow-lembut">
            <div>
                <p class="font-bold text-sekolah-ink">Kas Kelas</p>
                <p class="text-xs text-gray-500">Catat dan verifikasi keuangan kelas.</p>
            </div>
            <span class="text-sekolah-teal">→</span>
        </a>
        <a href="{{ route('denah.index') }}" class="kartu flex items-center justify-between p-5 transition hover:shadow-lembut">
            <div>
                <p class="font-bold text-sekolah-ink">Denah Sekolah</p>
                <p class="text-xs text-gray-500">Atur kursi dan ruangan.</p>
            </div>
            <span class="text-sekolah-teal">→</span>
        </a>
        <div class="kartu p-5">
            <p class="font-bold text-sekolah-ink">Tahun Ajaran</p>
            <p class="text-xs text-gray-500">Data kelas mengikuti tahun ajaran yang aktif.</p>
        </div>
    </div>

    @if ($kelas->isNotEmpty())
        <div class="kartu mt-5 overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-800">Kelas Aktif</h2>
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
