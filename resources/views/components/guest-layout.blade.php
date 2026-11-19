@props(['judul' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $judul ? $judul.' - ' : '' }}SakuSaka - SMA Negeri 1 Kabanjahe</title>

    <link rel="icon" href="{{ asset('images/logo-sakusaka-mark.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-sekolah-ink font-sans text-gray-800 antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-sekolah-ink via-sekolah-teal to-sekolah-blue px-4 py-10">
        <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-sekolah-amber/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-20 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute right-1/4 top-10 h-40 w-40 rounded-full bg-sekolah-teal/40 blur-2xl"></div>

        <div class="relative w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl shadow-black/30 sm:p-10">
            <div class="mb-7 flex items-center gap-3 border-b border-gray-100 pb-6">
                <x-logo mark tinggi="48" kelas="rounded-2xl" />
                <div>
                    <p class="text-lg font-extrabold leading-tight tracking-tight text-sekolah-ink">SakuSaka</p>
                    <p class="text-xs font-medium text-gray-500">SMA Negeri 1 Kabanjahe</p>
                </div>
            </div>

            {{ $slot }}

            <p class="mt-8 text-center text-[11px] text-gray-400">
                &copy; {{ date('Y') }} SakuSaka &middot; SMA Negeri 1 Kabanjahe
            </p>
        </div>
    </div>
</body>
</html>
