<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import SidebarAdmin from '@/Components/SidebarAdmin.vue';
// import Pagination from '@/Components/Pagination.vue'; // Asumsi ada komponen pagination

const props = defineProps({
    stats: Object,
    jadwals: Object,
    petugass: Object,
    klasifikasi_pending: Array,
    petugas_list: Array,
    filters: Object
});

// State Tab
const activeTab = ref('jadwal'); // 'jadwal' atau 'petugas'

// State Modal Tambah Jadwal
const showModalJadwal = ref(false);
const formJadwal = useForm({
    id_klasifikasi: '',
    id_user_petugas: '',
    tanggal_kunjungan: '',
    waktu_kunjungan: ''
});

// State Modal Tambah Petugas
const showModalPetugas = ref(false);
const formPetugas = useForm({
    username: '',
    email: '',
    password: '',
    posisi: '',
    kontak: ''
});

// Fitur Search
const searchJadwal = ref(props.filters.search_jadwal || '');
const searchPetugas = ref(props.filters.search_petugas || '');

const submitSearchJadwal = () => {
    router.get(route('admin.field-officers'), { search_jadwal: searchJadwal.value }, { preserveState: true, replace: true });
};

const submitSearchPetugas = () => {
    router.get(route('admin.field-officers'), { search_petugas: searchPetugas.value }, { preserveState: true, replace: true });
};

// Fungsi Submit Form
const submitJadwal = () => {
    formJadwal.post(route('admin.field-officers.jadwal.store'), {
        onSuccess: () => {
            showModalJadwal.value = false;
            formJadwal.reset();
        }
    });
};

const submitPetugas = () => {
    formPetugas.post(route('admin.field-officers.petugas.store'), {
        onSuccess: () => {
            showModalPetugas.value = false;
            formPetugas.reset();
        }
    });
};

</script>

<template>
    <Head title="Field Officers | Admin" />

    <div class="flex h-screen bg-[#F8FAFC] overflow-hidden">
        <SidebarAdmin />

        <main class="flex-1 overflow-y-auto">
            <div class="p-8 space-y-6">
                <!-- Header -->
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Field Officers Management</h1>
                    <p class="text-slate-500 mt-1">Kelola petugas lapangan dan jadwal kunjungan verifikasi klasifikasi.</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Total Petugas</p>
                            <h3 class="text-2xl font-bold text-slate-900">{{ stats.total_petugas }}</h3>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Petugas Aktif</p>
                            <h3 class="text-2xl font-bold text-slate-900">{{ stats.petugas_aktif }}</h3>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Total Jadwal</p>
                            <h3 class="text-2xl font-bold text-slate-900">{{ stats.jadwal_kunjungan }}</h3>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-500 text-sm font-medium">Sedang Berlangsung</p>
                            <h3 class="text-2xl font-bold text-slate-900">{{ stats.sedang_berlangsung }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-slate-200 space-x-8">
                    <button 
                        @click="activeTab = 'jadwal'" 
                        :class="['pb-3 text-sm font-semibold transition-colors relative', activeTab === 'jadwal' ? 'text-blue-600' : 'text-slate-500 hover:text-slate-700']">
                        Jadwal Kunjungan
                        <div v-if="activeTab === 'jadwal'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-t-full"></div>
                    </button>
                    <button 
                        @click="activeTab = 'petugas'" 
                        :class="['pb-3 text-sm font-semibold transition-colors relative', activeTab === 'petugas' ? 'text-blue-600' : 'text-slate-500 hover:text-slate-700']">
                        Data Petugas
                        <div v-if="activeTab === 'petugas'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-t-full"></div>
                    </button>
                </div>

                <!-- Tab Content: Jadwal Kunjungan -->
                <div v-if="activeTab === 'jadwal'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="relative w-72">
                            <input v-model="searchJadwal" @keyup.enter="submitSearchJadwal" type="text" placeholder="Cari Supplier..." class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <button @click="showModalJadwal = true" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Tambah Jadwal
                        </button>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-900">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">No</th>
                                    <th class="px-6 py-4 font-semibold">Nama Supplier</th>
                                    <th class="px-6 py-4 font-semibold">Petugas</th>
                                    <th class="px-6 py-4 font-semibold">Tanggal & Waktu</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(j, i) in jadwals.data" :key="j.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">{{ (jadwals.current_page - 1) * jadwals.per_page + i + 1 }}</td>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ j.klasifikasi?.supplier?.nama_perusahaan || '-' }}</td>
                                    <td class="px-6 py-4">{{ j.petugas?.username || '-' }}</td>
                                    <td class="px-6 py-4">{{ j.tanggal_kunjungan }} {{ j.waktu_kunjungan }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-amber-100 text-amber-700': j.status === 'menunggu',
                                                'bg-blue-100 text-blue-700': j.status === 'berlangsung',
                                                'bg-emerald-100 text-emerald-700': j.status === 'selesai',
                                                'bg-red-100 text-red-700': j.status === 'dibatalkan',
                                            }">
                                            {{ j.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Detail</button>
                                    </td>
                                </tr>
                                <tr v-if="jadwals.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada jadwal kunjungan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination Manual Sementara (Jika komponen Pagination tidak tersedia) -->
                    <div class="flex justify-between items-center mt-4">
                        <span class="text-sm text-slate-500">Menampilkan {{ jadwals.from || 0 }} sampai {{ jadwals.to || 0 }} dari {{ jadwals.total }} entri</span>
                        <div class="flex gap-1">
                            <template v-for="(link, i) in jadwals.links" :key="i">
                                <Link v-if="link.url" :href="link.url" class="px-3 py-1 border border-slate-200 rounded-md text-sm" :class="link.active ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-white text-slate-600 hover:bg-slate-50'" v-html="link.label"></Link>
                                <span v-else class="px-3 py-1 border border-slate-200 rounded-md text-sm bg-slate-50 text-slate-400" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Data Petugas -->
                <div v-if="activeTab === 'petugas'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="relative w-72">
                            <input v-model="searchPetugas" @keyup.enter="submitSearchPetugas" type="text" placeholder="Cari Petugas..." class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <button @click="showModalPetugas = true" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                            Tambah Petugas
                        </button>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-900">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">No</th>
                                    <th class="px-6 py-4 font-semibold">Nama Petugas</th>
                                    <th class="px-6 py-4 font-semibold">Posisi</th>
                                    <th class="px-6 py-4 font-semibold">Kontak</th>
                                    <th class="px-6 py-4 font-semibold text-center">Supplier Aktif</th>
                                    <th class="px-6 py-4 font-semibold text-center">Selesai</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(p, i) in petugass.data" :key="p.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">{{ (petugass.current_page - 1) * petugass.per_page + i + 1 }}</td>
                                    <td class="px-6 py-4 font-medium text-slate-900">
                                        {{ p.username }}<br>
                                        <span class="text-xs text-slate-400 font-normal">{{ p.email }}</span>
                                    </td>
                                    <td class="px-6 py-4">{{ p.profil_petugas?.posisi || '-' }}</td>
                                    <td class="px-6 py-4">{{ p.profil_petugas?.kontak || '-' }}</td>
                                    <td class="px-6 py-4 text-center"><span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs font-bold">0</span></td>
                                    <td class="px-6 py-4 text-center"><span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-xs font-bold">0</span></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :class="p.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                                            {{ p.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Edit</button>
                                    </td>
                                </tr>
                                <tr v-if="petugass.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500">Belum ada data petugas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <!-- <div class="flex justify-between items-center mt-4">
                        <span class="text-sm text-slate-500">Menampilkan {{ petugass.from || 0 }} sampai {{ petugass.to || 0 }} dari {{ petugass.total }} entri</span>
                        <div class="flex gap-1">
                            <template v-for="(link, i) in petugass.links" :key="i">
                                <Link v-if="link.url" :href="link.url" class="px-3 py-1 border border-slate-200 rounded-md text-sm" :class="link.active ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-white text-slate-600 hover:bg-slate-50'" v-html="link.label"></Link>
                                <span v-else class="px-3 py-1 border border-slate-200 rounded-md text-sm bg-slate-50 text-slate-400" v-html="link.label"></span>
                            </template>
                        </div>
                    </div> -->
                </div>

            </div>
        </main>

        <!-- Modal Tambah Jadwal -->
        <div v-if="showModalJadwal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showModalJadwal = false"></div>
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center shrink-0">
                    <h3 class="text-lg font-bold text-slate-900">Tambah Jadwal Kunjungan</h3>
                    <button @click="showModalJadwal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto">
                    <form @submit.prevent="submitJadwal" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Supplier (Klasifikasi Pending)</label>
                            <select v-model="formJadwal.id_klasifikasi" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="" disabled>-- Pilih Supplier --</option>
                                <option v-for="k in klasifikasi_pending" :key="k.id_klasifikasi" :value="k.id_klasifikasi">
                                    {{ k.supplier?.nama_perusahaan }} (Skor: {{ k.total_nilai }})
                                </option>
                            </select>
                            <span class="text-red-500 text-xs mt-1" v-if="formJadwal.errors.id_klasifikasi">{{ formJadwal.errors.id_klasifikasi }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Petugas Lapangan</label>
                            <select v-model="formJadwal.id_user_petugas" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="" disabled>-- Pilih Petugas --</option>
                                <option v-for="p in petugas_list" :key="p.id" :value="p.id">{{ p.username }}</option>
                            </select>
                            <span class="text-red-500 text-xs mt-1" v-if="formJadwal.errors.id_user_petugas">{{ formJadwal.errors.id_user_petugas }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                                <input type="date" v-model="formJadwal.tanggal_kunjungan" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required />
                                <span class="text-red-500 text-xs mt-1" v-if="formJadwal.errors.tanggal_kunjungan">{{ formJadwal.errors.tanggal_kunjungan }}</span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Waktu</label>
                                <input type="time" v-model="formJadwal.waktu_kunjungan" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required />
                                <span class="text-red-500 text-xs mt-1" v-if="formJadwal.errors.waktu_kunjungan">{{ formJadwal.errors.waktu_kunjungan }}</span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="p-6 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                    <button type="button" @click="showModalJadwal = false" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50">Batal</button>
                    <button type="button" @click="submitJadwal" :disabled="formJadwal.processing" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 disabled:opacity-50">Simpan Jadwal</button>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Petugas -->
        <div v-if="showModalPetugas" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showModalPetugas = false"></div>
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center shrink-0">
                    <h3 class="text-lg font-bold text-slate-900">Tambah Petugas Baru</h3>
                    <button @click="showModalPetugas = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto">
                    <form @submit.prevent="submitPetugas" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Petugas (Username)</label>
                            <input type="text" v-model="formPetugas.username" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required />
                            <span class="text-red-500 text-xs mt-1" v-if="formPetugas.errors.username">{{ formPetugas.errors.username }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input type="email" v-model="formPetugas.email" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required />
                            <span class="text-red-500 text-xs mt-1" v-if="formPetugas.errors.email">{{ formPetugas.errors.email }}</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                            <input type="password" v-model="formPetugas.password" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required />
                            <span class="text-red-500 text-xs mt-1" v-if="formPetugas.errors.password">{{ formPetugas.errors.password }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Posisi</label>
                                <input type="text" v-model="formPetugas.posisi" placeholder="Cth: Auditor Senior" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Kontak / No. HP</label>
                                <input type="text" v-model="formPetugas.kontak" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="p-6 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                    <button type="button" @click="showModalPetugas = false" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50">Batal</button>
                    <button type="button" @click="submitPetugas" :disabled="formPetugas.processing" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 disabled:opacity-50">Simpan Petugas</button>
                </div>
            </div>
        </div>

    </div>
</template>
