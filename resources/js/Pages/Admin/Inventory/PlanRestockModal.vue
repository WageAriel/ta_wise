<script setup>
import { ref } from 'vue';

const props = defineProps({
    item: Object
});
const emit = defineEmits(['close']);

const restockQuantity = ref('');
const restockScheduleDate = ref('');
const restockFrequency = ref('once');
const restockNotes = ref('');

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
                        <div class="w-10 h-10 rounded-xl bg-yellow-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Plan Restock Schedule</h2>
                            <p class="text-slate-400 text-sm">Schedule restocking for low stock items</p>
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
                            <p class="text-yellow-400 font-semibold">{{ item.currentStock }} {{ item.unit }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs mb-1">Min Stock</p>
                            <p class="text-white">{{ item.minStock }} {{ item.unit }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs mb-1">Location</p>
                            <p class="text-white">{{ item.location }}</p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-slate-300 text-sm font-medium">Restock Quantity ({{ item.unit }})</label>
                            <input 
                                v-model="restockQuantity" 
                                type="number" 
                                class="w-full bg-slate-900/50 border border-white/10 rounded-md px-3 py-2 text-white focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500" 
                                placeholder="Enter quantity"
                            />
                            <p class="text-xs text-slate-500">Deficit: {{ item.minStock - item.currentStock }} {{ item.unit }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-slate-300 text-sm font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Schedule Date
                            </label>
                            <input 
                                v-model="restockScheduleDate" 
                                type="date" 
                                class="w-full bg-slate-900/50 border border-white/10 rounded-md px-3 py-2 text-white focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 [color-scheme:dark]" 
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-slate-300 text-sm font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Restock Frequency
                        </label>
                        <select 
                            v-model="restockFrequency" 
                            class="w-full bg-slate-900/50 border border-white/10 rounded-md px-3 py-2 text-white focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500"
                        >
                            <option value="once">One-time Restock</option>
                            <option value="weekly">Weekly Restock</option>
                            <option value="biweekly">Bi-weekly Restock</option>
                            <option value="monthly">Monthly Restock</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-slate-300 text-sm font-medium">Planning Notes</label>
                        <textarea 
                            v-model="restockNotes" 
                            class="w-full bg-slate-900/50 border border-white/10 rounded-md px-3 py-2 text-white focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 min-h-[100px]" 
                            placeholder="Enter planning notes, reminders, or special instructions..."
                        ></textarea>
                    </div>

                    <div class="bg-gradient-to-br from-yellow-900/20 to-yellow-800/10 border border-yellow-500/30 rounded-lg p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-300">Current Stock:</span>
                                <span class="text-white font-semibold">{{ item.currentStock }} {{ item.unit }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-300">Planned Restock:</span>
                                <span class="text-yellow-400 font-semibold">+{{ restockQuantity || 0 }} {{ item.unit }}</span>
                            </div>
                            <div class="h-px bg-white/10"></div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-300 text-sm font-semibold">Expected Stock:</span>
                                <span class="text-white font-bold text-lg">
                                    {{ item.currentStock + Number(restockQuantity || 0) }} {{ item.unit }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-300 text-sm">Status After Restock:</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold" 
                                    :class="(item.currentStock + Number(restockQuantity || 0)) >= item.minStock ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30'">
                                    {{ (item.currentStock + Number(restockQuantity || 0)) >= item.minStock ? 'Normal Stock' : 'Still Low' }}
                                </span>
                            </div>
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
                    :disabled="!restockQuantity || !restockScheduleDate"
                    class="flex-1 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-md transition-colors flex justify-center items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Schedule Restock
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
