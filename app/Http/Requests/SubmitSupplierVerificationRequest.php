<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSupplierVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_id' => ['required', 'exists:purchase_orders,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.supplier_price' => ['required', 'numeric', 'min:0'],
            'items.*.supplier_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'po_id.required' => 'PO ID harus ada',
            'po_id.exists' => 'PO tidak valid',
            'items.required' => 'Minimal 1 item harus di-submit',
            'items.*.supplier_price.required' => 'Harga yang ditawarkan harus diisi',
            'items.*.supplier_quantity.required' => 'Quantity yang ditawarkan harus diisi',
        ];
    }
}
