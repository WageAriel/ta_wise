<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  purchaseOrders: { type: Object, default: () => ({ data: [] }) },
  segment: { type: String, default: 'order-request' },
  filters: { type: Object, default: () => ({}) },
  years: { type: Array, default: () => [] },
  poDescription: { type: String, default: '' },
  suppliers: { type: Array, default: () => [] },
  itemsCatalog: { type: Array, default: () => [] },
  itemTypes: { type: Array, default: () => [] },
  uomOptions: { type: Array, default: () => [] },
});

const segments = [
  { key: 'order-request', label: 'Order Request' },
  { key: 'waiting-list', label: 'Waiting List' },
  { key: 'order-list', label: 'Order List' },
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

const defaultUomOptions = ['pcs', 'box', 'pack', 'karton', 'lusin', 'kg', 'gram', 'liter', 'unit'];
const uomOptions = computed(() => (props.uomOptions?.length ? props.uomOptions : defaultUomOptions));

const normalize = (value) => String(value || '').trim().toLowerCase();

const formatPreviewUrl = (path) => {
  if (!path) return '#';
  const trimmed = path.trim();
  if (/^(https?:\/\/|\/|#)/i.test(trimmed)) {
    return trimmed;
  }
  return `https://${trimmed}`;
};

const findItemTypeById = (typeId) => props.itemTypes.find((type) => String(type.id_item_type) === String(typeId));
const findBarangById = (barangId) => props.itemsCatalog.find((barang) => String(barang.id_barang) === String(barangId));

const subtypesForItem = (item) => {
  const type = findItemTypeById(item.item_type_id);
  return type?.subtypes || [];
};

const itemTypesForItem = (item) => {
  const barang = findBarangById(item.barang_id);
  if (!barang) return props.itemTypes;

  const barangName = normalize(barang.nama_barang);
  const matched = props.itemTypes.filter((type) => {
    const typeName = normalize(type.type_name);
    return typeName === barangName || typeName.includes(barangName) || barangName.includes(typeName);
  });

  return matched.length ? matched : props.itemTypes;
};

const applyBarangSelection = (item) => {
  const barang = findBarangById(item.barang_id);
  item.item_type_id = '';
  item.subtype_id = '';
  item.uom_locked = false;

  if (!item.uom) {
    item.uom = barang?.satuan || '';
  }
};

const applyItemTypeSelection = (item) => {
  const type = findItemTypeById(item.item_type_id);
  item.subtype_id = '';

  const barang = findBarangById(item.barang_id);
  const defaultUom = type?.uomConfig?.default_uom || barang?.satuan || '';
  const forceUom = Boolean(type?.uomConfig?.force_uom);

  if (forceUom) {
    item.uom = defaultUom;
    item.uom_locked = true;
  } else {
    item.uom_locked = false;
    if (!item.uom) {
      item.uom = defaultUom;
    }
  }
};

const selectedType = (item) => findItemTypeById(item.item_type_id || item.id_item_type || item.itemType?.id_item_type);

const supplierDisplay = (supplier) => {
  const statusLabel = supplier.class_status || supplier.kelas_label || supplier.kelas || '';
  const classificationLabel = supplier.class_name || supplier.kelas_name || supplier.kelas || '';
  const parts = [supplier.nama_perusahaan, statusLabel, classificationLabel].filter(Boolean);
  return parts.join(' • ');
};

const historyAvailable = computed(() => ['order-request', 'order-list'].includes(props.segment));
const historyEnabled = ref(Boolean(props.filters?.history));

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

const segmentLabel = computed(() => {
  const found = segments.find((item) => item.key === props.segment);
  return found ? found.label : 'Order Request';
});

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

const totalQuantity = (items = []) => items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
const itemNames = (items = []) => {
  const barangNames = [...new Set(items
    .map((item) => item.barang?.nama_barang || '')
    .filter(Boolean)
  )];

  if (!barangNames.length) return '-';
  return barangNames.join(', ');
};

const itemTypesCount = (items = []) => {
  return items.length;
};

const itemUom = (items = []) => {
  const uoms = items.map((item) => item.uom).filter(Boolean);
  if (!uoms.length) return '-';
  const first = uoms[0];
  return uoms.every((uom) => uom === first) ? first : 'Campuran';
};

const statusLabel = (status) => {
  const map = {
    draft: 'Draft',
    inquiry: 'Inquiry',
    submitted: 'Submitted',
    rfq: 'Request For Quotation',
    verification: 'Menunggu Supplier',
    request: 'Review Penawaran',
    completeness: 'Kelengkapan Dokumen',
    approved: 'Approved',
    shipment: 'Confirm Purchase Order',
    completed: 'Sudah Diterima',
    rejected: 'Rejected',
    cancelled: 'Cancelled',
  };
  return map[status] || status;
};

const typeSubtypes = (typeId) => props.itemTypes.find((type) => String(type.id_item_type) === String(typeId))?.subtypes || [];

const applyFilters = (overrides = {}) => {
  const payload = {
    segment: props.segment,
    search: search.value || undefined,
    month: selectedMonth.value,
    year: selectedYear.value,
    history: historyEnabled.value ? 1 : 0,
    ...overrides,
  };

  if (!historyAvailable.value) {
    payload.history = 0;
  }

  router.get(route('admin.purchase-orders.index'), payload, {
    preserveState: true,
    replace: true,
  });
};

const changeSegment = (segmentKey) => {
  if (segmentKey === props.segment) return;
  historyEnabled.value = false;
  applyFilters({ segment: segmentKey, page: 1 });
};

const toggleHistory = () => {
  if (!historyAvailable.value) return;
  historyEnabled.value = !historyEnabled.value;
  applyFilters({ page: 1 });
};

const goToPage = (page) => {
  const target = Math.min(Math.max(page, 1), pagination.value.last_page || 1);
  applyFilters({ page: target });
};

const submitPageInput = () => {
  if (!pageInput.value) return;
  goToPage(Number(pageInput.value));
};

const showRequestModal = ref(false);
const showShipmentModal = ref(false);
const modalMode = ref('create');
const activePoId = ref(null);
const activeShipmentPo = ref(null);
const activeViewPo = ref(null);


const getSelectedItemType = () => {
  const barang = findBarangById(form.barang_id);
  if (!barang) return null;

  if (barang.id_item_type) {
    const found = findItemTypeById(barang.id_item_type);
    if (found) return found;
  }

  const matched = itemTypesForItem({ barang_id: form.barang_id });
  return matched.length ? matched[0] : null;
};

const makeTypeLine = () => {
  const itemType = getSelectedItemType();
  const barang = findBarangById(form.barang_id);
  const forceUom = Boolean(itemType?.uomConfig?.force_uom);
  const defaultUom = itemType?.uomConfig?.default_uom || barang?.satuan || '';

  return {
    item_type_id: itemType?.id_item_type || '',
    subtype_id: '',
    quantity: 1,
    unit_price: 0,
    uom: forceUom ? defaultUom : (barang?.satuan || ''),
    uom_locked: forceUom,
  };
};

const form = useForm({
  supplier_id: '',
  description: '',
  is_draft: false,
  barang_id: '',
  types: [],
});

// Watch barang_id to initialize types list if empty
watch(
  () => form.barang_id,
  (newVal) => {
    if (newVal && form.types.length === 0) {
      form.types = [makeTypeLine()];
    }
  }
);

const resetForm = () => {
  form.reset();
  form.clearErrors();
  form.barang_id = '';
  form.types = [];
  activeViewPo.value = null;
};

const openCreateModal = () => {
  resetForm();
  modalMode.value = 'create';
  activePoId.value = null;
  showRequestModal.value = true;
};

const openEditModal = (po) => {
  resetForm();
  modalMode.value = 'edit';
  activePoId.value = po.id;
  form.supplier_id = po.supplier_id || '';
  form.description = po.description || '';
  form.is_draft = (po.status === 'inquiry' || po.status === 'draft');
  form.barang_id = po.items?.[0]?.barang_id || '';
  form.types = po.items.map((item) => {
    const itemType = findItemTypeById(item.id_item_type || item.item_type_id || item.itemType?.id_item_type);
    const forceUom = Boolean(itemType?.uomConfig?.force_uom);
    return {
      id: item.id,
      item_type_id: item.id_item_type || item.item_type_id || item.itemType?.id_item_type || '',
      subtype_id: item.id_subtype || item.subtype_id || item.subtype?.id_subtype || '',
      quantity: item.quantity,
      unit_price: item.unit_price,
      uom: item.uom || '',
      uom_locked: forceUom,
    };
  });
  showRequestModal.value = true;
};

const openViewModal = (po) => {
  resetForm();
  modalMode.value = po.status === 'request' ? 'review' : 'view';
  activePoId.value = po.id;
  activeViewPo.value = po;
  form.supplier_id = po.supplier_id || '';
  form.description = po.description || '';
  form.is_draft = (po.status === 'inquiry' || po.status === 'draft');
  form.barang_id = po.items?.[0]?.barang_id || '';
  form.types = po.items.map((item) => {
    const itemType = findItemTypeById(item.id_item_type || item.item_type_id || item.itemType?.id_item_type);
    const forceUom = Boolean(itemType?.uomConfig?.force_uom);
    
    const displayQuantity = item.supplier_offered_quantity ?? item.quantity;
    const displayPrice = item.supplier_offered_price ?? item.unit_price;

    return {
      id: item.id,
      item_type_id: item.id_item_type || item.item_type_id || item.itemType?.id_item_type || '',
      subtype_id: item.id_subtype || item.subtype_id || item.subtype?.id_subtype || '',
      quantity: displayQuantity,
      unit_price: displayPrice,
      uom: item.uom || '',
      uom_locked: forceUom,
      original_quantity: item.quantity,
      original_price: item.unit_price,
    };
  });
  showRequestModal.value = true;
};

const addTypeLine = () => {
  form.types.push(makeTypeLine());
};

const removeTypeLine = (index) => {
  if (form.types.length === 1) return;
  form.types.splice(index, 1);
};

const onBarangChange = () => {
  const barang = findBarangById(form.barang_id);
  const itemType = getSelectedItemType();
  const forceUom = Boolean(itemType?.uomConfig?.force_uom);
  const defaultUom = itemType?.uomConfig?.default_uom || barang?.satuan || '';

  form.types = [
    {
      id: null,
      item_type_id: itemType?.id_item_type || '',
      subtype_id: '',
      quantity: 1,
      unit_price: 0,
      uom: forceUom ? defaultUom : (barang?.satuan || ''),
      uom_locked: forceUom,
    }
  ];
};

const onSubtypeChange = (typeLine) => {
  const itemType = getSelectedItemType();
  if (!itemType) return;

  const forceUom = Boolean(itemType.uomConfig?.force_uom);
  if (forceUom) {
    typeLine.uom = itemType.uomConfig?.default_uom || '';
    typeLine.uom_locked = true;
    return;
  }

  const subtype = itemType.subtypes?.find((st) => String(st.id_subtype) === String(typeLine.subtype_id));
  if (subtype && subtype.uom) {
    typeLine.uom = subtype.uom;
  }
};

const typeLineSubtotal = (t) => Number(t.quantity || 0) * Number(t.unit_price || 0);

const totalRequestValue = computed(() => form.types.reduce((sum, t) => sum + typeLineSubtotal(t), 0));

const formErrors = computed(() => {
  const errs = [];
  if (!form.errors) return errs;
  Object.keys(form.errors).forEach((key) => {
    if (key.startsWith('items.') || key === 'items') {
      errs.push(form.errors[key]);
    }
  });
  return [...new Set(errs)];
});

const submitRequest = () => {
  if (modalMode.value === 'view') return;

  const options = {
    preserveScroll: true,
    onSuccess: () => (showRequestModal.value = false),
  };

  const transformed = form.transform((data) => ({
    supplier_id: data.supplier_id || null,
    description: data.description || null,
    is_draft: data.is_draft,
    items: data.types.map((t) => ({
      id: t.id,
      barang_id: data.barang_id,
      item_type_id: t.item_type_id || null,
      subtype_id: t.subtype_id || null,
      quantity: t.quantity,
      unit_price: t.unit_price,
      uom: t.uom || null,
    })),
  }));

  if (modalMode.value === 'edit' && activePoId.value) {
    transformed.put(route('admin.order-request.update', activePoId.value), options);
    return;
  }

  transformed.post(route('admin.order-request.store'), options);
};

const acceptSupplierOffer = () => {
  if (!activePoId.value || !confirm('Setujui penawaran dari supplier?')) return;
  router.post(route('admin.accept-supplier-offer', activePoId.value), {}, { preserveScroll: true, onSuccess: () => (showRequestModal.value = false) });
};

const submitCounterOffer = () => {
  if (!activePoId.value) return;

  const options = {
    preserveScroll: true,
    onSuccess: () => (showRequestModal.value = false),
  };

  const transformed = form.transform((data) => ({
    items: data.types.map((t) => ({
      id: t.id,
      counter_price: t.unit_price,
      counter_quantity: t.quantity,
    })),
  }));

  transformed.post(route('admin.counter-offer', activePoId.value), options);
};

const deleteRequest = (po) => {
  if (!confirm(`Hapus request ${po.po_number}?`)) return;
  router.delete(route('admin.order-request.destroy', po.id), { preserveScroll: true });
};

/* ── Completeness Modal (Admin) ── */
const showCompletenessModal = ref(false);
const activeCompletenessPo = ref(null);
const completenessForm = useForm({
  documents_verified: {
    surat_permohonan: false,
    surat_penawaran: false,
    purchase_order: false
  }
});

const openCompletenessModal = (po) => {
  activeCompletenessPo.value = po;
  completenessForm.documents_verified = {
    surat_permohonan: false,
    surat_penawaran: false,
    purchase_order: false
  };
  showCompletenessModal.value = true;
};

const confirmCompleteness = () => {
  if (!activeCompletenessPo.value) return;
  completenessForm.post(route('admin.confirm-completeness', activeCompletenessPo.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showCompletenessModal.value = false;
    },
  });
};

const openShipmentModal = (po) => {
  activeShipmentPo.value = po;
  showShipmentModal.value = true;
};

const confirmArrival = () => {
  if (!activeShipmentPo.value) return;
  router.post(route('admin.purchase-orders.confirm-arrival', activeShipmentPo.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => (showShipmentModal.value = false),
  });
};
</script>

<template>
  <Head title="Purchase Orders - Admin" />

  <AdminLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
      <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-800">Purchase Order</h1>
            <p class="mt-2 text-sm text-slate-500">{{ poDescription }}</p>
          </div>
          <button
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            @click="openCreateModal"
          >
            Buat Request
          </button>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm border border-slate-100">
          <div class="grid gap-4 md:grid-cols-3">
            <div class="col-span-1 md:col-span-2">
              <label class="text-xs font-semibold text-slate-500">Search</label>
              <div class="mt-2 flex gap-2">
                <input
                  v-model="search"
                  type="text"
                  placeholder="Cari nama supplier atau item..."
                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
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
                  class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
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
                  class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
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

          <div class="mt-6 flex flex-col gap-4 border-t border-slate-100 pt-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap items-center gap-2">
              <button
                v-for="item in segments"
                :key="item.key"
                class="rounded-full px-4 py-1.5 text-sm font-semibold transition"
                :class="
                  item.key === segment
                    ? 'bg-blue-600 text-white shadow'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                "
                @click="changeSegment(item.key)"
              >
                {{ item.label }}
              </button>
            </div>
            <div class="flex items-center gap-3 text-sm text-slate-600">
              <span>History</span>
              <button
                class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                :class="historyEnabled && historyAvailable ? 'bg-blue-600' : 'bg-slate-300'"
                :disabled="!historyAvailable"
                @click="toggleHistory"
              >
                <span
                  class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                  :class="historyEnabled && historyAvailable ? 'translate-x-6' : 'translate-x-1'"
                ></span>
              </button>
            </div>
          </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">Supplier</th>
                <th class="px-4 py-3 text-left">Nama Barang</th>
                <th class="px-4 py-3 text-left">Total Quantity</th>
                <th class="px-4 py-3 text-left">UoM</th>
                <th class="px-4 py-3 text-left">Total Price</th>
                <th class="px-4 py-3 text-left">Tanggal</th>
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
                <td class="px-4 py-3">
                  {{ po.supplier?.nama_perusahaan || '-' }}
                </td>
                <td class="px-4 py-3">
                  <span>{{ itemNames(po.items || []) }}</span>
                  <span v-if="itemTypesCount(po.items || []) > 1" class="ml-1 inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-600">
                    {{ itemTypesCount(po.items || []) }} jenis
                  </span>
                </td>
                <td class="px-4 py-3">
                  {{ totalQuantity(po.items || []) }}
                </td>
                <td class="px-4 py-3">
                  {{ itemUom(po.items || []) }}
                </td>
                <td class="px-4 py-3">
                  {{ formatCurrency(po.total_price) }}
                </td>
                <td class="px-4 py-3">
                  {{ po.created_at }}
                </td>
                <td class="px-4 py-3">
                  <!-- Order Request: Inquiry/Draft -->
                  <button
                    v-if="segment === 'order-request' && (po.status === 'inquiry' || po.status === 'draft')"
                    class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-200 transition cursor-pointer"
                    title="Klik untuk mengedit inquiry"
                    @click="openEditModal(po)"
                  >
                    ● {{ statusLabel(po.status) }}
                  </button>
                  <!-- RFQ: read only -->
                  <span v-else-if="po.status === 'rfq'" class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                    ● {{ statusLabel(po.status) }}
                  </span>
                  <!-- Verification: menunggu supplier, no action -->
                  <span v-else-if="po.status === 'verification'" class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">
                    ● {{ statusLabel(po.status) }}
                  </span>
                  <!-- Request: admin can accept/counter — clickable -->
                  <button
                    v-else-if="po.status === 'request'"
                    class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-200 cursor-pointer animate-pulse"
                    @click="openViewModal(po)"
                    title="Klik untuk review penawaran supplier"
                  >
                    ● {{ statusLabel(po.status) }}
                  </button>
                  <!-- Completeness: admin confirms docs — clickable -->
                  <button
                    v-else-if="po.status === 'completeness'"
                    class="rounded-full bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-700 transition hover:bg-purple-200 cursor-pointer"
                    @click="openCompletenessModal(po)"
                    title="Klik untuk verifikasi kelengkapan dokumen"
                  >
                    ● {{ statusLabel(po.status) }}
                  </button>
                  <!-- Shipment: admin can confirm arrival — clickable -->
                  <button
                    v-else-if="po.status === 'shipment'"
                    class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-200 cursor-pointer"
                    @click="openShipmentModal(po)"
                    title="Klik untuk konfirmasi kedatangan"
                  >
                    ● {{ statusLabel(po.status) }}
                  </button>
                  <!-- Other statuses: non-clickable -->
                  <span v-else class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                    ● {{ statusLabel(po.status) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <button class="text-xs font-semibold text-blue-600" @click="openViewModal(po)">See</button>
                    <button class="text-xs font-semibold text-emerald-600" @click="openShipmentModal(po)">Status</button>
                    <button
                      v-if="segment === 'order-request' && (po.status === 'inquiry' || po.status === 'draft')"
                      class="text-xs font-semibold text-amber-600"
                      @click="openEditModal(po)"
                    >
                      Edit
                    </button>
                    <button
                      v-if="segment === 'order-request' && (po.status === 'inquiry' || po.status === 'draft')"
                      class="text-xs font-semibold text-red-500"
                      @click="deleteRequest(po)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!pagination.data.length">
                <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                  Tidak ada data pada segmen ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="text-sm text-slate-500">
            Menampilkan segmen <span class="font-semibold text-slate-700">{{ segmentLabel }}</span>
            <span class="mx-1">{{ pagination.from || 0 }} - {{ pagination.to || 0 }}</span>
            dari <span class="font-semibold text-slate-700">{{ pagination.total || 0 }}</span> transaksi.
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

    <div v-if="showRequestModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-4">
          <button
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
            @click="showRequestModal = false"
          >
            <span aria-hidden="true">←</span>
            Back
          </button>

          <div class="text-center">
            <h2 class="text-lg font-bold text-slate-800">Order Request</h2>
            <p class="text-xs text-slate-500">Isi detail request sesuai kebutuhan.</p>
          </div>

          <div class="text-right">
            <label class="block text-xs font-semibold text-slate-500">Status Transaksi</label>
            <div class="mt-2 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2">
              <span class="text-xs font-medium text-slate-500">Request PO</span>
              <button
                class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                :class="form.is_draft ? 'bg-blue-600' : 'bg-slate-300'"
                :disabled="modalMode === 'view' || modalMode === 'review'"
                @click="form.is_draft = !form.is_draft"
              >
                <span
                  class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                  :class="form.is_draft ? 'translate-x-6' : 'translate-x-1'"
                ></span>
              </button>
              <span class="text-xs font-medium text-slate-500">Inquiry (Draft)</span>
            </div>
          </div>
        </div>

        <div v-if="Object.keys(form.errors).length" class="mx-6 mt-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
          <p v-if="form.errors.supplier_id" class="text-xs text-red-600">{{ form.errors.supplier_id }}</p>
          <p v-if="form.errors.items" class="text-xs text-red-600">{{ form.errors.items }}</p>
          <p v-for="(err, idx) in formErrors" :key="idx" class="text-xs text-red-600">{{ err }}</p>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="text-xs font-semibold text-slate-500">Nama Supplier</label>
              <select
                v-model="form.supplier_id"
                class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                :disabled="modalMode === 'view'"
              >
                <option value="">Pilih Supplier</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                  {{ supplierDisplay(supplier) }}
                </option>
              </select>

            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500">Kelas Supplier</label>
              <div class="mt-2 flex min-h-[42px] items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600">
                {{ suppliers.find((supplier) => String(supplier.id) === String(form.supplier_id))?.class_name || suppliers.find((supplier) => String(supplier.id) === String(form.supplier_id))?.kelas || '-' }}
              </div>
            </div>
          </div>

          <div class="mt-4">
            <label class="text-xs font-semibold text-slate-500">Deskripsi</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
              placeholder="Deskripsi order request"
              :disabled="modalMode === 'view'"
            ></textarea>
          </div>

          <div class="mt-4 space-y-4">
            <div class="flex items-center justify-between gap-2">
              <h3 class="text-sm font-semibold text-slate-700">Detail Order</h3>
              <button
                class="inline-flex items-center gap-1 rounded px-3 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-50"
                @click="addTypeLine"
                :disabled="modalMode === 'view' || !form.barang_id"
                title="Tambah jenis item baru"
              >
                <span>+ Tambah Jenis</span>
              </button>
            </div>

            <div>
              <label class="text-xs font-semibold text-slate-500">Nama Barang</label>
              <select
                v-model="form.barang_id"
                class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                :disabled="modalMode === 'view'"
                @change="onBarangChange"
              >
                <option value="">Pilih Nama Item</option>
                <option v-for="barang in itemsCatalog" :key="barang.id_barang" :value="barang.id_barang">
                  {{ barang.nama_barang }}
                </option>
              </select>
            </div>

            <div v-if="!form.barang_id" class="mt-2 text-xs text-slate-400">Pilih nama barang terlebih dahulu untuk menambah jenis item.</div>

            <div v-for="(t, idx) in form.types" :key="idx" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis Item {{ idx + 1 }}</span>
                <button class="text-xs font-semibold text-red-500" @click="removeTypeLine(idx)" :disabled="modalMode === 'view' || modalMode === 'review'">
                  Hapus
                </button>
              </div>

              <div class="mt-3">
                <label class="text-xs font-semibold text-slate-500">Pilih Jenis Item</label>
                <select
                  v-model="t.subtype_id"
                  class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                  :disabled="modalMode === 'view' || modalMode === 'review' || !form.barang_id"
                  @change="onSubtypeChange(t)"
                >
                  <option value="">Pilih Jenis</option>
                  <option v-for="st in typeSubtypes(t.item_type_id)" :key="st.id_subtype" :value="st.id_subtype">
                    {{ st.subtype_name }}
                  </option>
                </select>
              </div>

              <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                  <label class="text-xs font-semibold text-slate-500">Price Request</label>
                  <input v-model.number="t.unit_price" type="number" min="0" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" :disabled="modalMode === 'view'" />
                  <div v-if="modalMode === 'review' && t.unit_price !== t.original_price" class="mt-1 text-[10px] text-amber-600 font-medium">
                    Semula: {{ formatCurrency(t.original_price) }}
                  </div>
                </div>
                <div>
                  <label class="text-xs font-semibold text-slate-500">Quantity</label>
                  <input v-model.number="t.quantity" type="number" min="1" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" :disabled="modalMode === 'view'" />
                  <div v-if="modalMode === 'review' && t.quantity !== t.original_quantity" class="mt-1 text-[10px] text-amber-600 font-medium">
                    Semula: {{ t.original_quantity }}
                  </div>
                </div>
                <div>
                  <label class="text-xs font-semibold text-slate-500">UoM</label>
                  <select v-model="t.uom" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" :disabled="modalMode === 'view' || modalMode === 'review' || t.uom_locked">
                    <option value="">Pilih UoM</option>
                    <option v-for="uom in uomOptions" :key="uom" :value="uom">{{ uom }}</option>
                  </select>
                </div>
              </div>

              <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>Subtotal Jenis: {{ formatCurrency(typeLineSubtotal(t)) }}</span>
                <span class="text-xs text-slate-400">(Price Request × Quantity)</span>
              </div>
            </div>
          </div>

          <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
            <span class="font-semibold text-slate-600">Total Price Request</span>
            <span class="text-lg font-bold text-slate-800">{{ formatCurrency(totalRequestValue) }}</span>
          </div>

          <!-- Lampiran Section untuk Admin View Modal -->
          <div v-if="(modalMode === 'view' || modalMode === 'review') && activeViewPo" class="mt-6 border-t border-slate-200 pt-4">
            <h3 class="text-sm font-bold text-slate-800 mb-3">Lampiran Terkait</h3>
            <div class="grid gap-3 md:grid-cols-2">
              
              <!-- Tautan Kelengkapan Dokumen -->
              <div v-if="activeViewPo.document_path" class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                <p class="text-xs font-semibold text-blue-800 mb-1">Dokumen Kelengkapan (Supplier)</p>
                <a :href="formatPreviewUrl(activeViewPo.document_path)" target="_blank" class="text-xs text-blue-600 underline break-all hover:text-blue-800">
                  Lihat Dokumen
                </a>
              </div>
              <div v-else class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs font-semibold text-slate-500 mb-1">Dokumen Kelengkapan</p>
                <span class="text-xs text-slate-400">Belum ada lampiran</span>
              </div>

              <!-- Tautan Shipment -->
              <div v-if="activeViewPo.weighing_note_path || activeViewPo.delivery_note_path" class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 flex flex-col gap-2">
                <p class="text-xs font-semibold text-emerald-800 mb-1">Dokumen Pengiriman</p>
                <a v-if="activeViewPo.weighing_note_path" :href="formatPreviewUrl(activeViewPo.weighing_note_path)" target="_blank" class="text-xs text-emerald-600 underline hover:text-emerald-800 flex items-center gap-1">
                  📄 Nota Timbang
                </a>
                <a v-if="activeViewPo.delivery_note_path" :href="formatPreviewUrl(activeViewPo.delivery_note_path)" target="_blank" class="text-xs text-emerald-600 underline hover:text-emerald-800 flex items-center gap-1">
                  📄 Surat Jalan
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-3">
          <button class="rounded px-3 py-1.5 text-xs font-medium border border-slate-200 hover:bg-slate-50" @click="showRequestModal = false">
            Batal
          </button>
          
          <template v-if="modalMode === 'create' || modalMode === 'edit'">
            <button
              class="rounded px-3 py-1.5 text-xs font-medium bg-blue-600 text-white hover:bg-blue-700"
              @click="submitRequest"
              :disabled="form.processing"
            >
              Simpan
            </button>
          </template>

          <template v-else-if="modalMode === 'review'">
            <button
              class="rounded px-3 py-1.5 text-xs font-medium bg-amber-500 text-white hover:bg-amber-600"
              @click="submitCounterOffer"
              :disabled="form.processing"
              title="Ajukan balik perubahan harga/kuantitas ke supplier"
            >
              Ajukan Perubahan
            </button>
            <button
              class="rounded px-3 py-1.5 text-xs font-medium bg-emerald-600 text-white hover:bg-emerald-700"
              @click="acceptSupplierOffer"
              :disabled="form.processing"
              title="Terima penawaran dari supplier saat ini"
            >
              Setujui Penawaran
            </button>
          </template>
        </div>
      </div>
    </div>

    <div v-if="showShipmentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-3xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Shipment Detail</h2>
            <p class="text-xs text-slate-500">Informasi pengiriman dan konfirmasi kedatangan.</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showShipmentModal = false">✕</button>
        </div>

        <div class="px-6 py-4 space-y-4" v-if="activeShipmentPo">
          <div class="grid gap-4 md:grid-cols-2 text-sm">
            <div class="rounded-xl border border-slate-100 p-4">
              <p class="text-xs font-semibold text-slate-500">Driver</p>
              <p class="mt-1 text-slate-700">{{ activeShipmentPo.driver_name || '-' }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 p-4">
              <p class="text-xs font-semibold text-slate-500">Plat Nomor</p>
              <p class="mt-1 text-slate-700">{{ activeShipmentPo.vehicle_plate || '-' }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 p-4">
              <p class="text-xs font-semibold text-slate-500">Carrier</p>
              <p class="mt-1 text-slate-700">{{ activeShipmentPo.carrier || '-' }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 p-4">
              <p class="text-xs font-semibold text-slate-500">Tracking Number</p>
              <p class="mt-1 text-slate-700">{{ activeShipmentPo.tracking_number || '-' }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 p-4">
              <p class="text-xs font-semibold text-slate-500">Status PO</p>
              <p class="mt-1 text-slate-700">{{ statusLabel(activeShipmentPo.status) }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 p-4">
              <p class="text-xs font-semibold text-slate-500">Tanggal Kirim</p>
              <p class="mt-1 text-slate-700">{{ activeShipmentPo.shipped_at || '-' }}</p>
            </div>
          </div>

          <div class="rounded-xl border border-slate-100 p-4">
            <p class="text-xs font-semibold text-slate-500">Catatan Shipment</p>
            <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ activeShipmentPo.shipment_notes || '-' }}</p>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <a
              v-if="activeShipmentPo.weighing_note_path"
              :href="formatPreviewUrl(activeShipmentPo.weighing_note_path)"
              target="_blank"
              class="rounded-xl border border-slate-100 p-4 text-sm font-semibold text-blue-600"
            >
              Preview Nota Timbang
            </a>
            <a
              v-if="activeShipmentPo.delivery_note_path"
              :href="formatPreviewUrl(activeShipmentPo.delivery_note_path)"
              target="_blank"
              class="rounded-xl border border-slate-100 p-4 text-sm font-semibold text-blue-600"
            >
              Preview Surat Jalan
            </a>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm" @click="showShipmentModal = false">Tutup</button>
          <button
            v-if="activeShipmentPo && activeShipmentPo.status !== 'completed'"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
            @click="confirmArrival"
          >
            Konfirmasi Kedatangan
          </button>
        </div>
      </div>
    </div>
    <!-- ════════════════════════════════════════════════════ -->
    <!-- Completeness Check Modal                             -->
    <!-- ════════════════════════════════════════════════════ -->
    <div v-if="showCompletenessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Verifikasi Kelengkapan Dokumen</h2>
            <p class="text-xs text-slate-500">Konfirmasi dokumen yang diunggah supplier untuk melanjutkan ke proses pengiriman.</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="showCompletenessModal = false">✕</button>
        </div>
        <div class="px-6 py-4">
          <div class="rounded-lg bg-blue-50 border border-blue-100 p-4 mb-4">
            <h3 class="text-xs font-semibold text-blue-800 mb-2">Informasi Supplier:</h3>
            <p class="text-sm text-blue-900 font-medium">{{ activeCompletenessPo?.supplier?.nama_perusahaan || '-' }}</p>
            
            <div v-if="activeCompletenessPo?.document_path" class="mt-3 border-t border-blue-200 pt-3">
              <span class="text-xs font-semibold text-blue-800 block mb-1">Tautan Dokumen dari Supplier:</span>
              <a :href="activeCompletenessPo.document_path" target="_blank" class="text-xs text-blue-600 underline break-all hover:text-blue-800">
                {{ activeCompletenessPo.document_path }}
              </a>
            </div>
            <div v-else class="mt-3 border-t border-blue-200 pt-3 text-xs text-amber-600">
              Supplier belum atau tidak melampirkan tautan dokumen.
            </div>
          </div>
          
          <p class="text-xs font-semibold text-slate-500 mb-3">Checklist Dokumen</p>
          <div class="space-y-3">
            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" v-model="completenessForm.documents_verified.surat_permohonan" class="h-4 w-4 rounded border-slate-300 text-purple-600" />
              <span class="text-sm font-medium text-slate-700">Surat Permohonan</span>
            </label>
            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" v-model="completenessForm.documents_verified.surat_penawaran" class="h-4 w-4 rounded border-slate-300 text-purple-600" />
              <span class="text-sm font-medium text-slate-700">Surat Penawaran</span>
            </label>
            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer">
              <input type="checkbox" v-model="completenessForm.documents_verified.purchase_order" class="h-4 w-4 rounded border-slate-300 text-purple-600" />
              <span class="text-sm font-medium text-slate-700">Order Pembelian</span>
            </label>
          </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
          <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm" @click="showCompletenessModal = false">Batal</button>
          <button 
            class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition disabled:opacity-50"
            :class="(completenessForm.documents_verified.surat_permohonan && completenessForm.documents_verified.surat_penawaran && completenessForm.documents_verified.purchase_order) ? 'bg-purple-600 hover:bg-purple-700' : 'bg-slate-400'"
            :disabled="!(completenessForm.documents_verified.surat_permohonan && completenessForm.documents_verified.surat_penawaran && completenessForm.documents_verified.purchase_order) || completenessForm.processing"
            @click="confirmCompleteness"
          >
            Konfirmasi Kelengkapan
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
