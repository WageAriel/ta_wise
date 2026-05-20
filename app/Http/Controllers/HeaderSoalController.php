<?php

namespace App\Http\Controllers;

use App\Models\HeaderSoal;
use App\Models\DetailSoal;
use App\Models\Pertanyaan;
use Illuminate\Http\Request;

class HeaderSoalController extends Controller
{
    /**
     * GET /api/soal/header
     * Daftar semua header soal beserta jumlah pertanyaannya
     */
    public function index()
    {
        $headerSoals = HeaderSoal::withCount('pertanyaans')
            ->latest()
            ->get();

        return response()->json($headerSoals);
    }

    /**
     * POST /api/soal/header
     * Buat header soal baru + link ke pertanyaan via detail_soal
     *
     * Body contoh:
     * {
     *   "nama_soal": "Soal Klasifikasi 2026",
     *   "pertanyaan_ids": [1, 2, 3]
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_soal'        => 'required|string|max:255',
            'pertanyaan_ids'   => 'required|array|min:1',
            'pertanyaan_ids.*' => 'exists:pertanyaan,id_pertanyaan',
        ]);

        $headerSoal = HeaderSoal::create([
            'nama_soal' => $validated['nama_soal'],
        ]);

        foreach ($validated['pertanyaan_ids'] as $idPertanyaan) {
            DetailSoal::create([
                'id_soal'       => $headerSoal->id_soal,
                'id_pertanyaan' => $idPertanyaan,
            ]);
        }

        return response()->json(
            $headerSoal->load('pertanyaans.opsis'),
            201
        );
    }

    /**
     * GET /api/soal/header/{id}
     * Detail header soal beserta seluruh pertanyaan & opsinya
     */
    public function show(HeaderSoal $headerSoal)
    {
        return response()->json(
            $headerSoal->load('pertanyaans.opsis')
        );
    }

    /**
     * PUT /api/soal/header/{id}
     * Update header soal + sinkronisasi detail_soal
     */
    public function update(Request $request, HeaderSoal $headerSoal)
    {
        $validated = $request->validate([
            'nama_soal'        => 'required|string|max:255',
            'pertanyaan_ids'   => 'required|array|min:1',
            'pertanyaan_ids.*' => 'exists:pertanyaan,id_pertanyaan',
        ]);

        $headerSoal->update(['nama_soal' => $validated['nama_soal']]);

        // Sync detail_soal: hapus lama, buat ulang
        DetailSoal::where('id_soal', $headerSoal->id_soal)->delete();
        foreach ($validated['pertanyaan_ids'] as $idPertanyaan) {
            DetailSoal::create([
                'id_soal'       => $headerSoal->id_soal,
                'id_pertanyaan' => $idPertanyaan,
            ]);
        }

        return response()->json(
            $headerSoal->load('pertanyaans.opsis')
        );
    }

    /**
     * DELETE /api/soal/header/{id}
     * Hapus header soal (detail_soal cascade delete)
     */
    public function destroy(HeaderSoal $headerSoal)
    {
        $headerSoal->delete();
        return response()->json(['message' => 'Header soal berhasil dihapus.']);
    }
}
