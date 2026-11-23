<?php

namespace App\Http\Requests\Kas;

use App\Enums\KategoriKas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTransaksiKasRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaksi = $this->route('transaksi');

        return $transaksi ? Gate::allows('kas.kelola', $transaksi->kelas) : false;
    }


    public function rules(): array
    {
        $transaksi = $this->route('transaksi');
        $idKelas = $transaksi?->id_kelas;

        return [
            'tanggal' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'jenis' => ['sometimes', 'required', Rule::in(['pemasukan', 'pengeluaran'])],
            'kategori' => ['sometimes', 'required', 'string', 'max:50', Rule::in(KategoriKas::values())],
            'nominal' => ['sometimes', 'required', 'numeric', 'min:1', 'max:999999999999.99'],
            'keterangan' => ['sometimes', 'required', 'string', 'max:255'],
            'id_siswa' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('anggota_kelas', 'id_siswa')
                    ->where(fn ($query) => $query->where('id_kelas', $idKelas)),
            ],
            'bukti' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
