<?php
include "../config.php";
include "../config/auth_check.php";

// Show the users registered via user/register.php
$usersResult = mysqli_query($conn, "SELECT id, office, first_name, last_name, email, created_at FROM users ORDER BY id ASC");
$usersCountResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$usersCount = 0;
if ($usersCountResult) {
    $countRow = mysqli_fetch_assoc($usersCountResult);
    $usersCount = $countRow['total'] ?? 0;
}

// Helper function to get initials for the avatar
function getInitials($first, $last) {
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

// Helper function to generate a consistent color based on name
function stringToColorCode($str) {
    $hash = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = ord($str[$i]) + (($hash << 5) - $hash);
    }
    $c = ($hash & 0x00FFFFFF) . str_repeat('0', 6);
    return '#' . substr(dechex($hash & 0x00FFFFFF), 0, 6);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'Users - ICTMIS'; ?>
<?php include 'components/head.php'; ?>
<style>
    /* Modern UI Custom Styles */
    .avatar-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 16px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }
    .modern-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        background: #fff;
        overflow: hidden;
    }
    .modern-card-header {
        background-color: #1e293b; /* Matches the dark header from the screenshot */
        color: white;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }
    .modern-card-header h3 {
        color: white;
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }
    .modern-table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e2e8f0;
        padding: 1rem 1.5rem;
    }
    .modern-table tbody td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .modern-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .badge-soft-primary {
        background-color: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-weight: 600;
    }
    .btn-modern-primary {
        background-color: #2563eb;
        border-color: #2563eb;
        color: white;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-modern-primary:hover {
        background-color: #1d4ed8;
        border-color: #1d4ed8;
        color: white;
    }
    .btn-modern-light {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-modern-light:hover {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    /* DataTables Customization */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #2563eb !important;
        color: white !important;
        border: 1px solid #2563eb !important;
        border-radius: 6px;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 0.375rem 0.75rem;
        margin-left: 0.5rem;
        outline: none;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }
    .page-title-box {
        padding: 1.5rem 1.5rem 0 1.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .page-title-box h4 {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        color: #0f172a;
    }
    .page-title-box .page-subtitle {
        color: #64748b;
        margin-top: 0.35rem;
        font-size: 0.9rem;
    }
    .main-content {
        background-color: #f8fafc;
        min-height: 100vh;
        flex-grow: 1;
    }
</style>
</head>
<body>
<div class="d-flex">
    <!-- SIDEBAR -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content flex-grow-1">
        <!-- TOP BAR -->
        <?php include 'components/header.php'; ?>

        <div class="container-fluid px-0">
            <!-- Breadcrumb & Header -->
            <div class="page-title-box">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                            <li class="breadcrumb-item active fw-medium" aria-current="page">Users</li>
                        </ol>
                    </nav>
                    <h4 class="mb-0 text-dark fw-bold">System Users</h4>
                    <p class="page-subtitle mb-0">Manage and view all registered users in the system.</p>
                </div>
                
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                        <div class="alert alert-success py-2 px-3 mb-0 d-flex align-items-center shadow-sm border-0" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> User deleted successfully.
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['register_success'])): ?>
                        <div class="alert alert-success py-2 px-3 mb-0 d-flex align-items-center shadow-sm border-0" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['register_success']; unset($_SESSION['register_success']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="px-4 pb-4">
                <div class="card modern-card animate__animated animate__fadeInUp">
                    <div class="modern-card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white bg-opacity-10 p-2 rounded">
                                <i class="bi bi-people fs-4 text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">Users Directory</h3>
                                <small class="text-white-50">Total Records: <?php echo htmlspecialchars($usersCount); ?></small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="register_user.php" class="btn btn-modern-primary d-flex align-items-center gap-2 shadow-sm">
                                <i class="bi bi-person-plus"></i> Add New User
                            </a>
                            <a href="?" class="btn btn-modern-light d-flex align-items-center justify-content-center" title="Refresh">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                            <button type="button" class="btn btn-modern-light d-flex align-items-center justify-content-center" onclick="window.print();" title="Print">
                                <i class="bi bi-printer"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table modern-table mb-0 w-100" id="usersTable">
                                <thead>
                                    <tr>
                                        <th width="8%">ID</th>
                                        <th width="30%">User Profile</th>
                                        <th width="20%">Office / Dept</th>
                                        <th width="25%">Email Address</th>
                                        <th width="17%">Date Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($usersCount > 0): ?>
                                    <?php while ($u = mysqli_fetch_assoc($usersResult)) { 
                                        $fullName = htmlspecialchars($u['first_name'] . ' ' . $u['last_name']);
                                        $initials = getInitials($u['first_name'], $u['last_name']);
                                        // generate color based on the name so it remains consistent
                                        $color = stringToColorCode($fullName);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-soft-primary px-2 py-1">#<?php echo htmlspecialchars($u['id']); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar-circle shadow-sm" style="background-color: <?php echo $color; ?>;">
                                                    <?php echo $initials; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo $fullName; ?></div>
                                                    <div class="small text-muted">System User</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center text-secondary">
                                                <i class="bi bi-building me-2 text-muted"></i>
                                                <?php echo htmlspecialchars($u['office']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center text-secondary">
                                                <i class="bi bi-envelope me-2 text-muted"></i>
                                                <a href="mailto:<?php echo htmlspecialchars($u['email']); ?>" class="text-decoration-none text-secondary">
                                                    <?php echo htmlspecialchars($u['email']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center text-secondary">
                                                <i class="bi bi-calendar3 me-2 text-muted"></i>
                                                <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        "pageLength": 10,
        "order": [[0, "asc"]],
        "responsive": true,
        "dom": '<"d-flex flex-wrap justify-content-between align-items-center p-4 border-bottom"<"search-box"f><"length-menu"l>>t<"d-flex flex-wrap justify-content-between align-items-center p-4"<"info text-muted small"i><"pagination"p>>',
        "language": {
            "search": "",
            "searchPlaceholder": "Search users...",
            "lengthMenu": "Show _MENU_ entries"
        },
        "drawCallback": function(settings) {
            // Add custom styling to datatables elements after draw
            $('.dataTables_filter input').addClass('form-control form-control-sm d-inline-block w-auto');
            $('.dataTables_length select').addClass('form-select form-select-sm d-inline-block w-auto');
        }
    });
});
</script>
</body>
</html>