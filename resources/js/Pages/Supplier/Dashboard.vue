<script setup>
import { Head, Link } from "@inertiajs/vue3";
import SupplierLayout from "../../Layouts/SupplierLayout.vue";

const props = defineProps({
    stats: { type: Object, required: true },
    logs: { type: Array, required: true },
    supplierName: { type: String, required: true }
});

// Format date to local Indonesian time
const formatTime = (timeString) => {
    if (!timeString) return "-";
    const date = new Date(timeString);
    return date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
    }) + " WIB";
};

// Activity styling mapper based on status/type
const getLogStyles = (status) => {
    switch (status) {
        case "success":
            return {
                bg: "bg-emerald-50",
                text: "text-emerald-700",
                border: "border-emerald-200",
                dot: "bg-emerald-500",
                ring: "ring-emerald-100"
            };
        case "warning":
            return {
                bg: "bg-amber-50",
                text: "text-amber-700",
                border: "border-amber-200",
                dot: "bg-amber-500",
                ring: "ring-amber-100"
            };
        case "danger":
            return {
                bg: "bg-rose-50",
                text: "text-rose-700",
                border: "border-rose-200",
                dot: "bg-rose-500",
                ring: "ring-rose-100"
            };
        case "info":
        default:
            return {
                bg: "bg-blue-50",
                text: "text-blue-700",
                border: "border-blue-200",
                dot: "bg-blue-500",
                ring: "ring-blue-100"
            };
    }
};

// Icon mapper for activity types
const getLogIcon = (type) => {
    switch (type) {
        case "profile":
            return `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>`;
        case "selection":
            return `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
        case "classification":
            return `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>`;
        case "po":
            return `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>`;
        default:
            return `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
    }
};
</script>

<template>
    <Head title="Supplier Dashboard | WISE" />

    <SupplierLayout>
        <!-- Page Header -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Dashboard Supplier</h1>
                <p class="text-sm text-gray-500 mt-1">Selamat datang kembali, <span class="font-semibold text-indigo-600">{{ supplierName }}</span>. Berikut ringkasan aktivitas dan status kemitraan Anda.</p>
            </div>
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-2 flex items-center gap-3">
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-500"></span>
                </span>
                <span class="text-xs font-semibold text-indigo-700">Kemitraan Aktif</span>
            </div>
        </div>

        <!-- Section 1: Kemitraan & Status Tahapan -->
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Status & Progress Kemitraan</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Profil Perusahaan -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4 hover:shadow-md transition-shadow">
                <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Profil Perusahaan</p>
                    <p class="text-base font-bold text-gray-800 mt-1">{{ stats.profileStatus }}</p>
                    <Link :href="route('supplier.data')" class="text-xs text-indigo-600 font-semibold hover:underline mt-2 inline-block">
                        Lihat / Edit Profil &rarr;
                    </Link>
                </div>
            </div>

            <!-- Hasil Seleksi -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4 hover:shadow-md transition-shadow">
                <div class="p-3 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Evaluasi Seleksi</p>
                    <p class="text-base font-bold text-gray-800 mt-1">
                        <span :class="{
                            'text-emerald-600': stats.selectionStatus === 'Lolos',
                            'text-rose-600': stats.selectionStatus === 'Tidak Lolos',
                            'text-amber-500': stats.selectionStatus === 'Menunggu Validasi',
                        }">
                            {{ stats.selectionStatus }}
                        </span>
                    </p>
                    <Link :href="route('supplier.selection')" class="text-xs text-indigo-600 font-semibold hover:underline mt-2 inline-block">
                        Kuesioner Seleksi &rarr;
                    </Link>
                </div>
            </div>

            <!-- Klasifikasi Supplier -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4 hover:shadow-md transition-shadow">
                <div class="p-3 rounded-lg bg-amber-50 text-amber-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Klasifikasi Kelas</p>
                    <p class="text-base font-bold text-gray-800 mt-1">
                        <span :class="{
                            'text-indigo-600': stats.classificationStatus.startsWith('Class'),
                            'text-amber-500': stats.classificationStatus === 'Pending' || stats.classificationStatus === 'Diproses',
                        }">
                            {{ stats.classificationStatus }}
                        </span>
                    </p>
                    <Link :href="route('supplier.classification')" class="text-xs text-indigo-600 font-semibold hover:underline mt-2 inline-block">
                        Kuesioner Klasifikasi &rarr;
                    </Link>
                </div>
            </div>
        </div>

        <!-- Section 2: PO Statistics & Activity Log -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left 1 Column: PO Statistics -->
            <div class="space-y-6">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Statistik Order</h2>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-50">
                    <div class="p-6">
                        <div class="text-gray-400 text-xs font-bold uppercase">Total Order (PO)</div>
                        <div class="text-3xl font-bold text-gray-800 mt-2">{{ stats.totalPo }}</div>
                        <div class="text-xs text-gray-400 mt-1">Seluruh PO yang pernah diterima</div>
                    </div>
                    
                    <div class="p-6">
                        <div class="text-indigo-600 text-xs font-bold uppercase">PO Sedang Berjalan</div>
                        <div class="text-3xl font-bold text-indigo-600 mt-2">{{ stats.activePo }}</div>
                        <div class="text-xs text-gray-400 mt-1">Menunggu penawaran, dokumen, atau pengiriman</div>
                    </div>

                    <div class="p-6">
                        <div class="text-emerald-600 text-xs font-bold uppercase">PO Sukses Diselesaikan</div>
                        <div class="text-3xl font-bold text-emerald-600 mt-2">{{ stats.completedPo }}</div>
                        <div class="text-xs text-gray-400 mt-1">Barang telah sukses diterima di gudang</div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="bg-slate-900 text-white rounded-xl shadow-md p-6 relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none transform translate-y-4 translate-x-4">
                        <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-base mb-2">Butuh Bantuan?</h3>
                    <p class="text-xs text-slate-300 mb-4 leading-relaxed">Hubungi admin gudang utama jika terjadi kendala pengiriman atau terdapat penawaran harga PO yang salah.</p>
                    <Link :href="route('supplier.purchase-orders.index')" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all">
                        Kelola Purchase Orders &rarr;
                    </Link>
                </div>
            </div>

            <!-- Right 2 Columns: Activity Log -->
            <div class="lg:col-span-2 space-y-6">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Log Aktivitas Terbaru</h2>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[400px]">
                    <!-- Empty State -->
                    <div v-if="logs.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm">Belum ada aktivitas</h3>
                        <p class="text-xs text-gray-500 mt-1">Semua tindakan Anda seperti pengisian formulir atau pengiriman PO akan dicatat di sini.</p>
                    </div>

                    <!-- Timeline Feed -->
                    <div v-else class="flow-root">
                        <ul role="list" class="-mb-8">
                            <li v-for="(log, logIdx) in logs" :key="logIdx">
                                <div class="relative pb-8">
                                    <!-- Timeline line connection -->
                                    <span v-if="logIdx !== logs.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    
                                    <div class="relative flex space-x-3">
                                        <!-- Icon container -->
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white" :class="[getLogStyles(log.status).bg, getLogStyles(log.status).text]">
                                                <span v-html="getLogIcon(log.type)"></span>
                                            </span>
                                        </div>
                                        
                                        <!-- Log Text Details -->
                                        <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm font-bold text-gray-800">{{ log.title }}</p>
                                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ log.description }}</p>
                                            </div>
                                            <div class="text-right text-xs whitespace-nowrap text-gray-400 font-semibold">
                                                <time>{{ formatTime(log.time) }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </SupplierLayout>
</template>
