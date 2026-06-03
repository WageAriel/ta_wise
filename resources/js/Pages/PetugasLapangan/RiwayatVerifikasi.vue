<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SidebarPetugas from '@/Components/SidebarPetugas.vue';

const props = defineProps({
    riwayats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, (value) => {
    router.get(route('petugas.verifikasi.riwayat'), { search: value }, {
        preserveState: true,
        replace: true,
    });
});

function statusClass(status) {
    if (status === 'selesai') return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
    if (status === 'menunggu_admin') return 'bg-amber-100 text-amber-700 border border-amber-200';
    return 'bg-slate-100 text-slate-700 border border-slate-200';
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
    <Head title="Riwayat Verifikasi | Petugas WISE" />

    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarPetugas class="flex-shrink-0 border-r border-slate-200 shadow-sm" />

        <main class="flex-1 overflow-y-auto w-full relative">
            <!-- Header -->
            <div class="bg-white/80 backdrop-blur-xl border-b border-slate-200 sticky top-0 z-30 shadow-sm">
                <div class="px-8 py-5 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Riwayat Verifikasi</h1>
                        <p class="text-sm text-slate-500 font-medium mt-1">Daftar semua verifikasi lapangan yang telah diselesaikan.</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <!-- Search & Filters -->
                <div class="mb-6 flex items-center justify-between bg-white p-3 rounded-2xl shadow-sm border border-slate-100">
                    <div class="relative w-full max-w-md">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input v-model="search" type="text" placeholder="Cari nama perusahaan supplier..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 font-medium" />
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-[24px] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-16">No</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Supplier</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Tgl Verifikasi</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Nilai</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Rekomendasi</th>
                                    <th class="py-4 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status Admin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="(item, idx) in riwayats.data" :key="item.id_verifikasi" class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="py-4 px-6 text-center text-sm font-bold text-slate-500">{{ (riwayats.current_page - 1) * riwayats.per_page + idx + 1 }}</td>
                                    <td class="py-4 px-6">
                                        <p class="text-sm font-bold text-slate-800">{{ item.klasifikasi?.supplier?.nama_perusahaan || '-' }}</p>
                                    </td>
                                    <td class="py-4 px-6 text-center text-sm font-medium text-slate-600">{{ formatDate(item.tanggal) }}</td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="text-sm font-black text-slate-800">{{ item.total_nilai }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg bg-blue-50 text-blue-700 border border-blue-200 shadow-sm">
                                            {{ item.rekomendasi_sistem || '-' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span :class="statusClass(item.status)" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider rounded-lg shadow-sm">
                                            {{ item.status === 'menunggu_admin' ? 'Menunggu Admin' : (item.status === 'selesai' ? 'Selesai' : item.status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!riwayats.data.length">
                                    <td colspan="6" class="py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 mb-4 border border-slate-100">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Belum ada riwayat verifikasi</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div v-if="riwayats.links && riwayats.links.length > 3" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex flex-wrap justify-center items-center gap-1.5">
                        <template v-for="(link, key) in riwayats.links" :key="key">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url)"
                                :class="link.active ? 'bg-blue-600 text-white shadow-md border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="min-w-[36px] h-9 px-2 rounded-lg text-xs font-black transition-all border"
                                v-html="link.label.replace('Previous', '&laquo;').replace('Next', '&raquo;')"
                            ></button>
                            <span v-else class="min-w-[36px] h-9 px-2 rounded-lg flex items-center justify-center text-xs font-black text-slate-400 border border-slate-200 bg-slate-50" v-html="link.label.replace('Previous', '&laquo;').replace('Next', '&raquo;')"></span>
                        </template>
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
