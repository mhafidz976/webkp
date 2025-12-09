<?php
require_once __DIR__ . '/auth.php';
require_login();
require_role(['admin']);

$id          = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$nama_lab    = '';
$kapasitas   = '';
$spesifikasi = '';
$lokasi      = '';
$status_lab  = 'available';
$errors      = [];

if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM labs WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $nama_lab    = $row['nama_lab'];
            $kapasitas   = $row['kapasitas'];
            $spesifikasi = $row['spesifikasi'];
            $lokasi      = $row['lokasi'];
            $status_lab  = $row['status_lab'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lab    = trim($_POST['nama_lab'] ?? '');
    $kapasitas   = (int)($_POST['kapasitas'] ?? 0);
    $spesifikasi = trim($_POST['spesifikasi'] ?? '');
    $lokasi      = trim($_POST['lokasi'] ?? '');
    $status_lab  = $_POST['status_lab'] ?? 'available';

    if ($nama_lab === '') {
        $errors[] = 'Nama lab wajib diisi.';
    }

    if (empty($errors)) {
        if ($id > 0) {
            $sql  = 'UPDATE labs SET nama_lab = ?, kapasitas = ?, spesifikasi = ?, lokasi = ?, status_lab = ? WHERE id = ?';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sisssi', $nama_lab, $kapasitas, $spesifikasi, $lokasi, $status_lab, $id);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $sql  = 'INSERT INTO labs (nama_lab, kapasitas, spesifikasi, lokasi, status_lab) VALUES (?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sisss', $nama_lab, $kapasitas, $spesifikasi, $lokasi, $status_lab);
                $stmt->execute();
                $stmt->close();
            }
        }

        header('Location: labs_index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id > 0 ? 'Edit' : 'Tambah'; ?> Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>
<div class="container py-4">
    <h3 class="mb-3"><?php echo $id > 0 ? 'Edit' : 'Tambah'; ?> Laboratorium</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Nama Lab</label>
                    <input type="text" name="nama_lab" class="form-control" value="<?php echo htmlspecialchars($nama_lab); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kapasitas Komputer</label>
                    <input type="number" name="kapasitas" class="form-control" value="<?php echo htmlspecialchars((string)$kapasitas); ?>" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Spesifikasi (Software / OS)</label>
                    <textarea name="spesifikasi" class="form-control" rows="3"><?php echo htmlspecialchars($spesifikasi); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="lokasi" class="form-control" value="<?php echo htmlspecialchars($lokasi); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status Lab</label>
                    <select name="status_lab" class="form-select">
                        <option value="available" <?php echo $status_lab === 'available' ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="in_use" <?php echo $status_lab === 'in_use' ? 'selected' : ''; ?>>Digunakan</option>
                        <option value="maintenance" <?php echo $status_lab === 'maintenance' ? 'selected' : ''; ?>>Maintenance / Rusak</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $id > 0 ? 'Simpan Perubahan' : 'Simpan'; ?></button>
                <a href="labs_index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
