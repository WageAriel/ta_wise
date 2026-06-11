<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ManajerLayout from '@/Layouts/ManajerLayout.vue';
import axios from 'axios';

const props = defineProps({
  stats:          { type: Object, default: () => ({}) },
  chartMonthly:   { type: Array,  default: () => [] },
  itemStats:      { type: Array,  default: () => [] },
  selectedYear:   { type: Number, default: () => new Date().getFullYear() },
  availableYears: { type: Array,  default: () => [] },
});

// ─── Periode & Filter ──────────────────────────────────
const mode        = ref('year');   // year | month | day
const selYear     = ref(props.selectedYear);
const selMonth    = ref(new Date().getMonth() + 1);
const selDay      = ref(new Date().toISOString().slice(0, 10));

const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// ─── Chart data (dynamic via API) ─────────────────────
const chartData   = ref([...props.chartMonthly]);
const chartLoading = ref(false);

const fetchChart = async () => {
  chartLoading.value = true;
  try {
    const params = { mode: mode.value, year: selYear.value, month: selMonth.value, day: selDay.value };
    const { data } = await axios.get(route('manajer.stats'), { params });
    chartData.value = data.chart;
  } catch (e) { /* silent */ }
  finally { chartLoading.value = false; }
};

watch([mode, selYear, selMonth, selDay], fetchChart);

// When year changes → reload page for fresh server-side data
const changeYear = (y) => {
  selYear.value = y;
  router.get(route('manajer.dashboard'), { year: y }, { preserveScroll: true, preserveState: true });
};

// ─── SVG bar chart helpers ─────────────────────────────
const BAR_W = 14;
const GAP   = 5;
const H     = 140;

const maxPo      = computed(() => Math.max(...chartData.value.map(d => d.po ?? 0), 1));
const maxInbound = computed(() => Math.max(...chartData.value.map(d => d.inbound ?? 0), 1));
const svgWidth   = computed(() => Math.max(chartData.value.length * (BAR_W * 2 + GAP + 6), 400));

const barY = (v, max) => H - Math.round((v / max) * H);
const barH = (v, max) => Math.round((v / max) * H);
const barX = (i)     => i * (BAR_W * 2 + GAP + 6) + 8;

const xLabel = (d) => {
  if (mode.value === 'year')  return d.label; // month name
  if (mode.value === 'month') return `${d.label}`; // day number
  return `${d.label}:00`;
};

// ─── Item stats search ─────────────────────────────────
const itemSearch  = ref('');
const filteredItems = computed(() =>
  props.itemStats.filter(i =>
    i.label.toLowerCase().includes(itemSearch.value.toLowerCase())
  )
);

const maxItemQty = computed(() => Math.max(...filteredItems.value.map(i => i.total_qty), 1));
</script>

<template>
  <Head title="Dashboard Manajer" />
  <ManajerLayout>
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-3xl font-bold text-slate-800">Dashboard Manajer</h1>
          <p class="mt-1 text-sm text-slate-500">Statistik menyeluruh Purchase Order & Operasi Gudang</p>
        </div>
        <!-- Year selector -->
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-400 font-medium">Tahun:</span>
          <div class="flex gap-1">
            <button v-for="y in availableYears" :key="y"
              @click="changeYear(y)"
              :class="y === selectedYear ? 'bg-emerald-600 text-white shadow' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
              class="px-3 py-1.5 rounded-lg text-sm font-semibold transition">
              {{ y }}
            </button>
          </div>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 col-span-2 md:col-span-1">
          <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total PO</p>
          <p class="mt-2 text-3xl font-extrabold text-slate-800">{{ stats.totalPo }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5">
          <p class="text-xs text-amber-400 font-semibold uppercase tracking-wider">PO Aktif</p>
          <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ stats.activePo }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-5">
          <p class="text-xs text-emerald-400 font-semibold uppercase tracking-wider">PO Selesai</p>
          <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ stats.completedPo }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-5">
          <p class="text-xs text-blue-400 font-semibold uppercase tracking-wider">Supplier</p>
          <p class="mt-2 text-3xl font-extrabold text-blue-600">{{ stats.totalSuppliers }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-5">
          <p class="text-xs text-purple-400 font-semibold uppercase tracking-wider">Jenis Item</p>
          <p class="mt-2 text-3xl font-extrabold text-purple-600">{{ stats.totalItemTypes }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-teal-100 shadow-sm p-5">
          <p class="text-xs text-teal-400 font-semibold uppercase tracking-wider">Total Inbound</p>
          <p class="mt-2 text-3xl font-extrabold text-teal-600">{{ stats.totalInbound }}</p>
          <p class="text-[10px] text-slate-400 mt-1">unit keseluruhan</p>
        </div>
      </div>

      <!-- Dynamic Chart -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Grafik Transaksi</h2>
            <p class="text-xs text-slate-400">PO & Barang Masuk — atur periode di kanan</p>
          </div>
          <!-- Mode controls -->
          <div class="flex flex-wrap gap-2 items-center">
            <div class="flex rounded-lg overflow-hidden border border-slate-200 text-xs font-semibold">
              <button v-for="m in [{k:'year',l:'Tahunan'},{k:'month',l:'Bulanan'},{k:'day',l:'Harian'}]" :key="m.k"
                @click="mode = m.k"
                :class="mode === m.k ? 'bg-emerald-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-50'"
                class="px-3 py-1.5 transition">
                {{ m.l }}
              </button>
            </div>
            <select v-if="mode === 'month' || mode === 'day'" v-model="selMonth"
              class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-600">
              <option v-for="(mn, mi) in monthNames" :key="mi+1" :value="mi+1">{{ mn }}</option>
            </select>
            <input v-if="mode === 'day'" type="date" v-model="selDay"
              class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-600" />
          </div>
        </div>

        <!-- Legend -->
        <div class="flex gap-4 mb-4">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Purchase Order
          </div>
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <span class="w-3 h-3 rounded-sm bg-blue-400 inline-block"></span> Barang Masuk (Unit)
          </div>
        </div>

        <div class="relative overflow-x-auto pb-2 min-h-[168px]">
          <div v-if="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 rounded-xl">
            <span class="text-sm text-slate-400">Memuat...</span>
          </div>
          <svg :width="svgWidth" :height="H + 30" v-if="chartData.length">
            <g v-for="(d, i) in chartData" :key="i">
              <rect :x="barX(i)" :y="barY(d.po ?? 0, maxPo)" :width="BAR_W" :height="barH(d.po ?? 0, maxPo)" rx="3" class="fill-emerald-500" />
              <rect :x="barX(i) + BAR_W + 2" :y="barY(d.inbound ?? 0, maxInbound)" :width="BAR_W" :height="barH(d.inbound ?? 0, maxInbound)" rx="3" class="fill-blue-400" />
              <text v-if="i % Math.max(1, Math.floor(chartData.length / 12)) === 0"
                :x="barX(i) + BAR_W" :y="H + 20" text-anchor="middle" font-size="9" fill="#94a3b8">
                {{ xLabel(d) }}
              </text>
            </g>
          </svg>
          <div v-else-if="!chartLoading" class="py-16 text-center text-slate-300 text-sm">Tidak ada data</div>
        </div>
      </div>

      <!-- Per-Item Statistics -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Statistik per Item</h2>
            <p class="text-xs text-slate-400">Volume transaksi berdasarkan jenis & subtype barang — {{ selectedYear }}</p>
          </div>
          <input v-model="itemSearch" type="text" placeholder="Cari barang..."
            class="text-sm border border-slate-200 rounded-xl px-3 py-2 w-56 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none" />
        </div>

        <div class="space-y-3">
          <div v-for="(item, idx) in filteredItems" :key="idx"
            class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition">
            <span class="text-xs text-slate-400 font-bold w-6 text-right shrink-0">{{ idx + 1 }}</span>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-1 gap-2">
                <p class="text-sm font-semibold text-slate-800 truncate">{{ item.label }}</p>
                <span class="text-xs text-slate-500 shrink-0">
                  <strong class="text-slate-700">{{ item.total_qty }}</strong> {{ item.uom }}
                </span>
              </div>
              <!-- Progress bar -->
              <div class="w-full bg-slate-100 rounded-full h-2">
                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                  :style="{ width: `${Math.round((item.total_qty / maxItemQty) * 100)}%` }">
                </div>
              </div>
            </div>
            <div class="shrink-0 text-right text-xs text-slate-400 w-20">
              <p><span class="text-emerald-600 font-semibold">{{ item.jumlah_transaksi }}</span> transaksi</p>
              <p><span class="text-blue-600 font-semibold">{{ item.qty_selesai }}</span> selesai</p>
            </div>
          </div>
          <div v-if="filteredItems.length === 0" class="py-10 text-center text-slate-300 text-sm">
            Tidak ada item ditemukan
          </div>
        </div>
      </div>

    </div>
  </ManajerLayout>
</template>
