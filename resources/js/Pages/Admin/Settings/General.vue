<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import SidebarAdmin from "@/Components/SidebarAdmin.vue";

const props = defineProps({
    settings: Object,
});

const form = useForm({
    system_name: props.settings.system_name || 'TA Wise',
    theme_color: props.settings.theme_color || '#2563eb',
    system_logo: null,
});

const submit = () => {
    form.post(route('admin.settings.general.update'));
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
