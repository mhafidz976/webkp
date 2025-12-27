<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();
$role = $user['role_key'] ?? '';

$where  = '';
$params = [];
$types  = '';

if (!in_array($role, ['admin','teknisi'], true)) {
    $where  = 'WHERE t.pelapor_id = ?';
    $params = [$user['id']];
    $types  = 'i';
}

$sql = "SELECT t.*, l.nama_lab, u.nama AS pelapor_nama, ut.nama AS teknisi_nama
        FROM tickets t
        JOIN labs l ON l.id = t.lab_id
        JOIN users u ON u.id = t.pelapor_id
        LEFT JOIN users ut ON ut.id = t.teknisi_id
        $where
        ORDER BY t.created_at DESC";

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tiket Kerusakan Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/partials_sidebar.php'; ?>

<main class="main-content">
    <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Tiket Kerusakan / Incident</h3>
        <a href="tickets_form.php" class="btn btn-primary btn-sm">+ Laporkan Kerusakan</a>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Lab</th>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Pelapor</th>
                    <th>Teknisi</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted">Belum ada tiket kerusakan.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $t): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($t['nama_lab']); ?></td>
                            <td><?php echo htmlspecialchars($t['judul']); ?></td>
                            <td><span class="badge bg-<?php echo $t['status'] === 'closed' ? 'success' : ($t['status'] === 'in_progress' ? 'warning' : 'danger'); ?>"><?php echo htmlspecialchars($t['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($t['pelapor_nama']); ?></td>
                            <td><?php echo htmlspecialchars($t['teknisi_nama'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($t['created_at']); ?></td>
                            <td>
                                <a href="tickets_form.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary">Detail / Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
