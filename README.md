# Web Penjadwalan Lab Kampus

Aplikasi web untuk mengelola **jadwal praktikum, peminjaman, data laboratorium, software, tiket kerusakan, dan laporan penggunaan lab kampus** dengan multi-role (Admin, Dosen, Teknisi, Mahasiswa).

Dibuat dengan:
- PHP (prosedural, mysqli)
- MySQL
- XAMPP (Apache + MySQL)
- Bootstrap 5 (via CDN) untuk tampilan tabel dan form

## Struktur Proyek (Singkat)

Folder: `c:/xampp/htdocs/webkp`

File utama (modul besar):
- `koneksi.php` — konfigurasi dan koneksi ke database MySQL (`db_lab`).
- `auth.php` — helper autentikasi & role (`require_login`, `require_role`).
- `functions.php` — fungsi umum (log aktivitas, notifikasi, cek bentrok jadwal, helper data).
- `schema.sql` — **skema lengkap database** (roles, users, labs, praktikum, bookings, software, tiket, notifikasi, dll.).

Modul halaman:
- Autentikasi & dashboard
  - `login.php`, `logout.php`, `dashboard.php`, `partials_nav.php`.
- Manajemen pengguna & role
  - `users_index.php`, `users_form.php` (admin saja).
- Data laboratorium & software
  - `labs_index.php`, `labs_form.php`.
  - `softwares_index.php`, `softwares_form.php`.
- Jadwal praktikum
  - `praktikum_index.php`, `praktikum_form.php`.
- Peminjaman lab non-praktikum
  - `bookings_index.php`, `bookings_form.php`.
- Kalender & tampilan TV
  - `calendar.php` — kalender lab terpusat.
  - `tv_display.php` — tampilan jadwal lab untuk TV (read-only, tanpa login).
- Notifikasi & tiket kerusakan
  - `notifications.php` — daftar notifikasi per user.
  - `tickets_index.php`, `tickets_form.php` — tiket kerusakan / incident.
- Laporan
  - `reports.php` — laporan & rekap penggunaan lab.

## Persiapan di XAMPP

1. Pastikan XAMPP sudah terinstall.
2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Pastikan folder proyek berada di:
   - `c:/xampp/htdocs/webkp`

## Setup Database (Menggunakan `schema.sql`)

1. Buka browser dan masuk ke phpMyAdmin:

   ```text
   http://localhost/phpmyadmin
   ```

2. Buat / pilih database `db_lab` (kalau belum ada, bisa dibuat otomatis saat import).

3. Masuk ke menu **Import**, pilih file:

   ```text
   c:/xampp/htdocs/webkp/schema.sql
   ```

   lalu jalankan. File ini akan membuat semua tabel yang dibutuhkan:
   - `roles`, `users`
   - `labs`, `lab_softwares`
   - `praktikum_schedules`, `lab_bookings`
   - `notifications`, `activity_logs`
   - `tickets`, `attendances`

4. Pastikan pengaturan di `koneksi.php` sesuai dengan server lokal kamu:

   ```php
   $host = 'localhost';
   $user = 'root';
   $pass = '';
   $db   = 'db_lab';
   ```

   - Jika password user `root` MySQL kamu tidak kosong, ubah variabel `$pass`.
   - Jika memakai nama database lain, sesuaikan variabel `$db`.

5. Tambahkan akun admin pertama ke tabel `users`:

   a. (Opsional) Buat file sementara `hash.php` di folder `webkp`:

   ```php
   <?php
   echo password_hash('admin123', PASSWORD_DEFAULT);
   ```

   Buka di browser `http://localhost/webkp/hash.php` lalu salin hasil hash.

   b. Di phpMyAdmin > database `db_lab` > tab **SQL**, jalankan (ganti `HASIL_HASH` dengan hash yang kamu salin):

   ```sql
   INSERT INTO users (nama, email, password_hash, role_id)
   VALUES (
       'Admin Demo',
       'admin@lab.test',
       'HASIL_HASH',
       (SELECT id FROM roles WHERE role_key = 'admin' LIMIT 1)
   );
   ```

   c. Hapus `hash.php` setelah selesai.

## Cara Menjalankan Aplikasi

1. Pastikan **Apache** dan **MySQL** sudah berjalan di XAMPP.

2. Buka halaman login:

   ```text
   http://localhost/webkp/login.php
   ```

3. Login dengan akun admin yang sudah dibuat, misalnya:

   - Email: `admin@lab.test`
   - Password: `admin123`

4. Setelah login akan masuk ke **Dashboard**:
   - Lihat ringkasan jumlah lab, jadwal praktikum, peminjaman, tiket.
   - Gunakan navbar untuk pindah modul:
     - **Jadwal Praktikum** → `praktikum_index.php`
     - **Peminjaman Lab** → `bookings_index.php`
     - **Kalender Lab** → `calendar.php`
     - **Data Lab** → `labs_index.php` (Admin & Teknisi)
     - **Software Lab** → `softwares_index.php` (Admin & Teknisi)
     - **Tiket Kerusakan** → `tickets_index.php`
     - **Pengguna** → `users_index.php` (Admin saja)
     - **Laporan** → `reports.php` (Admin saja)
     - **Notifikasi** → `notifications.php`

5. Untuk tampilan jadwal di TV/lobby lab (tanpa login):

   ```text
   http://localhost/webkp/tv_display.php?lab_id=ID_LAB
   ```

   Ganti `ID_LAB` dengan ID lab yang ada di tabel `labs`.

## Ringkasan Fitur per Role

- **Admin Lab**
  - Kelola user (dosen, teknisi, mahasiswa).
  - Kelola data lab & status lab.
  - Kelola software per lab.
  - Mengelola dan melihat semua jadwal praktikum.
  - Persetujuan peminjaman lab (approve / reject).
  - Melihat & mengelola tiket kerusakan.
  - Mengakses laporan & rekap penggunaan lab.

- **Dosen**
  - Mengajukan / mengedit jadwal praktikum (dosen pemilik).
  - Melihat jadwal praktikum.
  - Mengajukan peminjaman lab (workshop, ujian, penelitian, dll.).
  - Melihat status peminjaman lab miliknya.

- **Teknisi Laboratorium**
  - Melihat jadwal penggunaan lab (praktikum & peminjaman).
  - Mengatur status lab (tersedia, digunakan, maintenance/rusak).
  - Mengelola software & jadwal update per lab.
  - Menangani tiket kerusakan komputer/perangkat.

- **Mahasiswa**
  - Melihat jadwal praktikum.
  - Mengajukan peminjaman lab (sesuai kebijakan kampus).
  - Melihat status peminjaman miliknya.

## Contoh Akun Demo (Disarankan)

Setelah tabel `users` terisi, kamu bisa membuat akun-akun berikut (password di-hash dengan `password_hash`):

| Role          | Username (Email) | Password plain |
|--------------|------------------|----------------|
| Admin Lab    | admin@lab.test   | admin123       |
| Dosen        | dosen@lab.test   | dosen123       |
| Teknisi      | teknisi@lab.test | teknisi123     |
| Mahasiswa    | mhs@lab.test     | mhs123         |

Untuk setiap akun, buat hash password dengan cara yang sama seperti akun admin, lalu `INSERT` ke tabel `users` dengan `role_id` sesuai role-nya.

Proyek ini dapat dijadikan dasar/kerangka untuk dikembangkan lebih lanjut (integrasi SIAKAD, export PDF/Excel, absensi QR, dashboard grafik, dsb.) sesuai kebutuhan masing-masing kampus/lab.
