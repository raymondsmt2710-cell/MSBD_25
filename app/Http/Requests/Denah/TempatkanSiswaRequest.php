<?php

namespace App\Http\Requests\Denah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TempatkanSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kelas = $this->route('kelas');

        return $kelas ? Gate::allows('denah.kelola-kursi', $kelas) : false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id_siswa' => ['required', 'integer', 'exists:anggota_kelas,id_siswa'],
            'baris' => ['required', 'integer', 'min:1'],
            'kolom' => ['required', 'integer', 'min:1'],
            'sisi' => ['nullable', Rule::in(['kiri', 'kanan'])],
        ];
    }
}
