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
        $rules = [
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
        ];

        $docs = ['nib', 'npwp', 'akta_pendirian', 'izin_usaha', 'izin_khusus', 'sk_domisili', 'laporan_keuangan'];
        $supplier = auth()->user()->supplier;

        foreach ($docs as $doc) {
            $hasDocDb = false;
            if ($supplier) {
                $docRecord = $supplier->documents()->where('jenis_dokumen', $doc)->first();
                if ($docRecord && $docRecord->file_path) {
                    $hasDocDb = true;
                }
            }

            if ($hasDocDb) {
                // Jika dokumen sudah ada di database, file opsional (tidak perlu upload ulang)
                $rules["file_$doc"] = "nullable|file|mimes:pdf,jpg,jpeg,png|max:5120";
            } else {
                // Require file if 'has_X' is 1 or true
                $rules["file_$doc"] = "required_if:has_$doc,1,true|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120";
            }
        }

        return $rules;
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
