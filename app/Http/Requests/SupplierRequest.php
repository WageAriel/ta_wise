<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Izinkan semua user yang sudah login
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Data Perusahaan
            'nama_perusahaan'    => 'required|string|max:255',
            'no_telp_perusahaan' => 'required|string|max:20',
            'alamat_perusahaan'  => 'required|string',
            'email_perusahaan'   => 'required|email|max:255',

            // Data PIC
            'nama_pic'           => 'required|string|max:255',
            'no_telp_pic'        => 'required|string|max:20',
            'email_pic'          => 'required|email|max:255',

            // Data Bank
            'nama_bank'          => 'required|string|max:100',
            'no_rekening'        => 'required|string|max:50',
            'atas_nama'          => 'required|string|max:255',

            // Status kepemilikan dokumen (checkbox boolean)
            'has_nib'              => 'required|boolean',
            'has_npwp'             => 'required|boolean',
            'has_akta_pendirian'   => 'required|boolean',
            'has_izin_usaha'       => 'required|boolean',
            'has_izin_khusus'      => 'required|boolean',
            'has_sk_domisili'      => 'required|boolean',
            'has_laporan_keuangan' => 'required|boolean',

            // File Dokumen (hanya wajib jika has_* bernilai true)
            'file_nib'              => 'required_if:has_nib,true|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_npwp'             => 'required_if:has_npwp,true|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_akta_pendirian'   => 'required_if:has_akta_pendirian,true|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_izin_usaha'       => 'required_if:has_izin_usaha,true|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_izin_khusus'      => 'required_if:has_izin_khusus,true|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_sk_domisili'      => 'required_if:has_sk_domisili,true|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_laporan_keuangan' => 'required_if:has_laporan_keuangan,true|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'required'    => ':attribute wajib diisi.',
            'required_if' => 'File dokumen wajib diupload jika Anda mencentang "Ya".',
            'file.mimes'  => 'Dokumen harus berformat PDF, JPG, atau PNG.',
            'file.max'    => 'Ukuran dokumen maksimal adalah 5MB.',
        ];
    }
}
