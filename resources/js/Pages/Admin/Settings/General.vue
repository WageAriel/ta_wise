<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import SidebarAdmin from "@/Components/SidebarAdmin.vue";

const props = defineProps({
    settings: Object,
});

const form = useForm({
    system_name: props.settings.system_name || 'TA Wise',
    theme_color: props.settings.theme_color || '#2563eb',
    minimal_skor_lulus: props.settings.minimal_skor_lulus || 70,
    min_skor_kelas_a: props.settings.min_skor_kelas_a || 85,
    min_skor_kelas_b: props.settings.min_skor_kelas_b || 60,
    min_skor_kelas_c: props.settings.min_skor_kelas_c || 30,
    system_logo: null,
});

const submit = () => {
    form.post(route('admin.settings.general.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Pengaturan sistem berhasil disimpan.',
                icon: 'success',
                confirmButtonColor: '#059669',
            });
        },
        onError: () => {
            Swal.fire({
                title: 'Gagal!',
                text: 'Terjadi kesalahan. Pastikan isian Anda sudah benar.',
                icon: 'error',
                confirmButtonColor: '#e11d48',
            });
        }
    });
};
</script>

<template>
    <Head title="Pengaturan Umum | Admin" />
    
    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarAdmin class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-4xl mx-auto px-6 py-10 lg:px-10">
                <div class="mb-8">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Pengaturan Umum</h1>
                    <p class="text-slate-500 mt-1 text-sm">Konfigurasi nama sistem, tema, dan logo aplikasi.</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nama Sistem</label>
                            <input type="text" v-model="form.system_name" class="w-full border-slate-200 rounded-lg bg-slate-50 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: Aplikasi Klasifikasi Supplier">
                        </div>

                        <!-- Input Skor Minimum Lulus Seleksi -->
                        <div class="p-4 bg-amber-50/50 border border-amber-100 rounded-xl mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-1">
                                Minimal Skor Kelulusan Seleksi Supplier
                            </label>
                            <p class="text-xs text-slate-500 mb-3">
                                Batas minimum skor supplier dapat dinyatakan lulus secara sistem.
                            </p>
                            <div class="relative w-48">
                                <input 
                                    type="number" 
                                    min="0"
                                    max="100"
                                    required
                                    v-model="form.minimal_skor_lulus" 
                                    class="w-full border-slate-200 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500 text-sm font-bold text-amber-700 pr-10" 
                                    :class="{ 'border-rose-500 ring-1 ring-rose-500': form.errors.minimal_skor_lulus }"
                                    placeholder="Contoh: 70"
                                >
                                <span class="absolute right-3 top-2.5 text-sm font-bold text-slate-400">Poin</span>
                            </div>
                            <p v-if="form.errors.minimal_skor_lulus" class="text-rose-600 text-[11px] font-bold mt-2 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ form.errors.minimal_skor_lulus }}
                            </p>
                        </div>

                        <!-- Input Rekomendasi Kelas Klasifikasi -->
                        <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-xl mb-4">
                            <label class="block text-sm font-bold text-slate-700 mb-1">
                                Standar Nilai Kelas Klasifikasi
                            </label>
                            <p class="text-xs text-slate-500 mb-4">
                                Tentukan batas minimal nilai untuk masuk ke dalam masing-masing rekomendasi kelas.
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Kelas A -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Minimal Kelas A</label>
                                    <div class="relative">
                                        <input 
                                            type="number" min="0" max="100" required v-model="form.min_skor_kelas_a" 
                                            class="w-full border-slate-200 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500 text-sm font-bold text-blue-700 pr-10" 
                                            :class="{ 'border-rose-500 ring-1 ring-rose-500': form.errors.min_skor_kelas_a }"
                                            placeholder="Contoh: 85"
                                        >
                                        <span class="absolute right-3 top-2.5 text-sm font-bold text-slate-400">Poin</span>
                                    </div>
                                    <p v-if="form.errors.min_skor_kelas_a" class="text-rose-600 text-[11px] font-bold mt-2">{{ form.errors.min_skor_kelas_a }}</p>
                                </div>
                                
                                <!-- Kelas B -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Minimal Kelas B</label>
                                    <div class="relative">
                                        <input 
                                            type="number" min="0" max="100" required v-model="form.min_skor_kelas_b" 
                                            class="w-full border-slate-200 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500 text-sm font-bold text-blue-700 pr-10" 
                                            :class="{ 'border-rose-500 ring-1 ring-rose-500': form.errors.min_skor_kelas_b }"
                                            placeholder="Contoh: 60"
                                        >
                                        <span class="absolute right-3 top-2.5 text-sm font-bold text-slate-400">Poin</span>
                                    </div>
                                    <p v-if="form.errors.min_skor_kelas_b" class="text-rose-600 text-[11px] font-bold mt-2">{{ form.errors.min_skor_kelas_b }}</p>
                                </div>
                                
                                <!-- Kelas C -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Minimal Kelas C</label>
                                    <div class="relative">
                                        <input 
                                            type="number" min="0" max="100" required v-model="form.min_skor_kelas_c" 
                                            class="w-full border-slate-200 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500 text-sm font-bold text-blue-700 pr-10" 
                                            :class="{ 'border-rose-500 ring-1 ring-rose-500': form.errors.min_skor_kelas_c }"
                                            placeholder="Contoh: 30"
                                        >
                                        <span class="absolute right-3 top-2.5 text-sm font-bold text-slate-400">Poin</span>
                                    </div>
                                    <p v-if="form.errors.min_skor_kelas_c" class="text-rose-600 text-[11px] font-bold mt-2">{{ form.errors.min_skor_kelas_c }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Warna Tema (Hex Code)</label>
                            <div class="flex items-center gap-4">
                                <input type="color" v-model="form.theme_color" class="h-10 w-16 p-1 border border-slate-200 rounded-lg cursor-pointer">
                                <input type="text" v-model="form.theme_color" class="w-full border-slate-200 rounded-lg bg-slate-50 focus:ring-blue-500 focus:border-blue-500 text-sm font-mono uppercase" placeholder="#FFFFFF">
                            </div>
                        </div> -->

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Upload Logo Sistem (Opsional)</label>
                            <input type="file" @input="form.system_logo = $event.target.files[0]" accept="image/*" class="w-full border-slate-200 rounded-lg bg-slate-50 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-slate-500 mt-2">Pilih file gambar (JPG/PNG, max 2MB). Kosongkan jika tidak ingin mengubah.</p>
                            <div v-if="props.settings.system_logo" class="mt-3">
                                <p class="text-xs text-slate-600 font-semibold mb-1">Logo Saat Ini:</p>
                                <img :src="props.settings.system_logo" alt="Logo" class="h-16 rounded border border-slate-200 object-contain p-1 bg-white">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-sm transition disabled:opacity-50">
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>
