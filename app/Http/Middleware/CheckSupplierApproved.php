<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSupplierApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supplier = auth()->user()?->supplier;
        $tahunBerjalan = date('Y'); // Tahun sistem saat ini

        // 1. Jika belum mengisi data perusahaan atau belum disetujui
        if (!$supplier || $supplier->status !== 'approved') {
            return redirect()->route('supplier.data')
                ->with('error', 'Anda harus menyelesaikan dan lolos tahap Data Perusahaan terlebih dahulu.');
        }

        // 2. Jika sudah disetujui, TETAPI itu adalah data periode/tahun lalu
        if ($supplier->tahun_periode != $tahunBerjalan) {
            
            // Otomatis turunkan status ke draft & kosongkan tahun periode saat ini
            $supplier->update([
                'status' => 'draft',
                'tahun_periode' => null
            ]);

            return redirect()->route('supplier.data')
                ->with('info', 'Pendaftaran Seleksi Tahun ' . $tahunBerjalan . ' telah dibuka. Silakan perbarui Data Perusahaan & Dokumen Anda.');
        }

        // 3. Jika status approved dan tahun periodenya cocok dengan tahun ini, lolos!
        return $next($request);
    }
}
