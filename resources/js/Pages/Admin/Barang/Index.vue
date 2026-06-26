<script setup>
import { ref, computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AdminLayout from "../../../Layouts/AdminLayout.vue";
import Swal from "sweetalert2";

const props = defineProps({
    barangs: { type: Array, default: () => [] }
});

const searchQuery = ref("");
const showModal = ref(false);
const isEdit = ref(false);

const form = useForm({
    id_barang: null,
    nama_barang: "",
    satuan: "Pcs",
    status: "Aktif",
    min_stock: 0,
    max_stock: 0,
});

const filteredData = computed(() => {
    return props.barangs.filter(item => 
        item.nama_barang.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const openModal = (barang = null) => {
    if (barang) {
        isEdit.value = true;
        form.id_barang = barang.id_barang;
        form.nama_barang = barang.nama_barang;
        form.satuan = barang.satuan;
        form.status = barang.status;
        form.min_stock = barang.min_stock || 0;
        form.max_stock = barang.max_stock || 0;
    } else {
        isEdit.value = false;
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.barang.update', form.id_barang), {
            onSuccess: () => {
                Swal.fire("Berhasil", "Data barang diperbarui", "success");
                closeModal();
            },
            onError: () => {
                Swal.fire("Gagal", "Terjadi kesalahan", "error");
            }
        });
    } else {
        form.post(route('admin.barang.store'), {
            onSuccess: () => {
                Swal.fire("Berhasil", "Data barang ditambahkan", "success");
                closeModal();
            },
            onError: () => {
                Swal.fire("Gagal", "Terjadi kesalahan", "error");
            }
        });
    }
};

const deleteBarang = (id) => {
    Swal.fire({
        title: 'Hapus Barang?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route('admin.barang.destroy', id), {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Barang telah dihapus.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Setting Barang" />
    <AdminLayout>
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Setting Barang</h1>
                <p class="text-sm text-gray-500">Kelola master data barang beserta limit stok.</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <input 
                    v-model="searchQuery" 
                    type="text" 
                    placeholder="Cari barang..." 
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:ring-indigo-500 focus:border-indigo-500"
                />
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">
                        <tr>
                            <th class="px-6 py-4">No</th>
                            <th class="px-6 py-4">Nama Barang</th>
                            <th class="px-6 py-4">Satuan</th>
                            <th class="px-6 py-4">Min Stock</th>
                            <th class="px-6 py-4">Max Stock</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(item, idx) in filteredData" :key="item.id_barang" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 text-gray-500">{{ idx + 1 }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ item.nama_barang }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ item.satuan }}</td>
                            <td class="px-6 py-4 text-gray-600 font-medium">{{ item.min_stock }}</td>
                            <td class="px-6 py-4 text-gray-600 font-medium">{{ item.max_stock }}</td>
                            <td class="px-6 py-4">
                                <span :class="item.status === 'Aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded text-xs font-bold">
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center space-x-3">
                                <button @click="openModal(item)" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                                <button @click="deleteBarang(item.id_barang)" class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="filteredData.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">Tidak ada data ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-800">{{ isEdit ? 'Edit Barang' : 'Tambah Barang' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-red-500">&times;</button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Barang</label>
                            <input v-model="form.nama_barang" type="text" class="w-full border-gray-300 rounded-lg" required />
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Satuan</label>
                                <input v-model="form.satuan" type="text" class="w-full border-gray-300 rounded-lg" required />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                                <select v-model="form.status" class="w-full border-gray-300 rounded-lg">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Min Stock</label>
                                <input v-model="form.min_stock" type="number" min="0" class="w-full border-gray-300 rounded-lg" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Max Stock</label>
                                <input v-model="form.max_stock" type="number" min="0" class="w-full border-gray-300 rounded-lg" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700" :disabled="form.processing">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
