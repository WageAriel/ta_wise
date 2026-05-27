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

    public function create()
    {
        $user = auth()->user();
        $supplier = $user->supplier;

        // 1. Validasi: Harus Approved Pengajuan Data Perusahaan
        if (!$supplier || $supplier->status !== 'approved') {
            return redirect()->route('supplier.selection')->with('error', 'Data perusahaan Anda harus disetujui (Approved) terlebih dahulu sebelum mengikuti seleksi.');
        }

        // 2. Validasi: Hanya boleh 1 kali setahun
        $hasSubmitted = Seleksi::where('id_supplier', $supplier->id)
            ->whereYear('tanggal', now()->year)
            ->exists();

        if ($hasSubmitted) {
            return redirect()->route('supplier.selection')->with('error', 'Anda sudah mengirimkan pengajuan seleksi untuk tahun ini.');
        }

        // Ambil header soal yang berkaitan dengan Seleksi
        $header = \App\Models\HeaderSoal::where('nama_soal', 'like', '%Seleksi%')
            ->latest('id_soal')
            ->first();

        if (!$header) {
            // Jika bank soal belum di-seed, beri pesan error atau gunakan dummy
            return redirect()->back()->with('error', 'Bank soal seleksi belum tersedia. Silakan hubungi admin.');
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

        $dataSoal = [
            'id_soal' => $header->id_soal,
            'nama_soal' => $header->nama_soal,
            'pertanyaans' => $pertanyaans
        ];

        return Inertia::render('Supplier/SupplierSelection/Create', [
            'paket_soal' => (object) $dataSoal
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_soal' => 'required',
            'answers' => 'required|array',
        ]);

        $user = auth()->user();
        $supplier = $user->supplier;

        if (!$supplier) {
            return redirect()->back()->with('error', 'Profil supplier tidak ditemukan.');
        }

        // 1. Validasi: Harus Approved
        if ($supplier->status !== 'approved') {
            return redirect()->back()->with('error', 'Data perusahaan Anda belum disetujui.');
        }

        // 2. Validasi: Satu kali setahun
        $hasSubmitted = Seleksi::where('id_supplier', $supplier->id) // Ubah di sini
            ->whereYear('tanggal', now()->year)
            ->exists();

        if ($hasSubmitted) {
            return redirect()->back()->with('error', 'Anda sudah mengirimkan pengajuan seleksi tahun ini.');
        }

        return DB::transaction(function () use ($request, $user, $supplier) {
            $totalNilai = 0;
            $totalPertanyaan = count($request->answers);

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
            foreach ($request->answers as $pertanyaanId => $opsiId) {
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

            // 3. Update Skor Rata-rata
            $skorFinal = $totalPertanyaan > 0 ? ($totalNilai / $totalPertanyaan) : 0;
            $seleksi->update([
                'total_nilai' => $skorFinal
            ]);

            return redirect()->route('supplier.selection')->with('success', 'Jawaban seleksi berhasil dikirim!');
        });
    }

    public function adminIndex()
    {
        $selections = \App\Models\Seleksi::with('supplier')
            ->orderBy('id_seleksi', 'desc') 
            ->get()
            ->map(function($item) {
                // Normalisasi status untuk kebutuhan filter di Vue (Index.vue)
                $status = 'process';
                if ($item->status_seleksi === 'Lolos') $status = 'lolos';
                if ($item->status_seleksi === 'Tidak Lolos') $status = 'tidak_lolos';

                return [
                    'id_seleksi' => $item->id_seleksi,
                    'status' => $status, // Sesuai yang dicari item.status di Vue
                    'status_label' => $item->status_seleksi,
                    'tanggal' => $item->tanggal,
                    'total_nilai' => $item->total_nilai,
                    'supplier' => $item->supplier,
                ];
            });
        
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
        $selection = \App\Models\Seleksi::with(['supplier', 'jawaban.pertanyaan', 'jawaban.opsi'])->findOrFail($id);
        
        if (request()->wantsJson()) {
            return response()->json($selection);
        }

        return Inertia::render('Admin/Supplier Selection/Show', ['selection' => $selection]);
    }

    public function adminUpdateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Lolos,Tidak Lolos']);
        
        $selection = \App\Models\Seleksi::findOrFail($id);
        $selection->update(['status_seleksi' => $request->status]);

        return redirect()->back()->with('success', 'Status seleksi berhasil diperbarui.');
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