<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  chartByDate: { type: Array, default: () => [] },
  chartByMonth: { type: Array, default: () => [] },
});

// Toggle between "date" (30 hari) dan "month" (12 bulan)
const chartMode = ref('date'); // 'date' | 'month'

const chartData = computed(() =>
  chartMode.value === 'date' ? props.chartByDate : props.chartByMonth
);
const xKey = computed(() => chartMode.value === 'date' ? 'date' : 'month');

// ─── Simple SVG bar chart helpers ───────────────────────
const BAR_W   = 10;
const GAP     = 4;
const H       = 120;

const maxPo      = computed(() => Math.max(...chartData.value.map(d => d.po), 1));
const maxInbound = computed(() => Math.max(...chartData.value.map(d => d.inbound), 1));

const svgWidth = computed(() => chartData.value.length * (BAR_W * 2 + GAP + 4));

const barY    = (val, max) => H - Math.round((val / max) * H);
const barH    = (val, max) => Math.round((val / max) * H);
const barX    = (i) => i * (BAR_W * 2 + GAP + 4);

function formatNum(n) {
  if (n >= 1000) return (n / 1000).toFixed(1) + 'k';
  return n;
}
</script>

<template>
  <Head title="Dashboard Admin" />
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
          <p class="text-sm text-gray-400 mt-0.5">Statistik terkini — Purchase Order & Barang Masuk</p>
        </div>
        <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1.5 rounded-full font-medium">
          {{ new Date().toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' }) }}
        </span>
      </div>

      <!-- Stat Cards Row 1 -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">PO Hari Ini</p>
          <p class="mt-2 text-3xl font-extrabold text-indigo-600">{{ stats.poToday ?? 0 }}</p>
          <p class="mt-1 text-xs text-gray-400">transaksi baru</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">PO Bulan Ini</p>
          <p class="mt-2 text-3xl font-extrabold text-blue-600">{{ stats.poThisMonth ?? 0 }}</p>
          <p class="mt-1 text-xs text-gray-400">total transaksi</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Barang Masuk Hari Ini</p>
          <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ stats.inboundToday ?? 0 }}</p>
          <p class="mt-1 text-xs text-gray-400">unit</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Barang Masuk Bulan Ini</p>
          <p class="mt-2 text-3xl font-extrabold text-teal-600">{{ stats.inboundMonth ?? 0 }}</p>
          <p class="mt-1 text-xs text-gray-400">unit</p>
        </div>
      </div>

      <!-- Stat Cards Row 2 -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-indigo-50 to-white rounded-2xl border border-indigo-100 shadow-sm p-5">
          <p class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Total PO</p>
          <p class="mt-2 text-2xl font-bold text-indigo-700">{{ stats.poTotal ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-amber-50 to-white rounded-2xl border border-amber-100 shadow-sm p-5">
          <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider">PO Aktif</p>
          <p class="mt-2 text-2xl font-bold text-amber-600">{{ stats.poActive ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-50 to-white rounded-2xl border border-emerald-100 shadow-sm p-5">
          <p class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">PO Selesai</p>
          <p class="mt-2 text-2xl font-bold text-emerald-600">{{ stats.poCompleted ?? 0 }}</p>
        </div>
      </div>

      <!-- Chart -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h2 class="text-base font-bold text-gray-800">Grafik Transaksi</h2>
            <p class="text-xs text-gray-400">Purchase Order & Barang Masuk</p>
          </div>
          <div class="flex gap-2">
            <button @click="chartMode = 'date'"
              :class="chartMode === 'date' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">
              30 Hari
            </button>
            <button @click="chartMode = 'month'"
              :class="chartMode === 'month' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">
              12 Bulan
            </button>
          </div>
        </div>

        <!-- Legend -->
        <div class="flex gap-4 mb-3">
          <div class="flex items-center gap-1.5 text-xs text-gray-500">
            <span class="w-3 h-3 rounded-sm bg-indigo-500 inline-block"></span> Purchase Order
          </div>
          <div class="flex items-center gap-1.5 text-xs text-gray-500">
            <span class="w-3 h-3 rounded-sm bg-emerald-400 inline-block"></span> Barang Masuk (Unit)
          </div>
        </div>

        <!-- SVG Bar Chart -->
        <div class="overflow-x-auto pb-2">
          <svg :width="svgWidth" :height="H + 28" class="min-w-full" v-if="chartData.length">
            <g v-for="(d, i) in chartData" :key="i">
              <!-- PO bar (indigo) -->
              <rect
                :x="barX(i)"
                :y="barY(d.po, maxPo)"
                :width="BAR_W"
                :height="barH(d.po, maxPo)"
                rx="2"
                class="fill-indigo-500"
                :title="`PO: ${d.po}`"
              />
              <!-- Inbound bar (emerald) -->
              <rect
                :x="barX(i) + BAR_W + 2"
                :y="barY(d.inbound, maxInbound)"
                :width="BAR_W"
                :height="barH(d.inbound, maxInbound)"
                rx="2"
                class="fill-emerald-400"
                :title="`Inbound: ${d.inbound}`"
              />
              <!-- X label every ~5 items -->
              <text
                v-if="i % Math.max(1, Math.floor(chartData.length / 10)) === 0"
                :x="barX(i) + BAR_W"
                :y="H + 16"
                text-anchor="middle"
                font-size="9"
                fill="#9ca3af"
              >
                {{ chartMode === 'date' ? d.date?.slice(5) : d.month }}
              </text>
            </g>
          </svg>
          <div v-else class="py-16 text-center text-gray-300 text-sm">Tidak ada data untuk ditampilkan</div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
