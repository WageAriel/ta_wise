<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\PurchaseOrder;
use App\Models\Inbound;
use App\Models\InboundItem;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
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

        return Inertia::render('Dashboard', [
            'stats' => [
                'poToday'    => $poToday,
                'poThisMonth' => $poThisMonth,
                'poTotal'    => $poTotal,
                'poActive'   => $poActive,
                'poCompleted' => $poCompleted,
                'inboundToday' => (int) $inboundToday,
                'inboundMonth' => (int) $inboundMonth,
            ],
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
