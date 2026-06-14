<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\JadwalKunjungan;
use App\Models\JawabanKlasifikasi;
use App\Models\Verifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VerifikasiController extends Controller
{
    /**
     * Tampilkan form verifikasi lapangan untuk jadwal tertentu
     */
    public function show(JadwalKunjungan $jadwal)
    {
        // Pastikan jadwal milik petugas yang login
        abort_if($jadwal->id_user_petugas !== Auth::id(), 403);

        // Load semua relasi yang dibutuhkan
        $jadwal->load([
            'klasifikasi.supplier',
            'klasifikasi.jawabanKlasifikasis.pertanyaan.opsis',
            'klasifikasi.jawabanKlasifikasis.opsi',
            'klasifikasi.jawabanKlasifikasis.opsiVerifikasi',
            'klasifikasi.headerSoal',
        ]);

        // Kelompokkan jawaban supplier per pertanyaan
        $jawabanSupplier = $jadwal->klasifikasi->jawabanKlasifikasis
            ->map(function ($jawaban) {
                return [
                    'id_jawaban'    => $jawaban->id_jawaban,
                    'id_pertanyaan' => $jawaban->id_pertanyaan,
                    'id_opsi'       => $jawaban->id_opsi,       // Pilihan ASLI supplier (tidak berubah)
                    'pertanyaan'    => [
                        'id_pertanyaan'   => $jawaban->pertanyaan->id_pertanyaan,
                        'teks_pertanyaan' => $jawaban->pertanyaan->teks_pertanyaan,
                        'bobot'           => $jawaban->pertanyaan->bobot,
                        'opsis'           => $jawaban->pertanyaan->opsis->map(fn($o) => [
                            'id_opsi'   => $o->id_opsi,
                            'teks_opsi' => $o->teks_opsi,
                            'nilai'     => $o->nilai,
                        ])->values(),
                    ],
                    // Opsi asli supplier
                    'opsi_supplier' => $jawaban->opsi ? [
                        'id_opsi'   => $jawaban->opsi->id_opsi,
                        'teks_opsi' => $jawaban->opsi->teks_opsi,
                        'nilai'     => $jawaban->opsi->nilai,
                    ] : null,
                    // Opsi yang sudah pernah diverifikasi (untuk re-edit)
                    'opsi_verifikasi' => $jawaban->opsiVerifikasi ? [
                        'id_opsi'   => $jawaban->opsiVerifikasi->id_opsi,
                        'teks_opsi' => $jawaban->opsiVerifikasi->teks_opsi,
                        'nilai'     => $jawaban->opsiVerifikasi->nilai,
                    ] : null,
                    'catatan_verifikasi' => $jawaban->catatan_verifikasi,
                    'jawaban_verifikasi' => $jawaban->jawaban_verifikasi, // 'valid'/'invalid'/null
                ];
            })->values();

        // Hitung total nilai pengajuan supplier
        $nilaiPengajuan = $jadwal->klasifikasi->total_nilai ?? 0;

        return Inertia::render('PetugasLapangan/VerifikasiForm', [
            'jadwal'          => [
                'id'                => $jadwal->id,
                'tanggal_kunjungan' => $jadwal->tanggal_kunjungan,
                'waktu_kunjungan'   => $jadwal->waktu_kunjungan,
                'status'            => $jadwal->status,
            ],
            'supplier'        => [
                'nama_perusahaan'  => $jadwal->klasifikasi->supplier->nama_perusahaan ?? '-',
                'alamat'           => $jadwal->klasifikasi->supplier->alamat_perusahaan ?? '-',
                'nama_pic'         => $jadwal->klasifikasi->supplier->nama_pic ?? '-',
                'no_telp_pic'      => $jadwal->klasifikasi->supplier->no_telp_pic ?? '-',
            ],
            'nilaiPengajuan'  => $nilaiPengajuan,
            'jawabanSupplier' => $jawabanSupplier,
        ]);
    }

    /**
     * Simpan hasil verifikasi lapangan
     */
    public function store(JadwalKunjungan $jadwal, Request $request)
    {
        abort_if($jadwal->id_user_petugas !== Auth::id(), 403);

        $request->validate([
            'jawaban'              => 'required|array',
            'jawaban.*.id_jawaban' => 'required|exists:jawaban_klasifikasi,id_jawaban',
            'jawaban.*.id_opsi'    => 'required|exists:opsi,id_opsi',
            'jawaban.*.catatan'    => 'nullable|string|max:500',
            'catatan_umum'         => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($jadwal, $request) {
            // 1. Hitung total nilai dari jawaban verifikasi petugas
            $totalNilai = 0;
            foreach ($request->jawaban as $item) {
                $jawaban = JawabanKlasifikasi::with(['pertanyaan.opsis'])->find($item['id_jawaban']);
                if ($jawaban) {
                    $opsiDipilih = $jawaban->pertanyaan->opsis->firstWhere('id_opsi', $item['id_opsi']);
                    if ($opsiDipilih) {
                        $totalNilai += round(($opsiDipilih->nilai / 100) * $jawaban->pertanyaan->bobot);
                    }
                }
            }

            // 2. Tentukan rekomendasi kelas
            $rekomendasi = 'Belum Memenuhi';
            if ($totalNilai >= 85)      $rekomendasi = 'Class A';
            elseif ($totalNilai >= 60)  $rekomendasi = 'Class B';
            elseif ($totalNilai >= 30)  $rekomendasi = 'Class C';

            // 3. Buat/update record Verifikasi
            $verifikasi = Verifikasi::updateOrCreate(
                ['id_klasifikasi' => $jadwal->id_klasifikasi],
                [
                    'total_nilai'        => $totalNilai,
                    'status'             => 'menunggu_admin',
                    'tanggal'            => now()->toDateString(),
                    'rekomendasi_sistem' => $rekomendasi,
                    'id_user_petugas'    => Auth::id(),
                ]
            );
            foreach ($request->jawaban as $item) {
                $jawaban = JawabanKlasifikasi::find($item['id_jawaban']);
                if (!$jawaban) continue;

                $isValid = ((int) $jawaban->id_opsi === (int) $item['id_opsi'])
                    ? 'valid'
                    : 'invalid';

                $jawaban->update([
                    'id_opsi_verifikasi' => $item['id_opsi'],
                    'jawaban_verifikasi'  => $isValid,
                    'catatan_verifikasi'  => $item['catatan'] ?? null,
                    'id_verifikasi'       => $verifikasi->id_verifikasi,
                ]);
            }

            $jadwal->update(['status' => 'selesai']);
        });

        return redirect()
            ->route('petugas.jadwal')
            ->with('message', 'Verifikasi lapangan berhasil disimpan.');
    }
}

