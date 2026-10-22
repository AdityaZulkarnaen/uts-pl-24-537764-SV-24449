<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import ItemFormDialog from '../components/ItemFormDialog.vue'
import api from '../lib/api'
import { auth, loadUser, logout } from '../lib/auth'
import { CONDITIONS, conditionLabel } from '../lib/conditions'

const router = useRouter()

const items = ref([])
const meta = ref(null)
const loading = ref(false)
const failure = ref('')
const notice = ref('')

const filters = reactive({ q: '', condition: '', page: 1 })

const formOpen = ref(false)
const editing = ref(null)

const deleting = ref(null)
const deleteBusy = ref(false)

async function fetchItems() {
  loading.value = true
  failure.value = ''

  try {
    const { data } = await api.get('/items', {
      params: {
        q: filters.q || undefined,
        condition: filters.condition || undefined,
        page: filters.page,
        per_page: 10,
      },
    })

    items.value = data.data
    meta.value = data.meta
  } catch (error) {
    if (error.response?.status !== 401) {
      failure.value = 'Gagal memuat data item.'
    }
  } finally {
    loading.value = false
  }
}

let searchTimer
watch(
  () => filters.q,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
      filters.page = 1
      fetchItems()
    }, 300)
  },
)

watch(
  () => filters.condition,
  () => {
    filters.page = 1
    fetchItems()
  },
)

function goToPage(page) {
  filters.page = page
  fetchItems()
}

function flash(text) {
  notice.value = text
  setTimeout(() => {
    if (notice.value === text) notice.value = ''
  }, 3000)
}

function openCreate() {
  editing.value = null
  formOpen.value = true
}

function openEdit(item) {
  editing.value = item
  formOpen.value = true
}

function handleSaved(response) {
  formOpen.value = false
  flash(response.message)
  fetchItems()
}

async function confirmDelete() {
  deleteBusy.value = true

  try {
    const { data } = await api.delete(`/items/${deleting.value.id}`)
    flash(data.message)

    if (items.value.length === 1 && filters.page > 1) {
      filters.page -= 1
    }

    await fetchItems()
  } catch (error) {
    if (error.response?.status !== 401) {
      failure.value = 'Gagal menghapus item.'
    }
  } finally {
    deleteBusy.value = false
    deleting.value = null
  }
}

async function handleLogout() {
  await logout()
  router.push({ name: 'login' })
}

onMounted(() => {
  loadUser()
  fetchItems()
})
</script>

<template>
  <div class="layout">
    <header class="topbar">
      <div class="container topbar-inner">
        <strong class="brand">Inventaris Lab</strong>
        <div class="topbar-user">
          <span v-if="auth.user" class="muted">{{ auth.user.name }}</span>
          <button class="btn" type="button" @click="handleLogout">Logout</button>
        </div>
      </div>
    </header>

    <main class="container">
      <div class="page-header">
        <div>
          <h1>Daftar Alat Laboratorium</h1>
          <p class="muted">{{ meta ? `${meta.total} item tercatat` : 'Memuat...' }}</p>
        </div>
        <button class="btn btn-primary" type="button" @click="openCreate">+ Tambah Item</button>
      </div>

      <p v-if="notice" class="alert alert-success">{{ notice }}</p>
      <p v-if="failure" class="alert">{{ failure }}</p>

      <div class="toolbar">
        <input v-model.trim="filters.q" type="search" placeholder="Cari nama, kode, atau kategori..." />
        <select v-model="filters.condition">
          <option value="">Semua kondisi</option>
          <option v-for="condition in CONDITIONS" :key="condition.value" :value="condition.value">
            {{ condition.label }}
          </option>
        </select>
      </div>

      <div class="card table-wrap">
        <table>
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama</th>
              <th>Kategori</th>
              <th class="num">Jumlah</th>
              <th>Kondisi</th>
              <th>Lokasi</th>
              <th class="actions">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading && !items.length">
              <td colspan="7" class="empty">Memuat data...</td>
            </tr>
            <tr v-else-if="!items.length">
              <td colspan="7" class="empty">Belum ada item yang cocok.</td>
            </tr>
            <tr v-for="item in items" :key="item.id">
              <td><code>{{ item.code }}</code></td>
              <td>
                {{ item.name }}
                <small v-if="item.notes" class="muted block">{{ item.notes }}</small>
              </td>
              <td>{{ item.category }}</td>
              <td class="num">{{ item.quantity }}</td>
              <td>
                <span class="badge" :class="`badge-${item.condition}`">{{ conditionLabel(item.condition) }}</span>
              </td>
              <td>{{ item.location }}</td>
              <td class="actions">
                <button class="btn btn-sm" type="button" @click="openEdit(item)">Ubah</button>
                <button class="btn btn-sm btn-danger-outline" type="button" @click="deleting = item">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <nav v-if="meta && meta.last_page > 1" class="pagination">
        <button class="btn btn-sm" type="button" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">
          ‹ Sebelumnya
        </button>
        <span class="muted">Halaman {{ meta.current_page }} dari {{ meta.last_page }}</span>
        <button class="btn btn-sm" type="button" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">
          Berikutnya ›
        </button>
      </nav>
    </main>

    <ItemFormDialog :open="formOpen" :item="editing" @close="formOpen = false" @saved="handleSaved" />

    <ConfirmDialog
      :open="Boolean(deleting)"
      title="Hapus item?"
      :message="deleting ? `Item ${deleting.code} - ${deleting.name} akan dihapus permanen.` : ''"
      :busy="deleteBusy"
      @confirm="confirmDelete"
      @cancel="deleting = null"
    />
  </div>
</template>
