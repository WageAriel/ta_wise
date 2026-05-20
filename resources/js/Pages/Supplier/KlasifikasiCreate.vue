<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import SidebarSupplier from '@/Components/SidebarSupplier.vue';
import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ── State ─────────────────────────────────────────────────────────────────────
const pertanyaans    = ref([]);
const isLoading      = ref(true);
const errorMsg       = ref('');
const jawabanLokal   = ref({});
const isSubmitting   = ref(false);
const showSuccess    = ref(false);

// ── Fetch pertanyaan ──────────────────────────────────────────────────────────
onMounted(async () => {
    try {
        const res = await axios.get('/api/klasifikasi/pertanyaan');
        pertanyaans.value = res.data;
    } catch (err) {
        errorMsg.value = err.response?.status === 409
            ? err.response.data.message
            : 'Gagal memuat pertanyaan. Silakan muat ulang halaman.';
    } finally {
        isLoading.value = false;
    }
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function pilihOpsi(idPertanyaan, idOpsi) {
    jawabanLokal.value = { ...jawabanLokal.value, [idPertanyaan]: idOpsi };
}
function getOpsiDipilih(pertanyaan) {
    const idOpsi = jawabanLokal.value[pertanyaan.id_pertanyaan];
    return idOpsi ? pertanyaan.opsis.find(o => o.id_opsi === idOpsi) : null;
}
function hitungPoin(opsi, bobot) {
    return Math.round((opsi.nilai / 100) * bobot);
}

// ── Computed ──────────────────────────────────────────────────────────────────
const totalSkor = computed(() =>
    pertanyaans.value.reduce((acc, p) => {
        const opsi = getOpsiDipilih(p);
        return acc + (opsi ? hitungPoin(opsi, p.bobot) : 0);
    }, 0)
);

const prediksiKelas = computed(() => {
    const s = totalSkor.value;
    if (s >= 85) return { kelas: 'Class A', warna: '#16a34a', bg: '#f0fdf4', desc: 'Premium — Fasilitas sangat memadai' };
    if (s >= 60) return { kelas: 'Class B', warna: '#ea580c', bg: '#fff7ed', desc: 'Standard — Fasilitas memadai' };
    if (s >= 30) return { kelas: 'Class C', warna: '#2563eb', bg: '#eff6ff', desc: 'Basic — Fasilitas minimal' };
    return { kelas: 'Belum Memenuhi', warna: '#64748b', bg: '#f8fafc', desc: 'Perlu peningkatan fasilitas' };
});

const semuaDijawab = computed(() =>
    pertanyaans.value.length > 0 &&
    pertanyaans.value.every(p => jawabanLokal.value[p.id_pertanyaan] !== undefined)
);

const sudahDijawabCount = computed(() =>
    pertanyaans.value.filter(p => jawabanLokal.value[p.id_pertanyaan] !== undefined).length
);

// ── Submit ────────────────────────────────────────────────────────────────────
async function submitPengajuan(e) {
    e.preventDefault();
    if (!semuaDijawab.value) return;
    isSubmitting.value = true;
    errorMsg.value = '';
    try {
        const jawaban = Object.entries(jawabanLokal.value).map(([id, idOpsi]) => ({
            id_pertanyaan: parseInt(id),
            id_opsi: idOpsi,
        }));
        await axios.post('/supplier/classification/ajukan', { jawaban });
        showSuccess.value = true;
    } catch (err) {
        errorMsg.value = err.response?.data?.message ?? 'Gagal mengirim pengajuan. Coba lagi.';
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <Head title="Klasifikasi Supplier | WISE" />

    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">

        <!-- ── Sidebar ── -->
        <SidebarSupplier class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <!-- ── Main Content (Scrollable) ── -->
        <main class="flex-1 h-full overflow-y-auto">
            <div class="space-y-5 max-w-3xl mx-auto px-6 py-10">

                <!-- Loading -->
                <div v-if="isLoading" class="flex flex-col items-center justify-center py-32 gap-4">
                    <div class="w-10 h-10 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-slate-400 text-sm font-medium">Memuat pertanyaan...</p>
                </div>

                <template v-else>
                    <!-- Error -->
                    <div v-if="errorMsg && !showSuccess"
                        class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
                        <p class="text-amber-800 font-bold">{{ errorMsg }}</p>
                    </div>

                    <!-- Sukses -->
                    <div v-if="showSuccess" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center">
                        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-5">
                            <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-extrabold text-slate-900 mb-2">Pengajuan Terkirim!</h2>
                        <p class="text-slate-500 text-sm">Pengajuan klasifikasi Anda sedang menunggu verifikasi lapangan.</p>
                    </div>

                    <form v-else @submit.prevent="submitPengajuan" class="space-y-5">

                        <!-- 1. Header -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-slate-900 text-xl font-bold">Pengajuan Klasifikasi Supplier</h1>
                                    <p class="text-slate-500 text-sm">Jawab pertanyaan berikut untuk menentukan klasifikasi perusahaan Anda</p>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Score Card -->
                        <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl p-6 border-2 border-orange-200">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-slate-900 font-bold text-sm mb-1">Total Skor Pengajuan</h3>
                                    <p class="text-slate-600 text-xs">{{ sudahDijawabCount }} dari {{ pertanyaans.length }} pertanyaan dijawab</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-orange-700 text-4xl font-bold leading-none">{{ totalSkor }}</div>
                                    <div class="text-slate-600 text-xs mt-1">dari 100 poin</div>
                                </div>
                            </div>
                            <div class="p-4 rounded-xl border-2 transition-all"
                                :style="{ background: prediksiKelas.bg, borderColor: prediksiKelas.warna }">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        :style="{ color: prediksiKelas.warna }">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                    <div>
                                        <p class="text-slate-700 text-xs font-semibold mb-0.5">Prediksi Klasifikasi</p>
                                        <p class="text-lg font-bold" :style="{ color: prediksiKelas.warna }">{{ prediksiKelas.kelas }}</p>
                                        <p class="text-slate-600 text-xs">{{ prediksiKelas.desc }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Pertanyaan -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                            <h2 class="text-slate-900 text-base font-bold mb-5">Pertanyaan Klasifikasi</h2>
                            <div class="space-y-6">
                                <div
                                    v-for="(p, idx) in pertanyaans"
                                    :key="p.id_pertanyaan"
                                    class="p-5 rounded-xl border-2 transition-all"
                                    :style="{
                                        background: jawabanLokal[p.id_pertanyaan] ? '#f0f9ff' : '#f8fafc',
                                        borderColor: jawabanLokal[p.id_pertanyaan] ? '#bae6fd' : '#e2e8f0',
                                    }"
                                >
                                    <div class="mb-4">
                                        <div class="flex items-start justify-between mb-1 gap-3">
                                            <h3 class="text-slate-900 text-sm font-semibold flex-1">
                                                {{ idx + 1 }}. {{ p.teks_pertanyaan }}
                                            </h3>
                                            <span v-if="getOpsiDipilih(p)"
                                                class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold flex-shrink-0"
                                                style="color: #0ea5e9; background: #e0f2fe;">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ hitungPoin(getOpsiDipilih(p), p.bobot) }} poin
                                            </span>
                                        </div>
                                        <p class="text-slate-500 text-xs">Bobot: {{ p.bobot }} poin</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label
                                            v-for="opsi in p.opsis"
                                            :key="opsi.id_opsi"
                                            class="flex items-start gap-3 p-4 rounded-lg border-2 cursor-pointer transition-all hover:bg-white"
                                            :style="{
                                                background: jawabanLokal[p.id_pertanyaan] === opsi.id_opsi ? '#ffffff' : 'transparent',
                                                borderColor: jawabanLokal[p.id_pertanyaan] === opsi.id_opsi ? '#0ea5e9' : '#e2e8f0',
                                            }"
                                        >
                                            <input type="radio"
                                                :name="`pertanyaan_${p.id_pertanyaan}`"
                                                :value="opsi.id_opsi"
                                                :checked="jawabanLokal[p.id_pertanyaan] === opsi.id_opsi"
                                                @change="pilihOpsi(p.id_pertanyaan, opsi.id_opsi)"
                                                class="mt-0.5 accent-sky-500"
                                            />
                                            <div class="flex-1 flex items-center justify-between gap-2">
                                                <span class="text-slate-800 text-sm"
                                                    :class="jawabanLokal[p.id_pertanyaan] === opsi.id_opsi ? 'font-semibold' : 'font-medium'">
                                                    {{ opsi.teks_opsi }}
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0"
                                                    :style="{
                                                        color: jawabanLokal[p.id_pertanyaan] === opsi.id_opsi ? '#0ea5e9' : '#64748b',
                                                        background: jawabanLokal[p.id_pertanyaan] === opsi.id_opsi ? '#e0f2fe' : '#f1f5f9',
                                                    }">
                                                    {{ hitungPoin(opsi, p.bobot) }} poin
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Info Box -->
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-amber-900 text-sm font-semibold mb-1">Proses Verifikasi Lapangan</h4>
                                    <p class="text-amber-700 text-xs mb-2">
                                        Setelah pengajuan dikirim, petugas lapangan akan melakukan verifikasi untuk memvalidasi jawaban Anda. Hasil verifikasi menentukan klasifikasi final.
                                    </p>
                                    <p class="text-amber-700 text-xs">
                                        <strong>Penting:</strong> Pastikan semua jawaban sesuai kondisi aktual fasilitas.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Error submit -->
                        <div v-if="errorMsg" class="px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                            {{ errorMsg }}
                        </div>

                        <!-- 6. Tombol Submit -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button"
                                    class="px-5 py-2.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                    Batal
                                </button>
                                <button type="submit"
                                    :disabled="!semuaDijawab || isSubmitting"
                                    class="flex items-center gap-2 px-6 py-2.5 rounded-lg text-white text-sm font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md"
                                    style="background: linear-gradient(135deg, #ea580c, #f97316);">
                                    <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    {{ isSubmitting ? 'Mengirim...' : 'Kirim Pengajuan' }}
                                </button>
                            </div>
                        </div>

                    </form>
                </template>

            </div>
        </main>
    </div>
</template>

<style scoped>
main::-webkit-scrollbar { width: 6px; }
main::-webkit-scrollbar-track { background: transparent; }
main::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
main::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
</style>
