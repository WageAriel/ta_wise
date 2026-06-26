<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SidebarAdmin from "@/Components/SidebarAdmin.vue";
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    inventories: { type: Array, default: () => [] },
    locationsData: { type: Array, default: () => [] },
    layoutsRaw: { type: Array, default: () => [] },
    gudangsRaw: { type: Array, default: () => [] }
});

const inventoryData = ref(props.inventories);

const activeTab = ref('overview');
const searchQuery = ref('');
const entriesPerPage = ref(10);
const yearFilter = ref('2026');

// Menghitung total stok per id_barang
const totalStockByBarang = computed(() => {
    const totals = {};
    inventoryData.value.forEach(item => {
        if (!totals[item.id_barang]) {
            totals[item.id_barang] = 0;
        }
        totals[item.id_barang] += item.currentStock;
    });
    return totals;
});

// Computed property untuk menghitung status secara dinamis berdasarkan total stok gabungan
const processedInventory = computed(() => {
    return inventoryData.value.map(item => {
        let status = 'normal';
        const totalStock = totalStockByBarang.value[item.id_barang];
        
        // Critical jika total stok <= 50% dari batas minimal
        if (totalStock <= (item.minStock * 0.5)) {
            status = 'critical';
        } 
        // Low Stock jika total stok <= batas minimal (tapi > 50% minStock)
        else if (totalStock <= item.minStock) {
            status = 'low';
        }
        return { ...item, status, totalStock };
    });
});

// Mengelompokkan berdasarkan id_barang agar statistik & alert tidak double
const uniqueBarangs = computed(() => {
    const map = new Map();
    processedInventory.value.forEach(item => {
        if (!map.has(item.id_barang)) {
            // Kita kumpulkan semua lokasi untuk barang ini
            const locations = processedInventory.value
                .filter(i => i.id_barang === item.id_barang)
                .map(i => i.location);
            
            map.set(item.id_barang, { ...item, allLocations: locations });
        }
    });
    return Array.from(map.values());
});

const totalItems = computed(() => inventoryData.value.length);
const criticalStock = computed(() => uniqueBarangs.value.filter(item => item.status === 'critical').length);
const lowStock = computed(() => uniqueBarangs.value.filter(item => item.status === 'low').length);

const filteredInventory = computed(() => {
    return processedInventory.value.filter(item => {
        return item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
               item.id.toLowerCase().includes(searchQuery.value.toLowerCase());
    });
});

const alertItems = computed(() => {
    return uniqueBarangs.value.filter(item => 
        item.status === 'low' || item.status === 'critical');
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

// --- Gudang, Layout & Location Management State ---
const subTabLokasi = ref('kapasitas'); // 'kapasitas', 'gudang', 'layouts', 'locations'

// Gudang State
const showGudangModal = ref(false);
const isEditGudang = ref(false);
const gudangForm = ref({ id_gudang: null, nama_gudang: "" });

// Layout State
const showLayoutModal = ref(false);
const isEditLayout = ref(false);
const layoutForm = ref({ id_layout: null, nama_layout: "", id_gudang: "" });

// Location State
const showLocationModal = ref(false);
const isEditLocation = ref(false);
const locationForm = ref({ id_location: null, kode_location: "", kapasitas: 1, id_gudang: "", id_layout: "" });

const availableLayouts = computed(() => {
    if (!locationForm.value.id_gudang) return [];
    const gudang = props.gudangsRaw.find(g => g.id_gudang === locationForm.value.id_gudang);
    return gudang ? gudang.layouts : [];
});

const allLayoutsRaw = computed(() => {
    return props.gudangsRaw.flatMap(g => g.layouts.map(l => ({
        ...l,
        nama_gudang: g.nama_gudang
    })));
});

const allLocationsRaw = computed(() => {
    return props.gudangsRaw.flatMap(g => g.layouts.flatMap(l => l.locations.map(loc => ({
        ...loc,
        nama_layout: l.nama_layout,
        nama_gudang: g.nama_gudang
    }))));
});

// Gudang Actions
const openAddGudang = () => {
    isEditGudang.value = false;
    gudangForm.value = { id_gudang: null, nama_gudang: "" };
    showGudangModal.value = true;
};

const openEditGudang = (gudang) => {
    isEditGudang.value = true;
    gudangForm.value = { id_gudang: gudang.id_gudang, nama_gudang: gudang.nama_gudang };
    showGudangModal.value = true;
};

const saveGudang = () => {
    if (isEditGudang.value) {
        router.put(route('admin.inventory.gudang.update', gudangForm.value.id_gudang), gudangForm.value, {
            onSuccess: () => {
                showGudangModal.value = false;
                Swal.fire("Berhasil", "Gudang berhasil diperbarui", "success");
            },
            onError: () => Swal.fire("Error", "Gagal memperbarui gudang", "error")
        });
    } else {
        router.post(route('admin.inventory.gudang.store'), gudangForm.value, {
            onSuccess: () => {
                showGudangModal.value = false;
                Swal.fire("Berhasil", "Gudang berhasil ditambahkan", "success");
            },
            onError: () => Swal.fire("Error", "Gagal menambahkan gudang", "error")
        });
    }
};

const deleteGudang = (id) => {
    Swal.fire({
        title: "Apakah Anda yakin?",
        text: "Gudang yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#10b981",
        cancelButtonColor: "#ef4444",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.inventory.gudang.destroy', id), {
                onSuccess: () => Swal.fire("Terhapus!", "Gudang berhasil dihapus.", "success"),
                onError: (err) => Swal.fire("Gagal", err?.error || "Tidak dapat menghapus gudang karena masih memiliki layout.", "error")
            });
        }
    });
};

// Layout Actions
const openAddLayout = () => {
    isEditLayout.value = false;
    layoutForm.value = { id_layout: null, nama_layout: "", id_gudang: "" };
    showLayoutModal.value = true;
};

const openEditLayout = (layout) => {
    isEditLayout.value = true;
    layoutForm.value = { id_layout: layout.id_layout, nama_layout: layout.nama_layout, id_gudang: layout.id_gudang };
    showLayoutModal.value = true;
};

const saveLayout = () => {
    if (isEditLayout.value) {
        router.put(route('admin.inventory.layout.update', layoutForm.value.id_layout), layoutForm.value, {
            onSuccess: () => {
                showLayoutModal.value = false;
                Swal.fire("Berhasil", "Layout berhasil diperbarui", "success");
            },
            onError: () => Swal.fire("Error", "Gagal memperbarui layout", "error")
        });
    } else {
        router.post(route('admin.inventory.layout.store'), layoutForm.value, {
            onSuccess: () => {
                showLayoutModal.value = false;
                Swal.fire("Berhasil", "Layout berhasil ditambahkan", "success");
            },
            onError: () => Swal.fire("Error", "Gagal menambahkan layout", "error")
        });
    }
};

const deleteLayout = (id) => {
    Swal.fire({
        title: "Apakah Anda yakin?",
        text: "Layout yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#10b981",
        cancelButtonColor: "#ef4444",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.inventory.layout.destroy', id), {
                onSuccess: () => Swal.fire("Terhapus!", "Layout berhasil dihapus.", "success"),
                onError: (err) => Swal.fire("Gagal", err?.error || "Tidak dapat menghapus layout karena masih memiliki lokasi.", "error")
            });
        }
    });
};

// Location Actions
const openAddLocation = () => {
    isEditLocation.value = false;
    locationForm.value = { id_location: null, kode_location: "", kapasitas: 1, id_gudang: "", id_layout: "" };
    showLocationModal.value = true;
};

const openEditLocation = (location) => {
    isEditLocation.value = true;
    locationForm.value = { 
        id_location: location.id_location, 
        kode_location: location.kode_location, 
        kapasitas: location.kapasitas, 
        id_gudang: location.id_gudang || (props.gudangsRaw.find(g => g.layouts.some(l => l.id_layout === location.id_layout))?.id_gudang || ""),
        id_layout: location.id_layout 
    };
    showLocationModal.value = true;
};

const saveLocation = () => {
    if (isEditLocation.value) {
        router.put(route('admin.inventory.location.update', locationForm.value.id_location), locationForm.value, {
            onSuccess: () => {
                showLocationModal.value = false;
                Swal.fire("Berhasil", "Location berhasil diperbarui", "success");
            },
            onError: () => Swal.fire("Error", "Gagal memperbarui location", "error")
        });
    } else {
        router.post(route('admin.inventory.location.store'), locationForm.value, {
            onSuccess: () => {
                showLocationModal.value = false;
                Swal.fire("Berhasil", "Location berhasil ditambahkan", "success");
            },
            onError: () => Swal.fire("Error", "Gagal menambahkan location", "error")
        });
    }
};

const deleteLocation = (id) => {
    Swal.fire({
        title: "Apakah Anda yakin?",
        text: "Location yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#10b981",
        cancelButtonColor: "#ef4444",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.inventory.location.destroy', id), {
                onSuccess: () => Swal.fire("Terhapus!", "Location berhasil dihapus.", "success"),
                onError: (err) => Swal.fire("Gagal", err?.error || "Tidak dapat menghapus location karena mungkin masih berisi inventory.", "error")
            });
        }
    });
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
                            <button @click="activeTab = 'locations'" class="pb-4 font-bold flex items-center gap-2 border-b-2 transition-colors text-sm" :class="activeTab === 'locations' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-indigo-500'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                Manajemen Lokasi
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
                                            <span>Total Stock: <strong :class="item.status === 'critical' ? 'text-red-600' : 'text-orange-600'">{{ item.totalStock }} {{ item.unit }}</strong></span>
                                            <span class="text-black font-bold border-l-2 border-black/20 h-3 mx-1"></span>
                                            <span>Minimum Required: <strong class="text-slate-900">{{ item.minStock }} {{ item.unit }}</strong></span>
                                        </div>
                                        <p class="text-[13px] text-slate-800 font-medium">Locations: {{ item.allLocations.join(', ') }}</p>
                                    </div>
                                </div>

                                <div class="mt-2 sm:mt-0">
                                    <a :href="route('admin.inventory.pdf', item.id_barang)" target="_blank" class="inline-block px-5 py-2.5 rounded-lg font-bold text-sm text-white shadow-md hover:brightness-110 transition border border-black/10"
                                        :class="item.status === 'critical' ? 'bg-red-600' : 'bg-orange-500'">
                                        Cetak Dokumen
                                    </a>
                                </div>
                            </div>
                            
                            <div v-if="alertItems.length === 0" class="text-center py-10 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                                <svg class="w-12 h-12 text-emerald-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-slate-600 font-bold">Stok dalam keadaan aman. Tidak ada peringatan stok saat ini.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Content: Manajemen Lokasi -->
                    <div v-if="activeTab === 'locations'" class="p-6">
                        <!-- Sub-tabs Manajemen Lokasi -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 border-b border-slate-100 pb-4">
                            <div class="flex gap-4">
                                <button @click="subTabLokasi = 'kapasitas'" :class="subTabLokasi === 'kapasitas' ? 'text-indigo-600 border-b-2 border-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-500 font-semibold'" class="pb-2 px-1">Kapasitas Gudang</button>
                                <button @click="subTabLokasi = 'gudang'" :class="subTabLokasi === 'gudang' ? 'text-indigo-600 border-b-2 border-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-500 font-semibold'" class="pb-2 px-1">Daftar Gudang</button>
                                <button @click="subTabLokasi = 'layouts'" :class="subTabLokasi === 'layouts' ? 'text-indigo-600 border-b-2 border-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-500 font-semibold'" class="pb-2 px-1">Daftar Layout</button>
                                <button @click="subTabLokasi = 'locations'" :class="subTabLokasi === 'locations' ? 'text-indigo-600 border-b-2 border-indigo-600 font-bold' : 'text-slate-500 hover:text-indigo-500 font-semibold'" class="pb-2 px-1">Daftar Lokasi</button>
                            </div>
                            <div class="flex gap-3">
                                <button v-if="subTabLokasi === 'gudang'" @click="openAddGudang" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 shadow-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Gudang
                                </button>
                                <button v-if="subTabLokasi === 'layouts'" @click="openAddLayout" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Layout
                                </button>
                                <button v-if="subTabLokasi === 'locations'" @click="openAddLocation" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 shadow-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Location
                                </button>
                            </div>
                        </div>

                        <!-- Sub-tab Kapasitas -->
                        <div v-if="subTabLokasi === 'kapasitas'">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div v-for="loc in locationsData" :key="loc.id_location" class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-800">{{ loc.layout_name }} - {{ loc.kode_location }}</h3>
                                            <p class="text-xs text-slate-500 font-medium mt-1">ID: LOC-{{ String(loc.id_location).padStart(3, '0') }}</p>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700': loc.persentase < 50,
                                                'bg-yellow-100 text-yellow-700': loc.persentase >= 50 && loc.persentase < 80,
                                                'bg-red-100 text-red-700': loc.persentase >= 80
                                            }">
                                            {{ loc.persentase }}% Terisi
                                        </span>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="w-full bg-slate-100 rounded-full h-3 mb-3 overflow-hidden border border-slate-200/50">
                                        <div class="h-full rounded-full transition-all duration-500"
                                            :class="{
                                                'bg-emerald-500': loc.persentase < 50,
                                                'bg-yellow-500': loc.persentase >= 50 && loc.persentase < 80,
                                                'bg-red-500': loc.persentase >= 80
                                            }"
                                            :style="'width: ' + loc.persentase + '%'">
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center text-sm font-medium">
                                        <div class="text-slate-600">
                                            <span class="text-slate-900 font-bold">{{ loc.digunakan }}</span> / {{ loc.kapasitas }} Unit
                                        </div>
                                        <div class="text-slate-500 text-xs">
                                            Sisa: <span class="font-bold" :class="loc.tersisa === 0 ? 'text-red-500' : 'text-slate-700'">{{ loc.tersisa }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="!locationsData || locationsData.length === 0" class="text-center py-10 bg-slate-50 rounded-xl border border-slate-200 border-dashed">
                                <p class="text-slate-600 font-bold">Data lokasi penyimpanan tidak tersedia.</p>
                            </div>
                        </div>

                        <!-- Sub-tab Gudang -->
                        <div v-if="subTabLokasi === 'gudang'">
                            <div class="overflow-x-auto border border-slate-100 rounded-xl">
                                <table class="w-full text-left text-sm whitespace-nowrap">
                                    <thead class="bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center w-16">No</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Nama Gudang</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Jumlah Layout</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center w-32">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="(gudang, idx) in gudangsRaw" :key="gudang.id_gudang" class="hover:bg-slate-50/60 transition-colors">
                                            <td class="px-6 py-4 text-center text-slate-500 font-medium">{{ idx + 1 }}</td>
                                            <td class="px-6 py-4 font-semibold text-slate-900">{{ gudang.nama_gudang }}</td>
                                            <td class="px-6 py-4 text-center text-slate-600 font-bold">{{ gudang.layouts?.length || 0 }} Layout</td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button @click="openEditGudang(gudang)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                    <button @click="deleteGudang(gudang.id_gudang)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="gudangsRaw.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-slate-500 font-semibold">Belum ada data gudang.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sub-tab Layouts -->
                        <div v-if="subTabLokasi === 'layouts'">
                            <div class="overflow-x-auto border border-slate-100 rounded-xl">
                                <table class="w-full text-left text-sm whitespace-nowrap">
                                    <thead class="bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center w-16">No</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Nama Layout</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Gudang</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Jumlah Lokasi</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center w-32">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="(layout, idx) in allLayoutsRaw" :key="layout.id_layout" class="hover:bg-slate-50/60 transition-colors">
                                            <td class="px-6 py-4 text-center text-slate-500 font-medium">{{ idx + 1 }}</td>
                                            <td class="px-6 py-4 font-semibold text-slate-900">{{ layout.nama_layout }}</td>
                                            <td class="px-6 py-4 font-medium text-slate-700">{{ layout.nama_gudang }}</td>
                                            <td class="px-6 py-4 text-center text-slate-600 font-bold">{{ layout.locations?.length || 0 }} Lokasi</td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button @click="openEditLayout(layout)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                    <button @click="deleteLayout(layout.id_layout)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="allLayoutsRaw.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-slate-500 font-semibold">Belum ada data layout.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sub-tab Locations -->
                        <div v-if="subTabLokasi === 'locations'">
                            <div class="overflow-x-auto border border-slate-100 rounded-xl">
                                <table class="w-full text-left text-sm whitespace-nowrap">
                                    <thead class="bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center w-16">No</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Layout</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Kode Lokasi</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Kapasitas Max</th>
                                            <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-center w-32">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="(loc, idx) in allLocationsRaw" :key="loc.id_location" class="hover:bg-slate-50/60 transition-colors">
                                            <td class="px-6 py-4 text-center text-slate-500 font-medium">{{ idx + 1 }}</td>
                                            <td class="px-6 py-4 text-slate-600 font-medium">{{ loc.nama_layout }}</td>
                                            <td class="px-6 py-4 font-bold text-slate-900">{{ loc.kode_location }}</td>
                                            <td class="px-6 py-4 text-center text-slate-600 font-bold">{{ loc.kapasitas }} Unit</td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button @click="openEditLocation(loc)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>
                                                    <button @click="deleteLocation(loc.id_location)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="allLocationsRaw.length === 0">
                                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-semibold">Belum ada lokasi penyimpanan.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </main>

        <!-- Outbound Modal -->
        
        <!-- MODAL GUDANG -->
        <div v-if="showGudangModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800">{{ isEditGudang ? 'Edit Gudang' : 'Tambah Gudang' }}</h3>
                    <button @click="showGudangModal = false" class="text-slate-400 hover:text-red-500">&times;</button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveGudang">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Gudang</label>
                            <input v-model="gudangForm.nama_gudang" type="text" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm p-2 bg-slate-50" required placeholder="Gudang Bahan Baku..." />
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showGudangModal = false" class="px-4 py-2 text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg text-sm font-semibold">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-semibold">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL LAYOUT -->
        <div v-if="showLayoutModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800">{{ isEditLayout ? 'Edit Layout' : 'Tambah Layout' }}</h3>
                    <button @click="showLayoutModal = false" class="text-slate-400 hover:text-red-500">&times;</button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveLayout">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Pilih Gudang</label>
                            <select v-model="layoutForm.id_gudang" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm p-2 bg-slate-50" required>
                                <option value="" disabled>-- Pilih Gudang --</option>
                                <option v-for="g in gudangsRaw" :key="g.id_gudang" :value="g.id_gudang">{{ g.nama_gudang }}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Layout</label>
                            <input v-model="layoutForm.nama_layout" type="text" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm p-2 bg-slate-50" required placeholder="Lorong A, Area B..." />
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showLayoutModal = false" class="px-4 py-2 text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg text-sm font-semibold">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-semibold">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL LOCATION -->
        <div v-if="showLocationModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-800">{{ isEditLocation ? 'Edit Location' : 'Tambah Location' }}</h3>
                    <button @click="showLocationModal = false" class="text-slate-400 hover:text-red-500">&times;</button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveLocation">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Pilih Gudang</label>
                            <select v-model="locationForm.id_gudang" @change="locationForm.id_layout = ''" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm p-2 bg-slate-50" required>
                                <option value="" disabled>-- Pilih Gudang --</option>
                                <option v-for="g in gudangsRaw" :key="g.id_gudang" :value="g.id_gudang">{{ g.nama_gudang }}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Pilih Layout</label>
                            <select v-model="locationForm.id_layout" :disabled="!locationForm.id_gudang" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm p-2 bg-slate-50 disabled:bg-slate-100 disabled:text-slate-400" required>
                                <option value="" disabled>-- Pilih Layout --</option>
                                <option v-for="l in availableLayouts" :key="l.id_layout" :value="l.id_layout">{{ l.nama_layout }}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Location</label>
                            <input v-model="locationForm.kode_location" type="text" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm p-2 bg-slate-50" required placeholder="A1, B2..." />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Kapasitas Maksimal</label>
                            <input v-model="locationForm.kapasitas" type="number" min="1" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm p-2 bg-slate-50" required />
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showLocationModal = false" class="px-4 py-2 text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg text-sm font-semibold">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</template>
