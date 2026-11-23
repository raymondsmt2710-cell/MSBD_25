<?php

namespace App\Http\Requests\Kas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VerifikasiIuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        $iuran = $this->route('iuran');

        return $iuran ? Gate::allows('kas.kelola', $iuran->kelas) : false;
    }


    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['terverifikasi', 'ditolak'])],
            'catatan' => ['nullable', 'string', 'max:255', 'required_if:status,ditolak'],
        ];
    }
}
