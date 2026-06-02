<script setup>
import SidebarManajer from '../Components/SidebarManajer.vue';
</script>

<template>
  <div class="flex min-h-screen bg-slate-50 font-sans">
    <SidebarManajer />
    <div class="flex-1 flex flex-col overflow-hidden">
      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
