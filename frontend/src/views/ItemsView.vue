<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { auth, loadUser, logout } from '../lib/auth'

const router = useRouter()

onMounted(loadUser)

async function handleLogout() {
  await logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <main>
    <h1>Daftar Item</h1>
    <p v-if="auth.user">Masuk sebagai {{ auth.user.name }}</p>
    <button type="button" @click="handleLogout">Logout</button>
  </main>
</template>
