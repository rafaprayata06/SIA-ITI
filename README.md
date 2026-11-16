# SIAKAD ITI

Sistem Informasi Akademik Institut Teknologi Indonesia (ITI).

Project ini dikembangkan menggunakan Laravel sebagai framework backend dan PostgreSQL sebagai database.

## Teknologi

| Teknologi | Versi / Keterangan |
|---|---|
| Laravel | 13.39.0 |
| PHP | 8.5 |
| Database | PostgreSQL 17 |
| Web Server | Nginx |
| PHP Process Manager | PHP-FPM |
| Dependency Manager | Composer |
| Containerization | Docker & Docker Compose |

## Persyaratan

Untuk menjalankan project menggunakan Docker, pastikan sudah tersedia:

- Git
- Docker Desktop
- Docker Compose

Pastikan Docker Desktop sudah berjalan sebelum menjalankan perintah Docker.

## Menjalankan Project dengan Docker (Direkomendasikan)

> Panduan teknis ringkas Docker juga tersedia di **[docs/docker-setup.md](docs/docker-setup.md)**.

### 1. Clone repository & buat branch fitur baru dari `staging`

Jangan ngoding langsung di branch `main` maupun `staging`. Pindah ke `staging` terlebih dahulu untuk mengambil kode dasar terbaru, lalu buat branch fitur kamu sendiri (contoh: `feature/krs`):

```bash
git clone [https://github.com/rafaprayata06/SIA-ITI.git](https://github.com/rafaprayata06/SIA-ITI.git)
cd SIA-ITI
git checkout staging
git pull origin staging
git checkout -b feature/krs
```

### 2. Membuat file environment

Salin file `.env.example` menjadi `.env`.

PowerShell (Windows):

```powershell
Copy-Item .env.example .env
```

Git Bash / Linux / macOS:

```bash
cp .env.example .env
```

### 3. Mengatur konfigurasi environment

Pastikan konfigurasi berikut sesuai pada file `.env` (mengikuti template `.env.example`):

```env
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=sia-iti
DB_USERNAME=postgres
DB_PASSWORD=change_me_locally
```

**Catatan:** Konfigurasi database tersebut mengikuti Docker Compose untuk development lokal saat ini. Jangan menggunakan password contoh tersebut untuk lingkungan production.

### 4. Menjalankan container

Jalankan perintah:

```bash
docker compose up -d --build
```

Perintah tersebut akan membangun image dan menjalankan service:

- `app`: Laravel dengan PHP-FPM.
- `nginx`: Web server untuk menerima HTTP request.
- `db`: Database PostgreSQL 17.

### 5. Menginstal dependency PHP

```bash
docker compose exec app composer install
```

Perintah ini menginstal dependency Laravel ke dalam container.

### 6. Membuat application key

```bash
docker compose exec app php artisan key:generate
```

### 7. Menjalankan database migration

```bash
docker compose exec app php artisan migrate
```

Perintah ini menjalankan migration untuk membuat struktur tabel database.

### 8. Mengakses aplikasi

Buka browser dan kunjungi:

http://localhost:8000

---

## Alternatif: Setup Lokal Manual (Tanpa Docker)

Bagi programmer yang sudah terlanjur menginstal **PHP 8.5**, **Composer**, dan **PostgreSQL** secara manual di laptop masing-masing, tetap dapat menjalankan aplikasi tanpa terpengaruh file Docker:

1. Pastikan sudah berada di branch fitur masing-masing yang dibuat dari `staging` (contoh: `git checkout -b feature/krs`).
2. Jalankan `composer install` dan `npm install`.
3. Salin `.env.example` menjadi `.env`, lalu sesuaikan koneksi database lokal (`DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=sia-iti`, serta `DB_USERNAME` dan `DB_PASSWORD` PostgreSQL lokal).
4. Jalankan `php artisan key:generate`, `php artisan migrate`, lalu `php artisan serve`.

---

## Perintah Docker yang Sering Digunakan

| Perintah | Fungsi |
|---|---|
| `docker compose up -d` | Menjalankan container |
| `docker compose up -d --build` | Build ulang image dan menjalankan container |
| `docker compose down` | Menghentikan dan menghapus container |
| `docker compose ps` | Melihat status container |
| `docker compose logs -f` | Melihat log seluruh service |
| `docker compose logs -f app` | Melihat log service aplikasi |
| `docker compose exec app bash` | Masuk ke terminal container aplikasi |

**Penting:** Jangan menggunakan `docker compose down -v` sembarangan karena opsi `-v` juga menghapus named volume, termasuk penyimpanan database PostgreSQL.

## Perintah Laravel

Jalankan perintah Artisan melalui container aplikasi:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan route:list
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
```

Untuk menjalankan pengujian:

```bash
docker compose exec app php artisan test
```

## Struktur Infrastruktur Docker

```text
SIA-ITI/
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── docker/
│   └── nginx/
│       └── default.conf
├── docs/
│   └── docker-setup.md
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
└── ...
```

## Alur Branching & Pull Request (Git)

- **`main`**: Branch utama berisi kode stabil yang siap demo/production.
- **`staging`**: Branch integrasi tempat seluruh fitur tim digabung dan diuji otomatis oleh CI.
- **`feature/<nama-fitur>`**: Branch kerja masing-masing tim programmer yang dibuat di bawah `staging` (contoh: `feature/krs`, `feature/khs`, `feature/auth`).

**Langkah menyetor fitur setelah selesai ngoding:**
1. Pastikan branch fitur sudah dites dan berjalan normal tanpa error.
2. Tarik pembaruan terbaru dari `staging` (`git pull origin staging`) untuk memastikan tidak ada konflik file.
3. Push branch fitur kamu ke GitHub (`git push origin feature/<nama-fitur>`).
4. Buka **Pull Request (PR)** di GitHub dengan arah **`base: staging` <- `compare: feature/<nama-fitur>`** dan tunggu pengecekan otomatis CI selesai sebelum di-merge.

## Catatan Pengembangan

- Docker digunakan untuk menyediakan lingkungan development lokal yang lebih konsisten.
- PostgreSQL berjalan di dalam container Docker.
- Port aplikasi pada host adalah `8000`.
- Port PostgreSQL pada host adalah `5433`, sedangkan komunikasi antar-container menggunakan port `5432`.
- Konfigurasi deployment ke server kampus maupun cloud akan ditentukan dan didokumentasikan secara terpisah setelah keputusan infrastruktur dibuat.