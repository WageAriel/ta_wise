<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SidebarAdmin from "@/Components/SidebarAdmin.vue";

const props = defineProps({
    kelas: Array,
});

const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    nama_kelas: '',
});

const editItem = (item) => {
    isEditing.value = true;
    editingId.value = item.id_kelas;
    form.nama_kelas = item.nama_kelas;
};

const cancelEdit = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.settings.kelas.update', editingId.value), {
            onSuccess: () => cancelEdit()
        });
    } else {
        form.post(route('admin.settings.kelas.store'), {
            onSuccess: () => form.reset()
        });
    }
};

const deleteItem = (id) => {
    if (confirm('Yakin ingin menghapus kelas ini?')) {
        router.delete(route('admin.settings.kelas.destroy', id));
    }
};
</script>

<template>
    <Head title="Kelas Klasifikasi | Admin" />
    
    <div class="flex h-screen overflow-hidden bg-[#F8FAFC]">
        <SidebarAdmin class="flex-shrink-0 h-full overflow-y-auto border-r border-slate-200 shadow-sm" />

        <main class="flex-1 h-full overflow-y-auto">
            <div class="max-w-5xl mx-auto px-6 py-10 lg:px-10">
                <div class="mb-8">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kelas Klasifikasi</h1>
                    <p class="text-slate-500 mt-1 text-sm">Kelola master data kelas untuk hasil rekomendasi supplier (misal: Kelas A, Kelas B).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Form Tambah/Edit -->
                    <div class="md:col-span-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <h3 class="font-bold text-slate-800 mb-4">{{ isEditing ? 'Edit Kelas' : 'Tambah Kelas' }}</h3>
                            <form @submit.prevent="submit" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Kelas</label>
                                    <input type="text" v-model="form.nama_kelas" class="w-full border-slate-200 rounded-lg bg-slate-50 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Contoh: Kelas A" required>
                                </div>
                                <div class="flex gap-2 pt-2">
                                    <button type="button" v-if="isEditing" @click="cancelEdit" class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition text-sm">Batal</button>
                                    <button type="submit" :disabled="form.processing" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition text-sm">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabel Data -->
                    <div class="md:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">No</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest">Nama Kelas</th>
                                        <th class="px-6 py-3 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(item, idx) in kelas" :key="item.id_kelas" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 text-slate-500">{{ idx + 1 }}</td>
                                        <td class="px-6 py-4 font-bold text-slate-800">{{ item.nama_kelas }}</td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <button @click="editItem(item)" class="px-3 py-1.5 bg-amber-50 text-amber-600 hover:bg-amber-100 font-bold rounded-md text-xs transition">Edit</button>
                                            <button @click="deleteItem(item.id_kelas)" class="px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 font-bold rounded-md text-xs transition">Hapus</button>
                                        </td>
                                    </tr>
                                    <tr v-if="kelas.length === 0">
                                        <td colspan="3" class="px-6 py-8 text-center text-slate-500 font-medium">Belum ada data kelas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
