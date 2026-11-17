<?php $__env->startSection('judul', 'Pemilihan'); ?>
<?php $__env->startSection('isi'); ?>
<h1>Pemilihan</h1>
<p class="subjudul">Pemilihan yang Anda ikuti. Suara Anda rahasia dan tidak dapat diubah setelah dikirim.</p>

<?php $__empty_1 = true; $__currentLoopData = $pemilihan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $bagian = $partisipasi[$p->id_pemilihan] ?? null;
        $bisaMemilih = \App\Models\Pemilihan::bolehMemilih($p->id_pemilihan, auth()->user()->siswa->id_siswa);
    ?>
    <div class="kartu">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
                <h2 style="margin-bottom:6px"><?php echo e($p->judul); ?></h2>
                <div class="kecil" style="margin-bottom:8px">
                    <?php echo e($p->nama_organisasi); ?> ·
                    <?php echo e(\Illuminate\Support\Carbon::parse($p->waktu_mulai)->format('d M Y H:i')); ?> s/d
                    <?php echo e(\Illuminate\Support\Carbon::parse($p->waktu_selesai)->format('d M Y H:i')); ?>

                </div>
                <span class="badge <?php echo e($p->status); ?>"><?php echo e($p->status); ?></span>
                <?php if($p->sudah_memilih): ?>
                    <span class="badge berlangsung">✓ Sudah memilih</span>
                <?php endif; ?>
            </div>
            <div class="aksi-baris">
                <?php if($bisaMemilih): ?>
                    <a class="btn" href="<?php echo e(route('siswa.pemilihan.pilih', $p->id_pemilihan)); ?>">Pilih Sekarang</a>
                <?php elseif($p->status === 'selesai'): ?>
                    <a class="btn oranye" href="<?php echo e(route('siswa.pemilihan.hasil', $p->id_pemilihan)); ?>">Lihat Hasil</a>
                <?php elseif($p->sudah_memilih): ?>
                    <span class="pill">Terima kasih, suara Anda sudah tercatat</span>
                <?php elseif($p->status === 'draf'): ?>
                    <span class="pill">Menunggu dibuka panitia</span>
                <?php else: ?>
                    <span class="pill">Pemungutan suara sudah berakhir</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if($bagian): ?>
            <div style="margin-top:16px">
                <div class="meta" style="margin-bottom:6px">
                    <span>Partisipasi pemilih</span>
                    <span><strong><?php echo e($bagian->sudah_memilih); ?></strong> dari <?php echo e($bagian->total_pemilih); ?>

                        (<?php echo e(number_format((float) $bagian->persentase, 1)); ?>%)</span>
                </div>
                <div class="bar"><span style="width:<?php echo e((float) $bagian->persentase); ?>%"></span></div>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="kartu">
        <div class="kosong">Belum ada pemilihan yang Anda ikuti.</div>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.pemilihan', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\devid\MSBD_25\resources\views/siswa/pemilihan/index.blade.php ENDPATH**/ ?>