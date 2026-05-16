<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Models\SupplierScore;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DataSupplierController extends Controller
{
    // =========================================================================
    // 👤 SISI SUPPLIER
    // =========================================================================

    // 1. Tampil form data supplier
    public function index()
    {
        $supplier = auth()->user()->supplier()->with('documents')->first();

        return Inertia::render('Supplier/DataSupplier', [
            'supplier' => $supplier,
        ]);
    }

    // 2. Simpan dan ajukan data (Tahap 1)
    public function store(SupplierRequest $request)
    {
        $user = auth()->user();
        
        // Block jika status sudah diajukan (mencegah edit data yang sedang dinilai)
        if ($user->supplier && $user->supplier->status !== 'draft') {
            return back()->with('error', 'Data tidak bisa diubah karena sudah diajukan.');
        }

        // Simpan data utama ke tabel suppliers
        $supplier = Supplier::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_perusahaan' => $request->nama_perusahaan,
                'no_telp_perusahaan' => $request->no_telp_perusahaan,
                'alamat_perusahaan' => $request->alamat_perusahaan,
                'email_perusahaan' => $request->email_perusahaan,
                
                'nama_pic' => $request->nama_pic,
                'no_telp_pic' => $request->no_telp_pic,
                'email_pic' => $request->email_pic,
                
                'nama_bank' => $request->nama_bank,
                'no_rekening' => $request->no_rekening,
                'atas_nama' => $request->atas_nama,
                
                'status' => 'submitted',       // Langsung masuk status submitted
                'tahun_periode' => date('Y'),  // Catat tahun pendaftaran
                'submitted_at' => now(),
            ]
        );

        // Panggil fungsi handle dokumen
        $this->handleDocuments($request, $supplier);

        return redirect()->route('supplier.data')->with('success', 'Data berhasil diajukan ke Admin. Silakan tunggu proses verifikasi.');
    }

    // Fungsi private untuk upload dan simpan record dokumen
    private function handleDocuments(Request $request, Supplier $supplier)
    {
        // Sesuaikan dengan enum di tabel supplier_documents
        $dokumenList = ['nib', 'npwp', 'akta_pendirian', 'izin_usaha', 'izin_khusus', 'sk_domisili', 'laporan_keuangan'];

        foreach ($dokumenList as $jenis) {
            $hasDocument = $request->boolean('has_' . $jenis);
            $filePath = null;
            $fileName = null;

            // Jika supplier centang "Ya" dan file benar-benar diupload
            if ($hasDocument && $request->hasFile('file_' . $jenis)) {
                $file = $request->file('file_' . $jenis);
                $filePath = $file->store("supplier-docs/{$supplier->id}", 'public');
                $fileName = $file->getClientOriginalName();
            }

            // Simpan / update ke database
            SupplierDocument::updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'jenis_dokumen' => $jenis
                ],
                [
                    'has_document' => $hasDocument,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                ]
            );
        }
    }

    // =========================================================================
    // 🔐 SISI ADMIN
    // =========================================================================

    // 1. Tampil daftar semua supplier yang sudah submit
    public function adminIndex()
    {
        $suppliers = Supplier::with('user')
            ->whereIn('status', ['submitted', 'approved', 'rejected'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/SupplierData', [
            'suppliers' => $suppliers,
        ]);
    }

    // 2. Tampil detail 1 supplier beserta file dokumennya
    public function adminShow(Supplier $supplier)
    {
        // Load data, dokumen, dan riwayat skor HANYA untuk tahun yang sedang berjalan
        $supplier->load(['user', 'documents', 'scores' => function($query) use ($supplier) {
            $query->where('tahun_periode', $supplier->tahun_periode);
        }]);

        return Inertia::render('Admin/SupplierDetail', [
            'supplier' => $supplier,
        ]);
    }

    // 3. Setujui supplier (Approve) & Masukkan Skor
    public function adminApprove(Request $request, Supplier $supplier)
    {
        // Validasi input skor dari admin (0 = Tidak Memenuhi, 1 = Cukup, 2 = Memenuhi)
        $request->validate([
            'skor_kelengkapan_dokumen' => 'required|integer|min:0|max:2',
            'skor_nib' => 'required|integer|min:0|max:2',
            'skor_npwp' => 'required|integer|min:0|max:2',
            'skor_akta_pendirian' => 'required|integer|min:0|max:2',
            'skor_izin_usaha' => 'required|integer|min:0|max:2',
            'skor_izin_khusus' => 'required|integer|min:0|max:2',
            'skor_sk_domisili' => 'required|integer|min:0|max:2',
            'skor_laporan_keuangan' => 'required|integer|min:0|max:2',
            'catatan' => 'nullable|string',
        ]);

        // Kalkulasi Total Skor
        $totalSkor = $request->skor_kelengkapan_dokumen + 
                     $request->skor_nib + 
                     $request->skor_npwp + 
                     $request->skor_akta_pendirian + 
                     $request->skor_izin_usaha + 
                     $request->skor_izin_khusus + 
                     $request->skor_sk_domisili + 
                     $request->skor_laporan_keuangan;

        // Simpan nilai ke tabel supplier_scores
        SupplierScore::create([
            'supplier_id' => $supplier->id,
            'admin_id' => auth()->id(),
            'tahun_periode' => $supplier->tahun_periode, // Nilai ini dikunci untuk tahun pengajuan
            
            'skor_kelengkapan_dokumen' => $request->skor_kelengkapan_dokumen,
            'skor_nib' => $request->skor_nib,
            'skor_npwp' => $request->skor_npwp,
            'skor_akta_pendirian' => $request->skor_akta_pendirian,
            'skor_izin_usaha' => $request->skor_izin_usaha,
            'skor_izin_khusus' => $request->skor_izin_khusus,
            'skor_sk_domisili' => $request->skor_sk_domisili,
            'skor_laporan_keuangan' => $request->skor_laporan_keuangan,
            
            'total_skor' => $totalSkor,
            'catatan' => $request->catatan,
        ]);

        // Ubah status supplier menjadi approved
        $supplier->update([
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.supplier.data')->with('success', 'Supplier berhasil disetujui (Lolos Tahap 1).');
    }

    // 4. Tolak supplier (Reject)
    public function adminReject(Request $request, Supplier $supplier)
    {
        $request->validate([
            'catatan_admin' => 'required|string|max:500' // Alasan penolakan wajib diisi
        ]);

        $supplier->update([
            'status' => 'rejected',
            'catatan_admin' => $request->catatan_admin,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Supplier telah ditolak.');
    }
}
