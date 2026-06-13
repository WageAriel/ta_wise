<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Supplier;
use App\Models\POItemType;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->query('year', now()->year));

        // --- Statistik Utama ---
        $totalPo      = PurchaseOrder::count();
        $activePo     = PurchaseOrder::whereIn('status', [
            PurchaseOrder::STATUS_RFQ, PurchaseOrder::STATUS_VERIFICATION,
            PurchaseOrder::STATUS_REQUEST, PurchaseOrder::STATUS_COMPLETENESS,
            PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SHIPMENT,
        ])->count();
        $completedPo  = PurchaseOrder::where('status', PurchaseOrder::STATUS_COMPLETED)->count();
        $totalSuppliers = Supplier::count();
        $totalItemTypes = POItemType::count();

        $totalInbound = InboundItem::sum('qty');

        // --- Chart PO & Inbound per bulan dalam tahun dipilih ---
        $poByMonth = PurchaseOrder::selectRaw("MONTH(created_at) as bulan, COUNT(*) as jumlah")
            ->whereYear('created_at', $year)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()->keyBy('bulan');

        $inboundByMonth = Inbound::selectRaw("MONTH(tanggal) as bulan, SUM(isi.qty) as jumlah")
            ->join('inbound_items as isi', 'inbounds.id_inbound', '=', 'isi.id_inbound')
            ->whereYear('inbounds.tanggal', $year)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()->keyBy('bulan');

        $monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        $chartMonthly = collect();
        for ($m = 1; $m <= 12; $m++) {
            $chartMonthly->push([
                'label'   => $monthNames[$m - 1],
                'po'      => (int) ($poByMonth[$m]->jumlah ?? 0),
                'inbound' => (int) ($inboundByMonth[$m]->jumlah ?? 0),
            ]);
        }

        // --- Statistik per Item (barang + subtype) ---
        $itemStats = PurchaseOrderItem::with(['barang', 'subtype', 'itemType'])
            ->select('barang_id', 'id_subtype', 'id_item_type',
                DB::raw('COUNT(*) as jumlah_transaksi'),
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw("SUM(IF(purchase_orders.status = 'completed', final_quantity, 0)) as qty_selesai")
            )
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->whereYear('purchase_orders.created_at', $year)
            ->groupBy('barang_id', 'id_subtype', 'id_item_type')
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($item) {
                $name = $item->barang?->nama_barang ?? '-';
                $subtype = $item->subtype?->subtype_name ?? $item->itemType?->type_name ?? null;
                $uom = $item->uom ?? 'Unit';
                return [
                    'label'   => $subtype ? "{$name} - {$subtype}" : $name,
                    'uom'     => $uom,
                    'jumlah_transaksi' => (int) $item->jumlah_transaksi,
                    'total_qty'  => (int) $item->total_qty,
                    'qty_selesai' => (int) $item->qty_selesai,
                ];
            });

        $availableYears = PurchaseOrder::selectRaw('YEAR(created_at) as year')
            ->distinct()->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        return Inertia::render('Manajer/Dashboard', [
            'stats' => [
                'totalPo'       => $totalPo,
                'activePo'      => $activePo,
                'completedPo'   => $completedPo,
                'totalSuppliers' => $totalSuppliers,
                'totalItemTypes' => $totalItemTypes,
                'totalInbound'  => (int) $totalInbound,
            ],
            'chartMonthly' => $chartMonthly,
            'itemStats'    => $itemStats,
            'selectedYear' => $year,
            'availableYears' => $availableYears,
        ]);
    }

    public function stats(Request $request)
    {
        $mode  = $request->query('mode', 'year');   // year|month|day
        $year  = (int) ($request->query('year', now()->year));
        $month = (int) ($request->query('month', now()->month));
        $day   = $request->query('day', now()->toDateString());

        if ($mode === 'day') {
            $groups = PurchaseOrder::selectRaw("HOUR(created_at) as label, COUNT(*) as po")
                ->whereDate('created_at', $day)->groupBy('label')->orderBy('label')->get();
            $inboundGroups = Inbound::selectRaw("HOUR(tanggal) as label, SUM(isi.qty) as inbound")
                ->join('inbound_items as isi', 'inbounds.id_inbound', '=', 'isi.id_inbound')
                ->whereDate('inbounds.tanggal', $day)->groupBy('label')->orderBy('label')->get();
        } elseif ($mode === 'month') {
            $groups = PurchaseOrder::selectRaw("DAY(created_at) as label, COUNT(*) as po")
                ->whereYear('created_at', $year)->whereMonth('created_at', $month)
                ->groupBy('label')->orderBy('label')->get();
            $inboundGroups = Inbound::selectRaw("DAY(tanggal) as label, SUM(isi.qty) as inbound")
                ->join('inbound_items as isi', 'inbounds.id_inbound', '=', 'isi.id_inbound')
                ->whereYear('inbounds.tanggal', $year)->whereMonth('inbounds.tanggal', $month)
                ->groupBy('label')->orderBy('label')->get();
        } else {
            $groups = PurchaseOrder::selectRaw("MONTH(created_at) as label, COUNT(*) as po")
                ->whereYear('created_at', $year)->groupBy('label')->orderBy('label')->get();
            $inboundGroups = Inbound::selectRaw("MONTH(tanggal) as label, SUM(isi.qty) as inbound")
                ->join('inbound_items as isi', 'inbounds.id_inbound', '=', 'isi.id_inbound')
                ->whereYear('inbounds.tanggal', $year)->groupBy('label')->orderBy('label')->get();
        }

        $poMap      = $groups->keyBy('label');
        $inboundMap = $inboundGroups->keyBy('label');
        $allLabels  = $groups->pluck('label')->merge($inboundGroups->pluck('label'))->unique()->sort()->values();

        $chart = $allLabels->map(fn($l) => [
            'label'   => $l,
            'po'      => (int) ($poMap[$l]->po ?? 0),
            'inbound' => (int) ($inboundMap[$l]->inbound ?? 0),
        ]);

        return response()->json(['chart' => $chart]);
    }
}
