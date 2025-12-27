<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();
$role = $user['role_key'] ?? '';

// Statistik ringkas
$labsCount       = (int) ($conn->query('SELECT COUNT(*) AS c FROM labs')->fetch_assoc()['c'] ?? 0);
$praktikumCount  = (int) ($conn->query('SELECT COUNT(*) AS c FROM praktikum_schedules')->fetch_assoc()['c'] ?? 0);
$bookingCount    = (int) ($conn->query('SELECT COUNT(*) AS c FROM lab_bookings')->fetch_assoc()['c'] ?? 0);
$openTickets     = (int) ($conn->query("SELECT COUNT(*) AS c FROM tickets WHERE status <> 'closed'")->fetch_assoc()['c'] ?? 0);

// Data interaktif tambahan
$recentBookings  = [];
$recentTickets   = [];
$todaySchedules  = [];
$labStatus       = [];

if (in_array($role, ['admin','teknisi'], true)) {
    // Recent bookings (pending)
    $stmt = $conn->prepare('SELECT b.*, l.nama_lab, u.nama AS peminjam_nama 
                           FROM lab_bookings b 
                           JOIN labs l ON l.id = b.lab_id 
                           JOIN users u ON u.id = b.peminjam_id 
                           WHERE b.status = "pending" 
                           ORDER BY b.created_at DESC LIMIT 5');
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentBookings[] = $row;
        }
        $stmt->close();
    }
    
    // Recent tickets (open)
    $stmt = $conn->prepare('SELECT t.*, l.nama_lab, u.nama AS pelapor_nama 
                           FROM tickets t 
                           JOIN labs l ON l.id = t.lab_id 
                           JOIN users u ON u.id = t.pelapor_id 
                           WHERE t.status = "open" 
                           ORDER BY t.created_at DESC LIMIT 5');
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recentTickets[] = $row;
        }
        $stmt->close();
    }
    
    // Lab status summary
    $result = $conn->query('SELECT status_lab, COUNT(*) as count FROM labs GROUP BY status_lab');
    while ($row = $result->fetch_assoc()) {
        $labStatus[$row['status_lab']] = $row['count'];
    }
}

// Today's schedules for all users
$today = date('Y-m-d');
$hariIni = date('l');
$hariMap = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
$hariIndo = $hariMap[$hariIni] ?? 'Senin';

$stmt = $conn->prepare('SELECT ps.*, l.nama_lab, u.nama AS dosen_nama 
                       FROM praktikum_schedules ps 
                       JOIN labs l ON l.id = ps.lab_id 
                       JOIN users u ON u.id = ps.dosen_id 
                       WHERE ps.hari = ? AND ps.status = "aktif" 
                       AND ps.periode_mulai <= ? AND ps.periode_selesai >= ?
                       ORDER BY ps.jam_mulai ASC LIMIT 10');
if ($stmt) {
    $stmt->bind_param('sss', $hariIndo, $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $todaySchedules[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Penjadwalan Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/partials_sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Dashboard</h3>
                <p class="text-muted mb-0">Selamat datang, <strong><?php echo htmlspecialchars($user['nama']); ?></strong>!</p>
            </div>
            <div class="text-end">
                <small class="text-muted"><?php echo date('d M Y, H:i'); ?></small>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 bg-gradient-primary text-white h-100 dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold opacity-50">Total Lab</div>
                                <div class="display-6 fw-bold"><?php echo $labsCount; ?></div>
                                <small class="opacity-50">
                                    <?php echo ($labStatus['available'] ?? 0); ?> tersedia
                                </small>
                            </div>
                            <div class="opacity-25">
                                <i class="bi bi-building fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-gradient-success text-white h-100 dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold opacity-50">Jadwal Praktikum</div>
                                <div class="display-6 fw-bold"><?php echo $praktikumCount; ?></div>
                                <small class="opacity-50"><?php echo count($todaySchedules); ?> hari ini</small>
                            </div>
                            <div class="opacity-25">
                                <i class="bi bi-calendar-check fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-gradient-warning text-white h-100 dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold opacity-50">Peminjaman Lab</div>
                                <div class="display-6 fw-bold"><?php echo $bookingCount; ?></div>
                                <small class="opacity-50"><?php echo count($recentBookings); ?> pending</small>
                            </div>
                            <div class="opacity-25">
                                <i class="bi bi-key fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-gradient-danger text-white h-100 dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold opacity-50">Tiket Aktif</div>
                                <div class="display-6 fw-bold"><?php echo $openTickets; ?></div>
                                <small class="opacity-50">perlu ditangani</small>
                            </div>
                            <div class="opacity-25">
                                <i class="bi bi-exclamation-triangle fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Schedule -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-calendar3 text-primary me-2"></i>
                                Jadwal Hari Ini (<?php echo $hariIndo; ?>)
                            </h6>
                            <span class="badge bg-primary rounded-pill"><?php echo count($todaySchedules); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todaySchedules)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x fs-1 mb-2"></i>
                                <p class="mb-0">Tidak ada jadwal praktikum hari ini</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Lab</th>
                                            <th>Mata Kuliah</th>
                                            <th>Dosen</th>
                                            <th>Kelas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todaySchedules as $schedule): ?>
                                            <tr class="schedule-row">
                                                <td>
                                                    <span class="badge bg-success rounded-pill">
                                                        <?php echo htmlspecialchars($schedule['jam_mulai']); ?> - <?php echo htmlspecialchars($schedule['jam_selesai']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($schedule['nama_lab']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($schedule['mata_kuliah']); ?></td>
                                                <td><?php echo htmlspecialchars($schedule['dosen_nama']); ?></td>
                                                <td><?php echo htmlspecialchars($schedule['kelas']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-lightning-charge text-warning me-2"></i>
                            Aksi Cepat
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (in_array($role, ['admin','dosen'], true)): ?>
                                <a href="praktikum_form.php" class="btn btn-outline-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Jadwal Praktikum
                                </a>
                            <?php endif; ?>
                            <a href="bookings_form.php" class="btn btn-outline-warning">
                                <i class="bi bi-key me-2"></i>Ajukan Peminjaman Lab
                            </a>
                            <?php if (in_array($role, ['admin','teknisi'], true)): ?>
                                <a href="tickets_form.php" class="btn btn-outline-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Laporkan Kerusakan
                                </a>
                            <?php endif; ?>
                            <a href="calendar.php" class="btn btn-outline-info">
                                <i class="bi bi-calendar-week me-2"></i>Lihat Kalender Lab
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin/Teknisi Section -->
        <?php if (in_array($role, ['admin','teknisi'], true)): ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-clock-history text-warning me-2"></i>
                                Peminjaman Pending
                            </h6>
                            <span class="badge bg-warning rounded-pill"><?php echo count($recentBookings); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentBookings)): ?>
                            <p class="text-muted mb-0">Tidak ada peminjaman yang pending</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentBookings as $booking): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($booking['nama_lab']); ?></strong>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($booking['keperluan']); ?> - 
                                                    <?php echo htmlspecialchars($booking['peminjam_nama']); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($booking['tanggal']); ?> 
                                                    <?php echo htmlspecialchars($booking['jam_mulai']); ?>-<?php echo htmlspecialchars($booking['jam_selesai']); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <a href="bookings_index.php" class="btn btn-sm btn-outline-primary">Lihat</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-tools text-danger me-2"></i>
                                Tiket Terbaru
                            </h6>
                            <span class="badge bg-danger rounded-pill"><?php echo count($recentTickets); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentTickets)): ?>
                            <p class="text-muted mb-0">Tidak ada tiket baru</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($ticket['judul']); ?></strong>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($ticket['nama_lab']); ?> - 
                                                    <?php echo htmlspecialchars($ticket['pelapor_nama']); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <span class="badge bg-danger">Open</span>
                                                </div>
                                            </div>
                                            <div>
                                                <a href="tickets_index.php" class="btn btn-sm btn-outline-primary">Lihat</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
