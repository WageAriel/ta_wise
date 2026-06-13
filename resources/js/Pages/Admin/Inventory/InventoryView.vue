<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SidebarAdmin from "@/Components/SidebarAdmin.vue";
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    inventories: { type: Array, default: () => [] }
});

const inventoryData = ref(props.inventories);

const activeTab = ref('overview');
const searchQuery = ref('');
const entriesPerPage = ref(10);
const yearFilter = ref('2026');

// Computed property untuk menghitung status secara dinamis
const processedInventory = computed(() => {
    return inventoryData.value.map(item => {
        let status = 'normal';
        // Fleksibel: Critical jika stok <= 50% dari batas minimal
        if (item.currentStock <= (item.minStock * 0.5)) {
            status = 'critical';
        } 
        // Low Stock jika stok <= batas minimal (tapi > 50% minStock)
        else if (item.currentStock <= item.minStock) {
            status = 'low';
        }
        return { ...item, status };
    });
});

const totalItems = computed(() => processedInventory.value.length);
const criticalStock = computed(() => processedInventory.value.filter(item => item.status === 'critical').length);
const lowStock = computed(() => processedInventory.value.filter(item => item.status === 'low').length);

const filteredInventory = computed(() => {
    return processedInventory.value.filter(item => {
        return item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
               item.id.toLowerCase().includes(searchQuery.value.toLowerCase());
    });
});

const alertItems = computed(() => {
    return processedInventory.value.filter(item => item.status === 'low' || item.status === 'critical');
});

// Outbound Modal Logic
const showOutboundModal = ref(false);
const availableItems = ref([]);
const isSubmittingOutbound = ref(false);
const outboundForm = ref({
    tanggal: new Date().toISOString().split('T')[0],
    tujuan: '',
    items: []
});

const openOutboundModal = async () => {
    showOutboundModal.value = true;
    outboundForm.value = {
        tanggal: new Date().toISOString().split('T')[0],
        tujuan: '',
        items: []
    };
    try {
        const response = await axios.get('/admin/outbound/items');
        availableItems.value = response.data;
    } catch (error) {
        console.error("Gagal mengambil data barang:", error);
    }
};

const addOutboundItemRow = () => {
    outboundForm.value.items.push({ inventoryObj: null, id_barang: '', name: '', max_qty: 0, qty: 1, location: '' });
};

const removeOutboundItemRow = (index) => {
    outboundForm.value.items.splice(index, 1);
};

const onBarangSelect = (index) => {
    const item = outboundForm.value.items[index];
    if (item.inventoryObj) {
        item.id_barang = item.inventoryObj.id_barang;
        item.name = item.inventoryObj.barang?.nama_barang || item.inventoryObj.barang?.name || 'Unknown';
        item.location = item.inventoryObj.location?.kode_location || 'Unknown';
        item.max_qty = item.inventoryObj.qty;
        if (item.qty > item.max_qty) item.qty = item.max_qty;
    }
};

const submitOutbound = async () => {
    if (outboundForm.value.items.length === 0) {
        Swal.fire("Peringatan", "Pilih minimal 1 barang.", "warning");
        return;
    }
    isSubmittingOutbound.value = true;
    try {
        const payload = {
            tanggal: outboundForm.value.tanggal,
            tujuan: outboundForm.value.tujuan,
            barang_id: outboundForm.value.items.map(i => i.id_barang),
            qty: outboundForm.value.items.map(i => i.qty),
        };
        await axios.post('/admin/outbound', payload);
        showOutboundModal.value = false;
        
        Swal.fire({
            title: "Berhasil!",
            text: "Data outbound berhasil disimpan dan stok diperbarui.",
            icon: "success",
            confirmButtonColor: "#3b82f6"
        }).then(() => {
            window.location.reload();
        });
    } catch (error) {
        console.error(error);
        const errorMsg = error.response?.data?.message || "Terjadi kesalahan saat menyimpan data.";
        Swal.fire({
            title: "Gagal!",
            text: errorMsg,
            icon: "error",
            confirmButtonColor: "#ef4444"
        });
    } finally {
        isSubmittingOutbound.value = false;
    }
};

</script>

<template>
    <Head title="Inventory | Admin" />
    
    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <!-- Sidebar -->
        <SidebarAdmin class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <!-- Main -->
        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-8xl mx-auto px-6 py-10 lg:px-10">
                
                <!-- ── Page Header ── -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                            Inventory Management
                        </h1>
                        <p class="text-slate-500 mt-1 text-sm">
                            Monitor dan kelola stok barang secara real-time.
                        </p>
                    </div>
                </div>

                <!-- 1. Alert Section -->
                <div v-if="criticalStock > 0 || lowStock > 0" class="mb-8 p-4 rounded-xl border border-red-200 bg-red-50 flex gap-4 items-start shadow-sm">
                    <div class="p-2 bg-red-100 rounded-lg text-red-600 flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-red-800">Peringatan Stok</h3>
                        <p class="text-sm text-red-600 mt-1">Terdapat <strong>{{ criticalStock }}</strong> barang dalam kondisi kritis dan <strong>{{ lowStock }}</strong> barang menipis. Harap segera lakukan restock.</p>
                    </div>
                </div>

                <!-- 2. Stats Section -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                    <!-- Total Items -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-blue-100 text-blue-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Items</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ totalItems }}</p>
                        </div>
                    </div>

                    <!-- Low Stock Items -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-amber-100 text-amber-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Low Stock Items</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ lowStock }}</p>
                        </div>
                    </div>

                    <!-- Critical Stock -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-red-100 text-red-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Critical Stock</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-0.5">{{ criticalStock }}</p>
                        </div>
                    </div>
                </div>

                <!-- 3. Tabs & Filtering -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
                    
                    <!-- Tab Headers & Outbound Button -->
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 pt-4 bg-slate-50">
                        <div class="flex items-center gap-4">
                            <button @click="activeTab = 'overview'" class="pb-4 font-bold flex items-center gap-2 border-b-2 transition-colors text-sm" :class="activeTab === 'overview' ? 'border-slate-800 text-slate-800' : 'border-transparent text-slate-400 hover:text-slate-600'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Stock Overview
                            </button>
                            <button @click="activeTab = 'alert'" class="pb-4 font-bold flex items-center gap-2 border-b-2 transition-colors text-sm" :class="activeTab === 'alert' ? 'border-red-600 text-red-600' : 'border-transparent text-red-400 hover:text-red-500'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Stock Alert
                                <span v-if="criticalStock + lowStock > 0" class="bg-red-600 text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full shadow-sm">{{ criticalStock + lowStock }}</span>
                            </button>
                        </div>
                        <div class="pb-3">
                            <button @click="openOutboundModal" class="px-4 py-2 border border-slate-300 text-slate-700 bg-white rounded-lg text-sm font-bold shadow-sm hover:bg-slate-50 transition">
                                Outbound
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content: Stock Overview -->
                    <div v-if="activeTab === 'overview'" class="p-6">
                        <!-- 4. Table Controls -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-slate-500">Tampilkan</span>
                                <select v-model="entriesPerPage" class="border-slate-200 rounded-lg text-sm p-2 bg-slate-50 focus:ring-blue-500 focus:border-blue-500 font-semibold">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                                <span class="text-sm font-medium text-slate-500">entri</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <select v-model="yearFilter" class="border-slate-200 rounded-lg text-sm p-2 bg-slate-50 focus:ring-blue-500 focus:border-blue-500 font-semibold">
                                    <option value="2026">2026</option>
                                    <option value="2025">2025</option>
                                </select>
                                <div class="relative">
                                    <input type="text" v-model="searchQuery" placeholder="Cari barang..." class="pl-9 pr-4 py-2 border-slate-200 rounded-lg text-sm bg-slate-50 focus:ring-blue-500 focus:border-blue-500 w-full sm:w-64">
                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto border border-slate-100 rounded-xl">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">No</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Item ID</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Item Name</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Kategori</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Current Stock</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Min/Max</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Location</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(item, idx) in filteredInventory" :key="item.id" class="hover:bg-slate-50/60 transition-colors">
                                        <td class="px-6 py-4 text-slate-400 text-xs">{{ idx + 1 }}</td>
                                        <td class="px-6 py-4 font-mono font-bold text-slate-800">{{ item.id }}</td>
                                        <td class="px-6 py-4 font-semibold text-slate-900">{{ item.name }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-bold">{{ item.category }}</span>
                                        </td>
                                        <td class="px-6 py-4 font-extrabold text-slate-900">{{ item.currentStock }} {{ item.unit }}</td>
                                        <td class="px-6 py-4 text-slate-500 text-xs font-medium">{{ item.minStock }} / {{ item.maxStock }}</td>
                                        <td class="px-6 py-4 text-slate-600 text-sm font-medium">{{ item.location }}</td>
                                        <td class="px-6 py-4">
                                            <span v-if="item.status === 'normal'" class="inline-flex items-center px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md text-xs font-bold">Normal</span>
                                            <span v-if="item.status === 'low'" class="inline-flex items-center px-2 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-md text-xs font-bold">Low Stock</span>
                                            <span v-if="item.status === 'critical'" class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 border border-red-200 rounded-md text-xs font-bold">Critical</span>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredInventory.length === 0">
                                        <td colspan="8" class="text-center py-12 text-slate-500">
                                            <svg class="mx-auto w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="font-semibold text-slate-400">Tidak ada data ditemukan.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Content: Stock Alert -->
                    <div v-if="activeTab === 'alert'" class="p-6">
                        <!-- 5. Stock Alert Cards (Wireframe matching) -->
                        <div class="space-y-6">
                            <div v-for="item in alertItems" :key="item.id" 
                                class="p-5 border flex flex-col sm:flex-row sm:items-start justify-between gap-4 shadow-sm rounded-xl"
                                :class="item.status === 'critical' ? 'bg-red-100 border-red-500' : 'bg-orange-100 border-orange-500'"
                            >
                                <div class="flex gap-5">
                                    <!-- Icon Box -->
                                    <div class="w-16 h-16 rounded-xl border-2 flex items-center justify-center flex-shrink-0 bg-white/50"
                                        :class="item.status === 'critical' ? 'border-red-600 text-red-600' : 'border-orange-500 text-orange-600'">
                                        <svg v-if="item.status === 'critical'" class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <svg v-else class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="mt-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="px-3 py-0.5 rounded-full text-xs font-bold text-white shadow-sm"
                                                :class="item.status === 'critical' ? 'bg-red-600' : 'bg-orange-500'">
                                                {{ item.status === 'critical' ? 'Critical' : 'Low Stock' }}
                                            </span>
                                            <span class="text-slate-800 text-sm font-semibold">{{ item.id }}</span>
                                        </div>
                                        <h4 class="text-slate-900 font-extrabold text-xl mb-2">{{ item.name }}</h4>
                                        <div class="text-[13px] text-slate-800 font-medium flex items-center gap-2 mb-1.5">
                                            <span>Current Stock: <strong :class="item.status === 'critical' ? 'text-red-600' : 'text-orange-600'">{{ item.currentStock }} {{ item.unit }}</strong></span>
                                            <span class="text-black font-bold border-l-2 border-black/20 h-3 mx-1"></span>
                                            <span>Minimum Required: <strong class="text-slate-900">{{ item.minStock }} {{ item.unit }}</strong></span>
                                        </div>
                                        <p class="text-[13px] text-slate-800 font-medium">Location: {{ item.location }}</p>
                                    </div>
                                </div>

                                <!-- Button -->
                                <div class="mt-2 sm:mt-0">
                                    <button class="px-5 py-2.5 rounded-lg font-bold text-sm text-white shadow-md hover:brightness-110 transition border border-black/10"
                                        :class="item.status === 'critical' ? 'bg-red-600' : 'bg-orange-500'">
                                        Cetak Dokumen
                                    </button>
                                </div>
                            </div>
                            
                            <div v-if="alertItems.length === 0" class="text-center py-10 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                                <svg class="w-12 h-12 text-emerald-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-slate-600 font-bold">Stok dalam keadaan aman. Tidak ada peringatan stok saat ini.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Outbound Modal -->
        <div v-if="showOutboundModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <h2 class="text-lg font-bold text-slate-900">Form Outbound Barang</h2>
                    <button @click="showOutboundModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tanggal Outbound</label>
                            <input type="date" v-model="outboundForm.tanggal" class="w-full border-slate-200 rounded-lg text-sm p-2.5 focus:ring-blue-500 focus:border-blue-500 bg-slate-50" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tujuan / Keterangan</label>
                            <input type="text" v-model="outboundForm.tujuan" placeholder="Masukkan tujuan pengeluaran barang..." class="w-full border-slate-200 rounded-lg text-sm p-2.5 focus:ring-blue-500 focus:border-blue-500 bg-slate-50" />
                        </div>
                    </div>

                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-md font-bold text-slate-800">Daftar Barang Keluar</h3>
                        <button @click="addOutboundItemRow" class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-sm font-bold hover:bg-blue-100 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Tambah Barang
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 font-bold text-slate-600 w-12 text-center">No</th>
                                    <th class="px-4 py-3 font-bold text-slate-600">Pilih Inventory</th>
                                    <th class="px-4 py-3 font-bold text-slate-600">Nama Barang</th>
                                    <th class="px-4 py-3 font-bold text-slate-600">Lokasi</th>
                                    <th class="px-4 py-3 font-bold text-slate-600 w-32">Quantity</th>
                                    <th class="px-4 py-3 font-bold text-slate-600 w-16">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, index) in outboundForm.items" :key="index" class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3 text-center text-slate-500">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <select v-model="item.inventoryObj" @change="onBarangSelect(index)" class="w-full border-slate-200 rounded-lg text-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option :value="null" disabled>Pilih Stok...</option>
                                            <option v-for="inv in availableItems" :key="inv.id_inventory" :value="inv">
                                                {{ inv.barang?.nama_barang || inv.barang?.name }} (Stok: {{ inv.qty }})
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 font-semibold">{{ item.name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ item.location }}</td>
                                    <td class="px-4 py-3">
                                        <input type="number" min="1" :max="item.max_qty" v-model="item.qty" class="w-full border-slate-200 rounded-lg text-sm p-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="removeOutboundItemRow(index)" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="outboundForm.items.length === 0">
                                    <td colspan="6" class="text-center py-8 text-slate-500">
                                        Belum ada barang ditambahkan. Klik "Tambah Barang".
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                    <button @click="showOutboundModal = false" class="px-5 py-2.5 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-200 transition">Batal</button>
                    <button @click="submitOutbound" :disabled="isSubmittingOutbound" class="px-5 py-2.5 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50">
                        {{ isSubmittingOutbound ? 'Menyimpan...' : 'Simpan Outbound' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
