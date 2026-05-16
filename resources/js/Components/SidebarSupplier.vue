<script setup>
import { computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";

const user = computed(() => usePage().props.auth.user);
const logout = () => router.post(route("logout"));
const isActive = (routeName) => route().current(routeName);

// --- Definisi Ikon SVG ---
const icons = {
    dashboard: `
        <g clip-path="url(#clip0_320_683)">
            <path d="M5 12H3L12 3L21 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 12V19C5 19.5304 5.21071 20.0391 5.58579 20.4142C5.96086 20.7893 6.46957 21 7 21H17C17.5304 21 18.0391 20.7893 18.4142 20.4142C18.7893 20.0391 19 19.5304 19 19V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 21V15C9 14.4696 9.21071 13.9609 9.58579 13.5858C9.96086 13.2107 10.4696 13 11 13H13C13.5304 13 14.0391 13.2107 14.4142 13.5858C14.7893 13.9609 15 14.4696 15 15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </g>
        <defs><clipPath id="clip0_320_683"><rect width="24" height="24" fill="white"/></clipPath></defs>
    `,

    profile:
        '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />',

    selection: `
        <g clip-path="url(#clip0_320_1385)">
            <path d="M14 3V7C14 7.26522 14.1054 7.51957 14.2929 7.70711C14.4804 7.89464 14.7348 8 15 8H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M17 21H7C6.46957 21 5.96086 20.7893 5.58579 20.4142C5.21071 20.0391 5 19.5304 5 19V5C5 4.46957 5.21071 3.96086 5.58579 3.58579C5.96086 3.21071 6.46957 3 7 3H14L19 8V19C19 19.5304 18.7893 20.0391 18.4142 20.4142C18.0391 20.7893 17.5304 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 17H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 13H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </g>
        <defs><clipPath id="clip0_320_1385"><rect width="24" height="24" fill="white"/></clipPath></defs>
    `,

    classification: `
        <g clip-path="url(#clip0_320_1393)">
            <path d="M14 3V7C14 7.26522 14.1054 7.51957 14.2929 7.70711C14.4804 7.89464 14.7348 8 15 8H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M17 21H7C6.46957 21 5.96086 20.7893 5.58579 20.4142C5.21071 20.0391 5 19.5304 5 19V5C5 4.46957 5.21071 3.96086 5.58579 3.58579C5.96086 3.21071 6.46957 3 7 3H14L19 8V19C19 19.5304 18.7893 20.0391 18.4142 20.4142C18.0391 20.7893 17.5304 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 15L11 17L15 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </g>
        <defs><clipPath id="clip0_320_1393"><rect width="24" height="24" fill="white"/></clipPath></defs>
    `,

    timeline:
        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',

    po: `
        <g clip-path="url(#clip0_320_407)">
            <path d="M6 21C7.10457 21 8 20.1046 8 19C8 17.8954 7.10457 17 6 17C4.89543 17 4 17.8954 4 19C4 20.1046 4.89543 21 6 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M17 21C18.1046 21 19 20.1046 19 19C19 17.8954 18.1046 17 17 17C15.8954 17 15 17.8954 15 19C15 20.1046 15.8954 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M17 17H6V3H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6 5L20 6L19 13H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </g>
        <defs><clipPath id="clip0_320_407"><rect width="24" height="24" fill="white"/></clipPath></defs>
    `,
};
</script>

<template>
    <aside
        class="flex flex-col w-64 min-h-screen bg-white text-black border-r border-gray-200 shadow-sm"
    >
        <!-- Brand -->
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-50">
            <div
                class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-black text-white text-sm shadow-sm shadow-blue-200"
            >
                W
            </div>
            <span class="font-bold text-lg tracking-widest text-gray-800"
                >WISE</span
            >
        </div>

        <!-- Menu Navigation -->
        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-7">
            <!-- Dashboard -->
            <div>
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 group"
                    :class="
                        isActive('dashboard')
                            ? 'text-blue-600 bg-blue-50'
                            : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50'
                    "
                >
                    <svg
                        class="w-5 h-5 transition-colors"
                        :class="
                            isActive('dashboard')
                                ? 'text-blue-600'
                                : 'text-gray-400 group-hover:text-blue-600'
                        "
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        v-html="icons.dashboard"
                    ></svg>
                    <span>Dashboard</span>
                </Link>
            </div>

            <!-- GROUP: Supplier Management -->
            <div>
                <p
                    class="px-3 text-xs font-bold text-gray-400 uppercase tracking-widest mb-3"
                >
                    Supplier Management
                </p>
                <div class="space-y-1">
                    <Link
                        v-for="(val, rName) in {
                            'supplier.data': {
                                label: 'Profil Perusahaan',
                                icon: 'profile',
                            },
                            'supplier.selection': {
                                label: 'Supplier Selection',
                                icon: 'selection',
                            },
                            'supplier.classification': {
                                label: 'Classification Supplier',
                                icon: 'classification',
                            },
                            'supplier.timeline': {
                                label: 'Timeline Pengajuan',
                                icon: 'timeline',
                            },
                        }"
                        :key="rName"
                        :href="route(rName)"
                        class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-all duration-200 group"
                        :class="
                            isActive(rName)
                                ? 'text-blue-600 font-semibold bg-blue-50'
                                : 'text-black hover:text-blue-600 hover:bg-blue-50'
                        "
                    >
                        <svg
                            class="w-5 h-5 transition-colors"
                            :class="
                                isActive(rName)
                                    ? 'text-blue-600'
                                    : 'text-gray-400 group-hover:text-blue-600'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            v-html="icons[val.icon]"
                        ></svg>
                        <span>{{ val.label }}</span>
                    </Link>
                </div>
            </div>

            <!-- GROUP: Purchase Order -->
            <div>
                <p
                    class="px-3 text-xs font-bold text-gray-400 uppercase tracking-widest mb-3"
                >
                    Purchase Order
                </p>
                <div class="space-y-1">
                    <Link
                        :href="route('supplier.purchase-orders.index')"
                        class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-all duration-200 group"
                        :class="
                            isActive('supplier.purchase-orders.index')
                                ? 'text-blue-600 font-semibold bg-blue-50'
                                : 'text-black hover:text-blue-600 hover:bg-blue-50'
                        "
                    >
                        <svg
                            class="w-5 h-5 transition-colors"
                            :class="
                                isActive('supplier.purchase-orders.index')
                                    ? 'text-blue-600'
                                    : 'text-gray-400 group-hover:text-blue-600'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            v-html="icons.po"
                        ></svg>
                        <span>Purchase Orders</span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Logout Section -->
        <div class="px-4 py-6 border-t border-gray-100">
            <button
                @click="logout"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 transition-colors group"
            >
                <svg
                    class="w-5 h-5 text-red-400 group-hover:text-red-500"
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
