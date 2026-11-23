<?php

namespace App\Http\Requests\Kas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BatalTransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaksi = $this->route('transaksi');

        return $transaksi ? Gate::allows('kas.kelola', $transaksi->kelas) : false;
    }


    public function rules(): array
    {
        return [
            'alasan_batal' => ['required', 'string', 'max:255'],
        ];
    }
}
