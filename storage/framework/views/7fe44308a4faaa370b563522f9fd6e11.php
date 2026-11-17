<div x-show="modalRuangan" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="tutupRuangan()">
    <div @click.outside="tutupRuangan()" class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:max-w-2xl sm:rounded-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800" x-text="formRuangan.id_ruangan ? 'Edit Ruangan' : 'Tambah Ruangan'"></h3>
                <p class="text-xs text-gray-500">Posisi x/y memakai satuan grid kanvas.</p>
            </div>
            <button type="button" @click="tutupRuangan()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form @submit.prevent="simpanRuangan()" class="grid gap-4 p-5 sm:grid-cols-2">
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Kode ruangan</span>
                <input type="text" x-model="formRuangan.kode_ruangan" placeholder="R-101"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Nama ruangan</span>
                <input type="text" x-model="formRuangan.nama_ruangan" placeholder="Ruang Kelas XI IPA 1"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Kategori</span>
                <select x-model="formRuangan.kategori" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <template x-for="item in kategoriRuangan" :key="item.value">
                        <option :value="item.value" x-text="item.label"></option>
                    </template>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Status</span>
                <select x-model="formRuangan.status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <template x-for="item in statusRuangan" :key="item.value">
                        <option :value="item.value" x-text="item.label"></option>
                    </template>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Gedung</span>
                <input type="text" x-model="formRuangan.gedung"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Lantai</span>
                <input type="number" min="1" max="20" x-model.number="formRuangan.lantai"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Kapasitas</span>
                <input type="number" min="1" x-model.number="formRuangan.kapasitas"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Jam buka</span>
                <input type="time" x-model="formRuangan.jam_buka"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Jam tutup</span>
                <input type="time" x-model="formRuangan.jam_tutup"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Posisi X (grid)</span>
                <input type="number" min="0" max="100" x-model.number="formRuangan.posisi_x"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Posisi Y (grid)</span>
                <input type="number" min="0" max="100" x-model.number="formRuangan.posisi_y"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Lebar (grid)</span>
                <input type="number" min="1" max="10" x-model.number="formRuangan.lebar_grid"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700">Tinggi (grid)</span>
                <input type="number" min="1" max="10" x-model.number="formRuangan.tinggi_grid"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium text-gray-700">Fasilitas <span class="font-normal text-gray-400">(pisahkan dengan koma)</span></span>
                <input type="text" x-model="formRuangan.fasilitas" placeholder="AC, Proyektor, Papan Tulis"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium text-gray-700">Deskripsi</span>
                <textarea x-model="formRuangan.deskripsi" rows="2"
                          class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </label>

            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" @click="tutupRuangan()"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                    Simpan Ruangan
                </button>
            </div>
        </form>
    </div>
</div>
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/denah/_modal-ruangan.blade.php ENDPATH**/ ?>