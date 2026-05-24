<script setup>
import { ref } from 'vue';

const props = defineProps({
    item: Object
});
const emit = defineEmits(['close']);

const poQuantity = ref('');
const poDeliveryDate = ref('');
const poPriority = ref('high');
const poNotes = ref('');

const handleSubmit = () => {
    // Simulasi submit
    emit('close');
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 border border-white/10 rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-white/10 shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Create Purchase Order</h2>
                            <p class="text-slate-400 text-sm">Create a new purchase order for critical stock item</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="text-slate-400 hover:text-white p-2 rounded-lg hover:bg-white/5 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <div class="p-6 overflow-y-auto space-y-6 flex-1 custom-scrollbar">
                <!-- Item Info -->
                <div class="bg-slate-900/50 border border-white/10 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-slate-400 text-xs mb-1">Item ID</p>
                            <p class="text-blue-400 font-semibold">{{ item.id }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs mb-1">Item Name</p>
                            <p class="text-white font-semibold">{{ item.name }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs mb-1">Supplier</p>
                            <p class="text-white">{{ item.supplier }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs mb-1">Current Stock</p>
                            <p class="text-red-400 font-semibold">{{ item.currentStock }} {{ item.unit }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs mb-1">Min Stock</p>
                            <p class="text-white">{{ item.minStock }} {{ item.unit }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs mb-1">Max Stock</p>
                            <p class="text-white">{{ item.maxStock }} {{ item.unit }}</p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-slate-300 text-sm font-medium">Order Quantity ({{ item.unit }})</label>
                            <input 
                                v-model="poQuantity" 
                                type="number" 
                                class="w-full bg-slate-900/50 border border-white/10 rounded-md px-3 py-2 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                placeholder="Enter quantity"
                            />
                            <p class="text-xs text-slate-500">Suggested: {{ item.maxStock - item.currentStock }} {{ item.unit }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-slate-300 text-sm font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Expected Delivery Date
                            </label>
                            <input 
                                v-model="poDeliveryDate" 
                                type="date" 
                                class="w-full bg-slate-900/50 border border-white/10 rounded-md px-3 py-2 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 [color-scheme:dark]" 
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-slate-300 text-sm font-medium">Priority Level</label>
                        <select 
                            v-model="poPriority" 
                            class="w-full bg-slate-900/50 border border-white/10 rounded-md px-3 py-2 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="low">Low Priority</option>
                            <option value="normal">Normal Priority</option>
                            <option value="high">High Priority</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-slate-300 text-sm font-medium">Additional Notes</label>
                        <textarea 
                            v-model="poNotes" 
                            class="w-full bg-slate-900/50 border border-white/10 rounded-md px-3 py-2 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 min-h-[100px]" 
                            placeholder="Enter any special instructions or notes..."
                        ></textarea>
                    </div>

                    <div class="bg-gradient-to-br from-blue-900/20 to-blue-800/10 border border-blue-500/30 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-slate-300 text-sm font-medium">Estimated Total Value:</span>
                            </div>
                            <span class="text-white font-bold text-lg">
                                Rp {{ (((item.value / item.currentStock) * (Number(poQuantity) || 0)) / 1000000).toFixed(2) }}M
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-white/10 flex gap-3 shrink-0">
                <button 
                    @click="$emit('close')" 
                    class="flex-1 px-4 py-2 bg-transparent border border-white/10 hover:bg-white/5 text-slate-300 rounded-md transition-colors"
                >
                    Cancel
                </button>
                <button 
                    @click="handleSubmit" 
                    :disabled="!poQuantity || !poDeliveryDate"
                    class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-md transition-colors flex justify-center items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Create Purchase Order
                </button>
            </div>
        </div>
    </div>
</template>
<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.02);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
