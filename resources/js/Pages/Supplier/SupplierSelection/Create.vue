<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import SidebarSupplier from '@/Components/SidebarSupplier.vue';

const props = defineProps({
    paket_soal: Object
});

const form = useForm({
    id_soal: props.paket_soal?.id_soal || 0,
    answers: {}
});

// State untuk Modal Konfirmasi & Notifikasi
const showConfirmModal = ref(false);
const showNotificationModal = ref(false);
const notificationType = ref('success'); // 'success' atau 'error'
const notificationMessage = ref('');

const isAllAnswered = computed(() => {
    return props.paket_soal?.pertanyaans?.every(p => form.answers[p.id_pertanyaan]) || false;
});

const submit = () => {
    if (!isAllAnswered.value) {
        notificationType.value = 'error';
        notificationMessage.value = 'Mohon maaf, Anda harus menjawab seluruh pertanyaan sebelum dapat melakukan pengajuan.';
        showNotificationModal.value = true;
        return;
    }
    showConfirmModal.value = true;
};

const confirmSubmit = () => {
    form.post(route('supplier.selection.store'), {
        onSuccess: () => {
            showConfirmModal.value = false;
            notificationType.value = 'success';
            notificationMessage.value = 'Jawaban Anda telah berhasil terkirim. Admin akan segera melakukan peninjauan.';
            showNotificationModal.value = true;
        },
        onError: () => {
            showConfirmModal.value = false;
            notificationType.value = 'error';
            notificationMessage.value = 'Terjadi kesalahan saat mengirim jawaban. Silakan coba beberapa saat lagi.';
            showNotificationModal.value = true;
        }
    });
};

const closeNotification = () => {
    showNotificationModal.value = false;
    if (notificationType.value === 'success') {
        // Redirect setelah sukses jika diinginkan
    }
};
</script>

<template>
    <Head title="Form Pengajuan Seleksi" />

    <div class="flex min-h-screen bg-gray-50">
        <SidebarSupplier />

        <main class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <div class="mb-6">
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Form Pengajuan Seleksi</h1>
                    <p class="text-xs text-gray-500 mt-1 font-medium">Lengkapi Jawaban Anda untuk Tahap Seleksi Supplier</p>
                </div>

                <div class="bg-white rounded-[24px] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-sm font-black text-slate-800 tracking-widest">{{ paket_soal.nama_soal }}</h2>
                    </div>
                    
                    <form @submit.prevent="submit" class="p-6 lg:p-10">
                        <div v-for="(pertanyaan, index) in paket_soal.pertanyaans" :key="pertanyaan.id_pertanyaan" class="mb-10 last:mb-6">
                            <div class="flex items-start gap-4 mb-5">
                                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-blue-600 text-white rounded-xl text-xs font-black shadow-lg shadow-blue-100">
                                    {{ index + 1 }}
                                </span>
                                <p class="text-xs font-medium text-slate-800 pt-2">
                                    {{ pertanyaan.teks_pertanyaan }}
                                </p>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3 ml-12">
                                <label v-for="opsi in pertanyaan.opsi" :key="opsi.id_opsi" 
                                    class="relative flex items-center p-4 border rounded-2xl cursor-pointer transition-all duration-300"
                                    :class="form.answers[pertanyaan.id_pertanyaan] === opsi.id_opsi 
                                        ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-600/10' 
                                        : 'border-slate-100 bg-white hover:border-blue-300 hover:bg-slate-50'">
                                    
                                    <input type="radio" 
                                        v-model="form.answers[pertanyaan.id_pertanyaan]" 
                                        :value="opsi.id_opsi"
                                        class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500"
                                    />
                                    <span class="ml-4 text-xs font-medium text-slate-600">{{ opsi.teks_opsi }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-10 border-t border-slate-100 mt-10">
                            <button type="button" @click="$inertia.get(route('supplier.selection'))"
                                class="px-8 py-3 text-[10px] font-black tracking-widest text-slate-500 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                                BATAL
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="px-10 py-3 text-[10px] font-black tracking-widest text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-xl shadow-blue-500/20 active:scale-95 disabled:opacity-50 transition-all">
                                {{ form.processing ? 'MEMPROSES...' : 'AJUKAN JAWABAN' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Modal Konfirmasi -->
        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px]" @click="showConfirmModal = false"></div>
                <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                    <div class="relative w-full max-w-[420px] bg-white rounded-[32px] shadow-2xl p-8 border border-slate-100">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white mb-6 shadow-xl shadow-blue-100">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-center text-slate-900">Konfrimasi Pengajuan</h3>
                        <p class="text-xs text-center text-slate-500 mt-3 font-medium">
                            Yakin Ingin Mengirim Jawaban? Data Akan Dikunci dan Dinilai Oleh Sistem Untuk Kelolosan Supplier.
                        </p>
                        <div class="mt-8 flex gap-3">
                            <button @click="showConfirmModal = false" class="flex-1 py-3.5 text-[10px] font-black text-slate-500 hover:bg-slate-50 rounded-2xl transition-all tracking-widest">PERIKSA LAGI</button>
                            <button @click="confirmSubmit" :disabled="form.processing" class="flex-1 py-3.5 text-[10px] font-black text-white bg-blue-600 rounded-2xl hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all active:scale-95 tracking-widest">YA, KIRIM</button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Modal Notifikasi -->
        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showNotificationModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px]" @click="closeNotification"></div>
                <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                    <div class="relative w-full max-w-[380px] bg-white rounded-[32px] shadow-2xl p-8 border border-slate-100 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full mb-6"
                            :class="notificationType === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                            <svg v-if="notificationType === 'success'" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900">
                            {{ notificationType === 'success' ? 'BERHASIL!' : 'GAGAL!' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-3 font-medium">
                            {{ notificationMessage }}
                        </p>
                        <button @click="closeNotification" 
                            class="mt-8 w-full py-3.5 text-sm font-black text-white rounded-2xl shadow-lg transition-all active:scale-95"
                            :class="notificationType === 'success' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-100' : 'bg-rose-600 hover:bg-rose-700 shadow-rose-100'">
                            TUTUP
                        </button>
                    </div>
                </Transition>
            </div>
        </Transition>

    </div>
</template>