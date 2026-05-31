<?php

namespace App\Http\Controllers;

use App\Models\Seleksi;
use App\Models\JawabanSeleksi;
use App\Models\Opsi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SeleksiController extends Controller
{
    // =========================================================
    // SUPPLIER
    // =========================================================

    /**
     * GET /supplier/selection
     * Menampilkan daftar riwayat seleksi milik supplier.
     */
    public function index()
    {
        $user = auth()->user();
        $supplier = $user->supplier;

        $hasSubmittedThisYear = false;
        $applications = [];

        if ($supplier) {
            $hasSubmittedThisYear = Seleksi::where('id_supplier', $supplier->id)
                ->whereYear('tanggal', now()->year)
                ->exists();

            // Map data agar sesuai dengan prop yang diharapkan Vue (supplier_name, date, status)
            $applications = Seleksi::where('id_supplier', $supplier->id)
                ->orderBy('tanggal', 'desc')
                ->get()
                ->map(function ($item) use ($supplier) {
                    return [
                        'id_seleksi' => $item->id_seleksi,
                        'supplier_name' => $supplier->nama_perusahaan, // Mengambil nama dari model supplier
                        'date' => $item->tanggal,
                        'status' => $item->status_seleksi,
                        'total_nilai' => $item->total_nilai,
                    ];
                });
        }

        return Inertia::render('Supplier/SupplierSelection/Index', [
            'is_approved' => $supplier && $supplier->status === 'approved',
            'has_submitted_this_year' => $hasSubmittedThisYear,
            'stats' => [
                'total' => count($applications),
                'lolos' => $applications->where('status', 'Lolos')->count(),
                'pending' => $applications->where('status', 'Menunggu Validasi')->count(),
            ],
            'applications' => $applications,
        ]);
    }

    /**
     * GET /supplier/selection/create
     * Menampilkan halaman form pengerjaan soal seleksi.
     */
    public function create()
    {
        return Inertia::render('Supplier/SupplierSelection/Create');
    }

    /**
     * GET /api/seleksi/questions
     * Mengambil 15 pertanyaan secara acak untuk pengerjaan seleksi.
     */
    public function getQuestions()
    {
        $user = auth()->user();
        $supplier = $user->supplier;

        // 1. Validasi: Harus Approved Pengajuan Data Perusahaan
        if (!$supplier || $supplier->status !== 'approved') {
            return response()->json(['message' => 'Data perusahaan Anda harus disetujui (Approved) terlebih dahulu sebelum mengikuti seleksi.'], 403);
        }

        // 2. Validasi: Hanya boleh 1 kali setahun
        $hasSubmitted = Seleksi::where('id_supplier', $supplier->id)
            ->whereYear('tanggal', now()->year)
            ->exists();

        if ($hasSubmitted) {
            return response()->json(['message' => 'Anda sudah mengirimkan pengajuan seleksi untuk tahun ini.'], 409);
        }

        // Ambil header soal yang berkaitan dengan Seleksi
        $header = \App\Models\HeaderSoal::where('nama_soal', 'like', '%Seleksi%')
            ->latest('id_soal')
            ->first();

        if (!$header) {
            return response()->json(['message' => 'Bank soal seleksi belum tersedia. Silakan hubungi admin.'], 404);
        }

        // Ambil 15 pertanyaan secara acak dari header tersebut
        $pertanyaans = $header->pertanyaans()
            ->where('status', 'aktif')
            ->inRandomOrder()
            ->limit(15)
            ->with('opsis')
            ->get()
            ->map(function ($p) {
                return [
                    'id_pertanyaan' => $p->id_pertanyaan,
                    'teks_pertanyaan' => $p->teks_pertanyaan,
                    'opsi' => $p->opsis->map(function ($o) {
                        return [
                            'id_opsi' => $o->id_opsi,
                            'teks_opsi' => $o->teks_opsi,
                            'nilai' => $o->nilai,
                        ];
                    })
                ];
            });

        return response()->json([
            'id_soal' => $header->id_soal,
            'nama_soal' => $header->nama_soal,
            'pertanyaans' => $pertanyaans
        ]);
    }

    /**
     * POST /api/seleksi
     * Menyimpan jawaban seleksi supplier ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_soal' => 'required',
            'jawaban' => 'required|array',
        ]);

        $user = auth()->user();
        $supplier = $user->supplier;

        if (!$supplier) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Profil supplier tidak ditemukan.'], 404);
            }
            return redirect()->back()->with('error', 'Profil supplier tidak ditemukan.');
        }

        // 1. Validasi: Harus Approved
        if ($supplier->status !== 'approved') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Data perusahaan Anda belum disetujui.'], 403);
            }
            return redirect()->back()->with('error', 'Data perusahaan Anda belum disetujui.');
        }

        // 2. Validasi: Satu kali setahun
        $hasSubmitted = Seleksi::where('id_supplier', $supplier->id)
            ->whereYear('tanggal', now()->year)
            ->exists();

        if ($hasSubmitted) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Anda sudah mengirimkan pengajuan seleksi tahun ini.'], 409);
            }
            return redirect()->back()->with('error', 'Anda sudah mengirimkan pengajuan seleksi tahun ini.');
        }

        return DB::transaction(function () use ($request, $user, $supplier) {
            $totalNilai = 0;
            $totalPertanyaan = count($request->jawaban);

            // 1. Buat Header Seleksi
            $seleksi = Seleksi::create([
                'id_user'        => $user->id,
                'id_supplier'    => $supplier->id,
                'id_soal'        => $request->id_soal,
                'tanggal'        => now()->toDateString(),
                'status_seleksi' => 'Menunggu Validasi',
                'total_nilai'    => 0, // Akan diupdate setelah loop
            ]);

            // 2. Simpan Jawaban & Hitung Skor
            foreach ($request->jawaban as $item) {
                $pertanyaanId = $item['id_pertanyaan'];
                $opsiId = $item['id_opsi'];
                
                $opsi = Opsi::find($opsiId);
                if ($opsi) {
                    $totalNilai += $opsi->nilai;

                    JawabanSeleksi::create([
                        'id_seleksi'    => $seleksi->id_seleksi,
                        'id_pertanyaan' => $pertanyaanId,
                        'jawaban'       => $opsiId, // Simpan ID Opsi
                    ]);
                }
            }

            // 3. Update Skor
            $skorFinal = $totalPertanyaan > 0 ? ($totalNilai / $totalPertanyaan) : 0;
            $seleksi->update([
                'total_nilai' => $skorFinal
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Jawaban seleksi berhasil dikirim!']);
            }
            return redirect()->route('supplier.selection')->with('success', 'Jawaban seleksi berhasil dikirim!');
        });
    }

    // =========================================================
    // ADMIN
    // =========================================================

    /**
     * GET /admin/supplier-selection
     * Menampilkan semua daftar pengajuan seleksi untuk dikelola admin.
     */
    public function adminIndex(Request $request)
    {
        $query = Seleksi::with('supplier')
            ->orderBy('id_seleksi', 'desc');

        // Filter Search (Nama Supplier)
        if ($request->search) {
            $query->whereHas('supplier', function($q) use ($request) {
                $q->where('nama_perusahaan', 'like', '%' . $request->search . '%');
            });
        }

        // Filter Status
        if ($request->status) {
            $statusValue = $request->status;
            // Jika dikirim dari tab (pending/validated), kita sesuaikan
            if ($statusValue === 'pending') {
                $query->where('status_seleksi', 'Menunggu Validasi');
            } elseif ($statusValue === 'validated') {
                $query->whereIn('status_seleksi', ['Lolos', 'Tidak Lolos']);
            } else {
                $statusLabel = $statusValue === 'lolos' ? 'Lolos' : ($statusValue === 'tidak_lolos' ? 'Tidak Lolos' : 'Menunggu Validasi');
                $query->where('status_seleksi', $statusLabel);
            }
        }

        // Filter Tahun
        if ($request->tahun) {
            $query->whereYear('tanggal', $request->tahun);
        }

        $perPage = $request->per_page ?? 10;
        $selections = $query->paginate($perPage);

        // Transform results to match Index.vue structure
        $selections->getCollection()->transform(function($item) {
            $status = 'process';
            if ($item->status_seleksi === 'Lolos') $status = 'lolos';
            if ($item->status_seleksi === 'Tidak Lolos') $status = 'tidak_lolos';

            return [
                'id_seleksi' => $item->id_seleksi,
                'status' => $status,
                'status_label' => $item->status_seleksi,
                'tanggal' => $item->tanggal,
                'total_nilai' => $item->total_nilai,
                'supplier' => $item->supplier,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json($selections);
        }

        $years = Seleksi::selectRaw('YEAR(tanggal) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return Inertia::render('Admin/Supplier Selection/Index', [
            'selections' => $selections,
            'years' => $years
        ]);
    }

    /**
     * GET /admin/supplier-selection/{id}
     * Menampilkan detail hasil jawaban seleksi supplier tertentu.
     */
    public function adminShow($id)
    {
        $selection = Seleksi::with(['supplier', 'jawaban.pertanyaan', 'jawaban.opsi'])->findOrFail($id);
        
        if (request()->wantsJson()) {
            return response()->json($selection);
        }

        return Inertia::render('Admin/Supplier Selection/Show', ['selection' => $selection]);
    }

    /**
     * PATCH /admin/supplier-selection/{id}
     * Memperbarui status kelulusan seleksi supplier.
     */
    public function adminUpdateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Lolos,Tidak Lolos']);
        
        $selection = Seleksi::findOrFail($id);
        $selection->update(['status_seleksi' => $request->status]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Status seleksi berhasil diperbarui.']);
        }

        return redirect()->back()->with('success', 'Status seleksi berhasil diperbarui.');
    }

    /**
     * DELETE /admin/supplier-selection/{id}
     * Menghapus data riwayat seleksi.
     */
    public function adminDestroy($id)
    {
        $selection = Seleksi::findOrFail($id);
        $selection->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Data seleksi berhasil dihapus.']);
        }

        return redirect()->back()->with('success', 'Data seleksi berhasil dihapus.');
    }

    /**
     * GET /admin/supplier-selection/export
     * Export data riwayat seleksi ke file Excel.
     */
    public function adminExport(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\SeleksiExport, 'data_seleksi.xlsx');
    }

    /**
     * POST /admin/supplier-selection/import
     * Import data riwayat seleksi dari file Excel.
     */
    public function adminImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\SeleksiImport, $request->file('file'));

        return response()->json([
            'status' => 'success',
            'message' => 'Data seleksi berhasil diimport.'
        ]);
    }
}