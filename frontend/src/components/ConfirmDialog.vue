<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: '' },
  busy: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])

const dialog = ref(null)

watch(
  () => props.open,
  (open) => (open ? dialog.value?.showModal() : dialog.value?.close()),
)
</script>

<template>
  <dialog ref="dialog" class="dialog dialog-sm" @cancel.prevent="emit('cancel')">
    <h2>{{ title }}</h2>
    <p>{{ message }}</p>
    <footer class="dialog-footer">
      <button class="btn" type="button" @click="emit('cancel')">Batal</button>
      <button class="btn btn-danger" type="button" :disabled="busy" @click="emit('confirm')">
        {{ busy ? 'Menghapus...' : 'Hapus' }}
      </button>
    </footer>
  </dialog>
</template>
