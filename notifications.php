<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();

// Tandai dibaca
if (isset($_GET['read']) && $_GET['read'] === 'all') {
    $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $stmt->close();
    }
}

$rows = [];
$stmt = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
if ($stmt) {
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="app.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/partials_sidebar.php'; ?>

<main class="main-content">
    <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Notifikasi</h3>
        <a href="notifications.php?read=all" class="btn btn-sm btn-outline-secondary">Tandai semua sudah dibaca</a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($rows)): ?>
                <p class="mb-0 text-muted">Belum ada notifikasi.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($rows as $n): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start <?php echo $n['is_read'] ? '' : 'fw-bold'; ?>">
                            <div>
                                <div class="small text-uppercase text-muted"><?php echo htmlspecialchars($n['jenis']); ?></div>
                                <div><?php echo nl2br(htmlspecialchars($n['pesan'])); ?></div>
                            </div>
                            <span class="small text-muted"><?php echo htmlspecialchars($n['created_at']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
