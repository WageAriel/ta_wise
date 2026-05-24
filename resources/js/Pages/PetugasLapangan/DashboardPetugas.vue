<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import SidebarPetugas from '@/Components/SidebarPetugas.vue';

// ─── Data dari Backend (Mockup untuk saat ini) ──────────────────────────
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            jadwal_minggu_ini: 8,
            verifikasi_hari_ini: 2,
            selesai_bulan_ini: 24,
            menunggu_verifikasi: 5, // Stat ke-4 yang ditambahkan
        })
    },
    jadwalMendatang: {
        type: Array,
        default: () => [
            { id: 1, supplier: 'PT Maju Bersama', tanggal: '2025-05-22', waktu: '09:00 WIB', lokasi: 'Jakarta Selatan', status: 'terjadwal' },
            { id: 2, supplier: 'CV Karya Utama', tanggal: '2025-05-23', waktu: '13:30 WIB', lokasi: 'Bekasi', status: 'terjadwal' },
            { id: 3, supplier: 'PT Global Indo', tanggal: '2025-05-24', waktu: '10:00 WIB', lokasi: 'Tangerang', status: 'terjadwal' },
        ]
    },
    verifikasiTerbaru: {
        type: Array,
        default: () => [
            { id: 101, supplier: 'UD Sumber Makmur', tanggal: '2025-05-20', nilai: 88, hasil: 'Lulus' },
            { id: 102, supplier: 'PT Sukses Mandiri', tanggal: '2025-05-19', nilai: 75, hasil: 'Lulus' },
            { id: 103, supplier: 'CV Bintang Jaya', tanggal: '2025-05-18', nilai: 55, hasil: 'Tidak Lulus' },
        ]
    }
});

// ─── Tanggal & User ───────────────────────────────────────────────────────
const user = computed(() => usePage().props.auth.user);
const namaPetugas = computed(() => user.value?.name || 'Petugas');

const tanggalHariIni = computed(() => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date().toLocaleDateString('id-ID', options);
});
</script>

<template>
    <Head title="Dashboard Petugas | WISE" />

    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarPetugas class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-7xl mx-auto px-6 py-10 lg:px-10">
                
                <!-- ── Section 1: Welcome & Tanggal ── -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 bg-gradient-to-br from-blue-600 to-indigo-700 p-8 rounded-2xl shadow-md text-black">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight">
                            Selamat Datang, {{ namaPetugas }}! 👋
                        </h1>
                        <p class="mt-2 text-blue-100 font-medium">
                            Hari ini adalah {{ tanggalHariIni }}. Semoga pekerjaan Anda lancar hari ini.
                        </p>
                    </div>
                </div>

                <!-- ── Section 2: Stats (4 item) ── -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
                    <!-- Stat 1 -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-blue-100 text-blue-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-tight">Jadwal<br>Minggu Ini</p>
                            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ props.stats.jadwal_minggu_ini }}</p>
                        </div>
                    </div>

                    <!-- Stat 2 -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-indigo-100 text-indigo-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-tight">Verifikasi<br>Hari Ini</p>
                            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ props.stats.verifikasi_hari_ini }}</p>
                        </div>
                    </div>

                    <!-- Stat 3 -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-emerald-100 text-emerald-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-tight">Selesai<br>Bulan Ini</p>
                            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ props.stats.selesai_bulan_ini }}</p>
                        </div>
                    </div>

                    <!-- Stat 4 (Tambahan) -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-amber-100 text-amber-600 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-tight">Menunggu<br>Validasi</p>
                            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ props.stats.menunggu_verifikasi }}</p>
                        </div>
                    </div>
                </div>

                

                <!-- ── Section 3: Split Kiri Kanan ── -->
                <div class="grid grid-cols-2 gap-8">
                    
                    <!-- KIRI: Jadwal Mendatang -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-800">Jadwal Verifikasi Mendatang</h2>
                            <button class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">Lihat Semua</button>
                        </div>
                        <div class="p-6 flex-1">
                            <div class="space-y-4">
                                <div v-for="jadwal in props.jadwalMendatang.slice(0,3)" :key="jadwal.id" class="flex p-4 rounded-xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/50 transition-colors group">
                                    <div class="flex-shrink-0 w-14 h-14 bg-blue-50 text-blue-600 rounded-xl flex flex-col items-center justify-center font-bold">
                                        <span class="text-[10px] font-bold uppercase tracking-wider">{{ new Date(jadwal.tanggal).toLocaleDateString('id-ID', { month: 'short' }) }}</span>
                                        <span class="text-xl leading-none mt-0.5">{{ new Date(jadwal.tanggal).getDate() }}</span>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h3 class="text-sm font-bold text-slate-800">{{ jadwal.supplier }}</h3>
                                        <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-500 font-medium">
                                            <span class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                {{ jadwal.waktu }}
                                            </span>
                                            <span class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                {{ jadwal.lokasi }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 self-center ml-2">
                                        <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md bg-amber-50 text-amber-600 border border-amber-200">Terjadwal</span>
                                    </div>
                                </div>
                                <div v-if="props.jadwalMendatang.length === 0" class="text-center py-8">
                                    <p class="text-sm text-slate-400 font-medium">Belum ada jadwal verifikasi mendatang</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KANAN: Verifikasi Terbaru -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-800">Verifikasi Selesai Terbaru</h2>
                            <button class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors">Lihat Riwayat</button>
                        </div>
                        <div class="p-6 flex-1">
                            <div class="space-y-4">
                                <div v-for="item in props.verifikasiTerbaru.slice(0,3)" :key="item.id" class="flex items-center justify-between p-4 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border"
                                            :class="item.hasil === 'Lulus' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200'">
                                            {{ item.supplier.charAt(0) }}
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-slate-800">{{ item.supplier }}</h3>
                                            <p class="text-xs text-slate-500 mt-0.5 font-medium">Selesai: {{ new Date(item.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-black text-slate-800">{{ item.nilai }}</div>
                                        <div class="text-[10px] font-bold uppercase tracking-wider mt-0.5"
                                            :class="item.hasil === 'Lulus' ? 'text-emerald-600' : 'text-red-600'">
                                            {{ item.hasil }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="props.verifikasiTerbaru.length === 0" class="text-center py-8">
                                    <p class="text-sm text-slate-400 font-medium">Belum ada verifikasi yang diselesaikan</p>
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
