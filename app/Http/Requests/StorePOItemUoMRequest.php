<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePOItemUoMRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_uom' => ['required', 'string', 'max:50'],
            'force_uom' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'default_uom.required' => 'Satuan standar harus diisi',
            'default_uom.max' => 'Satuan standar maksimal 50 karakter',
            'force_uom.required' => 'Pilihan force_uom harus diisi',
            'force_uom.boolean' => 'Force UoM harus berupa true atau false',
        ];
    }
}
