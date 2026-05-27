<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    }
});

// State
const searchQuery = ref('');
const filterRole = ref('');
const perPage = ref(10);
const showModal = ref(false);
const showConfirmDelete = ref(false);
const selectedUser = ref(null);

// Filter Logic
const filteredUsers = computed(() => {
    return props.users.filter(user => {
        const matchesSearch = user.username?.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             user.email?.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesRole = !filterRole.value || user.role === filterRole.value;
        
        return matchesSearch && matchesRole;
    });
});

const handleView = (user) => {
    selectedUser.value = user;
    showModal.value = true;
};

const handleDelete = (user) => {
    selectedUser.value = user;
    showConfirmDelete.value = true;
};

const confirmDelete = () => {
    if (selectedUser.value) {
        router.delete(route('admin.user-management.destroy', selectedUser.value.id), {
            onSuccess: () => {
                showConfirmDelete.value = false;
                selectedUser.value = null;
            }
        });
    }
};

const getRoleBadge = (role) => {
    switch (role?.toLowerCase()) {
        case 'admin': return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'supplier': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'petugas_lapangan': return 'bg-amber-100 text-amber-700 border-amber-200';
        default: return 'bg-red-100 text-red-700 border-red-200';
    }
};
</script>

<template>
    <Head title="User Management | Admin" />

    <AdminLayout>
        <!-- Header Page -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">User Management</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola akun pengguna dan hak akses sistem.</p>
        </div>

        <!-- Action Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-800">User Management</h2>
                <p class="text-xs text-slate-400 font-medium">Daftar Pengguna Sistem</p>
            </div>
        </div>

        <!-- Filters & Search Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <!-- Search Input -->
            <div class="relative w-full md:flex-1">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input 
                    v-model="searchQuery"
                    type="text" 
                    placeholder="Cari username atau email..." 
                    class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 shadow-sm"
                />
            </div>

            <!-- Role Filter -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-400">Role</span>
                    <select v-model="filterRole" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm">
                        <option value="">Semua Role</option>
                        <option value="admin">Admin</option>
                        <option value="supplier">Supplier</option>
                        <option value="petugas_lapangan">Petugas Lapangan</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-400">Tampilkan</span>
                    <select v-model="perPage" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm">
                        <option :value="10">10 Data</option>
                        <option :value="25">25 Data</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-[32px] border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-16">No</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Username</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Email Address</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Role</th>
                            <th class="py-5 px-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="(user, idx) in filteredUsers.slice(0, perPage)" :key="user.id" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 px-6 text-center text-xs font-bold text-slate-400">{{ idx + 1 }}</td>
                            <td class="py-4 px-6 text-xs font-black text-slate-900">{{ user.username }}</td>
                            <td class="py-4 px-6 text-xs font-bold text-slate-500">{{ user.email }}</td>
                            <td class="py-4 px-6 text-center">
                                <span :class="getRoleBadge(user.role)" class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border shadow-sm">
                                    {{ user.role?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="handleView(user)" class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button @click="handleDelete(user)" class="p-2 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Empty State -->
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="5" class="py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4 border border-slate-100 text-slate-200">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    </div>
                                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">User Tidak Ditemukan</h3>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">Silakan gunakan kata kunci pencarian lain.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>

    <!-- Modal Detail User -->
    <div v-if="showModal" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

        <div class="bg-white rounded-[32px] overflow-hidden shadow-2xl transform transition-all sm:max-w-xs w-full border border-slate-100/50 relative">
            <!-- Close Button (Absolute) -->
            <button @click="showModal = false" class="absolute top-6 right-6 p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400 z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Modal Content -->
            <div class="p-8 pt-12 text-center">
                <!-- Avatar Section -->
                <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-50 rounded-[28px] mb-6 ring-8 ring-indigo-50/50">
                    <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>

                <!-- User Info -->
                <div class="space-y-1 mb-6">
                    <h3 class="text-xl font-black text-slate-900 leading-tight">{{ selectedUser?.username }}</h3>
                    <p class="text-xs font-bold text-slate-400 tracking-tight">{{ selectedUser?.email }}</p>
                </div>

                <!-- Role Badge -->
                <div class="flex justify-center mb-8">
                    <span :class="getRoleBadge(selectedUser?.role)" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border shadow-sm">
                        {{ selectedUser?.role?.replace('_', ' ') }}
                    </span>
                </div>

                <!-- Action -->
                <button @click="showModal = false" class="w-full py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white hover:bg-black shadow-lg shadow-slate-200 transition-all active:scale-95 leading-none">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Confirm Delete -->
    <div v-if="showConfirmDelete" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showConfirmDelete = false"></div>

        <div class="bg-white rounded-[32px] overflow-hidden shadow-2xl transform transition-all sm:max-w-sm w-full border border-slate-100">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-rose-50 rounded-[28px] flex items-center justify-center mx-auto mb-6 ring-8 ring-rose-50/50">
                    <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                
                <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight mb-2">Hapus Pengguna?</h3>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">
                    Tindakan ini tidak dapat dibatalkan. Akun <span class="font-bold text-slate-900">{{ selectedUser?.username }}</span> akan dihapus permanen.
                </p>
            </div>

            <div class="px-8 pb-8 flex gap-3">
                <button @click="showConfirmDelete = false" class="flex-1 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">
                    Batal
                </button>
                <button @click="confirmDelete" class="flex-1 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest bg-rose-500 text-white hover:bg-rose-600 shadow-lg shadow-rose-200 transition-all active:scale-95">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-size: 1.5em 1.5em;
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    appearance: none;
}
</style>
