<x-app-layout judul="Beranda Orang Tua">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="label-kapital">Beranda</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-sekolah-ink">Halo, {{ $orangTua?->nama ?? auth()->user()->username }}</h1>
                <p class="mt-1 text-sm text-gray-500">Pantau kegiatan kelas anak Anda.</p>
            </div>
            <span class="rounded-xl bg-sekolah-teal/10 px-3 py-2 text-sm font-semibold text-sekolah-teal">Orang Tua</span>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-2">
        <a href="{{ route('kas.index') }}" class="kartu p-5 transition hover:shadow-lembut">
            <p class="font-bold text-sekolah-ink">Kas Kelas</p>
            <p class="mt-1 text-xs text-gray-500">Lihat status iuran dan keuangan kelas anak Anda.</p>
            <span class="mt-3 inline-flex text-sm font-semibold text-sekolah-teal">Lihat kas →</span>
        </a>
        <a href="{{ route('denah.index') }}" class="kartu p-5 transition hover:shadow-lembut">
            <p class="font-bold text-sekolah-ink">Denah Kelas</p>
            <p class="mt-1 text-xs text-gray-500">Lihat posisi duduk dan ruangan sekolah.</p>
            <span class="mt-3 inline-flex text-sm font-semibold text-sekolah-teal">Lihat denah →</span>
        </a>
    </div>

    <div class="kartu mt-5 overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-800">Anak Anda</h2>
        </div>
        @forelse ($anak as $item)
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3 text-sm last:border-0">
                <div>
                    <p class="font-medium text-gray-800">{{ $item->nama_siswa }}</p>
                    <p class="text-xs text-gray-500">NIS {{ $item->nis }}</p>
                </div>
                <span class="rounded-full bg-sekolah-teal/10 px-2.5 py-1 text-[11px] font-semibold text-sekolah-teal">
                    {{ $item->anggotaKelas->first()?->kelas?->nama_kelas ?? 'Belum ada kelas' }}
                </span>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-sm text-gray-500">Belum ada data anak yang terhubung ke akun ini.</p>
        @endforelse
    </div>
</x-app-layout>
