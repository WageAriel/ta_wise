<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\JadwalKunjungan;
use App\Models\Verifikasi;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Stats
        $jadwalMingguIni = JadwalKunjungan::where('id_user_petugas', $user->id)
            ->whereBetween('tanggal_kunjungan', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();

        $verifikasiHariIni = Verifikasi::where('id_user_petugas', $user->id)
            ->whereDate('tanggal', Carbon::today())
            ->count();

        // Verifikasi selesai bulan ini
        // Asumsi status menunggu_admin atau lainnya berarti sudah diverifikasi oleh petugas
        $selesaiBulanIni = Verifikasi::where('id_user_petugas', $user->id)
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->count();

        // Verifikasi yang butuh tindak lanjut / menunggu
        // Jika Verifikasi sudah terbuat dengan status menunggu_admin, berarti petugas sudah selesai.
        // Jadwal yang "menunggu" verifikasi bagi petugas adalah jadwal yang belum selesai.
        $menungguValidasi = JadwalKunjungan::where('id_user_petugas', $user->id)
            ->where('status', '!=', 'selesai')
            ->where('tanggal_kunjungan', '<=', Carbon::today()) // Hari ini atau lewat
            ->count();

        // Jadwal Mendatang
        $jadwalMendatang = JadwalKunjungan::with('klasifikasi.supplier')
            ->where('id_user_petugas', $user->id)
            ->where('tanggal_kunjungan', '>=', Carbon::today())
            ->where('status', '!=', 'selesai')
            ->orderBy('tanggal_kunjungan', 'asc')
            ->orderBy('waktu_kunjungan', 'asc')
            ->take(3)
            ->get()
            ->map(function ($jadwal) {
                return [
                    'id' => $jadwal->id,
                    'supplier' => $jadwal->klasifikasi->supplier->nama_perusahaan ?? 'Unknown Supplier',
                    'tanggal' => $jadwal->tanggal_kunjungan,
                    'waktu' => \Carbon\Carbon::parse($jadwal->waktu_kunjungan)->format('H:i') . ' WIB',
                    'lokasi' => $jadwal->klasifikasi->supplier->kota ?? 'Lokasi tidak diketahui',
                    'status' => $jadwal->status,
                ];
            });

        // Verifikasi Terbaru
        $verifikasiTerbaru = Verifikasi::with('klasifikasi.supplier')
            ->where('id_user_petugas', $user->id)
            ->orderBy('tanggal', 'desc')
            ->take(3)
            ->get()
            ->map(function ($verifikasi) {
                return [
                    'id' => $verifikasi->id_verifikasi,
                    'supplier' => $verifikasi->klasifikasi->supplier->nama_perusahaan ?? 'Unknown Supplier',
                    'tanggal' => $verifikasi->tanggal,
                    'nilai' => $verifikasi->total_nilai,
                    'hasil' => $verifikasi->rekomendasi_sistem ?? 'Menunggu',
                ];
            });

        return Inertia::render('PetugasLapangan/DashboardPetugas', [
            'stats' => [
                'jadwal_minggu_ini' => $jadwalMingguIni,
                'verifikasi_hari_ini' => $verifikasiHariIni,
                'selesai_bulan_ini' => $selesaiBulanIni,
                'menunggu_verifikasi' => $menungguValidasi,
            ],
            'jadwalMendatang' => $jadwalMendatang,
            'verifikasiTerbaru' => $verifikasiTerbaru,
        ]);
    }
}
