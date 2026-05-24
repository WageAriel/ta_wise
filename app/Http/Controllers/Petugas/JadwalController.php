<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\JadwalKunjungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class JadwalController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Statistik jadwal milik petugas ini
        $totalJadwal   = JadwalKunjungan::where('id_user_petugas', $userId)->count();
        $terjadwal     = JadwalKunjungan::where('id_user_petugas', $userId)
                            ->whereIn('status', ['menunggu', 'berlangsung'])
                            ->count();
        $selesai       = JadwalKunjungan::where('id_user_petugas', $userId)
                            ->where('status', 'selesai')
                            ->count();

        $stats = [
            'total'     => $totalJadwal,
            'terjadwal' => $terjadwal,
            'selesai'   => $selesai,
        ];

        // Query jadwal dengan filter status
        $filterStatus = $request->get('status', 'semua');

        $query = JadwalKunjungan::with(['klasifikasi.supplier'])
            ->where('id_user_petugas', $userId)
            ->latest('tanggal_kunjungan');

        if ($filterStatus === 'terjadwal') {
            $query->whereIn('status', ['menunggu', 'berlangsung']);
        } elseif ($filterStatus === 'selesai') {
            $query->where('status', 'selesai');
        }

        $jadwals = $query->get();

        return Inertia::render('PetugasLapangan/JadwalVerifikasi', [
            'stats'         => $stats,
            'jadwals'       => $jadwals,
            'filterStatus'  => $filterStatus,
        ]);
    }
}
