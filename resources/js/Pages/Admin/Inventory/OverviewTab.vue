<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    inventoryData: Array
});

const searchQuery = ref("");
const selectedCategory = ref("all");

const filteredData = computed(() => {
    return props.inventoryData.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              item.id.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              item.supplier.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesCategory = selectedCategory.value === 'all' || item.category === selectedCategory.value;
        return matchesSearch && matchesCategory;
    });
});

const getStatusClass = (status) => {
    if (status === 'normal') return 'bg-green-500/20 text-green-400 border border-green-500/30';
    if (status === 'low') return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30';
    if (status === 'critical') return 'bg-red-500/20 text-red-400 border border-red-500/30';
    return 'bg-slate-500/20 text-slate-400 border border-slate-500/30';
};
</script>

<template>
    <div>
        <!-- Search and Filter -->
        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 mb-6 rounded-xl shadow-sm">
            <div class="p-4">
                <div class="flex gap-3 flex-col sm:flex-row">
                    <div class="flex-1 relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input
                            type="text"
                            placeholder="Search by item name, ID, or supplier..."
                            v-model="searchQuery"
                            class="w-full pl-10 py-2 rounded-md bg-slate-900/50 border border-white/10 text-white text-sm focus:ring-blue-500 focus:border-blue-500 transition-colors placeholder:text-slate-500"
                        />
                    </div>
                    <select
                        v-model="selectedCategory"
                        class="px-4 py-2 rounded-md bg-slate-900/50 border border-white/10 text-white text-sm focus:ring-blue-500 focus:border-blue-500"
                        style="min-width: 200px;"
                    >
                        <option value="all">All Categories</option>
                        <option value="Raw Materials">Raw Materials</option>
                        <option value="Additional Materials">Additional Materials</option>
                        <option value="Packaging">Packaging</option>
                    </select>
                    <button class="inline-flex items-center px-4 py-2 border border-white/10 text-slate-300 hover:bg-white/5 rounded-md text-sm transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-white/10">
                <h3 class="text-white text-lg font-bold">Inventory Items ({{ filteredData.length }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Item ID</th>
                            <th class="text-left py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Item Name</th>
                            <th class="text-left py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Category</th>
                            <th class="text-left py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Supplier</th>
                            <th class="text-center py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Current Stock</th>
                            <th class="text-center py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Min/Max</th>
                            <th class="text-left py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Location</th>
                            <th class="text-center py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Status</th>
                            <th class="text-left py-3 px-6 text-slate-400 font-medium" style="font-size: 12px;">Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in filteredData" :key="item.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-4 px-6">
                                <span class="text-blue-400 font-medium" style="font-size: 13px;">{{ item.id }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-white font-semibold" style="font-size: 13px;">{{ item.name }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-1 rounded-full border border-white/20 text-slate-300 text-xs">
                                    {{ item.category }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-slate-300" style="font-size: 12px;">{{ item.supplier }}</span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-white font-semibold" style="font-size: 13px;">
                                    {{ item.currentStock }} {{ item.unit }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-slate-400" style="font-size: 12px;">
                                    {{ item.minStock }} / {{ item.maxStock }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-slate-300" style="font-size: 12px;">{{ item.location }}</span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold capitalize" :class="getStatusClass(item.status)">
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-slate-400" style="font-size: 12px;">{{ item.lastUpdate }}</span>
                            </td>
                        </tr>
                        <tr v-if="filteredData.length === 0">
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                Tidak ada data yang sesuai.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
