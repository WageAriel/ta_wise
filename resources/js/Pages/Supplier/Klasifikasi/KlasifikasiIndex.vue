<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SidebarSupplier from '@/Components/SidebarSupplier.vue';
import axios from 'axios';

const rows = ref([]);
const pagination = ref({});
const stats = ref({
    total_pengajuan: 0,
    disetujui: 0,
    menunggu_validasi: 0
});
const currentPage = ref(1);
const perPage = ref(10);

async function fetchData(page = 1) {
    try {
        currentPage.value = page;
        // Panggil endpoint API untuk riwayat supplier yang login
        const response = await axios.get('/supplier/classification/data', {
            params: {
                page: page,
                per_page: perPage.value
            }
        });
        
        rows.value = response.data.data.data;
        pagination.value = response.data.data;
        stats.value = response.data.stats;
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

function kategoriClass(k) {
    if (!k) return "bg-slate-100 text-slate-400";
    const map = { A: "bg-blue-100 text-blue-700", B: "bg-indigo-100 text-indigo-700", C: "bg-orange-100 text-orange-700" };
    return map[k] ?? "bg-slate-100 text-slate-500";
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
    if (row.verifikasi?.admin) return row.verifikasi.admin.name;
    if (row.verifikasi?.petugas) return row.verifikasi.petugas.name;
    return '-';
}

function getCatatan(row) {
    return row.verifikasi?.catatan || '-';
}
</script>

<template>
    <Head title="Klasifikasi Saya | WISE" />

    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarSupplier class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-7xl mx-auto px-6 py-10 lg:px-10">
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
                            :href="route('supplier.klasifikasi-form')" 
                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-sm transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ajukan Klasifikasi
                        </Link>
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
                                    <!-- <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Nilai Pengajuan</th> -->
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Hasil Verifikasi</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Kelas Final</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Status Pengajuan</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Petugas</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Catatan</th>
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
                                    <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ row.tanggal }}</td>
                                    <!-- <td class="px-6 py-4 text-slate-700">
                                        <span v-if="row.total_nilai !== null" class="font-mono font-bold">{{ row.total_nilai }}</span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td> -->
                                    <td class="px-6 py-4 text-slate-700">
                                        <span v-if="row.verifikasi?.nilai_akhir !== undefined && row.verifikasi?.nilai_akhir !== null" class="font-mono font-bold text-emerald-600">
                                            {{ row.verifikasi.nilai_akhir }}
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            v-if="getKategori(row.verifikasi?.nilai_akhir || row.total_nilai)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                            :class="kategoriClass(getKategori(row.verifikasi?.nilai_akhir || row.total_nilai))"
                                        >
                                            Kategori {{ getKategori(row.verifikasi?.nilai_akhir || row.total_nilai) }}
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
                                    <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                        <div v-if="getPetugas(row) !== '-'" class="flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                                {{ getPetugas(row).charAt(0).toUpperCase() }}
                                            </span>
                                            <span class="text-sm font-medium">{{ getPetugas(row) }}</span>
                                        </div>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 max-w-xs truncate" :title="getCatatan(row)">
                                        {{ getCatatan(row) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
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
</template>

<style scoped>
main::-webkit-scrollbar { width: 6px; }
main::-webkit-scrollbar-track { background: transparent; }
main::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
main::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
</style>
