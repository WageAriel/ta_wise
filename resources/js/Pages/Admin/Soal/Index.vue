<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SidebarAdmin from "@/Components/SidebarAdmin.vue";
import axios from 'axios';

const props = defineProps({
    initialHeaderSoals: Array,
    initialIdSoalKlasifikasiAktif: Number,
    initialIdSoalSeleksiAktif: Number,
});

const activeTab = ref('header'); // 'header' or 'pertanyaan'

// --- Data Pertanyaan ---
const pertanyaans = ref([]);
const loadPertanyaan = async () => {
    try {
        const res = await axios.get(route('admin.soal.pertanyaan.index'));
        pertanyaans.value = res.data;
    } catch(e) {
        console.error(e);
    }
};

onMounted(() => {
    loadPertanyaan();
});

// --- Modal & Form Pertanyaan ---
const isModalPertanyaanOpen = ref(false);
const formPertanyaan = useForm({
    id_pertanyaan: null,
    jenis_soal: 'klasifikasi',
    teks_pertanyaan: '',
    bobot: 10,
    status: 'aktif',
    opsis: [
        { teks_opsi: '', nilai: 100 },
        { teks_opsi: '', nilai: 0 }
    ]
});

const addOpsi = () => {
    formPertanyaan.opsis.push({ teks_opsi: '', nilai: 0 });
};
const removeOpsi = (index) => {
    if(formPertanyaan.opsis.length > 2) formPertanyaan.opsis.splice(index, 1);
};

const openPertanyaanModal = async (item = null) => {
    if (item) {
        formPertanyaan.id_pertanyaan = item.id_pertanyaan;
        formPertanyaan.jenis_soal = item.jenis_soal;
        formPertanyaan.teks_pertanyaan = item.teks_pertanyaan;
        formPertanyaan.bobot = item.bobot;
        formPertanyaan.status = item.status;
        try {
            const res = await axios.get(route('admin.soal.pertanyaan.show', item.id_pertanyaan));
            formPertanyaan.opsis = res.data.opsis.map(o => ({ teks_opsi: o.teks_opsi, nilai: o.nilai }));
        } catch(e) {
            console.error(e);
        }
    } else {
        formPertanyaan.reset();
        formPertanyaan.id_pertanyaan = null;
    }
    isModalPertanyaanOpen.value = true;
};

const savePertanyaan = async () => {
    try {
        if (formPertanyaan.id_pertanyaan) {
            await axios.put(route('admin.soal.pertanyaan.update', formPertanyaan.id_pertanyaan), formPertanyaan);
            alert('Berhasil diupdate');
        } else {
            await axios.post(route('admin.soal.pertanyaan.store'), formPertanyaan);
            alert('Berhasil disimpan');
        }
        isModalPertanyaanOpen.value = false;
        loadPertanyaan();
    } catch (e) {
        alert('Gagal menyimpan. Cek kembali isian Anda.');
        console.error(e);
    }
};

const deletePertanyaan = async (id) => {
    if (confirm('Yakin ingin menghapus?')) {
        await axios.delete(route('admin.soal.pertanyaan.destroy', id));
        loadPertanyaan();
    }
};


// --- Modal & Form Header Soal ---
const headers = ref(props.initialHeaderSoals || []);
const aktifSoalKlasifikasiId = ref(props.initialIdSoalKlasifikasiAktif || null);
const aktifSoalSeleksiId = ref(props.initialIdSoalSeleksiAktif || null);
const isSettingAktif = ref(false);
const isModalHeaderOpen = ref(false);
const formHeader = useForm({
    id_soal: null,
    nama_soal: '',
    jenis_soal: 'seleksi',
    pertanyaan_ids: []
});

// Filtered pertanyaans based on jenis_soal selected in header modal
const pertanyaansForHeader = computed(() => {
    return pertanyaans.value.filter(p => p.jenis_soal === formHeader.jenis_soal);
});

// --- Filter untuk Tab Daftar Pertanyaan ---
const filterJenisSoal = ref('semua');
const filteredPertanyaans = computed(() => {
    if (filterJenisSoal.value === 'semua') return pertanyaans.value;
    return pertanyaans.value.filter(p => p.jenis_soal === filterJenisSoal.value);
});

const openHeaderModal = async (item = null) => {
    if (item) {
        formHeader.id_soal = item.id_soal;
        formHeader.nama_soal = item.nama_soal;
        formHeader.jenis_soal = item.jenis_soal || 'seleksi';
        try {
            const res = await axios.get(route('admin.soal.header.show', item.id_soal));
            formHeader.pertanyaan_ids = res.data.pertanyaans.map(p => p.id_pertanyaan);
        } catch(e) {
            console.error(e);
        }
    } else {
        formHeader.reset();
        formHeader.id_soal = null;
        formHeader.jenis_soal = 'seleksi';
        formHeader.pertanyaan_ids = [];
    }
    isModalHeaderOpen.value = true;
};

const loadHeaders = async () => {
    try {
        const res = await axios.get(route('admin.soal.header.index'), { headers: { 'Accept': 'application/json' }});
        // API now returns { data: [...], id_soal_klasifikasi_aktif: ..., id_soal_seleksi_aktif: ... }
        headers.value = res.data.data ?? res.data;
        aktifSoalKlasifikasiId.value = res.data.id_soal_klasifikasi_aktif ?? null;
        aktifSoalSeleksiId.value = res.data.id_soal_seleksi_aktif ?? null;
    } catch(e) {
        console.error(e);
    }
};

const saveHeader = async () => {
    try {
        if (formHeader.id_soal) {
            await axios.put(route('admin.soal.header.update', formHeader.id_soal), formHeader);
            alert('Berhasil diupdate');
        } else {
            await axios.post(route('admin.soal.header.store'), formHeader);
            alert('Berhasil disimpan');
        }
        isModalHeaderOpen.value = false;
        loadHeaders();
    } catch (e) {
        alert('Gagal menyimpan. Cek kembali (pastikan ada pertanyaan yg dipilih).');
        console.error(e);
    }
};

const deleteHeader = async (id) => {
    if (confirm('Yakin ingin menghapus paket soal ini?')) {
        await axios.delete(route('admin.soal.header.destroy', id));
        loadHeaders();
    }
};

const setAktifSoal = async (item) => {
    if (!confirm(`Jadikan "${item.nama_soal}" sebagai paket soal ${item.jenis_soal} aktif?`)) return;
    isSettingAktif.value = true;
    try {
        const res = await axios.patch(route('admin.soal.header.setAktif', item.id_soal));
        if (item.jenis_soal === 'seleksi') {
            aktifSoalSeleksiId.value = res.data.id_soal_aktif;
        } else {
            aktifSoalKlasifikasiId.value = res.data.id_soal_aktif;
        }
        alert(`Berhasil! Paket soal "${item.nama_soal}" kini aktif untuk pengajuan ${item.jenis_soal}.`);
    } catch (e) {
        alert('Gagal mengubah paket soal aktif.');
        console.error(e);
    } finally {
        isSettingAktif.value = false;
    }
};
</script>

<template>
    <Head title="Bank Pertanyaan | Admin" />
    
    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarAdmin class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-7xl mx-auto px-6 py-10 lg:px-10">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Bank Pertanyaan</h1>
                        <p class="text-slate-500 mt-1 text-sm">Kelola pertanyaan dan paket soal (Header) untuk pengajuan.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
                    <div class="flex items-center border-b border-slate-100 px-6 pt-4 bg-slate-50">
                        <button @click="activeTab = 'header'" class="pb-4 font-bold mr-6 border-b-2 transition-colors text-sm" :class="activeTab === 'header' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600'">
                            Paket Soal (Header)
                        </button>
                        <button @click="activeTab = 'pertanyaan'" class="pb-4 font-bold mr-6 border-b-2 transition-colors text-sm" :class="activeTab === 'pertanyaan' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600'">
                            Daftar Pertanyaan
                        </button>
                    </div>

                    <!-- TAB 1: Header Soal -->
                    <div v-if="activeTab === 'header'" class="p-6">
                        <!-- Info banner soal aktif klasifikasi -->
                        <div v-if="aktifSoalKlasifikasiId" class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-emerald-800 text-sm font-medium">
                                Paket soal klasifikasi aktif: 
                                <strong>{{ headers.find(h => h.id_soal === aktifSoalKlasifikasiId)?.nama_soal ?? `#${aktifSoalKlasifikasiId}` }}</strong>
                            </p>
                        </div>
                        <div v-else class="mb-4 flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <p class="text-amber-800 text-sm font-medium">Belum ada paket soal klasifikasi aktif.</p>
                        </div>

                        <!-- Info banner soal aktif seleksi -->
                        <div v-if="aktifSoalSeleksiId" class="mb-4 flex items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3">
                            <svg class="w-5 h-5 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-indigo-800 text-sm font-medium">
                                Paket soal seleksi aktif: 
                                <strong>{{ headers.find(h => h.id_soal === aktifSoalSeleksiId)?.nama_soal ?? `#${aktifSoalSeleksiId}` }}</strong>
                            </p>
                        </div>
                        <div v-else class="mb-4 flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <p class="text-amber-800 text-sm font-medium">Belum ada paket soal seleksi aktif.</p>
                        </div>

                        <div class="flex justify-end mb-4">
                            <button @click="openHeaderModal()" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg text-sm hover:bg-blue-700">
                                + Buat Paket Soal
                            </button>
                        </div>
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase">ID</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase">Nama Paket Soal</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase">Jenis</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase">Jml Pertanyaan</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="item in headers" :key="item.id_soal"
                                    :class="item.id_soal === aktifSoalId && item.jenis_soal === 'klasifikasi' ? 'bg-emerald-50/40' : ''"
                                >
                                    <td class="px-6 py-4 font-mono font-bold text-slate-600">#{{ item.id_soal }}</td>
                                    <td class="px-6 py-4 font-bold text-slate-800">
                                        <div class="flex items-center gap-2">
                                            {{ item.nama_soal }}
                                            <span
                                                v-if="(item.id_soal === aktifSoalKlasifikasiId && item.jenis_soal === 'klasifikasi') || (item.id_soal === aktifSoalSeleksiId && item.jenis_soal === 'seleksi')"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700"
                                            >
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                AKTIF
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2 py-0.5 rounded text-xs font-bold uppercase"
                                            :class="item.jenis_soal === 'seleksi' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'"
                                        >{{ item.jenis_soal }}</span>
                                    </td>
                                    <td class="px-6 py-4">{{ item.pertanyaans_count || 0 }} Pertanyaan</td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            v-if="(item.jenis_soal === 'klasifikasi' && item.id_soal !== aktifSoalKlasifikasiId) || (item.jenis_soal === 'seleksi' && item.id_soal !== aktifSoalSeleksiId)"
                                            @click="setAktifSoal(item)"
                                            :disabled="isSettingAktif"
                                            class="text-emerald-600 font-bold hover:underline mr-3 text-sm disabled:opacity-50"
                                        >Jadikan Aktif</button>
                                        <span
                                            v-else
                                            class="text-blue-600 font-bold mr-3 text-sm opacity-60 cursor-default"
                                        >✓ Aktif</span>
                                        <button @click="openHeaderModal(item)" class="text-blue-600 font-bold hover:underline mr-3">Edit</button>
                                        <button @click="deleteHeader(item.id_soal)" class="text-red-600 font-bold hover:underline">Hapus</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TAB 2: Pertanyaan -->
                    <div v-if="activeTab === 'pertanyaan'" class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <!-- Filter Jenis Soal (kiri) -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="filterJenisSoal = 'semua'"
                                    class="px-3 py-2 rounded-lg text-sm font-bold transition-colors"
                                    :class="filterJenisSoal === 'semua' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                >Semua</button>
                                <button
                                    @click="filterJenisSoal = 'seleksi'"
                                    class="px-3 py-2 rounded-lg text-sm font-bold transition-colors"
                                    :class="filterJenisSoal === 'seleksi' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                >Seleksi</button>
                                <button
                                    @click="filterJenisSoal = 'klasifikasi'"
                                    class="px-3 py-2 rounded-lg text-sm font-bold transition-colors"
                                    :class="filterJenisSoal === 'klasifikasi' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                >Klasifikasi</button>
                            </div>
                            <!-- Tombol Tambah Pertanyaan (kanan) -->
                            <button @click="openPertanyaanModal()" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg text-sm hover:bg-blue-700">
                                + Tambah Pertanyaan
                            </button>
                        </div>
                        <div class="space-y-4">
                            <p v-if="filteredPertanyaans.length === 0" class="text-center text-slate-400 py-8 text-sm">Tidak ada pertanyaan ditemukan.</p>
                            <div v-for="p in filteredPertanyaans" :key="p.id_pertanyaan" class="border border-slate-200 rounded-xl p-4 flex justify-between items-start hover:bg-slate-50 transition">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span
                                            class="px-2 py-0.5 rounded text-xs font-bold uppercase"
                                            :class="p.jenis_soal === 'seleksi' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'"
                                        >{{ p.jenis_soal }}</span>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold uppercase" :class="p.status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">{{ p.status }}</span>
                                    </div>
                                    <h4 class="font-bold text-slate-800">{{ p.teks_pertanyaan }}</h4>
                                    <div class="mt-2 text-sm text-slate-500">Bobot: {{ p.bobot }} | Opsi: {{ p.opsis_count }}</div>
                                </div>
                                <div class="space-x-3">
                                    <button @click="openPertanyaanModal(p)" class="text-blue-600 font-bold hover:underline text-sm">Edit</button>
                                    <button @click="deletePertanyaan(p.id_pertanyaan)" class="text-red-600 font-bold hover:underline text-sm">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modal Header Soal -->
        <div v-if="isModalHeaderOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center sticky top-0 bg-white">
                    <h3 class="font-extrabold text-lg text-slate-800">{{ formHeader.id_soal ? 'Edit Paket Soal' : 'Buat Paket Soal' }}</h3>
                    <button @click="isModalHeaderOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nama Paket Soal</label>
                        <input type="text" v-model="formHeader.nama_soal" class="w-full border-slate-200 rounded-lg bg-slate-50 text-sm p-2.5">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Jenis Soal</label>
                        <select v-model="formHeader.jenis_soal" class="w-full border-slate-200 rounded-lg bg-slate-50 text-sm p-2.5">
                            <option value="seleksi">Seleksi</option>
                            <option value="klasifikasi">Klasifikasi</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Daftar pertanyaan di bawah akan disesuaikan dengan jenis soal yang dipilih.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Pertanyaan yang Dimasukkan</label>
                        <p v-if="pertanyaansForHeader.length === 0" class="text-xs text-slate-400 italic p-3 border border-slate-200 rounded-lg">Tidak ada pertanyaan {{ formHeader.jenis_soal }} yang tersedia.</p>
                        <div v-else class="max-h-64 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-2">
                            <label v-for="p in pertanyaansForHeader" :key="p.id_pertanyaan" class="flex items-start gap-3 p-2 hover:bg-slate-50 rounded cursor-pointer border border-transparent hover:border-slate-200">
                                <input type="checkbox" :value="p.id_pertanyaan" v-model="formHeader.pertanyaan_ids" class="mt-1 rounded text-blue-600 focus:ring-blue-500">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ p.teks_pertanyaan }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Status: {{ p.status }}</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t border-slate-100 flex justify-end gap-3 sticky bottom-0 bg-white">
                    <button @click="isModalHeaderOpen = false" class="px-5 py-2 text-slate-600 font-bold bg-slate-100 rounded-lg hover:bg-slate-200">Batal</button>
                    <button @click="saveHeader" class="px-5 py-2 text-white font-bold bg-blue-600 rounded-lg hover:bg-blue-700">Simpan</button>
                </div>
            </div>
        </div>

        <!-- Modal Pertanyaan -->
        <div v-if="isModalPertanyaanOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center sticky top-0 bg-white z-10">
                    <h3 class="font-extrabold text-lg text-slate-800">{{ formPertanyaan.id_pertanyaan ? 'Edit Pertanyaan' : 'Tambah Pertanyaan' }}</h3>
                    <button @click="isModalPertanyaanOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Jenis Soal</label>
                            <select v-model="formPertanyaan.jenis_soal" class="w-full border-slate-200 rounded-lg bg-slate-50 text-sm p-2.5">
                                <option value="klasifikasi">Klasifikasi</option>
                                <option value="seleksi">Seleksi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Status</label>
                            <select v-model="formPertanyaan.status" class="w-full border-slate-200 rounded-lg bg-slate-50 text-sm p-2.5">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Teks Pertanyaan</label>
                        <textarea v-model="formPertanyaan.teks_pertanyaan" rows="3" class="w-full border-slate-200 rounded-lg bg-slate-50 text-sm p-2.5"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Bobot Pertanyaan</label>
                        <input type="number" v-model="formPertanyaan.bobot" class="w-full border-slate-200 rounded-lg bg-slate-50 text-sm p-2.5">
                    </div>
                    <hr>
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <label class="block text-sm font-bold text-slate-700">Opsi Jawaban</label>
                            <button @click="addOpsi" class="text-blue-600 font-bold text-xs hover:underline">+ Tambah Opsi</button>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(opsi, index) in formPertanyaan.opsis" :key="index" class="flex gap-3 items-center">
                                <input type="text" v-model="opsi.teks_opsi" placeholder="Teks Opsi (mis: Ya / Memiliki)" class="flex-1 border-slate-200 rounded-lg bg-slate-50 text-sm p-2">
                                <input type="number" v-model="opsi.nilai" placeholder="Nilai (0-100)" class="w-24 border-slate-200 rounded-lg bg-slate-50 text-sm p-2">
                                <button @click="removeOpsi(index)" class="text-red-500 hover:text-red-700 font-bold px-2">&times;</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t border-slate-100 flex justify-end gap-3 sticky bottom-0 bg-white">
                    <button @click="isModalPertanyaanOpen = false" class="px-5 py-2 text-slate-600 font-bold bg-slate-100 rounded-lg hover:bg-slate-200">Batal</button>
                    <button @click="savePertanyaan" class="px-5 py-2 text-white font-bold bg-blue-600 rounded-lg hover:bg-blue-700">Simpan Pertanyaan</button>
                </div>
            </div>
        </div>

    </div>
</template>
