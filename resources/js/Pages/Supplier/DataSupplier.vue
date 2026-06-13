<script setup>
import { reactive, ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import SupplierLayout from "@/Layouts/SupplierLayout.vue";

const props = defineProps({
    supplier: { type: Object, default: null },
});

// Mengecek apakah form sudah di-submit (hanya baca)
const isSubmitted = computed(
    () => props.supplier && props.supplier.status !== "draft",
);

const getHasDocument = (type) => {
    if (!props.supplier || !props.supplier.documents) return false;
    const doc = props.supplier.documents.find((d) => d.jenis_dokumen === type);
    return doc ? Boolean(doc.has_document) : false;
};

// Gunakan reactive agar form.errors & form.processing tetap sama dengan useForm
// → template TIDAK perlu diubah sama sekali
const form = reactive({
    nama_perusahaan: props.supplier?.nama_perusahaan ?? "",
    no_telp_perusahaan: props.supplier?.no_telp_perusahaan ?? "",
    alamat_perusahaan: props.supplier?.alamat_perusahaan ?? "",
    email_perusahaan: props.supplier?.email_perusahaan ?? "",

    nama_pic: props.supplier?.nama_pic ?? "",
    no_telp_pic: props.supplier?.no_telp_pic ?? "",
    email_pic: props.supplier?.email_pic ?? "",

    nama_bank: props.supplier?.nama_bank ?? "",
    no_rekening: props.supplier?.no_rekening ?? "",
    atas_nama: props.supplier?.atas_nama ?? "",

    // Checkbox boolean
    has_nib: getHasDocument("nib"),
    has_npwp: getHasDocument("npwp"),
    has_akta_pendirian: getHasDocument("akta_pendirian"),
    has_izin_usaha: getHasDocument("izin_usaha"),
    has_izin_khusus: getHasDocument("izin_khusus"),
    has_sk_domisili: getHasDocument("sk_domisili"),
    has_laporan_keuangan: getHasDocument("laporan_keuangan"),

    // Files
    file_nib: null,
    file_npwp: null,
    file_akta_pendirian: null,
    file_izin_usaha: null,
    file_izin_khusus: null,
    file_sk_domisili: null,
    file_laporan_keuangan: null,

    // State pengganti useForm (agar template tidak berubah)
    errors: {},
    processing: false,
});

const docs = [
    { id: "nib", label: "NIB (Nomor Induk Berusaha)" },
    { id: "npwp", label: "NPWP Perusahaan" },
    { id: "akta_pendirian", label: "Akta Pendirian" },
    { id: "izin_usaha", label: "Izin Usaha Operasional" },
    { id: "izin_khusus", label: "Izin Khusus (Opsional)" },
    { id: "sk_domisili", label: "Surat Keterangan Domisili" },
    { id: "laporan_keuangan", label: "Laporan Keuangan" },
];

const previews = ref({});

const handleFileUpload = (event, type) => {
    const file = event.target.files[0];
    form[`file_${type}`] = file;
    if (file) {
        previews.value[type] = file.type.startsWith("image/")
            ? URL.createObjectURL(file)
            : "doc";
    } else {
        delete previews.value[type];
    }
};

const removeFile = (type) => {
    form[`file_${type}`] = null;
    delete previews.value[type];
};

const showConfirmModal = ref(false);
const showNotificationModal = ref(false);
const notificationType = ref("success"); // 'success' or 'error'
const notificationMessage = ref("");

const closeNotification = () => {
    showNotificationModal.value = false;
    if (notificationType.value === "success") {
        window.location.reload(); // Reload agar status banner terupdate
    }
};

const submit = () => {
    showConfirmModal.value = true;
};

const confirmSubmit = async () => {
    showConfirmModal.value = false;
    form.processing = true;
    form.errors = {};

    // Bangun FormData secara manual (diperlukan untuk file upload via Axios)
    const formData = new FormData();

    const textFields = [
        "nama_perusahaan",
        "no_telp_perusahaan",
        "alamat_perusahaan",
        "email_perusahaan",
        "nama_pic",
        "no_telp_pic",
        "email_pic",
        "nama_bank",
        "no_rekening",
        "atas_nama",
    ];
    textFields.forEach((k) => formData.append(k, form[k] ?? ""));

    // Boolean checkbox → string "1"/"0" agar $request->boolean() bekerja
    const checkboxFields = [
        "has_nib",
        "has_npwp",
        "has_akta_pendirian",
        "has_izin_usaha",
        "has_izin_khusus",
        "has_sk_domisili",
        "has_laporan_keuangan",
    ];
    checkboxFields.forEach((k) => formData.append(k, form[k] ? "1" : "0"));

    // Append file hanya jika user memilih file baru
    const fileFields = [
        "file_nib",
        "file_npwp",
        "file_akta_pendirian",
        "file_izin_usaha",
        "file_izin_khusus",
        "file_sk_domisili",
        "file_laporan_keuangan",
    ];
    fileFields.forEach((k) => {
        if (form[k] instanceof File) formData.append(k, form[k]);
    });

    try {
        const response = await axios.post("/supplier/data", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        notificationType.value = "success";
        notificationMessage.value =
            response.data.message || "Data berhasil diajukan!";
        showNotificationModal.value = true;
    } catch (err) {
        if (err.response?.status === 422) {
            // Flatten array errors ke string pertama (sama seperti perilaku useForm)
            form.errors = Object.fromEntries(
                Object.entries(err.response.data.errors).map(([k, v]) => [
                    k,
                    Array.isArray(v) ? v[0] : v,
                ]),
            );
            //Pesan error ketika user menginput salah/kosong
            notificationType.value = "error";
            notificationMessage.value =
                "Terdapat inputan yang kosong atau salah. Silakan periksa kembali semua isian Anda.";
            showNotificationModal.value = true;
        } else {
            notificationType.value = "error";
            notificationMessage.value =
                err.response?.data?.message ||
                "Terjadi kesalahan. Silakan coba lagi.";
            showNotificationModal.value = true;
        }
    } finally {
        form.processing = false;
    }
};
</script>

<template>
    <Head title="Data Perusahaan | WISE" />

    <SupplierLayout>
        <div class="max-w-6xl mx-auto px-6 py-10 lg:px-10">
            <!-- Header Section -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4"
            >
                <div>
                    <h1
                        class="text-2xl font-extrabold text-slate-900 tracking-tight"
                    >
                        Profil Perusahaan
                    </h1>
                    <p class="text-slate-500 mt-2 text-md">
                        Lengkapi data untuk verifikasi identitas & legalitas
                    </p>
                </div>
                <div
                    class="flex items-center gap-2 text-sm font-medium bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm"
                >
                    <span
                        class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"
                    ></span>
                    <span class="text-slate-600"
                        >Periode Seleksi {{ new Date().getFullYear() }}</span
                    >
                </div>
            </div>

            <!-- Status Banner -->
            <div
                v-if="props.supplier"
                class="mb-10 overflow-hidden rounded-2xl border transition-all duration-300 shadow-sm"
                :class="{
                    'bg-amber-50 border-amber-200 shadow-amber-100/30':
                        props.supplier.status === 'draft',
                    'bg-blue-50 border-blue-200 shadow-blue-100/30':
                        props.supplier.status === 'submitted',
                    'bg-emerald-50 border-emerald-200 shadow-emerald-100/30':
                        props.supplier.status === 'approved',
                    'bg-rose-50 border-rose-200 shadow-rose-100/30':
                        props.supplier.status === 'rejected',
                }"
            >
                <div class="px-6 py-5 flex items-start gap-4">
                    <div class="shrink-0 mt-1">
                        <div
                            v-if="props.supplier.status === 'approved'"
                            class="p-2 bg-emerald-100 rounded-lg"
                        >
                            <svg
                                class="w-6 h-6 text-emerald-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M5 13l4 4L19 7"
                                ></path>
                            </svg>
                        </div>
                        <div
                            v-else-if="props.supplier.status === 'rejected'"
                            class="p-2 bg-rose-100 rounded-lg"
                        >
                            <svg
                                class="w-6 h-6 text-rose-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12"
                                ></path>
                            </svg>
                        </div>
                        <div v-else class="p-2 bg-blue-100 rounded-lg">
                            <svg
                                class="w-6 h-6 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-900 text-md">
                            Status:
                            <span class="uppercase tracking-widest">{{
                                props.supplier.status === 'submitted' ? 'Menunggu Review' : props.supplier.status
                            }}</span>
                        </h3>
                        <p class="text-slate-600 text-sm mt-1 leading-relaxed">
                            <span v-if="props.supplier.status === 'submitted'"
                                >Aplikasi Anda sedang dalam peninjauan oleh tim
                                pengadaan.</span
                            >
                            <span
                                v-else-if="props.supplier.status === 'approved'"
                                >Selamat! Profil perusahaan Anda telah
                                divalidasi.</span
                            >
                            <span
                                v-else-if="props.supplier.status === 'rejected'"
                                >Mohon maaf, pengajuan Anda ditolak. <br /><span
                                    class="font-semibold text-rose-700 italic"
                                    >Catatan:
                                    {{ props.supplier.catatan_admin }}</span
                                ></span
                            >
                            <span v-else
                                >Lengkapi data di bawah ini untuk memulai proses
                                seleksi.</span
                            >
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form starts -->
            <form @submit.prevent="submit" class="space-y-10 pb-20">
                <!-- Grid Row 1: Company & PIC -->
                <div class="grid grid-cols-1 gap-8">
                    <!-- 1. Data Perusahaan -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
                    >
                        <div
                            class="bg-slate-50/50 px-8 py-5 border-b border-slate-100 flex items-center gap-3"
                        >
                            <div
                                class="p-2 bg-blue-100 text-blue-600 rounded-xl"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                    ></path>
                                </svg>
                            </div>
                            <h2
                                class="text-lg font-bold text-slate-800 tracking-tight"
                            >
                                Informasi Perusahaan
                            </h2>
                        </div>
                        <div class="p-8 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-500 mb-2"
                                        >Nama Perusahaan</label
                                    >
                                    <input
                                        v-model="form.nama_perusahaan"
                                        type="text"
                                        :disabled="isSubmitted"
                                        class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all disabled:opacity-60"
                                        :class="{
                                            'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                                form.errors.nama_perusahaan,
                                        }"
                                        placeholder="PT. Example"
                                    />
                                    <div
                                        v-if="form.errors.nama_perusahaan"
                                        class="mt-2 flex items-center gap-1.5 text-rose-500"
                                    >
                                        <svg
                                            class="w-4 h-4 shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        <span class="text-xs font-bold">{{
                                            form.errors.nama_perusahaan
                                        }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-500 mb-2"
                                        >No. Telp Perusahaan</label
                                    >
                                    <input
                                        v-model="form.no_telp_perusahaan"
                                        type="text"
                                        :disabled="isSubmitted"
                                        class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all disabled:opacity-60"
                                        :class="{
                                            'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                                form.errors.no_telp_perusahaan,
                                        }"
                                        placeholder="085712345678"
                                    />
                                    <div
                                        v-if="form.errors.no_telp_perusahaan"
                                        class="mt-2 flex items-center gap-1.5 text-rose-500"
                                    >
                                        <svg
                                            class="w-4 h-4 shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        <span class="text-xs font-bold">{{
                                            form.errors.no_telp_perusahaan
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-500 mb-2"
                                    >Alamat Perusahaan</label
                                >
                                <textarea
                                    v-model="form.alamat_perusahaan"
                                    rows="3"
                                    :disabled="isSubmitted"
                                    class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all disabled:opacity-60"
                                    :class="{
                                        'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                            form.errors.alamat_perusahaan,
                                    }"
                                ></textarea>
                                <div
                                    v-if="form.errors.alamat_perusahaan"
                                    class="mt-2 flex items-center gap-1.5 text-rose-500"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="text-xs font-bold">{{
                                        form.errors.alamat_perusahaan
                                    }}</span>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-500 mb-2"
                                    >Email Perusahaan</label
                                >
                                <input
                                    v-model="form.email_perusahaan"
                                    type="email"
                                    :disabled="isSubmitted"
                                    class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all disabled:opacity-60"
                                    :class="{
                                        'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                            form.errors.email_perusahaan,
                                    }"
                                    placeholder="example@company.com"
                                />
                                <div
                                    v-if="form.errors.email_perusahaan"
                                    class="mt-2 flex items-center gap-1.5 text-rose-500"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="text-xs font-bold">{{
                                        form.errors.email_perusahaan
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Data PIC -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
                    >
                        <div
                            class="bg-slate-50/50 px-8 py-5 border-b border-slate-100 flex items-center gap-3"
                        >
                            <div
                                class="p-2 bg-indigo-100 text-indigo-600 rounded-xl"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    ></path>
                                </svg>
                            </div>
                            <h2
                                class="text-lg font-bold text-slate-800 tracking-tight"
                            >
                                Penanggung Jawab (PIC)
                            </h2>
                        </div>
                        <div class="p-8 space-y-5">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-500 mb-2"
                                    >Nama Lengkap PIC</label
                                >
                                <input
                                    v-model="form.nama_pic"
                                    type="text"
                                    :disabled="isSubmitted"
                                    class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all disabled:opacity-60"
                                    :class="{
                                        'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                            form.errors.nama_pic,
                                    }"
                                    placeholder="Mrexample"
                                />
                                <div
                                    v-if="form.errors.nama_pic"
                                    class="mt-2 flex items-center gap-1.5 text-rose-500"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="text-xs font-bold">{{
                                        form.errors.nama_pic
                                    }}</span>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-500 mb-2"
                                    >WhatsApp / No. HP</label
                                >
                                <input
                                    v-model="form.no_telp_pic"
                                    type="text"
                                    :disabled="isSubmitted"
                                    class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all disabled:opacity-60"
                                    :class="{
                                        'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                            form.errors.no_telp_pic,
                                    }"
                                    placeholder="085712345678"
                                />
                                <div
                                    v-if="form.errors.no_telp_pic"
                                    class="mt-2 flex items-center gap-1.5 text-rose-500"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="text-xs font-bold">{{
                                        form.errors.no_telp_pic
                                    }}</span>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-500 mb-2"
                                    >Email PIC</label
                                >
                                <input
                                    v-model="form.email_pic"
                                    type="email"
                                    :disabled="isSubmitted"
                                    class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all disabled:opacity-60"
                                    :class="{
                                        'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                            form.errors.email_pic,
                                    }"
                                    placeholder="mrexample@gmail.com"
                                />
                                <div
                                    v-if="form.errors.email_pic"
                                    class="mt-2 flex items-center gap-1.5 text-rose-500"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="text-xs font-bold">{{
                                        form.errors.email_pic
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Data Bank -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
                >
                    <div
                        class="bg-slate-50/50 px-8 py-5 border-b border-slate-100 flex items-center gap-3"
                    >
                        <div
                            class="p-2 bg-emerald-100 text-emerald-600 rounded-xl"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                ></path>
                            </svg>
                        </div>
                        <h2
                            class="text-lg font-bold text-slate-800 tracking-tight"
                        >
                            Informasi Rekening Bank
                        </h2>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-500 mb-2"
                                    >Nama Bank</label
                                >
                                <input
                                    v-model="form.nama_bank"
                                    type="text"
                                    :disabled="isSubmitted"
                                    placeholder="BCA / Mandiri"
                                    class="w-full px-4 py-3 rounded-xl bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all disabled:opacity-60"
                                    :class="{
                                        'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                            form.errors.nama_bank,
                                    }"
                                />
                                <div
                                    v-if="form.errors.nama_bank"
                                    class="mt-2 flex items-center gap-1.5 text-rose-500"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="text-xs font-bold">{{
                                        form.errors.nama_bank
                                    }}</span>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-500 mb-2"
                                    >Nomor Rekening</label
                                >
                                <input
                                    v-model="form.no_rekening"
                                    type="text"
                                    :disabled="isSubmitted"
                                    class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all disabled:opacity-60"
                                    :class="{
                                        'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                            form.errors.no_rekening,
                                    }"
                                    placeholder="123456789"
                                />
                                <div
                                    v-if="form.errors.no_rekening"
                                    class="mt-2 flex items-center gap-1.5 text-rose-500"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="text-xs font-bold">{{
                                        form.errors.no_rekening
                                    }}</span>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-500 mb-2"
                                    >Atas Nama Rekening</label
                                >
                                <input
                                    v-model="form.atas_nama"
                                    type="text"
                                    :disabled="isSubmitted"
                                    class="w-full px-4 py-3 rounded-lg bg-gray-200 border-slate-200 bg-slate-50/50 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all disabled:opacity-60"
                                    :class="{
                                        'border-rose-300 ring-4 ring-rose-500/10 focus:border-rose-500':
                                            form.errors.atas_nama,
                                    }"
                                    placeholder="mrexample"
                                />
                                <div
                                    v-if="form.errors.atas_nama"
                                    class="mt-2 flex items-center gap-1.5 text-rose-500"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="text-xs font-bold">{{
                                        form.errors.atas_nama
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Dokumen Legalitas (2 Columns Grid with Large Preview) -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
                >
                    <div
                        class="bg-slate-50/50 px-8 py-5 border-b border-slate-100 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="p-2 bg-amber-100 text-amber-600 rounded-xl"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                            </div>
                            <h2
                                class="text-lg font-bold text-slate-800 tracking-tight"
                            >
                                Dokumen Legalitas
                            </h2>
                        </div>
                    </div>

                    <!-- Grid 2 Kolom -->
                    <div
                        class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 gap-6"
                    >
                        <div
                            v-for="doc in docs"
                            :key="doc.id"
                            class="p-5 border rounded-lg transition-all duration-300 flex flex-col"
                            :class="[
                                form[`has_${doc.id}`]
                                    ? 'border-blue-100 bg-blue-50/10 ring-2 ring-blue-500/5'
                                    : 'border-slate-100 bg-white',
                                form.errors[`file_${doc.id}`]
                                    ? '!border-rose-300 !ring-rose-500/10'
                                    : '',
                            ]"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2 rounded-lg"
                                        :class="
                                            form[`has_${doc.id}`]
                                                ? 'bg-blue-100 text-blue-600'
                                                : 'bg-slate-100 text-slate-400'
                                        "
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <h4
                                        class="font-bold text-slate-700 text-sm leading-tight"
                                    >
                                        {{ doc.label }}
                                    </h4>
                                </div>

                                <div class="flex items-center">
                                    <div
                                        class="flex bg-slate-100 p-1 rounded-lg"
                                    >
                                        <button
                                            type="button"
                                            @click="
                                                form[`has_${doc.id}`] = true
                                            "
                                            :disabled="isSubmitted"
                                            class="px-3 py-1 rounded text-[10px] font-bold uppercase transition-all"
                                            :class="
                                                form[`has_${doc.id}`]
                                                    ? 'bg-white text-blue-600 shadow-sm'
                                                    : 'text-slate-400'
                                            "
                                        >
                                            Ya
                                        </button>
                                        <button
                                            type="button"
                                            @click="
                                                form[`has_${doc.id}`] = false
                                            "
                                            :disabled="isSubmitted"
                                            class="px-3 py-1 rounded text-[10px] font-bold uppercase transition-all"
                                            :class="
                                                !form[`has_${doc.id}`]
                                                    ? 'bg-white text-slate-500 shadow-sm'
                                                    : 'text-slate-400'
                                            "
                                        >
                                            Tidak
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- File Upload & Preview -->
                            <div
                                v-if="form[`has_${doc.id}`]"
                                class="flex-1 flex flex-col justify-end"
                            >
                                <div v-if="!isSubmitted">
                                    <!-- Large Preview Box -->
                                    <div
                                        v-if="previews[doc.id]"
                                        class="mt-4 mb-4 relative rounded-xl overflow-hidden border shadow-sm bg-white"
                                        :class="
                                            form.errors[`file_${doc.id}`]
                                                ? 'border-rose-200'
                                                : 'border-slate-200'
                                        "
                                    >
                                        <!-- Image Preview -->
                                        <div
                                            v-if="previews[doc.id] !== 'doc'"
                                            class="aspect-[16/9] w-full bg-slate-100"
                                        >
                                            <img
                                                :src="previews[doc.id]"
                                                class="w-full h-full object-contain"
                                            />
                                        </div>
                                        <!-- Document Icon Preview -->
                                        <div
                                            v-else
                                            class="aspect-[16/9] w-full bg-slate-50 flex flex-col items-center justify-center"
                                        >
                                            <svg
                                                class="w-16 h-16 text-slate-400 mb-3"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                ></path>
                                            </svg>
                                            <span
                                                class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                                                >DOKUMEN FILE</span
                                            >
                                        </div>

                                        <!-- File Info Overlay -->
                                        <div
                                            class="bg-white p-3 border-t border-slate-100 flex items-center justify-between"
                                        >
                                            <div class="flex-1 min-w-0 pr-3">
                                                <p
                                                    class="text-xs font-bold text-slate-800 truncate"
                                                >
                                                    {{
                                                        form[`file_${doc.id}`]
                                                            ?.name
                                                    }}
                                                </p>
                                                <p
                                                    class="text-[10px] text-slate-500 mt-0.5"
                                                >
                                                    {{
                                                        (
                                                            form[
                                                                `file_${doc.id}`
                                                            ]?.size /
                                                            1024 /
                                                            1024
                                                        ).toFixed(2)
                                                    }}
                                                    MB
                                                </p>
                                            </div>
                                            <button
                                                @click="removeFile(doc.id)"
                                                type="button"
                                                class="p-2 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 rounded-lg transition-colors flex items-center gap-1"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    ></path>
                                                </svg>
                                                <span
                                                    class="text-[10px] font-bold uppercase tracking-wider hidden sm:block"
                                                    >Hapus</span
                                                >
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Upload Button -->
                                    <div
                                        v-if="!form[`file_${doc.id}`]"
                                        class="relative"
                                    >
                                        <input
                                            type="file"
                                            @change="
                                                (e) =>
                                                    handleFileUpload(e, doc.id)
                                            "
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        />
                                        <div
                                            class="px-4 py-6 rounded-xl border-2 border-dashed bg-slate-50/50 transition-all flex flex-col items-center justify-center text-center"
                                            :class="
                                                form.errors[`file_${doc.id}`]
                                                    ? 'border-rose-300 hover:border-rose-400 hover:bg-rose-50/50'
                                                    : 'border-slate-300 hover:border-blue-400 hover:bg-white'
                                            "
                                        >
                                            <svg
                                                class="w-6 h-6 mb-2"
                                                :class="
                                                    form.errors[
                                                        `file_${doc.id}`
                                                    ]
                                                        ? 'text-rose-400'
                                                        : 'text-blue-500'
                                                "
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 4v16m8-8H4"
                                                ></path>
                                            </svg>
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-widest"
                                                :class="
                                                    form.errors[
                                                        `file_${doc.id}`
                                                    ]
                                                        ? 'text-rose-600'
                                                        : 'text-slate-600'
                                                "
                                            >
                                                Pilih File
                                            </p>
                                            <p
                                                class="text-[9px] text-slate-400 mt-1 uppercase tracking-widest"
                                            >
                                                PDF, JPG, PNG (Max 5MB)
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- View Only (Already Submitted) -->
                                <div
                                    v-else-if="
                                        props.supplier.documents.find(
                                            (d) => d.jenis_dokumen === doc.id,
                                        )?.file_path
                                    "
                                    class="mt-4"
                                >
                                    <div
                                        class="relative rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white"
                                    >
                                        <!-- Image or PDF Preview -->
                                        <div
                                            class="aspect-[16/9] w-full bg-slate-100"
                                        >
                                            <template
                                                v-if="
                                                    [
                                                        'jpg',
                                                        'jpeg',
                                                        'png',
                                                        'gif',
                                                    ].some(
                                                        (ext) =>
                                                            props.supplier.documents
                                                                .find(
                                                                    (d) =>
                                                                        d.jenis_dokumen ===
                                                                        doc.id,
                                                                )
                                                                .file_path.toLowerCase()
                                                                .endsWith(
                                                                    ext,
                                                                ) ||
                                                            (
                                                                props.supplier.documents.find(
                                                                    (d) =>
                                                                        d.jenis_dokumen ===
                                                                        doc.id,
                                                                ).file_name ||
                                                                ''
                                                            )
                                                                .toLowerCase()
                                                                .endsWith(ext),
                                                    )
                                                "
                                            >
                                                <img
                                                    :src="
                                                        '/storage/' +
                                                        props.supplier.documents.find(
                                                            (d) =>
                                                                d.jenis_dokumen ===
                                                                doc.id,
                                                        ).file_path
                                                    "
                                                    class="w-full h-full object-contain"
                                                    alt="Preview Dokumen"
                                                />
                                            </template>
                                            <template
                                                v-else-if="
                                                    ['pdf'].some(
                                                        (ext) =>
                                                            props.supplier.documents
                                                                .find(
                                                                    (d) =>
                                                                        d.jenis_dokumen ===
                                                                        doc.id,
                                                                )
                                                                .file_path.toLowerCase()
                                                                .endsWith(
                                                                    ext,
                                                                ) ||
                                                            (
                                                                props.supplier.documents.find(
                                                                    (d) =>
                                                                        d.jenis_dokumen ===
                                                                        doc.id,
                                                                ).file_name ||
                                                                ''
                                                            )
                                                                .toLowerCase()
                                                                .endsWith(ext),
                                                    )
                                                "
                                            >
                                                <iframe
                                                    :src="
                                                        '/storage/' +
                                                        props.supplier.documents.find(
                                                            (d) =>
                                                                d.jenis_dokumen ===
                                                                doc.id,
                                                        ).file_path
                                                    "
                                                    class="w-full h-full"
                                                    frameborder="0"
                                                ></iframe>
                                            </template>
                                            <template v-else>
                                                <div
                                                    class="w-full h-full bg-slate-50 flex flex-col items-center justify-center"
                                                >
                                                    <svg
                                                        class="w-16 h-16 text-slate-400 mb-3"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="1.5"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                        ></path>
                                                    </svg>
                                                    <span
                                                        class="text-xs font-bold text-slate-500 uppercase tracking-widest"
                                                        >DOKUMEN FILE</span
                                                    >
                                                </div>
                                            </template>
                                        </div>

                                        <div
                                            class="bg-white p-3 border-t border-slate-100"
                                        >
                                            <a
                                                :href="
                                                    '/storage/' +
                                                    props.supplier.documents.find(
                                                        (d) =>
                                                            d.jenis_dokumen ===
                                                            doc.id,
                                                    ).file_path
                                                "
                                                target="_blank"
                                                class="flex items-center justify-center gap-2 text-[10px] font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-3 py-2 rounded-lg border border-blue-100 w-full"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                    ></path>
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                    ></path>
                                                </svg>
                                                BUKA DOKUMEN FULL
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Message Display -->
                            <div
                                v-if="form.errors[`file_${doc.id}`]"
                                class="mt-3 flex items-center gap-2 p-2.5 rounded-lg bg-rose-50 border border-rose-100"
                            >
                                <svg
                                    class="w-4 h-4 text-rose-500 shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span
                                    class="text-[11px] text-rose-600 font-bold leading-tight"
                                    >{{ form.errors[`file_${doc.id}`] }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Action Bar -->
                <div
                    class="flex justify-end items-center gap-3 mt-10"
                    v-if="!isSubmitted"
                >
                    <button
                        type="button"
                        @click="form.reset()"
                        class="px-6 py-2.5 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all text-sm"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-8 py-2.5 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/20 disabled:opacity-50 text-sm flex items-center gap-2"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin h-4 w-4 text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </SupplierLayout>

    <!-- Modal Konfirmasi Simpan Data -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <!-- Background Overlay -->
            <div
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity"
                @click="showConfirmModal = false"
            ></div>

            <!-- Modal Content -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    class="relative w-full max-w-[420px] transform overflow-hidden rounded-[24px] bg-white shadow-2xl transition-all border border-slate-100"
                >
                    <div class="p-6">
                        <!-- Header: Icon + Title -->
                        <div class="flex items-start gap-4 mb-5">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-100"
                            >
                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div class="pt-0.5">
                                <h3
                                    class="text-lg font-bold text-slate-900 leading-tight"
                                >
                                    Konfirmasi Profil Perusahaan
                                </h3>
                                <p class="text-[13px] text-slate-500 mt-1">
                                    Data akan diperiksa oleh Admin.
                                </p>
                            </div>
                        </div>

                        <!-- Compact Information Box -->
                        <div
                            class="mb-5 p-4 rounded-xl bg-slate-50 border border-slate-100/50"
                        >
                            <p
                                class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3"
                            >
                                Ringkasan Data:
                            </p>
                            <div class="space-y-2.5">
                                <div
                                    class="flex items-center gap-2 text-[13px] text-slate-600"
                                >
                                    <svg
                                        class="h-4 w-4 text-emerald-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="3"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    <span>Informasi Profil & PIC</span>
                                </div>
                                <div
                                    class="flex items-center gap-2 text-[13px] text-slate-600"
                                >
                                    <svg
                                        class="h-4 w-4 text-emerald-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="3"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    <span>Rekening & Dokumen Legalitas</span>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Warning -->
                        <div
                            class="flex items-start gap-3 mb-6 p-3 rounded-xl bg-amber-50 border border-amber-100/50"
                        >
                            <svg
                                class="h-4 w-4 text-amber-600 shrink-0 mt-0.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            <p
                                class="text-[11px] font-medium text-amber-900 leading-normal"
                            >
                                Setelah diajukan, data akan dikunci dan tidak
                                dapat diubah selama periode seleksi.
                            </p>
                        </div>

                        <!-- Action Buttons: Side-by-Side -->
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="showConfirmModal = false"
                                class="flex-1 px-4 py-2.5 text-[13px] font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-xl transition-all"
                            >
                                Periksa Lagi
                            </button>
                            <button
                                type="button"
                                @click="confirmSubmit"
                                :disabled="form.processing"
                                class="flex-1 inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-[13px] font-bold text-white shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95 disabled:opacity-70"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                Ya, Ajukan
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>

    <!-- Modal Notifikasi (Sukses / Gagal) -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showNotificationModal"
            class="fixed inset-0 z-[110] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <!-- Background Overlay -->
            <div
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity"
                @click="closeNotification"
            ></div>

            <!-- Modal Content -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    class="relative w-full max-w-[380px] transform overflow-hidden rounded-[24px] bg-white shadow-2xl transition-all border border-slate-100 p-6"
                >
                    <div class="text-center">
                        <!-- Icon -->
                        <div
                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-full mb-4"
                            :class="{
                                'bg-emerald-100 text-emerald-600':
                                    notificationType === 'success',
                                'bg-rose-100 text-rose-600':
                                    notificationType === 'error',
                            }"
                        >
                            <!-- Success Checkmark Icon -->
                            <svg
                                v-if="notificationType === 'success'"
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <!-- Error X Icon -->
                            <svg
                                v-else
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </div>

                        <!-- Title -->
                        <h3
                            class="text-lg font-bold text-slate-900 leading-tight"
                        >
                            {{
                                notificationType === "success"
                                    ? "Berhasil!"
                                    : "Gagal!"
                            }}
                        </h3>

                        <!-- Message -->
                        <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                            {{ notificationMessage }}
                        </p>

                        <!-- Close Button -->
                        <div class="mt-6">
                            <button
                                type="button"
                                @click="closeNotification"
                                class="w-full inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95"
                                :class="{
                                    'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-100':
                                        notificationType === 'success',
                                    'bg-rose-600 hover:bg-rose-700 shadow-rose-100':
                                        notificationType === 'error',
                                }"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
/* Custom Scrollbar */
main::-webkit-scrollbar {
    width: 6px;
}
main::-webkit-scrollbar-track {
    background: transparent;
}
main::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
main::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
