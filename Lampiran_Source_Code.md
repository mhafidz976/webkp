# LAMPIRAN SOURCE CODE
# Aplikasi Penjadwalan Laboratorium Komputer Berbasis Web Multi Role

## 1. PSEUDOCODE SISTEM UTAMA

### 1.1 Pseudocode Login Multi Role

```
PROCEDURE LoginSystem()
    INPUT: email, password
    OUTPUT: user_session atau error_message
    
    // Validasi input
    IF email kosong OR password kosong THEN
        RETURN "Email dan password harus diisi"
    END IF
    
    // Query ke database
    user = database.query(
        "SELECT u.*, r.role_key 
         FROM users u 
         JOIN roles r ON u.role_id = r.id 
         WHERE u.email = ?", 
        [email]
    )
    
    IF user tidak ditemukan THEN
        RETURN "Email tidak terdaftar"
    END IF
    
    // Verifikasi password
    IF NOT password_verify(password, user.password_hash) THEN
        RETURN "Password salah"
    END IF
    
    // Set session berdasarkan role
    SESSION.user_id = user.id
    SESSION.user_name = user.nama
    SESSION.user_role = user.role_key
    
    // Redirect ke dashboard sesuai role
    SWITCH user.role_key
        CASE "admin":
            REDIRECT "dashboard.php"
        CASE "dosen":
            REDIRECT "dashboard.php"  
        CASE "teknisi":
            REDIRECT "dashboard.php"
        CASE "mahasiswa":
            REDIRECT "dashboard.php"
    END SWITCH
    
    RETURN "Login berhasil"
END PROCEDURE
```

### 1.2 Pseudocode Validasi Bentrok Jadwal

```
PROCEDURE CekBentrokJadwal(lab_id, hari, jam_mulai, jam_selesai, tanggal, schedule_id = NULL)
    OUTPUT: status_bentrok, detail_bentrok
    
    // Cek jadwal praktikum yang sudah ada
    praktikum = database.query(
        "SELECT * FROM praktikum_schedules 
         WHERE lab_id = ? AND hari = ? AND status = 'aktif'
         AND periode_mulai <= ? AND periode_selesai >= ?
         AND id != ?",
        [lab_id, hari, tanggal, tanggal, schedule_id]
    )
    
    FOR EACH jadwal IN praktikum
        IF AdaBentrokWaktu(jam_mulai, jam_selesai, jadwal.jam_mulai, jadwal.jam_selesai) THEN
            RETURN TRUE, "Bentrok dengan praktikum: " + jadwal.mata_kuliah
        END IF
    END FOR
    
    // Cek peminjaman lab di tanggal yang sama
    booking = database.query(
        "SELECT * FROM lab_bookings 
         WHERE lab_id = ? AND tanggal = ? AND status = 'approved'",
        [lab_id, tanggal]
    )
    
    FOR EACH pinjam IN booking
        IF AdaBentrokWaktu(jam_mulai, jam_selesai, pinjam.jam_mulai, pinjam.jam_selesai) THEN
            RETURN TRUE, "Bentrok dengan peminjaman: " + pinjam.keperluan
        END IF
    END FOR
    
    RETURN FALSE, "Tidak ada bentrok"
END PROCEDURE

FUNCTION AdaBentrokWaktu(mulai1, selesai1, mulai2, selesai2)
    // Konversi waktu ke menit
    start1 = KonversiKeMenit(mulai1)
    end1 = KonversiKeMenit(selesai1)
    start2 = KonversiKeMenit(mulai2)
    end2 = KonversiKeMenit(selesai2)
    
    // Cek overlap
    IF (start1 < end2 AND start2 < end1) THEN
        RETURN TRUE
    END IF
    
    RETURN FALSE
END FUNCTION
```

### 1.3 Pseudocode Dashboard Multi Role

```
PROCEDURE LoadDashboard(user_role)
    OUTPUT: data_dashboard
    
    SWITCH user_role
        CASE "admin":
            data = {
                total_labs: CountTable("labs"),
                total_praktikum: CountTable("praktikum_schedules"),
                total_booking: CountTable("lab_bookings"),
                pending_booking: QueryPendingBookings(),
                open_tickets: QueryOpenTickets(),
                today_schedules: QueryTodaySchedules(),
                lab_status: QueryLabStatusSummary()
            }
            
        CASE "dosen":
            data = {
                my_praktikum: QueryMyPraktikum(user_id),
                my_bookings: QueryMyBookings(user_id),
                today_schedules: QueryTodaySchedules(),
                available_labs: QueryAvailableLabs()
            }
            
        CASE "teknisi":
            data = {
                today_schedules: QueryTodaySchedules(),
                lab_status: QueryLabStatus(),
                open_tickets: QueryOpenTickets(),
                maintenance_schedule: QueryMaintenanceSchedule()
            }
            
        CASE "mahasiswa":
            data = {
                today_schedules: QueryTodaySchedules(),
                my_bookings: QueryMyBookings(user_id),
                available_labs: QueryAvailableLabs()
            }
    END SWITCH
    
    RETURN data
END PROCEDURE
```

## 2. STRUKTUR DATABASE

### 2.1 Schema Tabel Users

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL
);
```

### 2.2 Schema Tabel Praktikum Schedules

```sql
CREATE TABLE praktikum_schedules (
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
);
```

## 3. SOURCE CODE PENTING

### 3.1 Fungsi Autentikasi (auth.php)

```php
<?php
// Fungsi untuk memeriksa login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Fungsi untuk memeriksa role
function require_role($allowed_roles) {
    require_login();
    
    $user_role = $_SESSION['user_role'] ?? '';
    
    if (!in_array($user_role, (array)$allowed_roles, true)) {
        $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini';
        header('Location: dashboard.php');
        exit;
    }
}

// Fungsi untuk mendapatkan user yang sedang login
function current_user() {
    static $user = null;
    
    if ($user === null && isset($_SESSION['user_id'])) {
        global $conn;
        $stmt = $conn->prepare(
            'SELECT u.*, r.role_key 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.id = ?'
        );
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
    
    return $user;
}
?>
```

### 3.2 Fungsi Validasi Jadwal (functions.php)

```php
<?php
// Fungsi untuk cek bentrok jadwal
function cek_bentrok_jadwal($lab_id, $hari, $jam_mulai, $jam_selesai, $tanggal, $schedule_id = null) {
    global $conn;
    
    // Cek jadwal praktikum
    $stmt = $conn->prepare(
        'SELECT ps.*, l.nama_lab 
         FROM praktikum_schedules ps 
         JOIN labs l ON l.id = ps.lab_id 
         WHERE ps.lab_id = ? AND ps.hari = ? AND ps.status = "aktif"
         AND ps.periode_mulai <= ? AND ps.periode_selesai >= ?
         AND (ps.id != ? OR ? IS NULL)'
    );
    $stmt->bind_param('isssi', $lab_id, $hari, $tanggal, $tanggal, $schedule_id, $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if (ada_bentrok_waktu($jam_mulai, $jam_selesai, $row['jam_mulai'], $row['jam_selesai'])) {
            return [
                'bentrok' => true,
                'pesan' => 'Bentrok dengan praktikum: ' . $row['mata_kuliah'] . 
                          ' di ' . $row['nama_lab']
            ];
        }
    }
    $stmt->close();
    
    // Cek peminjaman lab
    $stmt = $conn->prepare(
        'SELECT lb.*, l.nama_lab 
         FROM lab_bookings lb 
         JOIN labs l ON l.id = lb.lab_id 
         WHERE lb.lab_id = ? AND lb.tanggal = ? AND lb.status = "approved"'
    );
    $stmt->bind_param('is', $lab_id, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if (ada_bentrok_waktu($jam_mulai, $jam_selesai, $row['jam_mulai'], $row['jam_selesai'])) {
            return [
                'bentrok' => true,
                'pesan' => 'Bentrok dengan peminjaman: ' . $row['keperluan'] . 
                          ' di ' . $row['nama_lab']
            ];
        }
    }
    $stmt->close();
    
    return ['bentrok' => false, 'pesan' => 'Tidak ada bentrok'];
}

// Fungsi helper untuk cek overlap waktu
function ada_bentrok_waktu($mulai1, $selesai1, $mulai2, $selesai2) {
    $start1 = strtotime('2000-01-01 ' . $mulai1);
    $end1 = strtotime('2000-01-01 ' . $selesai1);
    $start2 = strtotime('2000-01-01 ' . $mulai2);
    $end2 = strtotime('2000-01-01 ' . $selesai2);
    
    return ($start1 < $end2 && $start2 < $end1);
}
?>
```

### 3.3 Proses Login (login.php)

```php
<?php
require_once __DIR__ . '/koneksi.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validasi input
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        // Query user
        $stmt = $conn->prepare(
            'SELECT u.*, r.role_key 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.email = ?'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_role'] = $user['role_key'];
            
            // Log aktivitas
            log_activity($user['id'], 'login', 'User login ke sistem');
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email atau password salah';
        }
    }
}
?>
```

## 4. ALUR KERJA SISTEM

### 4.1 Flowchart Login Multi Role

```
[Start] → [Input Email/Password] → [Validasi Input] 
    ↓
[Query Database] → [User Ditemukan?] → [No] → [Error: Email tidak terdaftar]
    ↓ [Yes]
[Verifikasi Password] → [Password Benar?] → [No] → [Error: Password salah]
    ↓ [Yes]
[Set Session] → [Cek Role] → [Redirect Dashboard Role]
    ↓
[End]
```

### 4.2 Flowchart Validasi Jadwal

```
[Start] → [Input Data Jadwal] → [Query Praktikum Hari/Lab Sama]
    ↓
[Loop Setiap Praktikum] → [Cek Overlap Waktu] → [Ada Bentrok?] 
    ↓ [Yes]                    ↓ [No]
[Return Bentrok] ← [Next Praktikum] → [Selesai Loop Praktikum?]
    ↓ [No]                      ↓ [Yes]
[Query Booking Tanggal Sama] → [Loop Setiap Booking]
    ↓
[Cek Overlap Waktu] → [Ada Bentrok?] → [Yes] → [Return Bentrok]
    ↓ [No]
[Next Booking] → [Selesai Loop Booking?] → [No] → [Kembali Loop]
    ↓ [Yes]
[Return Tidak Ada Bentrok] → [End]
```

## 5. KESIMPULAN

Source code aplikasi penjadwalan laboratorium ini mengimplementasikan:

1. **Multi Role Authentication** - Sistem login dengan 4 role berbeda
2. **Schedule Conflict Detection** - Validasi otomatis untuk mencegah bentrok jadwal
3. **Real-time Dashboard** - Tampilan dinamis sesuai role user
4. **Database Normalization** - Struktur database yang optimal dengan foreign key constraints
5. **Session Management** - Keamanan session untuk autentikasi

Aplikasi dibangun dengan PHP procedural, MySQL database, dan Bootstrap 5 untuk UI yang responsif.

---
*Lampiran ini berisi pseudocode dan source code penting dari aplikasi Penjadwalan Laboratorium Komputer Berbasis Web Multi Role.*
