<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\PurchaseOrder;
use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Supplier;
use App\Models\Seleksi;
use App\Models\Klasifikasi;
use App\Models\Outbound;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user) {
            if ($user->role === 'supplier') {
                return redirect()->route('supplier.dashboard');
            }
            if ($user->role === 'manajer') {
                return redirect()->route('manajer.dashboard');
            }
            if ($user->role === 'petugas_lapangan') {
                return redirect()->route('petugas.dashboard.petugas');
            }
        }

        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Ringkasan PO
        $poToday    = PurchaseOrder::whereDate('created_at', $today)->count();
        $poThisMonth = PurchaseOrder::whereBetween('created_at', [$thisMonth, Carbon::now()])->count();
        $poTotal    = PurchaseOrder::count();
        $poActive   = PurchaseOrder::whereIn('status', [
            PurchaseOrder::STATUS_RFQ, PurchaseOrder::STATUS_VERIFICATION,
            PurchaseOrder::STATUS_REQUEST, PurchaseOrder::STATUS_COMPLETENESS,
            PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SHIPMENT,
        ])->count();
        $poCompleted = PurchaseOrder::where('status', PurchaseOrder::STATUS_COMPLETED)->count();

        // Barang Masuk (Inbound Items)
        $inboundToday = InboundItem::whereHas('inbound', fn($q) => $q->whereDate('tanggal', $today))->sum('qty');
        $inboundMonth = InboundItem::whereHas('inbound', fn($q) => $q->whereBetween('tanggal', [$thisMonth->toDateString(), $today->toDateString()]))->sum('qty');

        // Data chart PO per tanggal (30 hari terakhir)
        $poByDate = PurchaseOrder::selectRaw('DATE(created_at) as tanggal, COUNT(*) as jumlah')
            ->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        // Data chart inbound per tanggal (30 hari terakhir)
        $inboundByDate = Inbound::selectRaw('DATE(tanggal) as tgl, SUM(isi.qty) as jumlah')
            ->join('inbound_items as isi', 'inbounds.id_inbound', '=', 'isi.id_inbound')
            ->where('inbounds.tanggal', '>=', Carbon::now()->subDays(29)->toDateString())
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->get()
            ->keyBy('tgl');

        // Isi gap hari-hari yang kosong
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $days->push([
                'date'    => $date,
                'po'      => (int) ($poByDate[$date]->jumlah ?? 0),
                'inbound' => (int) ($inboundByDate[$date]->jumlah ?? 0),
            ]);
        }

        // Data chart PO per bulan (12 bulan terakhir)
        $poByMonth = PurchaseOrder::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bulan, COUNT(*) as jumlah")
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $inboundByMonth = Inbound::selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(isi.qty) as jumlah")
            ->join('inbound_items as isi', 'inbounds.id_inbound', '=', 'isi.id_inbound')
            ->where('inbounds.tanggal', '>=', Carbon::now()->subMonths(11)->startOfMonth()->toDateString())
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i)->format('Y-m');
            $months->push([
                'month'   => Carbon::now()->subMonths($i)->isoFormat('MMM YYYY'),
                'po'      => (int) ($poByMonth[$m]->jumlah ?? 0),
                'inbound' => (int) ($inboundByMonth[$m]->jumlah ?? 0),
            ]);
        }

        // Data Menunggu Persetujuan
        $pendingSupplier = Supplier::where('status', 'menunggu review')->count();
        $pendingSeleksi = Seleksi::where('status_seleksi', 'Menunggu Validasi')->count();
        $pendingKlasifikasi = Klasifikasi::whereIn('status_klasifikasi', ['pending', 'diproses'])->count();

        // Log Aktivitas
        $activities = collect();

        // PO
        PurchaseOrder::latest('updated_at')->take(5)->get()->each(function($po) use ($activities) {
            $activities->push([
                'type' => 'po',
                'title' => 'Purchase Order ' . $po->po_number,
                'description' => 'Status PO diperbarui: ' . $po->status,
                'date' => $po->updated_at,
                'url' => route('admin.purchase-orders.index'),
            ]);
        });

        // Supplier
        Supplier::where('status', 'approved')->latest('updated_at')->take(5)->get()->each(function($sup) use ($activities) {
            $activities->push([
                'type' => 'supplier',
                'title' => 'Supplier Disetujui',
                'description' => $sup->nama_perusahaan . ' telah disetujui.',
                'date' => $sup->updated_at,
                'url' => route('admin.supplier.index'),
            ]);
        });

        // Seleksi
        Seleksi::whereIn('status_seleksi', ['Lolos', 'Tidak Lolos'])->with('supplier')->latest('updated_at')->take(5)->get()->each(function($sel) use ($activities) {
            $activities->push([
                'type' => 'seleksi',
                'title' => 'Validasi Seleksi',
                'description' => 'Seleksi ' . ($sel->supplier->nama_perusahaan ?? 'Supplier') . ' divalidasi (' . $sel->status_seleksi . ')',
                'date' => $sel->updated_at,
                'url' => route('admin.supplier.selection.index'),
            ]);
        });

        // Klasifikasi
        Klasifikasi::whereIn('status_klasifikasi', ['disetujui', 'ditolak'])->with('supplier')->latest('updated_at')->take(5)->get()->each(function($klas) use ($activities) {
            $activities->push([
                'type' => 'klasifikasi',
                'title' => 'Validasi Klasifikasi',
                'description' => 'Klasifikasi ' . ($klas->supplier->nama_perusahaan ?? 'Supplier') . ' divalidasi (' . $klas->status_klasifikasi . ')',
                'date' => $klas->updated_at,
                'url' => route('admin.supplier.classification'),
            ]);
        });

        // Inbound
        Inbound::latest('created_at')->take(5)->get()->each(function($in) use ($activities) {
            $activities->push([
                'type' => 'inbound',
                'title' => 'Barang Masuk',
                'description' => 'Inbound ' . $in->no_inbound . ' dicatat.',
                'date' => $in->created_at,
                'url' => route('admin.inbound'),
            ]);
        });

        // Outbound
        Outbound::latest('created_at')->take(5)->get()->each(function($out) use ($activities) {
            $activities->push([
                'type' => 'outbound',
                'title' => 'Barang Keluar',
                'description' => 'Outbound ' . $out->no_outbound . ' dicatat.',
                'date' => $out->created_at,
                'url' => route('admin.outbound'),
            ]);
        });

        // Sortir descending
        $activities = $activities->sortByDesc('date')->values();

        return Inertia::render('Dashboard', [
            'stats' => [
                'poToday'    => $poToday,
                'poThisMonth' => $poThisMonth,
                'poTotal'    => $poTotal,
                'poActive'   => $poActive,
                'poCompleted' => $poCompleted,
                'inboundToday' => (int) $inboundToday,
                'inboundMonth' => (int) $inboundMonth,
                'pendingSupplier' => $pendingSupplier,
                'pendingSeleksi' => $pendingSeleksi,
                'pendingKlasifikasi' => $pendingKlasifikasi,
            ],
            'activities' => $activities,
            'chartByDate'  => $days,
            'chartByMonth' => $months,
        ]);
    }

    public function stats(Request $request)
    {
        $period = $request->query('period', 'month'); // today|month
        $today  = Carbon::today();
        $start  = $period === 'today' ? $today : Carbon::now()->startOfMonth();

        $po      = PurchaseOrder::whereBetween('created_at', [$start, Carbon::now()])->count();
        $inbound = InboundItem::whereHas('inbound', fn($q) =>
            $q->whereBetween('tanggal', [$start->toDateString(), $today->toDateString()])
        )->sum('qty');

        return response()->json(compact('po', 'inbound'));
    }
}
