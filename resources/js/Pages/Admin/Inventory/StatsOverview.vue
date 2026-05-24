<script setup>
defineProps({
    totalItems: Number,
    totalValue: Number,
    lowStockItems: Number,
    criticalItems: Number
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 12px; color: #94a3b8; margin-bottom: 4px;">Total Items</p>
                    <p class="text-white" style="font-size: 24px; font-weight: 700;">{{ totalItems }}</p>
                    <p style="font-size: 11px; color: #10b981; margin-top: 4px;">
                        <svg class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        +12% from last month
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 12px; color: #94a3b8; margin-bottom: 4px;">Total Value</p>
                    <p class="text-white" style="font-size: 24px; font-weight: 700;">
                        Rp {{ (totalValue / 1000000).toFixed(1) }}M
                    </p>
                    <p style="font-size: 11px; color: #10b981; margin-top: 4px;">
                        <svg class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        +8% from last month
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 12px; color: #94a3b8; margin-bottom: 4px;">Low Stock Items</p>
                    <p class="text-white" style="font-size: 24px; font-weight: 700;">{{ lowStockItems }}</p>
                    <p style="font-size: 11px; color: #f59e0b; margin-top: 4px;">
                        <svg class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Requires attention
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-800/50 to-slate-900/50 border border-white/10 rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 12px; color: #94a3b8; margin-bottom: 4px;">Critical Stock</p>
                    <p class="text-white" style="font-size: 24px; font-weight: 700;">{{ criticalItems }}</p>
                    <p style="font-size: 11px; color: #ef4444; margin-top: 4px;">
                        <svg class="w-3 h-3 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Immediate action required
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
        </div>
    </div>
</template>
