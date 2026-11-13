# Inventaris Alat Laboratorium

UTS Konstruksi dan Evolusi Perangkat Lunak (SVPL261501).

Aplikasi pencatatan inventaris alat laboratorium yang terdiri dari:

- `backend/` — RESTful API Laravel 13 dengan autentikasi token (Laravel Sanctum), dijalankan dengan FrankenPHP
- `frontend/` — antarmuka Vue 3 + Vite yang memakai API backend lewat Axios
- `postman/` — Postman Collection untuk menguji seluruh endpoint
- `docker-compose.yml` — menjalankan MySQL, backend, frontend, dan layanan pemantauan sekaligus
- `monitoring/` — konfigurasi Prometheus, aturan alarm, dan dashboard Grafana

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

## Pemantauan (Prometheus + Grafana)

`docker compose up -d` juga menjalankan empat layanan pemantau. Port-nya hanya dibuka
di `127.0.0.1`, tidak ke jaringan luar.

| Service | Alamat | Keterangan |
|---|---|---|
| prometheus | http://127.0.0.1:9090 | `/targets` untuk status scrape, `/alerts` untuk status alarm |
| grafana | http://127.0.0.1:3000 | dashboard **Inventaris Lab - Kesehatan Aplikasi** (folder Inventaris Lab) |
| cadvisor | (internal) | CPU, memori, jaringan per container |
| node-exporter | (internal) | CPU, memori, disk mesin host |

Backend (FrankenPHP/Caddy) membuka metrik HTTP di `backend:2019/metrics` (hanya di jaringan
compose), sehingga Prometheus mendapat jumlah permintaan, kode status, dan waktu respons.

```
monitoring/
├── prometheus/prometheus.yml        # target scrape
├── prometheus/alerts.yml            # aturan alarm BackendDown
└── grafana/
    ├── provisioning/                # datasource Prometheus + pemuat dashboard
    └── dashboards/inventaris-lab.json   # ekspor JSON dashboard
```

Isi dashboard (judul panel berupa pertanyaan):

1. Apakah setiap layanan sedang hidup? (`up`)
2. Berapa alarm yang sedang berbunyi? (`ALERTS`)
3. Container mana yang paling banyak memakai CPU? (%, 100% = 1 core)
4. Berapa memori yang dipakai tiap container? (byte)
5. Berapa permintaan per detik yang dilayani backend, per kode status? (req/s)
6. Seberapa lama backend menjawab permintaan (p95)? (detik)

### Alarm `BackendDown`

Berbunyi jika `up{job="backend"} == 0` selama 1 menit (`for: 1m`). Anotasi `action` berisi
tindakan pertama untuk penerima alarm. Cara membuktikan:

```sh
docker compose stop backend      # matikan backend dengan sengaja
# buka http://127.0.0.1:9090/alerts: Inactive → Pending → FIRING (±1–1,5 menit)
docker compose up -d backend     # pulihkan, alarm kembali Inactive
```

### Password admin Grafana

`GRAFANA_ADMIN_PASSWORD` wajib diisi di `.env`; compose menolak jalan jika kosong, jadi
Grafana tidak pernah berjalan dengan `admin/admin`. Kombinasi bawaan itu adalah yang pertama
dicoba penyerang dan bot. Siapa pun yang masuk sebagai admin bisa membaca seluruh metrik
(nama service, lalu lintas, waktu aktif), mengubah atau menghapus dashboard, menambah
datasource untuk menjangkau layanan internal lain, dan membuat akun baru. Pendaftaran akun
dan akses anonim juga dimatikan.

Grafana hanya memakai `GF_SECURITY_ADMIN_PASSWORD` saat database-nya pertama kali dibuat.
Untuk mengganti password pada volume `grafana_data` yang sudah ada:

```sh
docker compose exec grafana grafana cli admin reset-admin-password '<password-baru>'
```

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

## CI/CD

Workflow `.github/workflows/ci.yml` berisi tiga job:

| Job | Kapan berjalan | Isi |
|---|---|---|
| `backend-test` | push ke `main`, `dev`, `feature/**` dan Pull Request ke `main`/`dev` | `composer install`, `php artisan test` |
| `frontend-build` | sama dengan di atas | `npm ci`, `npm run build` |
| `deploy` | **hanya** push ke `main`, setelah dua job di atas lulus | build image backend & frontend, push ke `ghcr.io` dengan tag SHA commit, lalu smoke test image backend |

Image yang dipublikasikan:

- `ghcr.io/adityazulkarnaen/uts-pl-24-537764-sv-24449-backend:<sha>`
- `ghcr.io/adityazulkarnaen/uts-pl-24-537764-sv-24449-frontend:<sha>`

Smoke test memakai GitHub Secrets `APP_KEY` dan `DB_PASSWORD`
(Settings → Secrets and variables → Actions); nilai rahasia tidak disimpan di repository.

Branch `main` dan `dev` dilindungi ruleset: perubahan wajib lewat Pull Request dan status check
`backend-test` serta `frontend-build` harus lulus sebelum merge.
