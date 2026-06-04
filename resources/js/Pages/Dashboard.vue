<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import SupplierLayout from "@/Layouts/SupplierLayout.vue";

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Dashboard" />

    <!-- Admin Dashboard -->
    <AdminLayout v-if="user.role === 'admin'">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 font-medium">
                        Anda masuk sebagai Admin!
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>

    <!-- Supplier Dashboard -->
    <SupplierLayout v-else-if="user.role === 'supplier'">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 font-medium">
                        Anda masuk sebagai Supplier!
                    </div>
                </div>
            </div>
        </div>
    </SupplierLayout>

    <!-- Manajer Dashboard -->
    <AdminLayout v-else-if="user.role === 'manajer'">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 font-medium">
                        Anda masuk sebagai Manajer Gudang!
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>

    <!-- Fallback -->
    <div v-else class="p-6 text-gray-500">
        Role belum dikenali.
    </div>
</template>
