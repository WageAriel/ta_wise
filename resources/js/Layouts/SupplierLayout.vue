<script setup>
import SidebarSupplier from "../Components/SidebarSupplier.vue";
</script>

<template>
    <div class="flex h-screen bg-gray-50 font-sans">
        <!-- Sidebar Supplier -->
        <SidebarSupplier />

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Page Content -->
            <main
                class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6"
            >
                <slot />
            </main>
        </div>
    </div>
</template>
