<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();
$role = $user['role_key'] ?? '';

if (!in_array($role, ['admin','dosen','mahasiswa'], true)) {
    http_response_code(403);
    echo 'Tidak diizinkan mengajukan peminjaman.';
    exit;
}

$id           = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$labId        = 0;
$jenis        = 'Workshop';
$keperluan    = '';
$tanggal      = date('Y-m-d');
$jamMulai     = '08:00';
$jamSelesai   = '10:00';
$catatan      = '';
$errors       = [];

$labs = get_labs();

if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM lab_bookings WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($role !== 'admin' && (int)$row['peminjam_id'] !== (int)$user['id']) {
                http_response_code(403);
                echo 'Tidak diizinkan mengedit peminjaman ini.';
                exit;
            }
            $labId      = $row['lab_id'];
            $jenis      = $row['jenis'];
            $keperluan  = $row['keperluan'];
            $tanggal    = $row['tanggal'];
            $jamMulai   = $row['jam_mulai'];
            $jamSelesai = $row['jam_selesai'];
            $catatan    = $row['catatan'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $labId      = (int)($_POST['lab_id'] ?? 0);
    $jenis      = trim($_POST['jenis'] ?? '');
    $keperluan  = trim($_POST['keperluan'] ?? '');
    $tanggal    = $_POST['tanggal'] ?? '';
    $jamMulai   = $_POST['jam_mulai'] ?? '';
    $jamSelesai = $_POST['jam_selesai'] ?? '';
    $catatan    = trim($_POST['catatan'] ?? '');

    if ($labId <= 0 || $jenis === '' || $keperluan === '') {
        $errors[] = 'Lab, jenis, dan keperluan wajib diisi.';
    }
    if ($tanggal === '' || $jamMulai === '' || $jamSelesai === '') {
        $errors[] = 'Tanggal dan jam wajib diisi.';
    }

    if (empty($errors)) {
        $conf = find_booking_conflicts($labId, $tanggal, $jamMulai, $jamSelesai, $id > 0 ? $id : null);
        if (!empty($conf)) {
            foreach ($conf as $c) {
                if (($c['conflict_type'] ?? '') === 'booking') {
                    $errors[] = 'Bentrok dengan peminjaman lain di lab ini pada ' . $c['tanggal'] . ' ' .
                        substr($c['jam_mulai'],0,5) . '-' . substr($c['jam_selesai'],0,5) . ' (status: ' . $c['status'] . ')';
                } else {
                    $errors[] = 'Bentrok dengan jadwal praktikum: ' . $c['mata_kuliah'] . ' (' . $c['kelas'] . ')';
                }
            }
        }
    }

    if (empty($errors)) {
        if ($id > 0) {
            $sql  = 'UPDATE lab_bookings
                     SET lab_id = ?, jenis = ?, keperluan = ?, tanggal = ?, jam_mulai = ?, jam_selesai = ?, catatan = ?
                     WHERE id = ?';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('issssssi', $labId, $jenis, $keperluan, $tanggal, $jamMulai, $jamSelesai, $catatan, $id);
                $stmt->execute();
                $stmt->close();
            }
            log_activity('update_booking', 'Update booking ID ' . $id);
        } else {
            $sql  = 'INSERT INTO lab_bookings
                     (lab_id, peminjam_id, jenis, keperluan, tanggal, jam_mulai, jam_selesai, catatan)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $peminjamId = $user['id'];
                $stmt->bind_param('iissssss', $labId, $peminjamId, $jenis, $keperluan, $tanggal, $jamMulai, $jamSelesai, $catatan);
                $stmt->execute();
                $stmt->close();
            }
            log_activity('create_booking', 'Ajukan peminjaman lab ' . $keperluan . ' pada ' . $tanggal);
        }

        header('Location: bookings_index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id > 0 ? 'Edit' : 'Ajukan'; ?> Peminjaman Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/partials_sidebar.php'; ?>

<main class="main-content">
    <div class="container">
    <h3 class="mb-3"><?php echo $id > 0 ? 'Edit' : 'Ajukan'; ?> Peminjaman Laboratorium</h3>

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
                <div class="row g-3">
                    <div class="col-md-6">
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
                    <div class="col-md-6">
                        <label class="form-label">Jenis Peminjaman</label>
                        <select name="jenis" class="form-select" required>
                            <?php $jenisOpts = ['Workshop','Ujian','Penelitian','Lainnya'];
                            foreach ($jenisOpts as $j): ?>
                                <option value="<?php echo $j; ?>" <?php echo $jenis === $j ? 'selected' : ''; ?>><?php echo $j; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Keperluan</label>
                        <input type="text" name="keperluan" class="form-control" value="<?php echo htmlspecialchars($keperluan); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?php echo htmlspecialchars($tanggal); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" name="jam_mulai" class="form-control" value="<?php echo htmlspecialchars(substr($jamMulai,0,5)); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" name="jam_selesai" class="form-control" value="<?php echo htmlspecialchars(substr($jamSelesai,0,5)); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="catatan" class="form-control" rows="2"><?php echo htmlspecialchars($catatan); ?></textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><?php echo $id > 0 ? 'Simpan Perubahan' : 'Ajukan'; ?></button>
                    <a href="bookings_index.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
