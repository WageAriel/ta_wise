<script setup>
import { computed } from 'vue';

const props = defineProps({
    inventoryData: Array,
    criticalItems: Number,
    lowStockItems: Number,
    normalItems: Number
});

const topItems = computed(() => {
    return [...props.inventoryData].sort((a, b) => b.value - a.value).slice(0, 5);
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Placeholder for Line Chart -->
        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl p-6 shadow-sm">
            <h3 class="text-white text-lg font-bold mb-4">Stock Movement (Last 7 Days)</h3>
            <div class="h-[250px] flex items-end justify-between gap-2 border-b border-l border-slate-700 p-4">
                <div v-for="i in 7" :key="i" class="w-full flex justify-center items-end gap-1 relative group">
                    <div class="w-1/2 bg-emerald-500/80 hover:bg-emerald-400 rounded-t-sm transition-all" :style="{ height: Math.floor(Math.random() * 80 + 20) + '%' }"></div>
                    <div class="w-1/2 bg-red-500/80 hover:bg-red-400 rounded-t-sm transition-all" :style="{ height: Math.floor(Math.random() * 60 + 10) + '%' }"></div>
                    <span class="absolute -bottom-6 text-xs text-slate-400">Day {{ i }}</span>
                </div>
            </div>
            <div class="flex justify-center gap-6 mt-8">
                <div class="flex items-center gap-2"><div class="w-3 h-3 bg-emerald-500 rounded-full"></div><span class="text-slate-400 text-xs">Inbound</span></div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 bg-red-500 rounded-full"></div><span class="text-slate-400 text-xs">Outbound</span></div>
            </div>
        </div>

        <!-- Placeholder for Pie Chart -->
        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl p-6 shadow-sm">
            <h3 class="text-white text-lg font-bold mb-4">Inventory by Category</h3>
            <div class="h-[250px] flex items-center justify-center">
                <div class="w-48 h-48 rounded-full border-[16px] border-t-blue-500 border-r-indigo-500 border-b-purple-500 border-l-cyan-500 shadow-xl relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-slate-300 font-bold text-sm">Categories</span>
                    </div>
                </div>
            </div>
            <div class="flex justify-center gap-4 mt-6">
                <div class="flex items-center gap-2"><div class="w-3 h-3 bg-blue-500 rounded-full"></div><span class="text-slate-400 text-xs">Raw</span></div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 bg-indigo-500 rounded-full"></div><span class="text-slate-400 text-xs">Packaging</span></div>
                <div class="flex items-center gap-2"><div class="w-3 h-3 bg-purple-500 rounded-full"></div><span class="text-slate-400 text-xs">Additional</span></div>
            </div>
        </div>

        <!-- Stock Status Distribution -->
        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl p-6 shadow-sm">
            <h3 class="text-white text-lg font-bold mb-4">Stock Status Distribution</h3>
            <div class="h-[250px] flex items-end justify-around gap-6 border-b border-l border-slate-700 p-4">
                <!-- Normal -->
                <div class="w-1/3 flex flex-col items-center justify-end h-full">
                    <span class="text-slate-300 mb-2 font-bold">{{ normalItems }}</span>
                    <div class="w-full bg-blue-500 rounded-t-md transition-all hover:bg-blue-400" :style="{ height: (normalItems / inventoryData.length * 100) + '%' }"></div>
                    <span class="mt-2 text-xs text-slate-400">Normal</span>
                </div>
                <!-- Low -->
                <div class="w-1/3 flex flex-col items-center justify-end h-full">
                    <span class="text-slate-300 mb-2 font-bold">{{ lowStockItems }}</span>
                    <div class="w-full bg-yellow-500 rounded-t-md transition-all hover:bg-yellow-400" :style="{ height: (lowStockItems / inventoryData.length * 100) + '%' }"></div>
                    <span class="mt-2 text-xs text-slate-400">Low Stock</span>
                </div>
                <!-- Critical -->
                <div class="w-1/3 flex flex-col items-center justify-end h-full">
                    <span class="text-slate-300 mb-2 font-bold">{{ criticalItems }}</span>
                    <div class="w-full bg-red-500 rounded-t-md transition-all hover:bg-red-400" :style="{ height: (criticalItems / inventoryData.length * 100) + '%' }"></div>
                    <span class="mt-2 text-xs text-slate-400">Critical</span>
                </div>
            </div>
        </div>

        <!-- Top Items by Value -->
        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl p-6 shadow-sm">
            <h3 class="text-white text-lg font-bold mb-4">Top Items by Value</h3>
            <div class="space-y-4 mt-4">
                <div v-for="(item, index) in topItems" :key="item.id" class="flex items-center justify-between p-3 rounded-lg bg-slate-800/30 hover:bg-slate-800 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center border border-blue-500/30">
                            <span class="text-blue-400 font-semibold" style="font-size: 12px;">{{ index + 1 }}</span>
                        </div>
                        <div>
                            <p class="text-white font-medium" style="font-size: 14px;">{{ item.name }}</p>
                            <p class="text-slate-400" style="font-size: 12px;">{{ item.currentStock }} {{ item.unit }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-emerald-400 font-bold" style="font-size: 14px;">
                            Rp {{ (item.value / 1000000).toFixed(1) }}M
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
