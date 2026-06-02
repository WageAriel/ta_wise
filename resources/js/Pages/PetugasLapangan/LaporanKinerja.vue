<script setup>
import { Head } from '@inertiajs/vue3';
import SidebarPetugas from '@/Components/SidebarPetugas.vue';

const props = defineProps({
    stats: Object,
    aktivitasTerbaru: Array,
});

function getBarWidth(count, total) {
    if (!total || total === 0) return '0%';
    return Math.round((count / total) * 100) + '%';
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}
</script>

<template>
    <Head title="Laporan Kinerja | Petugas WISE" />

    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarPetugas class="flex-shrink-0 border-r border-slate-200 shadow-sm" />

        <main class="flex-1 overflow-y-auto w-full relative">
            <!-- Header -->
            <div class="bg-white/80 backdrop-blur-xl border-b border-slate-200 sticky top-0 z-30 shadow-sm">
                <div class="px-8 py-5 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Laporan Kinerja</h1>
                        <p class="text-sm text-slate-500 font-medium mt-1">Ringkasan aktivitas verifikasi lapangan Anda.</p>
                    </div>
                </div>
            </div>

            <div class="p-8 max-w-6xl mx-auto space-y-6">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Card Total -->
                    <div class="bg-white rounded-[24px] p-6 shadow-sm border border-slate-100 flex items-center gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0 border border-blue-100">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Total Diselesaikan</p>
                            <h2 class="text-4xl font-black text-slate-800">{{ stats.total }} <span class="text-sm font-bold text-slate-400">Verifikasi</span></h2>
                        </div>
                    </div>
                    
                    <!-- Card Bulan Ini -->
                    <div class="bg-white rounded-[24px] p-6 shadow-sm border border-slate-100 flex items-center gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center flex-shrink-0 border border-emerald-100">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Diselesaikan Bulan Ini</p>
                            <h2 class="text-4xl font-black text-slate-800">{{ stats.bulan_ini }} <span class="text-sm font-bold text-slate-400">Verifikasi</span></h2>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Rekomendasi Sistem -->
                    <div class="bg-white rounded-[24px] p-6 shadow-sm border border-slate-100">
                        <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-6 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            Distribusi Rekomendasi
                        </h3>
                        
                        <div class="space-y-4">
                            <!-- Class A -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1.5">
                                    <span class="text-slate-700">Class A (Premium)</span>
                                    <span class="text-slate-500">{{ stats.distribusi['Class A'] }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-1000" :style="{ width: getBarWidth(stats.distribusi['Class A'], stats.total) }"></div>
                                </div>
                            </div>
                            
                            <!-- Class B -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1.5">
                                    <span class="text-slate-700">Class B (Standard)</span>
                                    <span class="text-slate-500">{{ stats.distribusi['Class B'] }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5">
                                    <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-1000" :style="{ width: getBarWidth(stats.distribusi['Class B'], stats.total) }"></div>
                                </div>
                            </div>
                            
                            <!-- Class C -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1.5">
                                    <span class="text-slate-700">Class C (Basic)</span>
                                    <span class="text-slate-500">{{ stats.distribusi['Class C'] }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5">
                                    <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-1000" :style="{ width: getBarWidth(stats.distribusi['Class C'], stats.total) }"></div>
                                </div>
                            </div>
                            
                            <!-- Belum Memenuhi -->
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1.5">
                                    <span class="text-slate-700">Belum Memenuhi</span>
                                    <span class="text-slate-500">{{ stats.distribusi['Belum Memenuhi'] }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5">
                                    <div class="bg-rose-500 h-2.5 rounded-full transition-all duration-1000" :style="{ width: getBarWidth(stats.distribusi['Belum Memenuhi'], stats.total) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aktivitas Terbaru -->
                    <div class="bg-white rounded-[24px] p-6 shadow-sm border border-slate-100">
                        <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-6 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Aktivitas Terbaru
                        </h3>
                        
                        <div class="space-y-4">
                            <div v-if="!aktivitasTerbaru.length" class="text-center py-8">
                                <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Belum ada aktivitas tercatat</p>
                            </div>
                            
                            <div v-for="(aktivitas, index) in aktivitasTerbaru" :key="aktivitas.id_verifikasi" class="flex gap-4 relative">
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 border border-blue-100 flex items-center justify-center flex-shrink-0 z-10">
                                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                    </div>
                                    <div v-if="index !== aktivitasTerbaru.length - 1" class="w-px h-full bg-slate-200 mt-2 absolute top-8 bottom-[-16px]"></div>
                                </div>
                                <div class="pb-6">
                                    <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-0.5">{{ formatDate(aktivitas.tanggal) }}</p>
                                    <p class="text-sm font-bold text-slate-800">Menyelesaikan verifikasi <span class="text-blue-600">{{ aktivitas.klasifikasi?.supplier?.nama_perusahaan || 'Supplier' }}</span></p>
                                    <p class="text-[11px] font-bold text-slate-500 mt-1 uppercase tracking-widest">
                                        Skor Akhir: <span class="text-slate-800">{{ aktivitas.total_nilai }}</span> &bull; 
                                        Rekomendasi: <span class="text-slate-800">{{ aktivitas.rekomendasi_sistem }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
main::-webkit-scrollbar { width: 6px; }
main::-webkit-scrollbar-track { background: transparent; }
main::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
main::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
</style>
