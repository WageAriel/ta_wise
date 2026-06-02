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
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' },
];

const search = ref(props.filters?.search ?? '');
const selectedMonth = ref(props.filters?.month ?? new Date().getMonth() + 1);
const selectedYear = ref(props.filters?.year ?? new Date().getFullYear());

const pagination = computed(() => props.purchaseOrders || { data: [] });
const pageInput = ref(pagination.value.current_page || 1);

watch(
  () => pagination.value.current_page,
  (page) => {
    pageInput.value = page || 1;
  }
);

const yearOptions = computed(() => {
  if (props.years?.length) {
    return props.years;
  }
  return [new Date().getFullYear()];
});

const formatCurrency = (value) => {
  const amount = Number(value || 0);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount);
};

const statusLabel = (status) => {
  const map = {
    rfq: 'Accept Request',
    verification: 'Request Verification',
    request: 'Counter Offer',
    completeness: 'Complete Document',
    approved: 'Masih Diproses',
    shipment: 'Dalam Perjalanan',
    completed: 'Sudah Diterima',
  };
  return map[status] || status;
};

const applyFilters = (overrides = {}) => {
  const payload = {
    segment: props.segment,
    search: search.value || undefined,
    month: selectedMonth.value,
    year: selectedYear.value,
    ...overrides,
  };

  router.get(route('supplier.purchase-orders.index'), payload, {
    preserveState: true,
    replace: true,
  });
};

const changeSegment = (segmentKey) => {
  if (segmentKey === props.segment) return;
  applyFilters({ segment: segmentKey, page: 1 });
};

const goToPage = (page) => {
  const target = Math.min(Math.max(page, 1), pagination.value.last_page || 1);
  applyFilters({ page: target });
};

const submitPageInput = () => {
  if (!pageInput.value) return;
  goToPage(Number(pageInput.value));
};

const showVerificationModal = ref(false);
const showShipmentModal = ref(false);
const activePo = ref(null);
const verificationMode = ref('submit');

const verificationForm = useForm({
  po_id: '',
  items: [],
});

const openVerificationModal = (po, mode) => {
  verificationMode.value = mode;
  activePo.value = po;
  verificationForm.po_id = po.id;
  verificationForm.items = po.items.map((item) => ({
    purchase_order_item_id: item.id,
    supplier_price: item.supplier_offered_price ?? item.unit_price ?? 0,
    supplier_quantity: item.supplier_offered_quantity ?? item.quantity ?? 0,
  }));
  showVerificationModal.value = true;
};

const submitVerification = () => {
  if (!activePo.value) return;

  if (verificationMode.value === 'update') {
    verificationForm.put(route('supplier.purchase-orders.update-verification', activePo.value.id), {
      preserveScroll: true,
      onSuccess: () => (showVerificationModal.value = false),
    });
    return;
  }

  verificationForm.post(route('supplier.purchase-orders.submit-verification'), {
    preserveScroll: true,
    onSuccess: () => (showVerificationModal.value = false),
  });
};

const shipmentForm = useForm({
  driver_name: '',
  vehicle_plate: '',
  carrier: '',
  tracking_number: '',
  shipment_notes: '',
  weighing_note_path: '',
  delivery_note_path: '',
});

const openShipmentModal = (po) => {
  activePo.value = po;
  shipmentForm.driver_name = po.driver_name || '';
  shipmentForm.vehicle_plate = po.vehicle_plate || '';
  shipmentForm.carrier = po.carrier || '';
  shipmentForm.tracking_number = po.tracking_number || '';
  shipmentForm.shipment_notes = po.shipment_notes || '';
  shipmentForm.weighing_note_path = po.weighing_note_path || '';
  shipmentForm.delivery_note_path = po.delivery_note_path || '';
  showShipmentModal.value = true;
};

const submitShipment = () => {
  if (!activePo.value) return;
  shipmentForm.post(route('supplier.purchase-orders.shipment.store', activePo.value.id), {
    preserveScroll: true,
    onSuccess: () => (showShipmentModal.value = false),
  });
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

        <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
          <div class="grid gap-4 md:grid-cols-3">
            <div class="col-span-1 md:col-span-2">
              <label class="text-xs font-semibold text-slate-500">Search</label>
              <div class="mt-2 flex gap-2">
                <input
                  v-model="search"
                  type="text"
                  placeholder="Cari nomor PO atau item..."
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  @keyup.enter="applyFilters({ page: 1 })"
                />
                <button
                  class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                  @click="applyFilters({ page: 1 })"
                >
                  Search
                </button>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs font-semibold text-slate-500">Bulan</label>
                <select
                  v-model="selectedMonth"
                  class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  @change="applyFilters({ page: 1 })"
                >
                  <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                    {{ month.label }}
                  </option>
                </select>
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-500">Tahun</label>
                <select
                  v-model="selectedYear"
                  class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  @change="applyFilters({ page: 1 })"
                >
                  <option value="all">Semua Tahun</option>
                  <option v-for="year in yearOptions" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
            <button
              v-for="item in segments"
              :key="item.key"
              class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
              :class="
                item.key === segment
                  ? 'bg-emerald-600 text-white shadow'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              "
              @click="changeSegment(item.key)"
            >
              {{ item.label }}
            </button>
          </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">PO Number</th>
                <th class="px-4 py-3 text-left">Item</th>
                <th class="px-4 py-3 text-left">Total Price</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="po in pagination.data"
                :key="po.id"
                class="border-t border-slate-100 hover:bg-slate-50"
              >
                <td class="px-4 py-3">{{ po.po_number }}</td>
                <td class="px-4 py-3">
                  {{ po.items?.map((item) => item.barang?.nama_barang).filter(Boolean).join(', ') || '-' }}
                </td>
                <td class="px-4 py-3">{{ formatCurrency(po.total_price) }}</td>
                <td class="px-4 py-3">
                  <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-600">
                    {{ statusLabel(po.status) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <button
                      v-if="po.status === 'rfq'"
                      class="text-xs font-semibold text-emerald-600"
                      @click="openVerificationModal(po, 'submit')"
                    >
                      Accept Request
                    </button>
                    <button
                      v-else-if="po.status === 'verification'"
                      class="text-xs font-semibold text-blue-600"
                      @click="openVerificationModal(po, 'update')"
                    >
                      Update Verification
                    </button>
                    <button
                      v-else-if="po.status === 'approved' || po.status === 'shipment'"
                      class="text-xs font-semibold text-emerald-600"
                      @click="openShipmentModal(po)"
                    >
                      Isi Shipment
                    </button>
                    <button v-else class="text-xs font-semibold text-slate-400" disabled>
                      Detail
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!pagination.data.length">
                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                  Tidak ada purchase order pada segmen ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="text-sm text-slate-500">
            Menampilkan {{ pagination.from || 0 }} - {{ pagination.to || 0 }} dari
            <span class="font-semibold text-slate-700">{{ pagination.total || 0 }}</span> PO.
          </div>
          <div class="flex items-center gap-3">
            <button
              class="rounded-lg border border-slate-200 px-2 py-1 text-sm"
              :disabled="pagination.current_page <= 1"
              @click="goToPage((pagination.current_page || 1) - 1)"
            >
              &lt;
            </button>
            <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-2 py-1">
              <input
                v-model="pageInput"
                type="number"
                min="1"
                :max="pagination.last_page || 1"
                class="w-16 text-center text-sm outline-none"
                @keyup.enter="submitPageInput"
              />
              <span class="text-xs text-slate-400">/ {{ pagination.last_page || 1 }}</span>
            </div>
            <button
              class="rounded-lg border border-slate-200 px-2 py-1 text-sm"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="goToPage((pagination.current_page || 1) + 1)"
            >
              &gt;
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showVerificationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Verification</h2>
            <p class="text-xs text-slate-500">Masukkan harga dan jumlah sesuai kemampuan.</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showVerificationModal = false">✕</button>
        </div>

        <div class="px-6 py-4 space-y-3">
          <div v-for="(item, index) in verificationForm.items" :key="item.purchase_order_item_id" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <div class="grid gap-3 md:grid-cols-2">
              <div>
                <label class="text-xs font-semibold text-slate-500">Harga yang ditawarkan</label>
                <input v-model.number="item.supplier_price" type="number" min="0" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
              </div>
              <div>
                <label class="text-xs font-semibold text-slate-500">Jumlah yang disanggupi</label>
                <input v-model.number="item.supplier_quantity" type="number" min="0" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm" @click="showVerificationModal = false">Batal</button>
          <button
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
            @click="submitVerification"
            :disabled="verificationForm.processing"
          >
            Kirim Verification
          </button>
        </div>
      </div>
    </div>

    <div v-if="showShipmentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-3xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Data Shipment</h2>
            <p class="text-xs text-slate-500">Simpan detail pengiriman ke PO yang sama.</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showShipmentModal = false">✕</button>
        </div>

        <div class="px-6 py-4 space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="text-xs font-semibold text-slate-500">Nama Sopir</label>
              <input v-model="shipmentForm.driver_name" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Plat Nomor</label>
              <input v-model="shipmentForm.vehicle_plate" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Carrier</label>
              <input v-model="shipmentForm.carrier" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Tracking Number</label>
              <input v-model="shipmentForm.tracking_number" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="text-xs font-semibold text-slate-500">Preview Nota Timbang</label>
              <input v-model="shipmentForm.weighing_note_path" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="URL atau path file" />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Preview Surat Jalan</label>
              <input v-model="shipmentForm.delivery_note_path" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="URL atau path file" />
            </div>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-500">Catatan Pengiriman</label>
            <textarea v-model="shipmentForm.shipment_notes" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm" @click="showShipmentModal = false">Batal</button>
          <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white" @click="submitShipment" :disabled="shipmentForm.processing">
            Simpan Shipment
          </button>
        </div>
      </div>
    </div>
  </SupplierLayout>
</template>
