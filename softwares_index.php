<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();
require_role(['admin','teknisi']);

$sql = "SELECT s.*, l.nama_lab
        FROM lab_softwares s
        JOIN labs l ON l.id = s.lab_id
        ORDER BY l.nama_lab, s.nama_software";
$result = $conn->query($sql);
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
    <title>Software Laboratorium</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Software Laboratorium</h3>
        <a href="softwares_form.php" class="btn btn-primary btn-sm">+ Tambah Software</a>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Lab</th>
                    <th>Software</th>
                    <th>Versi</th>
                    <th>Jadwal Update</th>
                    <th>Kebutuhan MK</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Belum ada data software.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($r['nama_lab']); ?></td>
                            <td><?php echo htmlspecialchars($r['nama_software']); ?></td>
                            <td><?php echo htmlspecialchars($r['versi']); ?></td>
                            <td><?php echo htmlspecialchars($r['jadwal_update'] ?? '-'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($r['kebutuhan_mk'])); ?></td>
                            <td>
                                <a href="softwares_form.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
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
