<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user     = current_user();
$roleKey  = $user['role_key'] ?? '';
$notifCnt = $user ? unread_notification_count($user['id']) : 0;
$current  = basename($_SERVER['PHP_SELF'] ?? '');
?>
<style>
.navbar-brand img {
  height: 28px;
  margin-right: 8px;
  vertical-align: middle;
}
</style>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="logo-kampus.png" alt="Logo Kampus" onerror="this.style.display='none'">
            <span>Lab Kampus</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link<?php echo $current === 'dashboard.php' ? ' active' : ''; ?>" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $current === 'praktikum_index.php' ? ' active' : ''; ?>" href="praktikum_index.php">Jadwal Praktikum</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $current === 'bookings_index.php' ? ' active' : ''; ?>" href="bookings_index.php">Peminjaman Lab</a></li>
                <li class="nav-item"><a class="nav-link<?php echo $current === 'calendar.php' ? ' active' : ''; ?>" href="calendar.php">Kalender Lab</a></li>
                <?php if (in_array($roleKey, ['admin', 'teknisi'], true)): ?>
                    <li class="nav-item"><a class="nav-link<?php echo $current === 'labs_index.php' ? ' active' : ''; ?>" href="labs_index.php">Data Lab</a></li>
                    <li class="nav-item"><a class="nav-link<?php echo $current === 'softwares_index.php' ? ' active' : ''; ?>" href="softwares_index.php">Software Lab</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link<?php echo $current === 'tickets_index.php' ? ' active' : ''; ?>" href="tickets_index.php">Tiket Kerusakan</a></li>
                <?php if ($roleKey === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link<?php echo $current === 'users_index.php' ? ' active' : ''; ?>" href="users_index.php">Pengguna</a></li>
                    <li class="nav-item"><a class="nav-link<?php echo $current === 'reports.php' ? ' active' : ''; ?>" href="reports.php">Laporan</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <a href="notifications.php" class="btn btn-outline-light btn-sm me-2">
                    Notifikasi
                    <?php if ($notifCnt > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $notifCnt; ?></span>
                    <?php endif; ?>
                </a>
                <span class="navbar-text me-3 small">
                    <?php echo htmlspecialchars($user['nama'] ?? ''); ?> (<?php echo htmlspecialchars($user['role'] ?? ''); ?>)
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>
