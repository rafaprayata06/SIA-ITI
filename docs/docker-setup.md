# Panduan Setup Docker Environment — SIA-ITI

Dokumen ini merupakan panduan teknis standar untuk menjalankan aplikasi **SIA-ITI** secara lokal menggunakan Docker (referensi tiket **SCRUM-42**). Untuk ringkasan umum proyek, lihat juga file [README.md](../README.md).

---

## 1. Arsitektur Layanan (Services)

Konfigurasi `docker-compose.yml` menjalankan tiga layanan utama dalam satu jaringan internal Docker:

| Nama Service | Nama Container | Image / Basis           | Port Host -> Container | Fungsi |
| :---         | :---           | :---                    | :---                   | :---   |
| `app`        | `sia_iti_app`  | `sia-iti-app` (PHP-FPM) | `9000/tcp` (internal)  | Menjalankan aplikasi Laravel & Artisan CLI |
| `nginx`      | `sia_iti_nginx`| `nginx:alpine`          | `8000 -> 80`           | Web server & reverse proxy ke `/var/www/public` |
| `db`         | `sia_iti_db`   | `postgres:17`           | `5433 -> 5432`         | Database utama PostgreSQL |

---

## 2. Prasyarat

* **Docker Desktop** (Windows/macOS) atau **Docker Engine + Docker Compose v2** (Linux) sudah terpasang dan berjalan.
* **Git** untuk menyinkronkan kode dari repositori.

---

## 3. Langkah Menjalankan di Lokal

1. **Salin file konfigurasi environment:**
   ```bash
   cp .env.example .env
   ```
   Pastikan parameter koneksi database pada `.env` sudah sesuai dengan nama service Docker:
   ```env
   APP_URL=http://localhost:8000

   DB_CONNECTION=pgsql
   DB_HOST=db
   DB_PORT=5432
   DB_DATABASE=sia-iti
   DB_USERNAME=postgres
   DB_PASSWORD=change_me_locally
   ```

2. **Build image dan jalankan seluruh container:**
   ```bash
   docker compose up -d --build
   ```

3. **Generate application key dan jalankan migrasi database:**
   *(Wajib dijalankan saat pertama kali setup karena driver `session`, `cache`, dan `queue` menggunakan database)*
   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```

4. **Verifikasi layanan:**
   * Cek status container (pastikan `sia_iti_db` berstatus `healthy` dan seluruh service berstatus `Up`):
     ```bash
     docker compose ps
     ```
   * Buka browser dan akses: **`http://localhost:8000`**

---

## 4. Perintah Operasional Harian

* **Menjalankan perintah Artisan / Composer di dalam container:**
  ```bash
  docker compose exec app php artisan <perintah>
  docker compose exec app composer install
  ```
* **Melihat log container secara real-time:**
  ```bash
  docker compose logs -f app
  docker compose logs -f nginx
  ```
* **Menghentikan seluruh container tanpa menghapus data database:**
  ```bash
  docker compose down
  ```
* **Reset total termasuk volume database (jika ingin migrasi ulang dari awal):**
  ```bash
  docker compose down -v
  ```