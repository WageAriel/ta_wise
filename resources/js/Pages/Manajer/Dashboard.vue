<script setup>
import { Head } from '@inertiajs/vue3';
import ManajerLayout from '@/Layouts/ManajerLayout.vue';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalPo: 0,
      activePo: 0,
      completedPo: 0,
      totalSuppliers: 0,
      totalItemTypes: 0,
    }),
  },
});
</script>

<template>
  <Head title="Dashboard Manajer" />

  <ManajerLayout>
    <div class="max-w-7xl mx-auto space-y-8">
      <div>
        <h1 class="text-3xl font-bold text-slate-800">Dashboard Manajer Gudang</h1>
        <p class="mt-2 text-sm text-slate-500">Ringkasan statistik PO dan pengaturan master barang.</p>
      </div>

      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
          <p class="text-xs text-slate-400">Total Purchase Order</p>
          <p class="mt-2 text-2xl font-bold text-slate-800">{{ stats.totalPo }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
          <p class="text-xs text-slate-400">PO Aktif (on-going)</p>
          <p class="mt-2 text-2xl font-bold text-emerald-600">{{ stats.activePo }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
          <p class="text-xs text-slate-400">PO Selesai</p>
          <p class="mt-2 text-2xl font-bold text-indigo-600">{{ stats.completedPo }}</p>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
          <p class="text-xs text-slate-400">Supplier Terdaftar</p>
          <p class="mt-2 text-xl font-semibold text-slate-800">{{ stats.totalSuppliers }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
          <p class="text-xs text-slate-400">Jenis Item PO</p>
          <p class="mt-2 text-xl font-semibold text-slate-800">{{ stats.totalItemTypes }}</p>
        </div>
      </div>

      <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
        <h2 class="text-lg font-semibold text-slate-800">Aksi Cepat</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola konfigurasi PO dan master item.</p>
        <div class="mt-4 flex flex-wrap gap-3">
          <a class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white" :href="route('manajer.purchase-order-controller.index')">
            Buka PO Controller
          </a>
        </div>
      </div>

      <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
        <h2 class="text-lg font-semibold text-slate-800">Statistics</h2>
        <p class="mt-1 text-sm text-slate-500">Placeholder statistik — konten dikosongkan sementara.</p>
        <div class="mt-4">
          <!-- Empty for now; data will be added later -->
        </div>
      </div>
    </div>
  </ManajerLayout>
</template>
