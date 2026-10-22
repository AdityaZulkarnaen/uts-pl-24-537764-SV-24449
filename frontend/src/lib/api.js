import axios from 'axios'

const TOKEN_KEY = 'inventaris_lab_token'

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY)
}

if (!import.meta.env.VITE_API_BASE_URL) {
  console.error('VITE_API_BASE_URL belum diatur. Salin .env.example menjadi .env.')
}

// The API address comes only from the environment, never from components.
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = getToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

let unauthorizedHandler = () => {}

/**
 * Register what should happen when the API rejects the stored token.
 */
export function onUnauthorized(handler) {
  unauthorizedHandler = handler
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && getToken()) {
      clearToken()
      unauthorizedHandler()
    }

    return Promise.reject(error)
  },
)

export default api
