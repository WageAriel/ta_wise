<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya bisa edit jika masih inquiry (status draft)
        $id = $this->route('id');
        $po = \App\Models\PurchaseOrder::find($id);
        return $po && $po->status === 'inquiry';
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'exists:barang,id_barang', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.uom' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item dalam order request',
            'items.*.barang_id.distinct' => 'Barang tidak boleh ada duplikat',
        ];
    }
}
