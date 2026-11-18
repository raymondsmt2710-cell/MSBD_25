<?php

namespace App\Http\Requests\Denah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PindahPosisiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $posisi = $this->route('posisi');

        return $posisi ? Gate::allows('denah.kelola-kursi', $posisi->denah->kelas) : false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'baris' => ['required', 'integer', 'min:1'],
            'kolom' => ['required', 'integer', 'min:1'],
            'sisi' => ['nullable', Rule::in(['kiri', 'kanan'])],
        ];
    }
}
