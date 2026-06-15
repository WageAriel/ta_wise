<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use App\Models\ProfilPetugas;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->load('profilPetugas');
        return Inertia::render('PetugasLapangan/Profile', [
            'user' => $user,
            'status' => session('status'),
            'message' => session('message'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'nama_petugas' => ['nullable', 'string', 'max:255'],
            'posisi' => ['nullable', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:255'],
        ]);

        $user->username = $validated['username'];
        $user->email = $validated['email'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $profil = ProfilPetugas::firstOrCreate(
            ['user_id' => $user->id]
        );

        $profil->update([
            'nama_petugas' => $validated['nama_petugas'],
            'posisi' => $validated['posisi'],
            'kontak' => $validated['kontak'],
        ]);

        return redirect()->back()->with('message', 'Profil berhasil diperbarui.');
    }
}
