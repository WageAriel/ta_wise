<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmCompletenessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documents_verified' => ['required', 'array'],
            'documents_verified.surat_permohonan' => ['required', 'boolean'],
            'documents_verified.surat_penawaran' => ['required', 'boolean'],
            'documents_verified.purchase_order' => ['required', 'boolean'],
        ];
    }
}
