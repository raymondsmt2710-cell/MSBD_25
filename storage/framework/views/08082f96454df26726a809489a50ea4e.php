<div x-show="drawerRuangan" x-cloak class="fixed inset-0 z-50">
    <div @click="drawerRuangan = false" class="absolute inset-0 bg-black/30"></div>

    <aside class="absolute inset-x-0 bottom-0 max-h-[86vh] overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:inset-y-0 sm:left-auto sm:right-0 sm:max-h-none sm:w-full sm:max-w-md sm:rounded-none">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Detail Ruangan</p>
                <h3 class="mt-1 text-lg font-bold text-gray-900" x-text="ruanganTerpilih?.nama_ruangan ?? 'Ruangan'"></h3>
                <p class="text-sm text-gray-500" x-text="ruanganTerpilih?.kode_ruangan"></p>
            </div>
            <button type="button" @click="drawerRuangan = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="mt-5 space-y-3" x-show="ruanganTerpilih">
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Kategori</p>
                    <p class="mt-1 text-sm font-semibold capitalize text-gray-800" x-text="ruanganTerpilih?.kategori?.replace('_', ' ')"></p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Lokasi</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800" x-text="`${ruanganTerpilih?.gedung ?? '-'} · Lantai ${ruanganTerpilih?.lantai ?? '-'}`"></p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Kapasitas</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800" x-text="ruanganTerpilih?.kapasitas ? `${ruanganTerpilih.kapasitas} orang` : '-'"></p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Jam buka</p>
                    <p class="mt-1 text-sm font-semibold text-gray-800"
                       x-text="ruanganTerpilih?.jam_buka ? `${ruanganTerpilih.jam_buka} - ${ruanganTerpilih.jam_tutup ?? 'selesai'}` : '-'"></p>
                </div>
            </div>

            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Fasilitas</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <template x-for="item in (ruanganTerpilih?.fasilitas ?? [])" :key="item">
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-200" x-text="item"></span>
                    </template>
                    <span x-show="!ruanganTerpilih?.fasilitas?.length" class="text-xs text-gray-400">Belum ada data fasilitas.</span>
                </div>
            </div>

            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Deskripsi</p>
                <p class="mt-1 text-sm text-gray-700" x-text="ruanganTerpilih?.deskripsi || 'Tidak ada deskripsi.'"></p>
            </div>

            <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-700">Jadwal saat ini</p>
                <template x-if="ruanganTerpilih?.jadwal_sekarang">
                    <div class="mt-1 text-sm text-indigo-900">
                        <p class="font-semibold" x-text="ruanganTerpilih.jadwal_sekarang.nama_mapel"></p>
                        <p class="text-xs" x-text="`${ruanganTerpilih.jadwal_sekarang.nama_kelas} · ${ruanganTerpilih.jadwal_sekarang.nama_guru}`"></p>
                        <p class="text-xs" x-text="`${ruanganTerpilih.jadwal_sekarang.jam_mulai} - ${ruanganTerpilih.jadwal_sekarang.jam_selesai}`"></p>
                    </div>
                </template>
                <p x-show="!ruanganTerpilih?.jadwal_sekarang" class="mt-1 text-xs text-indigo-700">Tidak ada jadwal yang sedang berlangsung.</p>
            </div>

            <div x-show="bisaKelolaRuangan" class="space-y-3 border-t border-gray-100 pt-4">
                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Ubah status</p>
                    <div class="flex flex-wrap gap-2">
                        <button @click="ubahStatus(ruanganTerpilih, 'tersedia')"
                                class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">
                            Tersedia
                        </button>
                        <button @click="ubahStatus(ruanganTerpilih, 'maintenance')"
                                class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100">
                            Maintenance
                        </button>
                        <button @click="ubahStatus(ruanganTerpilih, 'tutup')"
                                class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-200">
                            Tutup
                        </button>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="bukaRuangan(ruanganTerpilih)"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Edit ruangan
                    </button>
                    <button @click="hapusRuangan(ruanganTerpilih)"
                            class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </aside>
</div>
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/denah/_drawer-ruangan.blade.php ENDPATH**/ ?>