<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();
$role = $user['role_key'] ?? '';

$id          = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$labId       = 0;
$judul       = '';
$deskripsi   = '';
$status      = 'open';
$teknisiId   = null;
$errors      = [];

$labs        = get_labs();
$teknisiList = get_users_by_roles(['teknisi']);

if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM tickets WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!in_array($role, ['admin','teknisi'], true) && (int)$row['pelapor_id'] !== (int)$user['id']) {
                http_response_code(403);
                echo 'Tidak diizinkan mengakses tiket ini.';
                exit;
            }
            $labId     = $row['lab_id'];
            $judul     = $row['judul'];
            $deskripsi = $row['deskripsi'];
            $status    = $row['status'];
            $teknisiId = $row['teknisi_id'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $labId     = (int)($_POST['lab_id'] ?? 0);
    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status    = $_POST['status'] ?? 'open';
    $teknisiId = isset($_POST['teknisi_id']) && $_POST['teknisi_id'] !== '' ? (int)$_POST['teknisi_id'] : null;

    if ($labId <= 0 || $judul === '' || $deskripsi === '') {
        $errors[] = 'Lab, judul, dan deskripsi wajib diisi.';
    }

    if (empty($errors)) {
        if ($id > 0) {
            $sql = 'UPDATE tickets SET lab_id = ?, judul = ?, deskripsi = ?, status = ?, teknisi_id = ? WHERE id = ?';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('isssii', $labId, $judul, $deskripsi, $status, $teknisiId, $id);
                $stmt->execute();
                $stmt->close();
            }
            log_activity('update_ticket', 'Update tiket ID ' . $id);
        } else {
            $sql = 'INSERT INTO tickets (lab_id, pelapor_id, judul, deskripsi, status, teknisi_id)
                    VALUES (?, ?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $pelaporId = $user['id'];
                $stmt->bind_param('iisssi', $labId, $pelaporId, $judul, $deskripsi, $status, $teknisiId);
                $stmt->execute();
                $stmt->close();
            }
            log_activity('create_ticket', 'Buat tiket kerusakan ' . $judul);
        }

        header('Location: tickets_index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id > 0 ? 'Edit' : 'Tambah'; ?> Tiket Kerusakan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/partials_sidebar.php'; ?>

<main class="main-content">
    <div class="container">
    <h3 class="mb-3"><?php echo $id > 0 ? 'Edit' : 'Tambah'; ?> Tiket Kerusakan</h3>

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
                    <label class="form-label">Lab</label>
                    <select name="lab_id" class="form-select" required>
                        <option value="">-- Pilih Lab --</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?php echo $lab['id']; ?>" <?php echo (int)$labId === (int)$lab['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lab['nama_lab']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                <div class="mb-3">
                    <label class="form-label">Judul Kerusakan</label>
                    <input type="text" name="judul" class="form-control" value="<?php echo htmlspecialchars($judul); ?>" required>
                    </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="4" required><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                    </div>
                <?php if (in_array($role, ['admin','teknisi'], true)): ?>
                    <div class="mb-3">
                        <label class="form-label">Teknisi Penanggung Jawab</label>
                        <select name="teknisi_id" class="form-select">
                            <option value="">-- Belum ditentukan --</option>
                            <?php foreach ($teknisiList as $tk): ?>
                                <option value="<?php echo $tk['id']; ?>" <?php echo (int)$teknisiId === (int)$tk['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tk['nama']); ?> (<?php echo htmlspecialchars($tk['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?php echo $id > 0 ? 'Simpan Perubahan' : 'Simpan'; ?></button>
                <a href="tickets_index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
            </div>
    </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
