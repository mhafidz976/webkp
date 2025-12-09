<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();
require_role(['admin','teknisi']);

$id            = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$labId         = 0;
$namaSoftware  = '';
$versi         = '';
$jadwalUpdate  = '';
$kebutuhanMk   = '';
$errors        = [];

$labs = get_labs();

if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM lab_softwares WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $labId        = $row['lab_id'];
            $namaSoftware = $row['nama_software'];
            $versi        = $row['versi'];
            $jadwalUpdate = $row['jadwal_update'];
            $kebutuhanMk  = $row['kebutuhan_mk'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $labId        = (int)($_POST['lab_id'] ?? 0);
    $namaSoftware = trim($_POST['nama_software'] ?? '');
    $versi        = trim($_POST['versi'] ?? '');
    $jadwalUpdate = $_POST['jadwal_update'] ?? '';
    $kebutuhanMk  = trim($_POST['kebutuhan_mk'] ?? '');

    if ($labId <= 0 || $namaSoftware === '') {
        $errors[] = 'Lab dan nama software wajib diisi.';
    }

    if (empty($errors)) {
        if ($id > 0) {
            $sql  = 'UPDATE lab_softwares
                     SET lab_id = ?, nama_software = ?, versi = ?, jadwal_update = ?, kebutuhan_mk = ?
                     WHERE id = ?';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('issssi', $labId, $namaSoftware, $versi, $jadwalUpdate, $kebutuhanMk, $id);
                $stmt->execute();
                $stmt->close();
            }
            log_activity('update_software', 'Update software ID ' . $id);
        } else {
            $sql  = 'INSERT INTO lab_softwares (lab_id, nama_software, versi, jadwal_update, kebutuhan_mk)
                     VALUES (?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('issss', $labId, $namaSoftware, $versi, $jadwalUpdate, $kebutuhanMk);
                $stmt->execute();
                $stmt->close();
            }
            log_activity('create_software', 'Tambah software ' . $namaSoftware);
        }

        header('Location: softwares_index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id > 0 ? 'Edit' : 'Tambah'; ?> Software Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>
<div class="container py-4">
    <h3 class="mb-3"><?php echo $id > 0 ? 'Edit' : 'Tambah'; ?> Software Laboratorium</h3>

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
                    <label class="form-label">Nama Software</label>
                    <input type="text" name="nama_software" class="form-control" value="<?php echo htmlspecialchars($namaSoftware); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Versi</label>
                    <input type="text" name="versi" class="form-control" value="<?php echo htmlspecialchars($versi); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jadwal Instalasi / Update</label>
                    <input type="date" name="jadwal_update" class="form-control" value="<?php echo htmlspecialchars($jadwalUpdate); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kebutuhan Mata Kuliah</label>
                    <textarea name="kebutuhan_mk" class="form-control" rows="3"><?php echo htmlspecialchars($kebutuhanMk); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $id > 0 ? 'Simpan Perubahan' : 'Simpan'; ?></button>
                <a href="softwares_index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
