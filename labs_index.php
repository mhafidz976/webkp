<?php
require_once __DIR__ . '/auth.php';
require_login();
require_role(['admin','teknisi']);

$labs = [];
$sql  = 'SELECT * FROM labs ORDER BY nama_lab';
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $labs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Laboratorium</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/partials_sidebar.php'; ?>

<main class="main-content">
    <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Data Laboratorium</h3>
        <a href="labs_form.php" class="btn btn-primary btn-sm">+ Tambah Lab</a>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama Lab</th>
                    <th>Kapasitas</th>
                    <th>Spesifikasi</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($labs)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Belum ada data lab.</td></tr>
                <?php else: ?>
                    <?php foreach ($labs as $i => $lab): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($lab['nama_lab']); ?></td>
                            <td><?php echo htmlspecialchars($lab['kapasitas']); ?></td>
                            <td><?php echo htmlspecialchars($lab['spesifikasi']); ?></td>
                            <td><?php echo htmlspecialchars($lab['lokasi']); ?></td>
                            <td><?php echo htmlspecialchars($lab['status_lab']); ?></td>
                            <td>
                                <a href="labs_form.php?id=<?php echo $lab['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
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
