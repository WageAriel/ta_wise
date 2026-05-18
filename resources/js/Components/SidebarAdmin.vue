<script setup>
import { computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";

const user = computed(() => usePage().props.auth.user);
const logout = () => router.post(route("logout"));
const isActive = (routeName) => route().current(routeName);

// --- Definisi Ikon SVG ---
const icons = {
    dashboard: `<g clip-path="url(#clip0_320_683)"><path d="M5 12H3L12 3L21 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12V19C5 19.5304 5.21071 20.0391 5.58579 20.4142C5.96086 20.7893 6.46957 21 7 21H17C17.5304 21 18.0391 20.7893 18.4142 20.4142C18.7893 20.0391 19 19.5304 19 19V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 21V15C9 14.4696 9.21071 13.9609 9.58579 13.5858C9.96086 13.2107 10.4696 13 11 13H13C13.5304 13 14.0391 13.2107 14.4142 13.5858C14.7893 13.9609 15 14.4696 15 15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_683"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    supplierData: `<g clip-path="url(#clip0_320_430)"><path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 21V19C6 17.9391 6.42143 16.9217 7.17157 16.1716C7.92172 15.4214 8.93913 15 10 15H14C15.0609 15 16.0783 15.4214 16.8284 16.1716C17.5786 16.9217 18 17.9391 18 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_430"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    selection: `<g clip-path="url(#clip0_320_399)"><path d="M14 3V7C14 7.26522 14.1054 7.51957 14.2929 7.70711C14.4804 7.89464 14.7348 8 15 8H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 21H7C6.46957 21 5.96086 20.7893 5.58579 20.4142C5.21071 20.0391 5 19.5304 5 19V5C5 4.46957 5.21071 3.96086 5.58579 3.58579C5.96086 3.21071 6.46957 3 7 3H14L19 8V19C19 19.5304 18.7893 20.0391 18.4142 20.4142C18.0391 20.7893 17.5304 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 17H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 13H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_399"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    classification: `<g clip-path="url(#clip0_320_274)"><path d="M14 3V7C14 7.26522 14.1054 7.51957 14.2929 7.70711C14.4804 7.89464 14.7348 8 15 8H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 21H7C6.46957 21 5.96086 20.7893 5.58579 20.4142C5.21071 20.0391 5 19.5304 5 19V5C5 4.46957 5.21071 3.96086 5.58579 3.58579C5.96086 3.21071 6.46957 3 7 3H14L19 8V19C19 19.5304 18.7893 20.0391 18.4142 20.4142C18.0391 20.7893 17.5304 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 15L11 17L15 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_274"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    fieldOfficers: `<g clip-path="url(#clip0_320_444)"><path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 21V19C3 17.9391 3.42143 16.9217 4.17157 16.1716C4.92172 15.4214 5.93913 15 7 15H11C12.0609 15 13.0783 15.4214 13.8284 16.1716C14.5786 16.9217 15 17.9391 15 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21V19C20.9949 18.1172 20.6979 17.2608 20.1553 16.5644C19.6126 15.868 18.8548 15.3707 18 15.15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_444"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    po: `<g clip-path="url(#clip0_320_1289)"><path d="M6 21C7.10457 21 8 20.1046 8 19C8 17.8954 7.10457 17 6 17C4.89543 17 4 17.8954 4 19C4 20.1046 4.89543 21 6 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 21C18.1046 21 19 20.1046 19 19C19 17.8954 18.1046 17 17 17C15.8954 17 15 17.8954 15 19C15 20.1046 15.8954 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 17H6V3H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 5L20 6L19 13H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_1289"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    inbound: `<g clip-path="url(#clip0_320_311)"><path d="M12 21L4 16.5V7.5L12 3L20 7.5V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12L20 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12L4 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 18H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 15L15 18L18 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_311"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    inventory: `<g clip-path="url(#clip0_320_468)"><path d="M12 3L20 7.5V16.5L12 21L4 16.5V7.5L12 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12L20 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12L4 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 5.25L8 9.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_468"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    returnManagement: `<g clip-path="url(#clip0_320_569)"><path d="M8.806 4.797L12 3L20 7.5V16M17.765 17.757L12 21L4 16.5V7.5L6.236 6.242L17.765 17.757Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14.561 10.559L20 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12L4 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 3L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_569"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    outbound: `<g clip-path="url(#clip0_320_1197)"><path d="M12 21L4 16.5V7.5L12 3L20 7.5V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12L20 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12L4 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 18H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 15L22 18L19 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_1197"><rect width="24" height="24" fill="white"/></clipPath></defs>`,

    userManagement: `<g clip-path="url(#clip0_320_491)"><path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.16797 18.849C6.41548 18.0252 6.92194 17.3032 7.61222 16.79C8.30249 16.2768 9.13982 15.9997 9.99997 16H14C14.8612 15.9997 15.6996 16.2774 16.3904 16.7918C17.0811 17.3062 17.5874 18.0298 17.834 18.855" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_320_491"><rect width="24" height="24" fill="white"/></clipPath></defs>`,
};
</script>

<template>
    <aside
        class="flex flex-col w-64 h-screen sticky top-0 bg-white text-black border-r border-gray-200 shadow-sm"
    >
        <!-- Brand (Bagian Atas - Tetap) -->
        <div
            class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 shrink-0"
        >
            <div
                class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-black text-white text-sm shadow-sm shadow-blue-200"
            >
                W
            </div>
            <span class="font-bold text-lg tracking-widest text-gray-800"
                >WISE</span
            >
        </div>

        <!-- Menu Navigation (Bagian Tengah - Bisa di-scroll) -->
        <nav
            class="flex-1 overflow-y-auto px-4 py-6 space-y-7 custom-scrollbar"
        >
            <!-- Dashboard -->
            <div>
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 group"
                    :class="
                        isActive('dashboard')
                            ? 'text-blue-600 bg-blue-50'
                            : 'text-black hover:text-blue-600 hover:bg-blue-50'
                    "
                >
                    <svg
                        class="w-5 h-5 transition-colors shrink-0"
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
                            'admin.supplier.index': {
                                label: 'Supplier Data',
                                icon: 'supplierData',
                            },
                            'admin.supplier.selection': {
                                label: 'Supplier Selection',
                                icon: 'selection',
                            },
                            'admin.supplier.classification': {
                                label: 'Classification Supplier',
                                icon: 'classification',
                            },
                            'admin.field-officers': {
                                label: 'Field Officers',
                                icon: 'fieldOfficers',
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
                            class="w-5 h-5 transition-colors shrink-0"
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
                        :href="route('admin.purchase-orders')"
                        class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-all duration-200 group"
                        :class="
                            isActive('admin.purchase-orders')
                                ? 'text-blue-600 font-semibold bg-blue-50'
                                : 'text-black hover:text-blue-600 hover:bg-blue-50'
                        "
                    >
                        <svg
                            class="w-5 h-5 transition-colors shrink-0"
                            :class="
                                isActive('admin.purchase-orders')
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

            <!-- GROUP: Warehouse Operations -->
            <div>
                <p
                    class="px-3 text-xs font-bold text-gray-400 uppercase tracking-widest mb-3"
                >
                    Warehouse Operations
                </p>
                <div class="space-y-1">
                    <Link
                        v-for="(val, rName) in {
                            'admin.inbound': {
                                label: 'Inbound',
                                icon: 'inbound',
                            },
                            'admin.inventory': {
                                label: 'Inventory',
                                icon: 'inventory',
                            },
                            'admin.return-management': {
                                label: 'Return Management',
                                icon: 'returnManagement',
                            },
                            'admin.outbound': {
                                label: 'Outbound',
                                icon: 'outbound',
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
                            class="w-5 h-5 transition-colors shrink-0"
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

            <!-- GROUP: System -->
            <div>
                <p
                    class="px-3 text-xs font-bold text-gray-400 uppercase tracking-widest mb-3"
                >
                    System
                </p>
                <div class="space-y-1">
                    <Link
                        :href="route('admin.user-management')"
                        class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-all duration-200 group"
                        :class="
                            isActive('admin.user-management')
                                ? 'text-blue-600 font-semibold bg-blue-50'
                                : 'text-black hover:text-blue-600 hover:bg-blue-50'
                        "
                    >
                        <svg
                            class="w-5 h-5 transition-colors shrink-0"
                            :class="
                                isActive('admin.user-management')
                                    ? 'text-blue-600'
                                    : 'text-gray-400 group-hover:text-blue-600'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            v-html="icons.userManagement"
                        ></svg>
                        <span>User Management</span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Logout Section (Bagian Bawah - Tetap) -->
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
