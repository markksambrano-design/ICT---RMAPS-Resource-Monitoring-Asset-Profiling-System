<?php
// Session should be started in the main file that includes config.php
$adminEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';

// Determine base paths based on current admin folder level
$lastDir = basename(dirname($_SERVER['PHP_SELF']));
$adminBase = $adminBase ?? (($lastDir === 'action' || $lastDir === 'modules' || $lastDir === 'api') ? '../' : '');
$assetPath = $assetPath ?? (($lastDir === 'action' || $lastDir === 'modules' || $lastDir === 'api') ? '../../assest/css/admin' : '../assest/css/admin');

// Fetch notification count (Example: number of open forms)
include_once dirname(__DIR__, 2) . '/config.php';
$notifCount = 0;
$notificationItems = [];
if ($adminEmail && isset($conn)) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_issp_form (
        id INT AUTO_INCREMENT PRIMARY KEY,
        office_name VARCHAR(150),
        date_submitted DATE,
        item VARCHAR(100),
        units INT DEFAULT 0,
        brand VARCHAR(100),
        processor VARCHAR(100),
        ram VARCHAR(50),
        hdd VARCHAR(50),
        ssd VARCHAR(50),
        equipment_image VARCHAR(500) DEFAULT NULL,
        system1 TEXT,
        system2 TEXT,
        system3 TEXT,
        system4 TEXT,
        system5 TEXT,
        proposed_system1 TEXT,
        proposed_system2 TEXT,
        proposed_system3 TEXT,
        proposed_system4 TEXT,
        proposed_system5 TEXT,
        inkjet_printer INT DEFAULT 0,
        deskjet_printer INT DEFAULT 0,
        dotmatrix_printer INT DEFAULT 0,
        switch_hubs INT DEFAULT 0,
        routers INT DEFAULT 0,
        modem INT DEFAULT 0,
        form_status VARCHAR(50) DEFAULT 'Open',
        inkjet_printer_model TEXT,
        deskjet_printer_model TEXT,
        dotmatrix_printer_model TEXT,
        switch_hubs_model TEXT,
        routers_model TEXT,
        modem_model TEXT,
        status VARCHAR(50) DEFAULT 'operational',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $openFormsRes = mysqli_query($conn, "SELECT COUNT(*) as total FROM (SELECT office_name, date_submitted FROM user_issp_form WHERE COALESCE(form_status, 'Open') = 'Open' GROUP BY office_name, date_submitted) as t");
    $openForms = $openFormsRes ? mysqli_fetch_assoc($openFormsRes) : ['total' => 0];
    $notifCount = intval($openForms['total']);

    $notificationItemsRes = mysqli_query($conn, "SELECT office_name, date_submitted FROM user_issp_form WHERE COALESCE(form_status, 'Open') = 'Open' GROUP BY office_name, date_submitted ORDER BY date_submitted DESC LIMIT 5");
    if ($notificationItemsRes) {
        while ($row = mysqli_fetch_assoc($notificationItemsRes)) {
            $notificationItems[] = $row;
        }
    }
}

function escapeHtml($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<link rel="stylesheet" href="<?php echo $assetPath; ?>/header.css">

<!-- TOP HEADER -->
<div class="top-header">

    <div class="header-left">
        <div class="header-logo-container">
            <i class="bi bi-cpu system-logo-icon"></i>
            <h1 class="system-title">ICT - RMAPS</h1>
        </div>
        <span class="system-tagline">
            Resource Monitoring & Asset Profiling System
        </span>
    </div>

    <div class="header-right">
        <div class="network-status" id="networkStatus">
            <i class="bi bi-wifi network-wifi-icon"></i>
            <div class="network-ping-large" id="networkPing">-- mbps</div>
        </div>

        <!-- NOTIFICATION DROPDOWN -->
        <div class="dropdown notification-dropdown">
            <button class="btn btn-link dropdown-toggle text-white text-decoration-none p-0 position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="notification-icon-container">
                    <i class="bi bi-bell notification-bell-icon"></i>
                    <span id="notificationBadge" class="notification-badge" style="display: <?php echo $notifCount > 0 ? 'inline-flex' : 'none'; ?>;">
                        <?php echo $notifCount; ?>
                    </span>
                </div>
            </button>
            <ul id="notificationMenu" class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 notification-menu" aria-labelledby="notificationDropdown">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li><hr class="dropdown-divider"></li>
                <?php if ($notifCount > 0): ?>
                    <?php foreach ($notificationItems as $item): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-start p-3" href="<?php echo $adminBase; ?>form.php">
                                <div class="notification-content">
                                    <p class="mb-0 fw-bold"><?php echo htmlspecialchars($item['office_name']); ?> submitted a form</p>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['date_submitted']); ?></small>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li id="notificationEmptyItem" class="notification-item-empty">
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-bell-slash fs-4 d-block mb-2"></i>
                            <span>No new notifications</span>
                        </div>
                    </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center text-primary fw-bold" href="<?php echo $adminBase; ?>form.php">View All Notifications</a></li>
            </ul>
        </div>

        <!-- USER PROFILE DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-link dropdown-toggle d-flex align-items-center text-white text-decoration-none p-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-profile-info">
                    <div class="user-avatar-icon">
                        <?php
                        $admin = null;
                        if ($adminEmail && isset($conn)) {
                            $stmt = mysqli_prepare($conn, 'SELECT profile_image FROM admin WHERE email = ?');
                            if ($stmt) {
                                mysqli_stmt_bind_param($stmt, 's', $adminEmail);
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_bind_result($stmt, $profileImage);
                                mysqli_stmt_fetch($stmt);
                                mysqli_stmt_close($stmt);
                                if (!empty($profileImage)) {
                                    $admin = ['profile_image' => $profileImage];
                                }
                            }
                        }
                        if ($admin && !empty($admin['profile_image'])): ?>
                            <img src="<?php echo $adminBase; ?>../assest/images/admin _profile/<?php echo escapeHtml($admin['profile_image']); ?>" alt="Profile" class="avatar-img">
                        <?php else: ?>
                            <i class="bi bi-person-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-details text-start">
                        <p class="user-name mb-0"><?php echo isset($_SESSION['name']) && $_SESSION['name'] != '' ? htmlspecialchars($_SESSION['name']) : 'Admin User'; ?></p>
                        <small class="user-role">ICTMIS</small>
                    </div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="dropdownMenuButton1">
                <li><h6 class="dropdown-header">Manage Account</h6></li>
                <li><a class="dropdown-item d-flex align-items-center" href="<?php echo $adminBase; ?>profile.php"><i class="bi bi-person me-3"></i> Profile</a></li>
                <li><a class="dropdown-item d-flex align-items-center" href="<?php echo $adminBase; ?>setting.php"><i class="bi bi-gear me-3"></i> Settings</a></li>
            </ul>
        </div>
    </div>

</div>

<script>
(function() {
    const pingEl = document.getElementById('networkPing');

    async function updateNetworkStatus() {
        // Use downlink if available (actual speed in Mbps)
        const speed = (navigator.connection && navigator.connection.downlink) 
            ? navigator.connection.downlink 
            : null;

        if (pingEl) {
            pingEl.textContent = speed !== null ? speed + ' mbps' : '-- mbps';
        }
    }

    updateNetworkStatus();
    setInterval(updateNetworkStatus, 1000);

    async function fetchNotifications() {
        const url = '<?php echo $adminBase; ?>api/get_notifications.php';
        try {
            const response = await fetch(url, { cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            updateNotificationMenu(data);
        } catch (error) {
            console.error('Notification fetch failed:', error);
        }
    }

    function updateNotificationMenu(data) {
        const badge = document.getElementById('notificationBadge');
        const menu = document.getElementById('notificationMenu');

        if (!badge || !menu || !data || typeof data !== 'object') return;

        const previousCount = Number(badge.textContent) || 0;
        const newCount = Number(data.count) || 0;
        const items = Array.isArray(data.items) ? data.items : [];

        badge.style.display = newCount > 0 ? 'inline-flex' : 'none';
        
        if (newCount !== previousCount) {
            animateBadgeCount(badge, previousCount, newCount);
        } else {
            badge.textContent = newCount;
        }

        if (newCount > previousCount && newCount > 0) {
            playNotificationSound();
        }

        let menuHtml = '<li><h6 class="dropdown-header">Notifications</h6></li><li><hr class="dropdown-divider"></li>';

        if (newCount > 0 && items.length > 0) {
            items.forEach((item) => {
                menuHtml += '<li><a class="dropdown-item d-flex align-items-start p-3" href="<?php echo $adminBase; ?>form.php">' +
                    '<div class="notification-content">' +
                    '<p class="mb-0 fw-bold">' + escapeHtml(item.office_name || 'Office') + ' submitted a form</p>' +
                    '<small class="text-muted">' + escapeHtml(item.date_submitted || '') + '</small>' +
                    '</div></a></li>';
            });
        } else {
            menuHtml += '<li class="notification-item-empty">' +
                '<div class="text-center py-3 text-muted">' +
                '<i class="bi bi-bell-slash fs-4 d-block mb-2"></i>' +
                '<span>No new notifications</span>' +
                '</div></li>';
        }

        menuHtml += '<li><hr class="dropdown-divider"></li>' +
            '<li><a class="dropdown-item text-center text-primary fw-bold" href="<?php echo $adminBase; ?>form.php">View All Notifications</a></li>';

        menu.innerHTML = menuHtml;
    }

    function animateBadgeCount(el, start, end) {
        if (!el) return;
        const duration = 1500;
        const frameDuration = 1000 / 60;
        const totalFrames = Math.round(duration / frameDuration);
        let frame = 0;
        const easeOutExpo = t => t === 1 ? 1 : 1 - Math.pow(2, -10 * t);

        const counter = setInterval(() => {
            frame++;
            const progress = easeOutExpo(frame / totalFrames);
            const currentCount = Math.round(start + (end - start) * progress);

            if (frame >= totalFrames) {
                clearInterval(counter);
                el.textContent = end;
            } else {
                el.textContent = currentCount;
            }
        }, frameDuration);
    }

    function playNotificationSound() {
        try {
            // Create audio context for notification sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            // Bell-like sound: two frequencies
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (error) {
            console.log('Audio notification not supported');
        }
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Initial animation for the badge on page load
    const initialBadge = document.getElementById('notificationBadge');
    if (initialBadge) {
        const targetCount = Number(initialBadge.textContent) || 0;
        if (targetCount > 0) {
            initialBadge.textContent = '0';
            animateBadgeCount(initialBadge, 0, targetCount);
        }
    }

    fetchNotifications();
    setInterval(fetchNotifications, 15000);
})();</script>
