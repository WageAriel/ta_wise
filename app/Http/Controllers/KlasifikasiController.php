<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Klasifikasi;
use App\Models\Pertanyaan;
use App\Models\Opsi;
use App\Models\JawabanKlasifikasi;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KlasifikasiController extends Controller
{
    // =========================================================
    // SUPPLIER
    // =========================================================

    /**
     * GET /api/klasifikasi/pertanyaan
     * Ambil daftar pertanyaan klasifikasi aktif.
     * Tidak memerlukan supplier — aman diakses saat halaman form dimuat.
     * Jika user sudah login dan punya supplier aktif,
     * akan sekalian mengecek apakah sudah ada pengajuan yang berjalan.
     */
    public function getPertanyaan()
    {
        // Cek pengajuan aktif hanya kalau user sudah login dan punya data supplier
        if (Auth::check()) {
            $supplier = Supplier::where('user_id', Auth::id())->first();

            if ($supplier) {
                $latestSeleksi = \App\Models\Seleksi::where('id_supplier', $supplier->id)
                    ->latest('tanggal')
                    ->first();

                if (!$latestSeleksi || $latestSeleksi->status_seleksi !== 'Lolos') {
                    return response()->json([
                        'message'  => 'Anda harus divalidasi dan lolos seleksi terlebih dahulu sebelum dapat mengajukan klasifikasi.',
                        'existing' => null,
                    ], 403);
                }

                $existing = Klasifikasi::where('id_supplier', $supplier->id)
                    ->whereIn('status_klasifikasi', ['pending', 'diproses'])
                    ->first();

                if ($existing) {
                    return response()->json([
                        'message'  => 'Anda sudah memiliki pengajuan yang sedang diproses.',
                        'existing' => $existing,
                    ], 409);
                }
            }
        }

        // Cari header soal aktif berdasarkan pilihan admin.
        // Jika admin sudah memilih, gunakan yang dipilih; fallback ke latest.
        $idSoalAktif = AppSetting::where('key', 'id_soal_klasifikasi_aktif')->value('value');

        $headerSoalQuery = \App\Models\HeaderSoal::whereHas('pertanyaans', function ($query) {
            $query->where('jenis_soal', 'klasifikasi')
                  ->where('status', 'aktif');
        });

        if ($idSoalAktif) {
            $headerSoal = $headerSoalQuery->find($idSoalAktif)
                ?? $headerSoalQuery->latest('id_soal')->first();
        } else {
            $headerSoal = $headerSoalQuery->latest('id_soal')->first();
        }

        if (!$headerSoal) {
            return response()->json([
                'id_soal' => null,
                'pertanyaans' => []
            ]);
        }

        $pertanyaans = $headerSoal->pertanyaans()
            ->where('pertanyaan.jenis_soal', 'klasifikasi')
            ->where('pertanyaan.status', 'aktif')
            ->with('opsis')
            ->get();

        return response()->json([
            'id_soal' => $headerSoal->id_soal,
            'pertanyaans' => $pertanyaans
        ]);
    }

    /**
     * POST /api/klasifikasi
     * Submit pengajuan klasifikasi oleh supplier.
     * Status otomatis di-set "pending".
     *
     * Body contoh:
     * {
     *   "jawaban": [
     *     { "id_pertanyaan": 1, "id_opsi": 3 },
     *     { "id_pertanyaan": 2, "id_opsi": 7 }
     *   ]
     * }
     */
    public function store(Request $request)
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return response()->json(['message' => 'Silakan login terlebih dahulu.'], 401);
        }

        // Cari data supplier milik user ini
        $supplier = Supplier::where('user_id', Auth::id())->first();
        if (!$supplier) {
            return response()->json([
                'message' => 'Data supplier tidak ditemukan. Lengkapi profil perusahaan Anda terlebih dahulu.'
            ], 422);
        }

        $latestSeleksi = \App\Models\Seleksi::where('id_supplier', $supplier->id)
            ->latest('tanggal')
            ->first();

        if (!$latestSeleksi || $latestSeleksi->status_seleksi !== 'Lolos') {
            return response()->json([
                'message' => 'Anda harus divalidasi dan lolos seleksi terlebih dahulu sebelum dapat mengajukan klasifikasi.'
            ], 403);
        }

        $validated = $request->validate([
            'id_soal'                 => 'required|exists:header_soal,id_soal',
            'jawaban'                 => 'required|array|min:1',
            'jawaban.*.id_pertanyaan' => 'required|exists:pertanyaan,id_pertanyaan',
            'jawaban.*.id_opsi'       => 'required|exists:opsi,id_opsi',
        ]);

        // Hitung total nilai dari opsi yang dipilih
        $totalNilai = 0;
        foreach ($validated['jawaban'] as $jwb) {
            $opsi = Opsi::find($jwb['id_opsi']);
            if ($opsi) {
                $pertanyaan = Pertanyaan::find($jwb['id_pertanyaan']);
                if ($pertanyaan) {
                    $totalNilai += round(($opsi->nilai / 100) * $pertanyaan->bobot);
                }
            }
        }

        $klasifikasi = DB::transaction(function () use ($supplier, $validated, $totalNilai) {
            $klasifikasi = Klasifikasi::create([
                'tanggal'            => now()->toDateString(),
                'status_klasifikasi' => 'pending',
                'total_nilai'        => $totalNilai,
                'id_user'            => Auth::id(),
                'id_supplier'        => $supplier->id,
                'id_soal'            => $validated['id_soal'],
            ]);

            foreach ($validated['jawaban'] as $jwb) {
                JawabanKlasifikasi::create([
                    'jawaban_verifikasi' => 'valid',
                    'id_klasifikasi'     => $klasifikasi->id_klasifikasi,
                    'id_pertanyaan'      => $jwb['id_pertanyaan'],
                    'id_opsi'            => $jwb['id_opsi'],
                    'id_verifikasi'      => null,
                ]);
            }

            return $klasifikasi;
        });

        return response()->json([
            'message'     => 'Pengajuan klasifikasi berhasil dikirim.',
            'klasifikasi' => $klasifikasi->load('jawabanKlasifikasis.opsi'),
        ], 201);
    }

    /**
     * GET /api/klasifikasi/saya
     * Riwayat pengajuan klasifikasi milik supplier yang sedang login.
     */
    public function supplierIndex(Request $request)
    {
        $supplier = Supplier::where('user_id', Auth::id())->first();

        $canSubmitClassification = false;
        if ($supplier) {
            $latestSeleksi = \App\Models\Seleksi::where('id_supplier', $supplier->id)
                ->latest('tanggal')
                ->first();
            if ($latestSeleksi && $latestSeleksi->status_seleksi === 'Lolos') {
                $canSubmitClassification = true;
            }
        }

        if (!$supplier) {
            return response()->json([
                'data'  => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $request->get('per_page', 10), 1, ['path' => $request->url()]),
                'stats' => [
                    'total_pengajuan'     => 0,
                    'disetujui'           => 0,
                    'menunggu_validasi'   => 0,
                ],
                'can_submit_classification' => false,
            ]);
        }

        $klasifikasis = Klasifikasi::where('id_supplier', $supplier->id)
            ->with([
                'jawabanKlasifikasis.pertanyaan',
                'jawabanKlasifikasis.opsi',
                'verifikasi.admin',
                'verifikasi.petugas.profilPetugas',
                'jadwalKunjungan.petugas.profilPetugas',
            ])
            ->latest()
            ->paginate($request->get('per_page', 10));

        $stats = [
            'total_pengajuan'     => Klasifikasi::where('id_supplier', $supplier->id)->count(),
            'disetujui'           => Klasifikasi::where('id_supplier', $supplier->id)->where('status_klasifikasi', 'selesai')->count(),
            'menunggu_validasi'   => Klasifikasi::where('id_supplier', $supplier->id)->whereIn('status_klasifikasi', ['pending', 'diproses'])->count(),
        ];

        return response()->json([
            'data'  => $klasifikasis,
            'stats' => $stats,
            'can_submit_classification' => $canSubmitClassification,
        ]);
    }

    // =========================================================
    // ADMIN
    // =========================================================

    /**
     * GET /api/admin/klasifikasi
     * Seluruh pengajuan klasifikasi beserta statistik.
     */
    public function adminIndex(Request $request)
    {
        $query = Klasifikasi::with(['supplier', 'user', 'verifikasi.petugas.profilPetugas', 'verifikasi.admin', 'jadwalKunjungan.petugas.profilPetugas'])->latest();

        if ($request->filled('status')) {
            if ($request->status === 'pending_diproses') {
                $query->whereIn('status_klasifikasi', ['pending', 'diproses']);
            } else {
                $query->where('status_klasifikasi', $request->status);
            }
        }

        if ($request->filled('search')) {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('nama_perusahaan', 'like', '%' . $request->search . '%');
            });
        }

        $klasifikasis = $query->paginate($request->get('per_page', 15));

        $stats = [
            'total_pengajuan'     => Klasifikasi::count(),
            'menunggu_validasi'   => Klasifikasi::whereIn('status_klasifikasi', ['pending', 'diproses'])->count(),
            'selesai'             => Klasifikasi::where('status_klasifikasi', 'selesai')->count(),
            'pengajuan_bulan_ini' => Klasifikasi::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
        ];

        return response()->json([
            'data'  => $klasifikasis,
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/admin/klasifikasi/{id}
     * Detail satu pengajuan klasifikasi beserta semua relasi.
     */
    public function adminShow(Klasifikasi $klasifikasi)
    {
        $klasifikasi->load([
            'supplier',
            'user',
            'jawabanKlasifikasis.pertanyaan',
            'jawabanKlasifikasis.opsi',
            'jawabanKlasifikasis.opsiVerifikasi',
            'verifikasi.admin',
            'verifikasi.petugas.profilPetugas',
        ]);

        return response()->json($klasifikasi);
    }

    /**
     * PATCH /api/admin/klasifikasi/{id}/status
     * Update status klasifikasi oleh admin.
     *
     * Body:
     * { "status_klasifikasi": "diproses" }
     */
    public function adminUpdateStatus(Request $request, Klasifikasi $klasifikasi)
    {
        $validated = $request->validate([
            'status_klasifikasi' => 'required|in:pending,diproses,selesai,ditolak',
        ]);

        $klasifikasi->update($validated);

        return response()->json([
            'message'     => 'Status klasifikasi berhasil diperbarui.',
            'klasifikasi' => $klasifikasi,
        ]);
    }

    /**
     * POST /api/admin/klasifikasi/{id}/validasi
     * Simpan keputusan akhir admin terhadap kelas supplier.
     *
     * Body:
     * { "keputusan_admin": "Class A" }
     */
    public function adminValidasi(Request $request, Klasifikasi $klasifikasi)
    {
        $validated = $request->validate([
            'keputusan_admin' => 'required|in:Class A,Class B,Class C',
        ]);

        // Pastikan sudah ada record verifikasi lapangan
        if (!$klasifikasi->verifikasi) {
            return response()->json([
                'message' => 'Belum ada hasil verifikasi lapangan untuk pengajuan ini.',
            ], 422);
        }

        // Pastikan belum pernah divalidasi (status selesai)
        if ($klasifikasi->status_klasifikasi === 'selesai') {
            return response()->json([
                'message' => 'Pengajuan ini sudah divalidasi dan tidak dapat diubah kembali.',
            ], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($klasifikasi, $validated) {
            // Simpan keputusan admin di tabel verifikasi
            $klasifikasi->verifikasi->update([
                'keputusan_admin' => $validated['keputusan_admin'],
                'id_user_admin'   => \Illuminate\Support\Facades\Auth::id(),
                'status'          => 'selesai',
            ]);

            // Update status klasifikasi menjadi selesai
            $klasifikasi->update([
                'status_klasifikasi' => 'selesai',
            ]);
        });

        return response()->json([
            'message' => 'Keputusan validasi akhir berhasil disimpan.',
        ]);
    }

    /**
     * GET /admin/supplier/classification/export
     * Admin export data klasifikasi
     */
    public function adminExport(\Illuminate\Http\Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\KlasifikasiExport, 'data_klasifikasi.xlsx');
    }
}
