<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const user = computed(() => usePage().props.auth.user);
const logout = () => router.post(route('logout'));
const isActive = (routeName) => route().current(routeName);

const icons = {
  dashboard: `<g clip-path="url(#clip0_320_683)"><path d="M5 12H3L12 3L21 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12V19C5 19.5304 5.21071 20.0391 5.58579 20.4142C5.96086 20.7893 6.46957 21 7 21H17C17.5304 21 18.0391 20.7893 18.4142 20.4142C18.7893 20.0391 19 19.5304 19 19V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 21V15C9 14.4696 9.21071 13.9609 9.58579 13.5858C9.96086 13.2107 10.4696 13 11 13H13C13.5304 13 14.0391 13.2107 14.4142 13.5858C14.7893 13.9609 15 14.4696 15 15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_683"><rect width="24" height="24" fill="white"/></clipPath></defs>`,
  po: `<g clip-path="url(#clip0_320_1289)"><path d="M6 21C7.10457 21 8 20.1046 8 19C8 17.8954 7.10457 17 6 17C4.89543 17 4 17.8954 4 19C4 20.1046 4.89543 21 6 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 21C18.1046 21 19 20.1046 19 19C19 17.8954 18.1046 17 17 17C15.8954 17 15 17.8954 15 19C15 20.1046 15.8954 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 17H6V3H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 5L20 6L19 13H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_1289"><rect width="24" height="24" fill="white"/></clipPath></defs>`,
  settings: `<g clip-path="url(#clip0_320_1393)"><path d="M12 3V5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4.93 6.93L6.34 8.34" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M17.66 17.66L19.07 19.07" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 12H5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4.93 17.07L6.34 15.66" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M17.66 6.34L19.07 4.93" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></g><defs><clipPath id="clip0_320_1393"><rect width="24" height="24" fill="white"/></clipPath></defs>`,
};
</script>

<template>
  <aside class="flex flex-col w-64 h-screen sticky top-0 bg-white text-black border-r border-gray-200 shadow-sm">
    <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 shrink-0">
      <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center font-black text-white text-sm shadow-sm shadow-emerald-200">
        W
      </div>
      <div>
        <p class="font-bold text-lg tracking-widest text-gray-800">WISE</p>
        <p class="text-[10px] text-gray-500">Manajer Gudang</p>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-7">
      <div>
        <Link
          :href="route('manajer.dashboard')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 group"
          :class="isActive('manajer.dashboard') ? 'text-emerald-600 bg-emerald-50' : 'text-gray-600 hover:text-emerald-600 hover:bg-emerald-50'"
        >
          <svg class="w-5 h-5" :class="isActive('manajer.dashboard') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-emerald-600'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" v-html="icons.dashboard"></svg>
          <span>Dashboard</span>
        </Link>
      </div>

      <div>
        <p class="px-3 text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Master</p>
        <div class="space-y-1">
          <Link
            :href="route('manajer.purchase-order-controller.index')"
            class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-all duration-200 group"
            :class="isActive('manajer.purchase-order-controller.index') ? 'text-emerald-600 font-semibold bg-emerald-50' : 'text-black hover:text-emerald-600 hover:bg-emerald-50'"
          >
            <svg class="w-5 h-5" :class="isActive('manajer.purchase-order-controller.index') ? 'text-emerald-600' : 'text-gray-400 group-hover:text-emerald-600'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" v-html="icons.settings"></svg>
            <span>PO Controller</span>
          </Link>
        </div>
      </div>
    </nav>

    <div class="px-4 py-6 border-t border-gray-100">
      <div class="mb-3 text-xs text-gray-500">Masuk sebagai</div>
      <div class="mb-4 text-sm font-semibold text-gray-700">{{ user?.username || 'Manajer' }}</div>
      <button @click="logout" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 transition-colors group">
        <svg class="w-5 h-5 text-red-400 group-hover:text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
        </svg>
        Logout
      </button>
    </div>
  </aside>
</template>
