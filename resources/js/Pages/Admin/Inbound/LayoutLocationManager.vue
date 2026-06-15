<script setup>
import { ref, computed } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import Swal from "sweetalert2";

const props = defineProps({
    layouts: { type: Array, default: () => [] }
});

const activeTab = ref('layouts'); // 'layouts' or 'locations'

// State untuk Modal Layout
const showLayoutModal = ref(false);
const isEditLayout = ref(false);
const layoutForm = ref({ id_layout: null, nama_layout: "" });

// State untuk Modal Location
const showLocationModal = ref(false);
const isEditLocation = ref(false);
const locationForm = ref({ id_location: null, kode_location: "", kapasitas: 1, id_layout: "" });

// Computed Locations Flat Map
const allLocations = computed(() => {
    return props.layouts.flatMap(l => l.locations.map(loc => ({
        ...loc,
        nama_layout: l.nama_layout
    })));
});

// Layout Actions
const openAddLayout = () => {
    isEditLayout.value = false;
    layoutForm.value = { id_layout: null, nama_layout: "" };
    showLayoutModal.value = true;
};

const openEditLayout = (layout) => {
    isEditLayout.value = true;
    layoutForm.value = { id_layout: layout.id_layout, nama_layout: layout.nama_layout };
    showLayoutModal.value = true;
};

const saveLayout = () => {
    if (isEditLayout.value) {
        router.put(route('admin.inbound.layout.update', layoutForm.value.id_layout), layoutForm.value, {
            onSuccess: () => {
                showLayoutModal.value = false;
                Swal.fire("Berhasil", "Layout berhasil diperbarui", "success");
            },
            onError: () => Swal.fire("Error", "Gagal memperbarui layout", "error")
        });
    } else {
        router.post(route('admin.inbound.layout.store'), layoutForm.value, {
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
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, Hapus!"
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.inbound.layout.destroy', id), {
                onSuccess: () => Swal.fire("Terhapus!", "Layout berhasil dihapus.", "success"),
                onError: (err) => {
                    if (err && err.error) {
                        Swal.fire("Gagal", err.error, "error");
                    } else {
                        Swal.fire("Gagal", "Tidak dapat menghapus layout karena masih memiliki lokasi.", "error");
                    }
                }
            });
        }
    });
};

// Location Actions
const openAddLocation = () => {
    isEditLocation.value = false;
    locationForm.value = { id_location: null, kode_location: "", kapasitas: 1, id_layout: "" };
    showLocationModal.value = true;
};

const openEditLocation = (location) => {
    isEditLocation.value = true;
    locationForm.value = { 
        id_location: location.id_location, 
        kode_location: location.kode_location, 
        kapasitas: location.kapasitas, 
        id_layout: location.id_layout 
    };
    showLocationModal.value = true;
};

const saveLocation = () => {
    if (isEditLocation.value) {
        router.put(route('admin.inbound.location.update', locationForm.value.id_location), locationForm.value, {
            onSuccess: () => {
                showLocationModal.value = false;
                Swal.fire("Berhasil", "Location berhasil diperbarui", "success");
            },
            onError: () => Swal.fire("Error", "Gagal memperbarui location", "error")
        });
    } else {
        router.post(route('admin.inbound.location.store'), locationForm.value, {
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
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, Hapus!"
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.inbound.location.destroy', id), {
                onSuccess: () => Swal.fire("Terhapus!", "Location berhasil dihapus.", "success"),
                onError: () => Swal.fire("Gagal", "Tidak dapat menghapus location karena mungkin masih berisi inventory.", "error")
            });
        }
    });
};

</script>

<template>
    <Head title="Manajemen Layout & Lokasi" />

    <AdminLayout>
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <Link :href="route('admin.inbound')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                        &larr; Kembali ke Inbound
                    </Link>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Manajemen Layout & Lokasi</h1>
                <p class="text-sm text-gray-500">Kelola master data tata letak gudang dan lokasi penyimpanan barang.</p>
            </div>
            
            <div class="flex gap-3">
                <button v-if="activeTab === 'layouts'" @click="openAddLayout" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 shadow-sm transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Layout
                </button>
                <button v-if="activeTab === 'locations'" @click="openAddLocation" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 shadow-sm transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Location
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
            <div class="flex border-b border-gray-100">
                <button 
                    @click="activeTab = 'layouts'" 
                    :class="['px-6 py-4 text-sm font-semibold transition-colors relative', activeTab === 'layouts' ? 
                    'text-indigo-600' : 'text-gray-500 hover:bg-gray-50']"
                >
                    Daftar Layout
                    <span v-if="activeTab === 'layouts'" class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600"></span>
                </button>
                <button 
                    @click="activeTab = 'locations'" 
                    :class="['px-6 py-4 text-sm font-semibold transition-colors relative', activeTab === 'locations' ? 
                    'text-indigo-600' : 'text-gray-500 hover:bg-gray-50']"
                >
                    Daftar Lokasi
                    <span v-if="activeTab === 'locations'" class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600"></span>
                </button>
            </div>
            
            <!-- Tab Layouts -->
            <div v-show="activeTab === 'layouts'" class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-400 uppercase text-xs font-bold">
                            <tr>
                                <th class="px-6 py-4 w-16 text-center">No</th>
                                <th class="px-6 py-4">Nama Layout</th>
                                <th class="px-6 py-4 text-center">Jumlah Lokasi</th>
                                <th class="px-6 py-4 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(layout, idx) in layouts" :key="layout.id_layout" class="hover:bg-gray-50/80 transition-colors">
                                <td class="px-6 py-4 text-center text-gray-500 font-medium">{{ idx + 1 }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ layout.nama_layout }}</td>
                                <td class="px-6 py-4 text-center text-gray-600">{{ layout.locations.length }} Lokasi</td>
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
                            <tr v-if="layouts.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">Belum ada layout.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Locations -->
            <div v-show="activeTab === 'locations'" class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-400 uppercase text-xs font-bold">
                            <tr>
                                <th class="px-6 py-4 w-16 text-center">No</th>
                                <th class="px-6 py-4">Layout</th>
                                <th class="px-6 py-4">Kode Lokasi</th>
                                <th class="px-6 py-4 text-center">Kapasitas Maksimal</th>
                                <th class="px-6 py-4 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(loc, idx) in allLocations" :key="loc.id_location" class="hover:bg-gray-50/80 transition-colors">
                                <td class="px-6 py-4 text-center text-gray-500 font-medium">{{ idx + 1 }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ loc.nama_layout }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ loc.kode_location }}</td>
                                <td class="px-6 py-4 text-center text-gray-600">{{ loc.kapasitas }} Unit</td>
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
                            <tr v-if="allLocations.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada lokasi penyimpanan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL LAYOUT -->
        <div v-if="showLayoutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-800">{{ isEditLayout ? 'Edit Layout' : 'Tambah Layout' }}</h3>
                    <button @click="showLayoutModal = false" class="text-gray-400 hover:text-red-500">&times;</button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveLayout">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Layout</label>
                            <input v-model="layoutForm.nama_layout" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required placeholder="Gudang A, Area B..." />
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showLayoutModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL LOCATION -->
        <div v-if="showLocationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-800">{{ isEditLocation ? 'Edit Location' : 'Tambah Location' }}</h3>
                    <button @click="showLocationModal = false" class="text-gray-400 hover:text-red-500">&times;</button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveLocation">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Pilih Layout</label>
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
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showLocationModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>
