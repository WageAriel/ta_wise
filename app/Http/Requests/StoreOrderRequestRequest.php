<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isDraft = (bool) $this->input('is_draft', false);

        return [
            'supplier_id' => $isDraft ? ['nullable', 'exists:suppliers,id'] : ['required', 'exists:suppliers,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_draft' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'exists:barang,id_barang'],
            'items.*.item_type_id' => ['nullable', 'exists:po_item_types,id_item_type'],
            'items.*.subtype_id' => ['nullable', 'exists:po_item_subtypes,id_subtype'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.uom' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier harus dipilih jika tidak membuat draft',
            'supplier_id.exists' => 'Supplier tidak valid',
            'items.required' => 'Minimal harus ada 1 item dalam order request',
            'items.min' => 'Minimal harus ada 1 item dalam order request',
            'items.*.barang_id.exists' => 'Salah satu barang tidak valid',
        ];
    }
}
