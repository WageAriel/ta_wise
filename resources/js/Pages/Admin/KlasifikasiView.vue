<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
import SidebarAdmin from "@/Components/SidebarAdmin.vue";
import axios from "axios";

// ─── Stats & Data State ──────────────────────────────────────────────────
const stats = ref({
    menunggu_validasi: 0,
    total_pengajuan: 0,
    pengajuan_bulan_ini: 0,
});

const rows = ref([]);
const pagination = ref({});
const currentPage = ref(1);

// ─── Filter & Search State ─────────────────────────────────────────────────
const search       = ref("");
const filterStatus = ref("semua");   // semua | pending | diproses | selesai | ditolak
const filterTahun  = ref(new Date().getFullYear());
const perPage      = ref(10);

const tahunOptions = computed(() => {
    const now = new Date().getFullYear();
    return Array.from({ length: 5 }, (_, i) => now - i);
});

async function fetchData(page = 1) {
    try {
        currentPage.value = page;
        const response = await axios.get('/api/admin/klasifikasi', {
            params: {
                search: search.value,
                status: filterStatus.value === 'semua' ? '' : filterStatus.value,
                tahun: filterTahun.value,
                per_page: perPage.value,
                page: page
            }
        });
        
        rows.value = response.data.data.data;
        pagination.value = response.data.data;
        stats.value = response.data.stats;
    } catch (error) {
        console.error("Error fetching data:", error);
    }
}

function applyFilter() {
    fetchData(1);
}

function clearSearch() {
    search.value = "";
    applyFilter();
}

onMounted(() => {
    fetchData(1);
});

// Tampilkan kolom Petugas hanya saat filter pending atau semua
const showPetugasCol = computed(() =>
    filterStatus.value === "semua" || filterStatus.value === "pending" || filterStatus.value === "diproses"
);

const filteredRows = computed(() => rows.value);

// ─── Badge & Label Helpers ─────────────────────────────────────────────────────────
function statusClass(status) {
    if (status === "selesai") return "bg-emerald-50 text-emerald-700 border border-emerald-200";
    if (status === "ditolak") return "bg-red-50 text-red-700 border border-red-200";
    if (status === "diproses") return "bg-blue-50 text-blue-700 border border-blue-200";
    return "bg-amber-50 text-amber-700 border border-amber-200";
}

function getStatusLabel(status) {
    const map = {
        'pending': 'Menunggu',
        'diproses': 'Diproses',
        'selesai': 'Selesai',
        'ditolak': 'Ditolak'
    };
    return map[status] || status;
}

function getKategori(score) {
    if (score === null || score === undefined) return null;
    if (score >= 80) return 'A';
    if (score >= 60) return 'B';
    return 'C';
}

function kategoriClass(k) {
    if (!k) return "bg-slate-100 text-slate-400";
    const map = { A: "bg-blue-100 text-blue-700", B: "bg-indigo-100 text-indigo-700", C: "bg-orange-100 text-orange-700" };
    return map[k] ?? "bg-slate-100 text-slate-500";
}

// ─── Pagination Helpers ────────────────────────────────────────────────────
const paginationLinks = computed(() => {
    if (!pagination.value.links) return [];
    return pagination.value.links
        .filter(l => !l.label.includes('Previous') && !l.label.includes('Next'))
        .map(l => ({
            ...l,
            page: parseInt(l.label)
        }));
});

// ─── Import / Export modal ─────────────────────────────────────────────────
const showImportModal = ref(false);
const importFile      = ref(null);

function handleImportFile(e) {
    importFile.value = e.target.files[0] ?? null;
}

function submitImport() {
    if (!importFile.value) return;
    // Kirim ke controller nanti
    showImportModal.value = false;
    importFile.value = null;
}

function doExport() {
    window.location.href = route("admin.supplier.classification") + "?export=1";
}
</script>

<template>
    <Head title="Klasifikasi Supplier | WISE" />

    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <!-- Sidebar -->
        <SidebarAdmin class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <!-- Main -->
        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-7xl mx-auto px-6 py-10 lg:px-10">

                <!-- ── Page Header ── -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                            Klasifikasi Supplier
                        </h1>
                        <p class="text-slate-500 mt-1 text-sm">
                            Kelola dan validasi pengajuan klasifikasi dari seluruh supplier terdaftar.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                        Periode {{ new Date().getFullYear() }}
                    </div>
                </div>

                <!-- ── STAT CARDS ── -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
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
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">
                                {{ stats.menunggu_validasi }}
                            </p>
                        </div>
                    </div>

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
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">
                                {{ stats.total_pengajuan }}
                            </p>
                        </div>
                    </div>

                    <!-- Pengajuan Bulan Ini -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-emerald-100 text-emerald-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Pengajuan Bulan Ini</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">
                                {{ stats.pengajuan_bulan_ini }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ── IMPORT / EXPORT SECTION ── -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Import &amp; Export Data</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Upload data klasifikasi via Excel atau unduh laporan.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Import -->
                            <button
                                @click="showImportModal = true"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-sm font-semibold hover:bg-blue-100 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Import Excel
                            </button>
                            <!-- Export -->
                            <button
                                @click="doExport"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold hover:bg-emerald-100 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export Excel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── TABLE SECTION ── -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                    <!-- Table Toolbar -->
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center gap-4">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-sm">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                @keyup.enter="applyFilter"
                                type="text"
                                placeholder="Cari nama supplier..."
                                class="w-full pl-9 pr-9 py-2.5 rounded-xl border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none"
                            />
                            <button
                                v-if="search"
                                @click="clearSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Filter Status -->
                        <div class="flex items-center gap-1 bg-slate-100  p-1 rounded-xl">
                            <button
                                v-for="opt in [
                                    { val: 'semua', label: 'Semua' },
                                    { val: 'pending', label: 'Menunggu' },
                                    { val: 'diproses', label: 'Diproses' },
                                    { val: 'selesai', label: 'Selesai' },
                                ]"
                                :key="opt.val"
                                @click="filterStatus = opt.val; applyFilter()"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                                :class="filterStatus === opt.val
                                    ? 'bg-white text-blue-600 shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700'"
                            >
                                {{ opt.label }}
                            </button>
                        </div>

                        <!-- Filter Tahun -->
                        <select
                            v-model="filterTahun"
                            @change="applyFilter"
                            class="px-7 py-2.53 rounded-xl border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none"
                        >
                            <option v-for="y in tahunOptions" :key="y" :value="y">{{ y }}</option>
                        </select>

                        <!-- Per Page -->
                        <select
                            v-model="perPage"
                            @change="applyFilter"
                            class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-left">
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">#</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Nama Supplier</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Skor</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Kategori</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Status</th>
                                    <th
                                        v-if="showPetugasCol"
                                        class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap"
                                    >Petugas</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Tanggal</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr
                                    v-for="(row, index) in filteredRows"
                                    :key="row.id_klasifikasi"
                                    class="hover:bg-slate-50/60 transition-colors"
                                >
                                    <td class="px-6 py-4 text-slate-400 text-xs">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                                    <td class="px-6 py-4 font-semibold text-slate-800">{{ row.supplier?.nama_perusahaan || '-' }}</td>
                                    <td class="px-6 py-4 text-slate-700">
                                        <span v-if="row.total_nilai !== null" class="font-mono font-bold">{{ row.total_nilai }}</span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="getKategori(row.total_nilai)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                            :class="kategoriClass(getKategori(row.total_nilai))"
                                        >
                                            Kategori {{ getKategori(row.total_nilai) }}
                                        </span>
                                        <span v-else class="text-slate-300 text-xs">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                            :class="statusClass(row.status_klasifikasi)"
                                        >
                                            {{ getStatusLabel(row.status_klasifikasi) }}
                                        </span>
                                    </td>
                                    <!-- Kolom Petugas (hanya tab Menunggu / Semua) -->
                                    <td v-if="showPetugasCol" class="px-6 py-4">
                                        <span
                                            v-if="row.verifikasi?.admin"
                                            class="inline-flex items-center gap-1.5 text-sm text-slate-700 font-medium"
                                        >
                                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                                {{ row.verifikasi.admin.name.charAt(0).toUpperCase() }}
                                            </span>
                                            {{ row.verifikasi.admin.name }}
                                        </span>
                                        <span v-else class="text-xs text-slate-300">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 text-xs whitespace-nowrap">{{ row.tanggal }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
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
                                            <button
                                                v-if="row.status_klasifikasi === 'pending'"
                                                class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors"
                                                title="Validasi"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="filteredRows.length === 0">
                                    <td colspan="7" class="py-16 text-center">
                                        <svg class="mx-auto w-12 h-12 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm font-semibold text-slate-400">Tidak ada data ditemukan</p>
                                        <p class="text-xs text-slate-300 mt-1">Coba ubah filter atau kata kunci pencarian</p>
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
                            <!-- Prev -->
                            <button
                                @click="fetchData(pagination.current_page - 1)"
                                :disabled="!pagination.prev_page_url"
                                class="p-2 rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50 disabled:opacity-40 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <!-- Page numbers -->
                            <button
                                v-for="link in paginationLinks"
                                :key="link.label"
                                @click="fetchData(link.page)"
                                class="w-9 h-9 rounded-lg border text-xs font-semibold transition-colors"
                                :class="link.active
                                    ? 'bg-blue-600 border-blue-600 text-white'
                                    : 'border-slate-200 text-slate-500 hover:bg-slate-50'"
                            >
                                <span v-html="link.label"></span>
                            </button>
                            <!-- Next -->
                            <button
                                @click="fetchData(pagination.current_page + 1)"
                                :disabled="!pagination.next_page_url"
                                class="p-2 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- end table section -->

            </div>
        </main>
    </div>

    <!-- ── Import Modal ── -->
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="showImportModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
                @click.self="showImportModal = false"
            >
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-900">Import Data Klasifikasi</h3>
                        <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Upload Area -->
                    <label class="block cursor-pointer">
                        <input type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="handleImportFile" />
                        <div
                            class="border-2 border-dashed rounded-xl p-8 flex flex-col items-center text-center transition-colors"
                            :class="importFile ? 'border-blue-400 bg-blue-50' : 'border-slate-300 hover:border-blue-400 hover:bg-slate-50'"
                        >
                            <svg class="w-10 h-10 mb-3" :class="importFile ? 'text-blue-500' : 'text-slate-300'"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <p v-if="importFile" class="text-sm font-bold text-blue-700">{{ importFile.name }}</p>
                            <p v-else class="text-sm font-semibold text-slate-500">Klik untuk pilih file</p>
                            <p class="text-xs text-slate-400 mt-1">Format: .xlsx, .xls, .csv</p>
                        </div>
                    </label>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            @click="showImportModal = false"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitImport"
                            :disabled="!importFile"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-40 transition-colors"
                        >
                            Upload & Import
                        </button>
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

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>