# Cara Setup Login dengan Google

## 1. Google Cloud Console

1. Buka [Google Cloud Console](https://console.cloud.google.com/) → pilih project atau buat baru.
2. Menu **APIs & Services > Credentials**.
3. Klik **+ CREATE CREDENTIALS > OAuth client ID**.
4. Pilih **Web application**.
5. **Authorized redirect URIs**: tambahkan  
   `http://localhost/webkp/google_login.php`
6. Klik **Create** → Anda akan dapatkan **Client ID** dan **Client Secret**.

## 2. Konfigurasi di aplikasi

Buka `google_config.php` dan ganti:

```php
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
```

## 3. Database

Jalankan SQL berikut (via phpMyAdmin atau MySQL CLI) untuk menambah kolom `google_id` dan `role_key`:

```sql
ALTER TABLE users ADD COLUMN google_id VARCHAR(100) NULL UNIQUE AFTER password_hash;
ALTER TABLE users ADD COLUMN role_key VARCHAR(50) NULL AFTER role_id;
```

Jika Anda membuat ulang DB dari `schema.sql`, pastikan kedua kolom ini ada.

## 4. Testing

- Buka `http://localhost/webkp/login.php`.
- Klik **Sign in with Google**.
- Login dengan akun Google Anda.
- Pertama kali akan auto-register sebagai **mahasiswa**.
- Selanjutnya login langsung tanpa password.

## 5. Peran (role)

- Auto-register Google → role `mahasiswa`.
- Jika email sudah terdaftar manual dengan role lain (admin/dosen/teknisi), setelah login Google pertama kali, kolom `google_id` akan di-link ke akun tersebut, sehingga role ikut akun lama.

## 6. Keamanan

- Pastikan `google_config.php` tidak di-commit ke repo publik (tambah ke `.gitignore` jika perlu).
- Redirect URI harus cocok persis dengan yang didaftarkan di Google Cloud Console.
- State token digunakan untuk mitigasi CSRF.
