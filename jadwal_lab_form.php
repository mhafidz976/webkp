<?php
require_once 'koneksi.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal     = $_POST['tanggal'] ?? '';
    $jam_mulai   = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $nama_lab    = $_POST['nama_lab'] ?? '';
    $mata_kuliah = $_POST['mata_kuliah'] ?? '';
    $dosen       = $_POST['dosen'] ?? '';
    $kelas       = $_POST['kelas'] ?? '';
    $keterangan  = $_POST['keterangan'] ?? '';

    if ($tanggal === '' || $jam_mulai === '' || $jam_selesai === '' || $nama_lab === '' || $mata_kuliah === '' || $dosen === '' || $kelas === '') {
        $errors[] = 'Semua field wajib diisi kecuali keterangan.';
    }

    if (empty($errors)) {
        $sql = "INSERT INTO jadwal_lab (tanggal, jam_mulai, jam_selesai, nama_lab, mata_kuliah, dosen, kelas, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ssssssss', $tanggal, $jam_mulai, $jam_selesai, $nama_lab, $mata_kuliah, $dosen, $kelas, $keterangan);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = 'Gagal menyimpan data: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Gagal menyiapkan query: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Jadwal Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Tambah Jadwal Lab</h2>
        <a href="jadwal_lab_index.php" class="btn btn-secondary btn-sm">&laquo; Kembali ke Jadwal</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">Jadwal berhasil disimpan.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" name="jam_mulai" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" name="jam_selesai" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Lab</label>
                        <input type="text" name="nama_lab" class="form-control" placeholder="Lab Komputer 1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mata Kuliah</label>
                        <input type="text" name="mata_kuliah" class="form-control" placeholder="Pemrograman Web" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Dosen</label>
                        <input type="text" name="dosen" class="form-control" placeholder="Nama Dosen" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="kelas" class="form-control" placeholder="TI-2A" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Keterangan (opsional)</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan"></textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                    <a href="jadwal_lab_index.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
