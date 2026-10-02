<?php
// Session should be started in the main file

// Use root-relative links for consistent user navigation
$userBase = '/user/';
?>

<div class="sidebar" id="userSidebar">
    <!-- Sidebar Toggle Button -->
    <div class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-chevron-left"></i>
    </div>
    <!-- Sidebar Brand/Logo -->
    <div class="sidebar-brand">
        <div class="brand-logo">
             <img src="/assest/images/logo3.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <div class="brand-text">
            <h2 class="brand-title">ICTMIS</h2>
            <p class="brand-subtitle">MUNICIPAL PORTAL</p>
        </div>
    </div>

    <!-- User Profile Section -->
    <div class="user-profile-vertical">
        <div class="profile-avatar">
            <i class="bi bi-person-circle"></i>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <!-- Main Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">MAIN</h6>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $userBase; ?>dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-speedometer2"></i>
                        </span>
                        <span class="nav-label">Dashboard</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo $userBase; ?>Issp_form.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'Issp_form.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-file-earmark-check"></i>
                        </span>
                        <span class="nav-label">RMAPS Form</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'Issp_form.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo $userBase; ?>devices.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'devices.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-laptop"></i>
                        </span>
                        <span class="nav-label">Devices</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'devices.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo $userBase; ?>office_profile.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'office_profile.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-building"></i>
                        </span>
                        <span class="nav-label">Office Profile</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'office_profile.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo $userBase; ?>history.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'history.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-clock-history"></i>
                        </span>
                        <span class="nav-label">History</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'history.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <ul class="nav-list nb-3">
            <li class="nav-item">
                <a href="javascript:void(0);" class="nav-link text-danger" id="logoutBtn">
                    <span class="nav-icon">
                        <i class="bi bi-box-arrow-right"></i>
                    </span>
                    <span class="nav-label fw-bold">Logout</span>
                </a>
            </li>
        </ul>
        <div class="system-info">
            <p class="system-version">System Version 1.0</p>
        </div>
    </div>
</div>

<!-- Custom Logout Modal -->
<div class="custom-modal" id="logoutModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="bi bi-box-arrow-right"></i>
            </div>
        </div>
        <div class="modal-body">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to sign out? You will need to login again to access your dashboard.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelLogout">Cancel</button>
            <a href="<?php echo $userBase; ?>logout.php" class="btn-confirm">Logout Now</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('sidebarToggle');
    const body = document.body;

    if (toggle) {
        toggle.addEventListener('click', function() {
            document.documentElement.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', document.documentElement.classList.contains('sidebar-collapsed'));
        });
    }

    // Logout Modal Logic
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogout = document.getElementById('cancelLogout');

    if (logoutBtn && logoutModal) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.classList.add('show');
        });

        cancelLogout.addEventListener('click', function() {
            logoutModal.classList.remove('show');
        });

        // Close modal if clicking outside the content
        logoutModal.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                logoutModal.classList.remove('show');
            }
        });
    }
});
</script>
