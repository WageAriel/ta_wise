<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppSettingController extends Controller
{
    public function index()
    {
        $settings = AppSetting::pluck('value', 'key')->toArray();
        return Inertia::render('Admin/Settings/General', [
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'system_name' => 'nullable|string',
            'theme_color' => 'nullable|string',
            'system_logo' => 'nullable|image|max:2048',
            'minimal_skor_lulus' => 'required|numeric|min:0|max:100',
        ]);

        $data = $request->only(['system_name', 'theme_color', 'minimal_skor_lulus']);

        if ($request->hasFile('system_logo')) {
            $file = $request->file('system_logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/logos'), $filename);
            $data['system_logo'] = '/uploads/logos/' . $filename;
        }

        foreach ($data as $key => $value) {
            if ($value !== null) {
                AppSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
