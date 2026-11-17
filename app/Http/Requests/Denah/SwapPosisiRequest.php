<?php

namespace App\Http\Requests\Denah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SwapPosisiRequest extends FormRequest
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
            'posisi_a' => ['required', 'integer', 'exists:posisi_duduk,id_posisi'],
            'posisi_b' => ['required', 'integer', 'exists:posisi_duduk,id_posisi'],
        ];
    }
}
