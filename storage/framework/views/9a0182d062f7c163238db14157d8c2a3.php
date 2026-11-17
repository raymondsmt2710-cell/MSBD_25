<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['judul' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['judul' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $pengguna = auth()->user();
    $peran = $pengguna?->role;

    $navigasi = match ($peran?->value) {
        'admin' => [
            ['label' => 'Beranda', 'route' => 'admin.dashboard'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index'],
            ['label' => 'Denah', 'route' => 'denah.index'],
        ],
        'guru' => [
            ['label' => 'Beranda', 'route' => 'guru.dashboard'],
            ['label' => 'Jadwal Presensi', 'route' => 'guru.presensi.index'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index'],
            ['label' => 'Denah', 'route' => 'denah.index'],
        ],
        'siswa' => [
            ['label' => 'Beranda', 'route' => 'siswa.dashboard'],
            ['label' => 'Riwayat Presensi', 'route' => 'siswa.presensi.riwayat'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index'],
            ['label' => 'Denah', 'route' => 'denah.index'],
        ],
        'orang_tua' => [
            ['label' => 'Beranda', 'route' => 'ortu.dashboard'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index'],
            ['label' => 'Denah', 'route' => 'denah.index'],
        ],
        default => [],
    };
?>

<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e($judul ? $judul.' — ' : ''); ?>E-Learning SMAN 1 Kabanjahe</title>

    <link rel="icon" href="<?php echo e(asset('images/logo-sekolah.png')); ?>" type="image/png">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-gray-800 antialiased" x-data="{ menuTerbuka: false }">
    <header class="sticky top-0 z-40 border-b border-gray-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo e($peran ? route($peran->home()) : route('beranda')); ?>" class="flex items-center gap-3">
                <img src="<?php echo e(asset('images/logo-sekolah.png')); ?>" alt="Logo SMAN 1 Kabanjahe"
                     class="h-11 w-11 rounded-xl object-contain ring-1 ring-gray-100">
                <span class="leading-tight">
                    <span class="block text-sm font-extrabold tracking-tight text-sekolah-ink">E-Learning</span>
                    <span class="block text-[11px] font-medium text-gray-500">SMA Negeri 1 Kabanjahe</span>
                </span>
            </a>

            <?php if($peran): ?>
                <nav class="hidden items-center gap-1 lg:flex">
                    <?php $__currentLoopData = $navigasi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route($item['route'])); ?>"
                           class="tautan-nav <?php echo e(request()->routeIs($item['route']) ? 'tautan-nav-aktif' : ''); ?>">
                            <?php echo e($item['label']); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </nav>
            <?php endif; ?>

            <div class="flex items-center gap-2">
                <?php if($pengguna): ?>
                    <div class="hidden text-right sm:block">
                        <span class="block text-sm font-semibold text-gray-800"><?php echo e($pengguna->username); ?></span>
                        <span class="block text-[11px] font-medium uppercase tracking-wider text-sekolah-teal">
                            <?php echo e($peran?->label()); ?>

                        </span>
                    </div>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" class="hidden sm:block">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="tombol-garis px-3 py-2">Keluar</button>
                    </form>
                <?php endif; ?>

                <button type="button" @click="menuTerbuka = ! menuTerbuka"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-600 lg:hidden"
                        aria-label="Buka menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <?php if($peran): ?>
            <div x-show="menuTerbuka" x-cloak x-transition class="border-t border-gray-100 bg-white px-4 py-3 lg:hidden">
                <div class="flex flex-col gap-1">
                    <?php $__currentLoopData = $navigasi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route($item['route'])); ?>"
                           class="tautan-nav <?php echo e(request()->routeIs($item['route']) ? 'tautan-nav-aktif' : ''); ?>">
                            <?php echo e($item['label']); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                    <span class="text-sm text-gray-500"><?php echo e($pengguna->username); ?> · <?php echo e($peran->label()); ?></span>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="tombol-garis px-3 py-2">Keluar</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <?php if(isset($header)): ?>
        <div class="border-b border-gray-100 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                <?php echo e($header); ?>

            </div>
        </div>
    <?php endif; ?>

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <?php if(session('success')): ?>
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span><?php echo e(session('success')); ?></span>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.2 14.2A1 1 0 003 19.5h18a1 1 0 00.87-1.5l-8.2-14.2a1 1 0 00-1.74 0z"/>
                </svg>
                <span><?php echo e(session('error')); ?></span>
            </div>
        <?php endif; ?>

        <?php echo e($slot); ?>

    </main>

    <footer class="border-t border-gray-100 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <span>© <?php echo e(date('Y')); ?> E-Learning SMA Negeri 1 Kabanjahe</span>
            <span>Manajemen Sistem Basis Data · Universitas Sumatera Utara</span>
        </div>
    </footer>
</body>
</html>
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/components/app-layout.blade.php ENDPATH**/ ?>