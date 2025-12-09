<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();
require_role(['admin']);

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');

// Laporan penggunaan lab (praktikum)
$stmt = $conn->prepare('SELECT l.nama_lab, COUNT(*) AS jml
                         FROM praktikum_schedules ps
                         JOIN labs l ON l.id = ps.lab_id
                         WHERE ps.periode_mulai <= ? AND ps.periode_selesai >= ?
                         GROUP BY l.id, l.nama_lab');
$praktikum = [];
if ($stmt) {
    $stmt->bind_param('ss', $end, $start);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $praktikum[] = $row;
    }
    $stmt->close();
}

// Laporan peminjaman lab
$stmt = $conn->prepare('SELECT l.nama_lab, COUNT(*) AS jml
                         FROM lab_bookings b
                         JOIN labs l ON l.id = b.lab_id
                         WHERE b.tanggal BETWEEN ? AND ?
                         GROUP BY l.id, l.nama_lab');
$booking = [];
if ($stmt) {
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $booking[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penggunaan Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>
<div class="container py-4">
    <h3 class="mb-3">Laporan & Rekapitulasi</h3>

    <form class="row g-3 mb-3" method="get">
        <div class="col-md-4">
            <label class="form-label">Periode Mulai</label>
            <input type="date" name="start" class="form-control" value="<?php echo htmlspecialchars($start); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Periode Selesai</label>
            <input type="date" name="end" class="form-control" value="<?php echo htmlspecialchars($end); ?>" required>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Rekap Jadwal Praktikum per Lab</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                        <tr>
                            <th>Lab</th>
                            <th>Jumlah Jadwal</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($praktikum)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Tidak ada data.</td></tr>
                        <?php else: ?>
                            <?php foreach ($praktikum as $p): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['nama_lab']); ?></td>
                                    <td><?php echo (int)$p['jml']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Rekap Peminjaman Lab per Lab</div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                        <tr>
                            <th>Lab</th>
                            <th>Jumlah Peminjaman</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($booking)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Tidak ada data.</td></tr>
                        <?php else: ?>
                            <?php foreach ($booking as $b): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($b['nama_lab']); ?></td>
                                    <td><?php echo (int)$b['jml']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
