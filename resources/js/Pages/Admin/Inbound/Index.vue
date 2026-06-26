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
const selectedYear = ref("");
const perPage = ref(10);
const showHistory = ref(false); // false = tampilkan yang bisa dilokasikan; true = tampilkan riwayat yang sudah selesai

// Data dari backend
const inbounds = ref(props.inboundData);

// Track status tiap inbound: 'pending' | 'partial' | 'completed' | 'returned'
// - pending: belum ada yang dilokasikan
// - partial: sebagian dilokasikan, sisanya masih bisa dilokasikan
// - completed: semua sudah dilokasikan (→ history hijau)
// - returned: sebagian/semua di-return secara manual, tidak ada sisa (→ history merah)
const inboundStatusMap = ref({}); // { id_inbound: 'pending'|'partial'|'completed'|'returned' }

const checkInboundCompletion = async () => {
    for (const inbound of inbounds.value) {
        try {
            const response = await axios.get(route('admin.inbound.items', inbound.id_inbound));
            const remaining = response.data.length;
            const total = inbound.jumlah;

            if (remaining === 0) {
                // Tidak ada sisa — cek apakah ada return record untuk inbound ini
                // Kita anggap jika backend mengembalikan 0 item dan inbound punya history,
                // cek melalui flag yang dikirim backend (atau kita tanda dengan endpoint baru)
                // Untuk sementara: jika remaining = 0 dan ada put-away = completed, ada return = returned
                inboundStatusMap.value[inbound.id_inbound] = 'completed';
            } else if (remaining < total) {
                inboundStatusMap.value[inbound.id_inbound] = 'partial';
            } else {
                inboundStatusMap.value[inbound.id_inbound] = 'pending';
            }
        } catch (e) { /* skip */ }
    }
};

// Setelah submit — jika ada item yang di-return, update status ke 'returned'
const markInboundAsReturned = (id_inbound) => {
    inboundStatusMap.value[id_inbound] = 'returned';
};

// Helper Format Tanggal (Indonesia)
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

// Logika Filtering berdasarkan toggle history
const filteredData = computed(() => {
    return inbounds.value.filter(item => {
        const matchesSearch = item.id_inbound.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                             item.id_po.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const itemYear = new Date(item.tgl).getFullYear();
        const matchesYear = !selectedYear.value || itemYear == selectedYear.value;

        const status = inboundStatusMap.value[item.id_inbound] ?? 'pending';
        // history = tampilkan completed atau returned
        // active = tampilkan pending atau partial
        const isHistory = (status === 'completed' || status === 'returned');
        const matchesHistoryFilter = showHistory.value ? isHistory : !isHistory;

        return matchesSearch && matchesYear && matchesHistoryFilter;
    });
});

// Helper untuk warna badge per status
const inboundBadgeClass = (id_inbound) => {
    const status = inboundStatusMap.value[id_inbound] ?? 'pending';
    if (status === 'completed') return 'bg-emerald-50 text-emerald-700 ring-emerald-100';
    if (status === 'partial')   return 'bg-orange-50 text-orange-700 ring-orange-100';
    if (status === 'returned')  return 'bg-red-50 text-red-700 ring-red-100';
    return 'bg-blue-50 text-blue-700 ring-blue-100'; // pending
};

const inboundBadgeLabel = (id_inbound, jumlah) => {
    const status = inboundStatusMap.value[id_inbound] ?? 'pending';
    if (status === 'completed') return `${jumlah} Unit ✓ Selesai`;
    if (status === 'partial')   return `${jumlah} Unit ⟳ Sebagian`;
    if (status === 'returned')  return `${jumlah} Unit ✕ Dikembalikan`;
    return `${jumlah} Unit`;
};

// Ikon SVG Mentah (Konsisten dengan Sidebar)
const icons = {
    search: `<path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`,
    layout: `<path d="M3 3h7v7H3V3zM14 3h7v7h-7V3zM14 14h7v7h-7v-7zM3 14h7v7H3v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`,
    package: `<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`,
    view: `<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`
};

const showInventoryModal = ref(false);
const showDetailModal = ref(false);

const detailInboundId = ref("");
const detailItems = ref([]);

const openDetailFor = async (id_inbound) => {
    detailInboundId.value = id_inbound;
    detailItems.value = [];
    showDetailModal.value = true;
    try {
        const response = await axios.get(route('admin.inbound.items', id_inbound));
        detailItems.value = response.data;
    } catch (error) {
        console.error("Gagal mengambil detail item inbound", error);
    }
};

const openAddInventoryFor = async (id_inbound) => {
    const status = inboundStatusMap.value[id_inbound] ?? 'pending';
    if (status === 'completed' || status === 'returned') {
        Swal.fire("Informasi", "Inbound ini sudah selesai diproses atau dikembalikan.", "info");
        return;
    }
    inventoryForm.value.id_inbound = id_inbound;
    await handleInboundChange();
    showInventoryModal.value = true;
};

const layouts = ref([]);
const barangs = ref([]);
const inventoryForm = ref({ 
    id_inbound: "", 
    items: [] // { id_barang, nama_barang, qty, id_location }
});

const handleInboundChange = async () => {
    if (!inventoryForm.value.id_inbound) return;
    
    try {
        const response = await axios.get(route('admin.inbound.items', inventoryForm.value.id_inbound));
        inventoryForm.value.items = response.data.map(item => ({
            id_barang: item.id_barang,
            id_subtype: item.id_subtype,
            nama_barang: item.nama_barang,
            qty: item.qty,
            max_qty: item.qty,
            id_location: "",
        }));
    } catch (error) {
        console.error("Gagal mengambil detail item inbound", error);
        inventoryForm.value.items = [];
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

// Hanya tampilkan inbound yang belum selesai di dropdown modal Add Inventory
const pendingInbounds = computed(() => {
    return inbounds.value.filter(item => {
        const status = inboundStatusMap.value[item.id_inbound] ?? 'pending';
        return status === 'pending' || status === 'partial';
    });
});

onMounted(async () => {
    await fetchDropdownData();
    await checkInboundCompletion();
});



const submitInventory = async () => {
    try {
        await axios.post(route('admin.inbound.inventory.store'), {
            id_inbound: inventoryForm.value.id_inbound,
            items: inventoryForm.value.items
        });
        Swal.fire("Berhasil", "Inventory berhasil ditambahkan", "success");
        showInventoryModal.value = false;
        inventoryForm.value = { id_inbound: "", items: [] };
        // Re-check completion after put-away
        await checkInboundCompletion();
    } catch (error) {
        const errorMessage = error.response?.data?.message || "Gagal menambahkan inventory";
        Swal.fire("Error", errorMessage, "error");
    }
};

const isInventoryFormValid = computed(() => {
    if (!inventoryForm.value.id_inbound || inventoryForm.value.items.length === 0) return false;
    
    const locationUsage = {};
    return inventoryForm.value.items.every(item => {
        const hasLocation = !!item.id_location;
        const validQty = item.qty > 0 && item.qty <= item.max_qty;
        
        if (hasLocation && validQty) {
            if (!locationUsage[item.id_location]) locationUsage[item.id_location] = 0;
            locationUsage[item.id_location] += Number(item.qty);
            
            const loc = getSelectedLocation(item.id_location);
            if (locationUsage[item.id_location] > loc.remaining_capacity) {
                return false;
            }
        }
        
        return hasLocation && validQty;
    });
});

const allLocations = computed(() => {
    return layouts.value.flatMap(l => l.locations.map(loc => {
        const used = loc.inventories ? loc.inventories.reduce((sum, inv) => sum + Number(inv.qty), 0) : 0;
        const remaining = Math.max(0, loc.kapasitas - used);
        return {
            ...loc,
            nama_layout: l.nama_layout,
            used_capacity: used,
            remaining_capacity: remaining
        };
    }));
});

const getSelectedLocation = (id_location) => {
    return allLocations.value.find(loc => loc.id_location === id_location) || {};
};

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
                <!-- History Toggle -->
                <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">History</span>
                    <button
                        @click="showHistory = !showHistory"
                        :class="showHistory ? 'bg-indigo-600' : 'bg-gray-200'"
                        class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    >
                        <span
                            :class="showHistory ? 'translate-x-5' : 'translate-x-0'"
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        />
                    </button>
                    <span class="text-xs font-medium" :class="showHistory ? 'text-indigo-600' : 'text-gray-400'">
                        {{ showHistory ? 'Riwayat Selesai' : 'Dapat Dilokasikan' }}
                    </span>
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
                                <span 
                                    @click="openAddInventoryFor(item.id_inbound)"
                                    :class="inboundBadgeClass(item.id_inbound)" 
                                    class="px-3 py-1 rounded-full text-xs font-bold ring-1 cursor-pointer hover:opacity-80 transition-opacity"
                                    title="Klik untuk Add Inventory"
                                >
                                    {{ inboundBadgeLabel(item.id_inbound, item.jumlah) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button 
                                    @click="openDetailFor(item.id_inbound)"
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
                                <option v-for="item in pendingInbounds" :key="item.id_inbound" :value="item.id_inbound">
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
                                                    <option v-for="loc in allLocations" :key="loc.id_location" :value="loc.id_location" :disabled="loc.remaining_capacity === 0">
                                                        {{ loc.nama_layout }} - {{ loc.kode_location }} (Sisa: {{ loc.remaining_capacity }})
                                                    </option>
                                                </select>
                                                <p v-if="item.id_location && getSelectedLocation(item.id_location).remaining_capacity < item.qty" class="text-xs font-medium text-rose-500 mt-1">
                                                    Kapasitas tidak cukup! Sisa: {{ getSelectedLocation(item.id_location).remaining_capacity }}
                                                </p>
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

        <!-- MODAL DETAIL PREVIEW INBOUND -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="font-bold text-gray-800">Detail Item Inbound - {{ detailInboundId }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Daftar sisa barang yang dapat dilokasikan (put-away).</p>
                    </div>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>
                <div class="p-6">
                    <div class="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-gray-50/50 border-b border-gray-100">
                                <tr>
                                    <th class="py-4 px-6 font-medium text-gray-400 w-16 text-center">No</th>
                                    <th class="py-4 px-6 font-medium text-gray-400">Nama Barang</th>
                                    <th class="py-4 px-6 font-medium text-gray-400 text-center">Remaining Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="(item, idx) in detailItems" :key="idx" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6 text-center font-semibold text-gray-400">{{ idx + 1 }}</td>
                                    <td class="py-4 px-6 font-semibold text-gray-900 text-sm">{{ item.nama_barang }}</td>
                                    <td class="py-4 px-6 text-center font-bold text-indigo-600 text-sm">{{ item.qty }}</td>
                                </tr>
                                <tr v-if="detailItems.length === 0">
                                    <td colspan="3" class="py-12 text-center text-gray-500 font-medium">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" v-html="icons.package"></svg>
                                            <p>Semua barang pada Inbound ini sudah selesai di put-away.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showDetailModal = false" class="px-6 py-2.5 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-semibold text-xs transition-all">
                            Tutup
                        </button>
                    </div>
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