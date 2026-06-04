<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePOItemTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_name' => ['required', 'string', 'max:255', 'unique:po_item_types'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_name.required' => 'Nama tipe item harus diisi',
            'type_name.unique' => 'Nama tipe item sudah ada dalam database',
            'sort_order.required' => 'Urutan harus diisi',
            'sort_order.integer' => 'Urutan harus berupa angka',
        ];
    }
}
