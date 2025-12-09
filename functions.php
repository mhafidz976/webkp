<?php
// Fungsi umum untuk sistem penjadwalan lab

require_once __DIR__ . '/koneksi.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function log_activity(string $aksi, string $detail = ''): void
{
    global $conn;
    $userId = $_SESSION['user']['id'] ?? null;

    $stmt = $conn->prepare('INSERT INTO activity_logs (user_id, aksi, detail) VALUES (?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('iss', $userId, $aksi, $detail);
        $stmt->execute();
        $stmt->close();
    }
}

function create_notification(int $userId, string $jenis, string $pesan): void
{
    global $conn;
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, jenis, pesan) VALUES (?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('iss', $userId, $jenis, $pesan);
        $stmt->execute();
        $stmt->close();
    }
}

function get_roles(): array
{
    global $conn;
    $roles = [];
    $result = $conn->query('SELECT id, role_key, nama FROM roles ORDER BY id');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
    }
    return $roles;
}

function get_labs(): array
{
    global $conn;
    $labs = [];
    $result = $conn->query('SELECT * FROM labs ORDER BY nama_lab');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $labs[] = $row;
        }
    }
    return $labs;
}

function get_users_by_roles(array $roleKeys): array
{
    global $conn;
    if (empty($roleKeys)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($roleKeys), '?'));
    $types        = str_repeat('s', count($roleKeys));

    $sql  = "SELECT u.*, r.role_key, r.nama AS role_nama
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.role_key IN ($placeholders)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$roleKeys);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    return $users;
}

function waktu_overlap(string $mulai1, string $selesai1, string $mulai2, string $selesai2): bool
{
    return ($mulai1 < $selesai2) && ($selesai1 > $mulai2);
}

function find_praktikum_conflicts(
    int $labId,
    int $dosenId,
    string $kelas,
    string $hari,
    string $jamMulai,
    string $jamSelesai,
    string $periodeMulai,
    string $periodeSelesai,
    ?int $excludeId = null
): array {
    global $conn;

    $sql = "SELECT ps.*, l.nama_lab, u.nama AS dosen_nama
            FROM praktikum_schedules ps
            JOIN labs l ON l.id = ps.lab_id
            JOIN users u ON u.id = ps.dosen_id
            WHERE ps.status = 'aktif'
              AND ps.hari = ?
              AND ps.periode_mulai <= ?
              AND ps.periode_selesai >= ?";

    $params = [$hari, $periodeSelesai, $periodeMulai];
    $types  = 'sss';

    if ($excludeId !== null) {
        $sql     .= ' AND ps.id <> ?';
        $params[] = $excludeId;
        $types   .= 'i';
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $conflicts = [];
    while ($row = $result->fetch_assoc()) {
        if (!waktu_overlap($row['jam_mulai'], $row['jam_selesai'], $jamMulai, $jamSelesai)) {
            continue;
        }

        $reasons = [];
        if ((int)$row['lab_id'] === $labId) {
            $reasons[] = 'lab';
        }
        if ((int)$row['dosen_id'] === $dosenId) {
            $reasons[] = 'dosen';
        }
        if ($row['kelas'] === $kelas) {
            $reasons[] = 'kelas';
        }

        if (!empty($reasons)) {
            $row['reasons'] = $reasons;
            $conflicts[]    = $row;
        }
    }

    $stmt->close();
    return $conflicts;
}

function find_booking_conflicts(
    int $labId,
    string $tanggal,
    string $jamMulai,
    string $jamSelesai,
    ?int $excludeId = null
): array {
    global $conn;

    $conflicts = [];

    // Cek bentrok dengan peminjaman lain
    $sql = "SELECT * FROM lab_bookings
            WHERE lab_id = ?
              AND tanggal = ?
              AND status IN ('pending','approved')";

    $params = [$labId, $tanggal];
    $types  = 'is';

    if ($excludeId !== null) {
        $sql     .= ' AND id <> ?';
        $params[] = $excludeId;
        $types   .= 'i';
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (waktu_overlap($row['jam_mulai'], $row['jam_selesai'], $jamMulai, $jamSelesai)) {
                $row['conflict_type'] = 'booking';
                $conflicts[]          = $row;
            }
        }
        $stmt->close();
    }

    // Cek bentrok dengan jadwal praktikum pada hari yang sama
    $hariIndex = (int) date('N', strtotime($tanggal));
    $hariMap   = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $hariName  = $hariMap[$hariIndex] ?? null;

    if ($hariName) {
        $sql = "SELECT ps.*, l.nama_lab, u.nama AS dosen_nama
                FROM praktikum_schedules ps
                JOIN labs l ON l.id = ps.lab_id
                JOIN users u ON u.id = ps.dosen_id
                WHERE ps.status = 'aktif'
                  AND ps.hari = ?
                  AND ps.periode_mulai <= ?
                  AND ps.periode_selesai >= ?";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sss', $hariName, $tanggal, $tanggal);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if (waktu_overlap($row['jam_mulai'], $row['jam_selesai'], $jamMulai, $jamSelesai)) {
                    $row['conflict_type'] = 'praktikum';
                    $conflicts[]          = $row;
                }
            }
            $stmt->close();
        }
    }

    return $conflicts;
}

function unread_notification_count(int $userId): int
{
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) AS jml FROM notifications WHERE user_id = ? AND is_read = 0');
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['jml'] ?? 0);
}
