<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import axios from "axios";
import Swal from "sweetalert2";

const props = defineProps({
    inboundData: { type: Array, default: () => [] },
    years: { type: Array, default: () => [2024, 2025, 2026] }
});

// State untuk Filter & Pencarian
const searchQuery = ref("");
const selectedType = ref("");
const selectedYear = ref("");
const perPage = ref(10); // Tambahkan ini

// Data dari backend
const inbounds = ref(props.inboundData);

// Helper Format Tanggal (Indonesia)
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

// Logika Filtering
const filteredData = computed(() => {
    return inbounds.value.filter(item => {
        const matchesSearch = item.id_inbound.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                             item.id_po.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const itemYear = new Date(item.tgl).getFullYear();
        const matchesYear = !selectedYear.value || itemYear == selectedYear.value;

        return matchesSearch && matchesYear;
    });
});

// Ikon SVG Mentah (Konsisten dengan Sidebar)
const icons = {
    search: `<path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`,
    layout: `<path d="M3 3h7v7H3V3zM14 3h7v7h-7V3zM14 14h7v7h-7v-7zM3 14h7v7H3v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`,
    package: `<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`,
    view: `<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
};

// --- LOGIKA MODAL & FORM ADDITIONS ---
const showLayoutModal = ref(false);
const showLocationModal = ref(false);
const showInventoryModal = ref(false);

const layouts = ref([]);
const barangs = ref([]);

const layoutForm = ref({ nama_layout: "" });
const locationForm = ref({ kode_location: "", kapasitas: 1, id_layout: "" });
const inventoryForm = ref({ 
    id_inbound: "", 
    items: [] // { id_barang, nama_barang, qty, id_location }
});

const handleInboundChange = async () => {
    if (!inventoryForm.value.id_inbound) return;
    
    try {
        // Asumsi endpoint untuk mengambil detail item berdasarkan ID Inbound
        // Jika belum ada, kita bisa menggunakan data dummy atau menunggu integrasi
        const response = await axios.get(route('admin.inbound.items', inventoryForm.value.id_inbound));
        inventoryForm.value.items = response.data.map(item => ({
            id_barang: item.id_barang,
            nama_barang: item.nama_barang,
            qty: item.qty,
            max_qty: item.qty, // Simpan batas maksimum asli
            id_location: ""
        }));
    } catch (error) {
        console.error("Gagal mengambil detail item inbound", error);
        // Fallback dummy data jika API belum tersedia
        inventoryForm.value.items = [
            { id_barang: 1, nama_barang: "Barang A", qty: 10, max_qty: 10, id_location: "" },
            { id_barang: 2, nama_barang: "Barang B", qty: 25, max_qty: 25, id_location: "" }
        ];
    }
};

const fetchDropdownData = async () => {
    try {
        const response = await axios.get(route('admin.inbound.data'));
        layouts.value = response.data.layouts;
        barangs.value = response.data.barangs;
    } catch (error) {
        console.error("Failed to fetch dropdown data", error);
    }
};

onMounted(() => {
    fetchDropdownData();
});

const submitLayout = async () => {
    try {
        await axios.post(route('admin.inbound.layout.store'), layoutForm.value);
        Swal.fire("Berhasil", "Layout berhasil ditambahkan", "success");
        showLayoutModal.value = false;
        layoutForm.value.nama_layout = "";
        fetchDropdownData();
    } catch (error) {
        Swal.fire("Error", "Gagal menambahkan layout", "error");
    }
};

const submitLocation = async () => {
    try {
        await axios.post(route('admin.inbound.location.store'), locationForm.value);
        Swal.fire("Berhasil", "Location berhasil ditambahkan", "success");
        showLocationModal.value = false;
        locationForm.value = { kode_location: "", kapasitas: 1, id_layout: "" };
        fetchDropdownData();
    } catch (error) {
        Swal.fire("Error", "Gagal menambahkan location", "error");
    }
};

const submitInventory = async () => {
    try {
        await axios.post(route('admin.inbound.inventory.store'), {
            id_inbound: inventoryForm.value.id_inbound,
            items: inventoryForm.value.items
        });
        Swal.fire("Berhasil", "Inventory berhasil ditambahkan", "success");
        showInventoryModal.value = false;
        inventoryForm.value = { id_inbound: "", items: [] };
    } catch (error) {
        Swal.fire("Error", "Gagal menambahkan inventory", "error");
    }
};

const isInventoryFormValid = computed(() => {
    if (!inventoryForm.value.id_inbound || inventoryForm.value.items.length === 0) return false;
    
    return inventoryForm.value.items.every(item => {
        const hasLocation = !!item.id_location;
        const validQty = item.qty > 0 && item.qty <= item.max_qty;
        return hasLocation && validQty;
    });
});

const allLocations = computed(() => {
    return layouts.value.flatMap(l => l.locations.map(loc => ({
        ...loc,
        nama_layout: l.nama_layout
    })));
});

const availableLocations = computed(() => {
    const layout = layouts.value.find(l => l.id_layout === inventoryForm.value.id_layout_temp);
    return layout ? layout.locations : [];
});

</script>

<template>
    <Head title="Inbound Barang" />

    <AdminLayout>
        <!-- Header Halaman -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Inbound</h1>
        </div>

        <!-- Section 1: Title & Action Buttons -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-700">Inbound Barang</h2>
                <p class="text-sm text-gray-500">Kelola data inbound dan inventaris dengan mudah.</p>
            </div>

            <div class="flex items-center gap-3">
                <button @click="showLayoutModal = true" class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg font-medium hover:bg-indigo-100 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" v-html="icons.layout"></svg>
                    Add Layout
                </button>
                <button @click="showLocationModal = true" class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-700 rounded-lg font-medium hover:bg-blue-100 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.242-4.243a8 8 0 1111.314 0z"></path><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Add Location
                </button>
                <button @click="showInventoryModal = true" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 shadow-sm transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" v-html="icons.package"></svg>
                    Add Inventory
                </button>
            </div>
        </div>

        <!-- SECTION 2: Filters & Search Section (Tampilan Bersih Tanpa Bungkus Card Putih) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <!-- Search Input -->
            <div class="relative w-full md:flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg
                        class="w-5 h-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        v-html="icons.search"
                    ></svg>
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari ID Inbound atau PO..."
                    class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-gray-400 shadow-sm"
                />
            </div>

            <!-- Right Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tampilkan:</span>
                    <select
                        v-model="perPage"
                        class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold shadow-sm appearance-none"
                    >
                        <option :value="10">10 Data</option>
                        <option :value="25">25 Data</option>
                        <option :value="50">50 Data</option>
                        <option :value="100">100 Data</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tahun:</span>
                    <select
                        v-model="selectedYear"
                        class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold shadow-sm appearance-none"
                    >
                        <option value="">Semua Tahun</option>
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 3: Data Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-400 uppercase text-xs font-bold">
                        <tr>
                            <th class="px-6 py-4 text-center w-16">No</th>
                            <th class="px-6 py-4">ID Inbound</th>
                            <th class="px-6 py-4">ID Purchase Order</th>
                            <th class="px-6 py-4">Tanggal Masuk</th>
                            <th class="px-6 py-4 text-center">Jumlah Barang</th>
                            <th class="px-6 py-4 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 italic-none">
                        <tr v-for="(item, index) in filteredData" :key="item.id" class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4 text-center text-gray-500 font-medium">{{ index + 1 }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ item.id_inbound }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ item.id_po }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ formatDate(item.tgl) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-bold ring-1 ring-blue-100">
                                    {{ item.jumlah }} Unit
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button 
                                    class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all group"
                                    title="Detail Inbound"
                                >
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" v-html="icons.view"></svg>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Empty State -->
                        <tr v-if="filteredData.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" v-html="icons.package"></svg>
                                    </div>
                                    <h3 class="text-gray-900 font-medium">Tidak ada data</h3>
                                    <p class="text-gray-500 text-sm">Coba ubah kata kunci pencarian atau filter Anda.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Table Footer -->
            <div class="p-4 bg-gray-50/50 border-t border-gray-100 flex justify-between items-center">
                <span class="text-xs font-medium text-gray-500">
                    Total: {{ filteredData.length }} Entri ditemukan
                </span>
                <span class="text-xs text-gray-400 italic">
                    Data diperbarui secara realtime sesuai filter
                </span>
            </div>
        </div>

        <!-- MODAL ADD LAYOUT -->
        <div v-if="showLayoutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-800">Add Layout</h3>
                    <button @click="showLayoutModal = false" class="text-gray-400 hover:text-red-500">&times;</button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submitLayout">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Layout</label>
                            <input v-model="layoutForm.nama_layout" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required placeholder="Gudang A, Area B..." />
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showLayoutModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Simpan Layout</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL ADD LOCATION -->
        <div v-if="showLocationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-800">Add Location</h3>
                    <button @click="showLocationModal = false" class="text-gray-400 hover:text-red-500">&times;</button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submitLocation">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Layout</label>
                            <select v-model="locationForm.id_layout" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="" disabled>-- Pilih Layout --</option>
                                <option v-for="l in layouts" :key="l.id_layout" :value="l.id_layout">{{ l.nama_layout }}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Kode Location</label>
                            <input v-model="locationForm.kode_location" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required placeholder="A1, B2..." />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Kapasitas</label>
                            <input v-model="locationForm.kapasitas" type="number" min="1" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required />
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showLocationModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan Location</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL ADD INVENTORY -->
        <div v-if="showInventoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl overflow-hidden border border-gray-100 flex flex-col" style="max-height: 90vh;">
                <!-- Header -->
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-center text-xl font-semibold text-gray-800">Add Inventory</h3>
                    </div>
                </div>

                <div class="p-8 overflow-y-auto flex-1 min-h-0">
                    <form @submit.prevent="submitInventory" id="add-inventory-form" class="space-y-6">
                        <!-- ID Inbound Dropdown -->
                        <div class="max-w-xs">
                            <label class="block text-sm font-medium text-gray-700 mb-2">ID Inbound</label>
                            <select 
                                v-model="inventoryForm.id_inbound" 
                                @change="handleInboundChange"
                                class="w-full bg-white border border-gray-200 text-gray-700 text-sm rounded-lg py-3 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm"
                                required
                            >
                                <option value="" disabled>-- Pilih ID Inbound --</option>
                                <option v-for="item in inbounds" :key="item.id_inbound" :value="item.id_inbound">
                                    {{ item.id_inbound }} ({{ item.id_po }})
                                </option>
                            </select>
                        </div>

                        <!-- Detail Barang Table -->
                        <div v-if="inventoryForm.id_inbound" class="space-y-4">
                            <label class="block text-sm font-medium text-gray-700">Detail Barang Inbound</label>
                            <div class="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-gray-50/50 border-b border-gray-100">
                                        <tr>
                                            <th class="py-4 px-6 font-medium text-gray-400 w-16 text-center">No</th>
                                            <th class="py-4 px-6 font-medium text-gray-400">Nama Barang</th>
                                            <th class="py-4 px-6 font-medium text-gray-400 text-center">Quantity</th>
                                            <th class="py-4 px-6 font-medium text-gray-400">Lokasi Barang</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 bg-white">
                                        <tr v-for="(item, idx) in inventoryForm.items" :key="idx" class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-4 px-6 text-center font-semibold text-gray-400">{{ idx + 1 }}</td>
                                            <td class="py-4 px-6 font-medium text-gray-900">{{ item.nama_barang }}</td>
                                            <td class="py-4 px-6 text-center">
                                                <div class="space-y-1">
                                                    <input 
                                                        type="number" 
                                                        v-model="item.qty"
                                                        :max="item.max_qty"
                                                        min="1"
                                                        class="w-20 text-center py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-xs"
                                                        :class="item.qty > item.max_qty ? 'border-rose-300 text-rose-600 focus:border-rose-500 focus:ring-rose-500/20' : ''"
                                                        required
                                                    />
                                                    <p v-if="item.qty > item.max_qty" class="text-xs font-medium text-rose-500">Maks: {{ item.max_qty }}</p>
                                                    <p v-else class="text-xs font-medium text-gray-400">Batas: {{ item.max_qty }}</p>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <select 
                                                    v-model="item.id_location"
                                                    class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg py-2 px-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium"
                                                    required
                                                >
                                                    <option value="" disabled>-- Pilih Lokasi --</option>
                                                    <option v-for="loc in allLocations" :key="loc.id_location" :value="loc.id_location">
                                                        {{ loc.nama_layout }} - {{ loc.kode_location }}
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr v-if="inventoryForm.items.length === 0">
                                            <td colspan="4" class="py-8 px-6 text-center text-gray-500 font-medium">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" v-html="icons.package"></svg>
                                                    <p>Semua barang pada Inbound ini sudah selesai di put-away.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Empty Placeholder -->
                        <div v-if="!inventoryForm.id_inbound" class="py-12 border-2 border-dashed border-gray-100 rounded-[32px] flex flex-col items-center justify-center text-gray-300 bg-gray-50/30">
                            <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="icons.package"></svg>
                            <p class="text-xs font-bold uppercase tracking-widest">Pilih ID Inbound untuk melihat detail barang</p>
                        </div>
                    </form>
                </div>

                <!-- Footer Actions -->
                <div class="px-8 py-6 bg-white border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button type="button" @click="showInventoryModal = false" class="px-8 py-4 rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all active:scale-95">
                        Batal
                    </button>
                    <button 
                        form="add-inventory-form"
                        type="submit" 
                        :disabled="!isInventoryFormValid"
                        class="px-8 py-4 rounded-xl bg-green-600 text-white hover:bg-green-700 shadow-lg shadow-green-100 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Simpan Inventory
                    </button>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>
/* Opsional: Efek focus pada input */
input:focus, select:focus {
    outline: none;
    border-color: #6366f1;
}
</style>