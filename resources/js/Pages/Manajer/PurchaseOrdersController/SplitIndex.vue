<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ManajerLayout from '@/Layouts/ManajerLayout.vue';

const defaultUomOptions = ['pcs', 'box', 'pack', 'karton', 'lusin', 'kg', 'gram', 'liter', 'unit'];

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  itemTypes: { type: Array, default: () => [] },
  segment: { type: String, default: 'supplier' },
  uomOptions: { type: Array, default: () => [] },
});

const segmentTabs = [
  { key: 'supplier', label: 'Supplier Controller' },
  { key: 'admin', label: 'Admin Controller' },
];

const activeSegment = ref(props.segment || 'supplier');
watch(
  () => props.segment,
  (next) => {
    activeSegment.value = next || 'supplier';
  }
);

const switchSegment = (segment) => {
  if (segment === activeSegment.value) return;
  activeSegment.value = segment;
  router.get(route('manajer.purchase-order-controller.index'), { segment }, { preserveState: true, replace: true });
};

const supplierForm = useForm({
  supplier_description: props.settings?.supplier_description ?? props.settings?.description ?? '',
});

const adminForm = useForm({
  admin_description: props.settings?.admin_description ?? '',
  uom_options: [...(props.settings?.uom_options?.length ? props.settings.uom_options : props.uomOptions || defaultUomOptions)],
  new_uom: '',
  limit_class_a: props.settings?.limit_class_a ?? 1000,
  limit_class_b: props.settings?.limit_class_b ?? 500,
  limit_class_c: props.settings?.limit_class_c ?? 100,
});

const saveSupplier = () => {
  supplierForm.put(route('manajer.purchase-order-controller.settings.update'), {
    preserveScroll: true,
  });
};

const saveAdmin = () => {
  adminForm.put(route('manajer.purchase-order-controller.settings.update'), {
    preserveScroll: true,
  });
};

const addUom = () => {
  const value = String(adminForm.new_uom || '').trim();
  if (!value) return;
  if (!adminForm.uom_options.includes(value)) {
    adminForm.uom_options.push(value);
  }
  adminForm.new_uom = '';
};

const removeUom = (index) => {
  adminForm.uom_options.splice(index, 1);
};

const mapItemTypes = (types) =>
  (types || []).map((type) => ({
    ...type,
    subtypes: (type.subtypes || []).map((subtype) => ({ ...subtype })),
  }));

const itemTypeState = ref(mapItemTypes(props.itemTypes));
watch(
  () => props.itemTypes,
  (next) => {
    itemTypeState.value = mapItemTypes(next);
  }
);

const itemTypeList = computed(() => itemTypeState.value);
const subtypeDrafts = ref({});
const uomDrafts = ref({});

const ensureSubtypeDraft = (typeId) => {
  if (!subtypeDrafts.value[typeId]) {
    subtypeDrafts.value[typeId] = { subtype_name: '', uom: '' };
  }
  return subtypeDrafts.value[typeId];
};

const ensureUomDraft = (type) => {
  if (!uomDrafts.value[type.id_item_type]) {
    uomDrafts.value[type.id_item_type] = { default_uom: '', force_uom: false };
  }
  return uomDrafts.value[type.id_item_type];
};

const createTypeForm = useForm({
  type_name: '',
});

const applyForceUom = (type) => {
  const draft = ensureUomDraft(type);
  if (draft.force_uom && draft.default_uom) {
    (type.subtypes || []).forEach((st) => {
      st.uom = draft.default_uom;
    });
    ensureSubtypeDraft(type.id_item_type).uom = draft.default_uom;
  }
};

const createType = () => {
  router.post(route('manajer.purchase-order-config.item-types.store'), createTypeForm.data(), {
    preserveScroll: true,
    onSuccess: () => createTypeForm.reset(),
  });
};

const updateType = (type) => {
  router.put(route('manajer.purchase-order-config.item-types.update', type.id_item_type), type, {
    preserveScroll: true,
  });
};

const deleteType = (type) => {
  if (!confirm('Hapus nama item ini?')) return;
  router.delete(route('manajer.purchase-order-config.item-types.destroy', type.id_item_type), {
    preserveScroll: true,
  });
};

const saveUom = (type) => {
  router.post(route('manajer.purchase-order-config.uom.store', type.id_item_type), ensureUomDraft(type), {
    preserveScroll: true,
  });
};

const addSubtype = (type) => {
  router.post(route('manajer.purchase-order-config.subtypes.store', type.id_item_type), ensureSubtypeDraft(type.id_item_type), {
    preserveScroll: true,
  });
};

const updateSubtype = (type, subtype) => {
  router.put(route('manajer.purchase-order-config.subtypes.update', [type.id_item_type, subtype.id_subtype]), subtype, {
    preserveScroll: true,
  });
};

const deleteSubtype = (type, subtype) => {
  if (!confirm('Hapus tipe item ini?')) return;
  router.delete(route('manajer.purchase-order-config.subtypes.destroy', [type.id_item_type, subtype.id_subtype]), {
    preserveScroll: true,
  });
};

const rowSpanForType = (type) => Math.max((type.subtypes || []).length, 0) + 2;
</script>

<template>
  <Head title="Purchase Order Controller - Manajer" />

  <ManajerLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50">
      <div class="mx-auto max-w-7xl px-6 py-8">
        <div>
          <h1 class="text-3xl font-bold text-slate-800">Purchase Order Controller</h1>
          <p class="mt-2 text-sm text-slate-500">Pisahkan pengaturan supplier dan admin agar alurnya lebih mudah dipelihara.</p>
        </div>

        <div class="mt-6 flex flex-wrap gap-2 rounded-2xl border border-slate-100 bg-white p-2 shadow-sm">
          <button
            v-for="tab in segmentTabs"
            :key="tab.key"
            class="rounded-xl px-4 py-2 text-sm font-semibold transition"
            :class="activeSegment === tab.key ? 'bg-emerald-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100'"
            @click="switchSegment(tab.key)"
          >
            {{ tab.label }}
          </button>
        </div>

        <div v-if="activeSegment === 'supplier'" class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-800">Supplier Controller</h2>
              <p class="mt-1 text-sm text-slate-500">Saat ini hanya isi bagian deskripsi Purchase Order untuk supplier.</p>
            </div>
            <div class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Supplier</div>
          </div>

          <div class="mt-6">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deskripsi Purchase Order</label>
            <textarea
              v-model="supplierForm.supplier_description"
              rows="6"
              class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
              placeholder="Isi deskripsi yang akan tampil di bagian supplier"
            />
          </div>

          <div class="mt-5 flex justify-end">
            <button
              class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60"
              :disabled="supplierForm.processing"
              @click="saveSupplier"
            >
              Simpan Deskripsi Supplier
            </button>
          </div>
        </div>

        <div v-else class="mt-6 space-y-6">
          <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-slate-800">Admin Controller</h2>
                <p class="mt-1 text-sm text-slate-500">Urutan: deskripsi admin, daftar UoM, lalu tabel nama item dan tipe item.</p>
              </div>
              <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Admin</div>
            </div>

            <div class="mt-6">
              <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deskripsi Purchase Order</label>
              <textarea
                v-model="adminForm.admin_description"
                rows="5"
                class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                placeholder="Isi deskripsi yang akan tampil di bagian admin"
              />
              <div class="mt-4 flex justify-end">
                <button
                  class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60"
                  :disabled="adminForm.processing"
                  @click="saveAdmin"
                >
                  Simpan Deskripsi Admin
                </button>
              </div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-lg font-semibold text-slate-800">Batas Transaksi (Kuantitas)</h3>
                <p class="mt-1 text-sm text-slate-500">Atur maksimal jumlah barang dalam satu kali transaksi (PO) berdasarkan kelas supplier.</p>
              </div>
            </div>
            
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="text-xs font-semibold text-emerald-600 uppercase">Kelas A (Tertinggi)</label>
                <input v-model="adminForm.limit_class_a" type="number" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" />
                <p v-if="adminForm.errors.limit_class_a" class="text-xs text-red-500 mt-1">{{ adminForm.errors.limit_class_a }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-blue-600 uppercase">Kelas B (Menengah)</label>
                <input v-model="adminForm.limit_class_b" type="number" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" />
                <p v-if="adminForm.errors.limit_class_b" class="text-xs text-red-500 mt-1">{{ adminForm.errors.limit_class_b }}</p>
              </div>
              <div>
                <label class="text-xs font-semibold text-amber-600 uppercase">Kelas C (Dasar)</label>
                <input v-model="adminForm.limit_class_c" type="number" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" />
                <p v-if="adminForm.errors.limit_class_c" class="text-xs text-red-500 mt-1">{{ adminForm.errors.limit_class_c }}</p>
              </div>
            </div>
            <div class="mt-4 flex justify-end">
              <button
                class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60"
                :disabled="adminForm.processing"
                @click="saveAdmin"
              >
                Simpan Batasan Transaksi
              </button>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-lg font-semibold text-slate-800">List Unit Of Measure</h3>
                <p class="mt-1 text-sm text-slate-500">Atur satuan yang boleh dipilih di seluruh konfigurasi PO.</p>
              </div>
              <button
                class="rounded-xl border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50"
                :disabled="adminForm.processing"
                @click="saveAdmin"
              >
                Simpan UoM
              </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <span
                v-for="(uom, index) in adminForm.uom_options"
                :key="uom + index"
                class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm text-slate-700"
              >
                {{ uom }}
                <button class="text-xs font-semibold text-red-500" @click="removeUom(index)">✕</button>
              </span>
            </div>

            <div class="mt-4 flex flex-col gap-3 md:flex-row">
              <input
                v-model="adminForm.new_uom"
                type="text"
                class="min-w-0 flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                placeholder="Tambah UoM baru, misalnya liter"
                @keyup.enter="addUom"
              />
              <button
                class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                @click="addUom"
              >
                Tambah UoM
              </button>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="text-lg font-semibold text-slate-800">Nama Item dan Tipe Item</h3>
                <p class="mt-1 text-sm text-slate-500">Tampilan tabel dibuat mirip contoh agar main item dan tipe-nya lebih mudah dipahami.</p>
              </div>
              <div class="grid gap-3 md:grid-cols-[1fr_120px]">
                <input
                  v-model="createTypeForm.type_name"
                  type="text"
                  class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                  placeholder="Nama item baru"
                />
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white" @click="createType" title="Tambah">
                  Tambah
                </button>
              </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
              <table class="min-w-full border-collapse text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                  <tr>
                    <th class="w-[30%] px-4 py-3">Nama Item</th>
                    <th class="w-[40%] px-4 py-3">Tipe Item</th>
                    <th class="w-[18%] px-4 py-3">Unit Of Measure</th>
                    <th class="w-[12%] px-4 py-3">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="type in itemTypeList" :key="type.id_item_type">
                    <tr class="border-t border-slate-200 align-top">
                      <td :rowspan="rowSpanForType(type)" class="bg-white px-4 py-4 align-top">
                        <input
                          v-model="type.type_name"
                          type="text"
                          class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        />
                        <!-- sort_order removed per UI simplification -->
                      </td>
                      <td class="bg-white px-4 py-4">
                        <div class="flex items-center justify-between gap-3">
                          <span class="font-semibold text-slate-700">Tipe Item</span>
                          <button class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" @click="ensureSubtypeDraft(type.id_item_type)">
                            +
                          </button>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                          <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">Pilih tipe dari dropdown di baris tipe</span>
                        </div>
                      </td>
                      <td class="bg-white px-4 py-4">
                        <select
                          v-model="ensureUomDraft(type).default_uom"
                          @change="applyForceUom(type)"
                          class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        >
                          <option value="">Pilih UoM</option>
                          <option v-for="uom in adminForm.uom_options" :key="uom" :value="uom">{{ uom }}</option>
                        </select>
                        <label class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                          <input v-model="ensureUomDraft(type).force_uom" @change="applyForceUom(type)" type="checkbox" class="h-4 w-4" />
                          terapkan ke semua tipe item
                        </label>
                      </td>
                      <td class="bg-white px-4 py-4">
                        <div class="flex flex-col gap-2">
                          <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white" @click="saveUom(type)" title="Simpan UoM">💾</button>
                          <button class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white" @click="updateType(type)" title="Simpan Nama Item">💾</button>
                          <button class="rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" @click="deleteType(type)" title="Hapus">🗑</button>
                        </div>
                      </td>
                    </tr>

                    <tr v-for="subtype in type.subtypes" :key="subtype.id_subtype" class="border-t border-slate-100 align-top">
                      <td class="bg-slate-50 px-4 py-4">
                        <input
                          v-model="subtype.subtype_name"
                          type="text"
                          class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        />
                      </td>
                      <td class="bg-slate-50 px-4 py-4">
                        <select v-model="subtype.uom" :disabled="ensureUomDraft(type).force_uom" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                          <option value="">Pilih UoM</option>
                          <option v-for="uom in adminForm.uom_options" :key="uom" :value="uom">{{ uom }}</option>
                        </select>
                      </td>
                      <td class="bg-slate-50 px-4 py-4">
                        <div class="flex flex-col gap-2">
                          <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white" @click="updateSubtype(type, subtype)" title="Simpan">💾</button>
                          <button class="rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" @click="deleteSubtype(type, subtype)" title="Hapus">🗑</button>
                        </div>
                      </td>
                    </tr>

                    <tr class="border-t border-slate-100 bg-slate-50">
                      <td class="px-4 py-4" colspan="3">
                        <div class="grid gap-3 md:grid-cols-[1fr_1fr_1fr_110px]">
                          <input
                            v-model="ensureSubtypeDraft(type.id_item_type).subtype_name"
                            type="text"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            placeholder="Tambah tipe item baru"
                          />
                          <select
                            v-model="ensureSubtypeDraft(type.id_item_type).uom"
                            :disabled="ensureUomDraft(type).force_uom"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                          >
                            <option value="">Pilih UoM</option>
                            <option v-for="uom in adminForm.uom_options" :key="uom" :value="uom">{{ uom }}</option>
                          </select>
                          <button class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white" @click="addSubtype(type)" title="Tambah subtype">＋</button>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManajerLayout>
</template>
