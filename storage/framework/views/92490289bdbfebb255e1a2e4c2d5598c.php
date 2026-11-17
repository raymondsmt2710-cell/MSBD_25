<div x-show="drawerSiswa" x-cloak class="fixed inset-0 z-50">
    <div @click="tutupDrawerSiswa()" class="absolute inset-0 bg-black/30"></div>

    <aside class="absolute inset-x-0 bottom-0 max-h-[82vh] overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:inset-y-0 sm:left-auto sm:right-0 sm:max-h-none sm:w-full sm:max-w-md sm:rounded-none">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Detail Siswa</p>
                <h3 class="mt-1 text-lg font-bold text-gray-900" x-text="siswaTerpilih?.nama_siswa ?? 'Siswa'"></h3>
                <p class="text-sm text-gray-500" x-text="siswaTerpilih?.nis ? `NIS ${siswaTerpilih.nis}` : ''"></p>
            </div>
            <button type="button" @click="tutupDrawerSiswa()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="mt-5 space-y-3">
            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Jabatan</p>
                <p class="mt-1 text-sm font-semibold text-gray-800" x-text="siswaTerpilih?.jabatan ?? 'Anggota kelas'"></p>
            </div>

            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Posisi duduk</p>
                <p class="mt-1 text-sm font-semibold text-gray-800"
                   x-text="kursiTerpilih ? `Meja ${kursiTerpilih.baris}.${kursiTerpilih.kolom} — sisi ${kursiTerpilih.sisi === 'kanan' ? 'kanan' : 'kiri'}` : 'Belum ditempatkan'"></p>
            </div>

            <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 text-sm text-indigo-900">
                <p class="font-medium">Posisi saat ini</p>
                <p class="mt-1 text-xs text-indigo-700">
                    Setiap meja menampung 2 murid (sisi kiri dan kanan). Gunakan mode edit untuk menukar
                    posisi dengan murid lain atau memindahkan ke sisi meja yang masih kosong.
                </p>
            </div>
        </div>
    </aside>
</div>
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/denah/_drawer-siswa.blade.php ENDPATH**/ ?>