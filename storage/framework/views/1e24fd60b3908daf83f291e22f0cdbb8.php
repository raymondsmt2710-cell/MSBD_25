<?php if (isset($component)) { $__componentOriginal4619374cef299e94fd7263111d0abc69 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4619374cef299e94fd7263111d0abc69 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-layout','data' => ['judul' => 'Riwayat Presensi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['judul' => 'Riwayat Presensi']); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="label-kapital">Presensi</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-sekolah-ink">Riwayat Presensi</h1>
                <p class="mt-1 text-sm text-gray-500">Rekap kehadiran Anda pada seluruh sesi pembelajaran.</p>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <?php
        $kartu = [
            ['label' => 'Persentase', 'nilai' => number_format($ringkasan['persen'] ?? 0, 2).'%', 'warna' => 'text-sekolah-teal', 'latar' => 'bg-sekolah-teal/10'],
            ['label' => 'Hadir', 'nilai' => $ringkasan['hadir'] ?? 0, 'warna' => 'text-emerald-700', 'latar' => 'bg-emerald-50'],
            ['label' => 'Izin / Sakit', 'nilai' => ($ringkasan['izin'] ?? 0) + ($ringkasan['sakit'] ?? 0), 'warna' => 'text-amber-700', 'latar' => 'bg-amber-50'],
            ['label' => 'Alpa', 'nilai' => $ringkasan['alpa'] ?? 0, 'warna' => 'text-rose-700', 'latar' => 'bg-rose-50'],
        ];
    ?>

    <?php if(!empty($peringatan)): ?>
        <div class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.2 14.2A1 1 0 003 19.5h18a1 1 0 00.87-1.5l-8.2-14.2a1 1 0 00-1.74 0z"/>
            </svg>
            <p><?php echo e($peringatan); ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <?php $__currentLoopData = $kartu ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="kartu p-5">
                <div class="flex items-center justify-between">
                    <span class="label-kapital"><?php echo e($item['label']); ?></span>
                    <span class="h-8 w-8 rounded-lg <?php echo e($item['latar']); ?>"></span>
                </div>
                <p class="mt-3 text-3xl font-extrabold tracking-tight <?php echo e($item['warna']); ?>"><?php echo e($item['nilai']); ?></p>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="mt-4 flex items-start gap-3 rounded-2xl border border-teal-100 bg-teal-50/60 px-4 py-3 text-sm text-sekolah-teal-deep">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v4m0 8v4m-4-8h8M4 4h16v16H4z"/>
        </svg>
        <p>Untuk presensi, pindai QR di layar guru dengan kamera ponsel (pastikan sudah masuk ke akun ini).</p>
    </div>

    <div class="kartu mt-5 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-800">Riwayat</h2>
            <span class="text-xs text-gray-500"><?php echo e($riwayat?->total() ?? 0); ?> catatan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50/80">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3">Jam</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $riwayat ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $warnaBadge = match ($r->status) {
                                'hadir' => 'bg-emerald-50 text-emerald-700',
                                'izin', 'sakit' => 'bg-amber-50 text-amber-700',
                                'alpa' => 'bg-rose-50 text-rose-700',
                                default => 'bg-gray-100 text-gray-500',
                            };
                        ?>
                        <tr class="bg-white">
                            <td class="px-4 py-3 text-gray-600"><?php echo e($r->tanggal); ?></td>
                            <td class="px-4 py-3 font-medium text-gray-800"><?php echo e($r->nama_mapel); ?></td>
                            <td class="px-4 py-3 text-gray-500"><?php echo e(substr($r->jam_mulai, 0, 5)); ?>–<?php echo e(substr($r->jam_selesai, 0, 5)); ?></td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize <?php echo e($warnaBadge); ?>"><?php echo e($r->status); ?></span></td>
                            <td class="px-4 py-3 text-gray-500"><?php echo e($r->metode ?? '—'); ?></td>
                            <td class="px-4 py-3 text-gray-500"><?php echo e($r->waktu_presensi ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <p class="font-semibold text-gray-700">Belum ada riwayat.</p>
                                <p class="text-sm text-gray-500">Catatan kehadiran akan muncul setelah Anda memindai QR presensi.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($riwayat?->hasPages()): ?>
            <div class="border-t border-gray-100 px-5 py-3">
                <?php echo e($riwayat->links()); ?>

            </div>
        <?php endif; ?>
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
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/siswa/presensi/riwayat.blade.php ENDPATH**/ ?>