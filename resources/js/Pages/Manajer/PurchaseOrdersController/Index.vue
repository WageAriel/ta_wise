<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ManajerLayout from '@/Layouts/ManajerLayout.vue';

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  itemTypes: { type: Array, default: () => [] },
});

const descriptionForm = useForm({
  description: props.settings?.description || '',
});

const typeForm = useForm({
  type_name: '',
  sort_order: 1,
});

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
const uomOptions = ['pcs', 'box', 'pack', 'karton', 'lusin', 'kg', 'gram', 'liter', 'unit'];

const ensureSubtypeDraft = (typeId) => {
  if (!subtypeDrafts.value[typeId]) {
    subtypeDrafts.value[typeId] = { subtype_name: '', description: '', sort_order: 1 };
  }
  return subtypeDrafts.value[typeId];
};

const ensureUomDraft = (type) => {
  if (!uomDrafts.value[type.id_item_type]) {
    uomDrafts.value[type.id_item_type] = { default_uom: '', force_uom: false };
  }
  return uomDrafts.value[type.id_item_type];
};

const saveDescription = () => {
  descriptionForm.put(route('manajer.purchase-order-controller.settings.update'), {
    preserveScroll: true,
  });
};

const createType = () => {
  router.post(route('manajer.purchase-order-config.item-types.store'), typeForm.data(), {
    preserveScroll: true,
    onSuccess: () => typeForm.reset(),
  });
};

const updateType = (type) => {
  router.put(route('manajer.purchase-order-config.item-types.update', type.id_item_type), type, {
    preserveScroll: true,
  });
};

const deleteType = (type) => {
  if (!confirm('Hapus jenis ini?')) return;
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
  if (!confirm('Hapus subtype ini?')) return;
  router.delete(route('manajer.purchase-order-config.subtypes.destroy', [type.id_item_type, subtype.id_subtype]), {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Purchase Order Controller - Manajer" />

  <ManajerLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50">
      <div class="max-w-7xl mx-auto px-6 py-8 space-y-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-800">Purchase Order Controller</h1>
          <p class="mt-2 text-sm text-slate-500">Kelola deskripsi default, main type, subtype, dan UoM untuk PO.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Deskripsi Purchase Order</h2>
            <p class="mt-1 text-sm text-slate-500">Teks ini tampil di admin dan supplier.</p>
            <textarea
              v-model="descriptionForm.description"
              rows="4"
              class="mt-4 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
              placeholder="Masukkan deskripsi default PO"
            ></textarea>
            <div class="mt-4 flex justify-end">
              <button
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
                :disabled="descriptionForm.processing"
                @click="saveDescription"
              >
                Simpan Deskripsi
              </button>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">Tambah Nama Item</h2>
            <div class="mt-4 space-y-3">
              <input v-model="typeForm.type_name" type="text" placeholder="Nama item (Main Item A/B)" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
              <input v-model.number="typeForm.sort_order" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            </div>
            <div class="mt-4 flex justify-end">
              <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="createType">
                Tambah Jenis
              </button>
            </div>
          </div>
        </div>

        <div v-for="type in itemTypeList" :key="type.id_item_type" class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
          <div class="space-y-3">
            <input v-model="type.type_name" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
            <input v-model.number="type.sort_order" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
          </div>
          <div class="mt-4 flex items-center gap-2">
            <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white" @click="updateType(type)">Simpan</button>
            <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-500" @click="deleteType(type)">Hapus</button>
          </div>

          <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
              <h3 class="text-sm font-semibold text-slate-700">Default UoM</h3>
              <p class="mt-1 text-xs text-slate-500">Pilih satu UoM default. Jika tidak dicentang, item satu per satu bisa memilih UoM sendiri.</p>
              <div class="mt-3 grid gap-3 md:grid-cols-3">
                <select v-model="ensureUomDraft(type).default_uom" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                  <option value="">Pilih UoM</option>
                  <option v-for="uom in uomOptions" :key="uom" :value="uom">{{ uom }}</option>
                </select>
                <label class="flex items-center gap-2 text-sm text-slate-600 md:col-span-1">
                  <input v-model="ensureUomDraft(type).force_uom" type="checkbox" class="h-4 w-4" />
                  Samakan UoM untuk jenis barang ini
                </label>
                <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white" @click="saveUom(type)">Simpan UoM</button>
              </div>
            </div>

            <div class="rounded-xl border border-slate-100 bg-white p-4">
              <h3 class="text-sm font-semibold text-slate-700">Detail Order / Tipe Item</h3>
              <div class="mt-3 grid gap-3 md:grid-cols-4">
                <input v-model="ensureSubtypeDraft(type.id_item_type).subtype_name" type="text" placeholder="Nama subtype" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" />
                <input v-model="ensureSubtypeDraft(type.id_item_type).description" type="text" placeholder="Deskripsi" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" />
                <input v-model.number="ensureSubtypeDraft(type.id_item_type).sort_order" type="number" min="1" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" />
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="addSubtype(type)">Tambah Subtype</button>
              </div>

              <div class="mt-4 space-y-3">
                <div v-for="subtype in type.subtypes" :key="subtype.id_subtype" class="grid gap-3 md:grid-cols-5 items-center">
                  <input v-model="subtype.subtype_name" type="text" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" />
                  <input v-model="subtype.description" type="text" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" />
                  <input v-model.number="subtype.sort_order" type="number" min="1" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" />
                  <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white" @click="updateSubtype(type, subtype)">Simpan</button>
                  <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-500" @click="deleteSubtype(type, subtype)">Hapus</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ManajerLayout>
</template>
