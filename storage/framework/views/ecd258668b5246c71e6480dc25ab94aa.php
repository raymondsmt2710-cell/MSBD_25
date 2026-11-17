<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Kas <?php echo e($kelas->nama_kelas); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/app.css'); ?>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-page { box-shadow: none !important; margin: 0 !important; max-width: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 p-4 text-gray-800 sm:p-8">
    <div class="print-page mx-auto max-w-5xl rounded-xl bg-white p-6 shadow-sm sm:p-10">
        <div class="no-print mb-6 flex justify-end">
            <button onclick="window.print()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Cetak / Simpan PDF
            </button>
        </div>

        <header class="border-b-2 border-gray-800 pb-4 text-center">
            <h1 class="text-xl font-bold uppercase tracking-wide">Sistem Informasi Sekolah</h1>
            <p class="mt-1 text-sm text-gray-600">Laporan Keuangan Kas Kelas</p>
            <h2 class="mt-2 text-lg font-semibold"><?php echo e($kelas->nama_kelas); ?></h2>
            <p class="text-xs text-gray-500">
                Periode:
                <?php if($filter['dari'] || $filter['sampai']): ?>
                    <?php echo e($filter['dari'] ? \Illuminate\Support\Carbon::parse($filter['dari'])->format('d/m/Y') : 'Awal'); ?>

                    s.d.
                    <?php echo e($filter['sampai'] ? \Illuminate\Support\Carbon::parse($filter['sampai'])->format('d/m/Y') : 'Sekarang'); ?>

                <?php else: ?>
                    Semua periode
                <?php endif; ?>
            </p>
        </header>

        <section class="mt-6 grid grid-cols-3 gap-4 text-center">
            <div class="rounded-lg border border-gray-200 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Saldo Akhir</p>
                <p class="mt-1 text-lg font-bold text-indigo-700">Rp <?php echo e(number_format($ringkasan['saldo'], 0, ',', '.')); ?></p>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Total Pemasukan</p>
                <p class="mt-1 text-lg font-bold text-emerald-700">Rp <?php echo e(number_format($ringkasan['total_pemasukan'], 0, ',', '.')); ?></p>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Total Pengeluaran</p>
                <p class="mt-1 text-lg font-bold text-rose-700">Rp <?php echo e(number_format($ringkasan['total_pengeluaran'], 0, ',', '.')); ?></p>
            </div>
        </section>

        <section class="mt-6 overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-600">
                        <th class="border border-gray-200 px-3 py-2">No</th>
                        <th class="border border-gray-200 px-3 py-2">Tanggal</th>
                        <th class="border border-gray-200 px-3 py-2">Jenis</th>
                        <th class="border border-gray-200 px-3 py-2">Kategori</th>
                        <th class="border border-gray-200 px-3 py-2 text-right">Nominal</th>
                        <th class="border border-gray-200 px-3 py-2">Keterangan</th>
                        <th class="border border-gray-200 px-3 py-2">Siswa</th>
                        <th class="border border-gray-200 px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transaksi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="<?php echo e($item->is_batal ? 'bg-rose-50 text-gray-400' : ''); ?>">
                            <td class="border border-gray-200 px-3 py-2"><?php echo e($index + 1); ?></td>
                            <td class="border border-gray-200 px-3 py-2"><?php echo e($item->tanggal?->format('d/m/Y')); ?></td>
                            <td class="border border-gray-200 px-3 py-2"><?php echo e($item->jenis?->label()); ?></td>
                            <td class="border border-gray-200 px-3 py-2"><?php echo e($item->kategori); ?></td>
                            <td class="border border-gray-200 px-3 py-2 text-right">Rp <?php echo e(number_format((float) $item->nominal, 0, ',', '.')); ?></td>
                            <td class="border border-gray-200 px-3 py-2"><?php echo e($item->keterangan); ?></td>
                            <td class="border border-gray-200 px-3 py-2"><?php echo e($item->siswa?->nama_siswa ?? '-'); ?></td>
                            <td class="border border-gray-200 px-3 py-2"><?php echo e($item->is_batal ? 'Dibatalkan' : 'Aktif'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="border border-gray-200 px-3 py-6 text-center text-gray-400">
                                Belum ada transaksi pada periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-semibold">
                        <td colspan="4" class="border border-gray-200 px-3 py-2 text-right">Saldo Akhir</td>
                        <td class="border border-gray-200 px-3 py-2 text-right">Rp <?php echo e(number_format($ringkasan['saldo'], 0, ',', '.')); ?></td>
                        <td colspan="3" class="border border-gray-200 px-3 py-2"></td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <section class="mt-10 flex justify-between text-sm">
            <div class="text-gray-500">
                <p>Dicetak: <?php echo e(now()->format('d/m/Y H:i')); ?></p>
                <p>Oleh: <?php echo e(auth()->user()->username); ?></p>
            </div>
            <div class="text-center">
                <p>Bendahara Kelas</p>
                <div class="mt-16 w-48 border-t border-gray-400 pt-1 text-xs text-gray-500">Nama &amp; tanda tangan</div>
            </div>
        </section>
    </div>
</body>
</html>
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/kas/cetak.blade.php ENDPATH**/ ?>