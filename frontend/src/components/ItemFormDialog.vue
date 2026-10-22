<script setup>
import { computed, reactive, ref, watch } from 'vue'
import api from '../lib/api'
import { CONDITIONS } from '../lib/conditions'

const props = defineProps({
  open: { type: Boolean, default: false },
  item: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const dialog = ref(null)
const saving = ref(false)
const errors = ref({})
const failure = ref('')

const emptyForm = () => ({
  code: '',
  name: '',
  category: '',
  quantity: 1,
  condition: 'baik',
  location: '',
  notes: '',
})

const form = reactive(emptyForm())
const isEdit = computed(() => Boolean(props.item))

watch(
  () => props.open,
  (open) => {
    if (open) {
      Object.assign(form, emptyForm(), props.item ?? {})
      form.notes = form.notes ?? ''
      errors.value = {}
      failure.value = ''
      dialog.value?.showModal()
    } else {
      dialog.value?.close()
    }
  },
)

async function submit() {
  saving.value = true
  errors.value = {}
  failure.value = ''

  const payload = {
    code: form.code,
    name: form.name,
    category: form.category,
    quantity: form.quantity,
    condition: form.condition,
    location: form.location,
    notes: form.notes || null,
  }

  try {
    const { data } = isEdit.value
      ? await api.put(`/items/${props.item.id}`, payload)
      : await api.post('/items', payload)

    emit('saved', data)
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors ?? {}
    } else {
      failure.value = 'Gagal menyimpan data. Coba lagi.'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <dialog ref="dialog" class="dialog" @cancel.prevent="emit('close')">
    <form novalidate @submit.prevent="submit">
      <header class="dialog-header">
        <h2>{{ isEdit ? 'Ubah Item' : 'Tambah Item' }}</h2>
        <button class="btn btn-ghost" type="button" aria-label="Tutup" @click="emit('close')">✕</button>
      </header>

      <p v-if="failure" class="alert">{{ failure }}</p>

      <div class="form-grid">
        <label class="field">
          <span>Kode</span>
          <input v-model.trim="form.code" placeholder="LAB-EL-010" />
          <small v-if="errors.code" class="error">{{ errors.code[0] }}</small>
        </label>

        <label class="field">
          <span>Nama</span>
          <input v-model.trim="form.name" placeholder="Multimeter Digital" />
          <small v-if="errors.name" class="error">{{ errors.name[0] }}</small>
        </label>

        <label class="field">
          <span>Kategori</span>
          <input v-model.trim="form.category" placeholder="Elektronika" />
          <small v-if="errors.category" class="error">{{ errors.category[0] }}</small>
        </label>

        <label class="field">
          <span>Jumlah</span>
          <input v-model.number="form.quantity" type="number" min="0" />
          <small v-if="errors.quantity" class="error">{{ errors.quantity[0] }}</small>
        </label>

        <label class="field">
          <span>Kondisi</span>
          <select v-model="form.condition">
            <option v-for="condition in CONDITIONS" :key="condition.value" :value="condition.value">
              {{ condition.label }}
            </option>
          </select>
          <small v-if="errors.condition" class="error">{{ errors.condition[0] }}</small>
        </label>

        <label class="field">
          <span>Lokasi</span>
          <input v-model.trim="form.location" placeholder="Lab Elektronika A-101" />
          <small v-if="errors.location" class="error">{{ errors.location[0] }}</small>
        </label>

        <label class="field field-wide">
          <span>Catatan</span>
          <textarea v-model="form.notes" rows="3" placeholder="Opsional"></textarea>
          <small v-if="errors.notes" class="error">{{ errors.notes[0] }}</small>
        </label>
      </div>

      <footer class="dialog-footer">
        <button class="btn" type="button" @click="emit('close')">Batal</button>
        <button class="btn btn-primary" type="submit" :disabled="saving">
          {{ saving ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </footer>
    </form>
  </dialog>
</template>
