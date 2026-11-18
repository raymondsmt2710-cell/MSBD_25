<?php

namespace App\Http\Requests\Kas;

use App\Enums\KategoriKas;
use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTransaksiKasRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kelas = Kelas::find($this->integer('id_kelas'));

        return $kelas ? Gate::allows('kas.kelola', $kelas) : true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'jenis' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
            'kategori' => ['required', 'string', 'max:50', Rule::in(KategoriKas::values())],
            'nominal' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'keterangan' => ['required', 'string', 'max:255'],
            'id_siswa' => [
                'nullable',
                'integer',
                Rule::exists('anggota_kelas', 'id_siswa')
                    ->where(fn ($query) => $query->where('id_kelas', $this->integer('id_kelas'))),
            ],
            'bukti' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
