<?php

use App\Http\Middleware\DBDynamicConnection;
use App\Http\Middleware\PastikanAkunAktif;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Aplikasi ini berjalan di belakang proxy yang menangani HTTPS:
        // cloudflared (lokal) atau Nginx (produksi). Proxy meneruskan header
        // X-Forwarded-Proto: https, tetapi Laravel mengabaikannya selama proxy
        // belum dipercaya.
        //
        // Akibatnya request dianggap http meski pengunjung memakai https:
        //   - asset() menghasilkan http://... di halaman https://
        //   - browser memblokirnya sebagai mixed content (pemindai QR gagal dimuat)
        //   - cookie sesi tidak ditandai Secure
        //
        // at: '*' aman karena satu-satunya jalan masuk adalah proxy itu sendiri;
        // port 8000 tidak terbuka langsung ke internet.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        // append => berjalan SETELAH StartSession, sehingga Auth::check() sudah tahu siapa yang login
        $middleware->web(append: [
            // Mencabut akses akun yang dinonaktifkan pada setiap request.
            PastikanAkunAktif::class,
            DBDynamicConnection::class,
            // Mencabut session akun yang dinonaktifkan admin (fitur Kelola Akun).
            PastikanAkunAktif::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
