<?php

namespace App\Http\Requests\Kas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BatalIuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        $iuran = $this->route('iuran');

        return $iuran ? Gate::allows('kas.kelola', $iuran->kelas) : false;
    }


    public function rules(): array
    {
        return [];
    }
}
