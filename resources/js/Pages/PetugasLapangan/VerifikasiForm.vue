<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import SidebarPetugas from '@/Components/SidebarPetugas.vue';

const props = defineProps({
    jadwal: Object,
    supplier: Object,
    nilaiPengajuan: Number,
    jawabanSupplier: Array,
});

const verifikasiJawaban = ref(
    Object.fromEntries(
        props.jawabanSupplier.map(j => [
            j.id_jawaban,
            j.opsi_verifikasi?.id_opsi ?? j.id_opsi  // prefer previous petugas answer
        ])
    )
);
const catatanItem = ref(
    Object.fromEntries(props.jawabanSupplier.map(j => [j.id_jawaban, j.catatan_verifikasi ?? '']))
);
const catatanUmum = ref('');
const showDialog  = ref(false);
const isSubmitting = ref(false);

// ─── Helpers ─────────────────────────────────────────────────────
const formatWaktu = (t) => t ? t.slice(0, 5) : '-';

const hitungPoin = (opsi, bobot) => opsi ? Math.round((opsi.nilai / 100) * bobot) : 0;

const getOpsiById = (jawaban, idOpsi) =>
    jawaban.pertanyaan.opsis.find(o => o.id_opsi === idOpsi);

// Ketidaksesuaian: bandingkan pilihan petugas vs jawaban ASLI supplier (id_opsi)
const isNotSesuai = (jawaban) =>
    verifikasiJawaban.value[jawaban.id_jawaban] !== jawaban.id_opsi;

// ─── Computed ────────────────────────────────────────────────────
const adaKetidaksesuaian = computed(() =>
    props.jawabanSupplier.some(j => isNotSesuai(j))
);

const totalNilaiVerifikasi = computed(() => {
    return props.jawabanSupplier.reduce((acc, j) => {
        const idOpsi = verifikasiJawaban.value[j.id_jawaban];
        const opsi = getOpsiById(j, idOpsi);
        return acc + hitungPoin(opsi, j.pertanyaan.bobot);
    }, 0);
});

const rekomendasiKelas = computed(() => {
    const s = totalNilaiVerifikasi.value;
    if (s >= 85) return { kelas: 'Class A', warna: '#16a34a', bg: '#f0fdf4' };
    if (s >= 60) return { kelas: 'Class B', warna: '#ea580c', bg: '#fff7ed' };
    if (s >= 30) return { kelas: 'Class C', warna: '#2563eb', bg: '#eff6ff' };
    return { kelas: 'Belum Memenuhi', warna: '#64748b', bg: '#f8fafc' };
});

// ─── Submit ──────────────────────────────────────────────────────

const doSubmit = async () => {
    isSubmitting.value = true;
    
    const payload = {
        jawaban: props.jawabanSupplier.map(j => ({
            id_jawaban: j.id_jawaban,
            id_opsi:    verifikasiJawaban.value[j.id_jawaban],  // pilihan petugas -> id_opsi_verifikasi
            catatan:    catatanItem.value[j.id_jawaban] || '',
        })),
        catatan_umum: catatanUmum.value,
    };

    try {
        await axios.post(route('petugas.verifikasi.store', props.jadwal.id), payload);
        isSubmitting.value = false;
        showDialog.value = false;
        // Gunakan router Inertia untuk redirect secara SPA
        router.visit(route('petugas.jadwal'));
    } catch (error) {
        console.error("Gagal mengirim verifikasi:", error);
        isSubmitting.value = false;
        showDialog.value = false;
        alert("Gagal mengirim data verifikasi. Silakan coba lagi.");
    }
};
</script>

<template>
    <Head title="Form Verifikasi | WISE" />
    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarPetugas class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-7xl mx-auto px-6 py-10 space-y-6">

                <!-- ── Header ── -->
                <div class="flex items-center gap-4">
                    <Link :href="route('petugas.jadwal')"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Kembali
                    </Link>
                    <div class="flex-1">
                        <h1 class="text-slate-900 font-bold" style="font-size:24px;margin-bottom:4px">Form Verifikasi Lapangan</h1>
                        <p class="text-slate-500" style="font-size:14px">Inputkan hasil verifikasi fasilitas supplier</p>
                    </div>
                </div>

                <!-- ── Supplier Info Card ── -->
                <div class="bg-blue-500 rounded-2xl p-6 text-white shadow-lg">
                    <h2 class="font-bold mb-4" style="font-size:20px">{{ supplier.nama_perusahaan }}</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <p style="font-size:11px;opacity:.8">Jadwal Verifikasi</p>
                                <p style="font-size:13px;font-weight:600">{{ jadwal.tanggal_kunjungan }} • {{ formatWaktu(jadwal.waktu_kunjungan) }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div>
                                <p style="font-size:11px;opacity:.8">Lokasi</p>
                                <p style="font-size:13px;font-weight:600">{{ supplier.alamat }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <div>
                                <p style="font-size:11px;opacity:.8">Kontak Person</p>
                                <p style="font-size:13px;font-weight:600">{{ supplier.nama_pic }}</p>
                                <p style="font-size:11px;opacity:.8">{{ supplier.no_telp_pic }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                            <div>
                                <p style="font-size:11px;opacity:.8">Skor Pengajuan Supplier</p>
                                <p style="font-size:13px;font-weight:600">{{ nilaiPengajuan }} poin</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Alert Ketidaksesuaian ── -->
                <div v-if="adaKetidaksesuaian" class="p-4 rounded-xl border-2 border-amber-300 bg-amber-50 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div>
                        <h4 class="text-amber-900 font-bold mb-1" style="font-size:13px">Ketidaksesuaian Terdeteksi</h4>
                        <p class="text-amber-700" style="font-size:12px">Terdapat perbedaan antara pengajuan supplier dengan hasil verifikasi lapangan Anda. Pastikan data yang diinputkan sudah sesuai kondisi aktual.</p>
                    </div>
                </div>

                <!-- ── Form Pertanyaan ── -->
                <div class="space-y-4">
                    <div
                        v-for="(jawaban, idx) in jawabanSupplier"
                        :key="jawaban.id_jawaban"
                        class="bg-white rounded-2xl shadow-sm border-2 overflow-hidden transition-all"
                        :class="isNotSesuai(jawaban) ? 'border-amber-300 bg-amber-50/30' : 'border-slate-100'"
                    >
                        <div class="p-6">
                            <!-- Header pertanyaan -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <h3 class="text-slate-900 font-bold" style="font-size:15px">
                                            {{ idx + 1 }}. {{ jawaban.pertanyaan.teks_pertanyaan }}
                                        </h3>
                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold" style="font-size:10px">
                                            {{ jawaban.pertanyaan.bobot }} poin
                                        </span>
                                        <span v-if="isNotSesuai(jawaban)"
                                            class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500 text-white font-bold" style="font-size:10px">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                                            </svg>
                                            TIDAK SESUAI
                                        </span>
                                    </div>

                                    <!-- Perbandingan jika tidak sesuai -->
                                    <div v-if="isNotSesuai(jawaban)" class="mt-2 p-2 rounded-lg bg-white border border-amber-200">
                                        <div class="flex items-center gap-3 flex-wrap" style="font-size:11px">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-slate-500 font-semibold">Pengajuan:</span>
                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold">
                                                    {{ jawaban.opsi_supplier?.teks_opsi ?? '-' }}
                                                </span>
                                            </div>
                                            <span class="text-slate-400">→</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-amber-600 font-semibold">Verifikasi Anda:</span>
                                                <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-bold">
                                                    {{ getOpsiById(jawaban, verifikasiJawaban[jawaban.id_jawaban])?.teks_opsi ?? '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Opsi Radio -->
                            <div class="space-y-2 mb-5">
                                <label
                                    v-for="opsi in jawaban.pertanyaan.opsis"
                                    :key="opsi.id_opsi"
                                    class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                                    :class="verifikasiJawaban[jawaban.id_jawaban] === opsi.id_opsi
                                        ? 'border-blue-400 bg-blue-50'
                                        : 'border-slate-100 hover:border-slate-200 bg-white'"
                                >
                                    <input
                                        type="radio"
                                        :name="`q_${jawaban.id_jawaban}`"
                                        :value="opsi.id_opsi"
                                        v-model="verifikasiJawaban[jawaban.id_jawaban]"
                                        class="accent-blue-600"
                                    />
                                    <div class="flex-1 flex items-center justify-between gap-2">
                                        <span class="text-slate-800 text-sm"
                                            :class="verifikasiJawaban[jawaban.id_jawaban] === opsi.id_opsi ? 'font-semibold' : 'font-medium'">
                                            {{ opsi.teks_opsi }}
                                        </span>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <span v-if="jawaban.id_opsi === opsi.id_opsi"
                                                class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-bold" style="font-size:9px">
                                                SUPPLIER
                                            </span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                                :class="verifikasiJawaban[jawaban.id_jawaban] === opsi.id_opsi
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-slate-100 text-slate-500'">
                                                {{ hitungPoin(opsi, jawaban.pertanyaan.bobot) }} poin
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Catatan per item -->
                            <div>
                                <label class="block text-slate-700 font-semibold mb-1.5" style="font-size:12px">
                                    Catatan Verifikasi
                                </label>
                                <input
                                    type="text"
                                    v-model="catatanItem[jawaban.id_jawaban]"
                                    placeholder="Jelaskan kondisi aktual yang Anda temukan di lapangan..."
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 focus:outline-none bg-slate-50"
                                />
                                <p class="text-slate-400 mt-1" style="font-size:11px">Jelaskan kondisi aktual fasilitas yang ditemukan di lapangan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Catatan Umum ── -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <label class="block text-slate-900 font-bold mb-2" style="font-size:14px">Catatan Umum Verifikasi</label>
                    <textarea
                        v-model="catatanUmum"
                        placeholder="Tambahkan catatan umum atau observasi tambahan tentang kondisi supplier secara keseluruhan..."
                        class="w-full min-h-[120px] p-3 rounded-lg border border-slate-200 focus:border-blue-400 focus:outline-none resize-none bg-slate-50"
                        style="font-size:13px"
                    ></textarea>
                </div>

                <!-- ── Ringkasan Verifikasi ── -->
                <div class="bg-white rounded-2xl shadow-sm border-2 border-blue-200 p-6">
                    <h3 class="text-slate-900 font-bold mb-4" style="font-size:16px">Ringkasan Verifikasi</h3>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200">
                            <p class="text-slate-600 font-semibold mb-1" style="font-size:11px">SKOR VERIFIKASI</p>
                            <p class="text-blue-700 font-bold" style="font-size:28px">{{ totalNilaiVerifikasi }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <p class="text-slate-600 font-semibold mb-1" style="font-size:11px">PENGAJUAN</p>
                            <p class="text-slate-700 font-bold" style="font-size:28px">{{ nilaiPengajuan }}</p>
                        </div>
                        <div class="p-4 rounded-xl border-2" :style="{ background: rekomendasiKelas.bg, borderColor: rekomendasiKelas.warna }">
                            <p class="text-slate-600 font-semibold mb-1" style="font-size:11px">REKOMENDASI</p>
                            <p class="font-bold" style="font-size:18px" :style="{ color: rekomendasiKelas.warna }">
                                {{ rekomendasiKelas.kelas }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                        <p class="text-slate-500" style="font-size:12px">
                            {{ jawabanSupplier.filter(j => isNotSesuai(j)).length }} pertanyaan diubah dari pengajuan supplier
                        </p>
                        <button
                            @click="showDialog = true"
                            class="flex items-center gap-2 px-6 py-2.5 rounded-lg text-white font-semibold transition-all hover:opacity-90 active:scale-95"
                            style="background:linear-gradient(135deg,#16a34a,#22c55e);font-size:14px"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan Verifikasi
                        </button>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- ── Dialog Konfirmasi ── -->
    <Teleport to="body">
        <div v-if="showDialog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showDialog = false"></div>
            <div class="bg-white rounded-2xl w-full max-w-md shadow-xl relative p-6 space-y-4">
                <h3 class="text-slate-900 font-bold" style="font-size:18px">Konfirmasi Verifikasi</h3>
                <p class="text-slate-600" style="font-size:13px">Anda akan menyimpan hasil verifikasi untuk:</p>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                    <p class="text-slate-900 font-bold" style="font-size:14px">{{ supplier.nama_perusahaan }}</p>
                    <p class="text-slate-600" style="font-size:12px">Skor Verifikasi: <strong>{{ totalNilaiVerifikasi }} poin</strong></p>
                    <p class="text-slate-600" style="font-size:12px">Rekomendasi: <strong :style="{ color: rekomendasiKelas.warna }">{{ rekomendasiKelas.kelas }}</strong></p>
                    <p v-if="adaKetidaksesuaian" class="text-amber-700 flex items-center gap-1 mt-2" style="font-size:12px">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                        </svg>
                        Ada ketidaksesuaian dengan pengajuan supplier
                    </p>
                </div>

                <p class="text-slate-500" style="font-size:12px">Pastikan semua data sudah benar. Data yang disimpan akan dikirim ke admin untuk validasi final.</p>

                <div class="flex items-center gap-3 pt-2">
                    <button @click="showDialog = false"
                        class="flex-1 px-4 py-2.5 rounded-lg border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button @click="doSubmit" :disabled="isSubmitting"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-white font-semibold text-sm transition-all disabled:opacity-60"
                        style="background:linear-gradient(135deg,#16a34a,#22c55e)">
                        <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ isSubmitting ? 'Menyimpan...' : 'Konfirmasi & Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
main::-webkit-scrollbar { width: 6px; }
main::-webkit-scrollbar-track { background: transparent; }
main::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
main::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
</style>
