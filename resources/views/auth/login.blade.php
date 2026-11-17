<x-guest-layout judul="Masuk">
    <h1 class="text-2xl font-extrabold tracking-tight text-sekolah-ink">Selamat Datang</h1>
    <p class="mt-1 text-sm text-gray-500">Masuk ke akun Anda untuk melanjutkan.</p>

    <form method="POST" action="{{ route('login.proses') }}" class="mt-6 space-y-5">
        @csrf

        @if ($errors->any())
            <div class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm text-rose-700">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.2 14.2A1 1 0 003 19.5h18a1 1 0 00.87-1.5l-8.2-14.2a1 1 0 00-1.74 0z"/>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <div>
            <label for="username" class="label-kapital">Username</label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </span>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                       autocomplete="username" placeholder="Masukkan username"
                       class="w-full rounded-xl border-gray-300 bg-gray-50/60 py-2.5 pl-10 text-sm shadow-sm focus:border-sekolah-teal focus:bg-white focus:ring-sekolah-teal">
            </div>
            @error('username')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="label-kapital">Password</label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       placeholder="Masukkan password"
                       class="w-full rounded-xl border-gray-300 bg-gray-50/60 py-2.5 pl-10 text-sm shadow-sm focus:border-sekolah-teal focus:bg-white focus:ring-sekolah-teal">
            </div>
        </div>

        <button type="submit" class="tombol-utama w-full py-3">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Masuk
        </button>
    </form>
</x-guest-layout>
