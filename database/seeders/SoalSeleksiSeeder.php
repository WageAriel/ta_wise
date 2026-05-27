<?php

namespace Database\Seeders;

use App\Models\HeaderSoal;
use App\Models\Pertanyaan;
use App\Models\Opsi;
use App\Models\DetailSoal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SoalSeleksiSeeder extends Seeder
{
    /**
     * Bank soal untuk Tahap Seleksi Supplier.
     * Menggunakan model Eloquent dengan referensi gaya BankSoalKlasifikasiSeeder.
     */
    public function run(): void
    {
        // ── 2. Definisi Pertanyaan & Opsi ───────────────────────────────────
        $bankSoal = [
            // SECTION 1: Operasional Dasar
            [
                'teks_pertanyaan' => 'Apakah perusahaan Anda memiliki gudang atau fasilitas penyimpanan sendiri?',
                'bobot'           => 1,
                'opsis' => [
                    ['teks_opsi' => 'Ya, milik sendiri dan memadai', 'nilai' => 100],
                    ['teks_opsi' => 'Ya, sewa jangka panjang', 'nilai' => 75],
                    ['teks_opsi' => 'Menggunakan pihak ketiga', 'nilai' => 50],
                    ['teks_opsi' => 'Tidak memiliki / tidak tetap', 'nilai' => 0],
                ]
            ],
                [
                    'teks_pertanyaan' => 'Berapa lama lead time rata-rata dari PO sampai barang siap kirim?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Maksimal 5 hari', 'nilai' => 100],
                        ['teks_opsi' => '6 – 10 hari', 'nilai' => 75],
                        ['teks_opsi' => '11 – 20 hari', 'nilai' => 50],
                        ['teks_opsi' => 'Lebih dari 20 hari', 'nilai' => 25],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Seberapa konsisten Anda memenuhi pesanan rutin setiap bulan?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Sangat konsisten', 'nilai' => 100],
                        ['teks_opsi' => 'Cukup konsisten', 'nilai' => 75],
                        ['teks_opsi' => 'Kadang tidak terpenuhi', 'nilai' => 25],
                        ['teks_opsi' => 'Tidak konsisten', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Status rekening bank perusahaan?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Atas nama perusahaan & aktif', 'nilai' => 100],
                        ['teks_opsi' => 'Atas nama perusahaan tapi jarang digunakan', 'nilai' => 50],
                        ['teks_opsi' => 'Atas nama pribadi', 'nilai' => 25],
                        ['teks_opsi' => 'Tidak memiliki', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Apakah Anda siap melayani repeat order dalam jumlah besar secara rutin?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, sangat siap', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, dengan kapasitas terbatas', 'nilai' => 75],
                        ['teks_opsi' => 'Belum siap sepenuhnya', 'nilai' => 50],
                        ['teks_opsi' => 'Tidak siap', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Sistem pencatatan stok barang yang digunakan?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Digital / ERP / Software', 'nilai' => 100],
                        ['teks_opsi' => 'Manual yang terstruktur', 'nilai' => 75],
                        ['teks_opsi' => 'Manual sederhana', 'nilai' => 50],
                        ['teks_opsi' => 'Tidak ada sistem', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Berapa jumlah karyawan tetap saat ini?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Lebih dari 30 orang', 'nilai' => 100],
                        ['teks_opsi' => '10 – 30 orang', 'nilai' => 75],
                        ['teks_opsi' => '1 – 9 orang', 'nilai' => 50],
                        ['teks_opsi' => 'Tidak memiliki karyawan tetap', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kondisi alamat kantor & gudang?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Tetap, jelas, dan dapat dikunjungi', 'nilai' => 100],
                        ['teks_opsi' => 'Tetap tapi sulit dijangkau', 'nilai' => 50],
                        ['teks_opsi' => 'Sering berpindah', 'nilai' => 25],
                        ['teks_opsi' => 'Tidak jelas / virtual', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kemampuan menangani kenaikan order mendadak?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Mampu hingga 50% atau lebih', 'nilai' => 100],
                        ['teks_opsi' => 'Mampu hingga 30%', 'nilai' => 75],
                        ['teks_opsi' => 'Hanya hingga 10%', 'nilai' => 50],
                        ['teks_opsi' => 'Tidak mampu', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kesediaan bekerja sama jangka panjang (minimal 1 tahun)?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, sangat siap', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, bersedia', 'nilai' => 75],
                        ['teks_opsi' => 'Belum yakin', 'nilai' => 25],
                        ['teks_opsi' => 'Tidak siap', 'nilai' => 0],
                    ]
                ],

                // SECTION 2: Kualitas Dasar
                [
                    'teks_pertanyaan' => 'Sertifikasi yang dimiliki barang yang disupply?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Lengkap & masih berlaku', 'nilai' => 100],
                        ['teks_opsi' => 'Sebagian & masih berlaku', 'nilai' => 75],
                        ['teks_opsi' => 'Sedang proses', 'nilai' => 50],
                        ['teks_opsi' => 'Tidak memiliki', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kebijakan penggantian barang cacat / tidak sesuai?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, cepat & tanpa biaya tambahan', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, tapi ada syarat', 'nilai' => 75],
                        ['teks_opsi' => 'Tergantung negosiasi', 'nilai' => 50],
                        ['teks_opsi' => 'Tidak bersedia', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Frekuensi pemeriksaan kualitas sebelum pengiriman?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Selalu dilakukan & terdokumentasi', 'nilai' => 100],
                        ['teks_opsi' => 'Dilakukan tapi tidak selalu', 'nilai' => 60],
                        ['teks_opsi' => 'Jarang dilakukan', 'nilai' => 30],
                        ['teks_opsi' => 'Tidak dilakukan', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Prosedur penanganan komplain?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ada SOP tertulis & cepat', 'nilai' => 100],
                        ['teks_opsi' => 'Ada tapi tidak tertulis', 'nilai' => 60],
                        ['teks_opsi' => 'Tidak ada prosedur', 'nilai' => 20],
                        ['teks_opsi' => 'Tidak menangani komplain', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kesediaan memberikan sampel untuk uji coba?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, gratis & dalam jumlah memadai', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, dengan biaya', 'nilai' => 50],
                        ['teks_opsi' => 'Hanya jika order besar', 'nilai' => 25],
                        ['teks_opsi' => 'Tidak bersedia', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kualitas kemasan barang untuk pengiriman?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Sangat baik & aman', 'nilai' => 100],
                        ['teks_opsi' => 'Standar biasa', 'nilai' => 60],
                        ['teks_opsi' => 'Perlu perbaikan', 'nilai' => 30],
                        ['teks_opsi' => 'Tidak memadai', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Frekuensi komplain kualitas dalam 1 tahun terakhir?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Tidak pernah', 'nilai' => 100],
                        ['teks_opsi' => 'Sangat jarang', 'nilai' => 75],
                        ['teks_opsi' => 'Cukup sering', 'nilai' => 25],
                        ['teks_opsi' => 'Sering', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Standar kontrol kualitas yang diterapkan?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ketat & terdokumentasi', 'nilai' => 100],
                        ['teks_opsi' => 'Standar dasar', 'nilai' => 60],
                        ['teks_opsi' => 'Minim', 'nilai' => 30],
                        ['teks_opsi' => 'Tidak ada', 'nilai' => 0],
                    ]
                ],

                // SECTION 3: Logistik Dasar
                [
                    'teks_pertanyaan' => 'Kemampuan pengiriman ke Jabodetabek?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, rutin & lancar', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, tapi terbatas', 'nilai' => 60],
                        ['teks_opsi' => 'Hanya volume besar', 'nilai' => 40],
                        ['teks_opsi' => 'Tidak mampu', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kerjasama dengan ekspedisi?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Memiliki beberapa mitra resmi', 'nilai' => 100],
                        ['teks_opsi' => 'Hanya satu mitra', 'nilai' => 60],
                        ['teks_opsi' => 'Sedang proses', 'nilai' => 30],
                        ['teks_opsi' => 'Tidak ada kerjasama', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Tingkat kepatuhan terhadap jadwal pengiriman?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Sangat patuh (>95%)', 'nilai' => 100],
                        ['teks_opsi' => 'Cukup patuh', 'nilai' => 70],
                        ['teks_opsi' => 'Sering terlambat', 'nilai' => 30],
                        ['teks_opsi' => 'Tidak dapat menjamin', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Pengalaman pengiriman volume besar?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Sangat berpengalaman', 'nilai' => 100],
                        ['teks_opsi' => 'Pernah beberapa kali', 'nilai' => 70],
                        ['teks_opsi' => 'Baru mulai', 'nilai' => 40],
                        ['teks_opsi' => 'Tidak berpengalaman', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kesediaan pengiriman di hari libur / luar jam kerja?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, siap', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, dengan biaya tambahan', 'nilai' => 60],
                        ['teks_opsi' => 'Tidak siap', 'nilai' => 20],
                        ['teks_opsi' => 'Tidak bisa', 'nilai' => 0],
                    ]
                ],

                // SECTION 4: Red Flag & Compliance
                [
                    'teks_pertanyaan' => 'Kasus barang ditolak massal dalam 2 tahun terakhir?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Tidak pernah', 'nilai' => 100],
                        ['teks_opsi' => 'Pernah 1 kali', 'nilai' => 50],
                        ['teks_opsi' => 'Pernah lebih dari 1 kali', 'nilai' => 25],
                        ['teks_opsi' => 'Sering terjadi', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Status masalah hukum perusahaan / pengurus?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Tidak ada sama sekali', 'nilai' => 100],
                        ['teks_opsi' => 'Ada kasus ringan', 'nilai' => 50],
                        ['teks_opsi' => 'Ada kasus sedang/berat', 'nilai' => 0],
                        ['teks_opsi' => 'Tidak ingin menjawab', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kesediaan dilakukan audit/verifikasi oleh kami?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, sewaktu-waktu', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, dengan pemberitahuan sebelumnya', 'nilai' => 75],
                        ['teks_opsi' => 'Hanya di lokasi tertentu', 'nilai' => 40],
                        ['teks_opsi' => 'Tidak bersedia', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Status NPWP Badan?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Aktif & patuh pajak', 'nilai' => 100],
                        ['teks_opsi' => 'Aktif tapi belum patuh', 'nilai' => 60],
                        ['teks_opsi' => 'Sedang proses', 'nilai' => 30],
                        ['teks_opsi' => 'Tidak memiliki', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kesediaan mengikuti syarat pembayaran kami?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, sangat fleksibel', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, dengan catatan', 'nilai' => 70],
                        ['teks_opsi' => 'Belum tahu', 'nilai' => 40],
                        ['teks_opsi' => 'Tidak siap', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Apakah ada tuntutan dari pihak ketiga saat ini?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Tidak ada', 'nilai' => 100],
                        ['teks_opsi' => 'Ada tapi kecil', 'nilai' => 50],
                        ['teks_opsi' => 'Ada & sedang berjalan', 'nilai' => 20],
                        ['teks_opsi' => 'Tidak ingin menjawab', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kondisi keuangan perusahaan?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Sehat & stabil', 'nilai' => 100],
                        ['teks_opsi' => 'Cukup stabil', 'nilai' => 70],
                        ['teks_opsi' => 'Ada masalah keuangan', 'nilai' => 30],
                        ['teks_opsi' => 'Tidak ingin menjawab', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kesediaan menandatangani perjanjian kerjasama resmi?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, dengan banyak syarat', 'nilai' => 50],
                        ['teks_opsi' => 'Belum tentu', 'nilai' => 25],
                        ['teks_opsi' => 'Tidak', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Pernah ada blacklist atau masalah dengan buyer sebelumnya?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Tidak pernah', 'nilai' => 100],
                        ['teks_opsi' => 'Pernah', 'nilai' => 40],
                        ['teks_opsi' => 'Masih ada masalah', 'nilai' => 0],
                        ['teks_opsi' => 'Tidak tahu', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kejujuran data yang diberikan?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Semua data benar & valid', 'nilai' => 100],
                        ['teks_opsi' => 'Ada sedikit ketidaksesuaian', 'nilai' => 50],
                        ['teks_opsi' => 'Ada ketidaksesuaian', 'nilai' => 0],
                        ['teks_opsi' => 'Tidak ingin menjawab', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Apakah Anda siap memenuhi standar supplier kami?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, siap', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, sebagian', 'nilai' => 60],
                        ['teks_opsi' => 'Belum yakin', 'nilai' => 30],
                        ['teks_opsi' => 'Tidak siap', 'nilai' => 0],
                    ]
                ],
                [
                    'teks_pertanyaan' => 'Kesediaan untuk evaluasi berkala sebagai supplier?',
                    'bobot'           => 1,
                    'opsis' => [
                        ['teks_opsi' => 'Ya, sangat siap', 'nilai' => 100],
                        ['teks_opsi' => 'Ya, bersedia', 'nilai' => 70],
                        ['teks_opsi' => 'Tergantung', 'nilai' => 40],
                        ['teks_opsi' => 'Tidak bersedia', 'nilai' => 0],
                    ]
                ],
            ];

            DB::transaction(function () use ($bankSoal) {
                // ── 1. Buat Header Soal ─────────────────────────────────────────────
                $header = HeaderSoal::create([
                    'nama_soal' => 'Bank Soal Seleksi Supplier ' . now()->year,
                ]);

                // ── 3. Simpan pertanyaan, opsi, dan detail_soal ─────────────────────
                foreach ($bankSoal as $soalData) {
                    $pertanyaan = Pertanyaan::create([
                        'jenis_soal'      => 'seleksi',
                        'teks_pertanyaan' => $soalData['teks_pertanyaan'],
                        'bobot'           => $soalData['bobot'],
                        'status'          => 'aktif',
                    ]);

                    foreach ($soalData['opsis'] as $opsiData) {
                        Opsi::create([
                            'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                            'teks_opsi'     => $opsiData['teks_opsi'],
                            'nilai'         => $opsiData['nilai'],
                        ]);
                    }

                    // Hubungkan ke header soal via detail_soal
                    DetailSoal::create([
                        'id_soal'       => $header->id_soal,
                        'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    ]);
                }
            });

            $this->command->info('✅ Bank soal seleksi berhasil di-seed! (' . count($bankSoal) . ' pertanyaan)');
        }
    }
