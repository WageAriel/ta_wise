<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import SidebarPetugas from '@/Components/SidebarPetugas.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total: 0, terjadwal: 0, selesai: 0 })
    },
    jadwals: {
        type: Array,
        default: () => []
    },
    filterStatus: {
        type: String,
        default: 'semua'
    }
});

// ─── Filter aktif ────────────────────────────────────────────────
const activeFilter = ref(props.filterStatus);

const setFilter = (status) => {
    activeFilter.value = status;
    router.get(
        route('petugas.jadwal'),
        { status },
        { preserveState: true, replace: true }
    );
};

// ─── Helpers ─────────────────────────────────────────────────────
const formatTanggal = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const formatWaktu = (timeStr) => {
    if (!timeStr) return '-';
    return timeStr.slice(0, 5);
};

const isHariIni = (dateStr) => {
    if (!dateStr) return false;
    const today = new Date().toISOString().split('T')[0];
    return dateStr === today;
};

const statusLabel = (status) => {
    const map = {
        menunggu: 'Terjadwal',
        berlangsung: 'Berlangsung',
        selesai: 'Selesai',
        dibatalkan: 'Dibatalkan',
    };
    return map[status] || status;
};

const statusClass = (status) => {
    const map = {
        menunggu: 'bg-amber-50 text-amber-600 border border-amber-200',
        berlangsung: 'bg-blue-50 text-blue-600 border border-blue-200',
        selesai: 'bg-emerald-50 text-emerald-600 border border-emerald-200',
        dibatalkan: 'bg-red-50 text-red-600 border border-red-200',
    };
    return map[status] || 'bg-slate-50 text-slate-600 border border-slate-200';
};

// Jumlah filter chips
const filterOptions = computed(() => [
    { key: 'semua',    label: 'Semua',    count: props.stats.total },
    { key: 'terjadwal', label: 'Terjadwal', count: props.stats.terjadwal },
    { key: 'selesai',  label: 'Selesai',  count: props.stats.selesai },
]);

// ─── Modal Detail ────────────────────────────────────────────────
const showDetailModal = ref(false);
const selectedJadwal = ref(null);
const selectedDetail = ref(null);
const isFetchingDetail = ref(false);

async function openDetailModal(jadwal) {
    selectedJadwal.value = jadwal;
    showDetailModal.value = true;
    isFetchingDetail.value = true;
    selectedDetail.value = null;

    try {
        const res = await axios.get(`/api/admin/klasifikasi/${jadwal.id_klasifikasi}`);
        selectedDetail.value = res.data;
    } catch (e) {
        console.error('Failed to fetch detail', e);
    } finally {
        isFetchingDetail.value = false;
    }
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedJadwal.value = null;
    selectedDetail.value = null;
}

// Hitung poin (sama seperti di Admin View)
function hitungPoinJawaban(jawaban) {
    if (!jawaban.opsi_verifikasi) return jawaban.opsi?.nilai ?? 0;
    const bobot = jawaban.pertanyaan?.bobot ?? 0;
    const nilai = jawaban.opsi_verifikasi?.nilai ?? 0;
    return Math.round((nilai / 100) * bobot);
}
</script>

<template>
    <Head title="Jadwal Verifikasi | WISE" />

    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarPetugas class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-5xl mx-auto px-6 py-10 lg:px-10 space-y-8">

                <!-- ── Header ── -->
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Jadwal Verifikasi</h1>
                    <p class="text-slate-500 text-sm mt-1">Daftar jadwal kunjungan verifikasi klasifikasi supplier yang ditugaskan kepada Anda.</p>
                </div>

                <!-- ── Stats Cards ── -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                    <!-- Total Jadwal -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="p-3 rounded-xl bg-blue-100 text-blue-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Jadwal</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ stats.total }}</p>
                        </div>
                    </div>

                    <!-- Terjadwal -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="p-3 rounded-xl bg-amber-100 text-amber-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Terjadwal</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ stats.terjadwal }}</p>
                        </div>
                    </div>

                    <!-- Selesai -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
                        <div class="p-3 rounded-xl bg-emerald-100 text-emerald-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Selesai</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ stats.selesai }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── Filter Chips + List ── -->
                <div class="space-y-4">

                    <!-- Filter Bar -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-4 flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-slate-500 mr-1">Filter:</span>
                        <button
                            v-for="opt in filterOptions"
                            :key="opt.key"
                            @click="setFilter(opt.key)"
                            :class="[
                                'px-4 py-1.5 rounded-full text-sm font-semibold transition-all duration-200',
                                activeFilter === opt.key
                                    ? 'bg-blue-600 text-white shadow-sm'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            ]"
                        >
                            {{ opt.label }} ({{ opt.count }})
                        </button>
                    </div>

                    <!-- Jadwal List -->
                    <div class="grid grid-cols-1 gap-4">

                        <!-- Empty State -->
                        <div v-if="jadwals.length === 0" class="bg-white rounded-2xl border-2 border-slate-100 py-20 flex flex-col items-center gap-3 text-slate-400">
                            <svg class="w-14 h-14 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="font-semibold text-base">Belum ada jadwal verifikasi</p>
                            <p class="text-sm">Admin belum menugaskan jadwal kepada Anda.</p>
                        </div>

                        <!-- Jadwal Card Items -->
                        <div
                            v-for="jadwal in jadwals"
                            :key="jadwal.id"
                            class="jadwal-card bg-white rounded-2xl shadow-sm border-2 border-slate-100 overflow-hidden transition-all duration-200 hover:border-blue-400 hover:shadow-md"
                            :class="isHariIni(jadwal.tanggal_kunjungan) && jadwal.status !== 'selesai'
                                ? 'bg-blue-50/30'
                                : ''"
                        >
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-4">

                                    <!-- Kiri: Info Supplier -->
                                    <div class="flex-1">
                                        <!-- Nama + Badge Hari Ini -->
                                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                                            <h3 class="text-slate-900 font-bold" style="font-size:16px">
                                                {{ jadwal.klasifikasi?.supplier?.nama_perusahaan || 'Supplier Tidak Diketahui' }}
                                            </h3>
                                            <span
                                                v-if="isHariIni(jadwal.tanggal_kunjungan) && jadwal.status !== 'selesai'"
                                                class="px-2 py-0.5 rounded-full bg-blue-600 text-white font-bold"
                                                style="font-size:10px"
                                            >
                                                HARI INI
                                            </span>
                                        </div>

                                        <p class="text-slate-600 mb-3" style="font-size:13px">
                                            Verifikasi Klasifikasi Supplier
                                        </p>

                                        <!-- Grid Info 2 Kolom -->
                                        <div class="grid grid-cols-2 gap-3">

                                            <!-- Tanggal & Waktu -->
                                            <div class="flex items-start gap-2">
                                                <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <div>
                                                    <p class="text-slate-500" style="font-size:11px">Tanggal &amp; Waktu</p>
                                                    <p class="text-slate-700 font-semibold" style="font-size:12px">
                                                        {{ jadwal.tanggal_kunjungan }} • {{ formatWaktu(jadwal.waktu_kunjungan) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Lokasi -->
                                            <div class="flex items-start gap-2">
                                                <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <div>
                                                    <p class="text-slate-500" style="font-size:11px">Lokasi</p>
                                                    <p class="text-slate-700 font-semibold" style="font-size:12px">
                                                        {{ jadwal.klasifikasi?.supplier?.alamat_perusahaan || '-' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Kontak Person -->
                                            <div class="flex items-start gap-2">
                                                <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <div>
                                                    <p class="text-slate-500" style="font-size:11px">Kontak Person</p>
                                                    <p class="text-slate-700 font-semibold" style="font-size:12px">
                                                        {{ jadwal.klasifikasi?.supplier?.nama_pic || '-' }}
                                                    </p>
                                                    <p class="text-slate-500" style="font-size:11px">
                                                        {{ jadwal.klasifikasi?.supplier?.no_telp_pic || '-' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Catatan -->
                                            <div class="flex items-start gap-2">
                                                <svg class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <div>
                                                    <p class="text-slate-500" style="font-size:11px">Catatan</p>
                                                    <p class="text-slate-700 font-semibold" style="font-size:12px">
                                                        {{ jadwal.klasifikasi?.supplier?.catatan_admin || 'Verifikasi klasifikasi supplier' }}
                                                    </p>
                                                </div>
                                            </div>

                                        </div>
                                        <!-- end grid info -->
                                    </div>
                                    <!-- end kiri -->

                                    <!-- Kanan: Status Badge + Tombol -->
                                    <div class="ml-4 flex flex-col items-end gap-3 flex-shrink-0">

                                        <!-- Badge Status -->
                                        <div
                                            v-if="jadwal.status === 'selesai'"
                                            class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-green-50 border border-green-200"
                                        >
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-green-700 font-bold" style="font-size:12px">Selesai</span>
                                        </div>
                                        <div
                                            v-else
                                            class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200"
                                        >
                                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-amber-700 font-bold" style="font-size:12px">Terjadwal</span>
                                        </div>

                                        <!-- Tombol Mulai Verifikasi -->
                                        <Link
                                            v-if="jadwal.status !== 'selesai' && jadwal.status !== 'dibatalkan'"
                                            :href="route('petugas.verifikasi.form', jadwal.id)"
                                            class="flex items-center gap-2 rounded-lg px-4 text-white font-semibold transition-all active:scale-95 hover:opacity-90"
                                            style="height:36px; background:linear-gradient(135deg,#2563eb,#3b82f6); font-size:13px"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Mulai Verifikasi
                                        </Link>

                                        <!-- Tombol Edit Verifikasi (Tampil jika sudah selesai tapi admin belum finalisasi) -->
                                        <Link
                                            v-if="jadwal.status === 'selesai' && jadwal.klasifikasi?.status_klasifikasi !== 'selesai'"
                                            :href="route('petugas.verifikasi.form', jadwal.id)"
                                            class="flex items-center gap-2 rounded-lg px-4 text-white font-semibold transition-all active:scale-95 hover:opacity-90"
                                            style="height:36px; background:linear-gradient(135deg,#eab308,#ca8a04); font-size:13px"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            Edit Verifikasi
                                        </Link>

                                        <!-- Tombol Lihat Detail (Selesai) -->
                                        <button
                                            v-if="jadwal.status === 'selesai'"
                                            @click="openDetailModal(jadwal)"
                                            class="flex items-center gap-2 rounded-lg px-4 border border-slate-300 text-slate-700 font-semibold bg-white hover:bg-slate-50 transition-all active:scale-95"
                                            style="height:36px; font-size:13px"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Lihat Detail
                                        </button>

                                    </div>
                                    <!-- end kanan -->

                                </div>
                            </div>
                        </div>
                        <!-- end card item -->

                    </div>
                    <!-- end grid -->
                </div>
                <!-- end filter + list wrapper -->



            </div>
        </main>
    </div>

    <!-- ── Detail Modal ── -->
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="showDetailModal && selectedJadwal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                @click.self="closeDetailModal"
            >
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="flex items-start justify-between p-6 border-b border-slate-100">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Detail Verifikasi Lapangan</h3>
                            <p class="text-sm text-slate-500 mt-0.5">{{ selectedJadwal.klasifikasi?.supplier?.nama_perusahaan }}</p>
                        </div>
                        <button @click="closeDetailModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <div v-if="isFetchingDetail" class="py-10 text-center">
                            <svg class="w-10 h-10 mx-auto text-blue-500 animate-spin mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <p class="text-sm font-semibold text-slate-500">Memuat detail verifikasi...</p>
                        </div>

                        <template v-else-if="selectedDetail">
                            <!-- Keputusan Admin (jika sudah selesai) -->
                            <div v-if="selectedDetail.status_klasifikasi === 'selesai' && selectedDetail.verifikasi?.keputusan_admin"
                                class="p-4 rounded-xl border-2 border-emerald-200 bg-emerald-50">
                                <p class="text-emerald-700 text-xs font-bold uppercase tracking-widest mb-1">Keputusan Final Admin</p>
                                <p class="text-2xl font-bold text-emerald-800">{{ selectedDetail.verifikasi.keputusan_admin }}</p>
                                <p class="text-emerald-600 text-xs mt-1">Verifikasi ini telah divalidasi akhir oleh admin.</p>
                            </div>

                            <!-- Ketidaksesuaian skor / Info Skor -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-4 rounded-lg bg-white border border-slate-200">
                                    <p class="text-slate-500 text-xs font-bold uppercase mb-1">Nilai Pengajuan</p>
                                    <p class="text-slate-900 text-xl font-bold">{{ selectedDetail.total_nilai }} <span class="text-sm font-normal">poin</span></p>
                                    <p class="text-slate-500 text-xs">Self-assessment supplier</p>
                                </div>
                                <div class="p-4 rounded-lg bg-white border border-slate-200">
                                    <p class="text-slate-500 text-xs font-bold uppercase mb-1">Nilai Verifikasi Anda</p>
                                    <p class="text-blue-700 text-xl font-bold">{{ selectedDetail.verifikasi?.total_nilai ?? '-' }} <span class="text-sm font-normal">poin</span></p>
                                    <p class="text-slate-500 text-xs">Hasil verifikasi lapangan</p>
                                </div>
                            </div>

                            <!-- ── Daftar Verifikasi Kriteria ── -->
                            <div>
                                <h4 class="text-slate-700 text-sm font-bold mb-3">Daftar Pertanyaan & Jawaban</h4>
                                <div class="space-y-3">
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
                                                            DIUBAH
                                                        </span>
                                                    </div>

                                                    <!-- Jawaban -->
                                                    <div
                                                        class="mt-2 mb-1 px-2 py-1.5 rounded-lg flex items-center gap-2 flex-wrap border"
                                                        :class="jawaban.jawaban_verifikasi === 'invalid'
                                                            ? 'bg-white border-orange-200'
                                                            : 'bg-emerald-50/50 border-emerald-100'"
                                                        style="font-size:11px"
                                                    >
                                                        <span class="text-slate-500 font-semibold">Awal:</span>
                                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold">
                                                            {{ jawaban.opsi?.teks_opsi ?? '—' }}
                                                        </span>
                                                        <template v-if="jawaban.jawaban_verifikasi === 'invalid'">
                                                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                            <span class="text-orange-600 font-semibold">Akhir (Anda):</span>
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
                            </div>
                        </template>
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
