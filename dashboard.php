<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();

// Statistik ringkas
$labsCount       = (int) ($conn->query('SELECT COUNT(*) AS c FROM labs')->fetch_assoc()['c'] ?? 0);
$praktikumCount  = (int) ($conn->query('SELECT COUNT(*) AS c FROM praktikum_schedules')->fetch_assoc()['c'] ?? 0);
$bookingCount    = (int) ($conn->query('SELECT COUNT(*) AS c FROM lab_bookings')->fetch_assoc()['c'] ?? 0);
$openTickets     = (int) ($conn->query("SELECT COUNT(*) AS c FROM tickets WHERE status <> 'closed'")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Penjadwalan Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>

<div class="container py-4">
    <h3 class="mb-3">Dashboard</h3>
    <p class="lead">Selamat datang di sistem penjadwalan & manajemen laboratorium kampus.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-bg-primary h-100">
                <div class="card-body">
                    <div class="fw-bold">Total Lab</div>
                    <div class="display-6"><?php echo $labsCount; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-success h-100">
                <div class="card-body">
                    <div class="fw-bold">Jadwal Praktikum</div>
                    <div class="display-6"><?php echo $praktikumCount; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-warning h-100">
                <div class="card-body">
                    <div class="fw-bold">Peminjaman Lab</div>
                    <div class="display-6"><?php echo $bookingCount; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-bg-danger h-100">
                <div class="card-body">
                    <div class="fw-bold">Tiket Aktif</div>
                    <div class="display-6"><?php echo $openTickets; ?></div>
                </div>
            </div>
        </div>
    </div>

    <p class="mb-0">Gunakan menu di atas untuk mengelola jadwal praktikum, peminjaman lab, data laboratorium, software, tiket kerusakan, dan laporan.</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
