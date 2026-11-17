<?php if (isset($component)) { $__componentOriginal4619374cef299e94fd7263111d0abc69 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4619374cef299e94fd7263111d0abc69 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-layout','data' => ['judul' => 'Jadwal Mengajar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['judul' => 'Jadwal Mengajar']); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="label-kapital">Presensi</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-sekolah-ink">Jadwal Mengajar</h1>
                <p class="mt-1 text-sm text-gray-500">Buka sesi presensi untuk kelas yang Anda ampu hari ini.</p>
            </div>
            <?php
                $namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][now()->dayOfWeek] ?? 'Senin';
            ?>
            <span class="inline-flex items-center gap-2 rounded-xl bg-sekolah-teal/10 px-3 py-2 text-sm font-semibold text-sekolah-teal">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                </svg>
                <?php echo e($namaHari); ?>, <?php echo e(now()->format('d/m/Y')); ?>

            </span>
        </div>
     <?php $__env->endSlot(); ?>

    <?php if(!empty($peringatan)): ?>
        <div class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.2 14.2A1 1 0 003 19.5h18a1 1 0 00.87-1.5l-8.2-14.2a1 1 0 00-1.74 0z"/>
            </svg>
            <p><?php echo e($peringatan); ?></p>
        </div>
    <?php endif; ?>

    <div class="kartu overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50/80">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">Hari</th>
                        <th class="px-4 py-3">Jam</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3">Mata Pelajaran</th>
                        <th class="px-4 py-3">Sesi Hari Ini</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $jadwal; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="<?php echo e($j->hari === $hariIni ? 'bg-sekolah-teal/[0.04]' : 'bg-white'); ?>">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?php echo e(ucfirst($j->hari)); ?>

                                <?php if($j->hari === $hariIni): ?>
                                    <span class="ml-2 rounded-full bg-sekolah-teal/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sekolah-teal">hari ini</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?php echo e(substr($j->jam_mulai, 0, 5)); ?>–<?php echo e(substr($j->jam_selesai, 0, 5)); ?></td>
                            <td class="px-4 py-3 font-medium text-gray-800"><?php echo e($j->nama_kelas); ?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo e($j->nama_mapel); ?></td>
                            <td class="px-4 py-3">
                                <?php if($j->id_sesi): ?>
                                    <a href="<?php echo e(route('guru.presensi.sesi', $j->id_sesi)); ?>"
                                       class="inline-flex items-center gap-2 rounded-lg bg-sekolah-teal/10 px-3 py-1.5 text-xs font-semibold text-sekolah-teal hover:bg-sekolah-teal/15">
                                        Buka sesi
                                        <span class="rounded-full bg-white px-2 py-0.5 text-[10px] uppercase tracking-wide"><?php echo e($j->status_sesi); ?></span>
                                    </a>
                                <?php elseif($j->hari === $hariIni): ?>
                                    <form method="POST" action="<?php echo e(route('guru.presensi.buka', $j->id_jadwal)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="tombol-utama px-3 py-1.5 text-xs">Mulai Presensi</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-300">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-sekolah-teal/10 text-sekolah-teal">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                                    </svg>
                                </div>
                                <p class="mt-3 font-semibold text-gray-700">Belum ada jadwal.</p>
                                <p class="text-sm text-gray-500">Jadwal yang ditugaskan kepada Anda akan tampil di sini.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/guru/presensi/index.blade.php ENDPATH**/ ?>