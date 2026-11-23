<div x-show="modalBuka" x-cloak
     class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4"
     @keydown.escape.window="tutupModal()">
    <div @click.outside="tutupModal()"
         class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800" x-text="modeEdit ? 'Edit Transaksi' : 'Tambah Transaksi'"></h3>
                <p class="text-xs text-gray-500">Nominal harus positif dan keterangan wajib diisi.</p>
            </div>
            <button type="button" @click="tutupModal()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form @submit.prevent="simpan()" class="grid gap-4 p-5 sm:grid-cols-2">
            <label class="block sm:col-span-1">
                <span class="mb-1 block text-sm font-medium text-gray-700">Tanggal</span>
                <input type="date" x-model="form.tanggal"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <span x-show="error('tanggal')" x-text="error('tanggal')" class="mt-1 block text-xs text-rose-600"></span>
            </label>

            <label class="block sm:col-span-1">
                <span class="mb-1 block text-sm font-medium text-gray-700">Jenis</span>
                <select x-model="form.jenis" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
                <span x-show="error('jenis')" x-text="error('jenis')" class="mt-1 block text-xs text-rose-600"></span>
            </label>

            <label class="block sm:col-span-1">
                <span class="mb-1 block text-sm font-medium text-gray-700">Kategori</span>
                <select x-model="form.kategori" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <template x-for="item in kategori" :key="item">
                        <option :value="item" x-text="item"></option>
                    </template>
                </select>
                <span x-show="error('kategori')" x-text="error('kategori')" class="mt-1 block text-xs text-rose-600"></span>
            </label>

            <label class="block sm:col-span-1">
                <span class="mb-1 block text-sm font-medium text-gray-700">Nominal (Rp)</span>
                <input type="number" min="1" step="0.01" x-model="form.nominal" placeholder="20000"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <span x-show="error('nominal')" x-text="error('nominal')" class="mt-1 block text-xs text-rose-600"></span>
            </label>

            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium text-gray-700">Keterangan</span>
                <textarea x-model="form.keterangan" rows="3" placeholder="Contoh: Iuran keuangan kelas bulan November"
                          class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <span x-show="error('keterangan')" x-text="error('keterangan')" class="mt-1 block text-xs text-rose-600"></span>
            </label>

            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium text-gray-700">Siswa terkait <span class="font-normal text-gray-400">(opsional)</span></span>
                <select x-model="form.id_siswa" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tidak terkait siswa tertentu</option>
                    <template x-for="item in anggota" :key="item.id_siswa">
                        <option :value="item.id_siswa" x-text="`${item.no_absen ? item.no_absen + '. ' : ''}${item.nama}`"></option>
                    </template>
                </select>
                <span x-show="error('id_siswa')" x-text="error('id_siswa')" class="mt-1 block text-xs text-rose-600"></span>
            </label>

            <label class="block sm:col-span-2">
                <span class="mb-1 block text-sm font-medium text-gray-700">Bukti transaksi <span class="font-normal text-gray-400">(opsional, maks. 2 MB)</span></span>
                <input type="file" accept="image/jpeg,image/png,image/webp" @change="pilihBukti($event)"
                       class="block w-full rounded-lg border border-gray-300 text-sm text-gray-600 file:mr-3 file:border-0 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-100">
                <span x-show="error('bukti')" x-text="error('bukti')" class="mt-1 block text-xs text-rose-600"></span>
            </label>

            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" @click="tutupModal()"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" :disabled="menyimpan"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50">
                    <svg x-show="menyimpan" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="menyimpan ? 'Menyimpan...' : 'Simpan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
