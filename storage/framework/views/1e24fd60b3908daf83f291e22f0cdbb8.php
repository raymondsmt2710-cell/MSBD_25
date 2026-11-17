<?php $__env->startSection('judul', 'Roster Presensi'); ?>
<?php $__env->startSection('isi'); ?>
<div class="card">
    <h2>Ringkasan Kehadiran</h2>
    <p style="font-size:28px;margin:4px 0"><?php echo e(number_format($ringkasan['persen'], 2)); ?>%</p>
    <p>Hadir <?php echo e($ringkasan['hadir']); ?> &middot; Izin <?php echo e($ringkasan['izin']); ?> &middot; Sakit <?php echo e($ringkasan['sakit']); ?> &middot; Alpa <?php echo e($ringkasan['alpa']); ?></p>
    <p style="font-size:13px;color:#6b7280">
        Untuk presensi, pindai QR di layar guru dengan kamera ponsel (pastikan sudah masuk ke akun ini).
    </p>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h3 style="margin:0">Roster Presensi</h3>
        <div style="display:flex;gap:8px;align-items:center">
            <a class="btn abu" href="<?php echo e(route('siswa.presensi.riwayat', ['pekan' => $pekanSebelum])); ?>">&lsaquo; Pekan lalu</a>
            <span style="font-size:13px;color:#374151">
                <?php echo e($pekan->copy()->locale('id')->isoFormat('D MMM')); ?> - <?php echo e($pekan->copy()->addDays(5)->locale('id')->isoFormat('D MMM YYYY')); ?>

            </span>
            <a class="btn abu" href="<?php echo e(route('siswa.presensi.riwayat', ['pekan' => $pekanBerikut])); ?>">Pekan depan &rsaquo;</a>
        </div>
    </div>

    <table style="margin-top:12px">
        <tr><th>Hari</th><th>Tanggal</th><th>Jam</th><th>Mata Pelajaran</th><th>Guru</th><th>Status</th></tr>
        <?php $__empty_1 = true; $__currentLoopData = $roster; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e(ucfirst($r->hari)); ?></td>
                <td><?php echo e($r->tanggal->locale('id')->isoFormat('D MMM')); ?></td>
                <td><?php echo e(substr($r->jam_mulai, 0, 5)); ?> - <?php echo e(substr($r->jam_selesai, 0, 5)); ?></td>
                <td><?php echo e($r->nama_mapel); ?></td>
                <td><?php echo e($r->nama_guru); ?></td>
                <td>
                    <?php if($r->status === '-'): ?>
                        <span style="color:#9ca3af">-</span>
                    <?php elseif($r->status === 'belum'): ?>
                        <span class="badge">belum</span>
                    <?php else: ?>
                        <span class="badge <?php echo e($r->status); ?>"><?php echo e($r->status); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6">Belum ada jadwal untuk kelas Anda.</td></tr>
        <?php endif; ?>
    </table>
    <p style="font-size:12px;color:#9ca3af;margin-bottom:0">
        Tanda <strong>-</strong> berarti guru belum membuka sesi pada tanggal tersebut.
        Status <strong>alpa</strong> diberikan otomatis saat guru menutup sesi.
    </p>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\devid\MSBD_25\resources\views/siswa/presensi/riwayat.blade.php ENDPATH**/ ?>