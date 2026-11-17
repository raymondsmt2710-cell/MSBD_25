<?php $__env->startSection('judul', 'Roster Presensi'); ?>
<?php $__env->startSection('isi'); ?>
<div class="card">
    <h2>Roster Presensi</h2>
    <p style="font-size:13px;color:#6b7280;margin-top:0">
        Daftar jadwal mengajar Anda. Tombol <strong>Buat Presensi</strong> hanya aktif pada hari yang sesuai jadwal.
        Setelah dibuat, satu QR berlaku sampai jam selesai.
    </p>
    <table>
        <tr>
            <th>Kelas</th><th>Hari</th><th>Jam</th><th>Mata Pelajaran</th><th>Aksi</th>
        </tr>
        <?php $__empty_1 = true; $__currentLoopData = $roster; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr <?php if($r->hari === $hariIni): ?> style="background:#eff6ff" <?php endif; ?>>
                <td><?php echo e($r->nama_kelas); ?></td>
                <td><?php echo e(ucfirst($r->hari)); ?><?php if($r->hari === $hariIni): ?> <span class="badge hadir">hari ini</span><?php endif; ?></td>
                <td><?php echo e(substr($r->jam_mulai, 0, 5)); ?> - <?php echo e(substr($r->jam_selesai, 0, 5)); ?></td>
                <td><?php echo e($r->nama_mapel); ?></td>
                <td>
                    <?php if($r->id_sesi): ?>
                        <a class="btn" href="<?php echo e(route('guru.presensi.sesi', $r->id_sesi)); ?>">Lihat Sesi</a>
                    <?php elseif($r->hari === $hariIni): ?>
                        <form method="POST" action="<?php echo e(route('guru.presensi.buka', $r->id_jadwal)); ?>"><?php echo csrf_field(); ?>
                            <button class="btn" type="submit">Buat Presensi</button>
                        </form>
                    <?php else: ?>
                        <span style="color:#9ca3af;font-size:13px">Bukan hari jadwal</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="5">Belum ada jadwal mengajar.</td></tr>
        <?php endif; ?>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\devid\MSBD_25\resources\views/guru/presensi/index.blade.php ENDPATH**/ ?>