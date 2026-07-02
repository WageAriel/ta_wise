<script setup>
import { ref, computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../Layouts/AdminLayout.vue";
import Swal from "sweetalert2";

const props = defineProps({
    outbounds: { type: Array, default: () => [] },
    inventoryStock: { type: Array, default: () => [] },
    recipients: { type: Array, default: () => [] }
});

// Search & Filtering State
const searchQuery = ref("");
const selectedYear = ref("");
const showAddModal = ref(false);
const showDetailModal = ref(false);
const selectedOutbound = ref(null);

// Recipient Master Data Modal State
const showRecipientModal = ref(false);
const recipientModalMode = ref('list'); // 'list', 'create', 'edit'
const activeRecipient = ref(null);

const recipientForm = useForm({
    nama_penerima: "",
    alamat_tujuan: "",
    kota_tujuan: "",
    telepon_penerima: "",
    keterangan_tujuan: "",
});

const openRecipientModal = () => {
    recipientModalMode.value = 'list';
    showRecipientModal.value = true;
};

const openAddRecipient = () => {
    recipientForm.reset();
    recipientModalMode.value = 'create';
};

const openEditRecipient = (rec) => {
    activeRecipient.value = rec;
    recipientForm.nama_penerima = rec.nama_penerima;
    recipientForm.alamat_tujuan = rec.alamat_tujuan;
    recipientForm.kota_tujuan = rec.kota_tujuan;
    recipientForm.telepon_penerima = rec.telepon_penerima;
    recipientForm.keterangan_tujuan = rec.keterangan_tujuan;
    recipientModalMode.value = 'edit';
};

const submitRecipient = () => {
    if (recipientModalMode.value === 'create') {
        recipientForm.post(route("admin.outbound.recipients.store"), {
            onSuccess: () => {
                Swal.fire("Berhasil", "Penerima berhasil ditambahkan", "success");
                recipientModalMode.value = 'list';
            }
        });
    } else {
        recipientForm.put(route("admin.outbound.recipients.update", activeRecipient.value.id_recipient), {
            onSuccess: () => {
                Swal.fire("Berhasil", "Penerima berhasil diperbarui", "success");
                recipientModalMode.value = 'list';
            }
        });
    }
};

const deleteRecipient = (id) => {
    Swal.fire({
        title: "Hapus Penerima?",
        text: "Data master penerima akan dihapus.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        confirmButtonText: "Ya, Hapus!"
    }).then((res) => {
        if (res.isConfirmed) {
            recipientForm.delete(route("admin.outbound.recipients.destroy", id), {
                onSuccess: () => Swal.fire("Dihapus", "Penerima berhasil dihapus", "success")
            });
        }
    });
};

const years = computed(() => {
    const yearsSet = new Set();
    props.outbounds.forEach(ob => {
        if (ob.tanggal_keluar) {
            yearsSet.add(new Date(ob.tanggal_keluar).getFullYear());
        }
    });
    return Array.from(yearsSet).sort((a, b) => b - a);
});

// Form state
const form = useForm({
    recipient_id: "",
    save_as_new_recipient: false,
    nama_penerima: "",
    alamat_tujuan: "",
    kota_tujuan: "",
    telepon_penerima: "",
    keterangan_tujuan: "",
    
    delivery_type: "self",
    nama_driver: "",
    plat_nomor: "",
    phone_number: "",
    
    courier_provider: "",
    no_resi: "",
    
    tanggal_keluar: new Date().toISOString().substring(0, 10),
    catatan_pengiriman: "",
    supplementary_doc_path: "",
    items: [] // { id_inventory: "", qty: 1 }
});

// Watch recipient selection to auto-fill
import { watch } from 'vue';
watch(() => form.recipient_id, (newVal) => {
    if (newVal === 'NEW') {
        form.save_as_new_recipient = true;
        form.nama_penerima = "";
        form.alamat_tujuan = "";
        form.kota_tujuan = "";
        form.telepon_penerima = "";
        form.keterangan_tujuan = "";
    } else if (newVal) {
        form.save_as_new_recipient = false;
        const rec = props.recipients.find(r => String(r.id_recipient) === String(newVal));
        if (rec) {
            form.nama_penerima = rec.nama_penerima;
            form.alamat_tujuan = rec.alamat_tujuan;
            form.kota_tujuan = rec.kota_tujuan;
            form.telepon_penerima = rec.telepon_penerima;
            form.keterangan_tujuan = rec.keterangan_tujuan;
        }
    }
});

// Format Date to Local string
const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric"
    });
};

// Format URL helper to ensure absolute external links don't route to localhost
const formatPreviewUrl = (path) => {
    if (!path) return '#';
    const trimmed = path.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
        return trimmed;
    }
    if (trimmed.startsWith('/')) {
        return trimmed;
    }
    return `https://${trimmed}`;
};

// Filtered Outbounds
const filteredOutbounds = computed(() => {
    return props.outbounds.filter(ob => {
        const matchesSearch = 
            ob.no_outbound.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            ob.nama_penerima.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (ob.courier_provider && ob.courier_provider.toLowerCase().includes(searchQuery.value.toLowerCase()));
        
        const obYear = ob.tanggal_keluar ? new Date(ob.tanggal_keluar).getFullYear().toString() : "";
        const matchesYear = !selectedYear.value || obYear === selectedYear.value;

        return matchesSearch && matchesYear;
    });
});

// Add new item to outbound list inside the form
const addFormItem = () => {
    form.items.push({
        id_inventory: "",
        qty: 1
    });
};

// Remove item from outbound list inside the form
const removeFormItem = (index) => {
    form.items.splice(index, 1);
};

// Find inventory item details by id
const getInventoryDetails = (id_inventory) => {
    return props.inventoryStock.find(inv => inv.id_inventory === parseInt(id_inventory));
};

// Check if inventory selection is already selected in other rows
const isInventorySelected = (id_inventory, currentIndex) => {
    return form.items.some((item, index) => item.id_inventory === id_inventory && index !== currentIndex);
};

// Get list of inventories that can be selected for a specific row
const getAvailableInventories = (currentIndex) => {
    return props.inventoryStock.filter(inv => {
        // Either it is selected in the current row, or not selected in any other row
        return !form.items.some((item, index) => item.id_inventory === inv.id_inventory && index !== currentIndex);
    });
};

// Submit outbound form
const submitOutbound = () => {
    if (form.items.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Peringatan",
            text: "Harap masukkan minimal 1 barang untuk dikeluarkan."
        });
        return;
    }

    if (!form.recipient_id) {
        Swal.fire({
            icon: "warning",
            title: "Peringatan",
            text: "Harap pilih penerima."
        });
        return;
    }

    // Validation check for qty
    for (const item of form.items) {
        if (!item.id_inventory) {
            Swal.fire({
                icon: "warning",
                title: "Peringatan",
                text: "Harap pilih barang untuk setiap baris."
            });
            return;
        }
        const inv = getInventoryDetails(item.id_inventory);
        if (inv && item.qty > inv.qty) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: `Kuantitas untuk "${inv.nama_barang}" melebihi stok tersedia (${inv.qty} ${inv.satuan}).`
            });
            return;
        }
    }

    form.post(route("admin.outbound.store"), {
        onSuccess: () => {
            Swal.fire({
                icon: "success",
                title: "Berhasil",
                text: "Catatan Barang Keluar berhasil disimpan."
            });
            showAddModal.value = false;
            form.reset();
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).join("\n") || "Terjadi kesalahan saat menyimpan data.";
            Swal.fire({
                icon: "error",
                title: "Gagal Menyimpan",
                text: errorMsg
            });
        }
    });
};

// Open Detail View
const openDetail = (ob) => {
    selectedOutbound.value = ob;
    showDetailModal.value = true;
};

// Delete Outbound Record
const deleteOutbound = (id) => {
    Swal.fire({
        title: "Apakah Anda yakin?",
        text: "Data barang keluar ini akan dihapus permanen! Catatan: Penghapusan data ini tidak mengembalikan stok inventory yang telah dikurangi.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route("admin.outbound.destroy", id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: "success",
                        title: "Dihapus",
                        text: "Data barang keluar berhasil dihapus."
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Barang Keluar" />

    <AdminLayout>
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Barang Keluar</h1>
        </div>

        <!-- Section 1: Title & Action Button -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-700">Manajemen Barang Keluar</h2>
                <p class="text-sm text-gray-500">Catat pengeluaran barang dari gudang dengan data tujuan & pengiriman lengkap.</p>
            </div>
            <div class="flex gap-2">
                <button 
                    @click="openRecipientModal" 
                    class="inline-flex items-center px-4 py-2.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg font-semibold hover:bg-slate-200 shadow-sm transition-all duration-200"
                >
                    Master Penerima
                </button>
                <button 
                    @click="showAddModal = true" 
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 shadow-md transition-all duration-200 active:scale-95"
                >
                    <svg class="w-4.5 h-4.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Catat Barang Keluar
                </button>
            </div>
        </div>

        <!-- Section 2: Filters & Search -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <!-- Search Box -->
            <div class="relative w-full md:flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari No Outbound, Nama Penerima, atau Ekspedisi..."
                    class="w-full pl-11 pr-4 py-2.5 text-sm bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-gray-400 shadow-sm"
                />
            </div>

            <!-- Year Filter -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tahun:</span>
                <select
                    v-model="selectedYear"
                    class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl py-2 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold shadow-sm appearance-none"
                >
                    <option value="">Semua Tahun</option>
                    <option v-for="year in years" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Section 3: Data Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-400 uppercase text-xs font-bold border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-center w-16">No</th>
                            <th class="px-6 py-4">No Outbound</th>
                            <th class="px-6 py-4">Tujuan</th>
                            <th class="px-6 py-4">Tanggal Keluar</th>
                            <th class="px-6 py-4">Pengiriman / Driver</th>
                            <th class="px-6 py-4 text-center">Jumlah Barang</th>
                            <th class="px-6 py-4 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(ob, index) in filteredOutbounds" :key="ob.id_outbound" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-center text-gray-500 font-medium">{{ index + 1 }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ ob.no_outbound }}</td>
                            <td class="px-6 py-4 text-gray-700">
                                <div class="font-medium text-gray-900">{{ ob.nama_penerima }}</div>
                                <div class="text-xs text-gray-500 truncate max-w-[200px]">{{ ob.alamat_tujuan }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ formatDate(ob.tanggal_keluar) }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <div v-if="ob.delivery_type === 'courier'">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-indigo-700 text-xs font-semibold">📦 {{ ob.courier_provider || 'Kurir' }}</span>
                                    <div class="text-[10px] mt-1 text-gray-500">Resi: {{ ob.no_resi || '-' }}</div>
                                </div>
                                <div v-else-if="ob.delivery_type === 'self'">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700 text-xs font-semibold">🚛 Sendiri</span>
                                    <div class="text-[10px] mt-1 text-gray-500">{{ ob.nama_driver || '-' }} <span v-if="ob.plat_nomor">({{ ob.plat_nomor }})</span></div>
                                </div>
                                <div v-else>-</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-full text-xs font-bold border border-indigo-100">
                                    {{ ob.total_items }} Unit
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center flex justify-center items-center gap-2">
                                <button 
                                    @click="openDetail(ob)"
                                    class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"
                                    title="Detail Outbound"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button 
                                    @click="deleteOutbound(ob.id_outbound)"
                                    class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                    title="Hapus Outbound"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="filteredOutbounds.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-gray-900 font-medium">Tidak ada data outbound</h3>
                                    <p class="text-gray-500 text-sm">Catat data pengeluaran barang baru untuk menampilkannya di sini.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="p-4 bg-gray-50/50 border-t border-gray-100 flex justify-between items-center">
                <span class="text-xs font-medium text-gray-500">
                    Total: {{ filteredOutbounds.length }} data outbound ditemukan
                </span>
                <span class="text-xs text-gray-400 italic">
                    Data diperbarui secara realtime
                </span>
            </div>
        </div>

        <!-- MODAL: ADD OUTBOUND -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-5xl overflow-hidden flex flex-col" style="max-height: 90vh;">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Catat Outbound Baru</h3>
                        <p class="text-xs text-gray-500">Kurangi stok inventory dan catat tujuan pengiriman secara akurat.</p>
                    </div>
                    <button @click="showAddModal = false" class="text-gray-400 hover:text-red-500 text-2xl font-semibold">&times;</button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto flex-1 min-h-0 space-y-6">
                    <form @submit.prevent="submitOutbound" id="add-outbound-form" class="space-y-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column: Destination Details -->
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b pb-2">Detail Tujuan Pengiriman</h4>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Pilih Penerima <span class="text-red-500">*</span></label>
                                    <select 
                                        v-model="form.recipient_id"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        required
                                    >
                                        <option value="" disabled>-- Pilih dari Master Penerima --</option>
                                        <option value="NEW" class="font-bold text-indigo-600">➕ Tambah Penerima Baru</option>
                                        <option v-for="rec in recipients" :key="rec.id_recipient" :value="rec.id_recipient">
                                            {{ rec.nama_penerima }} - {{ rec.kota_tujuan || rec.alamat_tujuan }}
                                        </option>
                                    </select>
                                </div>

                                <template v-if="form.recipient_id">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Penerima <span class="text-red-500">*</span></label>
                                        <input 
                                            v-model="form.nama_penerima" 
                                            type="text" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                            required 
                                            :readonly="!form.save_as_new_recipient"
                                            :class="{'bg-gray-50 text-gray-500 cursor-not-allowed': !form.save_as_new_recipient}"
                                            placeholder="Nama Toko, Perusahaan, atau Orang..." 
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                                        <textarea 
                                            v-model="form.alamat_tujuan" 
                                            rows="2"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                            required 
                                            placeholder="Alamat lengkap tujuan..."
                                        ></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Kota Tujuan</label>
                                            <input 
                                                v-model="form.kota_tujuan" 
                                                type="text" 
                                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                                placeholder="Surabaya, Jakarta..." 
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">No. Telepon</label>
                                            <input 
                                                v-model="form.telepon_penerima" 
                                                type="text" 
                                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                                placeholder="081234..." 
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Keterangan Tambahan</label>
                                        <textarea 
                                            v-model="form.keterangan_tujuan" 
                                            rows="2"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                            placeholder="Keterangan drop point, instruksi khusus, dll..."
                                        ></textarea>
                                    </div>
                                    <p v-if="form.save_as_new_recipient" class="text-xs text-indigo-600 font-semibold mt-2">
                                        ℹ️ Data ini akan disimpan ke Master Data Penerima.
                                    </p>
                                </template>
                            </div>

                            <!-- Right Column: Shipping & Document Details -->
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b pb-2">Informasi & Dokumen Pengiriman</h4>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Keluar <span class="text-red-500">*</span></label>
                                    <input 
                                        v-model="form.tanggal_keluar" 
                                        type="date" 
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                        required 
                                    />
                                </div>

                                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg bg-gray-50">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="form.delivery_type" value="self" class="text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                        <span class="text-sm font-bold text-gray-700">🚛 Pengiriman Sendiri</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="form.delivery_type" value="courier" class="text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                        <span class="text-sm font-bold text-gray-700">📦 Pengiriman Kurir</span>
                                    </label>
                                </div>

                                <!-- Self Delivery Fields -->
                                <div v-if="form.delivery_type === 'self'" class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Driver</label>
                                        <input 
                                            v-model="form.nama_driver" 
                                            type="text" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                            placeholder="Nama Sopir..." 
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Plat Nomor</label>
                                        <input 
                                            v-model="form.plat_nomor" 
                                            type="text" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                            placeholder="L 1234 AB..." 
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Telepon</label>
                                        <input 
                                            v-model="form.phone_number" 
                                            type="text" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                            placeholder="081234..." 
                                        />
                                    </div>
                                </div>

                                <!-- Courier Delivery Fields -->
                                <div v-else-if="form.delivery_type === 'courier'" class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Penyedia Jasa Kurir</label>
                                        <input 
                                            v-model="form.courier_provider" 
                                            type="text" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                            placeholder="JNE, Pos, GoSend..." 
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Resi</label>
                                        <input 
                                            v-model="form.no_resi" 
                                            type="text" 
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                            placeholder="Nomor resi pengiriman..." 
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Dokumen Pelengkap (URL)</label>
                                    <input 
                                        type="text" 
                                        v-model="form.supplementary_doc_path"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                                        placeholder="URL Google Drive nota timbang / surat jalan..." 
                                    />
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan Pengiriman</label>
                                    <textarea 
                                        v-model="form.catatan_pengiriman" 
                                        rows="2"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                                        placeholder="Catatan tambahan (opsional)..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Warehouse Item Picker Section -->
                        <div class="space-y-4 pt-4 border-t">
                            <div class="flex justify-between items-center">
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pilih Barang yang Dikeluarkan</h4>
                                <button 
                                    type="button" 
                                    @click="addFormItem" 
                                    class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-800"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Baris Barang
                                </button>
                            </div>

                            <div class="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-gray-50 border-b border-gray-100">
                                        <tr>
                                            <th class="py-3 px-4 font-bold text-gray-400 w-12 text-center">No</th>
                                            <th class="py-3 px-4 font-bold text-gray-400">Pilih Barang dari Lokasi Gudang</th>
                                            <th class="py-3 px-4 font-bold text-gray-400 w-32 text-center">Stok Tersedia</th>
                                            <th class="py-3 px-4 font-bold text-gray-400 w-44">Kuantitas Keluar</th>
                                            <th class="py-3 px-4 font-bold text-gray-400 w-16 text-center">Hapus</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-gray-50/30 transition-colors">
                                            <td class="py-4 px-4 text-center font-semibold text-gray-400">{{ idx + 1 }}</td>
                                            <td class="py-4 px-4">
                                                <select 
                                                    v-model="item.id_inventory"
                                                    class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-xs rounded-lg py-2 px-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold"
                                                    required
                                                >
                                                    <option value="" disabled>-- Pilih Barang dari Gudang & Lokasi --</option>
                                                    <option 
                                                        v-for="inv in getAvailableInventories(idx)" 
                                                        :key="inv.id_inventory" 
                                                        :value="inv.id_inventory"
                                                    >
                                                        {{ inv.nama_barang }} [{{ inv.satuan }}] - {{ inv.nama_layout }} ({{ inv.kode_location }}) - Tersedia: {{ inv.qty }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="py-4 px-4 text-center font-semibold text-gray-700">
                                                <span v-if="item.id_inventory">
                                                    {{ getInventoryDetails(item.id_inventory)?.qty || 0 }} {{ getInventoryDetails(item.id_inventory)?.satuan }}
                                                </span>
                                                <span v-else class="text-gray-400">-</span>
                                            </td>
                                            <td class="py-4 px-4">
                                                <div class="flex items-center gap-2">
                                                    <input 
                                                        type="number" 
                                                        v-model="item.qty"
                                                        min="1"
                                                        :max="item.id_inventory ? (getInventoryDetails(item.id_inventory)?.qty || 1) : 99999"
                                                        class="w-24 text-center py-1.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-semibold text-xs"
                                                        required
                                                    />
                                                    <span class="text-xs text-gray-500" v-if="item.id_inventory">
                                                        {{ getInventoryDetails(item.id_inventory)?.satuan }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <button 
                                                    type="button" 
                                                    @click="removeFormItem(idx)"
                                                    class="text-rose-600 hover:text-rose-800 p-1.5 rounded-lg hover:bg-rose-50"
                                                >
                                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.items.length === 0">
                                            <td colspan="5" class="py-8 px-4 text-center text-gray-400 font-semibold italic">
                                                Belum ada barang dipilih. Klik "Tambah Baris Barang" untuk memulai.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button 
                        type="button" 
                        @click="showAddModal = false" 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition"
                    >
                        Batal
                    </button>
                    <button 
                        form="add-outbound-form"
                        type="submit" 
                        :disabled="form.processing"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold shadow-md transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Barang Keluar' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: MASTER PENERIMA -->
        <div v-if="showRecipientModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl overflow-hidden flex flex-col" style="max-height: 90vh;">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-800 text-lg">
                        {{ recipientModalMode === 'list' ? 'Master Data Penerima' : (recipientModalMode === 'create' ? 'Tambah Penerima Baru' : 'Edit Penerima') }}
                    </h3>
                    <button @click="showRecipientModal = false" class="text-gray-400 hover:text-red-500 text-2xl font-semibold">&times;</button>
                </div>

                <!-- Body List -->
                <div v-if="recipientModalMode === 'list'" class="p-6 overflow-y-auto flex-1">
                    <div class="mb-4 flex justify-end">
                        <button @click="openAddRecipient" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 shadow-sm transition">➕ Tambah Penerima</button>
                    </div>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 font-bold w-10 text-center">No</th>
                                    <th class="px-4 py-3 font-bold">Nama Penerima</th>
                                    <th class="px-4 py-3 font-bold">Kota/Telepon</th>
                                    <th class="px-4 py-3 font-bold text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(rec, idx) in recipients" :key="rec.id_recipient" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center text-gray-500">{{ idx + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-800">
                                        {{ rec.nama_penerima }}
                                        <div class="text-xs font-normal text-gray-500 truncate max-w-[200px]">{{ rec.alamat_tujuan }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 text-xs">
                                        {{ rec.kota_tujuan || '-' }}<br/>{{ rec.telepon_penerima || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center flex gap-2 justify-center">
                                        <button @click="openEditRecipient(rec)" class="text-indigo-600 hover:text-indigo-800">✎</button>
                                        <button @click="deleteRecipient(rec.id_recipient)" class="text-rose-600 hover:text-rose-800">🗑</button>
                                    </td>
                                </tr>
                                <tr v-if="recipients.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada data penerima</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Body Form (Create/Edit) -->
                <div v-else class="p-6 overflow-y-auto flex-1">
                    <form @submit.prevent="submitRecipient" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Penerima <span class="text-red-500">*</span></label>
                            <input v-model="recipientForm.nama_penerima" type="text" class="w-full border-gray-300 rounded-lg shadow-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                            <textarea v-model="recipientForm.alamat_tujuan" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm" required></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Kota Tujuan</label>
                                <input v-model="recipientForm.kota_tujuan" type="text" class="w-full border-gray-300 rounded-lg shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">No. Telepon</label>
                                <input v-model="recipientForm.telepon_penerima" type="text" class="w-full border-gray-300 rounded-lg shadow-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Keterangan (Opsional)</label>
                            <textarea v-model="recipientForm.keterangan_tujuan" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 mt-4 pt-4 border-t">
                            <button type="button" @click="recipientModalMode = 'list'" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition">Batal</button>
                            <button type="submit" :disabled="recipientForm.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold shadow-md transition disabled:opacity-50">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: DETAIL VIEW -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl overflow-hidden flex flex-col" style="max-height: 90vh;">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Detail Outbound: {{ selectedOutbound?.no_outbound }}</h3>
                        <p class="text-xs text-gray-500">Catatan pengeluaran barang tanggal {{ formatDate(selectedOutbound?.tanggal_keluar) }}</p>
                    </div>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-red-500 text-2xl font-semibold">&times;</button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto flex-1 min-h-0 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Left Details Card -->
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 space-y-4">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b pb-2">Informasi Penerima & Tujuan</h4>
                            
                            <div class="grid grid-cols-3 gap-2 text-sm">
                                <div class="text-gray-500 font-medium">Nama Penerima</div>
                                <div class="col-span-2 text-gray-800 font-bold">: {{ selectedOutbound?.nama_penerima }}</div>

                                <div class="text-gray-500 font-medium">Alamat Lengkap</div>
                                <div class="col-span-2 text-gray-800 font-semibold">: {{ selectedOutbound?.alamat_tujuan }}</div>

                                <div class="text-gray-500 font-medium">Kota Tujuan</div>
                                <div class="col-span-2 text-gray-800 font-semibold">: {{ selectedOutbound?.kota_tujuan || '-' }}</div>

                                <div class="text-gray-500 font-medium">No. Telepon</div>
                                <div class="col-span-2 text-gray-800 font-semibold">: {{ selectedOutbound?.telepon_penerima || '-' }}</div>

                                <div class="text-gray-500 font-medium">Keterangan</div>
                                <div class="col-span-2 text-gray-800 italic">: {{ selectedOutbound?.keterangan_tujuan || '-' }}</div>
                            </div>
                        </div>

                        <!-- Right Details Card -->
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 space-y-4">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b pb-2">Informasi Pengiriman</h4>
                            
                            <div class="grid grid-cols-3 gap-2 text-sm">
                                <div class="text-gray-500 font-medium">Tipe Pengiriman</div>
                                <div class="col-span-2 text-gray-800 font-bold">: 
                                    <span v-if="selectedOutbound?.delivery_type === 'self'" class="text-emerald-600">🚛 Sendiri</span>
                                    <span v-else-if="selectedOutbound?.delivery_type === 'courier'" class="text-indigo-600">📦 Kurir</span>
                                    <span v-else>-</span>
                                </div>

                                <template v-if="selectedOutbound?.delivery_type === 'self'">
                                    <div class="text-gray-500 font-medium">Nama Driver</div>
                                    <div class="col-span-2 text-gray-800 font-semibold">: {{ selectedOutbound?.nama_driver || '-' }}</div>
                                    <div class="text-gray-500 font-medium">Plat Nomor</div>
                                    <div class="col-span-2 text-gray-800 font-semibold">: {{ selectedOutbound?.plat_nomor || '-' }}</div>
                                    <div class="text-gray-500 font-medium">No. Telepon</div>
                                    <div class="col-span-2 text-gray-800 font-semibold">: {{ selectedOutbound?.phone_number || '-' }}</div>
                                </template>
                                <template v-else-if="selectedOutbound?.delivery_type === 'courier'">
                                    <div class="text-gray-500 font-medium">Penyedia Kurir</div>
                                    <div class="col-span-2 text-gray-800 font-bold">: {{ selectedOutbound?.courier_provider || '-' }}</div>
                                    <div class="text-gray-500 font-medium">Nomor Resi</div>
                                    <div class="col-span-2 text-gray-800 font-semibold">: {{ selectedOutbound?.no_resi || '-' }}</div>
                                </template>

                                <div class="text-gray-500 font-medium mt-2">Catatan Pengiriman</div>
                                <div class="col-span-2 text-gray-800 italic mt-2">: {{ selectedOutbound?.catatan_pengiriman || '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Lampiran Dokumen Section -->
                    <div class="bg-slate-50 p-5 rounded-xl border border-slate-100 space-y-4">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b pb-2">Lampiran Dokumen</h4>
                        <div class="flex gap-4">
                            <div class="flex-1 p-3 border rounded-lg bg-white flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-semibold text-gray-700">Dokumen Pelengkap (URL)</div>
                                    <div class="text-xs text-gray-400">{{ selectedOutbound?.supplementary_doc_path ? 'Dokumen tersedia' : 'Tidak ada dokumen' }}</div>
                                </div>
                                <a 
                                    v-if="selectedOutbound?.supplementary_doc_path"
                                    :href="formatPreviewUrl(selectedOutbound?.supplementary_doc_path)" 
                                    target="_blank"
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center"
                                >
                                    Lihat File
                                    <svg class="w-4.5 h-4.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Items Detail Table -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest border-b pb-2">Detail Barang</h4>
                        <div class="border border-gray-100 rounded-lg overflow-hidden shadow-sm">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr>
                                        <th class="py-3 px-4 font-bold text-gray-400 w-12 text-center">No</th>
                                        <th class="py-3 px-4 font-bold text-gray-400">Nama Barang</th>
                                        <th class="py-3 px-4 font-bold text-gray-400">Lokasi / Rack</th>
                                        <th class="py-3 px-4 font-bold text-gray-400 text-center">Kuantitas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="(item, idx) in selectedOutbound?.items" :key="idx" class="hover:bg-gray-50/30 transition-colors">
                                        <td class="py-3 px-4 text-center font-semibold text-gray-400">{{ idx + 1 }}</td>
                                        <td class="py-3 px-4 font-bold text-gray-800">{{ item.nama_barang }}</td>
                                        <td class="py-3 px-4 font-medium text-gray-600">{{ item.lokasi }}</td>
                                        <td class="py-3 px-4 text-center font-bold text-indigo-700 text-sm">{{ item.qty }} Unit</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button 
                        type="button" 
                        @click="showDetailModal = false" 
                        class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold transition shadow-md"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>
input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #4f46e5;
}
</style>
