<?php $__env->startSection('judul', 'Portal Orang Tua'); ?>
<?php $__env->startSection('isi'); ?>
<h1>Portal Pemantauan Orang Tua/Wali</h1>
<p class="subjudul">
    Selamat datang, <strong><?php echo e($orangTua->nama ?? auth()->user()->username); ?></strong>.
    Halaman ini hanya untuk melihat; tidak ada data yang bisa diubah dari sini.
</p>

<?php if($anak->isEmpty()): ?>
    <div class="kartu">
        <div class="kosong">
            Belum ada data anak yang terhubung ke akun ini. Hubungi administrator sekolah.
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-2">
        <?php $__currentLoopData = $anak; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="kartu">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div>
                        <h2 style="margin-bottom:6px"><?php echo e($a->nama_siswa); ?></h2>
                        <div class="kecil">
                            NIS <?php echo e($a->nis); ?> &middot;
                            Kelas <?php echo e($a->nama_kelas ?? 'belum terdaftar'); ?>

                        </div>
                        <div style="margin-top:8px">
                            <span class="pill">
                                <?php echo e(ucfirst($a->hubungan)); ?><?php echo e($a->is_kontak_utama ? ' · kontak utama' : ''); ?>

                            </span>
                        </div>
                    </div>
                </div>

                <div class="meta" style="margin-top:16px">
                    <div class="stat">
                        <span class="angka"><?php echo e(number_format($a->ringkasan['persen'], 2)); ?>%</span>
                        <span class="label">Kehadiran</span>
                    </div>
                </div>

                <div class="bar" style="margin-top:10px">
                    <span style="width:<?php echo e(min(100, max(0, (float) $a->ringkasan['persen']))); ?>%"></span>
                </div>

                <p style="margin:12px 0 0;font-size:13px">
                    Hadir <?php echo e($a->ringkasan['hadir']); ?> &middot;
                    Izin <?php echo e($a->ringkasan['izin']); ?> &middot;
                    Sakit <?php echo e($a->ringkasan['sakit']); ?> &middot;
                    Alpa <?php echo e($a->ringkasan['alpa']); ?>

                </p>

                <div style="margin-top:14px">
                    <a class="btn" href="<?php echo e(route('ortu.anak', $a->id_siswa)); ?>">Lihat detail</a>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.pemilihan', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\devid\MSBD_25\resources\views/ortu/index.blade.php ENDPATH**/ ?>