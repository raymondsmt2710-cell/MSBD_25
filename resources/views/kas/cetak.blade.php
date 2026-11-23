<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan {{ $kelas->nama_kelas }}</title>
    @vite('resources/css/app.css')
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
            <p class="mt-1 text-sm text-gray-600">Laporan Keuangan Kelas</p>
            <h2 class="mt-2 text-lg font-semibold">{{ $kelas->nama_kelas }}</h2>
            <p class="text-xs text-gray-500">
                Periode:
                @if ($filter['dari'] || $filter['sampai'])
                    {{ $filter['dari'] ? \Illuminate\Support\Carbon::parse($filter['dari'])->format('d/m/Y') : 'Awal' }}
                    s.d.
                    {{ $filter['sampai'] ? \Illuminate\Support\Carbon::parse($filter['sampai'])->format('d/m/Y') : 'Sekarang' }}
                @else
                    Semua periode
                @endif
            </p>
        </header>

        <section class="mt-6 grid grid-cols-3 gap-4 text-center">
            <div class="rounded-lg border border-gray-200 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Saldo Akhir</p>
                <p class="mt-1 text-lg font-bold text-indigo-700">Rp {{ number_format($ringkasan['saldo'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Total Pemasukan</p>
                <p class="mt-1 text-lg font-bold text-emerald-700">Rp {{ number_format($ringkasan['total_pemasukan'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Total Pengeluaran</p>
                <p class="mt-1 text-lg font-bold text-rose-700">Rp {{ number_format($ringkasan['total_pengeluaran'], 0, ',', '.') }}</p>
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
                    @forelse ($transaksi as $index => $item)
                        <tr class="{{ $item->is_batal ? 'bg-rose-50 text-gray-400' : '' }}">
                            <td class="border border-gray-200 px-3 py-2">{{ $index + 1 }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $item->tanggal?->format('d/m/Y') }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $item->jenis?->label() }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $item->kategori }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-right">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $item->keterangan }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $item->siswa?->nama_siswa ?? '-' }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $item->is_batal ? 'Dibatalkan' : 'Aktif' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border border-gray-200 px-3 py-6 text-center text-gray-400">
                                Belum ada transaksi pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-semibold">
                        <td colspan="4" class="border border-gray-200 px-3 py-2 text-right">Saldo Akhir</td>
                        <td class="border border-gray-200 px-3 py-2 text-right">Rp {{ number_format($ringkasan['saldo'], 0, ',', '.') }}</td>
                        <td colspan="3" class="border border-gray-200 px-3 py-2"></td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <section class="mt-10 flex justify-between text-sm">
            <div class="text-gray-500">
                <p>Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
                <p>Oleh: {{ auth()->user()->username }}</p>
            </div>
            <div class="text-center">
                <p>Bendahara Kelas</p>
                <div class="mt-16 w-48 border-t border-gray-400 pt-1 text-xs text-gray-500">Nama &amp; tanda tangan</div>
            </div>
        </section>
    </div>
</body>
</html>
