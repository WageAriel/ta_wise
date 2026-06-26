<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3"; // Tambahkan router & usePage
import axios from "axios";
import Swal from "sweetalert2";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    selections: { type: Object, default: () => ({ data: [] }) },
    years: { type: Array, default: () => [] },
});

// State
const selections = ref(props.selections?.data || []);
const pagination = ref(props.selections || { data: [] });
const years = ref(props.years || []);
const isLoading = ref(false);
const searchQuery = ref("");
const selectedYear = ref("");
const perPage = ref(10);
const activeTab = ref('pending'); // 'pending' = Status Menunggu Review, 'validated' = Lolos/Tidak Lolos
const currentPage = ref(1);

// Ambil skor minimum dari Pengaturan Umum
const minimalSkorLulus = computed(() => {
    return usePage().props.app_settings?.minimal_skor_lulus ?? 70;
});

// Logic Fetch Data
const fetchData = async (page = 1) => {
    isLoading.value = true;
    currentPage.value = page;
    try {
        const response = await axios.get('/api/admin/seleksi', {
            params: {
                page: page,
                search: searchQuery.value,
                tahun: selectedYear.value,
                status: activeTab.value,
                per_page: perPage.value
            }
        });
        selections.value = response.data.data;
        pagination.value = response.data;
    } catch (error) {
        console.error("Gagal mengambil data:", error);
    } finally {
        isLoading.value = false;
    }
};

// Watcher untuk Filter & Search agar otomatis fetch
watch([searchQuery, selectedYear, activeTab, perPage], () => {
    fetchData(1);
});

onMounted(() => {
    // Sync initial data if needed
    if (props.selections.total === 0 && !searchQuery.value) {
        fetchData();
    }
});

// Aksi Buttons
const handleExport = () => {
    window.location.href = `/admin/supplier/selection/export?year=${selectedYear.value}`;
};

const showModal = ref(false);
const showValidationModal = ref(false); // Modal baru khusus validasi
const detailData = ref(null);
const isProcessing = ref(false);

const handleView = async (id) => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/admin/supplier/selection/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        detailData.value = response.data;
        showModal.value = true;
    } catch (error) {
        console.error("Gagal mengambil detail:", error);
    } finally {
        isLoading.value = false;
    }
};

const handleValidation = async (id) => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/admin/supplier/selection/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
        detailData.value = response.data;
        showValidationModal.value = true;
    } catch (error) {
        console.error("Gagal mengambil detail validasi:", error);
    } finally {
        isLoading.value = false;
    }
};

const updateStatus = async (id, newStatus) => {
    const isApprove = newStatus === 'Lolos';
    const result = await Swal.fire({
        title: 'Konfirmasi Validasi',
        text: `Apakah Anda yakin ingin menyatakan supplier ini ${newStatus.toUpperCase()}?`,
        icon: isApprove ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isApprove ? '#059669' : '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal',
        reverseButtons: true
    });

    if (result.isConfirmed) {
        isProcessing.value = true;
        try {
            await axios.post(`/api/admin/seleksi/${id}/status`, {
                status: newStatus
            });
            showModal.value = false;
            showValidationModal.value = false;
            
            Swal.fire({
                title: 'Berhasil!',
                text: `Supplier berhasil dinyatakan ${newStatus.toUpperCase()}.`,
                icon: 'success',
                confirmButtonColor: '#059669',
            });
            
            fetchData(currentPage.value); // Refresh data table
        } catch (error) {
            console.error("Gagal update status:", error);
            Swal.fire({
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat memproses data.',
                icon: 'error',
                confirmButtonColor: '#e11d48',
            });
        } finally {
            isProcessing.value = false;
        }
    }
};

const handleDelete = async (id) => {
    const result = await Swal.fire({
        title: 'Hapus Data Seleksi?',
        text: "Data seleksi supplier yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true
    });

    if (result.isConfirmed) {
        isLoading.value = true;
        try {
            await axios.delete(`/admin/supplier/selection/${id}`);
            
            Swal.fire({
                title: 'Terhapus!',
                text: 'Data seleksi supplier berhasil dihapus.',
                icon: 'success',
                confirmButtonColor: '#059669',
            });
            
            fetchData(currentPage.value); // Refresh data
        } catch (error) {
            console.error("Gagal menghapus data:", error);
            Swal.fire({
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat menghapus data.',
                icon: 'error',
                confirmButtonColor: '#e11d48',
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Hitung Rekomendasi berdasarkan pengaturan umum
const recommendation = computed(() => {
    if (!detailData.value) return null;
    return detailData.value.total_nilai >= minimalSkorLulus.value ? 'Lolos' : 'Tidak Lolos';
});

// Hitung poin dari jawaban verifikasi petugas
const paginationLinks = computed(() => {
    if (!pagination.value.links) return [];
    return pagination.value.links
        .filter(l => !l.label.includes('Previous') && !l.label.includes('Next'))
        .map(l => ({
            ...l,
            page: l.url ? parseInt(new URL(l.url).searchParams.get('page')) : null
        }));
});

const getStatusBadge = (status) => {
    switch (status) {
        case 'lolos': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'tidak_lolos': return 'bg-rose-100 text-rose-700 border-rose-200';
        case 'process': return 'bg-blue-100 text-blue-700 border-blue-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
};
</script>

<template>
    <Head title="Seleksi Supplier | Admin WISE" />

    <AdminLayout>
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Seleksi Supplier</h1>
        </div>

        <!-- Action Bar: Export/Import -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-700">Seleksi Supplier</h2>
                <p class="text-sm text-gray-500">Kelola dan tinjau hasil seleksi kapasitas supplier.</p>
            </div>
            <div class="flex items-center gap-3">
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
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 pointer-events-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input v-model="searchQuery" type="text" placeholder="Cari nama supplier..." class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-gray-400 shadow-sm" />
            </div>

            <!-- Select Filters -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-400">Tampilkan</span>
                    <select v-model="perPage" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm">
                        <option :value="10">10 Baris</option>
                        <option :value="25">25 Baris</option>
                        <option :value="50">50 Baris</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <select v-model="selectedYear" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm">
                        <option value="">Semua Tahun</option>
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Status Tab Filter -->
        <div class="mb-6 p-1.5 bg-gray-100 inline-flex rounded-lg border border-gray-200">
            <button @click="activeTab = 'pending'" 
                :class="activeTab === 'pending' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-6 py-2.5 text-xs font-semibold rounded-lg transition-all">
                Menunggu Validasi
            </button>
            <button @click="activeTab = 'validated'" 
                :class="activeTab === 'validated' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-6 py-2.5 text-xs font-semibold rounded-lg transition-all">
                Tervalidasi
            </button>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100">
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center w-16">No</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase">Nama Supplier</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center">Tahun</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center">Status</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center">Tgl Pengajuan</th>
                            <th class="py-5 px-6 text-xs font-bold text-gray-400 uppercase text-center w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(item, idx) in selections" :key="item.id_seleksi" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="py-4 px-6 text-center text-xs font-bold text-gray-500">{{ (pagination.from || 0) + idx }}</td>
                            <td class="py-4 px-6 font-bold text-gray-900 text-xs">{{ item.supplier?.nama_perusahaan || '-' }}</td>
                            <td class="py-4 px-6 text-center text-xs font-bold text-gray-600">{{ item.tanggal ? new Date(item.tanggal).getFullYear() : '-' }}</td>
                            <td class="py-4 px-6 text-center">
                                <span :class="getStatusBadge(item.status)" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border shadow-sm">
                                    {{ item.status === 'process' ? 'Menunggu Review' : (item.status === 'lolos' ? 'Lolos' : 'Tidak Lolos') }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-xs font-bold text-gray-500">{{ formatDate(item.tanggal) }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Jika Tab Menunggu Validasi -->
                                    <template v-if="activeTab === 'pending'">
                                        <button @click="handleValidation(item.id_seleksi)" 
                                            class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 active:scale-95 transition-all shadow-sm text-[9px] font-bold uppercase tracking-wider flex items-center gap-1 whitespace-nowrap">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Validasi Sekarang
                                        </button>
                                    </template>

                                    <!-- Jika Tab Tervalidasi -->
                                    <template v-else>
                                        <button @click="handleView(item.id_seleksi)" class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm group/btn">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button @click="handleDelete(item.id_seleksi)" class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1 v3M4 7h16"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-if="selections.length === 0">
                            <td colspan="6" class="py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <h3 class="text-xs font-black text-gray-700 uppercase tracking-widest">Data Tidak Ditemukan</h3>
                                    <p class="text-xs text-gray-400 mt-1 uppercase font-bold tracking-tighter">Silakan gunakan filter atau kata kunci pencarian lain.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Toolbar -->
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="text-xs font-bold text-gray-400">
                    Menampilkan <span class="font-bold text-gray-600">{{ pagination.from || 0 }} - {{ pagination.to || 0 }}</span> dari 
                    <span class="font-bold text-gray-600">{{ pagination.total || 0 }}</span> Data
                </div>
                <div class="flex items-center gap-1">
                    <button 
                        @click="fetchData(pagination.current_page - 1)"
                        :disabled="!pagination.prev_page_url"
                        class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    
                    <button 
                        v-for="link in paginationLinks" 
                        :key="link.label"
                        @click="link.page ? fetchData(link.page) : null"
                        :disabled="!link.page"
                        :class="link.active 
                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-100' 
                            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 disabled:opacity-40'"
                        class="min-w-[36px] h-9 px-2 rounded-lg border text-xs font-black transition-all"
                    >
                        <span v-html="link.label"></span>
                    </button>

                    <button 
                        @click="fetchData(pagination.current_page + 1)"
                        :disabled="!pagination.next_page_url"
                        class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>

    <!-- Modal Baru: Detail Pengajuan & Validasi -->
    <div v-if="showValidationModal" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showValidationModal = false"></div>

        <div class="bg-white rounded-3xl overflow-hidden shadow-2xl transform transition-all sm:max-w-xl w-full border border-gray-100">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-black text-gray-800 uppercase tracking-tight">Validasi Pengajuan Seleksi</h3>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="flex items-center text-[10px] font-bold text-gray-500 bg-white px-2 py-0.5 rounded-full border border-gray-200">
                            <svg class="w-2.5 h-2.5 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.827a1 1 0 00-.788 0L2.606 6A1 1 0 001 6.91v9.18a1 1 0 00.553.894l8 4a1 1 0 00.894 0l8-4A1 1 0 0019 16.09V6.91a1 1 0 00-.606-.883l-8-3.127z"/></svg>
                            {{ detailData?.supplier?.nama_perusahaan }}
                        </span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ formatDate(detailData?.tanggal) }}</span>
                    </div>
                </div>
                <button @click="showValidationModal = false" class="p-1.5 hover:bg-gray-200 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 max-h-[50vh] overflow-y-auto bg-white">
                <div class="space-y-4">
                    <div v-for="(jwb, index) in detailData?.jawaban" :key="index" class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 flex-shrink-0 bg-white border border-gray-200 rounded-lg flex items-center justify-center text-[10px] font-black text-gray-400">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-bold text-gray-800 leading-relaxed">{{ jwb.pertanyaan?.teks_pertanyaan }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-[10px] text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded-md">Jawaban: {{ jwb.opsi?.teks_opsi }}</span>
                                    <span class="text-[10px] font-black text-gray-500">POIN: <span :class="jwb.opsi?.nilai >= 3 ? 'text-emerald-500' : 'text-rose-500'">{{ jwb.opsi?.nilai }}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-gray-200 shadow-sm mb-4">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Nilai Supplier</p>
                        <p class="text-2xl font-black text-gray-800">{{ detailData?.total_nilai }} <span class="text-xs text-gray-400 font-bold">/ 100</span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Rekomendasi Sistem</p>
                        <span :class="recommendation === 'Lolos' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-rose-600 bg-rose-50 border-rose-100'" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border">
                            {{ recommendation }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mt-4">
                    <button @click="updateStatus(detailData.id_seleksi, 'Tidak Lolos')" :disabled="isProcessing"
                            class="py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest border-2 border-rose-100 text-rose-600 hover:bg-rose-600 hover:text-white transition-all active:scale-[0.98]">
                        Tidak Lolos
                    </button>
                    <button @click="updateStatus(detailData.id_seleksi, 'Lolos')" :disabled="isProcessing"
                            class="py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all active:scale-[0.98]">
                        Lolos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Review -->
        <div v-if="showModal" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

            <div class="bg-white rounded-3xl overflow-hidden shadow-2xl transform transition-all sm:max-w-xl w-full border border-gray-100">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-gray-800 uppercase tracking-tight">Detail Review Seleksi</h3>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="flex items-center text-[10px] font-bold text-gray-500 bg-white px-2 py-0.5 rounded-full border border-gray-200">
                                <svg class="w-2.5 h-2.5 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.827a1 1 0 00-.788 0L2.606 6A1 1 0 001 6.91v9.18a1 1 0 00.553.894l8 4a1 1 0 00.894 0l8-4A1 1 0 0019 16.09V6.91a1 1 0 00-.606-.883l-8-3.127z"/></svg>
                                {{ detailData?.supplier?.nama_perusahaan }}
                            </span>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ formatDate(detailData?.tanggal) }}</span>
                        </div>
                    </div>
                    <button @click="showModal = false" class="p-1.5 hover:bg-gray-200 rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-4 max-h-[50vh] overflow-y-auto bg-white">
                    <div class="space-y-4">
                        <div v-for="(jwb, index) in detailData?.jawaban" :key="index" class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 flex-shrink-0 bg-white border border-gray-200 rounded-lg flex items-center justify-center text-[10px] font-black text-gray-400">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-gray-800 leading-relaxed">{{ jwb.pertanyaan?.teks_pertanyaan }}</p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="text-[10px] text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded-md">Jawaban: {{ jwb.opsi?.teks_opsi }}</span>
                                        <span class="text-[10px] font-black text-gray-500">POIN: <span :class="jwb.opsi?.nilai >= 3 ? 'text-emerald-500' : 'text-rose-500'">{{ jwb.opsi?.nilai }}</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-gray-200 shadow-sm mb-4">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Nilai Supplier</p>
                            <p class="text-2xl font-black text-gray-800">{{ detailData?.total_nilai }} <span class="text-xs text-gray-400 font-bold">/ 100</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Rekomendasi Sistem</p>
                            <span :class="recommendation === 'Lolos' ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-rose-600 bg-rose-50 border-rose-100'" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border">
                                {{ recommendation }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


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