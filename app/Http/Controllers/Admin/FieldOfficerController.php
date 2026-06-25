<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalKunjungan;
use App\Models\Klasifikasi;
use App\Models\ProfilPetugas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FieldOfficerController extends Controller
{
    public function index(Request $request)
    {
        $totalPetugas = User::where('role', 'petugas_lapangan')->count();
        $petugasAktif = User::where('role', 'petugas_lapangan')->where('is_active', true)->count();
        $jadwalKunjungan = JadwalKunjungan::count();
        $sedangBerlangsung = JadwalKunjungan::where('status', 'berlangsung')->count();
        $stats = [
            'total_petugas' => $totalPetugas,
            'petugas_aktif' => $petugasAktif,
            'jadwal_kunjungan' => $jadwalKunjungan,
            'sedang_berlangsung' => $sedangBerlangsung,
        ];
        // Data Jadwal dengan Pagination & Search
        $jadwalQuery = JadwalKunjungan::with(['klasifikasi.supplier', 'petugas.profilPetugas'])->latest();
        if ($request->filled('search_jadwal')) {
            $jadwalQuery->whereHas('klasifikasi.supplier', function ($q) use ($request) {
                $q->where('nama_perusahaan', 'like', '%' . $request->search_jadwal . '%');
            });
        }
        $jadwals = $jadwalQuery->paginate(10, ['*'], 'jadwal_page');
        // Data Petugas dengan Pagination & Search
        $petugasQuery = User::with('profilPetugas')
            ->where('role', 'petugas_lapangan')
            ->withCount([
                'jadwalKunjungan as supplier_aktif_count' => function ($query) {
                    $query->whereIn('status', ['menunggu', 'berlangsung']);
                },
                'jadwalKunjungan as selesai_count' => function ($query) {
                    $query->where('status', 'selesai');
                }
            ])
            ->latest();
        if ($request->filled('search_petugas')) {
            $petugasQuery->where('username', 'like', '%' . $request->search_petugas . '%');
        }
        $petugass = $petugasQuery->paginate(10, ['*'], 'petugas_page');
        $klasifikasiPending = Klasifikasi::with('supplier')
            ->whereDoesntHave('jadwalKunjungan')
            ->whereIn('status_klasifikasi', ['pending', 'diproses'])
            ->get();  
        // Dropdown Petugas Aktif
        $petugasList = User::with('profilPetugas')
            ->where('role', 'petugas_lapangan')
            ->where('is_active', true)
            ->get();

        return Inertia::render('Admin/FieldOfficers/Index', [
            'stats' => $stats,
            'jadwals' => $jadwals,
            'petugass' => $petugass,
            'klasifikasi_pending' => $klasifikasiPending,
            'petugas_list' => $petugasList,
            'filters' => $request->only(['search_jadwal', 'search_petugas'])
        ]);
    }

    public function storePetugas(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'nama_petugas' => 'nullable|string|max:255',
            'posisi' => 'nullable|string|max:255',
            'kontak' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'petugas_lapangan',
                'is_active' => true,
            ]);

            ProfilPetugas::create([
                'user_id' => $user->id,
                'nama_petugas' => $validated['nama_petugas'],
                'posisi' => $validated['posisi'],
                'kontak' => $validated['kontak'],
            ]);
        });

        return redirect()->back()->with('message', 'Petugas berhasil ditambahkan.');
    }

    public function storeJadwal(Request $request)
    {
        $validated = $request->validate([
            'id_klasifikasi' => 'required|exists:klasifikasi,id_klasifikasi',
            'id_user_petugas' => 'required|exists:users,id',
            'tanggal_kunjungan' => 'required|date',
            'waktu_kunjungan' => 'required',
        ]);

        // Validasi: Maksimal 2 jadwal sehari dan jeda minimal 4 jam
        $jadwalHariIni = JadwalKunjungan::where('id_user_petugas', $validated['id_user_petugas'])
            ->whereDate('tanggal_kunjungan', $validated['tanggal_kunjungan'])
            ->get();

        if ($jadwalHariIni->count() >= 2) {
            return redirect()->back()->withErrors(['waktu_kunjungan' => 'Petugas ini sudah mencapai batas maksimal 2 jadwal untuk hari tersebut.']);
        }

        $waktuBaru = \Carbon\Carbon::parse($validated['waktu_kunjungan']);
        foreach ($jadwalHariIni as $jadwal) {
            $waktuLama = \Carbon\Carbon::parse($jadwal->waktu_kunjungan);
            $selisihMenit = $waktuBaru->diffInMinutes($waktuLama);
            
            if ($selisihMenit < 240) { // 240 menit = 4 jam
                return redirect()->back()->withErrors(['waktu_kunjungan' => 'Jadwal bertabrakan! Jeda antar kunjungan harus minimal 4 jam. Jadwal sebelumnya pukul ' . $waktuLama->format('H:i') . '.']);
            }
        }

        JadwalKunjungan::create([
            'id_klasifikasi' => $validated['id_klasifikasi'],
            'id_user_petugas' => $validated['id_user_petugas'],
            'tanggal_kunjungan' => $validated['tanggal_kunjungan'],
            'waktu_kunjungan' => $validated['waktu_kunjungan'],
            'status' => 'menunggu'
        ]);
        
        // Update status klasifikasi menjadi diproses
        Klasifikasi::where('id_klasifikasi', $validated['id_klasifikasi'])
            ->update(['status_klasifikasi' => 'diproses']);

        return redirect()->back()->with('message', 'Jadwal kunjungan berhasil ditambahkan.');
    }

    public function updatePetugasProfile(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'nama_petugas' => 'nullable|string|max:255',
            'posisi' => 'nullable|string|max:255',
            'kontak' => 'nullable|string|max:255',
        ]);

        $profil = ProfilPetugas::firstOrCreate(
            ['user_id' => $user->id]
        );
        
        $profil->update([
            'nama_petugas' => $validated['nama_petugas'],
            'posisi' => $validated['posisi'],
            'kontak' => $validated['kontak'],
        ]);

        return redirect()->back()->with('message', 'Profil petugas berhasil diperbarui.');
    }
}
