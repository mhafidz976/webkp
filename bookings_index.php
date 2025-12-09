<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();
$role = $user['role_key'] ?? '';

$where  = '';
$params = [];
$types  = '';

if (in_array($role, ['dosen','mahasiswa'], true)) {
    $where  = 'WHERE b.peminjam_id = ?';
    $params = [$user['id']];
    $types  = 'i';
}

$sql = "SELECT b.*, l.nama_lab, u.nama AS peminjam_nama, ua.nama AS approver_nama
        FROM lab_bookings b
        JOIN labs l ON l.id = b.lab_id
        JOIN users u ON u.id = b.peminjam_id
        LEFT JOIN users ua ON ua.id = b.approved_by
        $where
        ORDER BY b.tanggal DESC, b.jam_mulai DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = false;
    }
} else {
    $result = $conn->query($sql);
}

$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

// Proses approve / reject
if ($role === 'admin' && isset($_GET['action'], $_GET['id'])) {
    $id     = (int) $_GET['id'];
    $action = $_GET['action'] === 'approve' ? 'approved' : 'rejected';

    // Ambil peminjam untuk notifikasi
    $peminjamId = null;
    $stmtSel    = $conn->prepare('SELECT peminjam_id FROM lab_bookings WHERE id = ?');
    if ($stmtSel) {
        $stmtSel->bind_param('i', $id);
        $stmtSel->execute();
        $resSel = $stmtSel->get_result();
        if ($rowSel = $resSel->fetch_assoc()) {
            $peminjamId = (int) $rowSel['peminjam_id'];
        }
        $stmtSel->close();
    }

    $stmt = $conn->prepare('UPDATE lab_bookings SET status = ?, approved_by = ? WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('sii', $action, $user['id'], $id);
        $stmt->execute();
        $stmt->close();
        log_activity('update_booking_status', 'Booking ID ' . $id . ' => ' . $action);

        if ($peminjamId) {
            $pesan = $action === 'approved'
                ? 'Peminjaman laboratorium Anda telah disetujui (ID: ' . $id . ').'
                : 'Peminjaman laboratorium Anda ditolak (ID: ' . $id . ').';
            create_notification($peminjamId, 'booking', $pesan);
        }
    }

    header('Location: bookings_index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Peminjaman Laboratorium</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Peminjaman Laboratorium (Non-Praktikum)</h3>
        <?php if (in_array($role, ['admin','dosen','mahasiswa'], true)): ?>
            <a href="bookings_form.php" class="btn btn-primary btn-sm">+ Ajukan Peminjaman</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Lab</th>
                    <th>Jenis</th>
                    <th>Keperluan</th>
                    <th>Peminjam</th>
                    <th>Status</th>
                    <th>Disetujui Oleh</th>
                    <?php if ($role === 'admin'): ?><th>Aksi</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-center text-muted">Belum ada peminjaman.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($r['tanggal']); ?></td>
                            <td><?php echo htmlspecialchars(substr($r['jam_mulai'],0,5) . ' - ' . substr($r['jam_selesai'],0,5)); ?></td>
                            <td><?php echo htmlspecialchars($r['nama_lab']); ?></td>
                            <td><?php echo htmlspecialchars($r['jenis']); ?></td>
                            <td><?php echo htmlspecialchars($r['keperluan']); ?></td>
                            <td><?php echo htmlspecialchars($r['peminjam_nama']); ?></td>
                            <td><span class="badge bg-<?php echo $r['status'] === 'approved' ? 'success' : ($r['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($r['approver_nama'] ?? '-'); ?></td>
                            <?php if ($role === 'admin'): ?>
                                <td class="d-flex gap-1">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <a href="bookings_index.php?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                        <a href="bookings_index.php?action=reject&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
