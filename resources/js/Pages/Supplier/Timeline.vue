<script setup>
import { Head } from '@inertiajs/vue3';
import SupplierLayout from '../../Layouts/SupplierLayout.vue';

const props = defineProps({
    timeline: {
        type: Array,
        required: true,
    },
    currentStep: {
        type: Number,
        required: true,
    },
    supplierName: {
        type: String,
        default: 'Supplier'
    }
});

// Ikon untuk step (check, clock, X)
const getIcon = (status) => {
    if (status === 'completed') {
        return `<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />`;
    } else if (status === 'rejected') {
        return `<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />`;
    } else if (status === 'processing') {
        return `<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />`;
    }
    // pending
    return `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`;
};

// Styling warna step
const getStepClasses = (status, isLast) => {
    const baseCircle = "w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300 z-10 relative";
    const baseLine = "absolute top-10 left-5 -ml-0.5 w-1 h-full -z-10";
    
    if (status === 'completed') {
        return {
            circle: `${baseCircle} bg-green-500 border-green-500 text-white shadow-lg shadow-green-200`,
            line: isLast ? 'hidden' : `${baseLine} bg-green-500`,
            text: 'text-green-700 font-bold',
            badge: 'bg-green-100 text-green-700 border-green-200'
        };
    } else if (status === 'rejected') {
        return {
            circle: `${baseCircle} bg-red-500 border-red-500 text-white shadow-lg shadow-red-200`,
            line: isLast ? 'hidden' : `${baseLine} bg-gray-200`,
            text: 'text-red-700 font-bold',
            badge: 'bg-red-100 text-red-700 border-red-200'
        };
    } else if (status === 'processing') {
        return {
            circle: `${baseCircle} bg-white border-blue-500 text-blue-500 ring-4 ring-blue-100 animate-pulse`,
            line: isLast ? 'hidden' : `${baseLine} bg-gray-200 border-l-2 border-dashed border-blue-300`,
            text: 'text-blue-700 font-bold',
            badge: 'bg-blue-100 text-blue-700 border-blue-200'
        };
    }
    // pending
    return {
        circle: `${baseCircle} bg-white border-gray-300 text-gray-300`,
        line: isLast ? 'hidden' : `${baseLine} bg-gray-200 border-l-2 border-dashed border-gray-300`,
        text: 'text-gray-500 font-medium',
        badge: 'bg-gray-100 text-gray-500 border-gray-200'
    };
};
</script>

<template>
    <Head title="Timeline Pengajuan" />

    <SupplierLayout>
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-10 text-center">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Timeline Pengajuan Supplier</h1>
                <p class="mt-3 text-sm text-gray-500 max-w-2xl mx-auto">
                    Lacak progres pendaftaran, seleksi, dan klasifikasi perusahaan Anda <span class="font-bold text-gray-700">({{ supplierName }})</span> secara transparan.
                </p>
            </div>

            <!-- Card Timeline -->
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden relative">
                
                <!-- Dekorasi Background -->
                <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-full opacity-50 blur-3xl pointer-events-none"></div>

                <div class="p-8 md:p-12 relative z-10">
                    <div class="relative max-w-2xl mx-auto">
                        
                        <!-- List Step -->
                        <div v-for="(step, index) in timeline" :key="index" class="relative pb-16 last:pb-0">
                            
                            <!-- Garis Vertikal (Line Tracker) -->
                            <div v-if="index !== timeline.length - 1" :class="getStepClasses(step.status, false).line" style="height: calc(100% + 1rem);"></div>

                            <div class="relative flex items-start group">
                                <!-- Ikon Bulat -->
                                <div class="flex-shrink-0 relative">
                                    <div :class="getStepClasses(step.status, index === timeline.length - 1).circle">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" v-html="getIcon(step.status)"></svg>
                                    </div>
                                </div>

                                <!-- Konten Teks -->
                                <div class="ml-6 min-w-0 flex-1 bg-white p-5 rounded-2xl border border-gray-100 shadow-sm transition-all duration-300 hover:shadow-md hover:border-gray-200"
                                     :class="step.status === 'processing' ? 'ring-1 ring-blue-100 bg-blue-50/20' : ''">
                                    
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-2">
                                        <h3 class="text-lg" :class="getStepClasses(step.status, false).text">
                                            Tahap {{ index + 1 }}: {{ step.title }}
                                        </h3>
                                        
                                        <!-- Tanggal / Status Badge -->
                                        <span v-if="step.date" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="getStepClasses(step.status, false).badge">
                                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            {{ step.date }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-gray-50 text-gray-400 border-gray-100">
                                            Belum Selesai
                                        </span>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 leading-relaxed">
                                        {{ step.description }}
                                    </p>

                                    <!-- Final Result Highlight Box (Tahap 5) -->
                                    <div v-if="step.result" class="mt-4 p-4 rounded-xl flex items-center justify-center border-2 border-dashed"
                                         :class="step.status === 'completed' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                                        <div class="text-center">
                                            <span class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Keputusan Final</span>
                                            <span class="text-2xl font-black" :class="step.status === 'completed' ? 'text-green-600' : 'text-red-600'">
                                                {{ step.status === 'completed' ? 'Kelas ' + step.result : step.result }}
                                            </span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer Call to Action -->
                <div class="bg-gray-50 border-t border-gray-100 px-8 py-5 sm:flex sm:items-center sm:justify-between text-center sm:text-left">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800">Ada kendala dalam proses pengajuan?</h4>
                        <p class="text-xs text-gray-500 mt-1">Hubungi tim administrator kami untuk bantuan lebih lanjut.</p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <a href="mailto:admin@wise.com" class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none transition-colors">
                            Hubungi Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </SupplierLayout>
</template>
