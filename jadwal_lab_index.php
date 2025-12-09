<?php
require_once 'koneksi.php';

// Ambil semua jadwal lab
$sql  = "SELECT * FROM jadwal_lab ORDER BY tanggal, jam_mulai";
$stmt = $conn->query($sql);
$jadwal = [];
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $jadwal[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Lab Kampus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Jadwal Penggunaan Lab</h2>
        <a href="jadwal_lab_form.php" class="btn btn-primary btn-sm">+ Tambah Jadwal</a>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="mb-3">Gunakan halaman ini untuk melihat dan mengelola jadwal penggunaan lab kampus.</p>

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Nama Lab</th>
                        <th>Mata Kuliah</th>
                        <th>Dosen</th>
                        <th>Kelas</th>
                        <th>Keterangan</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($jadwal)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada jadwal.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jadwal as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= htmlspecialchars($row['jam_mulai']) ?> - <?= htmlspecialchars($row['jam_selesai']) ?></td>
                                <td><?= htmlspecialchars($row['nama_lab']) ?></td>
                                <td><?= htmlspecialchars($row['mata_kuliah']) ?></td>
                                <td><?= htmlspecialchars($row['dosen']) ?></td>
                                <td><?= htmlspecialchars($row['kelas']) ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <hr class="my-4">
            <h5>Instruksi Setup Database (jalankan sekali saja)</h5>
            <pre class="small bg-light p-2 border rounded"><code>CREATE DATABASE IF NOT EXISTS db_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_lab;

CREATE TABLE IF NOT EXISTS jadwal_lab (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    nama_lab VARCHAR(100) NOT NULL,
    mata_kuliah VARCHAR(150) NOT NULL,
    dosen VARCHAR(100) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
</code></pre>
            <p class="small text-muted mb-0">
                Jalankan SQL di atas melalui phpMyAdmin (&quot;db_lab&quot; akan dibuat otomatis jika belum ada).
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
