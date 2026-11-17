<?php

namespace App\Http\Requests\Denah;

use App\Enums\KategoriRuangan;
use App\Enums\StatusRuangan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreRuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('denah.kelola-ruangan');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'kode_ruangan' => ['required', 'string', 'max:20', 'unique:ruangan_sekolah,kode_ruangan'],
            'nama_ruangan' => ['required', 'string', 'max:100'],
            'kategori' => ['required', Rule::in(KategoriRuangan::values())],
            'gedung' => ['required', 'string', 'max:50'],
            'lantai' => ['required', 'integer', 'min:1', 'max:20'],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'posisi_x' => ['required', 'integer', 'min:0', 'max:100'],
            'posisi_y' => ['required', 'integer', 'min:0', 'max:100'],
            'lebar_grid' => ['required', 'integer', 'min:1', 'max:10'],
            'tinggi_grid' => ['required', 'integer', 'min:1', 'max:10'],
            'status' => ['required', Rule::in(StatusRuangan::values())],
            'jam_buka' => ['nullable', 'date_format:H:i'],
            'jam_tutup' => ['nullable', 'date_format:H:i'],
        ];
    }
}
