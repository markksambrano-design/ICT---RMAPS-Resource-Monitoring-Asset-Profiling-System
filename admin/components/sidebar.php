<?php
// Session should be started in the main file that includes config.php

// Set base path for admin links
$currentDir = dirname($_SERVER['SCRIPT_NAME']);
$dirParts = explode('/', trim($currentDir, '/'));
$lastDir = end($dirParts);
$adminBase = ($lastDir == 'action' || $lastDir == 'modules' || $lastDir == 'api') ? '../' : '';
?>

<div class="sidebar" id="adminSidebar">
    <!-- Sidebar Toggle Button -->
    <div class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-chevron-left"></i>
    </div>
    <!-- Sidebar Brand/Logo -->
    <div class="sidebar-brand">
        <div class="brand-logo">
             <img src="<?php echo $adminBase; ?>../assest/images/logo3.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <div class="brand-text">
            <h5 class="brand-title">ICTMIS</h5>
            <p class="brand-subtitle">Municipal Portal</p>
        </div>
    </div>

    <!-- User Profile Section -->
    <div class="user-profile-vertical">
        <div class="profile-avatar">
            <?php 
            $email = $_SESSION['email'];
            $adminRes = mysqli_query($conn, "SELECT profile_image FROM admin WHERE email = '$email'");
            $admin = mysqli_fetch_assoc($adminRes);
            if ($admin && $admin['profile_image']): ?>
                <img src="<?php echo $adminBase; ?>../assest/images/admin _profile/<?php echo htmlspecialchars($admin['profile_image']); ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
                <i class="bi bi-person-circle"></i>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <!-- Core Services Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">CORE SERVICES</h6>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $adminBase; ?>dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
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
                    <a href="<?php echo $adminBase; ?>form.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'form.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </span>
                        <span class="nav-label">RMAPS Form</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'form.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo $adminBase; ?>inventory.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'inventory.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-box-seam"></i>
                        </span>
                        <span class="nav-label">Inventory</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'inventory.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Office Management Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">OFFICE MANAGEMENT</h6>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $adminBase; ?>offices_directory.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'offices_directory.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-geo-alt"></i>
                        </span>
                        <span class="nav-label">Offices Directory</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'offices_directory.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Administration Section -->
        <div class="nav-section">
            <h6 class="nav-section-title">ADMINISTRATION</h6>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo $adminBase; ?>users.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-people"></i>
                        </span>
                        <span class="nav-label">Users</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'users.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo $adminBase; ?>register_user.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'register_user.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-person-plus"></i>
                        </span>
                        <span class="nav-label">Add User</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'register_user.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo $adminBase; ?>report.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'report.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <i class="bi bi-bar-chart"></i>
                        </span>
                        <span class="nav-label">Reports</span>
                        <?php if(basename($_SERVER['PHP_SELF']) == 'report.php'): ?>
                            <span class="nav-badge active-badge"></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <ul class="nav-list mb-3">
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
    <div class="modal-content animate__animated animate__zoomIn">
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
            <a href="<?php echo $adminBase; ?>logout.php" class="btn-confirm">Logout Now</a>
        </div>
    </div>
</div>
<style>
/* Custom Modal Styles */
.custom-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.custom-modal.show {
    display: flex;
}

.custom-modal .modal-content {
    background-color: #fff;
    border-radius: 20px;
    width: 90%;
    max-width: 400px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    --animate-duration: 0.3s;
}

.custom-modal .modal-header {
    margin-bottom: 20px;
    display: flex;
    justify-content: center;
}

.modal-icon {
    width: 70px;
    height: 70px;
    background: #0f172a;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 32px;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
}

.custom-modal .modal-body h3 {
    font-size: 24px;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 10px;
}

.custom-modal .modal-body p {
    color: #718096;
    font-size: 15px;
    line-height: 1.5;
    margin-bottom: 30px;
}

.custom-modal .modal-footer {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-cancel {
    padding: 12px 25px;
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    background: white;
    color: #4a5568;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 1;
}

.btn-cancel:hover {
    background: #f7fafc;
    border-color: #cbd5e0;
}

.btn-confirm {
    padding: 12px 25px;
    border-radius: 12px;
    border: none;
    background: #dc3545;
    color: white;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 1;
}

.btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
    color: white;
}
</style>

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
            logoutModal.querySelector('.modal-content').classList.add('animate__zoomIn');
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
