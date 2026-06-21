<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Seleksi;
use App\Models\Barang;
use App\Models\Layout;
use App\Models\Location;
use Carbon\Carbon;

class DemoSidangSeeder extends Seeder
{
    public function run(): void
    {
        // 1. DATA SUPPLIER
        
        // --- 1A. PT. Global Microchips (Approved, Lolos Seleksi) ---
        // Skenario: Cocok untuk demo pengajuan Klasifikasi & Verifikasi Lapangan
        $userGlobal = User::create([
            'username' => 'global_micro',
            'email' => 'admin@globalmicro.com',
            'password' => Hash::make('password'),
            'role' => 'supplier',
            'is_active' => true,
        ]);
        
        $supGlobal = Supplier::create([
            'user_id' => $userGlobal->id,
            'nama_perusahaan' => 'PT. Global Microchips',
            'no_telp_perusahaan' => '021-9876543',
            'alamat_perusahaan' => 'Jl. Industri Teknologi No.1, Cikarang',
            'email_perusahaan' => 'contact@globalmicro.com',
            'nama_pic' => 'Budi Santoso',
            'no_telp_pic' => '081234567890',
            'email_pic' => 'budi@globalmicro.com',
            'nama_bank' => 'Bank Mandiri',
            'no_rekening' => '123-456-789-012',
            'atas_nama' => 'PT Global Microchips',
            'tahun_periode' => 2026,
            'status' => 'approved',
            'catatan_admin' => 'Dokumen lengkap dan valid. Lulus verifikasi administrasi tahap awal.',
            'submitted_at' => Carbon::now()->subDays(6),
            'reviewed_at' => Carbon::now()->subDays(5),
        ]);

        Seleksi::create([
            'id_user' => $userGlobal->id,
            'id_supplier' => $supGlobal->id,
            'id_soal' => 1,
            'tanggal' => Carbon::now()->subDays(3)->toDateString(),
            'status_seleksi' => 'Lolos',
            'total_nilai' => 85,
        ]);

        // --- 1B. CV. Surya Kabel (Approved, Lolos Seleksi) ---
        // Skenario: Sebagai data pelengkap di dashboard.
        $userSurya = User::create([
            'username' => 'surya_kabel',
            'email' => 'info@suryakabel.com',
            'password' => Hash::make('password'),
            'role' => 'supplier',
            'is_active' => true,
        ]);
        
        $supSurya = Supplier::create([
            'user_id' => $userSurya->id,
            'nama_perusahaan' => 'CV. Surya Kabel',
            'no_telp_perusahaan' => '024-1234567',
            'alamat_perusahaan' => 'Kawasan Industri Terboyo, Semarang',
            'email_perusahaan' => 'info@suryakabel.com',
            'nama_pic' => 'Agus Surya',
            'no_telp_pic' => '08987654321',
            'email_pic' => 'agus@suryakabel.com',
            'nama_bank' => 'BCA',
            'no_rekening' => '0987654321',
            'atas_nama' => 'CV Surya Kabel',
            'tahun_periode' => 2026,
            'status' => 'approved',
            'catatan_admin' => 'Dokumen valid, siap mengikuti tahap seleksi dan klasifikasi.',
            'submitted_at' => Carbon::now()->subDays(3),
            'reviewed_at' => Carbon::now()->subDays(2),
        ]);

        Seleksi::create([
            'id_user' => $userSurya->id,
            'id_supplier' => $supSurya->id,
            'id_soal' => 1,
            'tanggal' => Carbon::now()->subDays(1)->toDateString(),
            'status_seleksi' => 'Lolos',
            'total_nilai' => 75,
        ]);

        // --- 1C. UD. Maju Komponen (Draft) ---
        // Skenario: Cocok untuk demo "Review Profil Supplier" oleh Admin atau demo isi Seleksi.
        $userMaju = User::create([
            'username' => 'maju_komponen',
            'email' => 'majukom@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'supplier',
            'is_active' => true,
        ]);
        
        Supplier::create([
            'user_id' => $userMaju->id,
            'nama_perusahaan' => 'UD. Maju Komponen',
            'no_telp_perusahaan' => '021-3334445',
            'alamat_perusahaan' => 'Pasar Glodok Blok A No. 12, Jakarta',
            'email_perusahaan' => 'majukom@gmail.com',
            'nama_pic' => 'Hendra',
            'no_telp_pic' => '087711223344',
            'email_pic' => 'hendra@majukom.com',
            'nama_bank' => 'BNI',
            'no_rekening' => '1122334455',
            'atas_nama' => 'Hendra',
            'tahun_periode' => 2026,
            'status' => 'menunggu review',
            'submitted_at' => Carbon::now(),
        ]);

        // 2. DATA BARANG (INVENTORY)
        Barang::create([
            'nama_barang' => 'Motherboard XYZ-99',
            'satuan' => 'Pcs',
            'status' => 'aktif',
            'min_stock' => 50,
            'max_stock' => 500,
        ]);

        Barang::create([
            'nama_barang' => 'Kabel Power AC SNI',
            'satuan' => 'Roll',
            'status' => 'aktif',
            'min_stock' => 100,
            'max_stock' => 2000,
        ]);

        // 3. LAYOUT & LOCATION GUDANG
        $layoutA = Layout::create([
            'nama_layout' => 'Gudang Utama - Rak A',
        ]);

        Location::create([
            'id_layout' => $layoutA->id_layout,
            'kode_location' => 'Rak A-1 (Motherboard)',
            'kapasitas' => 1000,
        ]);

        $layoutB = Layout::create([
            'nama_layout' => 'Gudang Barang Kasar - Rak B',
        ]);

        Location::create([
            'id_layout' => $layoutB->id_layout,
            'kode_location' => 'Rak B-2 (Kabel)',
            'kapasitas' => 100, // Sengaja diset kecil (100) untuk mendemokan pesan error saat Inbound
        ]);

        $this->command->info('✅ Demo Sidang Seeder (Supplier Fiktif, Barang Elektronik, Gudang) berhasil di-seed!');
    }
}
