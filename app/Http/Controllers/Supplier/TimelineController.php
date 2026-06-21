<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Supplier;
use App\Models\Seleksi;
use App\Models\Klasifikasi;

class TimelineController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $supplier = Supplier::where('user_id', $user->id)->first();

        // Jika belum ada data supplier, kirim default
        if (!$supplier) {
            return Inertia::render('Supplier/Timeline', [
                'timeline' => $this->getDefaultTimeline(),
                'currentStep' => 0,
                'supplierName' => $user->username
            ]);
        }

        $seleksi = Seleksi::where('id_supplier', $supplier->id)->latest('tanggal')->first();
        $klasifikasi = Klasifikasi::with('verifikasi')->where('id_supplier', $supplier->id)->latest('tanggal')->first();

        $timeline = [];
        $currentStep = 0; // 0 sampai 5

        // TAHAP 1: Kelengkapan Profil
        $statusProfil = $supplier->status; // draft, menunggu review, approved, rejected
        $tahap1 = [
            'title' => 'Kelengkapan Profil',
            'description' => 'Pengisian data awal dan dokumen perusahaan.',
            'status' => 'pending',
            'date' => $supplier->created_at ? $supplier->created_at->format('d M Y') : null,
        ];
        if ($statusProfil === 'approved') {
            $tahap1['status'] = 'completed';
            $tahap1['description'] = 'Profil telah disetujui admin.';
            $currentStep = 1;
        } elseif ($statusProfil === 'menunggu review') {
            $tahap1['status'] = 'processing';
            $tahap1['description'] = 'Menunggu proses review dari admin.';
            $currentStep = 1; // Sudah berjalan
        } elseif ($statusProfil === 'rejected') {
            $tahap1['status'] = 'rejected';
            $tahap1['description'] = 'Profil ditolak: ' . $supplier->catatan_admin;
        } else {
            $tahap1['status'] = 'processing'; // draft
        }
        $timeline[] = $tahap1;

        // TAHAP 2: Seleksi Evaluasi
        $tahap2 = [
            'title' => 'Seleksi Evaluasi',
            'description' => 'Mengisi kuesioner kelayakan supplier (pra-syarat masuk vendor).',
            'status' => 'pending',
            'date' => $seleksi ? \Carbon\Carbon::parse($seleksi->tanggal)->format('d M Y') : null,
        ];
        if ($currentStep >= 1) {
            if ($seleksi) {
                if ($seleksi->status_seleksi === 'Lolos') {
                    $tahap2['status'] = 'completed';
                    $tahap2['description'] = 'Telah lolos seleksi evaluasi administrasi.';
                    $currentStep = 2;
                } elseif ($seleksi->status_seleksi === 'Tidak Lolos') {
                    $tahap2['status'] = 'rejected';
                    $tahap2['description'] = 'Tidak lolos evaluasi seleksi.';
                } else {
                    $tahap2['status'] = 'processing';
                    $tahap2['description'] = 'Menunggu validasi hasil seleksi.';
                    $currentStep = 2;
                }
            } elseif ($currentStep === 1 && $statusProfil === 'approved') {
                $tahap2['status'] = 'processing';
                $tahap2['description'] = 'Silakan mulai proses pengajuan seleksi.';
            }
        }
        $timeline[] = $tahap2;

        // TAHAP 3: Pengajuan Klasifikasi
        $tahap3 = [
            'title' => 'Pengajuan Klasifikasi',
            'description' => 'Mengisi kuesioner klasifikasi sistem skor nilai.',
            'status' => 'pending',
            'date' => $klasifikasi ? \Carbon\Carbon::parse($klasifikasi->tanggal)->format('d M Y') : null,
        ];
        if ($currentStep >= 2) {
            if ($klasifikasi) {
                $tahap3['status'] = 'completed';
                $tahap3['description'] = 'Kuesioner klasifikasi telah disubmit.';
                $currentStep = 3;
            } elseif ($currentStep === 2 && $seleksi && $seleksi->status_seleksi === 'Lolos') {
                $tahap3['status'] = 'processing';
                $tahap3['description'] = 'Silakan mulai pengajuan klasifikasi.';
            }
        }
        $timeline[] = $tahap3;

        // TAHAP 4: Verifikasi Lapangan
        $verifikasi = $klasifikasi ? $klasifikasi->verifikasi : null;
        $tahap4 = [
            'title' => 'Verifikasi Lapangan',
            'description' => 'Kunjungan petugas lapangan untuk mencocokkan data faktual.',
            'status' => 'pending',
            'date' => $verifikasi ? \Carbon\Carbon::parse($verifikasi->tanggal)->format('d M Y') : null,
        ];
        if ($currentStep >= 3) {
            if ($verifikasi) {
                if (in_array($verifikasi->status, ['menunggu_admin', 'selesai', 'disetujui', 'ditolak'])) {
                    $tahap4['status'] = 'completed';
                    $tahap4['description'] = 'Verifikasi lapangan selesai. Menunggu validasi admin.';
                    $currentStep = 4;
                } else {
                    $tahap4['status'] = 'processing';
                    $tahap4['description'] = 'Sedang dalam proses penjadwalan / kunjungan verifikasi.';
                    $currentStep = 4;
                }
            } else {
                $tahap4['status'] = 'processing';
                $tahap4['description'] = 'Menunggu penjadwalan verifikasi lapangan oleh petugas.';
            }
        }
        $timeline[] = $tahap4;

        // TAHAP 5: Keputusan Akhir (Kelas)
        $tahap5 = [
            'title' => 'Hasil Akhir (Klasifikasi Kelas)',
            'description' => 'Penetapan kelas akhir (A/B/C) oleh Admin berdasarkan semua penilaian.',
            'status' => 'pending',
            'date' => ($verifikasi && $verifikasi->updated_at) ? $verifikasi->updated_at->format('d M Y') : null,
            'result' => null
        ];
        if ($currentStep >= 4) {
            if ($verifikasi && $verifikasi->keputusan_admin) {
                $tahap5['status'] = 'completed';
                $tahap5['description'] = 'Selamat! Supplier Anda telah diklasifikasikan dengan kelas ' . $verifikasi->keputusan_admin . '.';
                $tahap5['result'] = $verifikasi->keputusan_admin;
                $currentStep = 5;
            } elseif ($verifikasi && $verifikasi->status === 'ditolak') {
                $tahap5['status'] = 'rejected';
                $tahap5['description'] = 'Maaf, pengajuan ditolak.';
                $tahap5['result'] = 'Ditolak';
            } else {
                $tahap5['status'] = 'processing';
                $tahap5['description'] = 'Menunggu keputusan final dari Manager/Admin.';
            }
        }
        $timeline[] = $tahap5;

        return Inertia::render('Supplier/Timeline', [
            'timeline' => $timeline,
            'currentStep' => $currentStep,
            'supplierName' => $supplier->nama_perusahaan
        ]);
    }

    private function getDefaultTimeline()
    {
        return [
            ['title' => 'Kelengkapan Profil', 'description' => 'Pengisian data awal dan dokumen perusahaan.', 'status' => 'pending', 'date' => null],
            ['title' => 'Seleksi Evaluasi', 'description' => 'Mengisi kuesioner kelayakan supplier (pra-syarat masuk vendor).', 'status' => 'pending', 'date' => null],
            ['title' => 'Pengajuan Klasifikasi', 'description' => 'Mengisi kuesioner klasifikasi sistem skor nilai.', 'status' => 'pending', 'date' => null],
            ['title' => 'Verifikasi Lapangan', 'description' => 'Kunjungan petugas lapangan untuk mencocokkan data faktual.', 'status' => 'pending', 'date' => null],
            ['title' => 'Hasil Akhir (Klasifikasi Kelas)', 'description' => 'Penetapan kelas akhir (A/B/C) oleh Admin berdasarkan semua penilaian.', 'status' => 'pending', 'date' => null],
        ];
    }
}
