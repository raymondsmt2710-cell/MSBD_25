<?php

namespace App\Http\Requests\Denah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SimpanDenahRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kelas = $this->route('kelas');

        return $kelas ? Gate::allows('denah.kelola-kursi', $kelas) : false;
    }


    public function rules(): array
    {
        return [
            'jumlah_baris' => ['required', 'integer', 'min:1', 'max:12'],
            'jumlah_kolom' => ['required', 'integer', 'min:1', 'max:10'],
            'posisi_meja_guru' => ['required', Rule::in(['depan_kiri', 'depan_tengah', 'depan_kanan'])],
            'posisi_pintu' => ['required', Rule::in(['kiri', 'kanan', 'belakang'])],
        ];
    }
}
