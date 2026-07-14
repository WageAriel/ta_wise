<?php

namespace Database\Seeders;

use App\Models\HeaderSoal;
use App\Models\Pertanyaan;
use App\Models\Opsi;
use App\Models\DetailSoal;
use Illuminate\Database\Seeder;

class BankSoalKlasifikasiSeeder extends Seeder
{
    /**
     * Bank soal untuk Klasifikasi Supplier.
     * Total bobot = 100 (10 pertanyaan, masing-masing bobot 10).
     * Nilai tiap opsi mewakili % dari bobot pertanyaan tersebut.
     */
    public function run(): void
    {
        // ── 1. Buat Header Soal ─────────────────────────────────────────────
        $header = HeaderSoal::create([
            'nama_soal' => 'Bank Soal Klasifikasi Supplier ' . now()->year,
        ]);

        // ── 2. Definisi Pertanyaan & Opsi ───────────────────────────────────
        $bankSoal = [
            [
                'teks_pertanyaan' => 'Berapa lama perusahaan Anda telah beroperasi di bidang usaha saat ini?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Lebih dari 10 tahun', 'nilai' => 10],
                    ['teks_opsi' => '5 – 10 tahun', 'nilai' => 7],
                    ['teks_opsi' => '2 – 5 tahun', 'nilai' => 5],
                    ['teks_opsi' => 'Kurang dari 2 tahun', 'nilai' => 2],
                ],
            ],
            [
                'teks_pertanyaan' => 'Apakah perusahaan Anda memiliki sertifikat manajemen mutu (ISO 9001 atau sejenisnya)?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Ya, sudah tersertifikasi dan masih berlaku', 'nilai' => 10],
                    ['teks_opsi' => 'Sedang dalam proses sertifikasi', 'nilai' => 6],
                    ['teks_opsi' => 'Belum memiliki, namun ada rencana', 'nilai' => 3],
                    ['teks_opsi' => 'Tidak memiliki dan tidak ada rencana', 'nilai' => 0],
                ],
            ],
            [
                'teks_pertanyaan' => 'Bagaimana kapasitas produksi atau layanan perusahaan Anda dalam memenuhi permintaan besar?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Dapat memenuhi permintaan sangat besar tanpa kendala', 'nilai' => 10],
                    ['teks_opsi' => 'Dapat memenuhi permintaan besar dengan sedikit kendala', 'nilai' => 7],
                    ['teks_opsi' => 'Kapasitas terbatas, perlu negosiasi lebih lanjut', 'nilai' => 4],
                    ['teks_opsi' => 'Kapasitas sangat terbatas', 'nilai' => 1],
                ],
            ],
            [
                'teks_pertanyaan' => 'Apakah perusahaan Anda memiliki rekam jejak pengiriman tepat waktu yang konsisten?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Lebih dari 95% pengiriman tepat waktu', 'nilai' => 10],
                    ['teks_opsi' => '85% – 95% pengiriman tepat waktu', 'nilai' => 7],
                    ['teks_opsi' => '70% – 85% pengiriman tepat waktu', 'nilai' => 5],
                    ['teks_opsi' => 'Di bawah 70% pengiriman tepat waktu', 'nilai' => 2],
                ],
            ],
            [
                'teks_pertanyaan' => 'Bagaimana kondisi keuangan perusahaan Anda secara umum?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Sangat sehat, laporan keuangan audited tersedia', 'nilai' => 10],
                    ['teks_opsi' => 'Sehat, tidak ada masalah arus kas berarti', 'nilai' => 7],
                    ['teks_opsi' => 'Cukup stabil, namun ada beberapa tantangan', 'nilai' => 4],
                    ['teks_opsi' => 'Kondisi keuangan sedang dalam perbaikan', 'nilai' => 1],
                ],
            ],
            [
                'teks_pertanyaan' => 'Apakah perusahaan Anda memiliki fasilitas penyimpanan atau gudang yang memadai?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Memiliki gudang sendiri berkapasitas besar', 'nilai' => 10],
                    ['teks_opsi' => 'Memiliki gudang sendiri berkapasitas sedang', 'nilai' => 7],
                    ['teks_opsi' => 'Menyewa gudang pihak ketiga', 'nilai' => 5],
                    ['teks_opsi' => 'Tidak memiliki fasilitas gudang yang memadai', 'nilai' => 1],
                ],
            ],
            [
                'teks_pertanyaan' => 'Bagaimana kemampuan perusahaan Anda dalam menangani klaim atau komplain produk?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Memiliki SOP penanganan klaim yang terstandar dan cepat', 'nilai' => 10],
                    ['teks_opsi' => 'Dapat menangani klaim meski prosesnya belum formal', 'nilai' => 6],
                    ['teks_opsi' => 'Proses penanganan klaim masih ad-hoc', 'nilai' => 3],
                    ['teks_opsi' => 'Belum memiliki mekanisme penanganan klaim', 'nilai' => 0],
                ],
            ],
            [
                'teks_pertanyaan' => 'Apakah perusahaan Anda memiliki tim Quality Control (QC) yang berdedikasi?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Ya, tim QC penuh waktu dengan prosedur tertulis', 'nilai' => 10],
                    ['teks_opsi' => 'Ya, ada personel QC meski tidak penuh waktu', 'nilai' => 6],
                    ['teks_opsi' => 'QC dilakukan secara informal tanpa tim khusus', 'nilai' => 3],
                    ['teks_opsi' => 'Tidak ada proses QC formal', 'nilai' => 0],
                ],
            ],
            [
                'teks_pertanyaan' => 'Seberapa luas jangkauan distribusi dan logistik perusahaan Anda?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Nasional — dapat melayani seluruh wilayah Indonesia', 'nilai' => 10],
                    ['teks_opsi' => 'Regional — beberapa provinsi', 'nilai' => 6],
                    ['teks_opsi' => 'Lokal — satu provinsi / kota', 'nilai' => 3],
                    ['teks_opsi' => 'Sangat terbatas, hanya area tertentu', 'nilai' => 1],
                ],
            ],
            [
                'teks_pertanyaan' => 'Apakah perusahaan Anda memiliki pengalaman bermitra dengan instansi pemerintah atau BUMN?',
                'bobot' => 10,
                'opsis' => [
                    ['teks_opsi' => 'Ya, sudah bermitra aktif dengan lebih dari satu instansi', 'nilai' => 10],
                    ['teks_opsi' => 'Ya, pernah bermitra setidaknya sekali', 'nilai' => 7],
                    ['teks_opsi' => 'Belum pernah, namun sedang dalam proses negosiasi', 'nilai' => 3],
                    ['teks_opsi' => 'Belum pernah sama sekali', 'nilai' => 0],
                ],
            ],
        ];

        // ── 3. Simpan pertanyaan, opsi, dan detail_soal ─────────────────────
        foreach ($bankSoal as $soalData) {
            $pertanyaan = Pertanyaan::create([
                'jenis_soal' => 'klasifikasi',
                'teks_pertanyaan' => $soalData['teks_pertanyaan'],
                'bobot' => $soalData['bobot'],
                'status' => 'aktif',
            ]);

            foreach ($soalData['opsis'] as $opsiData) {
                Opsi::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'teks_opsi' => $opsiData['teks_opsi'],
                    'nilai' => $opsiData['nilai'],
                ]);
            }

            // Hubungkan ke header soal via detail_soal
            DetailSoal::create([
                'id_soal' => $header->id_soal,
                'id_pertanyaan' => $pertanyaan->id_pertanyaan,
            ]);
        }

        $this->command->info('✅ Bank soal klasifikasi berhasil di-seed! (' . count($bankSoal) . ' pertanyaan, total bobot 100)');
    }
}
