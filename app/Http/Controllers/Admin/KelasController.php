<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KelasController extends Controller
{
    public function index()
    {
        $kelas = DB::table('kelas')->get();
        return Inertia::render('Admin/Settings/Kelas', [
            'kelas' => $kelas
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255'
        ]);

        DB::table('kelas')->insert([
            'nama_kelas' => $validated['nama_kelas'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255'
        ]);

        DB::table('kelas')->where('id_kelas', $id)->update([
            'nama_kelas' => $validated['nama_kelas'],
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Kelas berhasil diubah.');
    }

    public function destroy($id)
    {
        DB::table('kelas')->where('id_kelas', $id)->delete();
        return redirect()->back()->with('success', 'Kelas berhasil dihapus.');
    }
}
