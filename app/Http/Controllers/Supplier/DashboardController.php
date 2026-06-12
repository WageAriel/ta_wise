<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Supplier;
use App\Models\Seleksi;
use App\Models\Klasifikasi;
use App\Models\PurchaseOrder;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $supplier = Supplier::where('user_id', $user->id)->first();

        // 1. Inisialisasi statistik default
        $stats = [
            'totalPo' => 0,
            'activePo' => 0,
            'completedPo' => 0,
            'profileStatus' => $supplier ? ucfirst($supplier->status) : 'Belum Melengkapi Profil',
            'selectionStatus' => 'Belum Mengajukan',
            'classificationStatus' => 'Belum Mengajukan',
        ];

        $logs = collect();

        if ($supplier) {
            // 2. Query data terkait
            $pos = PurchaseOrder::where('supplier_id', $supplier->id)->get();
            $seleksis = Seleksi::where('id_supplier', $supplier->id)->get();
            $klasifikasis = Klasifikasi::with(['verifikasi'])->where('id_supplier', $supplier->id)->get();

            // 3. Hitung statistik
            $stats['totalPo'] = $pos->count();
            $stats['activePo'] = $pos->whereIn('status', [
                PurchaseOrder::STATUS_RFQ,
                PurchaseOrder::STATUS_VERIFICATION,
                PurchaseOrder::STATUS_REQUEST,
                PurchaseOrder::STATUS_COMPLETENESS,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SHIPMENT,
            ])->count();
            $stats['completedPo'] = $pos->where('status', PurchaseOrder::STATUS_COMPLETED)->count();

            // Status Seleksi Terkini
            $latestSeleksi = $seleksis->sortByDesc('tanggal')->first();
            if ($latestSeleksi) {
                $stats['selectionStatus'] = $latestSeleksi->status_seleksi;
            }

            // Status Klasifikasi Terkini
            $latestKlasifikasi = $klasifikasis->sortByDesc('tanggal')->first();
            if ($latestKlasifikasi) {
                if ($latestKlasifikasi->status_klasifikasi === 'selesai' && $latestKlasifikasi->verifikasi) {
                    $stats['classificationStatus'] = $latestKlasifikasi->verifikasi->keputusan_admin ?: 'Selesai';
                } else {
                    $stats['classificationStatus'] = ucfirst($latestKlasifikasi->status_klasifikasi);
                }
            }

            // 4. Bangun log aktivitas secara dinamis
            
            // Log Profil Perusahaan
            $logs->push([
                'type' => 'profile',
                'title' => 'Profil Perusahaan Dibuat',
                'description' => 'Menyelesaikan pengisian profil awal perusahaan: ' . $supplier->nama_perusahaan,
                'time' => $supplier->created_at ? $supplier->created_at->toIso8601String() : now()->toIso8601String(),
                'status' => 'success',
            ]);

            if ($supplier->reviewed_at) {
                $logs->push([
                    'type' => 'profile',
                    'title' => 'Profil Perusahaan Ditinjau',
                    'description' => 'Admin telah meninjau profil perusahaan Anda dengan status: ' . ucfirst($supplier->status) . ($supplier->catatan_admin ? ' (Catatan: ' . $supplier->catatan_admin . ')' : ''),
                    'time' => $supplier->reviewed_at->toIso8601String(),
                    'status' => $supplier->status === 'approved' ? 'success' : 'danger',
                ]);
            }

            // Log Seleksi
            foreach ($seleksis as $sel) {
                $logs->push([
                    'type' => 'selection',
                    'title' => 'Mengajukan Evaluasi Seleksi',
                    'description' => 'Mengirim berkas kuesioner seleksi dengan total skor: ' . $sel->total_nilai,
                    'time' => Carbon::parse($sel->tanggal)->startOfDay()->toIso8601String(),
                    'status' => 'info',
                ]);

                if ($sel->status_seleksi !== 'Menunggu Validasi') {
                    $logs->push([
                        'type' => 'selection',
                        'title' => 'Hasil Evaluasi Seleksi',
                        'description' => 'Evaluasi supplier telah divalidasi dengan hasil: ' . $sel->status_seleksi,
                        'time' => Carbon::parse($sel->tanggal)->endOfDay()->toIso8601String(),
                        'status' => $sel->status_seleksi === 'Lolos' ? 'success' : 'danger',
                    ]);
                }
            }

            // Log Klasifikasi
            foreach ($klasifikasis as $klas) {
                $logs->push([
                    'type' => 'classification',
                    'title' => 'Mengajukan Klasifikasi Kelas',
                    'description' => 'Mengirimkan kuesioner klasifikasi supplier dengan total nilai: ' . $klas->total_nilai,
                    'time' => Carbon::parse($klas->tanggal)->startOfDay()->toIso8601String(),
                    'status' => 'info',
                ]);

                if ($klas->status_klasifikasi === 'selesai' && $klas->verifikasi) {
                    $logs->push([
                        'type' => 'classification',
                        'title' => 'Klasifikasi Kelas Disetujui',
                        'description' => 'Validasi akhir klasifikasi diselesaikan dengan keputusan kelas: ' . ($klas->verifikasi->keputusan_admin ?: 'Selesai'),
                        'time' => $klas->updated_at ? $klas->updated_at->toIso8601String() : now()->toIso8601String(),
                        'status' => 'success',
                    ]);
                } elseif ($klas->status_klasifikasi === 'ditolak') {
                    $logs->push([
                        'type' => 'classification',
                        'title' => 'Klasifikasi Kelas Ditolak',
                        'description' => 'Pengajuan klasifikasi kelas ditolak oleh admin.',
                        'time' => $klas->updated_at ? $klas->updated_at->toIso8601String() : now()->toIso8601String(),
                        'status' => 'danger',
                    ]);
                }
            }

            // Log Purchase Order
            foreach ($pos as $po) {
                // PO Dibuat / RFQ dikirim oleh admin
                $logs->push([
                    'type' => 'po',
                    'title' => 'Menerima Permintaan Purchase Order',
                    'description' => 'Menerima permintaan Purchase Order baru dengan nomor: ' . $po->po_number,
                    'time' => $po->created_at ? $po->created_at->toIso8601String() : now()->toIso8601String(),
                    'status' => 'warning',
                ]);

                // Status verifikasi
                if (in_array($po->status, [PurchaseOrder::STATUS_VERIFICATION, PurchaseOrder::STATUS_COMPLETENESS, PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SHIPMENT, PurchaseOrder::STATUS_COMPLETED])) {
                    $logs->push([
                        'type' => 'po',
                        'title' => 'Menyetujui Penawaran PO',
                        'description' => 'Menyetujui harga penawaran Purchase Order: ' . $po->po_number,
                        'time' => $po->updated_at ? $po->updated_at->toIso8601String() : now()->toIso8601String(),
                        'status' => 'success',
                    ]);
                }

                // Counter offer
                if ($po->status === PurchaseOrder::STATUS_REQUEST) {
                    $logs->push([
                        'type' => 'po',
                        'title' => 'Mengajukan Counter Offer PO',
                        'description' => 'Mengajukan tawaran harga atau kuantitas baru untuk PO: ' . $po->po_number,
                        'time' => $po->updated_at ? $po->updated_at->toIso8601String() : now()->toIso8601String(),
                        'status' => 'info',
                    ]);
                }

                // Upload Dokumen Kelengkapan
                if ($po->document_path && in_array($po->status, [PurchaseOrder::STATUS_COMPLETENESS, PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SHIPMENT, PurchaseOrder::STATUS_COMPLETED])) {
                    $logs->push([
                        'type' => 'po',
                        'title' => 'Mengunggah Kelengkapan Dokumen PO',
                        'description' => 'Telah mengunggah berkas penawaran/persyaratan kelengkapan PO: ' . $po->po_number,
                        'time' => $po->updated_at ? $po->updated_at->toIso8601String() : now()->toIso8601String(),
                        'status' => 'success',
                    ]);
                }

                // Kirim Barang / Shipment
                if ($po->shipped_at) {
                    $logs->push([
                        'type' => 'po',
                        'title' => 'Mengirim Barang PO (Shipment)',
                        'description' => 'Barang untuk PO ' . $po->po_number . ' telah dikirim melalui ' . ($po->carrier ?: 'Ekspedisi') . ' dengan nomor resi/tracking: ' . ($po->tracking_number ?: '-'),
                        'time' => $po->shipped_at->toIso8601String(),
                        'status' => 'info',
                    ]);
                }

                // Selesai / Diterima
                if ($po->status === PurchaseOrder::STATUS_COMPLETED) {
                    $logs->push([
                        'type' => 'po',
                        'title' => 'Purchase Order Selesai',
                        'description' => 'Barang dari PO ' . $po->po_number . ' telah sampai dan diterima dengan sukses oleh gudang utama.',
                        'time' => $po->delivered_at ? $po->delivered_at->toIso8601String() : ($po->updated_at ? $po->updated_at->toIso8601String() : now()->toIso8601String()),
                        'status' => 'success',
                    ]);
                }

                // Batal / Tolak
                if ($po->status === PurchaseOrder::STATUS_REJECTED) {
                    $logs->push([
                        'type' => 'po',
                        'title' => 'Purchase Order Ditolak',
                        'description' => 'Penawaran Purchase Order ' . $po->po_number . ' ditolak.',
                        'time' => $po->updated_at ? $po->updated_at->toIso8601String() : now()->toIso8601String(),
                        'status' => 'danger',
                    ]);
                }
            }
        }

        // Urutkan log berdasarkan waktu terbaru
        $sortedLogs = $logs->sortByDesc('time')->values();

        return Inertia::render('Supplier/Dashboard', [
            'stats' => $stats,
            'logs' => $sortedLogs,
            'supplierName' => $supplier ? $supplier->nama_perusahaan : $user->username
        ]);
    }
}
