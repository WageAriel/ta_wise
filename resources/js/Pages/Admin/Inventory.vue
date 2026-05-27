<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AdminLayout from "../../Layouts/AdminLayout.vue";

import StockAlert from "./Inventory/StockAlert.vue";
import StatsOverview from "./Inventory/StatsOverview.vue";
import OverviewTab from "./Inventory/OverviewTab.vue";
import AnalyticsTab from "./Inventory/AnalyticsTab.vue";
import AlertsTab from "./Inventory/AlertsTab.vue";
import CreatePOModal from "./Inventory/CreatePOModal.vue";
import PlanRestockModal from "./Inventory/PlanRestockModal.vue";

// Dummy Data
const inventoryData = ref([
    { id: 'INV-001', name: 'Premium Coffee Beans', category: 'Raw Materials', supplier: 'Global Trade Co.', currentStock: 150, unit: 'kg', minStock: 200, maxStock: 500, location: 'A-01', status: 'low', lastUpdate: '2026-05-20', value: 15000000 },
    { id: 'INV-002', name: 'Packaging Boxes', category: 'Packaging', supplier: 'Pack Solutions', currentStock: 50, unit: 'pcs', minStock: 500, maxStock: 2000, location: 'B-02', status: 'critical', lastUpdate: '2026-05-21', value: 250000 },
    { id: 'INV-003', name: 'Milk Powder', category: 'Raw Materials', supplier: 'Dairy Inc.', currentStock: 400, unit: 'kg', minStock: 100, maxStock: 800, location: 'C-05', status: 'normal', lastUpdate: '2026-05-22', value: 20000000 },
]);

const activeTab = ref("overview");
const showStockAlert = ref(true);

const criticalItems = computed(() => inventoryData.value.filter(i => i.status === 'critical').length);
const lowStockItems = computed(() => inventoryData.value.filter(i => i.status === 'low').length);
const normalItems = computed(() => inventoryData.value.filter(i => i.status === 'normal').length);
const totalValue = computed(() => inventoryData.value.reduce((acc, curr) => acc + curr.value, 0));

// Modals State
const showCreatePOModal = ref(false);
const showPlanRestockModal = ref(false);
const selectedItem = ref(null);

const handleOpenCreatePO = (item) => {
    selectedItem.value = item;
    showCreatePOModal.value = true;
};

const handleOpenPlanRestock = (item) => {
    selectedItem.value = item;
    showPlanRestockModal.value = true;
};
</script>

<template>
    <Head title="Inventory Monitoring" />

    <AdminLayout>
        <div class="flex-1 p-8 overflow-auto min-h-screen bg-white">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-2 gap-4">
                    <div>
                        <h1 class="text-white mb-1" style="font-size: 28px; font-weight: 700;">Inventory Monitoring</h1>
                        <p style="font-size: 13px; color: #94a3b8;">Real-time inventory tracking and stock management</p>
                    </div>
                    <div class="flex gap-2">
                        <button class="inline-flex items-center justify-center px-3 py-1.5 border border-white/10 text-slate-300 hover:bg-white/5 rounded-md text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export
                        </button>
                        <button
                            @click="() => { if (criticalItems > 0 || lowStockItems > 0) { showStockAlert = true; window.scrollTo({ top: 0, behavior: 'smooth' }); } }"
                            class="inline-flex items-center justify-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            <StockAlert 
                v-if="showStockAlert && (criticalItems > 0 || lowStockItems > 0)"
                :criticalItems="criticalItems"
                :lowStockItems="lowStockItems"
                @close="showStockAlert = false"
                @viewAlerts="activeTab = 'alerts'; window.scrollTo({ top: 0, behavior: 'smooth' });"
            />

            <StatsOverview 
                :totalItems="inventoryData.length"
                :totalValue="totalValue"
                :lowStockItems="lowStockItems"
                :criticalItems="criticalItems"
            />

            <!-- Tabs -->
            <div class="mb-6 flex gap-2 border-b border-white/10 pb-1">
                <button 
                    @click="activeTab = 'overview'" 
                    class="px-4 py-2 rounded-t-lg font-medium text-sm transition-colors flex items-center"
                    :class="activeTab === 'overview' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Stock Overview
                </button>
                <button 
                    @click="activeTab = 'analytics'" 
                    class="px-4 py-2 rounded-t-lg font-medium text-sm transition-colors flex items-center"
                    :class="activeTab === 'analytics' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Analytics
                </button>
                <button 
                    @click="activeTab = 'alerts'" 
                    class="px-4 py-2 rounded-t-lg font-medium text-sm transition-colors flex items-center"
                    :class="activeTab === 'alerts' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Alerts ({{ criticalItems + lowStockItems }})
                </button>
            </div>

            <!-- Tab Contents -->
            <div v-show="activeTab === 'overview'" class="mt-6">
                <OverviewTab :inventoryData="inventoryData" />
            </div>

            <div v-show="activeTab === 'analytics'" class="mt-6">
                <AnalyticsTab 
                    :inventoryData="inventoryData" 
                    :criticalItems="criticalItems"
                    :lowStockItems="lowStockItems"
                    :normalItems="normalItems"
                />
            </div>

            <div v-show="activeTab === 'alerts'" class="mt-6">
                <AlertsTab 
                    :inventoryData="inventoryData"
                    @openCreatePO="handleOpenCreatePO"
                    @openPlanRestock="handleOpenPlanRestock"
                />
            </div>

            <!-- Modals -->
            <CreatePOModal 
                v-if="showCreatePOModal" 
                :item="selectedItem" 
                @close="showCreatePOModal = false" 
            />
            <PlanRestockModal 
                v-if="showPlanRestockModal" 
                :item="selectedItem" 
                @close="showPlanRestockModal = false" 
            />
        </div>
    </AdminLayout>
</template>
