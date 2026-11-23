<?php

namespace App\Http\Requests\Kas;

use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GenerateIuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kelas = Kelas::find($this->integer('id_kelas'));

        return $kelas ? Gate::allows('kas.kelola', $kelas) : true;
    }


    public function rules(): array
    {
        return [
            'id_kelas' => ['required', 'integer', 'exists:kelas,id_kelas'],
            'periode' => ['required', 'date_format:Y-m'],
            'nominal' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
        ];
    }
}
