<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import AdminLayout from "../../../Layouts/AdminLayout.vue";

// Props dari controller (data awal saat halaman pertama dimuat)
const props = defineProps({
    suppliers: { type: Object, default: () => ({ data: [] }) },
    years: { type: Array, default: () => [] },
});

// State reaktif
const suppliers = ref(props.suppliers.data);
const pagination = ref(props.suppliers);
const currentPage = ref(props.suppliers.current_page || 1);
const years = ref(props.years);
const isLoading = ref(false);

// State untuk Pencarian dan Filter
const searchQuery = ref("");
const selectedYear = ref("");
const perPage = ref(10);
const sortKey = ref("nama_perusahaan"); 
const sortOrder = ref("asc");

// State untuk Modal Detail Supplier
const showDetailModal = ref(false);
const selectedSupplier = ref(null);

// State untuk Modal Konfirmasi Disetujui/Ditolak
const showConfirmModal = ref(false);
const confirmAction = ref(""); // 'approve' atau 'reject'
const catatanAdmin = ref("");
const isSubmitting = ref(false);

// State untuk Modal Import Excel
const showImportModal = ref(false);

// State untuk Modal Notifikasi
const showNotificationModal = ref(false);
const notificationType = ref("success"); // 'success' or 'error'
const notificationMessage = ref("");

const triggerNotification = (type, message) => {
    notificationType.value = type;
    notificationMessage.value = message;
    showNotificationModal.value = true;
};

const closeNotification = () => {
    showNotificationModal.value = false;
};

// State untuk Modal Konfirmasi Hapus Supplier
const showDeleteConfirmModal = ref(false);
const supplierIdToDelete = ref(null);

const confirmDeleteSupplier = (id) => {
    supplierIdToDelete.value = id;
    showDeleteConfirmModal.value = true;
};

// Hapus Supplier via Axios
const executeDeleteSupplier = async () => {
    showDeleteConfirmModal.value = false;
    if (!supplierIdToDelete.value) return;

    try {
        const response = await axios.delete(
            `/admin/supplier/data/${supplierIdToDelete.value}`,
        );
        triggerNotification(
            "success",
            response.data.message || "Data supplier berhasil dihapus",
        );
        await fetchSuppliers(); // refresh tabel
    } catch (err) {
        triggerNotification(
            "error",
            err.response?.data?.message ||
                "Gagal menghapus supplier. Silakan coba lagi.",
        );
    } finally {
        supplierIdToDelete.value = null;
    }
};

// Refresh data dari server via Axios (dipanggil setelah setiap aksi CRUD)
const fetchSuppliers = async (page = 1) => {
    try {
        isLoading.value = true;
        currentPage.value = page;
        const response = await axios.get("/admin/supplier/data", {
            params: {
                page: page,
                search: searchQuery.value,
                tahun: selectedYear.value,
                per_page: perPage.value,
            }
        });
        
        if (response.data.status === "success") {
            suppliers.value = response.data.data.suppliers.data;
            pagination.value = response.data.data.suppliers;
            years.value = response.data.data.years;
        }
    } catch (err) {
        console.error("Gagal memuat data supplier:", err);
    } finally {
        isLoading.value = false;
    }
};

// Computed untuk Link Pagination (seperti KlasifikasiView)
const paginationLinks = computed(() => {
    if (!pagination.value.links) return [];
    return pagination.value.links
        .filter(l => !l.label.includes('Previous') && !l.label.includes('Next') && l.label !== '...')
        .map(l => ({
            ...l,
            page: parseInt(l.label)
        }));
});

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortOrder.value = "asc";
    }
};

// Logic Pencarian & Filter (Sekarang server-side, computed ini hanya sorting data yang tampil di halaman aktif)
const filteredSuppliers = computed(() => {
    let result = [...suppliers.value];

    return result.sort((a, b) => {
        let valA = a[sortKey.value];
        let valB = b[sortKey.value];

        // Normalisasi untuk string agar tidak case-sensitive
        if (typeof valA === "string") valA = valA.toLowerCase();
        if (typeof valB === "string") valB = valB.toLowerCase();

        if (valA < valB) return sortOrder.value === "asc" ? -1 : 1;
        if (valA > valB) return sortOrder.value === "asc" ? 1 : -1;
        return 0;
    });
});


// Buka Modal Detail Supplier
const openDetailModal = (supplier) => {
    selectedSupplier.value = supplier;
    showDetailModal.value = true;
};

// Handle Export Excel (tidak perlu Axios, cukup redirect browser)
const handleExport = () => {
    const params = new URLSearchParams({
        year: selectedYear.value,
        search: searchQuery.value,
    });
    window.location.href = `/admin/supplier/export?${params.toString()}`;
};

// Handle Import Excel via Axios + FormData
const triggerFileInput = () =>
    document.getElementById("excel-file-input").click();

const onFileChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("file", file);

    try {
        const response = await axios.post("/admin/supplier/import", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        showImportModal.value = false;
        triggerNotification(
            "success",
            response.data.message || "Data Excel berhasil diimport!",
        );
        await fetchSuppliers(); // refresh tabel
    } catch (err) {
        triggerNotification(
            "error",
            err.response?.data?.message || "Gagal mengimport data.",
        );
    }
};

// Helper Fungsi untuk Deteksi Format Berkas
const getFileType = (path) => {
    if (!path) return "other";
    const ext = path.split(".").pop().toLowerCase();
    if (["jpg", "jpeg", "png", "gif", "webp"].includes(ext)) return "image";
    if (ext === "pdf") return "pdf";
    return "other";
};

// Buka Modal Konfirmasi
const openConfirm = (action) => {
    confirmAction.value = action;
    catatanAdmin.value = "";
    showConfirmModal.value = true;
};

// Submit Keputusan Approve/Reject via Axios
const submitDecision = async () => {
    if (confirmAction.value === "reject" && !catatanAdmin.value.trim()) {
        triggerNotification("error", "Alasan penolakan wajib diisi!");
        return;
    }

    isSubmitting.value = true;

    try {
        let response;

        if (confirmAction.value === "approve") {
            response = await axios.post(
                `/admin/supplier/data/${selectedSupplier.value.id}/approve`,
                {
                    skor_kelengkapan_dokumen: 2,
                    skor_nib: 2,
                    skor_npwp: 2,
                    skor_akta_pendirian: 2,
                    skor_izin_usaha: 2,
                    skor_izin_khusus: 2,
                    skor_sk_domisili: 2,
                    skor_laporan_keuangan: 2,
                    catatan: "Disetujui otomatis melalui peninjauan cepat",
                },
            );
            triggerNotification(
                "success",
                response.data.message || "Supplier berhasil diloloskan!",
            );
        } else {
            response = await axios.post(
                `/admin/supplier/data/${selectedSupplier.value.id}/reject`,
                { catatan_admin: catatanAdmin.value },
            );
            triggerNotification(
                "success",
                response.data.message || "Supplier berhasil ditolak.",
            );
        }

        showConfirmModal.value = false;
        showDetailModal.value = false;
        await fetchSuppliers(); // refresh tabel setelah aksi
    } catch (err) {
        console.error(err);
        triggerNotification(
            "error",
            err.response?.data?.message || "Gagal menyimpan keputusan.",
        );
    } finally {
        isSubmitting.value = false;
    }
};

// Rekomendasi Sistem computed property
const systemRecommendation = computed(() => {
    if (!selectedSupplier.value) return null;

    const docs = selectedSupplier.value.documents || [];
    const requiredDocs = ["nib", "npwp", "akta_pendirian", "izin_usaha"];

    // Hitung dokumen yang benar-benar ada/diupload
    const uploadedDocs = docs.filter(
        (doc) => doc.has_document && doc.file_path,
    );
    const uploadedCount = uploadedDocs.length;
    const totalCount = 7;

    // Deteksi berkas wajib yang hilang
    const missingRequired = requiredDocs.filter((req) => {
        const doc = docs.find((d) => d.jenis_dokumen === req);
        return !doc || !doc.has_document || !doc.file_path;
    });

    const completenessPercentage = Math.round(
        (uploadedCount / totalCount) * 100,
    );

    let status = "";
    let colorClass = "";
    let borderClass = "";
    let textClass = "";
    let description = "";

    if (missingRequired.length === 0 && uploadedCount === totalCount) {
        status = "Sangat Direkomendasikan (Lolos)";
        colorClass = "bg-emerald-50/50";
        borderClass = "border-emerald-100";
        textClass = "text-emerald-700";
        description =
            "Seluruh dokumen legalitas lengkap (7/7) termasuk NIB, NPWP, Akta Pendirian, dan Izin Usaha. Supplier sangat memenuhi syarat untuk diloloskan.";
    } else if (missingRequired.length === 0) {
        status = "Direkomendasikan (Lolos)";
        colorClass = "bg-emerald-50/30";
        borderClass = "border-emerald-100/60";
        textClass = "text-emerald-600";
        description = `Dokumen wajib lengkap (${uploadedCount}/7). NIB, NPWP, Akta Pendirian, dan Izin Usaha telah terverifikasi. Rekomendasi sistem: Lolos dengan catatan beberapa dokumen pendukung dapat menyusul.`;
    } else if (uploadedCount >= 4) {
        status = "Pertimbangkan (Butuh Review)";
        colorClass = "bg-amber-50/50";
        borderClass = "border-amber-100";
        textClass = "text-amber-700";
        description = `Dokumen cukup lengkap (${uploadedCount}/7), namun terdapat dokumen wajib yang belum lengkap: ${missingRequired
            .map((d) => d.toUpperCase())
            .join(
                ", ",
            )}. Harap tinjau lebih lanjut sebelum mengambil keputusan.`;
    } else {
        status = "Tidak Direkomendasikan (Tidak Lolos)";
        colorClass = "bg-rose-50/50";
        borderClass = "border-rose-100";
        textClass = "text-rose-700";
        description = `Kelengkapan dokumen sangat rendah (${uploadedCount}/7). Dokumen wajib yang hilang: ${missingRequired
            .map((d) => d.toUpperCase())
            .join(", ")}. Sistem merekomendasikan untuk menolak supplier ini.`;
    }

    return {
        status,
        colorClass,
        borderClass,
        textClass,
        description,
        uploadedCount,
        totalCount,
        completenessPercentage,
        missingRequired,
    };
});
</script>

<template>
    <Head title="Data Supplier | Admin WISE" />

    <AdminLayout>
        <!-- SECTION 1: Judul Header (Atas) -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">
                    Data Supplier
                </h1>
            </div>
            <!-- SECTION 1.5: Tombol Aksi Import & Export (Bawah) -->
            <div
                class="bg-white p-5 rounded-lg shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6"
            >
                <div>
                    <h1 class="text-lg font-semibold text-gray-700">
                        Data Supplier
                    </h1>
                    <p class="text-sm text-gray-500">
                        Kelola dan tinjau seluruh data legalitas perusahaan
                        supplier terdaftar.
                    </p>
                </div>
                <!-- Action Buttons (Export & Import) -->
                <div class="flex items-center gap-3">
                    <button
                        @click="triggerFileInput"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 transition-all shadow-sm"
                    >
                        <svg
                            class="w-4 h-4 text-slate-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                            />
                        </svg>
                        IMPORT EXCEL
                    </button>
                    <input
                        type="file"
                        id="excel-file-input"
                        class="hidden"
                        accept=".xlsx, .xls"
                        @change="onFileChange"
                    />
                    <button
                        @click="handleExport"
                        class="inline-flex items-center gap-2 px-5 py-3 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 active:scale-95 transition-all shadow-md shadow-emerald-100"
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
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        EXPORT EXCEL
                    </button>
                </div>
            </div>

            <!-- SECTION 2: Filters & Search Section (Tampilan Bersih Tanpa Bungkus Card Putih) -->
            <div
                class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6"
            >
                <!-- Search Input -->
                <div class="relative w-full md:flex-1">
                    <span
                        class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none"
                    >
                        <svg
                            class="w-5 h-5 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </span>
                    <input
                        v-model="searchQuery"
                        @input="fetchSuppliers(1)"
                        type="text"
                        placeholder="Cari nama perusahaan, email, atau alamat..."
                        class="w-full pl-11 pr-4 py-3 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 shadow-sm"
                    />
                </div>

                <!-- Right Filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span
                            class="text-sm font-medium text-slate-400"
                            >Tampilkan</span
                        >
                        <select
                            v-model="perPage"
                            @change="fetchSuppliers(1)"
                            class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm"
                        >
                            <option :value="10">10 Data</option>
                            <option :value="25">25 Data</option>
                            <option :value="50">50 Data</option>
                            <option :value="100">100 Data</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="selectedYear"
                            @change="fetchSuppliers(1)"
                            class="bg-white border border-slate-200 text-slate-700 text-sm rounded-xl py-2.5 px-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium shadow-sm"
                        >
                            <option value="">Semua Tahun</option>
                            <option
                                v-for="year in years"
                                :key="year"
                                :value="year"
                            >
                                {{ year }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Tabel Supplier di Bawahnya -->
            <div
                class="bg-white rounded-lg border border-slate-100 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                                <!-- Kolom No (Sort by ID) -->
                                <th @click="toggleSort('id')" class="py-5 px-6 w-16 text-center cursor-pointer hover:bg-slate-100 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        No
                                        <svg v-if="sortKey === 'id'" :class="sortOrder === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </th>
                                
                                <!-- Kolom Nama Perusahaan -->
                                <th @click="toggleSort('nama_perusahaan')" class="py-5 px-6 cursor-pointer hover:bg-slate-100 transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        Nama Perusahaan
                                        <svg v-if="sortKey === 'nama_perusahaan'" :class="sortOrder === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </th>

                                <!-- Kolom Email -->
                                <th @click="toggleSort('email_perusahaan')" class="py-5 px-6 cursor-pointer hover:bg-slate-100 transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        Email Perusahaan
                                        <svg v-if="sortKey === 'email_perusahaan'" :class="sortOrder === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </th>

                                <!-- Kolom Alamat -->
                                <th @click="toggleSort('alamat_perusahaan')" class="py-5 px-6 cursor-pointer hover:bg-slate-100 transition-colors">
                                    <div class="flex items-center gap-1.5">
                                        Alamat Kantor
                                        <svg v-if="sortKey === 'alamat_perusahaan'" :class="sortOrder === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </th>

                                <!-- Kolom Status -->
                                <th @click="toggleSort('status')" class="py-5 px-6 w-48 text-center cursor-pointer hover:bg-slate-100 transition-colors">
                                    <div class="flex items-center justify-center gap-1.5">
                                        Status
                                        <svg v-if="sortKey === 'status'" :class="sortOrder === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </th>

                                <!-- Kolom Aksi (Tanpa Sorting) -->
                                <th class="py-5 px-6 w-28 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(
                                    supplier, idx
                                ) in filteredSuppliers"
                                :key="supplier.id"
                                class="hover:bg-slate-50/50 transition-colors group"
                            >
                                <!-- No -->
                                <td
                                    class="py-4 px-6 text-center text-sm font-bold text-slate-500"
                                >
                                    {{ idx + 1 + ((pagination.current_page || 1) - 1) * (pagination.per_page || 10) }}
                                </td>
                                <!-- Nama Perusahaan -->
                                <td class="py-4 px-6">
                                    <div
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        {{ supplier.nama_perusahaan || "N/A" }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        Telp:
                                        {{ supplier.no_telp_perusahaan || "-" }}
                                    </div>
                                </td>
                                <!-- Email -->
                                <td
                                    class="py-4 px-6 text-sm font-semibold text-slate-600"
                                >
                                    {{ supplier.email_perusahaan || "-" }}
                                </td>
                                <!-- Alamat -->
                                <td
                                    class="py-4 px-6 text-sm text-slate-500 max-w-xs truncate"
                                >
                                    {{ supplier.alamat_perusahaan || "-" }}
                                </td>
                                <!-- Status Badge -->
                                <td class="py-4 px-6 text-center">
                                    <span
                                        v-if="supplier.status === 'approved'"
                                        class="inline-flex items-center gap-1.5 px-5 py-1 rounded-3xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 whitespace-nowrap"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        Disetujui
                                    </span>
                                    <span
                                        v-else-if="
                                            supplier.status === 'submitted'
                                        "
                                        class="inline-flex items-center gap-1.5 px-5 py-1 rounded-3xl text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 whitespace-nowrap"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-blue-500"
                                        ></span>
                                        Menunggu Review
                                    </span>
                                    <span
                                        v-else-if="
                                            supplier.status === 'rejected'
                                        "
                                        class="inline-flex items-center gap-1.5 px-5 py-1 rounded-3xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100 whitespace-nowrap"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-rose-500"
                                        ></span>
                                        Ditolak
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200 whitespace-nowrap"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-slate-400"
                                        ></span>
                                        Draft
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td class="py-4 px-6 text-center">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <button
                                            @click="openDetailModal(supplier)"
                                            class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm group/btn"
                                            title="Lihat Detail Profil"
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
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="
                                                confirmDeleteSupplier(
                                                    supplier.id,
                                                )
                                            "
                                            class="p-2 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm"
                                            title="Hapus Supplier"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredSuppliers.length === 0">
                                <td colspan="6" class="py-16 text-center">
                                    <div
                                        class="flex flex-col items-center justify-center"
                                    >
                                        <div
                                            class="h-16 w-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100 mb-4"
                                        >
                                            <svg
                                                class="w-8 h-8"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v4.5m15 3.5v3a2 2 0 01-2 2H6a2 2 0 01-2-2v-3m15 0H4"
                                                />
                                            </svg>
                                        </div>
                                        <h3
                                            class="text-sm font-bold text-slate-700"
                                        >
                                            Tidak ada data supplier ditemukan
                                        </h3>
                                        <p
                                            class="text-xs text-slate-400 mt-1 max-w-xs leading-relaxed"
                                        >
                                            Coba ganti filter pencarian atau
                                            pastikan supplier sudah memproses
                                            berkas mereka.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- NEW: Pagination Section -->
                <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
                    <p class="text-xs text-slate-400 font-medium">
                        Menampilkan {{ pagination.from || 0 }} - {{ pagination.to || 0 }} dari {{ pagination.total || 0 }} data
                    </p>
                    <div class="flex items-center gap-1">
                        <button
                            v-for="link in paginationLinks"
                            :key="link.label"
                            @click="fetchSuppliers(link.page)"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="link.active 
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' 
                                : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50'"
                            :disabled="!link.page"
                        >
                            {{ link.label }}
                        </button>
                    </div>
                </div>
            </div>
    </AdminLayout>

    <!-- SECTION 4: Detail Modal (Supplier Info) -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="showDetailModal = false"
            ></div>

            <!-- Modal Content (Scrollable Container) -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-4"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 translate-y-4"
            >
                <div
                    class="relative w-full max-w-3xl max-h-[90vh] flex flex-col transform overflow-hidden rounded-[28px] bg-white shadow-2xl transition-all border border-slate-100"
                >
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between px-8 py-5 border-b border-slate-100 shrink-0 bg-slate-50/50"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
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
                                    />
                                </svg>
                            </span>
                            <div>
                                <h3
                                    class="text-lg font-black text-slate-900 leading-none"
                                >
                                    Detail Profil Supplier
                                </h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    ID Pengajuan: #{{ selectedSupplier.id }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="showDetailModal = false"
                            class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="flex-1 overflow-y-auto px-8 py-6 space-y-8">
                        <!-- Row 1: Informasi Kantor & Perusahaan -->
                        <div>
                            <h4
                                class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-4"
                            >
                                I. Profil Perusahaan
                            </h4>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 rounded-2xl border border-slate-100 p-5 bg-slate-50/30"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Nama Perusahaan</span
                                    >
                                    <span
                                        class="text-sm font-bold text-slate-800 mt-1 block"
                                    >
                                        {{
                                            selectedSupplier.nama_perusahaan ||
                                            "-"
                                        }}
                                    </span>
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Telepon Kantor</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-slate-700 mt-1 block"
                                    >
                                        {{
                                            selectedSupplier.no_telp_perusahaan ||
                                            "-"
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="md:col-span-2 border-t border-slate-100 pt-3"
                                >
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Alamat Kantor</span
                                    >
                                    <span
                                        class="text-sm text-slate-600 mt-1 block"
                                    >
                                        {{
                                            selectedSupplier.alamat_perusahaan ||
                                            "-"
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="md:col-span-2 border-t border-slate-100 pt-3"
                                >
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Email Kantor</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-slate-700 mt-1 block"
                                    >
                                        {{
                                            selectedSupplier.email_perusahaan ||
                                            "-"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Kontak PIC -->
                        <div>
                            <h4
                                class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-4"
                            >
                                II. Penanggung Jawab (PIC)
                            </h4>
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-6 rounded-2xl border border-slate-100 p-5 bg-slate-50/30"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Nama Lengkap PIC</span
                                    >
                                    <span
                                        class="text-sm font-bold text-slate-800 mt-1 block"
                                    >
                                        {{ selectedSupplier.nama_pic || "-" }}
                                    </span>
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Telepon PIC</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-slate-700 mt-1 block"
                                    >
                                        {{
                                            selectedSupplier.no_telp_pic || "-"
                                        }}
                                    </span>
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Email PIC</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-slate-700 mt-1 block"
                                    >
                                        {{ selectedSupplier.email_pic || "-" }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Rekening Bank -->
                        <div>
                            <h4
                                class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-4"
                            >
                                III. Informasi Rekening Bank
                            </h4>
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-6 rounded-2xl border border-slate-100 p-5 bg-slate-50/30"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Nama Bank</span
                                    >
                                    <span
                                        class="text-sm font-bold text-slate-800 mt-1 block"
                                    >
                                        {{ selectedSupplier.nama_bank || "-" }}
                                    </span>
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Nomor Rekening</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-indigo-600 mt-1 block"
                                    >
                                        {{
                                            selectedSupplier.no_rekening || "-"
                                        }}
                                    </span>
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                        >Nama Pemilik Rekening</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-slate-700 mt-1 block"
                                    >
                                        {{ selectedSupplier.atas_nama || "-" }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 4: Dokumen Legalitas (Dengan Live Preview) -->
                        <div>
                            <h4
                                class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-4"
                            >
                                IV. Berkas & Dokumen Legalitas
                            </h4>
                            <div
                                v-if="
                                    selectedSupplier.documents &&
                                    selectedSupplier.documents.length > 0
                                "
                                class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                            >
                                <div
                                    v-for="doc in selectedSupplier.documents"
                                    :key="doc.id"
                                    class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm flex flex-col"
                                >
                                    <div
                                        class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between"
                                    >
                                        <span
                                            class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                        >
                                            {{
                                                doc.jenis_dokumen.toUpperCase()
                                            }}
                                        </span>
                                    </div>

                                    <!-- Live Preview Area -->
                                    <div
                                        class="aspect-[16/9] w-full bg-slate-100 flex-1 relative flex items-center justify-center"
                                    >
                                        <template
                                            v-if="
                                                getFileType(doc.file_path) ===
                                                'image'
                                            "
                                        >
                                            <img
                                                :src="
                                                    '/storage/' + doc.file_path
                                                "
                                                class="w-full h-full object-contain"
                                                alt="Preview"
                                            />
                                        </template>
                                        <template
                                            v-else-if="
                                                getFileType(doc.file_path) ===
                                                'pdf'
                                            "
                                        >
                                            <iframe
                                                :src="
                                                    '/storage/' + doc.file_path
                                                "
                                                class="w-full h-full"
                                                frameborder="0"
                                            ></iframe>
                                        </template>
                                        <template v-else>
                                            <div
                                                class="flex flex-col items-center justify-center p-6 text-center"
                                            >
                                                <svg
                                                    class="w-12 h-12 text-slate-400 mb-2"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                    />
                                                </svg>
                                                <span
                                                    class="text-[10px] font-bold text-slate-500 uppercase tracking-wider"
                                                    >Dokumen Legal</span
                                                >
                                            </div>
                                        </template>
                                    </div>

                                    <div
                                        class="p-3 bg-white border-t border-slate-100"
                                    >
                                        <a
                                            :href="'/storage/' + doc.file_path"
                                            target="_blank"
                                            class="flex items-center justify-center gap-2 text-[10px] font-bold text-indigo-600 hover:text-indigo-700 bg-indigo-50 px-3 py-2 rounded-lg border border-indigo-100 w-full transition-colors"
                                        >
                                            BUKA BERKAS UTAMA
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- No Documents State -->
                            <div
                                v-else
                                class="text-center py-8 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50"
                            >
                                <svg
                                    class="w-12 h-12 text-slate-300 mx-auto mb-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                                <span class="text-xs font-bold text-slate-400">
                                    Belum ada dokumen legalitas yang di-upload
                                </span>
                            </div>
                        </div>

                        <!-- Row 5: Rekomendasi Sistem & Keputusan Admin -->
                        <div>
                            <h4
                                class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-4"
                            >
                                V. Rekomendasi & Keputusan Sistem
                            </h4>

                            <!-- Box Rekomendasi -->
                            <div
                                :class="[
                                    'rounded-2xl border p-5 flex flex-col justify-between gap-4 transition-all duration-300 shadow-sm bg-white',
                                    systemRecommendation.colorClass,
                                    systemRecommendation.borderClass,
                                ]"
                            >
                                <div class="space-y-2.5">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[10px] font-black uppercase tracking-wider text-slate-400"
                                            >Rekomendasi Sistem:</span
                                        >
                                        <span
                                            :class="[
                                                'text-[10px] font-black px-2.5 py-0.5 rounded-full bg-white border shadow-sm',
                                                systemRecommendation.textClass,
                                            ]"
                                        >
                                            {{ systemRecommendation.status }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-xs text-slate-600 font-medium leading-relaxed max-w-2xl"
                                    >
                                        {{ systemRecommendation.description }}
                                    </p>
                                    <div
                                        class="flex flex-wrap items-center gap-x-4 gap-y-2 pt-1 border-t border-slate-100/50 mt-2"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                                >Kelengkapan:</span
                                            >
                                            <span
                                                class="text-xs font-bold text-slate-700 bg-white px-2 py-0.5 border border-slate-200/60 rounded-md"
                                            >
                                                {{
                                                    systemRecommendation.uploadedCount
                                                }}/{{
                                                    systemRecommendation.totalCount
                                                }}
                                                Dokumen
                                            </span>
                                        </div>
                                        <div
                                            v-if="
                                                systemRecommendation
                                                    .missingRequired.length > 0
                                            "
                                            class="flex flex-wrap items-center gap-1.5"
                                        >
                                            <span
                                                class="text-[10px] font-black text-rose-500 uppercase tracking-wider mr-0.5"
                                                >Dokumen Wajib Belum Ada:</span
                                            >
                                            <span
                                                v-for="missing in systemRecommendation.missingRequired"
                                                :key="missing"
                                                class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-100 uppercase tracking-wider"
                                            >
                                                {{ missing.replace("_", " ") }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi Lolos / Tidak Lolos (Besar & Keren) -->
                            <div
                                class="mt-8 pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-4"
                            >
                                <template
                                    v-if="
                                        selectedSupplier.status === 'submitted'
                                    "
                                >
                                    <button
                                        @click="openConfirm('reject')"
                                        class="flex-1 inline-flex items-center justify-center gap-3 px-8 py-5 text-sm font-black text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/60 rounded-2xl active:scale-[0.98] transition-all shadow-sm"
                                    >
                                        <svg
                                            class="w-5 h-5 shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2.5"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        TIDAK LOLOS KUALIFIKASI
                                    </button>

                                    <button
                                        @click="openConfirm('approve')"
                                        class="flex-1 inline-flex items-center justify-center gap-3 px-8 py-5 text-sm font-black text-white bg-emerald-600 hover:bg-emerald-700 border border-transparent rounded-2xl active:scale-[0.98] transition-all shadow-md shadow-emerald-100"
                                    >
                                        <svg
                                            class="w-5 h-5 shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2.5"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        LOLOS KUALIFIKASI
                                    </button>
                                </template>

                                <div v-else class="w-full">
                                    <div
                                        :class="[
                                            'w-full rounded-2xl p-4 border flex items-center justify-between gap-4 font-bold text-sm',
                                            selectedSupplier.status ===
                                            'approved'
                                                ? 'bg-emerald-50 border-emerald-100 text-emerald-800'
                                                : 'bg-rose-50 border-rose-100 text-rose-800',
                                        ]"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span
                                                :class="[
                                                    'h-8 w-8 rounded-xl flex items-center justify-center border',
                                                    selectedSupplier.status ===
                                                    'approved'
                                                        ? 'bg-white border-emerald-200 text-emerald-600'
                                                        : 'bg-white border-rose-200 text-rose-600',
                                                ]"
                                            >
                                                <svg
                                                    v-if="
                                                        selectedSupplier.status ===
                                                        'approved'
                                                    "
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                                <svg
                                                    v-else
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M6 18L18 6M6 6l12 12"
                                                    />
                                                </svg>
                                            </span>
                                            <div>
                                                <span
                                                    class="block text-[10px] font-black uppercase tracking-wider text-slate-400"
                                                    >Keputusan Akhir:</span
                                                >
                                                <span
                                                    class="text-sm font-black leading-none mt-0.5 block"
                                                >
                                                    {{
                                                        selectedSupplier.status ===
                                                        "approved"
                                                            ? "Supplier Telah Diloloskan"
                                                            : "Supplier Telah Ditolak"
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                        <span
                                            class="text-xs font-medium text-slate-400 font-sans"
                                        >
                                            Keputusan Bersifat Final
                                        </span>
                                    </div>

                                    <!-- Catatan Rejection Alasan jika ada -->
                                    <div
                                        v-if="
                                            selectedSupplier.status ===
                                                'rejected' &&
                                            selectedSupplier.catatan_admin
                                        "
                                        class="mt-3 p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs"
                                    >
                                        <span
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1"
                                            >Catatan Alasan Penolakan:</span
                                        >
                                        <p
                                            class="text-slate-600 font-medium leading-relaxed"
                                        >
                                            {{ selectedSupplier.catatan_admin }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>

    <!-- SECTION 5: Custom Confirmation Modal (Keputusan Final - Sleek & Compact) -->
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
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
        >
            <div
                class="relative w-full max-w-sm transform overflow-hidden rounded-[24px] bg-white p-5 shadow-2xl transition-all border border-slate-100"
            >
                <div class="flex flex-col items-center text-center">
                    <!-- Icon Warning/Danger -->
                    <div
                        :class="[
                            'h-12 w-12 rounded-xl flex items-center justify-center mb-3 shadow-sm border shrink-0',
                            confirmAction === 'approve'
                                ? 'bg-emerald-50 text-emerald-600 border-emerald-100'
                                : 'bg-rose-50 text-rose-600 border-rose-100',
                        ]"
                    >
                        <svg
                            v-if="confirmAction === 'approve'"
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>

                    <h3
                        class="text-base font-black text-slate-900 leading-snug"
                    >
                        {{
                            confirmAction === "approve"
                                ? "Konfirmasi Kelulusan"
                                : "Konfirmasi Penolakan"
                        }}
                    </h3>

                    <!-- Peringatan Utama (Tebal & Kontras) -->
                    <div
                        class="mt-2.5 px-3 py-2 bg-amber-50 border border-amber-100 rounded-xl flex items-start gap-2 text-left mb-3.5 w-full"
                    >
                        <svg
                            class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <p
                            class="text-[11px] font-bold text-amber-800 leading-relaxed"
                        >
                            PERINGATAN: Keputusan bersifat final dan tidak dapat
                            diubah kembali setelah disimpan.
                        </p>
                    </div>

                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                        Apakah Anda yakin menetapkan status supplier
                        <strong>{{ selectedSupplier?.nama_perusahaan }}</strong>
                        menjadi
                        <span
                            :class="
                                confirmAction === 'approve'
                                    ? 'text-emerald-600 font-bold'
                                    : 'text-rose-600 font-bold'
                            "
                        >
                            {{
                                confirmAction === "approve"
                                    ? "Lolos (Disetujui)"
                                    : "Tidak Lolos (Ditolak)"
                            }} </span
                        >?
                    </p>

                    <!-- Textarea Catatan Admin (Wajib diisi untuk penolakan) -->
                    <div
                        class="w-full text-left mb-4"
                        v-if="confirmAction === 'reject'"
                    >
                        <label
                            class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1"
                            >Alasan Penolakan (Wajib)</label
                        >
                        <textarea
                            v-model="catatanAdmin"
                            rows="2"
                            placeholder="Tulis alasan penolakan di sini..."
                            class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:bg-white transition-all placeholder:text-slate-400 resize-none font-medium text-slate-700"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-2">
                    <button
                        @click="showConfirmModal = false"
                        class="flex-1 px-3 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-xs font-bold active:scale-95 transition-all"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitDecision"
                        :disabled="isSubmitting"
                        :class="[
                            'flex-1 px-3 py-2.5 text-white rounded-xl text-xs font-bold active:scale-95 transition-all shadow-md flex items-center justify-center gap-1.5',
                            confirmAction === 'approve'
                                ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-100 disabled:bg-emerald-400'
                                : 'bg-rose-600 hover:bg-rose-700 shadow-rose-100 disabled:bg-rose-400',
                        ]"
                    >
                        <span
                            v-if="isSubmitting"
                            class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"
                        ></span>
                        <span>Ya, Simpan</span>
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal Konfirmasi Hapus Supplier -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showDeleteConfirmModal"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <!-- Background Overlay -->
            <div
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] transition-opacity"
                @click="showDeleteConfirmModal = false"
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
                    class="relative w-full max-w-[400px] transform overflow-hidden rounded-[24px] bg-white shadow-2xl transition-all border border-slate-100 p-6"
                >
                    <!-- Header: Icon + Title -->
                    <div class="flex items-start gap-4 mb-5">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 shadow-lg shadow-rose-50"
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
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </div>
                        <div class="pt-0.5">
                            <h3
                                class="text-lg font-bold text-slate-900 leading-tight"
                            >
                                Hapus Supplier
                            </h3>
                            <p class="text-[13px] text-slate-500 mt-1">
                                Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>

                    <!-- Warning Text -->
                    <div
                        class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-100/50"
                    >
                        <p
                            class="text-[13px] text-slate-600 leading-relaxed font-medium"
                        >
                            Apakah Anda yakin ingin menghapus data supplier ini
                            dari sistem? Seluruh data legalitas dan dokumen
                            terkait juga akan dihapus.
                        </p>
                    </div>

                    <!-- Action Buttons: Side-by-Side -->
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="showDeleteConfirmModal = false"
                            class="flex-1 px-4 py-2.5 text-[13px] font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-xl transition-all border border-slate-200"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="executeDeleteSupplier"
                            class="flex-1 inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-[13px] font-bold text-white shadow-lg shadow-rose-100 hover:bg-rose-700 transition-all active:scale-95"
                        >
                            Ya, Hapus
                        </button>
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
/* Transisi mulus untuk modal */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
