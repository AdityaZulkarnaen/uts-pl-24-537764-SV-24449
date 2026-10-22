import { reactive } from 'vue'
import api, { clearToken, getToken, setToken } from './api'

export const auth = reactive({
  user: null,
})

export function isLoggedIn() {
  return Boolean(getToken())
}

export async function login(email, password) {
  const { data } = await api.post('/login', {
    email,
    password,
    device_name: 'vue-frontend',
  })

  setToken(data.token)
  auth.user = data.user
}

export async function loadUser() {
  if (auth.user || !isLoggedIn()) {
    return
  }

  const { data } = await api.get('/me')
  auth.user = data.user
}

export async function logout() {
  try {
    await api.post('/logout')
  } finally {
    clearToken()
    auth.user = null
  }
}
