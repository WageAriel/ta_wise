<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    returns: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// Local state for returns
const localReturns = ref([]);

// Fetch Returns
const fetchReturns = async () => {
    try {
        const response = await axios.get(route('admin.return-management.data'));
        localReturns.value = response.data;
    } catch (error) {
        console.error("Error fetching returns:", error);
    }
};

onMounted(() => {
    fetchReturns();
});

// State
const searchQuery = ref('');
const selectedYear = ref('');
const perPage = ref(10);

// Data tahun untuk filter (3 tahun terakhir)
const years = computed(() => {
    const currentYear = new Date().getFullYear();
    return [currentYear, currentYear - 1, currentYear - 2];
});

// Logic Filter: Memproses data returns berdasarkan input user
const filteredReturns = computed(() => {
    let data = localReturns.value.length > 0 ? localReturns.value : [];

    // Filter Berdasarkan Search (ID Return atau ID Inbound)
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        data = data.filter(item => 
            String(item.id_return).toLowerCase().includes(query) || 
            String(item.id_inbound).toLowerCase().includes(query) ||
            (item.notes && item.notes.toLowerCase().includes(query))
        );
    }

    // Filter Berdasarkan Tahun
    if (selectedYear.value) {
        data = data.filter(item => {
            const itemYear = new Date(item.tanggal_return).getFullYear();
            return itemYear == selectedYear.value;
        });
    }

    return data;
});

// Data yang ditampilkan di tabel (dengan limit per halaman)
const displayReturns = computed(() => {
    return filteredReturns.value.slice(0, perPage.value);
});

// --- MODAL & FORM LOGIC ---
const showAddModal = ref(false);
const returnForm = ref({
    id_inbound: "",
    items: [] // { id_barang, nama_barang, qty, kondisi, alasan, max_qty }
});

// Dummy data Inbound (Idealnya dikirim via props atau fetch API)
const inboundsList = ref([
    { id_inbound: "INB-001" },
    { id_inbound: "INB-002" },
    { id_inbound: "INB-003" }
]);

const conditions = [
    { label: "Rusak", value: "Rusak" },
    { label: "Cacat", value: "Cacat" },
    { label: "Tidak Sesuai", value: "Tidak Sesuai" },
    { label: "Lainnya", value: "Lainnya" }
];

const handleInboundChange = async () => {
    if (!returnForm.value.id_inbound) return;
    
    // Logika Return: Jumlah Inbound dikurangi Jumlah Put Away
    // Contoh dari user: Inbound 20, Put Away 15 -> Return = 5
    // Disini saya buatkan dummy mapping untuk contoh sesuai request
    returnForm.value.items = [
        { 
            id_barang: 1, 
            nama_barang: "Semen Tiga Roda", 
            inbound_qty: 20, 
            put_away_qty: 15, 
            qty: 5, // 20 - 15 = 5 (Return)
            max_qty: 5, 
            kondisi: "Tidak Sesuai", 
            alasan: "Barang tidak sesuai/diterima" 
        },
        { 
            id_barang: 2, 
            nama_barang: "Paku Beton", 
            inbound_qty: 30, 
            put_away_qty: 28, 
            qty: 2, // 30 - 28 = 2 (Return)
            max_qty: 2, 
            kondisi: "Cacat", 
            alasan: "Rusak di perjalanan" 
        }
    ].filter(item => item.qty > 0); // Hanya yang ada selisih yang bisa direturn
};

const handleAddReturn = () => {
    showAddModal.value = true;
};

const deleteReturn = async (id) => {
    if (confirm('Yakin ingin menghapus data return ini?')) {
        try {
            await axios.delete(route('admin.return-management.destroy', id));
            alert("Data return berhasil dihapus!");
            fetchReturns(); // refresh data
        } catch (error) {
            console.error("Error deleting return:", error);
            alert("Terjadi kesalahan saat menghapus data!");
        }
    }
};

const submitReturn = async () => {
    try {
        await axios.post(route('admin.return-management.store'), returnForm.value);
        showAddModal.value = false;
        returnForm.value = { id_inbound: "", items: [] };
        alert("Data berhasil disimpan!");
        fetchReturns();
    } catch (error) {
        console.error("Error submitting return:", error);
        alert("Terjadi kesalahan saat menyimpan data!");
    }
};
</script>

<template>
    <Head title="Return Management | Admin" />

    <AdminLayout>
        <!-- Header Page -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Return Management</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola data pengembalian barang dari gudang ke supplier.</p>
        </div>

        <!-- Action Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Return Management</h2>
                <p class="text-sm text-gray-400 font-medium">Daftar Transaksi Return</p>
            </div>
            <button 
                @click="handleAddReturn"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition-all active:scale-95 shadow-lg shadow-red-100"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Add Return
            </button>
        </div>

        <!-- SECTION 2: Filters & Search Section (Tampilan Bersih Tanpa Bungkus Card Putih) -->
        <div
             class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6"
        >
            <!-- Search Input -->
             <div class="relative w-full md:flex-1">
                <span
                    class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none"
                >
                    <svg
                        class="w-5 h-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama perusahaan, email, atau alamat..."
                        class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-gray-400 shadow-sm"
                />
            </div>

            <!-- Right Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span
                        class="text-sm font-medium text-gray-400"
                        >Tampilkan</span
                    >
                    <select
                        v-model="perPage"
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
                        class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm"
                    >
                        <option value="">Semua Tahun</option>
                        <option
                            v-for="year in years"
                            :key="year"
                            :value="year"
                        >
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center w-16">No</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase">ID Return</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase">ID Inbound</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center">Tanggal Return</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center">Jumlah Item</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase">Notes</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(item, idx) in displayReturns" :key="idx" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="py-4 px-6 text-center text-xs font-bold text-gray-400">{{ idx + 1 }}</td>
                            <td class="py-4 px-6">
                                <span class="text-xs font-black text-gray-900 leading-none">RET-{{ item.id_return }}</span>
                            </td>
                            <td class="py-4 px-6 text-xs font-bold text-gray-600">INB-{{ item.id_inbound }}</td>
                            <td class="py-4 px-6 text-center text-xs font-bold text-gray-500">{{ item.tanggal_return }}</td>
                            <td class="py-4 px-6 text-center">
                                <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-lg text-[10px] font-black shadow-sm border border-indigo-100">
                                    {{ item.jumlah_item }} Items
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-gray-500 font-medium italic">
                                {{ item.notes || '-' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <button @click="deleteReturn(item.id_return)" class="p-2 bg-gray-50 text-red-400 rounded-xl hover:bg-red-50 hover:text-red-600 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Empty State -->
                        <tr v-if="displayReturns.length === 0">
                            <td colspan="7" class="py-24 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-gray-50 rounded-[28px] flex items-center justify-center mb-6 border border-gray-100">
                                        <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-black text-gray-700 uppercase tracking-widest">No Return Data Found</h3>
                                    <p class="text-[10px] text-gray-400 mt-2 uppercase font-bold tracking-tighter">Silakan gunakan button di atas untuk menambahkan data retur baru.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Simple Pagination Footer -->
            <div class="px-8 py-5 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Showing 0 of 0 Records</p>
                <div class="flex gap-2">
                    <button class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-[10px] font-black text-gray-400 uppercase tracking-widest opacity-50 cursor-not-allowed transition-all">Previous</button>
                    <button class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-[10px] font-black text-gray-400 uppercase tracking-widest opacity-50 cursor-not-allowed transition-all">Next</button>
                </div>
            </div>
        </div>

        <!-- MODAL ADD RETURN -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-5xl overflow-hidden border border-gray-100 transform transition-all">
                <!-- Header -->
                <div class="px-8 py-6 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-gray-800 uppercase tracking-tight">Add New Return</h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Form Pengembalian Barang ke Supplier</p>
                    </div>
                    <button @click="showAddModal = false" class="p-2 hover:bg-gray-200 rounded-xl transition-colors text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-8">
                    <form @submit.prevent="submitReturn" class="space-y-6">
                        <!-- ID Inbound Selection -->
                        <div class="max-w-xs">
                            <label class="block text-sm font-medium text-gray-400 mb-2">ID Inbound</label>
                            <select 
                                v-model="returnForm.id_inbound" 
                                @change="handleInboundChange"
                                class="w-full bg-white border border-gray-200 text-gray-700 text-sm rounded-lg py-3.5 px-4 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all font-medium shadow-sm"
                                required
                            >
                                <option value="" disabled>-- Pilih ID Inbound --</option>
                                <option v-for="inb in inboundsList" :key="inb.id_inbound" :value="inb.id_inbound">
                                    {{ inb.id_inbound }}
                                </option>
                            </select>
                        </div>

                        <!-- Detail Barang Table -->
                        <div v-if="returnForm.id_inbound" class="space-y-4">
                            <label class="block text-sm font-medium text-gray-400">Detail Barang Return</label>
                            <div class="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-gray-50/50 border-b border-gray-100">
                                        <tr>
                                            <th class="py-4 px-6 font-medium text-gray-400 w-16 text-center">No</th>
                                            <th class="py-4 px-6 font-medium text-gray-400">Nama Barang</th>
                                            <th class="py-4 px-6 font-medium text-gray-400 text-center">Qty</th>
                                            <th class="py-4 px-6 font-medium text-gray-400">Kondisi Barang</th>
                                            <th class="py-4 px-6 font-medium text-gray-400">Alasan Return</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray--50 bg-white">
                                        <tr v-for="(item, idx) in returnForm.items" :key="idx" class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-4 px-6 text-center font-semibold text-gray-400">{{ idx + 1 }}</td>
                                            <td class="py-4 px-6">
                                                <p class="font-medium text-gray-900">{{ item.nama_barang }}</p>
                                                <p class="text-xs text-gray-400 font-medium mt-0.5">Stok Inbound: {{ item.max_qty }}</p>
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <input 
                                                    type="number" 
                                                    v-model="item.qty"
                                                    :max="item.max_qty"
                                                    min="1"
                                                    class="w-20 text-center py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all font-medium text-xs"
                                                    required
                                                />
                                            </td>
                                            <td class="py-4 px-6">
                                                <select 
                                                    v-model="item.kondisi"
                                                    class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg py-2 px-3 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all font-medium"
                                                    required
                                                >
                                                    <option v-for="c in conditions" :key="c.value" :value="c.value">{{ c.label }}</option>
                                                </select>
                                            </td>
                                            <td class="py-4 px-6">
                                                <input 
                                                    v-model="item.alasan"
                                                    type="text"
                                                    placeholder="Contoh: Barang penyok, label lepas..."
                                                    class="w-full bg-gray-50 border border-gray-200 text-xs rounded-lg py-2 px-4 focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all font-medium placeholder:text-gray-300"
                                                    required
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Empty Placeholder -->
                        <div v-if="!returnForm.id_inbound" class="py-16 border-2 border-dashed border-gray-100 rounded-[32px] flex flex-col items-center justify-center text-gray-300 bg-gray-50/30">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <p class="text-xs font-bold uppercase tracking-widest">Silakan pilih ID Inbound terlebih dahulu</p>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="showAddModal = false" class="px-8 py-4 rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all active:scale-95">
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="!returnForm.id_inbound"
                                class="px-8 py-4 rounded-xl bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-100 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Save Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
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