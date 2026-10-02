<?php
include "../config.php";
include "../config/functions.php";
include "../config/auth_check.php";

$email = $_SESSION['email'];
$adminQuery = safeQuery($conn, "SELECT * FROM admin WHERE email = '$email'");
$adminData = mysqli_fetch_assoc($adminQuery);

$success = "";
$error = "";

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_pass = md5($_POST['current_password']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($current_pass === $adminData['password']) {
        if ($new_pass === $confirm_pass) {
            $hashed_new_pass = md5($new_pass);
            $updateQuery = "UPDATE admin SET password = '$hashed_new_pass' WHERE id = '{$adminData['id']}'";
            if (mysqli_query($conn, $updateQuery)) {
                $success = "Password has been changed successfully!";
            } else {
                $error = "System error: Failed to update password.";
            }
        } else {
            $error = "The new passwords you entered do not match.";
        }
    } else {
        $error = "The current password you entered is incorrect.";
    }
}

// Handle Database Backup
if (isset($_POST['backup_database'])) {
    include "../config/db.php";
    
    $tables = array();
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    $return = '';
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SELECT * FROM $table");
        $num_fields = mysqli_num_fields($result);
        
        $return .= "DROP TABLE IF EXISTS $table;";
        $row2 = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE $table"));
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysqli_fetch_row($result)) {
                $return .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    
    $filename = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $return;
    exit;
}

// Handle Database Import
if (isset($_POST['import_database'])) {
    include "../config/db.php";
    
    if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] == 0) {
        $file_path = $_FILES['sql_file']['tmp_name'];
        $file_content = file_get_contents($file_path);
        
        $sql_statements = explode(';', $file_content);
        
        foreach ($sql_statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                mysqli_query($conn, $statement);
            }
        }
        
        $success = "Database imported successfully!";
    } else {
        $error = "Error uploading file. Please try again.";
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
</head>

<body>

<div class="d-flex">
    <!-- SIDEBAR -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <?php include 'components/header.php'; ?>
        
        <!-- PAGE HEADER -->
        <div class="welcome-header futuristic-header">
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

        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-lg-3">
                    <!-- SETTINGS NAVIGATION -->
                    <div class="security-card profile-card-bg mb-4 p-3 animate__animated animate__fadeInLeft">
                        <div class="nav flex-column nav-pills" id="settings-tabs" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active d-flex align-items-center mb-2 py-3" id="security-tab" data-bs-toggle="pill" data-bs-target="#security-pane" type="button" role="tab">
                                <i class="bi bi-shield-lock me-3 fs-5"></i>
                                <span>Security & Login</span>
                            </button>
                            <button class="nav-link d-flex align-items-center mb-2 py-3" id="preferences-tab" data-bs-toggle="pill" data-bs-target="#preferences-pane" type="button" role="tab">
                                <i class="bi bi-sliders2 me-3 fs-5"></i>
                                <span>System Preferences</span>
                            </button>
                            <button class="nav-link d-flex align-items-center py-3" id="database-tab" data-bs-toggle="pill" data-bs-target="#database-pane" type="button" role="tab">
                                <i class="bi bi-database me-3 fs-5"></i>
                                <span>Database Management</span>
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
                            <div class="security-card profile-card-bg animate__animated animate__fadeInUp">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="security-icon-wrapper me-3">
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
                            <div class="security-card profile-card-bg animate__animated animate__fadeInUp">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="security-icon-wrapper me-3">
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
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-light mb-3 border">
                                            <div>
                                                <h6 class="mb-1 fw-bold">Email Notifications</h6>
                                                <p class="mb-0 text-muted small">Receive system alerts via email.</p>
                                            </div>
                                            <div class="form-check form-switch fs-4">
                                                <input class="form-check-input" type="checkbox" role="switch" checked>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-light border">
                                            <div>
                                                <h6 class="mb-1 fw-bold">Compact Sidebar</h6>
                                                <p class="mb-0 text-muted small">Use a smaller sidebar layout.</p>
                                            </div>
                                            <div class="form-check form-switch fs-4">
                                                <input class="form-check-input" type="checkbox" role="switch">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ABOUT PANE -->
                        <div class="tab-pane fade" id="about-pane" role="tabpanel">
                            <div class="security-card profile-card-bg animate__animated animate__fadeInUp">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="security-icon-wrapper me-3">
                                        <i class="bi bi-info-circle"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Account Details</h5>
                                        <p class="text-muted mb-0 small">Information about your current session.</p>
                                    </div>
                                </div>
                                <hr class="mb-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-light border">
                                            <p class="text-muted small mb-1 text-uppercase fw-semibold">Account Role</p>
                                            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-shield-check me-2"></i> <?php echo strtoupper($adminData['role']); ?></h6>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-light border">
                                            <p class="text-muted small mb-1 text-uppercase fw-semibold">Member Since</p>
                                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar3 me-2"></i> <?php echo date('F d, Y', strtotime($adminData['created_at'])); ?></h6>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <div class="p-3 rounded-4 bg-light border">
                                            <p class="text-muted small mb-1 text-uppercase fw-semibold">Account ID</p>
                                            <h6 class="fw-bold mb-0 text-dark">ADM-<?php echo str_pad($adminData['id'], 5, '0', STR_PAD_LEFT); ?></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DATABASE MANAGEMENT PANE -->
                        <div class="tab-pane fade" id="database-pane" role="tabpanel">
                            <div class="security-card profile-card-bg animate__animated animate__fadeInUp">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="security-icon-wrapper me-3">
                                        <i class="bi bi-database"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Database Management</h5>
                                        <p class="text-muted mb-0 small">Backup and restore your database.</p>
                                    </div>
                                </div>
                                <hr class="mb-4">
                                
                                <div class="row g-4">
                                    <!-- DATABASE BACKUP -->
                                    <div class="col-12">
                                        <div class="p-4 rounded-4 bg-light border mb-4">
                                            <h6 class="fw-bold mb-3"><i class="bi bi-download me-2"></i> Backup Database</h6>
                                            <p class="text-muted small mb-3">Download a complete SQL backup of your database.</p>
                                            <form method="POST">
                                                <button type="submit" name="backup_database" class="btn btn-primary-custom">
                                                    <i class="bi bi-hdd-stack me-2"></i> Create Backup
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- DATABASE IMPORT -->
                                    <div class="col-12">
                                        <div class="p-4 rounded-4 bg-light border">
                                            <h6 class="fw-bold mb-3"><i class="bi bi-upload me-2"></i> Import Database</h6>
                                            <p class="text-muted small mb-3">Upload and restore a SQL backup file.</p>
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small text-uppercase tracking-wider">Select SQL File</label>
                                                    <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                                                </div>
                                                <button type="submit" name="import_database" class="btn btn-success">
                                                    <i class="bi bi-database-check me-2"></i> Import Database
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    
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
});
</script>

</body>
</html>
