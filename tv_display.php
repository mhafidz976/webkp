<?php
// Halaman tampilan jadwal untuk TV (read-only, tanpa login)
require_once __DIR__ . '/koneksi.php';

$labId = isset($_GET['lab_id']) ? (int) $_GET['lab_id'] : 0;
$hari  = $_GET['hari'] ?? date('l');

$hariMapEnToId = [
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu',
    'Sunday'    => 'Minggu',
];

if (isset($hariMapEnToId[$hari])) {
    $hariId = $hariMapEnToId[$hari];
} else {
    $hariId = 'Senin';
}

$labs = [];
$result = $conn->query('SELECT * FROM labs ORDER BY nama_lab');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $labs[] = $row;
    }
}

$today = date('Y-m-d');

$events = [];
if ($labId > 0) {
    // Praktikum hari ini di lab yang dipilih
    $sql = "SELECT ps.*, u.nama AS dosen_nama
            FROM praktikum_schedules ps
            JOIN users u ON u.id = ps.dosen_id
            WHERE ps.lab_id = ? AND ps.hari = ?
              AND ps.periode_mulai <= ? AND ps.periode_selesai >= ?
              AND ps.status = 'aktif'
            ORDER BY ps.jam_mulai";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('isss', $labId, $hariId, $today, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $row['event_type'] = 'Praktikum';
            $row['tanggal']    = $today;
            $events[]          = $row;
        }
        $stmt->close();
    }

    // Peminjaman hari ini di lab yang sama
    $sql = "SELECT b.*, u.nama AS peminjam_nama
            FROM lab_bookings b
            JOIN users u ON u.id = b.peminjam_id
            WHERE b.lab_id = ? AND b.tanggal = ? AND b.status = 'approved'
            ORDER BY b.jam_mulai";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('is', $labId, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $row['event_type'] = 'Peminjaman';
            $events[]          = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Display Jadwal Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
    <style>
        body { background: #000; color: #fff; }
        .card { background: rgba(0,0,0,0.7); border: none; }
    </style>
</head>
<body>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Jadwal Lab Hari Ini</h2>
        <form class="d-flex" method="get">
            <select name="lab_id" class="form-select me-2" onchange="this.form.submit()">
                <option value="">-- Pilih Lab --</option>
                <?php foreach ($labs as $lab): ?>
                    <option value="<?php echo $lab['id']; ?>" <?php echo (int)$labId === (int)$lab['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lab['nama_lab']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($labId === 0): ?>
        <p class="text-muted">Pilih lab untuk menampilkan jadwal.</p>
    <?php elseif (empty($events)): ?>
        <p class="text-muted">Tidak ada jadwal untuk hari ini.</p>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <table class="table table-dark table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th>Detail</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
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
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto refresh tiap 60 detik untuk mode TV
    setTimeout(function(){ window.location.reload(); }, 60000);
</script>
</body>
</html>
