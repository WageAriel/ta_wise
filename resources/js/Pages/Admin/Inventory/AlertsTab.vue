<script setup>
import { computed } from 'vue';

const props = defineProps({
    inventoryData: Array
});

const emit = defineEmits(['openCreatePO', 'openPlanRestock']);

const criticalItems = computed(() => props.inventoryData.filter(i => i.status === 'critical'));
const lowStockItems = computed(() => props.inventoryData.filter(i => i.status === 'low'));
</script>

<template>
    <div class="grid grid-cols-1 gap-4">
        <!-- Critical Stock Alerts -->
        <div v-for="item in criticalItems" :key="item.id" class="bg-gradient-to-br from-red-900/20 to-red-800/10 border border-red-500/30 rounded-xl overflow-hidden shadow-sm">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 border border-red-500/30 text-xs font-bold tracking-wider">CRITICAL</span>
                                <span class="text-red-400 font-medium text-sm">{{ item.id }}</span>
                            </div>
                            <h3 class="text-white font-semibold mb-1 text-base">{{ item.name }}</h3>
                            <p class="text-slate-300 mb-2 text-sm">
                                Current stock: <span class="text-red-400 font-semibold">{{ item.currentStock }} {{ item.unit }}</span> |
                                Minimum required: {{ item.minStock }} {{ item.unit }}
                            </p>
                            <p class="text-slate-400 text-xs">
                                Location: {{ item.location }} &bull; Supplier: {{ item.supplier }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="$emit('openCreatePO', item)"
                        class="w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm font-medium transition-colors whitespace-nowrap shadow-sm shadow-red-900/50"
                    >
                        Create PO
                    </button>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div v-for="item in lowStockItems" :key="item.id" class="bg-gradient-to-br from-yellow-900/20 to-yellow-800/10 border border-yellow-500/30 rounded-xl overflow-hidden shadow-sm">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 text-xs font-bold tracking-wider">LOW STOCK</span>
                                <span class="text-yellow-400 font-medium text-sm">{{ item.id }}</span>
                            </div>
                            <h3 class="text-white font-semibold mb-1 text-base">{{ item.name }}</h3>
                            <p class="text-slate-300 mb-2 text-sm">
                                Current stock: <span class="text-yellow-400 font-semibold">{{ item.currentStock }} {{ item.unit }}</span> |
                                Minimum required: {{ item.minStock }} {{ item.unit }}
                            </p>
                            <p class="text-slate-400 text-xs">
                                Location: {{ item.location }} &bull; Supplier: {{ item.supplier }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="$emit('openPlanRestock', item)"
                        class="w-full sm:w-auto px-4 py-2 bg-transparent border border-yellow-500/50 text-yellow-400 hover:bg-yellow-500/10 rounded-md text-sm font-medium transition-colors whitespace-nowrap"
                    >
                        Plan Restock
                    </button>
                </div>
            </div>
        </div>

        <div v-if="criticalItems.length === 0 && lowStockItems.length === 0" class="p-8 text-center bg-slate-800/30 rounded-xl border border-white/5">
            <svg class="w-12 h-12 text-slate-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3 class="text-white font-medium text-lg">Semua Stok Aman</h3>
            <p class="text-slate-400 text-sm mt-1">Tidak ada peringatan stok kritis atau menipis saat ini.</p>
        </div>
    </div>
</template>
