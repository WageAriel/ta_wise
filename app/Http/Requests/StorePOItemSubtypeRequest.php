<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePOItemSubtypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subtype_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'uom' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'subtype_name.required' => 'Nama subtype harus diisi',
            'subtype_name.max' => 'Nama subtype maksimal 255 karakter',
            'sort_order.required' => 'Urutan harus diisi',
            'sort_order.integer' => 'Urutan harus berupa angka',
        ];
    }
}
