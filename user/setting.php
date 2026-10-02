<?php
include "../config.php";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = '$userId'");
$userData = mysqli_fetch_assoc($userRes);

$success = "";
$error = "";

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // In user table, password is hashed using password_hash()
    if (password_verify($current_pass, $userData['password'])) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 8) {
                $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET password = '$hashed_new_pass' WHERE id = '$userId'";
                if (mysqli_query($conn, $updateQuery)) {
                    $success = "Password has been changed successfully!";
                } else {
                    $error = "System error: Failed to update password.";
                }
            } else {
                $error = "New password must be at least 8 characters long.";
            }
        } else {
            $error = "The new passwords you entered do not match.";
        }
    } else {
        $error = "The current password you entered is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'Account Settings | ICTMIS'; ?>
<?php include 'components/head.php'; ?>
<link rel="stylesheet" href="../assest/css/admin/setting.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    .dashboard-container {
        display: flex;
        min-height: 100vh;
        width: 100%;
        background: #f8fafc;
    }

    .main-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        margin-left: 260px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sidebar-collapsed .main-content {
        margin-left: 90px;
    }
    @media (max-width: 992px) {
        .main-content {
            margin-left: 90px;
        }
    }
</style>
</head>

<body>

<div class="dashboard-container">
    <!-- SIDEBAR -->
    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOP BAR -->
        <?php include __DIR__ . '/components/header.php'; ?>
        
        <!-- PAGE HEADER -->
        <div class="welcome-header futuristic-header" style="padding: 30px 40px; margin: 0 0 30px 0; border-radius: 0;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title">Account Settings</h1>
                    <p class="page-subtitle mb-0">Manage your account profile, security settings, and system preferences.</p>
                </div>
                <div class="col-md-4 text-md-end d-none d-md-block">
                    <i class="bi bi-gear-wide-connected header-icon"></i>
                </div>
            </div>
        </div>

        <div class="container-fluid py-4 px-4">
            <div class="row">
                <div class="col-lg-3">
                    <!-- SETTINGS NAVIGATION -->
                    <div class="security-card profile-card-bg mb-4 p-3 animate__animated animate__fadeInLeft" style="background: white;">
                        <div class="nav flex-column nav-pills" id="settings-tabs" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active d-flex align-items-center mb-2 py-3" id="security-tab" data-bs-toggle="pill" data-bs-target="#security-pane" type="button" role="tab">
                                <i class="bi bi-shield-lock me-3 fs-5"></i>
                                <span>Security & Login</span>
                            </button>
                            <button class="nav-link d-flex align-items-center mb-2 py-3" id="preferences-tab" data-bs-toggle="pill" data-bs-target="#preferences-pane" type="button" role="tab">
                                <i class="bi bi-sliders2 me-3 fs-5"></i>
                                <span>System Preferences</span>
                            </button>
                            <button class="nav-link d-flex align-items-center py-3" id="about-tab" data-bs-toggle="pill" data-bs-target="#about-pane" type="button" role="tab">
                                <i class="bi bi-info-circle me-3 fs-5"></i>
                                <span>About Account</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <!-- STATUS MESSAGES -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 animate__animated animate__fadeInDown" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 animate__animated animate__fadeInDown" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="tab-content" id="settings-content">
                        <!-- SECURITY PANE -->
                        <div class="tab-pane fade show active" id="security-pane" role="tabpanel">
                            <div class="security-card profile-card-bg animate__animated animate__fadeInUp" style="background: white;">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="security-icon-wrapper me-3" style="width: 45px; height: 45px; background: rgba(13, 110, 253, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0d6efd;">
                                        <i class="bi bi-shield-lock"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Security & Password</h5>
                                        <p class="text-muted mb-0 small">Update your account password for security.</p>
                                    </div>
                                </div>
                                <hr class="mb-4">
                                <form method="POST">
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold small text-uppercase tracking-wider">Current Password</label>
                                        <div class="input-group custom-input-group">
                                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                                            <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-uppercase tracking-wider">New Password</label>
                                            <div class="input-group custom-input-group">
                                                <span class="input-group-text"><i class="bi bi-shield-plus"></i></span>
                                                <input type="password" name="new_password" class="form-control" required placeholder="Enter new password">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-uppercase tracking-wider">Confirm New Password</label>
                                            <div class="input-group custom-input-group">
                                                <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                                <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-primary-custom">
                                        <i class="bi bi-arrow-clockwise"></i> Change Password
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- PREFERENCES PANE -->
                        <div class="tab-pane fade" id="preferences-pane" role="tabpanel">
                            <div class="security-card profile-card-bg animate__animated animate__fadeInUp" style="background: white;">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="security-icon-wrapper me-3" style="width: 45px; height: 45px; background: rgba(13, 110, 253, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0d6efd;">
                                        <i class="bi bi-sliders2"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">System Preferences</h5>
                                        <p class="text-muted mb-0 small">Customize your system experience.</p>
                                    </div>
                                </div>
                                <hr class="mb-4">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-light mb-3 border">
                                            <div>
                                                <h6 class="mb-1 fw-bold">Dark Mode</h6>
                                                <p class="mb-0 text-muted small">Enable dark theme for the interface.</p>
                                            </div>
                                            <div class="form-check form-switch fs-4">
                                                <input class="form-check-input" type="checkbox" role="switch" id="darkModeSwitch">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-light border">
                                            <div>
                                                <h6 class="mb-1 fw-bold">Compact Sidebar</h6>
                                                <p class="mb-0 text-muted small">Use a smaller sidebar layout.</p>
                                            </div>
                                            <div class="form-check form-switch fs-4">
                                                <input class="form-check-input" type="checkbox" role="switch" id="compactSidebarSwitch">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ABOUT PANE -->
                        <div class="tab-pane fade" id="about-pane" role="tabpanel">
                            <div class="security-card profile-card-bg animate__animated animate__fadeInUp" style="background: white;">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="security-icon-wrapper me-3" style="width: 45px; height: 45px; background: rgba(13, 110, 253, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0d6efd;">
                                        <i class="bi bi-info-circle"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Account Details</h5>
                                        <p class="text-muted mb-0 small">Information about your current account.</p>
                                    </div>
                                </div>
                                <hr class="mb-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-light border">
                                            <p class="text-muted small mb-1 text-uppercase fw-semibold">Office Assigned</p>
                                            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-building me-2"></i> <?php echo htmlspecialchars($userData['office']); ?></h6>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-light border">
                                            <p class="text-muted small mb-1 text-uppercase fw-semibold">Member Since</p>
                                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar3 me-2"></i> <?php echo date('F d, Y', strtotime($userData['created_at'])); ?></h6>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <div class="p-3 rounded-4 bg-light border">
                                            <p class="text-muted small mb-1 text-uppercase fw-semibold">Account ID</p>
                                            <h6 class="fw-bold mb-0 text-dark">USR-<?php echo str_pad($userData['id'], 5, '0', STR_PAD_LEFT); ?></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    const compactSidebarSwitch = document.getElementById('compactSidebarSwitch');
    
    // Check for saved dark mode preference
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.documentElement.classList.add('dark-mode');
        if (darkModeSwitch) darkModeSwitch.checked = true;
    }

    if (darkModeSwitch) {
        darkModeSwitch.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.documentElement.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    }

    // Sidebar compact logic
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        if (compactSidebarSwitch) compactSidebarSwitch.checked = true;
    }

    if (compactSidebarSwitch) {
        compactSidebarSwitch.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.classList.add('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', 'true');
            } else {
                document.documentElement.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', 'false');
            }
        });
    }
});
</script>

</body>
</html>
