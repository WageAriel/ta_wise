<script setup>
import SupplierLayout from "@/Layouts/SupplierLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    is_approved: Boolean,
    has_submitted_this_year: Boolean,
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            lolos: 0,
            review: 0,
            tidak_lolos: 0,
        }),
    },
    applications: {
        type: Array,
        default: () => [],
    },
});

const getStatusBadgeClass = (status) => {
  const s = status?.toLowerCase();
  if (s === 'lolos') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
  if (s === 'tidak lolos') return 'bg-rose-100 text-rose-700 border-rose-200';
  if (s === 'menunggu validasi') return 'bg-amber-100 text-amber-700 border-amber-200'; // Tambahkan ini
  return 'bg-gray-100 text-gray-700 border-gray-200';
};
</script>

<template>
    <Head title="Seleksi Supplier | WISE" />

    <SupplierLayout>
        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header Halaman -->
            <div class="flex items-center space-x-2 text-sm text-gray-500">
                <span class="hover:text-indigo-600 transition-colors cursor-default">Seleksi Supplier</span>
            </div>

            <!-- Div Seleksi Supplier & Button Tambah -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Seleksi Supplier</h1>
                    <p class="text-gray-500 text-sm mt-1">Kelola dan pantau status pengajuan seleksi Anda</p>
                </div>
                <div v-if="props.is_approved">
                    <!-- BUTTON TAMBAH (JIKA BELUM SUBMIT) -->
                    <Link
                        v-if="!props.has_submitted_this_year"
                        :href="route('supplier.selection.create')"
                        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 transition shadow-md"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Pengajuan Seleksi
                    </Link>

                    <!-- BUTTON TERKUNCI (JIKA SUDAH SUBMIT) -->
                    <div
                        v-else
                        class="inline-flex items-center px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg font-semibold text-[10px] text-gray-400 uppercase tracking-widest cursor-not-allowed"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Anda sudah melakukan pengajuan
                    </div>
                </div>
                <!-- PESAN JIKA BELUM APPROVED -->
                <div v-else class="flex items-center space-x-2 bg-amber-50 border border-amber-200 text-amber-700 px-4 py-2.5 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-xs font-semibold">Fitur pengajuan terkunci. Harap lengkapi & pastikan Data Perusahaan lolos verifikasi.</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Pengajuan -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 transition-all hover:shadow-md">
                    <div class="flex items-center text-blue-600 mb-2">
                        <div class="p-2 rounded-lg bg-blue-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Pengajuan</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                    </div>
                </div>

                <!-- Lolos -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 transition-all hover:shadow-md">
                    <div class="flex items-center text-green-600 mb-2">
                        <div class="p-2 rounded-lg bg-green-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Lolos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.lolos }}</p>
                    </div>
                </div>

                <!-- Menunggu Review -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 transition-all hover:shadow-md">
                    <div class="flex items-center text-amber-600 mb-2">
                        <div class="p-2 rounded-lg bg-amber-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Menunggu Review</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.review }}</p>
                    </div>
                </div>

                <!-- Tidak Lolos -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 transition-all hover:shadow-md">
                    <div class="flex items-center text-red-600 mb-2">
                        <div class="p-2 rounded-lg bg-red-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Tidak Lolos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.tidak_lolos }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabel Pengajuan -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Supplier</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal Pengajuan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Surat Penetapan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="(app, index) in applications" :key="index" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ app.supplier_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ app.date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[getStatusBadgeClass(app.status), 'px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm']">
                                        {{ app.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button
                                        v-if="app.status?.toLowerCase() === 'lolos'"
                                        class="inline-flex items-center px-4 py-1.5 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-lg text-xs font-bold hover:bg-indigo-100 transition shadow-sm"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Download PDF
                                    </button>
                                    <span v-else class="text-xs text-gray-400 italic font-medium">Belum tersedia</span>
                                </td>
                            </tr>
                            <!-- Empty State -->
                            <tr v-if="applications.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center border-b border-gray-100">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="p-3 rounded-full bg-gray-50 mb-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-500 font-medium">Data tidak ditemukan</p>
                                        <p class="text-xs text-gray-400 mt-1">Belum ada riwayat pengajuan seleksi supplier.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SupplierLayout>
</template>