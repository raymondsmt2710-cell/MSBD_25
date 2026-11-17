
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
    $peran = $pengguna?->role?->value ?? (string) $pengguna?->role;

    $navigasi = match ($peran) {
        'admin' => [
            ['label' => 'Pemilihan', 'route' => 'kelola.pemilihan.index', 'aktif' => 'kelola.pemilihan.*'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'aktif' => 'kas.*'],
            ['label' => 'Denah', 'route' => 'denah.index', 'aktif' => 'denah.*'],
        ],
        'guru' => [
            ['label' => 'Presensi', 'route' => 'guru.presensi.index', 'aktif' => 'guru.presensi.*'],
            ['label' => 'Pemilihan', 'route' => 'kelola.pemilihan.index', 'aktif' => 'kelola.pemilihan.*'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'aktif' => 'kas.*'],
            ['label' => 'Denah', 'route' => 'denah.index', 'aktif' => 'denah.*'],
        ],
        'siswa' => [
            ['label' => 'Presensi', 'route' => 'siswa.presensi.riwayat', 'aktif' => 'siswa.presensi.*'],
            ['label' => 'Pemilihan', 'route' => 'siswa.pemilihan.index', 'aktif' => 'siswa.pemilihan.*'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'aktif' => 'kas.*'],
            ['label' => 'Denah', 'route' => 'denah.index', 'aktif' => 'denah.*'],
        ],
        'orang_tua' => [
            ['label' => 'Portal Orang Tua', 'route' => 'ortu.index', 'aktif' => 'ortu.*'],
            ['label' => 'Kas Kelas', 'route' => 'kas.index', 'aktif' => 'kas.*'],
            ['label' => 'Denah', 'route' => 'denah.index', 'aktif' => 'denah.*'],
        ],
        default => [],
    };
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($judul ? $judul.' — ' : ''); ?>E-Learning SMAN 1 Kabanjahe</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Figtree,system-ui,-apple-system,sans-serif;background:#f6f7f8;color:#1f2937}
        a{color:inherit;text-decoration:none}
        .topbar{background:#09656F;color:#fff;padding:10px 16px;font-size:12px}
        .topbar-inner{max-width:1120px;margin:0 auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .dot{width:6px;height:6px;border-radius:999px;background:#C97A14;display:inline-block}
        header.nav{position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid #eef0f2;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .nav-inner{max-width:1120px;margin:0 auto;padding:10px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .brand{display:flex;align-items:center;gap:10px}
        .brand .logo{width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#043d44,#09656F);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px}
        .brand .nama{font-size:13px;font-weight:800;color:#09656F;line-height:1.1}
        .brand .sub{font-size:11px;color:#9ca3af;line-height:1.2}
        .nav-links{display:flex;align-items:center;gap:2px;flex-wrap:wrap}
        .nav-links a{padding:7px 11px;border-radius:10px;font-size:13px;font-weight:500;color:#4b5563}
        .nav-links a:hover{background:#f3f4f6;color:#09656F}
        .nav-links a.aktif{background:rgba(9,101,111,.06);color:#09656F}
        .nav-aksi{display:flex;align-items:center;gap:8px}
        main{max-width:1120px;margin:0 auto;padding:22px 16px 60px}
        h1{font-size:22px;margin:0 0 4px}
        .subjudul{font-size:13px;color:#6b7280;margin:0 0 18px}
        .kartu{background:#fff;border:1px solid #eef0f2;border-radius:16px;padding:18px;box-shadow:0 1px 3px rgba(16,24,40,.05);margin-bottom:16px}
        .kartu h2,.kartu h3{margin:0 0 12px;font-size:15px}
        .ok{background:#dcfce7;color:#166534;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .err{background:#fee2e2;color:#991b1b;padding:11px 14px;border-radius:12px;margin-bottom:14px;font-size:13px}
        .btn{display:inline-flex;align-items:center;gap:6px;background:#09656F;color:#fff;border:0;border-radius:11px;padding:9px 15px;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit}
        .btn:hover{background:#07535C}
        .btn.oranye{background:#C97A14}.btn.oranye:hover{background:#a86410}
        .btn.merah{background:#b91c1c}.btn.merah:hover{background:#991b1b}
        .btn.abu{background:#6b7280}.btn.abu:hover{background:#4b5563}
        .btn.garis{background:#fff;color:#09656F;border:1px solid rgba(9,101,111,.25)}
        .btn.garis:hover{background:rgba(9,101,111,.05)}
        .btn:disabled{opacity:.5;cursor:not-allowed}
        table{width:100%;border-collapse:collapse}
        th,td{padding:10px 8px;border-bottom:1px solid #f1f2f4;text-align:left;font-size:13px;vertical-align:middle}
        th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;font-weight:700}
        input,select,textarea{padding:9px 11px;border:1px solid #dfe2e6;border-radius:10px;font-size:13px;font-family:inherit;width:100%;background:#fff}
        input:focus,select:focus,textarea:focus{outline:none;border-color:#09656F;box-shadow:0 0 0 3px rgba(9,101,111,.12)}
        label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px}
        .badge{display:inline-block;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700}
        .badge.berlangsung{background:#dcfce7;color:#166534}
        .badge.selesai{background:#dbeafe;color:#1e40af}
        .badge.draf{background:#e5e7eb;color:#374151}
        .grid{display:grid;gap:14px;align-items:start}
        .grid-3{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
        .grid-2{grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}
        .kosong{text-align:center;padding:34px 16px;color:#9ca3af;font-size:13px}
        .pill{display:inline-flex;align-items:center;gap:5px;background:#f3f4f6;border-radius:999px;padding:4px 10px;font-size:11px;color:#4b5563;font-weight:600}
        .kecil{font-size:11px;color:#9ca3af}
        .bar{height:9px;background:#eef0f2;border-radius:999px;overflow:hidden}
        .bar span{display:block;height:100%;background:linear-gradient(90deg,#09656F,#0d8b96);border-radius:999px}
        .modal-latar{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:100}
        .modal-latar.buka{display:flex}
        .modal{background:#fff;border-radius:18px;max-width:460px;width:100%;padding:22px;box-shadow:0 24px 60px rgba(15,23,42,.35);max-height:90vh;overflow:auto}
        .modal h3{margin:0 0 8px;font-size:16px}
        .modal-aksi{display:flex;gap:9px;justify-content:flex-end}
        .paginasi{margin-top:14px}
        .paginasi nav{display:flex;gap:4px;flex-wrap:wrap}
        .paginasi a,.paginasi span{padding:6px 10px;border-radius:8px;font-size:12px;border:1px solid #e5e7eb;color:#4b5563;background:#fff}
        .paginasi span[aria-current]{background:#09656F;color:#fff;border-color:#09656F}
        .aksi-baris{display:flex;gap:6px;flex-wrap:wrap}
        .aksi-baris form{display:inline}
        .stat{display:flex;flex-direction:column;gap:2px}
        .stat .angka{font-size:20px;font-weight:800;color:#09656F;line-height:1.1}
        .stat .label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;font-weight:700}
        [x-cloak]{display:none !important}
        @media(max-width:640px){
            .nav-links{display:none}
            h1{font-size:19px}
            th,td{padding:8px 6px;font-size:12px}
        }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-inner">
        <span class="dot"></span>
        <strong><?php echo e($judul ?? 'E-Learning'); ?></strong>
        <span style="opacity:.75">SMA Negeri 1 Kabanjahe</span>
    </div>
</div>

<header class="nav">
    <div class="nav-inner">
        <a href="<?php echo e(url('/')); ?>" class="brand">
            <div class="logo">SM</div>
            <div>
                <div class="nama">E-Learning</div>
                <div class="sub">SMA Negeri 1 Kabanjahe</div>
            </div>
        </a>

        <nav class="nav-links">
            <?php if(auth()->guard()->check()): ?>
                <?php $__currentLoopData = $navigasi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route($item['route'])); ?>"
                       class="<?php echo e(request()->routeIs($item['aktif']) ? 'aktif' : ''); ?>"><?php echo e($item['label']); ?></a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </nav>

        <div class="nav-aksi">
            <?php if(auth()->guard()->check()): ?>
                <span class="pill"><?php echo e($pengguna->username); ?> · <?php echo e($peran); ?></span>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="btn abu" type="submit">Keluar</button>
                </form>
            <?php else: ?>
                <a class="btn" href="<?php echo e(route('login')); ?>">Masuk</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <?php if(session('success')): ?><div class="ok"><?php echo e(session('success')); ?></div><?php endif; ?>
    <?php if(session('error')): ?><div class="err"><?php echo e(session('error')); ?></div><?php endif; ?>
    <?php if($errors->any()): ?>
        <div class="err">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pesan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div><?php echo e($pesan); ?></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <?php echo e($slot); ?>

</main>
</body>
</html>
<?php /**PATH C:\Users\devid\MSBD_25\resources\views/components/app-layout.blade.php ENDPATH**/ ?>