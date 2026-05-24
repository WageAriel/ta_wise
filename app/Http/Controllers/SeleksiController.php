<?php

namespace App\Http\Controllers;

use App\Models\Seleksi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeleksiController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $supplier = $user->supplier;

        // Logika "belum isi data" dan "tidak lolos/approved" 
        // sudah ditangani oleh middleware 'supplier.approved' pada rute.

        return Inertia::render('Supplier/SupplierSelection/Index', [
            'is_approved' => $supplier && $supplier->status === 'approved', // Status kelolosan
            'stats' => [ /* logic statistik Anda */ ],
            'applications' => [ /* logic riwayat pengajuan */ ],
        ]);
    }

    public function create()
    {
        // Data Dummy agar tampilan bisa dicek tanpa Model/Seeder
        $dummySoal = [
            'id_soal' => 1,
            'nama_soal' => 'Contoh Form Seleksi (Dummy Data)',
            'pertanyaans' => [
                [
                    'id_pertanyaan' => 1,
                    'teks_pertanyaan' => 'Apakah perusahaan memiliki gudang sendiri?',
                    'opsi' => [
                        ['id_opsi' => 1, 'teks_opsi' => 'Ya, milik sendiri', 'nilai' => 100],
                        ['id_opsi' => 2, 'teks_opsi' => 'Ya, sewa', 'nilai' => 50],
                        ['id_opsi' => 3, 'teks_opsi' => 'Tidak memiliki', 'nilai' => 0],
                    ]
                ],
                [
                    'id_pertanyaan' => 2,
                    'teks_pertanyaan' => 'Seberapa cepat lead time pengiriman Anda?',
                    'opsi' => [
                        ['id_opsi' => 4, 'teks_opsi' => 'Maksimal 3 hari', 'nilai' => 100],
                        ['id_opsi' => 5, 'teks_opsi' => '4 - 7 hari', 'nilai' => 50],
                        ['id_opsi' => 6, 'teks_opsi' => 'Lebih dari 7 hari', 'nilai' => 0],
                    ]
                ]
            ]
        ];

        return Inertia::render('Supplier/SupplierSelection/Create', [
            'paket_soal' => (object) $dummySoal
        ]);
    }

    public function store(Request $request)
    {
       
    }

    public function adminIndex()
    {
        // PERBAIKAN: Gunakan id_seleksi untuk pengurutan karena created_at tidak ada
        $selections = \App\Models\Seleksi::with('supplier')
            ->orderBy('id_seleksi', 'desc') 
            ->get();
        
        $years = \App\Models\Seleksi::selectRaw('YEAR(tanggal) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return Inertia::render('Admin/Supplier Selection/Index', [
            'selections' => $selections,
            'years' => $years
        ]);
    }

    /**
     * Detail Hasil Jawaban Seleksi Supplier
     */
    public function adminShow($id)
    {
        // PERBAIKAN: Nama relasi adalah 'jawaban' (tanpa s) sesuai Model Seleksi.php
        $selection = \App\Models\Seleksi::with(['supplier', 'jawaban.pertanyaan'])->findOrFail($id);
        
        return Inertia::render('Admin/Supplier Selection/Show', [
            'selection' => $selection
        ]);
    }

    /**
     * Hapus Data Seleksi
     */
    public function adminDestroy($id)
    {
        $selection = Seleksi::findOrFail($id);
        $selection->delete();

        return redirect()->back()->with('success', 'Data seleksi berhasil dihapus.');
    }

    /**
     * Export Data Seleksi ke Excel (Placeholder)
     */
    public function adminExport(Request $request)
    {
        // Logika export excel menggunakan Laravel Excel
        return response()->json(['message' => 'Fungsi export sedang disiapkan']);
    }
}