<script setup>
defineProps({
    criticalItems: Number,
    lowStockItems: Number
});
defineEmits(['close', 'viewAlerts']);
</script>

<template>
    <div class="mb-6 bg-gradient-to-r from-red-900/30 via-yellow-900/30 to-orange-900/30 border border-red-500/40 shadow-lg rounded-xl overflow-hidden relative">
        <div class="p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4 flex-1">
                    <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center flex-shrink-0 animate-pulse">
                        <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="text-white font-bold" style="font-size: 16px;">
                                Peringatan Stok Menipis!
                            </h3>
                            <span class="px-2 py-0.5 rounded-full bg-red-500/30 text-red-300 border border-red-500/50 text-xs font-semibold">
                                Memerlukan Tindakan
                            </span>
                        </div>
                        <p class="text-slate-300 mb-3" style="font-size: 14px;">
                            Terdapat {{ criticalItems + lowStockItems }} item dengan stok yang perlu ditinjau:
                        </p>
                        <div class="flex items-center gap-6 mb-4">
                            <div v-if="criticalItems > 0" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-red-500/20 flex items-center justify-center">
                                    <span class="text-red-400 font-bold" style="font-size: 14px;">{{ criticalItems }}</span>
                                </div>
                                <div>
                                    <p class="text-red-400 font-semibold" style="font-size: 13px;">Critical Stock</p>
                                    <p class="text-slate-400" style="font-size: 11px;">Stok kritis, segera order</p>
                                </div>
                            </div>
                            <div v-if="lowStockItems > 0" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                                    <span class="text-yellow-400 font-bold" style="font-size: 14px;">{{ lowStockItems }}</span>
                                </div>
                                <div>
                                    <p class="text-yellow-400 font-semibold" style="font-size: 13px;">Low Stock</p>
                                    <p class="text-slate-400" style="font-size: 11px;">Stok menipis, perlu restock</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button 
                                @click="$emit('viewAlerts')"
                                class="inline-flex items-center justify-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm transition-colors"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Tinjau Daftar Stok
                            </button>
                            <button 
                                @click="$emit('close')"
                                class="inline-flex items-center justify-center px-3 py-1.5 border border-white/20 text-slate-300 hover:bg-white/5 rounded-md text-sm transition-colors"
                            >
                                Tutup Notifikasi
                            </button>
                        </div>
                    </div>
                </div>
                <button @click="$emit('close')" class="text-slate-400 hover:text-white hover:bg-white/10 p-1 rounded-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
    </div>
</template>
