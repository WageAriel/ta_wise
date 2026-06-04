<script setup>
import SidebarAdmin from "../Components/SidebarAdmin.vue";
import { usePage, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const showDropdown = ref(false);

const closeDropdown = (e) => {
    if (!e.target.closest('.user-menu-container')) {
        showDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});
</script>

<template>
    <div class="flex h-screen bg-gray-50 font-sans">
        <!-- Sidebar Admin -->
        <SidebarAdmin />

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Topbar / Header -->
            <header class="bg-white border-b border-gray-100 flex items-center justify-end px-6 py-2 shadow-sm z-10">
                <div class="relative user-menu-container">
                    <button @click="showDropdown = !showDropdown" class="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-1.5 rounded-xl transition-colors focus:outline-none">
                        <div class="text-right hidden sm:block leading-tight">
                            <p class="text-[13px] font-bold text-gray-800">{{ user?.name || 'Administrator' }}</p>
                            <p class="text-[11px] font-medium text-gray-400">{{ user?.email || 'admin@ta-wise.com' }}</p>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm border-2 border-white shadow-sm uppercase">
                            {{ (user?.name || 'A').charAt(0) }}
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400" :class="{'rotate-180': showDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 0.2s;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <!-- Dropdown -->
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <div v-if="showDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-20">
                            <div class="p-3 border-b border-gray-50 block sm:hidden">
                                <p class="text-sm font-bold text-gray-800">{{ user?.name || 'Administrator' }}</p>
                                <p class="text-xs font-medium text-gray-400">{{ user?.email || 'admin@ta-wise.com' }}</p>
                            </div>
                            <Link :href="route('profile.edit')" class="px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600 font-medium transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Profile
                            </Link>
                        </div>
                    </transition>
                </div>
            </header>

            <!-- Page Content -->
            <main
                class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6"
            >
                <slot />
            </main>
        </div>
    </div>
</template>
