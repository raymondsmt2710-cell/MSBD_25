<?php

namespace App\Http\Requests\Denah;

use App\Enums\StatusRuangan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StatusRuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('denah.kelola-ruangan');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(StatusRuangan::values())],
        ];
    }
}
