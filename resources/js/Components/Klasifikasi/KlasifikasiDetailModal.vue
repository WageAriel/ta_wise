<script setup>
const props = defineProps({
    show: Boolean,
    selectedRow: Object,
    selectedDetail: Object,
    isFetchingDetail: Boolean,
});

const emit = defineEmits(['close']);
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
                            <h3 class="text-lg font-bold text-slate-900">Detail Validasi Akhir</h3>
                            <p class="text-sm text-slate-500 mt-0.5">{{ selectedRow.supplier?.nama_perusahaan }}</p>
                        </div>
                        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <!-- Keputusan Admin (jika sudah selesai) -->
                        <div v-if="selectedRow.status_klasifikasi === 'selesai' && selectedRow.verifikasi?.keputusan_admin"
                            class="p-4 rounded-xl border-2 border-emerald-200 bg-emerald-50">
                            <p class="text-emerald-700 text-xs font-bold uppercase tracking-widest mb-1">Keputusan Final Admin</p>
                            <p class="text-2xl font-bold text-emerald-800">{{ selectedRow.verifikasi.keputusan_admin }}</p>
                        </div>

                        <!-- Ketidaksesuaian skor / Info Skor -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-lg bg-white border border-slate-200">
                                <p class="text-slate-500 text-xs font-bold uppercase mb-1">Nilai Pengajuan</p>
                                <p class="text-slate-900 text-xl font-bold">{{ selectedRow.total_nilai }} <span class="text-sm font-normal">poin</span></p>
                                <p class="text-slate-500 text-xs">Self-assessment supplier</p>
                            </div>
                            <div class="p-4 rounded-lg bg-white border border-slate-200">
                                <p class="text-slate-500 text-xs font-bold uppercase mb-1">Nilai Verifikasi</p>
                                <p class="text-slate-900 text-xl font-bold">{{ selectedRow.verifikasi?.total_nilai ?? '-' }} <span class="text-sm font-normal">poin</span></p>
                                <p class="text-slate-500 text-xs">Hasil verifikasi petugas</p>
                            </div>
                        </div>

                        <!-- Petugas Info -->
                        <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-blue-700 text-sm font-bold">
                                    {{ (selectedRow.verifikasi?.petugas?.username || 'P').charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div>
                                <p class="text-slate-700 text-sm font-semibold">{{ selectedRow.verifikasi?.petugas?.username || 'Petugas' }}</p>
                                <p class="text-slate-500 text-xs">Petugas Lapangan • {{ selectedRow.verifikasi?.tanggal }}</p>
                            </div>
                        </div>

                        <!-- ── Daftar Verifikasi Kriteria ── -->
                        <div>
                            <h4 class="text-slate-700 text-sm font-bold mb-3">Daftar Pertanyaan & Jawaban</h4>

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
                                            <div class="flex-1 min-w-0">
                                                <!-- Judul pertanyaan -->
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
                                                        BEDA DENGAN VERIFIKASI
                                                    </span>
                                                </div>

                                                <!-- Jawaban -->
                                                <div
                                                    class="mt-2 mb-1 px-2 py-1.5 rounded-lg flex items-center gap-2 flex-wrap border"
                                                    :class="jawaban.jawaban_verifikasi === 'invalid'
                                                        ? 'bg-white border-orange-200'
                                                        : 'bg-slate-50 border-slate-100'"
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
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-slate-300 text-xs text-center py-4">Tidak ada data jawaban.</p>
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
