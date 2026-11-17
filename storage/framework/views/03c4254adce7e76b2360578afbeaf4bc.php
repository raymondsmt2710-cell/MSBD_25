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
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Denah Interaktif</h2>
                <p class="text-sm text-gray-500">Tempat duduk kelas dan peta area sekolah</p>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-6" x-data="denahPage(<?php echo \Illuminate\Support\Js::from($initial)->toHtml() ?>)" x-cloak>
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
                </div>

                <div class="flex gap-2 rounded-xl bg-gray-100 p-1">
                    <button @click="tab = 'kursi'"
                            :class="tab === 'kursi' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition">
                        Tempat Duduk
                    </button>
                    <button @click="tab = 'sekolah'"
                            :class="tab === 'sekolah' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="rounded-lg px-4 py-2 text-sm font-medium transition">
                        Area Sekolah
                    </button>
                </div>
            </div>

            
            <section x-show="tab === 'kursi'" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative min-w-[14rem] flex-1 sm:max-w-xs">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                        </svg>
                        <input type="search" x-model.debounce.300ms="cariSiswa" placeholder="Cari nama siswa..."
                               class="w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <button x-show="bisaKelolaKursi" @click="modeEdit = !modeEdit"
                            :class="modeEdit ? 'bg-amber-500 text-white hover:bg-amber-600' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                            class="rounded-lg px-4 py-2 text-sm font-medium shadow-sm">
                        <span x-text="modeEdit ? 'Selesai Edit' : 'Mode Edit'"></span>
                    </button>
                    <button x-show="bisaKelolaKursi" @click="bukaPengaturan()"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Atur Denah
                    </button>
                    <button x-show="bisaKelolaKursi" @click="acak()"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                        Acak Posisi
                    </button>
                </div>

                <div x-show="modeEdit" class="mt-3 rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-3">
                    <p class="text-xs font-medium text-amber-800">
                        Klik dua kursi untuk menukar. Klik kursi lalu klik kursi kosong untuk memindahkan.
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <label class="text-xs text-amber-800">Tempatkan siswa yang belum duduk:</label>
                        <select x-model="siswaAkanDitempatkan"
                                class="rounded-lg border-amber-300 bg-white text-xs shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="">Pilih siswa</option>
                            <template x-for="item in siswaBelumDuduk()" :key="item.id_siswa">
                                <option :value="item.id_siswa" x-text="item.nama_siswa"></option>
                            </template>
                        </select>
                        <span class="text-xs text-amber-700">lalu klik kursi kosong.</span>
                    </div>
                </div>

                
                <div class="mx-auto mt-6 max-w-4xl">
                    <div class="rounded-lg bg-slate-800 py-3 text-center text-xs font-semibold uppercase tracking-[0.3em] text-white sm:text-sm">
                        Papan Tulis
                    </div>

                    
                    <div class="mt-4 flex"
                         :class="{
                            'justify-start': denah?.posisi_meja_guru === 'depan_kiri',
                            'justify-center': denah?.posisi_meja_guru === 'depan_tengah',
                            'justify-end': denah?.posisi_meja_guru === 'depan_kanan',
                         }">
                        <div class="rounded-lg border border-amber-200 bg-amber-100 px-8 py-3 text-xs font-medium text-amber-800 shadow-sm">
                            Meja Guru
                        </div>
                    </div>

                    
                    <div class="mt-6 overflow-x-auto pb-2">
                        <div class="grid min-w-[38rem] gap-3"
                             :style="`grid-template-columns: repeat(${jumlahKolom()}, minmax(7rem, 1fr))`">
                            <template x-for="baris in jumlahBaris()" :key="baris">
                                <template x-for="kolom in jumlahKolom()" :key="`${baris}-${kolom}`">
                                    <button type="button"
                                            @click="klikKursi(kursiPada(baris, kolom), baris, kolom)"
                                            :class="{
                                                'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-300': sorotSiswaId && kursiPada(baris, kolom)?.siswa?.id_siswa === sorotSiswaId,
                                                'border-amber-400 bg-amber-50 ring-2 ring-amber-300': kursiSwapPertama?.id_posisi && kursiSwapPertama?.id_posisi === kursiPada(baris, kolom)?.id_posisi,
                                                'border-dashed border-gray-300 bg-gray-50 text-gray-300': !kursiPada(baris, kolom),
                                                'border-gray-200 bg-white hover:border-indigo-300': kursiPada(baris, kolom)?.siswa,
                                            }"
                                            class="min-h-[4.75rem] rounded-xl border p-2 text-left text-xs shadow-sm transition">
                                        <template x-if="kursiPada(baris, kolom)?.siswa">
                                            <div>
                                                <p class="line-clamp-2 font-semibold text-gray-800" x-text="kursiPada(baris, kolom).siswa.nama_siswa"></p>
                                                <p class="mt-1 text-[10px] text-gray-400" x-text="kursiPada(baris, kolom).siswa.nis"></p>
                                                <p x-show="kursiPada(baris, kolom).siswa.jabatan"
                                                   class="mt-1 inline-block rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-600"
                                                   x-text="kursiPada(baris, kolom).siswa.jabatan"></p>
                                            </div>
                                        </template>
                                        <template x-if="!kursiPada(baris, kolom)?.siswa">
                                            <span class="flex h-full items-center justify-center text-[11px]">Kosong</span>
                                        </template>
                                    </button>
                                </template>
                            </template>
                        </div>
                    </div>

                    
                    <div class="mt-2 flex"
                         :class="{
                            'justify-start': denah?.posisi_pintu === 'kiri',
                            'justify-end': denah?.posisi_pintu === 'kanan',
                            'justify-center': denah?.posisi_pintu === 'belakang',
                         }">
                        <span class="rounded bg-gray-800 px-4 py-1 text-[10px] font-semibold uppercase tracking-wider text-white">Pintu</span>
                    </div>
                </div>

                <div x-show="!denah" class="mt-4 rounded-lg bg-gray-50 p-4 text-center text-sm text-gray-500">
                    Denah belum dibuat. Klik <span class="font-medium text-gray-700">Atur Denah</span> untuk mulai.
                </div>
            </section>

            
            <section x-show="tab === 'sekolah'" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <div class="flex flex-wrap items-center gap-2">
                    <select x-model="gedung" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <template x-for="item in gedungTersedia()" :key="item">
                            <option :value="item" x-text="item"></option>
                        </template>
                    </select>
                    <select x-model.number="lantai" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <template x-for="item in lantaiTersedia()" :key="item">
                            <option :value="item" x-text="`Lantai ${item}`"></option>
                        </template>
                    </select>

                    <div class="relative min-w-[13rem] flex-1 sm:max-w-xs">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                        </svg>
                        <input type="search" x-model.debounce.300ms="cariRuangan" placeholder="Cari ruangan..."
                               class="w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <button x-show="bisaKelolaRuangan" @click="bukaRuangan()"
                            class="ml-auto rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                        + Ruangan
                    </button>
                </div>

                <div class="mt-4 overflow-auto rounded-xl border border-gray-200 bg-gray-50"
                     style="background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 34px 34px;">
                    <div class="relative h-[560px] w-[1280px]">
                        <template x-for="ruangan in ruanganTampil()" :key="ruangan.id_ruangan">
                            <button type="button"
                                    @click="klikRuangan(ruangan)"
                                    :style="gayaRuangan(ruangan)"
                                    :class="[
                                        kelasRuangan(ruangan),
                                        ruanganTerpilih?.id_ruangan === ruangan.id_ruangan ? 'ring-2 ring-indigo-500 ring-offset-1' : '',
                                    ]"
                                    class="absolute flex flex-col justify-between overflow-hidden rounded-lg border p-2 text-left text-xs shadow-sm transition hover:shadow-md">
                                <span class="line-clamp-2 font-semibold leading-tight" x-text="ruangan.nama_ruangan"></span>
                                <span class="mt-1 flex items-center justify-between gap-1 text-[10px] opacity-70">
                                    <span x-text="ruangan.kode_ruangan"></span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                              :class="ruangan.status === 'tersedia' ? 'bg-emerald-500' : (ruangan.status === 'maintenance' ? 'bg-amber-500' : 'bg-gray-400')"></span>
                                        <span x-text="ruangan.status"></span>
                                    </span>
                                </span>
                            </button>
                        </template>

                        <div x-show="ruanganTampil().length === 0" class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">
                            Tidak ada ruangan pada lantai ini.
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Tersedia</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> Maintenance</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-gray-400"></span> Tutup</span>
                </div>
            </section>
        </div>

        <?php echo $__env->make('denah._drawer-siswa', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('denah._drawer-ruangan', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('denah._modal-ruangan', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div x-show="pengaturanBuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="pengaturanBuka = false">
            <div @click.outside="pengaturanBuka = false" class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
                <h3 class="text-lg font-semibold text-gray-800">Atur Denah Kelas</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-gray-700">Jumlah baris</span>
                        <input type="number" min="1" max="12" x-model.number="pengaturan.jumlah_baris" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-gray-700">Jumlah kolom</span>
                        <input type="number" min="1" max="10" x-model.number="pengaturan.jumlah_kolom" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-gray-700">Meja guru</span>
                        <select x-model="pengaturan.posisi_meja_guru" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="depan_kiri">Depan kiri</option>
                            <option value="depan_tengah">Depan tengah</option>
                            <option value="depan_kanan">Depan kanan</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-gray-700">Pintu</span>
                        <select x-model="pengaturan.posisi_pintu" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="kiri">Kiri</option>
                            <option value="kanan">Kanan</option>
                            <option value="belakang">Belakang</option>
                        </select>
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="pengaturanBuka = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                    <button @click="simpanDenah()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
                </div>
            </div>
        </div>

        
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
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/denah/index.blade.php ENDPATH**/ ?>