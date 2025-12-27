<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user     = current_user();
$roleKey  = $user['role_key'] ?? '';
$notifCnt = $user ? unread_notification_count($user['id']) : 0;
$current  = basename($_SERVER['PHP_SELF'] ?? '');
?>
<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 260px;
    background: linear-gradient(135deg, #1f2937 0%, #020617 100%);
    border-right: 1px solid rgba(15, 23, 42, 0.7);
    z-index: 1000;
    transition: transform 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
}

/* Custom scrollbar for sidebar */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.3);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

.sidebar::-webkit-scrollbar-thumb:active {
    background: rgba(255, 255, 255, 0.4);
}

.sidebar.collapsed {
    transform: translateX(-100%);
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.7);
    background: rgba(15, 23, 42, 0.5);
}

.sidebar-brand {
    display: flex;
    align-items: center;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    letter-spacing: 0.04em;
}

.sidebar-brand img {
    height: 28px;
    margin-right: 8px;
    vertical-align: middle;
}

.sidebar-nav {
    padding: 1rem 0;
}

.sidebar-item {
    display: block;
    padding: 0.75rem 1.5rem;
    color: #e5e7eb;
    text-decoration: none;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-weight: 500;
    transition: all 0.16s ease;
    position: relative;
}

.sidebar-item:hover {
    background: rgba(15, 23, 42, 0.9);
    color: #fff;
}

.sidebar-item.active {
    background: #f9fafb;
    color: #111827;
    border-left: 3px solid var(--color-primary);
}

.sidebar-item.active:hover {
    background: #f3f4f6;
    color: #111827;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(15, 23, 42, 0.7);
    background: rgba(15, 23, 42, 0.5);
}

.user-info {
    color: #d1d5db;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
}

.sidebar-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.main-content {
    margin-left: 260px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

.main-content.expanded {
    margin-left: 0;
}

.mobile-toggle {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: var(--color-primary);
    border: none;
    color: white;
    padding: 0.5rem;
    border-radius: 0.5rem;
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .mobile-toggle {
        display: block;
    }
    
    .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }
    
    .overlay.show {
        display: block;
    }
}
</style>

<!-- Mobile Toggle Button -->
<button class="mobile-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Overlay for mobile -->
<div class="overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="dashboard.php">
            <img src="logo-kampus.png" alt="Logo Kampus" onerror="this.style.display='none'">
            <span>Lab Kampus</span>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <a class="sidebar-item<?php echo $current === 'dashboard.php' ? ' active' : ''; ?>" href="dashboard.php">
            📊 Dashboard
        </a>
        <a class="sidebar-item<?php echo $current === 'praktikum_index.php' ? ' active' : ''; ?>" href="praktikum_index.php">
            📅 Jadwal Praktikum
        </a>
        <a class="sidebar-item<?php echo $current === 'bookings_index.php' ? ' active' : ''; ?>" href="bookings_index.php">
            🔑 Peminjaman Lab
        </a>
        <a class="sidebar-item<?php echo $current === 'calendar.php' ? ' active' : ''; ?>" href="calendar.php">
            📆 Kalender Lab
        </a>
        
        <?php if (in_array($roleKey, ['admin', 'teknisi'], true)): ?>
            <a class="sidebar-item<?php echo $current === 'labs_index.php' ? ' active' : ''; ?>" href="labs_index.php">
                🏢 Data Lab
            </a>
            <a class="sidebar-item<?php echo $current === 'softwares_index.php' ? ' active' : ''; ?>" href="softwares_index.php">
                💿 Software Lab
            </a>
        <?php endif; ?>
        
        <a class="sidebar-item<?php echo $current === 'tickets_index.php' ? ' active' : ''; ?>" href="tickets_index.php">
            🎫 Tiket Kerusakan
        </a>
        
        <?php if ($roleKey === 'admin'): ?>
            <a class="sidebar-item<?php echo $current === 'users_index.php' ? ' active' : ''; ?>" href="users_index.php">
                👥 Pengguna
            </a>
            <a class="sidebar-item<?php echo $current === 'reports.php' ? ' active' : ''; ?>" href="reports.php">
                📈 Laporan
            </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <strong><?php echo htmlspecialchars($user['nama'] ?? ''); ?></strong><br>
            <small><?php echo htmlspecialchars($user['role'] ?? ''); ?></small>
        </div>
        <div class="sidebar-actions">
            <a href="notifications.php" class="btn btn-outline-light btn-sm">
                🔔 Notifikasi
                <?php if ($notifCnt > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $notifCnt; ?></span>
                <?php endif; ?>
            </a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                🚪 Logout
            </a>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    
    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
    }
    
    function closeSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    }
    
    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', closeSidebar);
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    });
    
    // Close sidebar when clicking a navigation item on mobile
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
});
</script>
