<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';

const props = defineProps({
  purchaseOrders: { type: Object, default: () => ({ data: [] }) },
  segment: { type: String, default: 'ongoing' },
  filters: { type: Object, default: () => ({}) },
  years: { type: Array, default: () => [] },
  poDescription: { type: String, default: '' },
});

const segments = [
  { key: 'ongoing', label: 'Sedang Berjalan' },
  { key: 'completed', label: 'Selesai' },
];

const monthOptions = [
  { value: 'all', label: 'Semua Bulan' },
  { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' }, { value: 4, label: 'April' },
  { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' }, { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
];

const search = ref(props.filters?.search ?? '');
const selectedMonth = ref(props.filters?.month ?? new Date().getMonth() + 1);
const selectedYear = ref(props.filters?.year ?? new Date().getFullYear());
const pagination = computed(() => props.purchaseOrders || { data: [] });
const pageInput = ref(pagination.value.current_page || 1);
watch(() => pagination.value.current_page, (p) => { pageInput.value = p || 1; });
const yearOptions = computed(() => props.years?.length ? props.years : [new Date().getFullYear()]);

const formatCurrency = (v) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(v || 0));

const statusConfig = {
  rfq:          { label: 'Accept Request',       color: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200', clickable: true,  pulse: false },
  verification: { label: 'Request Verification', color: 'bg-blue-100 text-blue-700 hover:bg-blue-200',         clickable: true,  pulse: false },
  request:      { label: 'Menunggu Admin',        color: 'bg-slate-100 text-slate-500',                          clickable: false, pulse: false },
  completeness: { label: 'Lengkapi Dokumen',      color: 'bg-purple-100 text-purple-700 hover:bg-purple-200',   clickable: true,  pulse: false },
  approved:     { label: 'Isi Data Shipment',     color: 'bg-teal-100 text-teal-700 hover:bg-teal-200',         clickable: true,  pulse: false },
  shipment:     { label: 'Dalam Perjalanan',      color: 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200',   clickable: true,  pulse: false },
  completed:    { label: 'Sudah Diterima',        color: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200', clickable: true, pulse: false },
  rejected:     { label: 'Ditolak',              color: 'bg-red-100 text-red-700',                              clickable: false, pulse: false },
};

const getStatusConfig = (status) => statusConfig[status] || { label: status, color: 'bg-slate-100 text-slate-500', clickable: false, pulse: false };

const applyFilters = (overrides = {}) => {
  router.get(route('supplier.purchase-orders.index'), {
    segment: props.segment, search: search.value || undefined,
    month: selectedMonth.value, year: selectedYear.value, ...overrides,
  }, { preserveState: true, replace: true });
};
const changeSegment = (k) => { if (k !== props.segment) applyFilters({ segment: k, page: 1 }); };
const goToPage = (p) => applyFilters({ page: Math.min(Math.max(p, 1), pagination.value.last_page || 1) });
const submitPageInput = () => { if (pageInput.value) goToPage(Number(pageInput.value)); };

/* ── Accept Request (RFQ → VERIFICATION / REJECTED) ── */
const showAcceptModal = ref(false);
const acceptingForm = useForm({});

const openAcceptModal = (po) => {
  activePo.value = po;
  showAcceptModal.value = true;
};

const submitAcceptRequest = () => {
  if (!activePo.value) return;
  acceptingForm.post(route('supplier.purchase-orders.accept-request', activePo.value.id), {
    preserveScroll: true, onSuccess: () => (showAcceptModal.value = false),
  });
};

const submitDeclineRequest = () => {
  if (!activePo.value) return;
  if (!confirm('Anda yakin ingin menolak request ini? Request akan masuk ke riwayat sebagai ditolak.')) return;
  acceptingForm.post(route('supplier.purchase-orders.decline-request', activePo.value.id), {
    preserveScroll: true, onSuccess: () => (showAcceptModal.value = false),
  });
};

/* ── Request Verification modal (VERIFICATION → COMPLETENESS / REQUEST) ── */
const showVerificationModal = ref(false);
const activePo = ref(null);
const verificationForm = useForm({ accept_as_is: false, items: [] });

const openVerificationModal = (po) => {
  activePo.value = po;
  verificationForm.accept_as_is = false;
  verificationForm.items = po.items.map((item) => ({
    purchase_order_item_id: item.id,
    barang_name: item.barang?.nama_barang || '-',
    subtype_name: item.subtype?.subtype_name || item.itemType?.type_name || '-',
    uom: item.uom || '-',
    original_price: item.unit_price ?? 0,
    original_quantity: item.quantity ?? 0,
    counter_price: item.counter_offered_price,
    counter_quantity: item.counter_offered_quantity,
    supplier_price: item.counter_offered_price ?? item.unit_price ?? 0,
    supplier_quantity: item.counter_offered_quantity ?? item.quantity ?? 0,
  }));
  showVerificationModal.value = true;
};

const submitAcceptAsIs = () => {
  if (!activePo.value) return;
  verificationForm.accept_as_is = true;
  verificationForm.post(route('supplier.purchase-orders.request-verification', activePo.value.id), {
    preserveScroll: true, onSuccess: () => (showVerificationModal.value = false),
  });
};

const submitWithChanges = () => {
  if (!activePo.value) return;
  verificationForm.accept_as_is = false;
  verificationForm.post(route('supplier.purchase-orders.request-verification', activePo.value.id), {
    preserveScroll: true, onSuccess: () => (showVerificationModal.value = false),
  });
};

const onNumberInput = (obj, key, event) => {
  let val = event.target.value.replace(/\D/g, '');
  let num = val ? parseInt(val, 10) : '';
  obj[key] = num;
  event.target.value = num !== '' ? new Intl.NumberFormat('id-ID').format(num) : '';
};

const formatInputValue = (val) => {
  if (val === null || val === undefined || val === '') return '';
  return new Intl.NumberFormat('id-ID').format(val);
};

/* ── Shipment modal ── */
const showShipmentModal = ref(false);
const shipmentForm = useForm({
  delivery_type: 'self',
  driver_name: '', vehicle_plate: '', phone_number: '',
  courier_provider: '', tracking_number: '',
  shipment_notes: '', supplementary_doc_path: '',
});

const openShipmentModal = (po) => {
  activePo.value = po;
  shipmentForm.delivery_type = po.delivery_type || 'self';
  shipmentForm.driver_name = po.driver_name || '';
  shipmentForm.vehicle_plate = po.vehicle_plate || '';
  shipmentForm.phone_number = po.phone_number || '';
  shipmentForm.courier_provider = po.courier_provider || '';
  shipmentForm.tracking_number = po.tracking_number || '';
  shipmentForm.shipment_notes = po.shipment_notes || '';
  shipmentForm.supplementary_doc_path = po.supplementary_doc_path || '';
  showShipmentModal.value = true;
};

const submitShipment = () => {
  if (!activePo.value) return;
  shipmentForm.post(route('supplier.purchase-orders.shipment.store', activePo.value.id), {
    preserveScroll: true, onSuccess: () => (showShipmentModal.value = false),
  });
};

/* ── Completeness Modal ── */
const showCompletenessModal = ref(false);
const completenessForm = useForm({
  document_path: '',
});

const openCompletenessModal = (po) => {
  activePo.value = po;
  completenessForm.document_path = ''; // reset or fill with existing if we had it
  showCompletenessModal.value = true;
};

const submitCompleteness = () => {
  if (!activePo.value) return;
  completenessForm.post(route('supplier.purchase-orders.completeness.store', activePo.value.id), {
    preserveScroll: true, onSuccess: () => (showCompletenessModal.value = false),
  });
};

/* ── Completed PO Detail modal ── */
const showCompletedModal = ref(false);
const openCompletedModal = (po) => {
  activePo.value = po;
  showCompletedModal.value = true;
};

/* ── Status click handler ── */
const onStatusClick = (po) => {
  if (po.status === 'rfq') return openAcceptModal(po);
  if (po.status === 'verification') return openVerificationModal(po);
  if (po.status === 'completeness') return openCompletenessModal(po);
  if (po.status === 'approved' || po.status === 'shipment') return openShipmentModal(po);
  if (po.status === 'completed') return openCompletedModal(po);
};
</script>

<template>
  <Head title="Purchase Orders - Supplier" />
  <SupplierLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50">
      <div class="max-w-7xl mx-auto px-6 py-8">
        <div>
          <h1 class="text-3xl font-bold text-slate-800">Purchase Order</h1>
          <p class="mt-2 text-sm text-slate-500">{{ poDescription }}</p>
        </div>

        <!-- Filters -->
        <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
          <div class="grid gap-4 md:grid-cols-3">
            <div class="col-span-1 md:col-span-2">
              <label class="text-xs font-semibold text-slate-500">Search</label>
              <div class="mt-2 flex gap-2">
                <input v-model="search" type="text" placeholder="Cari nomor PO atau item..."
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  @keyup.enter="applyFilters({ page: 1 })" />
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="applyFilters({ page: 1 })">Search</button>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs font-semibold text-slate-500">Bulan</label>
                <select v-model="selectedMonth" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" @change="applyFilters({ page: 1 })">
                  <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-500">Tahun</label>
                <select v-model="selectedYear" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" @change="applyFilters({ page: 1 })">
                  <option value="all">Semua Tahun</option>
                  <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
            </div>
          </div>
          <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
            <button v-for="s in segments" :key="s.key"
              class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
              :class="s.key === segment ? 'bg-emerald-600 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              @click="changeSegment(s.key)">{{ s.label }}</button>
          </div>
        </div>

        <!-- Table -->
        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">PO Number</th>
                <th class="px-4 py-3 text-left">Nama Barang</th>
                <th class="px-4 py-3 text-left">Total Price</th>
                <th class="px-4 py-3 text-left">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="po in pagination.data" :key="po.id" class="border-t border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3">{{ po.po_number }}</td>
                <td class="px-4 py-3">{{ po.items?.map(i => i.barang?.nama_barang).filter(Boolean).join(', ') || '-' }}</td>
                <td class="px-4 py-3">{{ formatCurrency(po.total_price) }}</td>
                <td class="px-4 py-3">
                  <button v-if="getStatusConfig(po.status).clickable"
                    class="rounded-full px-3 py-1 text-xs font-semibold transition hover:shadow-sm cursor-pointer"
                    :class="[getStatusConfig(po.status).color, { 'animate-pulse': getStatusConfig(po.status).pulse }]"
                    @click="onStatusClick(po)">
                    ● {{ getStatusConfig(po.status).label }}
                  </button>
                  <span v-else class="rounded-full px-3 py-1 text-xs font-semibold" :class="getStatusConfig(po.status).color">
                    ● {{ getStatusConfig(po.status).label }}
                  </span>
                </td>
              </tr>
              <tr v-if="!pagination.data.length">
                <td colspan="4" class="px-4 py-8 text-center text-slate-400">Tidak ada purchase order pada segmen ini.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="text-sm text-slate-500">
            Menampilkan {{ pagination.from || 0 }} - {{ pagination.to || 0 }} dari <span class="font-semibold text-slate-700">{{ pagination.total || 0 }}</span> PO.
          </div>
          <div class="flex items-center gap-3">
            <button class="rounded-lg border border-slate-200 px-2 py-1 text-sm" :disabled="pagination.current_page <= 1" @click="goToPage((pagination.current_page || 1) - 1)">&lt;</button>
            <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-2 py-1">
              <input v-model="pageInput" type="number" min="1" :max="pagination.last_page || 1" class="w-16 text-center text-sm outline-none" @keyup.enter="submitPageInput" />
              <span class="text-xs text-slate-400">/ {{ pagination.last_page || 1 }}</span>
            </div>
            <button class="rounded-lg border border-slate-200 px-2 py-1 text-sm" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage((pagination.current_page || 1) + 1)">&gt;</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- Accept Request Modal                                -->
    <!-- ════════════════════════════════════════════════════ -->
    <div v-if="showAcceptModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Detail Request PO</h2>
            <p class="text-xs text-slate-500">Tinjau detail permintaan dari Admin sebelum menerima.</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showAcceptModal = false">✕</button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4 space-y-4">
          <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <h3 class="text-sm font-semibold text-amber-800">Pengingat</h3>
            <p class="mt-1 text-xs text-amber-700">
              Jika Anda menerima request ini, Anda akan diarahkan ke tahap verifikasi di mana Anda dapat mengajukan perubahan (Counter Offer) pada harga atau jumlah yang diminta.
            </p>
          </div>

          <div class="space-y-3">
            <h3 class="text-sm font-bold text-slate-700 border-b pb-2">Daftar Barang</h3>
            <div v-for="item in activePo?.items" :key="item.id" class="flex justify-between items-center rounded-lg border border-slate-100 bg-slate-50 p-3">
              <div>
                <div class="text-sm font-semibold text-slate-800">{{ item.barang?.nama_barang || '-' }}</div>
                <div class="text-xs text-slate-500">{{ item.subtype?.subtype_name || item.itemType?.type_name || '-' }}</div>
              </div>
              <div class="text-right">
                <div class="text-sm font-semibold text-emerald-600">{{ formatCurrency(item.unit_price) }}</div>
                <div class="text-xs text-slate-500">{{ item.quantity }} {{ item.uom || 'Unit' }}</div>
              </div>
            </div>
          </div>
          <div class="flex justify-between items-center border-t border-slate-100 pt-3">
            <span class="text-sm font-bold text-slate-700">Total Nilai Request</span>
            <span class="text-lg font-bold text-emerald-600">{{ formatCurrency(activePo?.total_price) }}</span>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4 bg-slate-50">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium bg-white hover:bg-slate-100" @click="showAcceptModal = false">
            Kembali
          </button>
          <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
            @click="submitDeclineRequest" :disabled="acceptingForm.processing">
            Tolak (Decline)
          </button>
          <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 shadow-sm"
            @click="submitAcceptRequest" :disabled="acceptingForm.processing">
            Terima & Lanjut (Accept)
          </button>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- Request Verification Modal                          -->
    <!-- ════════════════════════════════════════════════════ -->
    <div v-if="showVerificationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Request Verification</h2>
            <p class="text-xs text-slate-500">Verifikasi detail transaksi. Anda bisa langsung menerima atau mengubah harga/jumlah.</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showVerificationModal = false">✕</button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4 space-y-3">
          <div v-for="item in verificationForm.items" :key="item.purchase_order_item_id" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ item.barang_name }} — {{ item.subtype_name }}</span>
              <span class="text-xs text-slate-400">UoM: {{ item.uom }}</span>
            </div>

            <!-- Request admin awal -->
            <div class="mb-3 rounded-lg bg-blue-50 border border-blue-100 p-3">
              <p class="text-[11px] font-semibold text-blue-600 mb-1">Request Admin</p>
              <div class="grid grid-cols-2 gap-2 text-xs text-blue-700">
                <span>Harga: {{ formatCurrency(item.original_price) }}</span>
                <span>Quantity: {{ item.original_quantity }}</span>
              </div>
            </div>

            <!-- Counter offer admin (jika ada) -->
            <div v-if="item.counter_price != null" class="mb-3 rounded-lg bg-amber-50 border border-amber-100 p-3">
              <p class="text-[11px] font-semibold text-amber-600 mb-1">Counter Offer Admin</p>
              <div class="grid grid-cols-2 gap-2 text-xs text-amber-700">
                <span>Harga: {{ formatCurrency(item.counter_price) }}</span>
                <span>Quantity: {{ item.counter_quantity }}</span>
              </div>
            </div>

            <!-- Supplier input -->
            <div class="grid gap-3 md:grid-cols-2">
              <div>
                <label class="text-xs font-semibold text-slate-500">Harga yang ditawarkan</label>
                <input :value="formatInputValue(item.supplier_price)" @input="e => onNumberInput(item, 'supplier_price', e)" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-500">Jumlah yang disanggupi</label>
                <input :value="formatInputValue(item.supplier_quantity)" @input="e => onNumberInput(item, 'supplier_quantity', e)" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
              </div>
            </div>
            <div class="mt-2 text-right text-xs text-slate-500">Subtotal: {{ formatCurrency(item.supplier_price * item.supplier_quantity) }}</div>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
          <button class="rounded-lg bg-red-500 px-4 py-2 text-sm font-semibold text-white hover:bg-red-600" @click="showVerificationModal = false">Batal</button>
          <button class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600"
            @click="submitWithChanges" :disabled="verificationForm.processing">
            Ajukan Penawaran
          </button>
          <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
            @click="submitAcceptAsIs" :disabled="verificationForm.processing">
            Terima Penawaran
          </button>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- Shipment Modal                                      -->
    <!-- ════════════════════════════════════════════════════ -->
    <div v-if="showShipmentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-3xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Data Shipment</h2>
            <p class="text-xs text-slate-500">Lengkapi detail pengiriman barang.</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showShipmentModal = false">✕</button>
        </div>
        <div class="px-6 py-4 space-y-4">
          <!-- Switch tipe pengiriman -->
          <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
            <span class="text-xs font-semibold text-slate-600">Tipe Pengiriman:</span>
            <button type="button"
              class="flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold transition"
              :class="shipmentForm.delivery_type === 'self' ? 'bg-emerald-600 text-white shadow' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'"
              @click="shipmentForm.delivery_type = 'self'">
              🚛 Pengiriman Sendiri
            </button>
            <button type="button"
              class="flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold transition"
              :class="shipmentForm.delivery_type === 'courier' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'"
              @click="shipmentForm.delivery_type = 'courier'">
              📦 Pengiriman Kurir
            </button>
          </div>
          <!-- Self delivery fields -->
          <div v-if="shipmentForm.delivery_type === 'self'" class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="text-xs font-semibold text-slate-500">Nama Sopir</label>
              <input v-model="shipmentForm.driver_name" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Plat Nomor</label>
              <input v-model="shipmentForm.vehicle_plate" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Nomor Telepon</label>
              <input v-model="shipmentForm.phone_number" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
          </div>
          <!-- Courier fields -->
          <div v-else-if="shipmentForm.delivery_type === 'courier'" class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="text-xs font-semibold text-slate-500">Penyedia Jasa Kurir</label>
              <input v-model="shipmentForm.courier_provider" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="JNE, JT, SiCepat, dll" />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Nomor Resi</label>
              <input v-model="shipmentForm.tracking_number" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-500">Dokumen Pelengkap (URL)</label>
            <input v-model="shipmentForm.supplementary_doc_path" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="URL dokumen" />
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-500">Catatan Pengiriman</label>
            <textarea v-model="shipmentForm.shipment_notes" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm" @click="showShipmentModal = false">Batal</button>
          <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white" @click="submitShipment" :disabled="shipmentForm.processing">Simpan & Kirim</button>
        </div>
      </div>
    </div>
    <!-- ════════════════════════════════════════════════════ -->
    <!-- Completeness Modal                                   -->
    <!-- ════════════════════════════════════════════════════ -->
    <div v-if="showCompletenessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Lengkapi Dokumen</h2>
            <p class="text-xs text-slate-500">Unggah dokumen pelengkap seperti Surat Permohonan, Surat Penawaran atau Order Pembelian.</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showCompletenessModal = false">✕</button>
        </div>
        <div class="px-6 py-4">
          <!-- Opsi Unduh Dokumen Template -->
          <div class="mb-5 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
            <h3 class="mb-2 text-xs font-bold text-indigo-800">Template Dokumen Pelengkap</h3>
            <p class="mb-3 text-[11px] text-indigo-700">Unduh dan lengkapi dokumen di bawah ini sesuai pesanan, lalu tanda tangani sebelum diunggah kembali.</p>
            <div class="flex flex-col gap-2 sm:flex-row">
              <a :href="route('supplier.purchase-orders.download-doc', { id: activePo?.id, type: 'permohonan' })" target="_blank"
                 class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-white px-3 py-2 text-[11px] font-semibold text-indigo-600 shadow-sm transition hover:bg-indigo-50 border border-indigo-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Surat Permohonan
              </a>
              <a :href="route('supplier.purchase-orders.download-doc', { id: activePo?.id, type: 'penawaran' })" target="_blank"
                 class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-white px-3 py-2 text-[11px] font-semibold text-indigo-600 shadow-sm transition hover:bg-indigo-50 border border-indigo-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Surat Penawaran
              </a>
              <a :href="route('supplier.purchase-orders.download-doc', { id: activePo?.id, type: 'order' })" target="_blank"
                 class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-white px-3 py-2 text-[11px] font-semibold text-indigo-600 shadow-sm transition hover:bg-indigo-50 border border-indigo-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Order Pembelian
              </a>
            </div>
          </div>

          <!-- Status link dokumen -->
          <div v-if="activePo?.document_path" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
            <div class="flex items-center gap-2 mb-2">
              <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white text-xs font-bold">✓</span>
              <span class="text-xs font-bold text-emerald-800">Dokumen sudah dikirim</span>
            </div>
            <p class="text-[11px] text-emerald-700 mb-1">Tautan sebelumnya:</p>
            <a :href="activePo.document_path" target="_blank" class="text-xs text-emerald-600 underline break-all">{{ activePo.document_path }}</a>
            <p class="mt-2 text-[11px] text-emerald-600">Anda dapat memperbarui tautan di bawah jika perlu.</p>
          </div>
          <div v-else class="rounded-lg border border-amber-200 bg-amber-50 p-3">
            <div class="flex items-center gap-2">
              <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-white text-xs font-bold">!</span>
              <span class="text-xs font-bold text-amber-800">Belum ada dokumen yang dikirim</span>
            </div>
            <p class="mt-1 text-[11px] text-amber-700">Silakan masukkan tautan dokumen yang sudah ditandatangani.</p>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-500">Link Dokumen (URL Google Drive / dsb)</label>
            <input v-model="completenessForm.document_path" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Masukkan URL dokumen yang disyaratkan" />
          </div>
          <p class="text-xs font-medium text-amber-600">Pastikan dokumen sudah ditandatangani sebelum diunggah.</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm" @click="showCompletenessModal = false">Batal</button>
          <button class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white" @click="submitCompleteness" :disabled="completenessForm.processing">Kirim Dokumen</button>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════ -->
    <!-- Completed PO Detail Modal                           -->
    <!-- ════════════════════════════════════════════════════ -->
    <div v-if="showCompletedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Detail Purchase Order</h2>
            <p class="text-xs text-slate-500">{{ activePo?.po_number }}</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showCompletedModal = false">✕</button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4 space-y-4">
          <div class="flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3">
            <span class="text-emerald-600 text-lg">✅</span>
            <span class="text-sm font-semibold text-emerald-800">PO Sudah Diterima</span>
          </div>
          <div class="grid gap-3 md:grid-cols-2 text-sm">
            <div class="rounded-xl border border-slate-100 p-3">
              <p class="text-xs font-semibold text-slate-500">PO Number</p>
              <p class="mt-1 text-slate-800 font-medium">{{ activePo?.po_number }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 p-3">
              <p class="text-xs font-semibold text-slate-500">Total Nilai</p>
              <p class="mt-1 text-slate-800 font-bold text-emerald-700">{{ formatCurrency(activePo?.total_price) }}</p>
            </div>
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-700 mb-2">Daftar Barang</h3>
            <div v-for="item in activePo?.items" :key="item.id" class="flex justify-between items-center rounded-lg border border-slate-100 bg-slate-50 p-3 mb-2">
              <div>
                <p class="text-sm font-semibold text-slate-800">{{ item.barang?.nama_barang || '-' }}</p>
                <p class="text-xs text-slate-500">{{ item.subtype?.subtype_name || item.itemType?.type_name || '-' }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-emerald-600">{{ formatCurrency(item.final_price ?? item.unit_price) }}</p>
                <p class="text-xs text-slate-500">{{ item.final_quantity ?? item.quantity }} {{ item.uom || 'Unit' }}</p>
              </div>
            </div>
          </div>
          <div v-if="activePo?.shipment_notes" class="rounded-xl border border-slate-100 p-3">
            <p class="text-xs font-semibold text-slate-500">Catatan Pengiriman</p>
            <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ activePo.shipment_notes }}</p>
          </div>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-6 py-4">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm" @click="showCompletedModal = false">Tutup</button>
        </div>
      </div>
    </div>
  </SupplierLayout>
</template>
