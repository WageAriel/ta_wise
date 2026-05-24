<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import AdminLayout from "../../../Layouts/AdminLayout.vue";

const props = defineProps({
    selections: { type: Array, default: () => [] },
    years: { type: Array, default: () => [2024, 2025, 2026] },
});

// State
const selections = ref(props.selections);
const years = ref(props.years);
const isLoading = ref(false);
const searchQuery = ref("");
const selectedYear = ref("");
const perPage = ref(10);
const activeTab = ref('pending'); // 'pending' = Status Menunggu Review, 'validated' = Lolos/Tidak Lolos

// Logic Filter & Search
const filteredSelections = computed(() => {
    return selections.value.filter((item) => {
        // Filter Berdasarkan Tab (Status)
        const isPending = item.status === 'process';
        const isValidated = ['lolos', 'tidak_lolos'].includes(item.status);
        
        const matchesTab = activeTab.value === 'pending' ? isPending : isValidated;

        // Filter Berdasarkan Search (Nama Supplier)
        const matchesSearch = item.supplier?.nama_perusahaan
            ?.toLowerCase()
            .includes(searchQuery.value.toLowerCase());

        // Filter Berdasarkan Tahun
        const itemYear = item.tanggal ? new Date(item.tanggal).getFullYear() : null;
        const matchesYear = !selectedYear.value || itemYear == selectedYear.value;

        return matchesTab && matchesSearch && matchesYear;
    });
});

// Aksi Buttons
const handleExport = () => {
    window.location.href = `/admin/supplier/selection/export?year=${selectedYear.value}`;
};

const handleView = (id) => {
    // Navigasi ke detail atau buka modal detail
};

const handleDelete = async (id) => {
    if(confirm('Apakah Anda yakin ingin menghapus data pengajuan ini?')) {
        // Logika delete via axios
    }
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'lolos': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'tidak_lolos': return 'bg-rose-100 text-rose-700 border-rose-200';
        case 'process': return 'bg-blue-100 text-blue-700 border-blue-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};
</script>

<template>
    <Head title="Supplier Selection | Admin WISE" />

    <AdminLayout>
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Supplier Selection</h1>
        </div>

        <!-- Action Bar: Export/Import -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-700">Supplier Selection</h2>
                <p class="text-sm text-gray-500">Kelola dan tinjau hasil seleksi kapasitas supplier.</p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    @click="triggerFileInput"
                    class="inline-flex items-center gap-2 px-5 py-3 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all shadow-sm"
                    >
                    <svg
                        class="w-4 h-4 text-slate-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                    />
                    </svg>
                    IMPORT EXCEL
                </button>
                <input
                        type="file"
                        id="excel-file-input"
                        class="hidden"
                        accept=".xlsx, .xls"
                        @change="onFileChange"
                    />
                <button
                    @click="handleExport"
                    class="inline-flex items-center gap-2 px-5 py-3 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 active:scale-95 transition-all shadow-md shadow-emerald-100"
                    >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                        />
                    </svg>
                    EXPORT EXCEL
                </button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <!-- Search -->
            <div class="relative w-full md:flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 pointer-events-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input v-model="searchQuery" type="text" placeholder="Cari nama supplier..." class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 shadow-sm" />
            </div>

            <!-- Select Filters -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-400">Tampilkan</span>
                    <select v-model="perPage" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm">
                        <option :value="10">10 Baris</option>
                        <option :value="25">25 Baris</option>
                        <option :value="50">50 Baris</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <select v-model="selectedYear" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm">
                        <option value="">Semua Tahun</option>
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Status Tab Filter -->
        <div class="mb-6 p-1.5 bg-slate-100 inline-flex rounded-lg border border-slate-200">
            <button @click="activeTab = 'pending'" 
                :class="activeTab === 'pending' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-6 py-2.5 text-xs font-semibold rounded-lg transition-all">
                Menunggu Validasi
            </button>
            <button @click="activeTab = 'validated'" 
                :class="activeTab === 'validated' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-6 py-2.5 text-xs font-semibold rounded-lg transition-all">
                Tervalidasi
            </button>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-[24px] border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-100">
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-16">No</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Nama Supplier</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Tahun</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Tgl Pengajuan</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="(item, idx) in filteredSelections.slice(0, perPage)" :key="item.id_seleksi" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-6 text-center text-xs font-bold text-slate-500">{{ idx + 1 }}</td>
                            <td class="py-4 px-6 font-bold text-slate-900 text-xs">{{ item.supplier?.nama_perusahaan || '-' }}</td>
                            <td class="py-4 px-6 text-center text-xs font-bold text-slate-600">{{ item.tanggal ? new Date(item.tanggal).getFullYear() : '-' }}</td>
                            <td class="py-4 px-6 text-center">
                                <span :class="getStatusBadge(item.status)" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border shadow-sm">
                                    {{ item.status === 'process' ? 'Menunggu Review' : (item.status === 'lolos' ? 'Lolos' : 'Tidak Lolos') }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-xs font-bold text-slate-500">{{ item.tanggal || '-' }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="handleView(item.id_seleksi)" class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button @click="handleDelete(item.id_seleksi)" class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-if="filteredSelections.length === 0">
                            <td colspan="6" class="py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4 border border-slate-100">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Data Tidak Ditemukan</h3>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">Silakan gunakan filter atau kata kunci pencarian lain.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Styling khusus seleksi */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-size: 1.5em 1.5em;
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
}
</style>