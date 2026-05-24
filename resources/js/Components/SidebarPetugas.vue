<script setup>
import { computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";

const user = computed(() => usePage().props.auth.user);
const logout = () => router.post(route("logout"));
const isActive = (routeName) => {
    try {
        return route().current(routeName);
    } catch (e) {
        return false;
    }
};

// --- Definisi Ikon SVG ---
const icons = {
    dashboard: `<g clip-path="url(#clip0_320_683)"><path d="M5 12H3L12 3L21 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12V19C5 19.5304 5.21071 20.0391 5.58579 20.4142C5.96086 20.7893 6.46957 21 7 21H17C17.5304 21 18.0391 20.7893 18.4142 20.4142C18.7893 20.0391 19 19.5304 19 19V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 21V15C9 14.4696 9.21071 13.9609 9.58579 13.5858C9.96086 13.2107 10.4696 13 11 13H13C13.5304 13 14.0391 13.2107 14.4142 13.5858C14.7893 13.9609 15 14.4696 15 15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g>`,

    jadwal: `<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />`,

    verifikasiAktif: `<path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />`,

    riwayat: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`,

    laporan: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />`
};

// Pastikan untuk mendaftarkan nama-nama route ini di web.php nantinya
const menus = [
    { name: 'petugas.dashboard', label: 'Dashboard', icon: 'dashboard', href: route().has('petugas.dashboard') ? route('petugas.dashboard') : '#' },
    { name: 'petugas.jadwal', label: 'Jadwal Verifikasi', icon: 'jadwal', href: route().has('petugas.jadwal') ? route('petugas.jadwal') : '#' },
    { name: 'petugas.verifikasi.aktif', label: 'Verifikasi Aktif', icon: 'verifikasiAktif', href: route().has('petugas.verifikasi.aktif') ? route('petugas.verifikasi.aktif') : '#' },
    { name: 'petugas.verifikasi.riwayat', label: 'Riwayat Verifikasi', icon: 'riwayat', href: route().has('petugas.verifikasi.riwayat') ? route('petugas.verifikasi.riwayat') : '#' },
    { name: 'petugas.laporan', label: 'Laporan Kinerja', icon: 'laporan', href: route().has('petugas.laporan') ? route('petugas.laporan') : '#' },
];
</script>

<template>
    <aside
        class="flex flex-col w-64 h-screen sticky top-0 bg-white text-black border-r border-gray-200 shadow-sm"
    >
        <!-- Brand -->
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 shrink-0">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-black text-white text-sm shadow-sm shadow-blue-200">
                W
            </div>
            <span class="font-bold text-lg tracking-widest text-gray-800">WISE</span>
        </div>

        <!-- Menu Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-2 custom-scrollbar">
            <div class="space-y-1">
                <Link
                    v-for="menu in menus"
                    :key="menu.name"
                    :href="menu.href"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 group"
                    :class="
                        isActive(menu.name)
                            ? 'text-blue-600 bg-blue-50 font-semibold'
                            : 'text-black hover:text-blue-600 hover:bg-blue-50 font-medium'
                    "
                >
                    <svg
                        class="w-5 h-5 transition-colors shrink-0"
                        :class="
                            isActive(menu.name)
                                ? 'text-blue-600'
                                : 'text-gray-400 group-hover:text-blue-600'
                        "
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        v-html="icons[menu.icon]"
                    ></svg>
                    <span>{{ menu.label }}</span>
                </Link>
            </div>
        </nav>

        <!-- Logout Section -->
        <div class="px-4 py-6 border-t border-gray-100 shrink-0">
            <button
                @click="logout"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 transition-colors group"
            >
                <svg
                    class="w-5 h-5 text-red-400 group-hover:text-red-500 transition-colors"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"
                    />
                </svg>
                Logout
            </button>
        </div>
    </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>
