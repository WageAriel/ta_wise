<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SidebarAdmin from "@/Components/SidebarAdmin.vue";
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    outbounds: {
        type: Array,
        default: () => []
    }
});

// Search, Filter, Pagination State
const searchQuery = ref('');
const selectedYear = ref('');
const perPage = ref(10);
const currentPage = ref(1);

const years = computed(() => {
    const currentYear = new Date().getFullYear();
    return [currentYear, currentYear - 1, currentYear - 2];
});

const filteredOutbounds = computed(() => {
    let data = props.outbounds;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        data = data.filter(item => 
            String(item.id_outbound).toLowerCase().includes(query) || 
            (item.tujuan && item.tujuan.toLowerCase().includes(query)) ||
            (item.status && item.status.toLowerCase().includes(query))
        );
    }

    if (selectedYear.value) {
        data = data.filter(item => {
            const itemYear = new Date(item.tanggal).getFullYear();
            return itemYear == selectedYear.value;
        });
    }

    return data;
});

const totalPages = computed(() => Math.ceil(filteredOutbounds.value.length / perPage.value) || 1);

const displayOutbounds = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredOutbounds.value.slice(start, end);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };

// Modal Logic
const showDetailModal = ref(false);
const selectedOutbound = ref(null);

const openDetailModal = (outbound) => {
    selectedOutbound.value = outbound;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedOutbound.value = null;
};

const deleteOutbound = async (id) => {
    const result = await Swal.fire({
        title: 'Konfirmasi',
        text: "Yakin ingin menghapus data outbound ini? Stok barang akan otomatis dikembalikan ke inventory.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Ya, Hapus!'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/admin/outbound/${id}`);
            Swal.fire("Berhasil", "Data outbound berhasil dihapus dan stok dikembalikan!", "success").then(() => {
                window.location.reload();
            });
        } catch (error) {
            console.error("Error deleting outbound:", error);
            Swal.fire("Error", "Terjadi kesalahan saat menghapus data!", "error");
        }
    }
};

const downloadSurat = (id) => {
    window.open(`/admin/outbound/${id}/pdf`, '_blank');
};
</script>

<template>
    <Head title="Outbound Management | Admin" />
    
    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarAdmin class="flex-shrink-0 h-full overflow-y-auto border-r border-gray-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-7xl mx-auto px-6 py-10 lg:px-10">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                            Outbound Management
                        </h1>
                        <p class="text-gray-500 mt-1 text-sm">
                            Kelola data pengeluaran barang (Outbound).
                        </p>
                    </div>
                </div>

                <!-- Filters & Search Section -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <!-- Search Input -->
                    <div class="relative w-full md:flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            v-model="searchQuery"
                            @input="currentPage = 1"
                            type="text"
                            placeholder="Cari ID Outbound, Tujuan, atau Status..."
                            class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-gray-400 shadow-sm"
                        />
                    </div>

                    <!-- Right Filters -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-400">Tampilkan</span>
                            <select
                                v-model="perPage"
                                @change="currentPage = 1"
                                class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm"
                            >
                                <option :value="10">10 Data</option>
                                <option :value="25">25 Data</option>
                                <option :value="50">50 Data</option>
                                <option :value="100">100 Data</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <select
                                v-model="selectedYear"
                                @change="currentPage = 1"
                                class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm"
                            >
                                <option value="">Semua Tahun</option>
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">No</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">ID Outbound</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Tanggal</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Tujuan</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">Surat Outbound</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(outbound, index) in displayOutbounds" :key="outbound.id_outbound" class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-gray-800">{{ index + 1 }}</td>
                                    <td class="text-xs font-black text-gray-800 leading-none">OUT-{{ outbound.id_outbound }}</td>
                                    <td class="py-4 px-6 text-center text-xs font-bold text-gray-500">{{ outbound.tanggal }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ outbound.tujuan }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-md text-xs font-bold">
                                            {{ outbound.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a
                                            @click="downloadSurat(outbound.id_outbound)"
                                            class="inline-flex items-center px-4 py-1.5 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg text-xs font-bold hover:bg-indigo-100 transition shadow-sm"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Download PDF
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="openDetailModal(outbound)" class="p-2 bg-gray-50 text-indigo-400 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all" title="Lihat Detail">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                            <button @click="deleteOutbound(outbound.id_outbound)" class="p-2 bg-gray-50 text-red-400 rounded-xl hover:bg-red-50 hover:text-red-600 transition-all" title="Hapus Outbound">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="displayOutbounds.length === 0">
                                    <td colspan="5" class="text-center py-12 text-gray-500">
                                        Tidak ada data outbound ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Simple Pagination Footer -->
                    <div class="px-8 py-5 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            Showing {{ displayOutbounds.length }} of {{ filteredOutbounds.length }} Records
                        </p>
                        <div class="flex gap-2">
                            <button @click="prevPage" :disabled="currentPage === 1" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-[10px] font-black text-gray-700 uppercase tracking-widest transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">Previous</button>
                            <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-[10px] font-black text-gray-700 uppercase tracking-widest transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Detail Modal -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">Detail Barang Outbound (OUT-{{ selectedOutbound?.id_outbound }})</h2>
                    <button @click="closeDetailModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <div class="grid grid-cols-3 gap-4 mb-6 text-sm border border-gray-100 p-4 rounded-xl bg-gray-50">
                        <div>
                            <p class="text-gray-500 font-medium">Tanggal</p>
                            <p class="font-bold text-gray-800">{{ selectedOutbound?.tanggal }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 font-medium">Tujuan</p>
                            <p class="font-bold text-gray-800">{{ selectedOutbound?.tujuan }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 font-medium">Pembuat</p>
                            <p class="font-bold text-gray-800">{{ selectedOutbound?.user?.username || '-' }}</p>
                        </div>
                    </div>

                    <h3 class="text-md font-bold text-gray-800 mb-3">Daftar Barang</h3>
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 font-bold text-gray-600 w-12 text-center">No</th>
                                    <th class="px-4 py-3 font-bold text-gray-600">Nama Barang</th>
                                    <th class="px-4 py-3 font-bold text-gray-600">Lokasi</th>
                                    <th class="px-4 py-3 font-bold text-gray-600 text-center">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(detail, index) in selectedOutbound?.details" :key="detail.id_detail" class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-center text-gray-500">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ detail.barang?.nama_barang || detail.barang?.name || 'Unknown' }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ detail.barang?.inventories?.[0]?.location?.kode_location || '-' }}</td>
                                    <td class="px-4 py-3 text-center font-extrabold text-gray-900">{{ detail.qty }}</td>
                                </tr>
                                <tr v-if="!selectedOutbound?.details || selectedOutbound.details.length === 0">
                                    <td colspan="4" class="text-center py-6 text-gray-500">
                                        Tidak ada detail barang untuk outbound ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <button @click="closeDetailModal" class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 shadow-sm transition">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Styling khusus untuk select dropdown */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-size: 1.5em 1.5em;
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    appearance: none;
}
</style>
