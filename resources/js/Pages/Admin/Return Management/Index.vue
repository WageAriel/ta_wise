<script setup>
import { ref, computed } from 'vue';
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
    let data = props.returns.length > 0 ? props.returns : [];

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

const handleAddReturn = () => {
    // router.get(route('admin.returns.create'));
    console.log('Add Return Clicked');
};
</script>

<template>
    <Head title="Return Management | Admin" />

    <AdminLayout>
        <!-- Header Page -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">Return Management</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola data pengembalian barang dari gudang ke supplier.</p>
        </div>

        <!-- Action Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Return Management</h2>
                <p class="text-sm text-slate-400 font-medium">Daftar Transaksi Return</p>
            </div>
            <button 
                @click="handleAddReturn"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-red-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-red-700 transition-all active:scale-95 shadow-lg shadow-red-100"
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
                        class="w-5 h-5 text-slate-400"
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
                        class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 shadow-sm"
                />
            </div>

            <!-- Right Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span
                        class="text-sm font-medium text-slate-400"
                        >Tampilkan</span
                    >
                    <select
                        v-model="perPage"
                        class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm"
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
                        class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm"
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
        <div class="bg-white rounded-[32px] border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-16">No</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID Return</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID Inbound</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Tanggal Return</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Jumlah Item</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Notes</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="(item, idx) in displayReturns" :key="idx" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-6 text-center text-xs font-bold text-slate-400">{{ idx + 1 }}</td>
                            <td class="py-4 px-6">
                                <span class="text-xs font-black text-slate-900 leading-none">RET-{{ item.id_return }}</span>
                            </td>
                            <td class="py-4 px-6 text-xs font-bold text-slate-600">INB-{{ item.id_inbound }}</td>
                            <td class="py-4 px-6 text-center text-xs font-bold text-slate-500">{{ item.tanggal_return }}</td>
                            <td class="py-4 px-6 text-center">
                                <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-lg text-[10px] font-black shadow-sm border border-indigo-100">
                                    {{ item.jumlah_item }} Items
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 font-medium italic">
                                {{ item.notes || '-' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <button class="p-2 bg-slate-50 text-slate-400 rounded-xl hover:bg-indigo-50 hover:text-indigo-600 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Empty State -->
                        <tr v-if="displayReturns.length === 0">
                            <td colspan="7" class="py-24 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-[28px] flex items-center justify-center mb-6 border border-slate-100">
                                        <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">No Return Data Found</h3>
                                    <p class="text-[10px] text-slate-400 mt-2 uppercase font-bold tracking-tighter">Silakan gunakan button di atas untuk menambahkan data retur baru.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Simple Pagination Footer -->
            <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Showing 0 of 0 Records</p>
                <div class="flex gap-2">
                    <button class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-[10px] font-black text-slate-400 uppercase tracking-widest opacity-50 cursor-not-allowed transition-all">Previous</button>
                    <button class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-[10px] font-black text-slate-400 uppercase tracking-widest opacity-50 cursor-not-allowed transition-all">Next</button>
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