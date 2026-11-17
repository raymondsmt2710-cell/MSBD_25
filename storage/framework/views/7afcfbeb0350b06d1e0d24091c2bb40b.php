<?php if (isset($component)) { $__componentOriginal4619374cef299e94fd7263111d0abc69 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4619374cef299e94fd7263111d0abc69 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-layout','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Kas Kelas</h2>
                <p class="text-sm text-gray-500"><?php echo e($initial['kelas']['nama_kelas']); ?></p>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-6" x-data="kasPage(<?php echo \Illuminate\Support\Js::from($initial)->toHtml() ?>)" x-cloak>
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-sm text-gray-500">Kelas</label>
                    <select x-model.number="idKelas" @change="gantiKelas()"
                            class="rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <template x-for="kelas in daftarKelas" :key="kelas.id_kelas">
                            <option :value="kelas.id_kelas" x-text="kelas.nama_kelas"></option>
                        </template>
                    </select>
                    <span x-show="memuat" class="text-xs text-indigo-600">Memuat...</span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a :href="urlLaporan('/kas/export')"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 4h16v16H4z"/>
                        </svg>
                        Export CSV
                    </a>
                    <a :href="urlLaporan('/kas/cetak')" target="_blank"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
                        </svg>
                        Cetak / PDF
                    </a>
                </div>
            </div>

            
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Saldo Akhir</p>
                        <span class="rounded-full bg-indigo-50 p-2 text-indigo-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h2m4 0h4M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-gray-900" x-text="formatRp(ringkasan.saldo)"></p>
                    <p class="mt-1 text-xs text-gray-400">Total pemasukan dikurangi pengeluaran</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Pemasukan Bulan Ini</p>
                        <span class="rounded-full bg-emerald-50 p-2 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0 0l-6-6m6 6l6-6"/>
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-emerald-600" x-text="formatRp(ringkasan.pemasukan_bulan_ini)"></p>
                    <p class="mt-1 text-xs text-gray-400">Transaksi masuk bulan berjalan</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Pengeluaran Bulan Ini</p>
                        <span class="rounded-full bg-rose-50 p-2 text-rose-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V4m0 0l-6 6m6-6l6 6"/>
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-rose-600" x-text="formatRp(ringkasan.pengeluaran_bulan_ini)"></p>
                    <p class="mt-1 text-xs text-gray-400">Transaksi keluar bulan berjalan</p>
                </div>
            </div>

            
            <div class="flex gap-2 rounded-xl bg-gray-100 p-1">
                <button @click="tab = 'transaksi'"
                        :class="tab === 'transaksi' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition">
                    Riwayat Transaksi
                </button>
                <button @click="tab = 'iuran'"
                        :class="tab === 'iuran' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition">
                    Matriks Iuran
                </button>
            </div>

            
            <div x-show="tab === 'transaksi'" class="grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 sm:grid-cols-2 lg:grid-cols-6">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-500">Dari tanggal</span>
                    <input type="date" x-model="filter.dari" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-500">Sampai tanggal</span>
                    <input type="date" x-model="filter.sampai" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-500">Jenis</span>
                    <select x-model="filter.jenis" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua jenis</option>
                        <option value="pemasukan">Pemasukan</option>
                        <option value="pengeluaran">Pengeluaran</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-500">Kategori</span>
                    <select x-model="filter.kategori" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua kategori</option>
                        <template x-for="item in kategori" :key="item">
                            <option :value="item" x-text="item"></option>
                        </template>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-gray-500">Cari</span>
                    <input type="search" x-model.debounce.300ms="filter.q" placeholder="Keterangan / siswa..."
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>
                <div class="flex items-end justify-between gap-2">
                    <label class="flex items-center gap-2 pb-2 text-xs text-gray-500">
                        <input type="checkbox" x-model="filter.termasuk_batal" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Tampilkan batal
                    </label>
                    <button @click="resetFilter()" class="rounded-lg px-2 py-2 text-xs font-medium text-gray-500 hover:bg-gray-50">Reset</button>
                </div>
            </div>

            
            <div x-show="tab === 'transaksi'" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-800" x-text="pagination.total"></span> transaksi ditemukan
                    </p>
                    <button x-show="bisaKelola" @click="bukaTambah()"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Transaksi
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">Tanggal</th>
                                <th class="px-4 py-3 font-medium">Jenis</th>
                                <th class="px-4 py-3 font-medium">Kategori</th>
                                <th class="px-4 py-3 font-medium">Nominal</th>
                                <th class="px-4 py-3 font-medium">Keterangan</th>
                                <th class="px-4 py-3 font-medium">Siswa</th>
                                <th class="px-4 py-3 font-medium">Dicatat</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="transaksi in transaksi" :key="transaksi.id_transaksi">
                                <tr :class="transaksi.is_batal ? 'bg-rose-50/50' : 'hover:bg-gray-50/70'">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600" x-text="transaksi.tanggal?.slice(0, 10)"></td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium"
                                              :class="transaksi.jenis === 'pemasukan' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                              x-text="transaksi.jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600" x-text="transaksi.kategori"></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold"
                                        :class="transaksi.jenis === 'pemasukan' ? 'text-emerald-600' : 'text-rose-600'"
                                        x-text="formatRp(transaksi.nominal)"></td>
                                    <td class="max-w-[18rem] px-4 py-3 text-gray-600">
                                        <span class="line-clamp-2" x-text="transaksi.keterangan"></span>
                                        <span x-show="transaksi.is_batal" class="mt-1 block text-xs text-rose-600" x-text="`Dibatalkan: ${transaksi.alasan_batal ?? '-'}`"></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500" x-text="transaksi.siswa?.nama_siswa ?? '-'"></td>
                                    <td class="px-4 py-3 text-gray-500" x-text="transaksi.pencatat?.username ?? '-'"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <template x-if="bisaKelola && !transaksi.is_batal">
                                            <div class="flex justify-end gap-2">
                                                <button @click="bukaEdit(transaksi)" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</button>
                                                <button @click="batalkan(transaksi)" class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Batal</button>
                                            </div>
                                        </template>
                                        <span x-show="transaksi.is_batal" class="text-xs text-gray-400">—</span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="transaksi.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">
                                    Belum ada transaksi yang cocok dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div x-show="pagination.last_page > 1" class="flex items-center justify-between border-t border-gray-100 px-4 py-3">
                    <button @click="muatHalaman(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 disabled:cursor-not-allowed disabled:opacity-40">
                        Sebelumnya
                    </button>
                    <span class="text-sm text-gray-500" x-text="`Halaman ${pagination.current_page} dari ${pagination.last_page}`"></span>
                    <button @click="muatHalaman(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 disabled:cursor-not-allowed disabled:opacity-40">
                        Berikutnya
                    </button>
                </div>
            </div>

            
            <div x-show="tab === 'iuran'" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-800">Matriks Iuran <span x-text="matriks.tahun"></span></h3>
                        <p class="text-sm text-gray-500">Klik sel untuk memverifikasi atau membatalkan iuran.</p>
                    </div>
                    <div x-show="bisaKelola" class="flex flex-wrap items-end gap-2">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-gray-500">Bulan tagihan</span>
                            <input type="month" x-model="generate.periode" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-gray-500">Nominal</span>
                            <input type="number" min="1" x-model.number="generate.nominal" class="w-32 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </label>
                        <button @click="generateIuran()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                            Buat tagihan
                        </button>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-[980px] border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr>
                                <th class="sticky left-0 z-10 border-b border-gray-100 bg-white px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Siswa</th>
                                <template x-for="(label, bulan) in matriks.bulan" :key="bulan">
                                    <th class="border-b border-gray-100 px-1.5 py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="label"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in matriks.siswa" :key="row.id_siswa">
                                <tr>
                                    <td class="sticky left-0 z-10 border-b border-gray-50 bg-white px-3 py-2">
                                        <p class="font-medium text-gray-800" x-text="row.nama"></p>
                                        <p class="text-xs text-gray-400" x-text="row.nis"></p>
                                    </td>
                                    <template x-for="(label, bulan) in matriks.bulan" :key="bulan">
                                        <td class="border-b border-gray-50 p-1 text-center">
                                            <button @click="toggleIuran(row, bulan)"
                                                    :disabled="!bisaKelola"
                                                    :title="row.iuran[bulan]?.catatan ?? ''"
                                                    :class="chipClass(row.iuran[bulan]?.status)"
                                                    class="w-full min-w-[3rem] rounded-md border px-1 py-1.5 text-[10px] font-medium transition hover:opacity-80 disabled:cursor-default">
                                                <span x-text="statusLabel(row.iuran[bulan]?.status)"></span>
                                            </button>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                            <tr x-show="matriks.siswa.length === 0">
                                <td :colspan="Object.keys(matriks.bulan).length + 1" class="px-4 py-8 text-center text-sm text-gray-400">
                                    Belum ada data anggota kelas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-gray-200"></span> Belum bayar</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span> Menunggu</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Lunas</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span> Ditolak</span>
                </div>
            </div>
        </div>

        <?php echo $__env->make('kas._modal-transaksi', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div x-show="toast" x-transition x-cloak
             class="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg"
             :class="toast?.tipe === 'error' ? 'bg-rose-600' : 'bg-gray-900'">
            <span x-text="toast?.pesan"></span>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4619374cef299e94fd7263111d0abc69)): ?>
<?php $attributes = $__attributesOriginal4619374cef299e94fd7263111d0abc69; ?>
<?php unset($__attributesOriginal4619374cef299e94fd7263111d0abc69); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4619374cef299e94fd7263111d0abc69)): ?>
<?php $component = $__componentOriginal4619374cef299e94fd7263111d0abc69; ?>
<?php unset($__componentOriginal4619374cef299e94fd7263111d0abc69); ?>
<?php endif; ?>
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/kas/index.blade.php ENDPATH**/ ?>