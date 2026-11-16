<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Dashboard Siswa</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            Selamat datang, {{ auth()->user()->username }}
        </div>
    </div>
</x-app-layout>