# PRD — Inventaris Alat Laboratorium

Product Requirements Document untuk aplikasi web **Inventaris Lab**
(UTS Konstruksi dan Evolusi Perangkat Lunak, SVPL261501).

## 1. Latar belakang

Pencatatan alat laboratorium yang masih memakai kertas atau spreadsheet terpisah membuat
petugas sulit menjawab pertanyaan sederhana: alat apa saja yang ada, berapa jumlahnya, di
mana letaknya, dan bagaimana kondisinya. Data mudah berbeda antar salinan dan tidak ada
jejak siapa yang mengubahnya.

## 2. Tujuan

- Satu sumber data inventaris alat yang bisa diakses lewat browser.
- Petugas dapat menambah, mencari, mengubah, dan menghapus data alat dengan cepat.
- Hanya pengguna yang login yang dapat melihat dan mengubah data.
- Aplikasi mudah dijalankan (satu perintah Docker Compose), teruji otomatis di CI, dan
  kesehatannya dapat dipantau.

Di luar cakupan: peminjaman/pengembalian alat, multi-laboratorium, peran pengguna
bertingkat, dan pendaftaran akun mandiri.

## 3. Pengguna

| Peran | Kebutuhan |
|---|---|
| Petugas / laboran | Mencatat alat baru, memperbarui jumlah dan kondisi, mencari alat dengan cepat |
| Operator sistem | Menjalankan aplikasi, mengetahui saat layanan mati, dan menemukan penyebabnya |

## 4. Kebutuhan fungsional

### 4.1 Autentikasi

| ID | Kebutuhan |
|---|---|
| F-01 | Pengguna login dengan email dan password; sistem mengembalikan token (Laravel Sanctum). |
| F-02 | Kredensial salah dijawab `422` dengan pesan yang jelas. |
| F-03 | Pengguna dapat logout; token yang sedang dipakai dicabut. |
| F-04 | Semua endpoint selain login menolak permintaan tanpa token valid dengan `401` JSON. |
| F-05 | Frontend mengarahkan pengguna yang belum login ke halaman `/login`, dan pengguna yang sudah login keluar dari `/login`. |

### 4.2 Data alat (`items`)

Atribut setiap alat:

| Field | Aturan |
|---|---|
| `code` | wajib, unik, maks. 30 karakter |
| `name` | wajib, maks. 255 karakter |
| `category` | wajib, maks. 100 karakter |
| `quantity` | wajib, bilangan bulat 0–100000 |
| `condition` | wajib, salah satu dari `baik`, `rusak_ringan`, `rusak_berat` |
| `location` | wajib, maks. 100 karakter |
| `notes` | opsional, maks. 1000 karakter |

| ID | Kebutuhan |
|---|---|
| F-10 | Menampilkan daftar alat terbaru dengan pagination (`per_page` 1–100, bawaan 10). |
| F-11 | Mencari alat berdasarkan nama, kode, atau kategori (`?q=`) dan menyaring kondisi (`?condition=`). |
| F-12 | Menambah alat; sukses dijawab `201`, input tidak valid dijawab `422` beserta pesan per field. |
| F-13 | Melihat detail satu alat; id yang tidak ada dijawab `404`. |
| F-14 | Mengubah data alat dengan validasi yang sama (kode tetap unik kecuali milik alat itu sendiri). |
| F-15 | Menghapus alat setelah konfirmasi di antarmuka. |

### 4.3 Antarmuka

| ID | Kebutuhan |
|---|---|
| F-20 | Halaman login sederhana. |
| F-21 | Halaman daftar alat dengan kolom pencarian, filter kondisi, pagination, tombol tambah, ubah, dan hapus. |
| F-22 | Form tambah/ubah dalam dialog, menampilkan pesan validasi dari API. |
| F-23 | Tampilan tetap dapat dipakai di layar ponsel. |

## 5. Kebutuhan non-fungsional

| ID | Kategori | Kebutuhan |
|---|---|---|
| N-01 | Keamanan | Rahasia (APP_KEY, password DB, password Grafana) hanya ada di `.env` / GitHub Secrets, tidak di repository atau image. |
| N-02 | Keamanan | Access log server dimatikan agar header sensitif (token) tidak tercatat. |
| N-03 | Keamanan | Port pemantauan hanya dibuka di `127.0.0.1`; Grafana tidak boleh memakai `admin/admin`. |
| N-04 | Keandalan | Data MySQL, Prometheus, dan Grafana disimpan di volume sehingga bertahan setelah container dibuat ulang. |
| N-05 | Keandalan | Endpoint `/health` memeriksa database dan cache; dipakai healthcheck Docker. |
| N-06 | Observabilitas | Log Laravel terstruktur ke stdout, ukuran log container dibatasi (10 MB × 3). |
| N-07 | Observabilitas | Metrik CPU/memori per container, permintaan per detik, waktu respons, dan status hidup layanan tampil di Grafana. |
| N-08 | Observabilitas | Alarm `BackendDown` berbunyi jika backend tidak terjangkau lebih dari 1 menit, dengan anotasi tindakan pertama. |
| N-09 | Reproducibility | Semua versi image dikunci (tanpa `latest`). |
| N-10 | Kualitas | Setiap push dan Pull Request menjalankan test backend dan build frontend; merge ke `main`/`dev` wajib lewat PR yang lulus CI. |

## 6. Arsitektur

```
Browser ──► frontend (Nginx + Vue 3) ──/api──► backend (Laravel 13 di FrankenPHP) ──► db (MySQL 8.4)
                                                   │ :2019/metrics
cadvisor, node-exporter, grafana ──────────────────┴──► prometheus ──► grafana (dashboard)
                                                                  └──► /alerts (BackendDown)
```

| Komponen | Teknologi |
|---|---|
| Backend | Laravel 13, Sanctum, FormRequest, API Resource, FrankenPHP |
| Frontend | Vue 3, Vite, Vue Router, Axios, Nginx |
| Database | MySQL 8.4 (SQLite in-memory untuk test) |
| CI/CD | GitHub Actions → image di `ghcr.io` bertag SHA commit |
| Pemantauan | Prometheus, Grafana, cAdvisor, node-exporter |

## 7. Endpoint API

| Method | Endpoint | Auth | Respons |
|---|---|---|---|
| POST | `/api/login` | – | 200, 422 |
| POST | `/api/logout` | token | 200 |
| GET | `/api/me` | token | 200 |
| GET | `/api/items` | token | 200 |
| POST | `/api/items` | token | 201, 422 |
| GET | `/api/items/{id}` | token | 200, 404 |
| PUT | `/api/items/{id}` | token | 200, 404, 422 |
| DELETE | `/api/items/{id}` | token | 200, 404 |
| GET | `/health` | – | 200 sehat, 503 jika database/cache bermasalah |

## 8. Kriteria penerimaan

- `docker compose up -d --build` menjalankan seluruh layanan dan akun demo bisa login.
- Feature test autentikasi dan CRUD lulus (`php artisan test`), Postman Collection lulus di Newman.
- Seluruh target di Prometheus `/targets` berstatus UP.
- Dashboard Grafana menampilkan data pada keenam panel.
- Mematikan backend membuat alarm `BackendDown` berubah dari Pending menjadi FIRING, dan
  kembali Inactive setelah backend dinyalakan.
