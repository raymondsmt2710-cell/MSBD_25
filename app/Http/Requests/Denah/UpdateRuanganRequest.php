<?php

namespace App\Http\Requests\Denah;

use App\Enums\KategoriRuangan;
use App\Enums\StatusRuangan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateRuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('denah.kelola-ruangan');
    }


    public function rules(): array
    {
        $ruangan = $this->route('ruangan');

        return [
            'kode_ruangan' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('ruangan_sekolah', 'kode_ruangan')->ignore($ruangan?->id_ruangan, 'id_ruangan'),
            ],
            'nama_ruangan' => ['sometimes', 'required', 'string', 'max:100'],
            'kategori' => ['sometimes', 'required', Rule::in(KategoriRuangan::values())],
            'gedung' => ['sometimes', 'required', 'string', 'max:50'],
            'lantai' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'posisi_x' => ['sometimes', 'required', 'integer', 'min:0', 'max:100'],
            'posisi_y' => ['sometimes', 'required', 'integer', 'min:0', 'max:100'],
            'lebar_grid' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'tinggi_grid' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'status' => ['sometimes', 'required', Rule::in(StatusRuangan::values())],
            'jam_buka' => ['nullable', 'date_format:H:i'],
            'jam_tutup' => ['nullable', 'date_format:H:i'],
        ];
    }
}
