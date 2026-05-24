<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SoalSeleksiSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Buat Header Paket Soal
            $id_soal = DB::table('header_soal')->insertGetId([
                'nama_soal' => 'Bank Soal Tahap Seleksi - Versi Ketat',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $sections = [
                // SECTION 1: Operasional Dasar (10 Soal)
                [
                    'per' => 'Apakah perusahaan Anda memiliki gudang atau fasilitas penyimpanan sendiri?',
                    'opsi' => [
                        ['t' => 'Ya, milik sendiri dan memadai', 'n' => 100],
                        ['t' => 'Ya, sewa jangka panjang', 'n' => 75],
                        ['t' => 'Menggunakan pihak ketiga', 'n' => 50],
                        ['t' => 'Tidak memiliki / tidak tetap', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Berapa lama lead time rata-rata dari PO sampai barang siap kirim?',
                    'opsi' => [
                        ['t' => 'Maksimal 5 hari', 'n' => 100],
                        ['t' => '6 – 10 hari', 'n' => 75],
                        ['t' => '11 – 20 hari', 'n' => 50],
                        ['t' => 'Lebih dari 20 hari', 'n' => 25],
                    ]
                ],
                [
                    'per' => 'Seberapa konsisten Anda memenuhi pesanan rutin setiap bulan?',
                    'opsi' => [
                        ['t' => 'Sangat konsisten', 'n' => 100],
                        ['t' => 'Cukup konsisten', 'n' => 75],
                        ['t' => 'Kadang tidak terpenuhi', 'n' => 25],
                        ['t' => 'Tidak konsisten', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Status rekening bank perusahaan?',
                    'opsi' => [
                        ['t' => 'Atas nama perusahaan & aktif', 'n' => 100],
                        ['t' => 'Atas nama perusahaan tapi jarang digunakan', 'n' => 50],
                        ['t' => 'Atas nama pribadi', 'n' => 25],
                        ['t' => 'Tidak memiliki', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Apakah Anda siap melayani repeat order dalam jumlah besar secara rutin?',
                    'opsi' => [
                        ['t' => 'Ya, sangat siap', 'n' => 100],
                        ['t' => 'Ya, dengan kapasitas terbatas', 'n' => 75],
                        ['t' => 'Belum siap sepenuhnya', 'n' => 50],
                        ['t' => 'Tidak siap', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Sistem pencatatan stok barang yang digunakan?',
                    'opsi' => [
                        ['t' => 'Digital / ERP / Software', 'n' => 100],
                        ['t' => 'Manual yang terstruktur', 'n' => 75],
                        ['t' => 'Manual sederhana', 'n' => 50],
                        ['t' => 'Tidak ada sistem', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Berapa jumlah karyawan tetap saat ini?',
                    'opsi' => [
                        ['t' => 'Lebih dari 30 orang', 'n' => 100],
                        ['t' => '10 – 30 orang', 'n' => 75],
                        ['t' => '1 – 9 orang', 'n' => 50],
                        ['t' => 'Tidak memiliki karyawan tetap', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kondisi alamat kantor & gudang?',
                    'opsi' => [
                        ['t' => 'Tetap, jelas, dan dapat dikunjungi', 'n' => 100],
                        ['t' => 'Tetap tapi sulit dijangkau', 'n' => 50],
                        ['t' => 'Sering berpindah', 'n' => 25],
                        ['t' => 'Tidak jelas / virtual', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kemampuan menangani kenaikan order mendadak?',
                    'opsi' => [
                        ['t' => 'Mampu hingga 50% atau lebih', 'n' => 100],
                        ['t' => 'Mampu hingga 30%', 'n' => 75],
                        ['t' => 'Hanya hingga 10%', 'n' => 50],
                        ['t' => 'Tidak mampu', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kesediaan bekerja sama jangka panjang (minimal 1 tahun)?',
                    'opsi' => [
                        ['t' => 'Ya, sangat siap', 'n' => 100],
                        ['t' => 'Ya, bersedia', 'n' => 75],
                        ['t' => 'Belum yakin', 'n' => 25],
                        ['t' => 'Tidak siap', 'n' => 0],
                    ]
                ],

                // SECTION 2: Kualitas Dasar (8 Soal)
                [
                    'per' => 'Sertifikasi yang dimiliki barang yang disupply?',
                    'opsi' => [
                        ['t' => 'Lengkap & masih berlaku', 'n' => 100],
                        ['t' => 'Sebagian & masih berlaku', 'n' => 75],
                        ['t' => 'Sedang proses', 'n' => 50],
                        ['t' => 'Tidak memiliki', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kebijakan penggantian barang cacat / tidak sesuai?',
                    'opsi' => [
                        ['t' => 'Ya, cepat & tanpa biaya tambahan', 'n' => 100],
                        ['t' => 'Ya, tapi ada syarat', 'n' => 75],
                        ['t' => 'Tergantung negosiasi', 'n' => 50],
                        ['t' => 'Tidak bersedia', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Frekuensi pemeriksaan kualitas sebelum pengiriman?',
                    'opsi' => [
                        ['t' => 'Selalu dilakukan & terdokumentasi', 'n' => 100],
                        ['t' => 'Dilakukan tapi tidak selalu', 'n' => 60],
                        ['t' => 'Jarang dilakukan', 'n' => 30],
                        ['t' => 'Tidak dilakukan', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Prosedur penanganan komplain?',
                    'opsi' => [
                        ['t' => 'Ada SOP tertulis & cepat', 'n' => 100],
                        ['t' => 'Ada tapi tidak tertulis', 'n' => 60],
                        ['t' => 'Tidak ada prosedur', 'n' => 20],
                        ['t' => 'Tidak menangani komplain', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kesediaan memberikan sampel untuk uji coba?',
                    'opsi' => [
                        ['t' => 'Ya, gratis & dalam jumlah memadai', 'n' => 100],
                        ['t' => 'Ya, dengan biaya', 'n' => 50],
                        ['t' => 'Hanya jika order besar', 'n' => 25],
                        ['t' => 'Tidak bersedia', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kualitas kemasan barang untuk pengiriman?',
                    'opsi' => [
                        ['t' => 'Sangat baik & aman', 'n' => 100],
                        ['t' => 'Standar biasa', 'n' => 60],
                        ['t' => 'Perlu perbaikan', 'n' => 30],
                        ['t' => 'Tidak memadai', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Frekuensi komplain kualitas dalam 1 tahun terakhir?',
                    'opsi' => [
                        ['t' => 'Tidak pernah', 'n' => 100],
                        ['t' => 'Sangat jarang', 'n' => 75],
                        ['t' => 'Cukup sering', 'n' => 25],
                        ['t' => 'Sering', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Standar kontrol kualitas yang diterapkan?',
                    'opsi' => [
                        ['t' => 'Ketat & terdokumentasi', 'n' => 100],
                        ['t' => 'Standar dasar', 'n' => 60],
                        ['t' => 'Minim', 'n' => 30],
                        ['t' => 'Tidak ada', 'n' => 0],
                    ]
                ],

                // SECTION 3: Logistik Dasar (5 Soal)
                [
                    'per' => 'Kemampuan pengiriman ke Jabodetabek?',
                    'opsi' => [
                        ['t' => 'Ya, rutin & lancar', 'n' => 100],
                        ['t' => 'Ya, tapi terbatas', 'n' => 60],
                        ['t' => 'Hanya volume besar', 'n' => 40],
                        ['t' => 'Tidak mampu', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kerjasama dengan ekspedisi?',
                    'opsi' => [
                        ['t' => 'Memiliki beberapa mitra resmi', 'n' => 100],
                        ['t' => 'Hanya satu mitra', 'n' => 60],
                        ['t' => 'Sedang proses', 'n' => 30],
                        ['t' => 'Tidak ada kerjasama', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Tingkat kepatuhan terhadap jadwal pengiriman?',
                    'opsi' => [
                        ['t' => 'Sangat patuh (>95%)', 'n' => 100],
                        ['t' => 'Cukup patuh', 'n' => 70],
                        ['t' => 'Sering terlambat', 'n' => 30],
                        ['t' => 'Tidak dapat menjamin', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Pengalaman pengiriman volume besar?',
                    'opsi' => [
                        ['t' => 'Sangat berpengalaman', 'n' => 100],
                        ['t' => 'Pernah beberapa kali', 'n' => 70],
                        ['t' => 'Baru mulai', 'n' => 40],
                        ['t' => 'Tidak berpengalaman', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kesediaan pengiriman di hari libur / luar jam kerja?',
                    'opsi' => [
                        ['t' => 'Ya, siap', 'n' => 100],
                        ['t' => 'Ya, dengan biaya tambahan', 'n' => 60],
                        ['t' => 'Tidak siap', 'n' => 20],
                        ['t' => 'Tidak bisa', 'n' => 0],
                    ]
                ],

                // SECTION 4: Red Flag & Compliance (12 Soal)
                [
                    'per' => 'Kasus barang ditolak massal dalam 2 tahun terakhir?',
                    'opsi' => [
                        ['t' => 'Tidak pernah', 'n' => 100],
                        ['t' => 'Pernah 1 kali', 'n' => 50],
                        ['t' => 'Pernah lebih dari 1 kali', 'n' => 25],
                        ['t' => 'Sering terjadi', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Status masalah hukum perusahaan / pengurus?',
                    'opsi' => [
                        ['t' => 'Tidak ada sama sekali', 'n' => 100],
                        ['t' => 'Ada kasus ringan', 'n' => 50],
                        ['t' => 'Ada kasus sedang/berat', 'n' => 0],
                        ['t' => 'Tidak ingin menjawab', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kesediaan dilakukan audit/verifikasi oleh kami?',
                    'opsi' => [
                        ['t' => 'Ya, sewaktu-waktu', 'n' => 100],
                        ['t' => 'Ya, dengan pemberitahuan sebelumnya', 'n' => 75],
                        ['t' => 'Hanya di lokasi tertentu', 'n' => 40],
                        ['t' => 'Tidak bersedia', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Status NPWP Badan?',
                    'opsi' => [
                        ['t' => 'Aktif & patuh pajak', 'n' => 100],
                        ['t' => 'Aktif tapi belum patuh', 'n' => 60],
                        ['t' => 'Sedang proses', 'n' => 30],
                        ['t' => 'Tidak memiliki', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kesediaan mengikuti syarat pembayaran kami?',
                    'opsi' => [
                        ['t' => 'Ya, sangat fleksibel', 'n' => 100],
                        ['t' => 'Ya, dengan catatan', 'n' => 70],
                        ['t' => 'Belum tahu', 'n' => 40],
                        ['t' => 'Tidak siap', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Apakah ada tuntutan dari pihak ketiga saat ini?',
                    'opsi' => [
                        ['t' => 'Tidak ada', 'n' => 100],
                        ['t' => 'Ada tapi kecil', 'n' => 50],
                        ['t' => 'Ada & sedang berjalan', 'n' => 20],
                        ['t' => 'Tidak ingin menjawab', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kondisi keuangan perusahaan?',
                    'opsi' => [
                        ['t' => 'Sehat & stabil', 'n' => 100],
                        ['t' => 'Cukup stabil', 'n' => 70],
                        ['t' => 'Ada masalah keuangan', 'n' => 30],
                        ['t' => 'Tidak ingin menjawab', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kesediaan menandatangani perjanjian kerjasama resmi?',
                    'opsi' => [
                        ['t' => 'Ya', 'n' => 100],
                        ['t' => 'Ya, dengan banyak syarat', 'n' => 50],
                        ['t' => 'Belum tentu', 'n' => 25],
                        ['t' => 'Tidak', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Pernah ada blacklist atau masalah dengan buyer sebelumnya?',
                    'opsi' => [
                        ['t' => 'Tidak pernah', 'n' => 100],
                        ['t' => 'Pernah', 'n' => 40],
                        ['t' => 'Masih ada masalah', 'n' => 0],
                        ['t' => 'Tidak tahu', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kejujuran data yang diberikan?',
                    'opsi' => [
                        ['t' => 'Semua data benar & valid', 'n' => 100],
                        ['t' => 'Ada sedikit ketidaksesuaian', 'n' => 50],
                        ['t' => 'Ada ketidaksesuaian', 'n' => 0],
                        ['t' => 'Tidak ingin menjawab', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Apakah Anda siap memenuhi standar supplier kami?',
                    'opsi' => [
                        ['t' => 'Ya, siap', 'n' => 100],
                        ['t' => 'Ya, sebagian', 'n' => 60],
                        ['t' => 'Belum yakin', 'n' => 30],
                        ['t' => 'Tidak siap', 'n' => 0],
                    ]
                ],
                [
                    'per' => 'Kesediaan untuk evaluasi berkala sebagai supplier?',
                    'opsi' => [
                        ['t' => 'Ya, sangat siap', 'n' => 100],
                        ['t' => 'Ya, bersedia', 'n' => 70],
                        ['t' => 'Tergantung', 'n' => 40],
                        ['t' => 'Tidak bersedia', 'n' => 0],
                    ]
                ],
            ];

            foreach ($sections as $index => $item) {
                // Insert Pertanyaan
                $id_pertanyaan = DB::table('pertanyaan')->insertGetId([
                    'jenis_soal' => 'pilihan_ganda',
                    'teks_pertanyaan' => $item['per'],
                    'bobot' => 1, // Kita beri bobot 1 untuk semua agar rata
                    'status' => 'aktif',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // Hubungkan ke Header (Detail Soal)
                DB::table('detail_soal')->insert([
                    'id_soal' => $id_soal,
                    'id_pertanyaan' => $id_pertanyaan,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // Insert Opsi
                foreach ($item['opsi'] as $o) {
                    DB::table('opsi')->insert([
                        'id_pertanyaan' => $id_pertanyaan,
                        'teks_opsi' => $o['t'],
                        'nilai' => $o['n'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        });
    }
}