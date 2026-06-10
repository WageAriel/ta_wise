<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    selectedRow: Object,
    selectedDetail: Object,
    isFetchingDetail: Boolean,
    isValidating: Boolean,
});

const emit = defineEmits(['close', 'validasi']);

const rekomendasiConfig = {
    'Class A': { color: '#16a34a', bg: '#f0fdf4', border: '#bbf7d0' },
    'Class B': { color: '#ea580c', bg: '#fff7ed', border: '#fed7aa' },
    'Class C': { color: '#2563eb', bg: '#eff6ff', border: '#bfdbfe' },
    'Belum Memenuhi': { color: '#64748b', bg: '#f8fafc', border: '#e2e8f0' },
};

function getCfg(kelas) {
    return rekomendasiConfig[kelas] ?? rekomendasiConfig['Belum Memenuhi'];
}

function hitungPoinJawaban(jawaban) {
    if (!jawaban.opsi_verifikasi) return jawaban.opsi?.nilai ?? 0;
    const bobot = jawaban.pertanyaan?.bobot ?? 0;
    const nilai = jawaban.opsi_verifikasi?.nilai ?? 0;
    return Math.round((nilai / 100) * bobot);
}

async function handleValidasi(kelas) {
    emit('validasi', kelas);
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="show && selectedRow"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                @click.self="emit('close')"
            >
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="flex items-start justify-between p-6 border-b border-slate-100">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Hasil Verifikasi Lapangan</h3>
                            <p class="text-sm text-slate-500 mt-0.5">{{ selectedRow.supplier?.nama_perusahaan }}</p>
                        </div>
                        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <!-- Ketidaksesuaian skor -->
                        <div
                            v-if="selectedRow.total_nilai !== selectedRow.verifikasi?.total_nilai"
                            class="p-4 rounded-xl border-2 border-red-200 bg-red-50"
                        >
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-red-900 font-bold text-sm mb-1">Ketidaksesuaian Data Terdeteksi</h4>
                                    <p class="text-red-700 text-xs mb-3">Terdapat perbedaan antara data pengajuan dengan hasil verifikasi lapangan.</p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="p-3 rounded-lg bg-white border border-red-200">
                                            <p class="text-slate-500 text-xs font-bold uppercase mb-1">Nilai Pengajuan</p>
                                            <p class="text-slate-900 text-xl font-bold">{{ selectedRow.total_nilai }} <span class="text-sm font-normal">poin</span></p>
                                            <p class="text-slate-500 text-xs">Self-assessment supplier</p>
                                        </div>
                                        <div class="p-3 rounded-lg bg-white border border-red-200">
                                            <p class="text-slate-500 text-xs font-bold uppercase mb-1">Nilai Verifikasi</p>
                                            <p class="text-red-700 text-xl font-bold">{{ selectedRow.verifikasi?.total_nilai }} <span class="text-sm font-normal">poin</span></p>
                                            <p class="text-slate-500 text-xs">Hasil verifikasi petugas</p>
                                        </div>
                                    </div>
                                    <div class="mt-2 p-2 rounded bg-red-100/60">
                                        <p class="text-red-800 text-xs font-semibold">⚠️ Selisih: {{ selectedRow.total_nilai - selectedRow.verifikasi?.total_nilai }} poin</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Petugas Info -->
                        <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-700 text-sm font-bold">
                                    {{ (selectedRow.verifikasi?.petugas?.profil_petugas?.nama_petugas || selectedRow.verifikasi?.petugas?.username || 'P').charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div>
                                <p class="text-slate-700 text-sm font-semibold">{{ selectedRow.verifikasi?.petugas?.profil_petugas?.nama_petugas || selectedRow.verifikasi?.petugas?.username || 'Petugas' }}</p>
                                <p class="text-slate-500 text-xs">Petugas Lapangan • {{ selectedRow.verifikasi?.tanggal }}</p>
                            </div>
                        </div>

                        <!-- ── Daftar Verifikasi Kriteria ── -->
                        <div>
                            <h4 class="text-slate-700 text-sm font-bold mb-3">Daftar Verifikasi Kriteria</h4>z

                            <!-- Loading skeleton -->
                            <div v-if="isFetchingDetail" class="space-y-3">
                                <div v-for="i in 4" :key="i" class="h-16 rounded-xl bg-slate-100 animate-pulse"></div>
                            </div>

                            <div v-else-if="selectedDetail" class="space-y-3">
                                <div
                                    v-for="jawaban in selectedDetail.jawaban_klasifikasis"
                                    :key="jawaban.id_jawaban"
                                    class="rounded-xl border-2 p-4 transition-all"
                                    :class="jawaban.jawaban_verifikasi === 'invalid'
                                        ? 'border-orange-200 bg-orange-50/40'
                                        : 'border-emerald-200 bg-emerald-50/40'"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-2 flex-1 min-w-0">
                                            <!-- Icon valid/invalid -->
                                            <div class="flex-shrink-0 mt-0.5">
                                                <!-- Valid -->
                                                <svg v-if="jawaban.jawaban_verifikasi !== 'invalid'" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <!-- Invalid -->
                                                <svg v-else class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <!-- Judul pertanyaan + badge TIDAK SESUAI -->
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-slate-800 text-sm font-semibold leading-snug">
                                                        {{ jawaban.pertanyaan?.teks_pertanyaan }}
                                                    </span>
                                                    <span
                                                        v-if="jawaban.jawaban_verifikasi === 'invalid'"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-orange-500 text-white font-bold"
                                                        style="font-size:9px"
                                                    >
                                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                                        TIDAK SESUAI
                                                    </span>
                                                </div>

                                                <!-- Jawaban (selalu tampil, beda styling jika invalid) -->
                                                <div
                                                    class="mt-2 mb-1 px-2 py-1.5 rounded-lg flex items-center gap-2 flex-wrap border"
                                                    :class="jawaban.jawaban_verifikasi === 'invalid'
                                                        ? 'bg-white border-orange-200'
                                                        : 'bg-emerald-50/50 border-emerald-100'"
                                                    style="font-size:11px"
                                                >
                                                    <span class="text-slate-500 font-semibold">Jawaban:</span>
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold">
                                                        {{ jawaban.opsi?.teks_opsi ?? '—' }}
                                                    </span>
                                                    <template v-if="jawaban.jawaban_verifikasi === 'invalid'">
                                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                        <span class="text-orange-600 font-semibold">Verifikasi:</span>
                                                        <span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 font-bold">
                                                            {{ jawaban.opsi_verifikasi?.teks_opsi ?? '—' }}
                                                        </span>
                                                    </template>
                                                </div>

                                                <!-- Catatan petugas -->
                                                <p
                                                    v-if="jawaban.catatan_verifikasi"
                                                    class="text-slate-500 mt-1"
                                                    style="font-size:12px"
                                                >
                                                    {{ jawaban.catatan_verifikasi }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Poin -->
                                        <span
                                            class="flex-shrink-0 text-xs font-bold px-2.5 py-0.5 rounded-full"
                                            :class="jawaban.jawaban_verifikasi === 'invalid'
                                                ? 'bg-orange-100 text-orange-700'
                                                : 'bg-emerald-50 text-emerald-700'"
                                        >
                                            {{ hitungPoinJawaban(jawaban) }} poin
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <p v-else class="text-slate-300 text-xs text-center py-4">Tidak ada data jawaban.</p>
                        </div>

                        <!-- Skor & Rekomendasi Sistem -->
                        <div class="flex items-center justify-between p-4 rounded-xl bg-blue-50 border border-blue-200">
                            <span class="text-slate-700 text-sm font-semibold">Total Nilai Verifikasi Lapangan</span>
                            <span class="text-blue-700 text-xl font-bold">{{ selectedRow.verifikasi?.total_nilai ?? '-' }} / 100</span>
                        </div>

                        <div
                            class="p-5 rounded-xl border-2"
                            :style="{ background: getCfg(selectedRow.verifikasi?.rekomendasi_sistem).bg, borderColor: getCfg(selectedRow.verifikasi?.rekomendasi_sistem).border }"
                        >
                            <p class="text-slate-600 text-xs font-bold uppercase tracking-widest mb-2">Rekomendasi Sistem</p>
                            <p class="text-2xl font-bold" :style="{ color: getCfg(selectedRow.verifikasi?.rekomendasi_sistem).color }">
                                {{ selectedRow.verifikasi?.rekomendasi_sistem || '-' }}
                            </p>
                            <p class="text-slate-500 text-xs mt-1">
                                <span v-if="selectedRow.verifikasi?.rekomendasi_sistem === 'Class A'">Premium — semua kriteria utama terpenuhi</span>
                                <span v-else-if="selectedRow.verifikasi?.rekomendasi_sistem === 'Class B'">Standard — sebagian besar kriteria terpenuhi</span>
                                <span v-else-if="selectedRow.verifikasi?.rekomendasi_sistem === 'Class C'">Basic — kriteria minimal terpenuhi</span>
                                <span v-else>Nilai tidak mencukupi standar minimum</span>
                            </p>
                        </div>

                        <!-- Keputusan Admin (jika sudah selesai) -->
                        <div v-if="selectedRow.status_klasifikasi === 'selesai' && selectedRow.verifikasi?.keputusan_admin"
                            class="p-4 rounded-xl border-2 border-emerald-200 bg-emerald-50">
                            <p class="text-emerald-700 text-xs font-bold uppercase tracking-widest mb-1">Keputusan Admin</p>
                            <p class="text-2xl font-bold text-emerald-800">{{ selectedRow.verifikasi.keputusan_admin }}</p>
                            <p class="text-emerald-600 text-xs mt-1">Validasi telah selesai dan tidak dapat diubah.</p>
                        </div>

                        <!-- Tombol Pilih Kelas -->
                        <div v-if="selectedRow.status_klasifikasi !== 'selesai'" class="border-t border-slate-100 pt-5">
                            <h4 class="text-slate-700 text-sm font-bold mb-1">Validasi Klasifikasi Supplier</h4>
                            <p class="text-slate-400 text-xs mb-4">Pilih kelas yang sesuai berdasarkan hasil verifikasi. Keputusan tidak dapat diubah setelah dikonfirmasi.</p>
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    v-for="kelas in ['Class A', 'Class B', 'Class C']"
                                    :key="kelas"
                                    @click="handleValidasi(kelas)"
                                    :disabled="isValidating"
                                    class="flex flex-col items-center gap-2 py-5 rounded-xl border-2 font-bold transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :style="{ background: getCfg(kelas).bg, borderColor: getCfg(kelas).border, color: getCfg(kelas).color }"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                    <span class="text-base">{{ kelas }}</span>
                                    <span class="text-xs font-normal opacity-70">
                                        {{ kelas === 'Class A' ? 'Premium' : kelas === 'Class B' ? 'Standard' : 'Basic' }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
