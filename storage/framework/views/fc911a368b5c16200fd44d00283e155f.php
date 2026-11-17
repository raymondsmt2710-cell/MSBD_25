<?php $__env->startSection('judul', 'Kelola Pemilihan'); ?>
<?php $__env->startSection('isi'); ?>
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <h1>Kelola Pemilihan</h1>
        <p class="subjudul">Buat pemilihan, atur kandidat, daftarkan pemilih, lalu buka pemungutan suara.</p>
    </div>
    <a class="btn" href="<?php echo e(route('kelola.pemilihan.form')); ?>">+ Buat Pemilihan</a>
</div>

<div class="kartu">
    <?php if($pemilihan->isEmpty()): ?>
        <div class="kosong">Belum ada pemilihan. Mulai dengan menekan "Buat Pemilihan".</div>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Judul</th>
                <th>Organisasi</th>
                <th>Tahun Ajaran</th>
                <th>Status</th>
                <th>Waktu</th>
                <th>Kandidat</th>
                <th>Pemilih</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $pemilihan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><strong><?php echo e($p->judul); ?></strong></td>
                    <td><?php echo e($p->nama_organisasi); ?></td>
                    <td><?php echo e($p->tahun_ajaran); ?></td>
                    <td><span class="badge <?php echo e($p->status); ?>"><?php echo e($p->status); ?></span></td>
                    <td class="kecil">
                        <?php echo e(\Illuminate\Support\Carbon::parse($p->waktu_mulai)->format('d M Y H:i')); ?><br>
                        s/d <?php echo e(\Illuminate\Support\Carbon::parse($p->waktu_selesai)->format('d M Y H:i')); ?>

                    </td>
                    <td><?php echo e($p->jumlah_kandidat); ?></td>
                    <td><?php echo e($p->jumlah_pemilih); ?></td>
                    <td>
                        <div class="aksi-baris">
                            <a class="btn garis" href="<?php echo e(route('kelola.pemilihan.monitor', $p->id_pemilihan)); ?>">Monitor</a>
                            <a class="btn garis" href="<?php echo e(route('kelola.pemilihan.kandidat', $p->id_pemilihan)); ?>">Kandidat</a>
                            <a class="btn garis" href="<?php echo e(route('kelola.pemilihan.pemilih', $p->id_pemilihan)); ?>">Pemilih</a>
                            <?php if($p->status === 'draf'): ?>
                                <a class="btn garis" href="<?php echo e(route('kelola.pemilihan.ubah', $p->id_pemilihan)); ?>">Ubah</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <div class="paginasi"><?php echo e($pemilihan->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.pemilihan', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\devid\MSBD_25\resources\views/kelola/pemilihan/index.blade.php ENDPATH**/ ?>