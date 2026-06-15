<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import SidebarPetugas from '@/Components/SidebarPetugas.vue';

const props = defineProps({
    user: Object,
    message: String,
});

const form = useForm({
    username: props.user.username || '',
    email: props.user.email || '',
    password: '',
    password_confirmation: '',
    nama_petugas: props.user.profil_petugas?.nama_petugas || '',
    posisi: props.user.profil_petugas?.posisi || '',
    kontak: props.user.profil_petugas?.kontak || '',
});

const submit = () => {
    form.put(route('petugas.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Profil Petugas | WISE" />

    <div class="flex h-screen bg-[#F8FAFC] overflow-hidden">
        <SidebarPetugas class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="p-8 max-w-4xl mx-auto space-y-6">
                <!-- Header -->
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Profil Petugas</h1>
                    <p class="text-slate-500 mt-1">Perbarui informasi akun dan profil lapangan Anda di sini.</p>
                </div>

                <div v-if="props.message" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-medium">
                    {{ props.message }}
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <form @submit.prevent="submit" class="p-8 space-y-8">
                        
                        <!-- Akun Section -->
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-2 mb-4">Informasi Akun</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                                    <input type="text" v-model="form.username" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                                    <span class="text-red-500 text-xs mt-1" v-if="form.errors.username">{{ form.errors.username }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                    <input type="email" v-model="form.email" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                                    <span class="text-red-500 text-xs mt-1" v-if="form.errors.email">{{ form.errors.email }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Password Baru (Opsional)</label>
                                    <input type="password" v-model="form.password" placeholder="Kosongkan jika tidak ingin mengubah" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                                    <span class="text-red-500 text-xs mt-1" v-if="form.errors.password">{{ form.errors.password }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password Baru</label>
                                    <input type="password" v-model="form.password_confirmation" placeholder="Konfirmasi password" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                            </div>
                        </div>

                        <!-- Profil Petugas Section -->
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-2 mb-4">Profil Lapangan</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap Petugas</label>
                                    <input type="text" v-model="form.nama_petugas" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                                    <span class="text-red-500 text-xs mt-1" v-if="form.errors.nama_petugas">{{ form.errors.nama_petugas }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Posisi</label>
                                    <input type="text" v-model="form.posisi" placeholder="Cth: Auditor Senior" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                                    <span class="text-red-500 text-xs mt-1" v-if="form.errors.posisi">{{ form.errors.posisi }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Kontak / No. HP</label>
                                    <input type="text" v-model="form.kontak" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500" />
                                    <span class="text-red-500 text-xs mt-1" v-if="form.errors.kontak">{{ form.errors.kontak }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold shadow-sm hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 disabled:opacity-50 transition-all">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>
