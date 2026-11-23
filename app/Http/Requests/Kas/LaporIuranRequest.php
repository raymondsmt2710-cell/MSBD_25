<?php

namespace App\Http\Requests\Kas;

use App\Models\IuranKas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LaporIuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        $iuran = $this->route('iuran');

        return $iuran instanceof IuranKas && Gate::allows('iuran.lapor', $iuran);
    }


    public function rules(): array
    {
        return [
            'bukti' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'nominal' => ['nullable', 'numeric', 'min:1', 'max:999999999999.99'],
            'status' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'bukti.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti.image' => 'Bukti harus berupa gambar.',
            'bukti.max' => 'Ukuran bukti maksimal 2 MB.',


            'status.prohibited' => 'Status iuran tidak boleh ditentukan dari form laporan.',
        ];
    }
}
