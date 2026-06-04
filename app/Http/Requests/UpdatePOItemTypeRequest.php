<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePOItemTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemTypeId = $this->route('id');

        return [
            'type_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('po_item_types')->ignore($itemTypeId, 'id_item_type'),
            ],
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
