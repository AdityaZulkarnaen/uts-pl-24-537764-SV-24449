# Inventaris Alat Laboratorium

UTS Konstruksi dan Evolusi Perangkat Lunak (SVPL261501).

Aplikasi pencatatan inventaris alat laboratorium yang terdiri dari:

- `backend/` — RESTful API Laravel 13 dengan autentikasi token (Laravel Sanctum), dijalankan dengan FrankenPHP
- `frontend/` — antarmuka Vue 3 + Vite yang memakai API backend lewat Axios
- `postman/` — Postman Collection untuk menguji seluruh endpoint
- `docker-compose.yml` — menjalankan MySQL, backend, dan frontend sekaligus

## Menjalankan dengan Docker

Prasyarat: Docker dengan Docker Compose.

```sh
cp .env.example .env
# isi APP_KEY (php artisan key:generate --show), DB_PASSWORD, dan DB_ROOT_PASSWORD
docker compose up -d --build
```

| Service | Alamat | Keterangan |
|---|---|---|
| frontend | http://localhost:8080 | Nginx menyajikan build Vue dan meneruskan `/api` ke `backend:8000` |
| backend | http://localhost:8000/api | Laravel di FrankenPHP |
| db | (internal) | MySQL 8.4, data disimpan di volume `db_data` |

Akun demo dibuat otomatis oleh seeder: `admin@lab.test` / `password`
(dapat diubah lewat `DEMO_USER_EMAIL` dan `DEMO_USER_PASSWORD`).

Port di laptop dapat diganti lewat `FRONTEND_PORT` dan `BACKEND_PORT` di `.env`
jika 8080 atau 8000 sudah dipakai aplikasi lain.

Data tetap ada setelah `docker compose down` lalu `docker compose up -d`.
Untuk menghapus data sekalian, gunakan `docker compose down -v`.

## Pengembangan lokal (tanpa Docker)

Backend (PHP 8.3, Composer):

```sh
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Frontend (Node.js 22):

```sh
cd frontend
npm install
cp .env.example .env   # VITE_API_BASE_URL=http://localhost:8000/api
npm run dev
```

## Pengujian

```sh
cd backend && php artisan test
npx newman run postman/uts-pl.postman_collection.json
```

## Endpoint API

Semua endpoint selain login membutuhkan header `Authorization: Bearer <token>`.

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/login` | Login, mengembalikan token |
| POST | `/api/logout` | Mencabut token yang sedang dipakai |
| GET | `/api/me` | Data user yang login |
| GET | `/api/items` | Daftar item (`?q=`, `?condition=`, `?page=`, `?per_page=`) |
| POST | `/api/items` | Tambah item (201) |
| GET | `/api/items/{id}` | Detail item |
| PUT | `/api/items/{id}` | Ubah item |
| DELETE | `/api/items/{id}` | Hapus item |

Kode status: `201` saat data dibuat, `422` saat validasi gagal, `401` saat token tidak ada
atau tidak valid, `404` saat data tidak ditemukan.

## Alur data

1. Vue mengirim request lewat Axios ke `VITE_API_BASE_URL` dengan header Bearer token.
2. Di Docker, Nginx pada container frontend meneruskan `/api/*` ke `http://backend:8000`.
3. FrankenPHP menjalankan Laravel: middleware `auth:sanctum` memeriksa token, FormRequest memvalidasi input.
4. Eloquent membaca/menulis tabel `items` di MySQL (`db:3306`).
5. Laravel membalas JSON melalui `ItemResource`, lalu Vue memperbarui tampilan.

## CI

Workflow `.github/workflows/ci.yml` menjalankan `php artisan test` (job `backend-test`) serta
`npm ci` dan `npm run build` (job `frontend-build`) pada push ke `main`, `dev`, `feature/**`,
dan pada Pull Request ke `main` atau `dev`.
