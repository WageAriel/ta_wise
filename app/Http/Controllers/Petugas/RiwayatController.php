<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Verifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Ambil riwayat verifikasi yang sudah selesai dikerjakan oleh petugas ini
        $query = Verifikasi::with(['klasifikasi.supplier', 'admin'])
            ->where('id_user_petugas', $userId)
            ->latest('tanggal');
            
        // Filter Pencarian
        if ($request->filled('search')) {
            $query->whereHas('klasifikasi.supplier', function ($q) use ($request) {
                $q->where('nama_perusahaan', 'like', '%' . $request->search . '%');
            });
        }
        
        $riwayats = $query->paginate(10);

        return Inertia::render('PetugasLapangan/RiwayatVerifikasi', [
            'riwayats' => $riwayats,
            'filters'  => $request->only(['search'])
        ]);
    }
}
