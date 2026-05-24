<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
import SidebarAdmin from "@/Components/SidebarAdmin.vue";
import axios from "axios";
import Swal from 'sweetalert2';

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

// Tampilkan kolom Petugas selalu
const showPetugasCol = computed(() => true);

const filteredRows = computed(() => rows.value);

// ─── Format Helpers ────────────────────────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
}

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

function getKelasName(row) {
    if (row.verifikasi?.keputusan_admin) return row.verifikasi.keputusan_admin;
    if (row.verifikasi?.rekomendasi_sistem) return row.verifikasi.rekomendasi_sistem;
    return '-';
}

function kategoriClass(k) {
    if (!k || k === '-') return "bg-slate-100 text-slate-400";
    if (k.includes('A')) return "bg-green-100 text-green-700";
    if (k.includes('B')) return "bg-indigo-100 text-indigo-700";
    if (k.includes('C')) return "bg-orange-100 text-orange-700";
    return "bg-slate-100 text-slate-500";
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
    showImportModal.value = false;
    importFile.value = null;
}

function doExport() {
    window.location.href = route("admin.supplier.classification") + "?export=1";
}

// ─── Validasi Modal ───────────────────────────────────────────────────────
const showValidasiModal  = ref(false);
const selectedRow        = ref(null);
const selectedDetail     = ref(null);  // full data from /api/admin/klasifikasi/{id}
const isFetchingDetail   = ref(false);
const isValidating       = ref(false);

async function openValidasiModal(row) {
    selectedRow.value    = row;
    selectedDetail.value = null;
    showValidasiModal.value = true;
    isFetchingDetail.value  = true;
    try {
        const res = await axios.get(`/api/admin/klasifikasi/${row.id_klasifikasi}`);
        selectedDetail.value = res.data;
    } catch (e) {
        console.error('Failed to fetch detail:', e);
    } finally {
        isFetchingDetail.value = false;
    }
}

function closeValidasiModal() {
    showValidasiModal.value  = false;
    selectedRow.value        = null;
    selectedDetail.value     = null;
}

// ─── Detail Modal ─────────────────────────────────────────────────────────
const showDetailModal  = ref(false);

async function openDetailModal(row) {
    selectedRow.value    = row;
    selectedDetail.value = null;
    showDetailModal.value = true;
    isFetchingDetail.value  = true;
    try {
        const res = await axios.get(`/api/admin/klasifikasi/${row.id_klasifikasi}`);
        selectedDetail.value = res.data;
    } catch (e) {
        console.error('Failed to fetch detail:', e);
    } finally {
        isFetchingDetail.value = false;
    }
}

function closeDetailModal() {
    showDetailModal.value  = false;
    selectedRow.value        = null;
    selectedDetail.value     = null;
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

async function handleValidasi(kelas) {
    const result = await Swal.fire({
        title: `Tetapkan ${kelas}?`,
        html: `<p style="font-size:14px;color:#475569">Anda akan menetapkan <strong>${selectedRow.value?.supplier?.nama_perusahaan}</strong> ke <strong>${kelas}</strong>.<br><br>Keputusan ini <strong>tidak dapat diubah</strong> setelah dikonfirmasi.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: `Ya, Tetapkan ${kelas}`,
        cancelButtonText: 'Batal',
        confirmButtonColor: getCfg(kelas).color,
        reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    isValidating.value = true;
    try {
        await axios.post(`/api/admin/klasifikasi/${selectedRow.value.id_klasifikasi}/validasi`, {
            keputusan_admin: kelas,
        });
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: `Supplier telah ditetapkan ke ${kelas}.`, timer: 2000, showConfirmButton: false });
        closeValidasiModal();
        fetchData(currentPage.value);
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Gagal', text: err?.response?.data?.message || 'Terjadi kesalahan.' });
    } finally {
        isValidating.value = false;
    }
}

// Hitung poin dari jawaban verifikasi petugas
function hitungPoinJawaban(jawaban) {
    if (!jawaban.opsi_verifikasi) return jawaban.opsi?.nilai ?? 0;
    const bobot = jawaban.pertanyaan?.bobot ?? 0;
    const nilai = jawaban.opsi_verifikasi?.nilai ?? 0;
    return Math.round((nilai / 100) * bobot);
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
                                    { val: 'pending_diproses', label: 'Menunggu & Diproses' },
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
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Skor Pengajuan</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Skor Verif</th>
                                    <th class="px-10 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Kelas</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Status</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Petugas</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Tgl Pengajuan</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Tgl Verifikasi</th>
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
                                    <!-- Skor Pengajuan -->
                                    <td class="px-6 py-4 text-slate-700">
                                        <span v-if="row.total_nilai !== null" class="font-mono font-bold">{{ row.total_nilai }}</span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <!-- Skor Verifikasi -->
                                    <td class="px-6 py-4 text-slate-700">
                                        <span v-if="row.verifikasi?.total_nilai !== null && row.verifikasi?.total_nilai !== undefined" class="font-mono font-bold text-blue-600">{{ row.verifikasi.total_nilai }}</span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <!-- Kelas -->
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                            :class="kategoriClass(getKelasName(row))"
                                        >
                                            {{ getKelasName(row) }}
                                        </span>
                                    </td>
                                    <!-- Status -->
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                            :class="statusClass(row.status_klasifikasi)"
                                        >
                                            {{ getStatusLabel(row.status_klasifikasi) }}
                                        </span>
                                    </td>
                                    <!-- Petugas -->
                                    <td class="px-6 py-4">
                                        <span v-if="row.verifikasi?.petugas || row.jadwal_kunjungan?.petugas" class="inline-flex items-center gap-1.5 text-sm text-slate-700 font-medium">
                                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                                {{ (row.verifikasi?.petugas?.profil_petugas?.nama_petugas || row.verifikasi?.petugas?.username || row.jadwal_kunjungan?.petugas?.profil_petugas?.nama_petugas || row.jadwal_kunjungan?.petugas?.username || 'P').charAt(0).toUpperCase() }}
                                            </span>
                                            {{ row.verifikasi?.petugas?.profil_petugas?.nama_petugas || row.verifikasi?.petugas?.username || row.jadwal_kunjungan?.petugas?.profil_petugas?.nama_petugas || row.jadwal_kunjungan?.petugas?.username || 'Petugas' }}
                                        </span>
                                        <span v-else class="text-xs text-slate-400 italic">Belum dijadwalkan</span>
                                    </td>
                                    <!-- Tgl Pengajuan -->
                                    <td class="px-6 py-4 text-slate-500 text-xs whitespace-nowrap">{{ formatDate(row.tanggal) }}</td>
                                    <!-- Tgl Verifikasi -->
                                    <td class="px-6 py-4 text-slate-500 text-xs whitespace-nowrap">{{ formatDate(row.verifikasi?.tanggal || row.jadwal_kunjungan?.tanggal_kunjungan) }}</td>
                                    <!-- Aksi -->
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Validasi Akhir: tampil jika sudah ada verifikasi lapangan & belum selesai -->
                                            <button
                                                v-if="row.verifikasi && row.status_klasifikasi !== 'selesai'"
                                                @click="openValidasiModal(row)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors shadow-sm"
                                                title="Validasi Akhir"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Validasi
                                            </button>
                                            <!-- Lihat Detail: tampil jika selesai -->
                                            <button
                                                v-else-if="row.status_klasifikasi === 'selesai'"
                                                @click="openDetailModal(row)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-sm"
                                                title="Lihat Detail"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Detail
                                            </button>
                                            <span v-else class="text-xs text-slate-300">Menunggu verifikasi</span>
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

    <!-- ── Validasi Modal ── -->
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="showValidasiModal && selectedRow"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                @click.self="closeValidasiModal"
            >
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="flex items-start justify-between p-6 border-b border-slate-100">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Hasil Verifikasi Lapangan</h3>
                            <p class="text-sm text-slate-500 mt-0.5">{{ selectedRow.supplier?.nama_perusahaan }}</p>
                        </div>
                        <button @click="closeValidasiModal" class="text-slate-400 hover:text-slate-600 p-1">
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
                            <h4 class="text-slate-700 text-sm font-bold mb-3">Daftar Verifikasi Kriteria</h4>

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
    <!-- ── Detail Modal ── -->
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="showDetailModal && selectedRow"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                @click.self="closeDetailModal"
            >
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="flex items-start justify-between p-6 border-b border-slate-100">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Detail Validasi Akhir</h3>
                            <p class="text-sm text-slate-500 mt-0.5">{{ selectedRow.supplier?.nama_perusahaan }}</p>
                        </div>
                        <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600 p-1">
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
main::-webkit-scrollbar { width: 6px; }
main::-webkit-scrollbar-track { background: transparent; }
main::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
main::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>