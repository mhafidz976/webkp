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
        body { 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            color: #fff; 
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .card { 
            background: rgba(255,255,255,0.1); 
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
        }
        .table {
            background: rgba(255,255,255,0.05);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .table thead th {
            background: rgba(255,255,255,0.15);
            border: none;
            color: #fff;
            font-weight: 600;
        }
        .table td {
            border-color: rgba(255,255,255,0.1);
            color: #fff;
        }
        .badge {
            font-weight: 500;
            padding: 0.5em 1em;
        }
        .display-header {
            background: rgba(255,255,255,0.1);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        .time-badge {
            background: rgba(76, 175, 80, 0.8);
            font-size: 0.9rem;
        }
        .event-type-praktikum {
            background: rgba(33, 150, 243, 0.8);
        }
        .event-type-peminjaman {
            background: rgba(255, 152, 0, 0.8);
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .live-indicator {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="display-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-3">
                    <h1 class="mb-0 me-3">Jadwal Lab Hari Ini</h1>
                    <span class="badge bg-danger live-indicator">LIVE</span>
                </div>
                <p class="mb-0 opacity-75">
                    <i class="bi bi-calendar3 me-2"></i>
                    <?php echo date('d F Y'); ?> - 
                    <i class="bi bi-clock me-2 ms-3"></i>
                    <span id="current-time"><?php echo date('H:i:s'); ?></span>
                </p>
            </div>
            <div class="col-md-4">
                <form class="d-flex" method="get">
                    <select name="lab_id" class="form-select form-select-lg" onchange="this.form.submit()">
                <option value="">-- Pilih Lab --</option>
                <?php foreach ($labs as $lab): ?>
                    <option value="<?php echo $lab['id']; ?>" <?php echo (int)$labId === (int)$lab['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lab['nama_lab']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
            </div>
        </div>
    </div>

    <?php if ($labId === 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-building display-1 opacity-50"></i>
            <h3 class="mt-3">Pilih Lab</h3>
            <p class="opacity-75">Silakan pilih lab untuk menampilkan jadwal hari ini.</p>
        </div>
    <?php elseif (empty($events)): ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x display-1 opacity-50"></i>
            <h3 class="mt-3">Tidak Ada Jadwal</h3>
            <p class="opacity-75">Tidak ada jadwal untuk lab ini pada hari ini.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th width="20%">Waktu</th>
                            <th width="15%">Jenis</th>
                            <th width="65%">Detail</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($events as $ev): ?>
                            <tr>
                                <td>
                                    <span class="badge time-badge">
                                        <?php echo htmlspecialchars(substr($ev['jam_mulai'],0,5) . ' - ' . substr($ev['jam_selesai'],0,5)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ev['event_type'] === 'Praktikum'): ?>
                                        <span class="badge event-type-praktikum">Praktikum</span>
                                    <?php else: ?>
                                        <span class="badge event-type-peminjaman">Peminjaman</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ev['event_type'] === 'Praktikum'): ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($ev['mata_kuliah']); ?></strong>
                                            <div class="small opacity-75">
                                                <?php echo htmlspecialchars($ev['dosen_nama']); ?> - 
                                                Kelas <?php echo htmlspecialchars($ev['kelas']); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($ev['keperluan']); ?></strong>
                                            <div class="small opacity-75">
                                                Oleh: <?php echo htmlspecialchars($ev['peminjam_nama']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Update current time every second
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { hour12: false });
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }
    
    // Update immediately and then every second
    updateTime();
    setInterval(updateTime, 1000);
    
    // Auto-refresh every 5 minutes
    setTimeout(() => {
        window.location.reload();
    }, 300000);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
