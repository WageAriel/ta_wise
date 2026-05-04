<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Sign Up — WISE" />

    <!-- Full-screen split layout (form LEFT, brand RIGHT) -->
    <div class="flex min-h-screen font-sans">

        <!-- ====== LEFT: FORM PANEL ====== -->
        <div class="flex w-full lg:w-1/2 min-h-screen bg-white px-12 py-10 flex-col">

            <!-- Logo pojok kiri atas -->
            <div class="flex items-center gap-2 mb-16">
                <svg viewBox="0 0 32 32" fill="none" class="w-7 h-7">
                    <circle cx="16" cy="16" r="14" stroke="#2563eb" stroke-width="2.5" fill="none"/>
                    <line x1="16" y1="4" x2="16" y2="28" stroke="#2563eb" stroke-width="2"/>
                    <line x1="4" y1="16" x2="28" y2="16" stroke="#2563eb" stroke-width="2"/>
                    <line x1="7.5" y1="7.5" x2="24.5" y2="24.5" stroke="#2563eb" stroke-width="2"/>
                    <line x1="24.5" y1="7.5" x2="7.5" y2="24.5" stroke="#2563eb" stroke-width="2"/>
                </svg>
                <span class="text-gray-800 text-base font-semibold">Wise</span>
            </div>

            <!-- Form container -->
            <div class="flex flex-col justify-center flex-1 max-w-xs mx-auto w-full lg:mx-0 lg:ml-auto lg:mr-16 xl:mr-32">

                <h2 class="text-3xl font-bold text-gray-900 mb-1">Sign up</h2>
                <p class="text-sm text-gray-400 mb-8">Sign up to enjoy the feature of Wise</p>

                <form @submit.prevent="submit" class="flex flex-col gap-5">

                    <!-- USERNAME — Floating Label -->
                    <div class="relative">
                        <input
                            id="name"
                            type="text"
                            v-model="form.name"
                            placeholder=" "
                            required
                            autofocus
                            autocomplete="name"
                            class="peer w-full border border-gray-300 rounded px-3 pt-5 pb-2 text-sm text-gray-900 bg-white focus:outline-none focus:border-blue-500"
                        />
                        <label
                            for="name"
                            class="absolute left-3 top-3.5 text-gray-400 text-sm transition-all duration-150
                                   peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm
                                   peer-focus:top-1 peer-focus:text-xs peer-focus:text-blue-500
                                   peer-[&:not(:placeholder-shown)]:top-1 peer-[&:not(:placeholder-shown)]:text-xs peer-[&:not(:placeholder-shown)]:text-gray-500"
                        >
                            Username
                        </label>
                        <InputError class="mt-1 text-xs" :message="form.errors.name" />
                    </div>

                    <!-- EMAIL — Floating Label -->
                    <div class="relative">
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            placeholder=" "
                            required
                            autocomplete="username"
                            class="peer w-full border border-gray-300 rounded px-3 pt-5 pb-2 text-sm text-gray-900 bg-white focus:outline-none focus:border-blue-500"
                        />
                        <label
                            for="email"
                            class="absolute left-3 top-3.5 text-gray-400 text-sm transition-all duration-150
                                   peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm
                                   peer-focus:top-1 peer-focus:text-xs peer-focus:text-blue-500
                                   peer-[&:not(:placeholder-shown)]:top-1 peer-[&:not(:placeholder-shown)]:text-xs peer-[&:not(:placeholder-shown)]:text-gray-500"
                        >
                            Email
                        </label>
                        <InputError class="mt-1 text-xs" :message="form.errors.email" />
                    </div>

                    <!-- PASSWORD — Floating Label + Eye Toggle -->
                    <div class="relative">
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                            class="peer w-full border border-gray-300 rounded px-3 pt-5 pb-2 pr-10 text-sm text-gray-900 bg-white focus:outline-none focus:border-blue-500"
                        />
                        <label
                            for="password"
                            class="absolute left-3 top-3.5 text-gray-400 text-sm transition-all duration-150
                                   peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm
                                   peer-focus:top-1 peer-focus:text-xs peer-focus:text-blue-500
                                   peer-[&:not(:placeholder-shown)]:top-1 peer-[&:not(:placeholder-shown)]:text-xs peer-[&:not(:placeholder-shown)]:text-gray-500"
                        >
                            Password
                        </label>
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <!-- Eye-off icon -->
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <!-- Eye icon -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <InputError class="mt-1 text-xs" :message="form.errors.password" />
                    </div>

                    <!-- CONFIRM PASSWORD — Floating Label + Eye Toggle -->
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            v-model="form.password_confirmation"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                            class="peer w-full border border-gray-300 rounded px-3 pt-5 pb-2 pr-10 text-sm text-gray-900 bg-white focus:outline-none focus:border-blue-500"
                        />
                        <label
                            for="password_confirmation"
                            class="absolute left-3 top-3.5 text-gray-400 text-sm transition-all duration-150
                                   peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm
                                   peer-focus:top-1 peer-focus:text-xs peer-focus:text-blue-500
                                   peer-[&:not(:placeholder-shown)]:top-1 peer-[&:not(:placeholder-shown)]:text-xs peer-[&:not(:placeholder-shown)]:text-gray-500"
                        >
                            Confirm Password
                        </label>
                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <!-- Eye-off icon -->
                            <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <!-- Eye icon -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <InputError class="mt-1 text-xs" :message="form.errors.password_confirmation" />
                    </div>

                    <!-- SUBMIT -->
                    <button
                        type="submit"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold text-sm py-3 rounded disabled:opacity-60 disabled:cursor-not-allowed"
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing">Sign up</span>
                        <span v-else>Processing...</span>
                    </button>

                    <!-- DIVIDER -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="bg-white px-3 text-xs text-gray-400">or</span>
                        </div>
                    </div>

                    <!-- LOGIN LINK -->
                    <p class="text-center text-sm text-gray-500">
                        Already have an account??
                        <Link :href="route('login')" class="font-semibold text-blue-500 hover:text-blue-600">
                            Sign in
                        </Link>
                    </p>

                </form>
            </div>
        </div>

        <!-- ====== RIGHT: BRAND PANEL ====== -->
        <div class="hidden lg:flex flex-1 relative flex-col items-center justify-center overflow-hidden bg-gray-900">
            
            <!-- Background Image -->
            <img 
                src="/Images/Warehouse.jpeg" 
                alt="Warehouse Background" 
                class="absolute inset-0 w-full h-full object-cover opacity-90 mix-blend-overlay"
            />
            
            <!-- Dark/Blue Gradient Overlay (agar teks makin kontras) -->
            <div class="absolute inset-0 bg-gradient-to-b from-blue-900/60 to-gray-900/80"></div>

            <!-- Teks Besar Tengah (z-index agar berada di atas gambar) -->
            <div class="relative z-10 flex flex-col items-center text-center px-8">
                <h1 class="text-6xl font-black text-white tracking-widest mb-6 drop-shadow-xl">WISE</h1>
                <p class="text-xl font-medium text-gray-200 tracking-widest max-w-md drop-shadow-md">
                    Warehouse Integrated Supply Evaluation
                </p>
                <p class="text-sm font-light text-gray-400 tracking-widest mt-3">
                    Sistem satu pintu yang menghubungkan proses verifikasi penyedia barang dengan ruang penyimpanan
                    untuk memastikan setiap urusan operasional dan data inventaris Anda tersusun dengan rapi dan teratur.
                </p>
            </div>

        </div><!-- end RIGHT panel -->


    </div>
</template>
