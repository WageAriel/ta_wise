<?php

namespace App\Http\Controllers;

use App\Models\Pertanyaan;
use App\Models\Opsi;
use Illuminate\Http\Request;

class PertanyaanController extends Controller
{
    /**
     * GET /api/pertanyaan
     * Daftar semua pertanyaan (bisa difilter jenis/status)
     */
    public function index(Request $request)
    {
        $query = Pertanyaan::withCount('opsis');

        if ($request->filled('jenis_soal')) {
            $query->where('jenis_soal', $request->jenis_soal);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * POST /api/pertanyaan
     * Buat pertanyaan baru beserta opsi jawabannya
     *
     * Body contoh:
     * {
     *   "jenis_soal": "klasifikasi",
     *   "teks_pertanyaan": "...",
     *   "bobot": 20,
     *   "status": "aktif",
     *   "opsis": [
     *     { "teks_opsi": "Ya", "nilai": 80 },
     *     { "teks_opsi": "Tidak", "nilai": 20 }
     *   ]
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_soal'            => 'required|in:seleksi,klasifikasi',
            'teks_pertanyaan'       => 'required|string',
            'bobot'                 => 'required|integer|min:1|max:100',
            'status'                => 'required|string|in:aktif,nonaktif',
            'opsis'                 => 'required|array|min:2',
            'opsis.*.teks_opsi'     => 'required|string|max:255',
            'opsis.*.nilai'         => 'required|integer|min:0|max:100',
        ]);

        $pertanyaan = Pertanyaan::create([
            'jenis_soal'      => $validated['jenis_soal'],
            'teks_pertanyaan' => $validated['teks_pertanyaan'],
            'bobot'           => $validated['bobot'],
            'status'          => $validated['status'],
        ]);

        foreach ($validated['opsis'] as $opsiData) {
            Opsi::create([
                'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                'teks_opsi'     => $opsiData['teks_opsi'],
                'nilai'         => $opsiData['nilai'],
            ]);
        }

        return response()->json(
            $pertanyaan->load('opsis'),
            201
        );
    }

    /**
     * GET /api/pertanyaan/{id}
     */
    public function show(Pertanyaan $pertanyaan)
    {
        return response()->json($pertanyaan->load('opsis'));
    }

    /**
     * PUT /api/pertanyaan/{id}
     * Update pertanyaan + sinkronisasi opsi (replace strategy)
     */
    public function update(Request $request, Pertanyaan $pertanyaan)
    {
        $validated = $request->validate([
            'jenis_soal'            => 'required|in:seleksi,klasifikasi',
            'teks_pertanyaan'       => 'required|string',
            'bobot'                 => 'required|integer|min:1|max:100',
            'status'                => 'required|string|in:aktif,nonaktif',
            'opsis'                 => 'required|array|min:2',
            'opsis.*.teks_opsi'     => 'required|string|max:255',
            'opsis.*.nilai'         => 'required|integer|min:0|max:100',
        ]);

        $pertanyaan->update([
            'jenis_soal'      => $validated['jenis_soal'],
            'teks_pertanyaan' => $validated['teks_pertanyaan'],
            'bobot'           => $validated['bobot'],
            'status'          => $validated['status'],
        ]);

        // Hapus opsi lama, buat ulang
        $pertanyaan->opsis()->delete();
        foreach ($validated['opsis'] as $opsiData) {
            Opsi::create([
                'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                'teks_opsi'     => $opsiData['teks_opsi'],
                'nilai'         => $opsiData['nilai'],
            ]);
        }

        return response()->json($pertanyaan->load('opsis'));
    }

    /**
     * DELETE /api/pertanyaan/{id}
     */
    public function destroy(Pertanyaan $pertanyaan)
    {
        $pertanyaan->delete();
        return response()->json(['message' => 'Pertanyaan berhasil dihapus.']);
    }
}
