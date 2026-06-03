<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Verifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // 1. Total Verifikasi Selesai
        $totalVerifikasi = Verifikasi::where('id_user_petugas', $userId)->count();

        // 2. Verifikasi Bulan Ini
        $bulanIni = Verifikasi::where('id_user_petugas', $userId)
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->count();

        // 3. Distribusi Rekomendasi (Class A, Class B, Class C, Belum Memenuhi)
        $distribusi = Verifikasi::where('id_user_petugas', $userId)
            ->selectRaw('rekomendasi_sistem, count(*) as count')
            ->groupBy('rekomendasi_sistem')
            ->pluck('count', 'rekomendasi_sistem')
            ->toArray();
            
        // 4. Aktivitas Terbaru (Timeline Singkat)
        $aktivitasTerbaru = Verifikasi::with(['klasifikasi.supplier'])
            ->where('id_user_petugas', $userId)
            ->latest('created_at') // urutkan berdasar kapan verifikasi dibuat
            ->take(5)
            ->get();

        return Inertia::render('PetugasLapangan/LaporanKinerja', [
            'stats' => [
                'total'      => $totalVerifikasi,
                'bulan_ini'  => $bulanIni,
                'distribusi' => [
                    'Class A'        => $distribusi['Class A'] ?? 0,
                    'Class B'        => $distribusi['Class B'] ?? 0,
                    'Class C'        => $distribusi['Class C'] ?? 0,
                    'Belum Memenuhi' => $distribusi['Belum Memenuhi'] ?? 0,
                ],
            ],
            'aktivitasTerbaru' => $aktivitasTerbaru
        ]);
    }
}
