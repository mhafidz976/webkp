<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$labs = get_labs();

$labId   = isset($_GET['lab_id']) ? (int) $_GET['lab_id'] : 0;
$start   = $_GET['start'] ?? date('Y-m-01');
$end     = $_GET['end'] ?? date('Y-m-t');
$events  = [];

if ($labId > 0) {
    // Ambil peminjaman dalam rentang
    $stmt = $conn->prepare('SELECT b.*, l.nama_lab, u.nama AS peminjam_nama
                             FROM lab_bookings b
                             JOIN labs l ON l.id = b.lab_id
                             JOIN users u ON u.id = b.peminjam_id
                             WHERE b.lab_id = ? AND b.tanggal BETWEEN ? AND ?');
    if ($stmt) {
        $stmt->bind_param('iss', $labId, $start, $end);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['event_type'] = 'Peminjaman';
            $events[]          = $row;
        }
        $stmt->close();
    }

    // Ambil jadwal praktikum (generate per hari dalam range)
    $stmt = $conn->prepare('SELECT ps.*, l.nama_lab, u.nama AS dosen_nama
                             FROM praktikum_schedules ps
                             JOIN labs l ON l.id = ps.lab_id
                             JOIN users u ON u.id = ps.dosen_id
                             WHERE ps.lab_id = ? AND ps.status = "aktif"');
    if ($stmt) {
        $stmt->bind_param('i', $labId);
        $stmt->execute();
        $result = $stmt->get_result();
        $hariMap = ['Senin' => 1,'Selasa'=>2,'Rabu'=>3,'Kamis'=>4,'Jumat'=>5,'Sabtu'=>6,'Minggu'=>7];
        while ($row = $result->fetch_assoc()) {
            $startDate = max($start, $row['periode_mulai']);
            $endDate   = min($end, $row['periode_selesai']);
            $cur       = strtotime($startDate);
            $endTs     = strtotime($endDate);
            $targetDow = $hariMap[$row['hari']] ?? null;
            if (!$targetDow) {
                continue;
            }
            while ($cur <= $endTs) {
                if ((int)date('N', $cur) === $targetDow) {
                    $ev              = $row;
                    $ev['tanggal']   = date('Y-m-d', $cur);
                    $ev['event_type']= 'Praktikum';
                    $events[]        = $ev;
                }
                $cur = strtotime('+1 day', $cur);
            }
        }
        $stmt->close();
    }
}

usort($events, function ($a, $b) {
    $da = $a['tanggal'] . ' ' . $a['jam_mulai'];
    $db = $b['tanggal'] . ' ' . $b['jam_mulai'];
    return strcmp($da, $db);
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kalender Lab Terpusat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/partials_nav.php'; ?>
<div class="container py-4">
    <h3 class="mb-3">Kalender Lab Terpusat</h3>

    <form class="row g-3 mb-3" method="get">
        <div class="col-md-4">
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
        <div class="col-md-3">
            <label class="form-label">Mulai</label>
            <input type="date" name="start" class="form-control" value="<?php echo htmlspecialchars($start); ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Sampai</label>
            <input type="date" name="end" class="form-control" value="<?php echo htmlspecialchars($end); ?>" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Jenis</th>
                    <th>Detail</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($labId === 0): ?>
                    <tr><td colspan="4" class="text-center text-muted">Pilih lab dan rentang tanggal terlebih dahulu.</td></tr>
                <?php elseif (empty($events)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Tidak ada jadwal pada rentang ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ev['tanggal']); ?></td>
                            <td><?php echo htmlspecialchars(substr($ev['jam_mulai'],0,5) . ' - ' . substr($ev['jam_selesai'],0,5)); ?></td>
                            <td>
                                <?php if ($ev['event_type'] === 'Praktikum'): ?>
                                    <span class="badge bg-primary">Praktikum</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Peminjaman</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ev['event_type'] === 'Praktikum'): ?>
                                    <strong><?php echo htmlspecialchars($ev['mata_kuliah']); ?></strong>
                                    (<?php echo htmlspecialchars($ev['kelas']); ?>)
                                    &mdash; Dosen: <?php echo htmlspecialchars($ev['dosen_nama']); ?>
                                <?php else: ?>
                                    <strong><?php echo htmlspecialchars($ev['jenis']); ?></strong>
                                    &mdash; <?php echo htmlspecialchars($ev['keperluan']); ?>
                                    &mdash; Peminjam: <?php echo htmlspecialchars($ev['peminjam_nama']); ?>
                                <?php endif; ?>
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
