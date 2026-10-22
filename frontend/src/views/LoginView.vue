<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { login } from '../lib/auth'

const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const errors = ref({})
const message = ref(route.query.expired ? 'Sesi berakhir, silakan login kembali.' : '')
const loading = ref(false)

async function submit() {
  loading.value = true
  errors.value = {}
  message.value = ''

  try {
    await login(form.email, form.password)
    router.push({ name: 'items' })
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors ?? {}
    } else {
      message.value = 'Tidak dapat terhubung ke server. Coba lagi nanti.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth-page">
    <form class="card auth-card" novalidate @submit.prevent="submit">
      <h1>Inventaris Lab</h1>
      <p class="muted">Masuk untuk mengelola data alat laboratorium.</p>

      <p v-if="message" class="alert">{{ message }}</p>

      <label class="field">
        <span>Email</span>
        <input v-model.trim="form.email" type="email" autocomplete="username" required />
        <small v-if="errors.email" class="error">{{ errors.email[0] }}</small>
      </label>

      <label class="field">
        <span>Password</span>
        <input v-model="form.password" type="password" autocomplete="current-password" required />
        <small v-if="errors.password" class="error">{{ errors.password[0] }}</small>
      </label>

      <button class="btn btn-primary btn-block" type="submit" :disabled="loading">
        {{ loading ? 'Memproses...' : 'Login' }}
      </button>

      <p class="muted hint">Akun demo: admin@lab.test / password</p>
    </form>
  </main>
</template>
