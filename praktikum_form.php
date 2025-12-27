<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();
require_role(['admin','dosen']);

$user = current_user();
$role = $user['role_key'] ?? '';

$id            = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$labId         = 0;
$dosenId       = $user['id'];
$mataKuliah    = '';
$kelas         = '';
$hari          = 'Senin';
$jamMulai      = '08:00';
$jamSelesai    = '10:00';
$periodeMulai  = date('Y-m-01');
$periodeSelesai= date('Y-m-t');
$tipe          = 'mingguan';
$status        = 'aktif';
$errors        = [];

$labs = get_labs();

if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM praktikum_schedules WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Hanya admin atau dosen pemilik yang boleh edit
            if ($role !== 'admin' && (int)$row['dosen_id'] !== (int)$user['id']) {
                http_response_code(403);
                echo 'Tidak diizinkan mengedit jadwal ini.';
                exit;
            }
            $labId          = $row['lab_id'];
            $dosenId        = $row['dosen_id'];
            $mataKuliah     = $row['mata_kuliah'];
            $kelas          = $row['kelas'];
            $hari           = $row['hari'];
            $jamMulai       = $row['jam_mulai'];
            $jamSelesai     = $row['jam_selesai'];
            $periodeMulai   = $row['periode_mulai'];
            $periodeSelesai = $row['periode_selesai'];
            $tipe           = $row['tipe'];
            $status         = $row['status'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $labId          = (int)($_POST['lab_id'] ?? 0);
    $dosenId        = (int)($_POST['dosen_id'] ?? $user['id']);
    $mataKuliah     = trim($_POST['mata_kuliah'] ?? '');
    $kelas          = trim($_POST['kelas'] ?? '');
    $hari           = $_POST['hari'] ?? 'Senin';
    $jamMulai       = $_POST['jam_mulai'] ?? '';
    $jamSelesai     = $_POST['jam_selesai'] ?? '';
    $periodeMulai   = $_POST['periode_mulai'] ?? '';
    $periodeSelesai = $_POST['periode_selesai'] ?? '';
    $tipe           = $_POST['tipe'] ?? 'mingguan';
    $status         = $_POST['status'] ?? 'aktif';

    if ($labId <= 0 || $dosenId <= 0 || $mataKuliah === '' || $kelas === '') {
        $errors[] = 'Lab, dosen, mata kuliah, dan kelas wajib diisi.';
    }
    if ($jamMulai === '' || $jamSelesai === '') {
        $errors[] = 'Jam mulai dan selesai wajib diisi.';
    }
    if ($periodeMulai === '' || $periodeSelesai === '') {
        $errors[] = 'Periode mulai dan selesai wajib diisi.';
    }

    if (empty($errors)) {
        // Cek bentrok jadwal lab/dosen/kelas
        $conflicts = find_praktikum_conflicts(
            $labId,
            $dosenId,
            $kelas,
            $hari,
            $jamMulai,
            $jamSelesai,
            $periodeMulai,
            $periodeSelesai,
            $id > 0 ? $id : null
        );

        if (!empty($conflicts)) {
            foreach ($conflicts as $c) {
                $reasonText = implode(', ', $c['reasons']);
                $errors[]   = 'Bentrok dengan jadwal lain: ' .
                    $c['mata_kuliah'] . ' (' . $c['kelas'] . ') di ' . $c['nama_lab'] .
                    ' [' . $reasonText . '] pada ' . $c['hari'] . ' ' .
                    substr($c['jam_mulai'],0,5) . '-' . substr($c['jam_selesai'],0,5);
            }
        }
    }

    if (empty($errors)) {
        if ($id > 0) {
            $sql  = 'UPDATE praktikum_schedules
                     SET lab_id = ?, dosen_id = ?, mata_kuliah = ?, kelas = ?, hari = ?, jam_mulai = ?, jam_selesai = ?,
                         periode_mulai = ?, periode_selesai = ?, tipe = ?, status = ?
                     WHERE id = ?';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(
                    'iisssssssssi',
                    $labId,
                    $dosenId,
                    $mataKuliah,
                    $kelas,
                    $hari,
                    $jamMulai,
                    $jamSelesai,
                    $periodeMulai,
                    $periodeSelesai,
                    $tipe,
                    $status,
                    $id
                );
                $stmt->execute();
                $stmt->close();
            }
            log_activity('update_praktikum', 'Update jadwal ID ' . $id);
        } else {
            $sql  = 'INSERT INTO praktikum_schedules
                     (lab_id, dosen_id, mata_kuliah, kelas, hari, jam_mulai, jam_selesai, periode_mulai, periode_selesai, tipe, status, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $createdBy = $user['id'];
                $stmt->bind_param(
                    'iisssssssssi',
                    $labId,
                    $dosenId,
                    $mataKuliah,
                    $kelas,
                    $hari,
                    $jamMulai,
                    $jamSelesai,
                    $periodeMulai,
                    $periodeSelesai,
                    $tipe,
                    $status,
                    $createdBy
                );
                $stmt->execute();
                $stmt->close();
            }
            log_activity('create_praktikum', 'Tambah jadwal praktikum ' . $mataKuliah . ' - ' . $kelas);
        }

        header('Location: praktikum_index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id > 0 ? 'Edit' : 'Ajukan / Tambah'; ?> Jadwal Praktikum</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/partials_sidebar.php'; ?>

<main class="main-content">
    <div class="container">
    <h3 class="mb-3"><?php echo $id > 0 ? 'Edit' : 'Ajukan / Tambah'; ?> Jadwal Praktikum</h3>

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
                        <label class="form-label">Mata Kuliah</label>
                        <input type="text" name="mata_kuliah" class="form-control" value="<?php echo htmlspecialchars($mataKuliah); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="kelas" class="form-control" value="<?php echo htmlspecialchars($kelas); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Hari</label>
                        <select name="hari" class="form-select">
                            <?php $hariOptions = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                            foreach ($hariOptions as $h): ?>
                                <option value="<?php echo $h; ?>" <?php echo $hari === $h ? 'selected' : ''; ?>><?php echo $h; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Dosen Pengampu (ID User Dosen)</label>
                        <input type="number" name="dosen_id" class="form-control" value="<?php echo htmlspecialchars((string)$dosenId); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" name="jam_mulai" class="form-control" value="<?php echo htmlspecialchars(substr($jamMulai,0,5)); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" name="jam_selesai" class="form-control" value="<?php echo htmlspecialchars(substr($jamSelesai,0,5)); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Periode Mulai</label>
                        <input type="date" name="periode_mulai" class="form-control" value="<?php echo htmlspecialchars($periodeMulai); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Periode Selesai</label>
                        <input type="date" name="periode_selesai" class="form-control" value="<?php echo htmlspecialchars($periodeSelesai); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipe Penjadwalan</label>
                        <select name="tipe" class="form-select">
                            <option value="sekali" <?php echo $tipe === 'sekali' ? 'selected' : ''; ?>>Sekali</option>
                            <option value="mingguan" <?php echo $tipe === 'mingguan' ? 'selected' : ''; ?>>Mingguan</option>
                            <option value="semester" <?php echo $tipe === 'semester' ? 'selected' : ''; ?>>Per Semester</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="aktif" <?php echo $status === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="dibatalkan" <?php echo $status === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><?php echo $id > 0 ? 'Simpan Perubahan' : 'Simpan'; ?></button>
                    <a href="praktikum_index.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
