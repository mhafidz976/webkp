-- Skema database lengkap untuk sistem penjadwalan & manajemen lab kampus

CREATE DATABASE IF NOT EXISTS db_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_lab;

-- 1. Role & Pengguna -------------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(50) NOT NULL UNIQUE, -- admin, dosen, teknisi, mahasiswa
    nama VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- 2. Data Laboratorium -----------------------------------------------------

CREATE TABLE IF NOT EXISTS labs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lab VARCHAR(100) NOT NULL,
    kapasitas INT DEFAULT 0,
    spesifikasi TEXT NULL,
    lokasi VARCHAR(150) NULL,
    status_lab ENUM('available','in_use','maintenance') DEFAULT 'available'
) ENGINE=InnoDB;

-- 3. Manajemen Software & Perangkat ---------------------------------------

CREATE TABLE IF NOT EXISTS lab_softwares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_id INT NOT NULL,
    nama_software VARCHAR(150) NOT NULL,
    versi VARCHAR(50) NULL,
    jadwal_update DATE NULL,
    kebutuhan_mk TEXT NULL,
    FOREIGN KEY (lab_id) REFERENCES labs(id)
) ENGINE=InnoDB;

-- 4. Jadwal Praktikum ------------------------------------------------------

CREATE TABLE IF NOT EXISTS praktikum_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_id INT NOT NULL,
    dosen_id INT NOT NULL,
    mata_kuliah VARCHAR(150) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    periode_mulai DATE NOT NULL,
    periode_selesai DATE NOT NULL,
    tipe ENUM('sekali','mingguan','semester') DEFAULT 'mingguan',
    status ENUM('aktif','dibatalkan') DEFAULT 'aktif',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES labs(id),
    FOREIGN KEY (dosen_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- 5. Peminjaman Lab Non-Praktikum ----------------------------------------

CREATE TABLE IF NOT EXISTS lab_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_id INT NOT NULL,
    peminjam_id INT NOT NULL,
    approved_by INT NULL,
    jenis VARCHAR(50) NOT NULL, -- workshop, ujian, penelitian, dll
    keperluan VARCHAR(150) NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES labs(id),
    FOREIGN KEY (peminjam_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- 6. Notifikasi & Informasi -----------------------------------------------

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jenis VARCHAR(50) NOT NULL, -- jadwal, booking, maintenance, dll
    pesan TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- 7. Log Aktivitas ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    aksi VARCHAR(100) NOT NULL,
    detail TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- 8. Tiket Kerusakan / Incident -------------------------------------------

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_id INT NOT NULL,
    pelapor_id INT NOT NULL,
    teknisi_id INT NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT NOT NULL,
    status ENUM('open','in_progress','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES labs(id),
    FOREIGN KEY (pelapor_id) REFERENCES users(id),
    FOREIGN KEY (teknisi_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- 9. Absensi Praktikum (untuk QR sederhana) -------------------------------

CREATE TABLE IF NOT EXISTS attendances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    user_id INT NOT NULL,
    waktu DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES praktikum_schedules(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- 10. Data awal role (tanpa user, karena perlu password_hash dari PHP) ----

INSERT INTO roles (role_key, nama) VALUES
('admin', 'Admin Lab'),
('dosen', 'Dosen'),
('teknisi', 'Teknisi Laboratorium'),
('mahasiswa', 'Mahasiswa')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);
