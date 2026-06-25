<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SidebarSupplier from '@/Components/SidebarSupplier.vue';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import axios from 'axios';

const rows = ref([]);
const pagination = ref({});
const stats = ref({
    total_pengajuan: 0,
    disetujui: 0,
    menunggu_validasi: 0
});
const canSubmit = ref(false);
const hasSubmittedThisYear = ref(false);
const currentPage = ref(1);
const perPage = ref(10);

const showDetailModal = ref(false);
const selectedRow = ref(null);

function openDetailModal(row) {
    selectedRow.value = row;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedRow.value = null;
}

function hitungPoinJawaban(jawaban) {
    if (!jawaban.opsi_verifikasi) return jawaban.opsi?.nilai ?? 0;
    const bobot = jawaban.pertanyaan?.bobot ?? 0;
    const nilai = jawaban.opsi_verifikasi?.nilai ?? 0;
    return Math.round((nilai / 100) * bobot);
}

const rekomendasiConfig = {
    'Class A': { color: '#16a34a', bg: '#f0fdf4', border: '#bbf7d0' },
    'Class B': { color: '#ea580c', bg: '#fff7ed', border: '#fed7aa' },
    'Class C': { color: '#2563eb', bg: '#eff6ff', border: '#bfdbfe' },
    'Belum Memenuhi': { color: '#64748b', bg: '#f8fafc', border: '#e2e8f0' },
};

function getCfg(kelas) {
    return rekomendasiConfig[kelas] ?? rekomendasiConfig['Belum Memenuhi'];
}

async function fetchData(page = 1) {
    try {
        currentPage.value = page;
        const response = await axios.get('/supplier/classification/data', {
            params: {
                page: page,
                per_page: perPage.value
            }
        });
        
        rows.value = response.data.data.data;
        pagination.value = response.data.data;
        stats.value = response.data.stats;
        canSubmit.value = response.data.can_submit_classification;
        hasSubmittedThisYear.value = response.data.has_submitted_this_year;
    } catch (error) {
        console.error("Error fetching classification data:", error);
    }
}

onMounted(() => {
    fetchData(1);
});

// Helper labels & classes
function getStatusLabel(status) {
    const map = {
        'pending': 'Menunggu Validasi',
        'diproses': 'Sedang Diproses',
        'selesai': 'Selesai / Disetujui',
        'ditolak': 'Ditolak'
    };
    return map[status] || status;
}

function statusClass(status) {
    if (status === "selesai") return "bg-emerald-50 text-emerald-700 border border-emerald-200";
    if (status === "ditolak") return "bg-red-50 text-red-700 border border-red-200";
    if (status === "diproses") return "bg-blue-50 text-blue-700 border border-blue-200";
    return "bg-amber-50 text-amber-700 border border-amber-200";
}

function getKategori(score) {
    if (score === null || score === undefined) return null;
    if (score >= 80) return 'A';
    if (score >= 60) return 'B';
    return 'C';
}

function getKelasName(row) {
    if (row.status_klasifikasi === 'selesai' && row.verifikasi?.keputusan_admin) {
        return row.verifikasi.keputusan_admin;
    }
    return '-';
}

function kategoriClass(k) {
    if (!k || k === '-') return "bg-slate-100 text-slate-400";
    if (k.includes('A')) return "bg-green-100 text-green-700";
    if (k.includes('B')) return "bg-indigo-100 text-indigo-700";
    if (k.includes('C')) return "bg-orange-100 text-orange-700";
    return "bg-slate-100 text-slate-500";
}

const paginationLinks = computed(() => {
    if (!pagination.value.links) return [];
    return pagination.value.links
        .filter(l => !l.label.includes('Previous') && !l.label.includes('Next'))
        .map(l => ({
            ...l,
            page: parseInt(l.label)
        }));
});

function getPetugas(row) {
    if (row.verifikasi?.petugas?.profil_petugas?.nama_petugas) return row.verifikasi.petugas.profil_petugas.nama_petugas;
    if (row.verifikasi?.petugas?.username) return row.verifikasi.petugas.username;
    if (row.jadwal_kunjungan?.petugas?.profil_petugas?.nama_petugas) return row.jadwal_kunjungan.petugas.profil_petugas.nama_petugas;
    if (row.jadwal_kunjungan?.petugas?.username) return row.jadwal_kunjungan.petugas.username;
    return '-';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Klasifikasi Saya | WISE" />
    <SupplierLayout>
    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-8xl mx-auto px-6 py-10 lg:px-10">
                <!-- ── Page Header ── -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                            Klasifikasi Saya
                        </h1>
                        <p class="text-slate-500 mt-1 text-sm">
                            Pantau riwayat dan status pengajuan klasifikasi perusahaan Anda.
                        </p>
                    </div>
                    <div>
                        <Link 
                            v-if="canSubmit && !hasSubmittedThisYear"
                            :href="route('supplier.klasifikasi-form')" 
                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-sm transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ajukan Klasifikasi
                        </Link>
                        <button 
                            v-else-if="hasSubmittedThisYear"
                            disabled
                            class="inline-flex items-center gap-2 bg-slate-300 text-white font-semibold px-5 py-2.5 rounded-xl shadow-sm cursor-not-allowed"
                            title="Anda sudah memiliki pengajuan aktif atau klasifikasi final tahun ini"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ajukan Klasifikasi
                        </button>
                        <button 
                            v-else
                            disabled
                            class="inline-flex items-center gap-2 bg-slate-300 text-white font-semibold px-5 py-2.5 rounded-xl shadow-sm cursor-not-allowed"
                            title="Seleksi Anda belum divalidasi/lolos"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ajukan Klasifikasi
                        </button>
                    </div>
                </div>

                <!-- ── STAT CARDS ── -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                    <!-- Total Pengajuan -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-blue-100 text-blue-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Pengajuan</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ stats.total_pengajuan }}</p>
                        </div>
                    </div>

                    <!-- Disetujui -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-emerald-100 text-emerald-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Disetujui</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ stats.disetujui }}</p>
                        </div>
                    </div>

                    <!-- Menunggu Validasi -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-amber-100 text-amber-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Menunggu Validasi</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ stats.menunggu_validasi }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── TABLE SECTION ── -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-left">
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">No</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Tanggal Pengajuan</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Hasil Verifikasi</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Kelas Final</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Status Pengajuan</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Petugas</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr
                                    v-for="(row, index) in rows"
                                    :key="row.id_klasifikasi"
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="px-6 py-4 text-slate-400 text-xs">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                                    <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ formatDate(row.tanggal) }}</td>
                                    <td class="px-6 py-4 text-slate-700">
                                        <span v-if="row.verifikasi?.total_nilai !== undefined && row.verifikasi?.total_nilai !== null" class="font-mono font-bold text-emerald-600">
                                            {{ row.verifikasi.total_nilai }}
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                            :class="kategoriClass(getKelasName(row))"
                                        >
                                            {{ getKelasName(row) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                            :class="statusClass(row.status_klasifikasi)"
                                        >
                                            {{ getStatusLabel(row.status_klasifikasi) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                        <div v-if="getPetugas(row) !== '-'" class="flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                                {{ getPetugas(row).charAt(0).toUpperCase() }}
                                            </span>
                                            <span class="text-sm font-medium">{{ getPetugas(row) }}</span>
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">Belum dijadwalkan</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            @click="openDetailModal(row)"
                                            class="p-1.5 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors"
                                            title="Lihat Detail"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="rows.length === 0">
                                    <td colspan="9" class="py-16 text-center">
                                        <svg class="mx-auto w-12 h-12 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm font-semibold text-slate-400">Belum ada riwayat pengajuan</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <p class="text-xs text-slate-400 font-medium">
                            Menampilkan <span class="font-bold text-slate-600">{{ pagination.from || 0 }}–{{ pagination.to || 0 }}</span> dari
                            <span class="font-bold text-slate-600">{{ pagination.total || 0 }}</span> pengajuan
                        </p>
                        <div class="flex items-center gap-1">
                            <button
                                @click="fetchData(pagination.current_page - 1)"
                                :disabled="!pagination.prev_page_url"
                                class="p-2 rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50 disabled:opacity-40 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <button
                                v-for="link in paginationLinks"
                                :key="link.label"
                                @click="fetchData(link.page)"
                                class="w-9 h-9 rounded-lg border text-xs font-semibold transition-colors"
                                :class="link.active ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-200 text-slate-500 hover:bg-slate-50'"
                            >
                                <span v-html="link.label"></span>
                            </button>
                            <button
                                @click="fetchData(pagination.current_page + 1)"
                                :disabled="!pagination.next_page_url"
                                class="p-2 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    </SupplierLayout>

    <!-- ── Detail Modal ── -->
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="showDetailModal && selectedRow"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                @click.self="closeDetailModal"
            >
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="flex items-start justify-between p-6 border-b border-slate-100">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Detail Pengajuan Klasifikasi</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Riwayat jawaban dan hasil verifikasi</p>
                        </div>
                        <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Skor Pengajuan (Self-Assessment)</p>
                                <p class="text-slate-900 text-2xl font-bold">{{ selectedRow.total_nilai }} <span class="text-sm font-normal">poin</span></p>
                            </div>
                            <div class="p-4 rounded-xl border border-blue-200 bg-blue-50">
                                <p class="text-blue-600 text-xs font-bold uppercase tracking-widest mb-1">Skor Verifikasi Lapangan</p>
                                <p class="text-blue-700 text-2xl font-bold">{{ selectedRow.verifikasi?.total_nilai ?? '-' }} <span class="text-sm font-normal">poin</span></p>
                            </div>
                        </div>

                        <!-- ── Daftar Jawaban ── -->
                        <div>
                            <h4 class="text-slate-700 text-sm font-bold mb-3">Daftar Pertanyaan & Jawaban</h4>
                            <div class="space-y-3">
                                <div
                                    v-for="jawaban in selectedRow.jawaban_klasifikasis"
                                    :key="jawaban.id_jawaban"
                                    class="rounded-xl border p-4 transition-all"
                                    :class="jawaban.jawaban_verifikasi === 'invalid'
                                        ? 'border-orange-200 bg-orange-50/40'
                                        : 'border-slate-200 bg-white'"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-2 flex-1 min-w-0">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                                    <span class="text-slate-800 text-sm font-semibold leading-snug">
                                                        {{ jawaban.pertanyaan?.teks_pertanyaan }}
                                                    </span>
                                                    <span
                                                        v-if="jawaban.jawaban_verifikasi === 'invalid'"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-orange-500 text-white font-bold"
                                                        style="font-size:9px"
                                                    >
                                                        TIDAK SESUAI
                                                    </span>
                                                </div>

                                                <div
                                                    class="mt-2 mb-1 px-2 py-1.5 rounded-lg flex items-center gap-2 flex-wrap border bg-slate-50"
                                                    style="font-size:12px"
                                                >
                                                    <span class="text-slate-500 font-semibold">Pengajuan Anda:</span>
                                                    <span class="px-1.5 py-0.5 rounded bg-white text-slate-700 font-bold border border-slate-200">
                                                        {{ jawaban.opsi?.teks_opsi ?? '—' }}
                                                    </span>
                                                    <template v-if="jawaban.jawaban_verifikasi === 'invalid'">
                                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                        <span class="text-orange-600 font-semibold">Koreksi Verifikasi:</span>
                                                        <span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 font-bold">
                                                            {{ jawaban.opsi_verifikasi?.teks_opsi ?? '—' }}
                                                        </span>
                                                    </template>
                                                </div>

                                                <p
                                                    v-if="jawaban.catatan_verifikasi"
                                                    class="text-slate-500 mt-2 bg-white p-2 rounded border border-slate-100 italic"
                                                    style="font-size:12px"
                                                >
                                                    <span class="font-semibold text-slate-600 mr-1">Catatan Petugas:</span>
                                                    {{ jawaban.catatan_verifikasi }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Poin -->
                                        <span
                                            class="flex-shrink-0 text-xs font-bold px-2.5 py-0.5 rounded-full mt-1"
                                            :class="jawaban.jawaban_verifikasi === 'invalid'
                                                ? 'bg-orange-100 text-orange-700'
                                                : 'bg-emerald-50 text-emerald-700'"
                                        >
                                            {{ hitungPoinJawaban(jawaban) }} poin
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Keputusan Final -->
                        <div v-if="selectedRow.status_klasifikasi === 'selesai' && selectedRow.verifikasi?.keputusan_admin"
                            class="p-5 rounded-xl border-2 border-emerald-200 bg-emerald-50 text-center">
                            <p class="text-emerald-700 text-xs font-bold uppercase tracking-widest mb-1">Keputusan Kelas Final Anda</p>
                            <p class="text-3xl font-bold text-emerald-800">{{ selectedRow.verifikasi.keputusan_admin }}</p>
                            <p class="text-emerald-600 text-xs mt-1">Klasifikasi ini telah disetujui dan diverifikasi oleh Admin.</p>
                        </div>
                        <div v-else-if="selectedRow.status_klasifikasi === 'ditolak'"
                            class="p-5 rounded-xl border-2 border-red-200 bg-red-50 text-center">
                            <p class="text-red-700 text-xs font-bold uppercase tracking-widest mb-1">Status Pengajuan</p>
                            <p class="text-2xl font-bold text-red-800">Ditolak</p>
                        </div>
                        <div v-else
                            class="p-5 rounded-xl border-2 border-slate-200 bg-slate-50 text-center">
                            <p class="text-slate-600 text-xs font-bold tracking-widest mb-1">Status saat ini</p>
                            <p class="text-lg font-bold text-slate-800">{{ getStatusLabel(selectedRow.status_klasifikasi) }}</p>
                        </div>

                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
main::-webkit-scrollbar { width: 6px; }
main::-webkit-scrollbar-track { background: transparent; }
main::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
main::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
</style>
