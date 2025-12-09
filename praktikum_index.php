<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();
$role = $user['role_key'] ?? '';

$where  = '';
$params = [];
$types  = '';

if ($role === 'dosen') {
    $where   = 'WHERE ps.dosen_id = ?';
    $params  = [$user['id']];
    $types   = 'i';
}

$sql = "SELECT ps.*, l.nama_lab, u.nama AS dosen_nama
        FROM praktikum_schedules ps
        JOIN labs l ON l.id = ps.lab_id
        JOIN users u ON u.id = ps.dosen_id
        $where
        ORDER BY ps.hari, ps.jam_mulai";

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
    <title>Jadwal Praktikum</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Jadwal Praktikum</h3>
        <?php if (in_array($role, ['admin','dosen'], true)): ?>
            <a href="praktikum_form.php" class="btn btn-primary btn-sm">+ Ajukan / Tambah Jadwal</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Mata Kuliah</th>
                    <th>Dosen</th>
                    <th>Kelas</th>
                    <th>Lab</th>
                    <th>Hari</th>
                    <th>Jam</th>
                    <th>Periode</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <?php if (in_array($role, ['admin','dosen'], true)): ?><th>Aksi</th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="11" class="text-center text-muted">Belum ada jadwal praktikum.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($r['mata_kuliah']); ?></td>
                            <td><?php echo htmlspecialchars($r['dosen_nama']); ?></td>
                            <td><?php echo htmlspecialchars($r['kelas']); ?></td>
                            <td><?php echo htmlspecialchars($r['nama_lab']); ?></td>
                            <td><?php echo htmlspecialchars($r['hari']); ?></td>
                            <td><?php echo htmlspecialchars(substr($r['jam_mulai'],0,5) . ' - ' . substr($r['jam_selesai'],0,5)); ?></td>
                            <td><?php echo htmlspecialchars($r['periode_mulai'] . ' s/d ' . $r['periode_selesai']); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($r['tipe']); ?></span></td>
                            <td><span class="badge bg-<?php echo $r['status'] === 'aktif' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                            <?php if (in_array($role, ['admin','dosen'], true)): ?>
                                <td>
                                    <a href="praktikum_form.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
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
