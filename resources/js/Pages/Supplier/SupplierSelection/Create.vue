<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SidebarSupplier from '@/Components/SidebarSupplier.vue';
import axios from 'axios';
import Swal from 'sweetalert2';

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ── State ─────────────────────────────────────────────────────────────────────
const paket_soal = ref({
    nama_soal: '',
    pertanyaans: []
});
const id_soal = ref(null);
const answers = ref({});
const isLoading = ref(true);
const errorMsg = ref('');
const isSubmitting = ref(false);

// ── Fetch Questions ───────────────────────────────────────────────────────────
onMounted(async () => {
    try {
        const res = await axios.get('/api/seleksi/pertanyaan');
        paket_soal.value = res.data;
        id_soal.value = res.data.id_soal;
    } catch (err) {
        errorMsg.value = err.response?.data?.message || 'Gagal memuat pertanyaan. Silakan muat ulang halaman.';
        if (err.response?.status === 403 || err.response?.status === 409) {
            Swal.fire({
                title: 'Informasi',
                text: errorMsg.value,
                icon: 'info',
                confirmButtonColor: '#2563eb'
            }).then(() => {
                window.location.href = route('supplier.selection');
            });
        }
    } finally {
        isLoading.value = false;
    }
});

const isAllAnswered = computed(() => {
    return paket_soal.value?.pertanyaans?.length > 0 && 
           paket_soal.value.pertanyaans.every(p => answers.value[p.id_pertanyaan]);
});

const submit = async () => {
    if (!isAllAnswered.value) {
        Swal.fire({
            title: 'Gagal!',
            text: 'Mohon maaf, Anda harus menjawab seluruh pertanyaan sebelum dapat melakukan pengajuan.',
            icon: 'error',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Konfirmasi Pengajuan',
        text: 'Yakin Ingin Mengirim Jawaban? Data Akan Dikunci dan Dinilai Oleh Sistem Untuk Kelolosan Supplier.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'YA, KIRIM',
        cancelButtonText: 'PERIKSA LAGI'
    });

    if (result.isConfirmed) {
        confirmSubmit();
    }
};

const confirmSubmit = async () => {
    isSubmitting.value = true;
    try {
        const jawaban = Object.entries(answers.value).map(([id, idOpsi]) => ({
            id_pertanyaan: parseInt(id),
            id_opsi: idOpsi,
        }));

        await axios.post('/api/seleksi', {
            id_soal: id_soal.value,
            jawaban: jawaban
        });

        Swal.fire({
            title: 'BERHASIL!',
            text: 'Jawaban Anda telah berhasil terkirim. Admin akan segera melakukan peninjauan.',
            icon: 'success',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        }).then(() => {
            window.location.href = route('supplier.selection');
        });
    } catch (err) {
        Swal.fire({
            title: 'GAGAL!',
            text: err.response?.data?.message || 'Terjadi kesalahan saat mengirim jawaban. Silakan coba beberapa saat lagi.',
            icon: 'error',
            confirmButtonColor: '#2563eb'
        });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Form Pengajuan Seleksi" />

    <div class="flex min-h-screen bg-gray-50 font-sans">
        <SidebarSupplier />

        <main class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Form Pengajuan Seleksi</h1>
                    <p class="text-sm text-slate-500 mt-2 font-medium">Lengkapi Jawaban Anda untuk Tahap Seleksi Supplier</p>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white rounded-[32px] border border-slate-100">
                    <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-4 text-slate-500 font-medium">Memuat butir pertanyaan...</p>
                </div>

                <div v-else-if="errorMsg" class="bg-rose-50 border border-rose-100 rounded-[32px] p-10 text-center">
                    <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Terjadi Kendala</h3>
                    <p class="text-slate-600 mb-6">{{ errorMsg }}</p>
                    <Link :href="route('supplier.selection')" class="px-8 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold text-xs hover:bg-slate-50 transition-all uppercase tracking-widest"> Kembali ke Dashboard </Link>
                </div>

                <div v-else class="bg-white rounded-[32px] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-8 border-b border-slate-100 bg-slate-50/30 flex items-center justify-between">
                        <div>
                            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black rounded-lg mb-2 uppercase tracking-widest">Bank Soal Aktif</span>
                            <h2 class="text-lg font-black text-slate-900 tracking-tight uppercase">{{ paket_soal.nama_soal }}</h2>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-black text-slate-400 block uppercase tracking-widest">Progress</span>
                            <span class="text-xl font-black text-blue-600">{{ Object.keys(answers).length }}<span class="text-slate-300 mx-1">/</span>{{ paket_soal.pertanyaans.length }}</span>
                        </div>
                    </div>
                    
                    <form @submit.prevent="submit" class="p-8 lg:p-12">
                        <div v-for="(pertanyaan, index) in paket_soal.pertanyaans" :key="pertanyaan.id_pertanyaan" class="mb-12 last:mb-6 group">
                            <div class="flex items-start gap-5 mb-6">
                                <span class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-white text-slate-900 border-2 border-slate-100 rounded-2xl text-sm font-black transition-all group-hover:border-blue-500 group-hover:text-blue-600 group-hover:shadow-lg group-hover:shadow-blue-50">
                                    {{ index + 1 }}
                                </span>
                                <p class="text-[15px] font-bold text-slate-800 pt-2 leading-relaxed">
                                    {{ pertanyaan.teks_pertanyaan }}
                                </p>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3 ml-14">
                                <label v-for="opsi in pertanyaan.opsi" :key="opsi.id_opsi" 
                                    class="relative flex items-center p-5 border-2 rounded-[22px] cursor-pointer transition-all duration-300"
                                    :class="answers[pertanyaan.id_pertanyaan] === opsi.id_opsi 
                                        ? 'border-blue-600 bg-blue-50/50 ring-4 ring-blue-600/5 shadow-sm' 
                                        : 'border-slate-50 bg-slate-50/30 hover:border-slate-200 hover:bg-white'">
                                    
                                    <div class="relative flex items-center justify-center w-5 h-5">
                                        <input type="radio" 
                                            v-model="answers[pertanyaan.id_pertanyaan]" 
                                            :value="opsi.id_opsi"
                                            class="peer opacity-0 absolute inset-0 cursor-pointer z-10"
                                        />
                                        <div class="w-5 h-5 border-2 border-slate-300 rounded-full peer-checked:border-blue-600 transition-all"></div>
                                        <div class="w-2.5 h-2.5 bg-blue-600 rounded-full scale-0 peer-checked:scale-100 transition-transform absolute"></div>
                                    </div>
                                    <span class="ml-4 text-sm font-bold transition-colors"
                                        :class="answers[pertanyaan.id_pertanyaan] === opsi.id_opsi ? 'text-blue-700' : 'text-slate-600'">
                                        {{ opsi.teks_opsi }}
                                    </span>

                                    <!-- Indicator selected -->
                                    <div v-if="answers[pertanyaan.id_pertanyaan] === opsi.id_opsi" class="ml-auto">
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 pt-10 border-t border-slate-100 mt-16">
                            <Link :href="route('supplier.selection')"
                                class="px-8 py-4 text-xs font-black tracking-widest text-slate-400 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 hover:text-slate-600 transition-all uppercase">
                                Batal & Kembali
                            </Link>
                            <button type="submit" :disabled="isSubmitting"
                                class="px-12 py-4 text-xs font-black tracking-widest text-white bg-blue-600 rounded-2xl hover:bg-blue-700 shadow-2xl shadow-blue-600/20 active:scale-95 disabled:opacity-50 transition-all uppercase flex items-center gap-3">
                                <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                {{ isSubmitting ? 'Mengirim Data...' : 'Kirim Pengajuan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.font-sans {
    font-family: 'Plus Jakarta Sans', sans-serif;
}
</style>
