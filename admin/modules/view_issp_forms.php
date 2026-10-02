<?php
include "../../config.php";
if (!isset($_SESSION['email'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SESSION['role'] != "admin" && $_SESSION['role'] != "user") {
    // redirect unauthorized users back to login
    header("Location: ../../login.php");
    exit;
}

$officeFilter = "";
if ($_SESSION['role'] == "user") {
    $officeName = mysqli_real_escape_string($conn, $_SESSION['user_office'] ?? '');
    if ($officeName !== '') {
        $officeFilter = " WHERE office_name = '$officeName'";
    }
}

// Fetch ISSP Form Statistics
// Total unique office/date combinations
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM (SELECT office_name, date_submitted FROM user_issp_form{$officeFilter} GROUP BY office_name, date_submitted) as t");
$totalForms = mysqli_fetch_assoc($totalQuery)['total'] ?? 0;

// Pending unique forms (where status is NOT 'Closed')
$pendingQuery = mysqli_query($conn, "SELECT COUNT(*) as pending FROM (SELECT office_name, date_submitted FROM user_issp_form WHERE COALESCE(form_status, 'Open') != 'Closed'" . ($officeFilter ? " AND office_name = '" . mysqli_real_escape_string($conn, $_SESSION['user_office']) . "'" : "") . " GROUP BY office_name, date_submitted) as t");
$pendingForms = mysqli_fetch_assoc($pendingQuery)['pending'] ?? 0;

// Complete unique forms (where status IS 'Closed')
$completeQuery = mysqli_query($conn, "SELECT COUNT(*) as complete FROM (SELECT office_name, date_submitted FROM user_issp_form WHERE form_status = 'Closed'" . ($officeFilter ? " AND office_name = '" . mysqli_real_escape_string($conn, $_SESSION['user_office']) . "'" : "") . " GROUP BY office_name, date_submitted) as t");
$completeForms = mysqli_fetch_assoc($completeQuery)['complete'] ?? 0;
?>



<!DOCTYPE html>
<html>
<?php $pageTitle = 'Submitted RMAPS Forms - ICTMIS'; ?>
<?php $assetPath = '../../assest/css/admin'; ?>
<?php include '../components/head.php'; ?>
<link rel="stylesheet" href="../../assest/css/admin/form.css">
<style>
    /* Override for better icon visibility */
    .watermark-icon {
        opacity: 0.15 !important; /* Fixed opacity for watermarks */
        color: #0f172a !important;
        font-size: 7.5rem !important;
        right: 5px !important;
    }
    
    .stat-icon-box i {
        font-size: 2.2rem !important; /* Larger main icons */
        font-weight: 900 !important;
        opacity: 1 !important; /* Full visibility */
    }
    
    .stat-footer i {
        font-size: 1.3rem !important; /* Larger footer icons */
        opacity: 1 !important;
    }
    
    /* Ensure colors are solid and bold */
    .stat-total .stat-icon-box i { color: #2563eb !important; }
    .stat-pending .stat-icon-box i { color: #d97706 !important; }
    .stat-complete .stat-icon-box i { color: #16a34a !important; }
    
    .stat-total .stat-footer i { color: #2563eb !important; }
    .stat-pending .stat-footer i { color: #f59e0b !important; }
    .stat-complete .stat-footer i { color: #16a34a !important; }
</style>
</head>
<body>
<div class="d-flex">
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <div class="issp-header animate__animated animate__fadeInDown">
            <div class="header-container">
                <div class="logo-left">
                    <img src="../../assest/images/logo1.png" alt="Logo">
                </div>
                <div class="header-text">
                    <h2 class="mb-0 text-uppercase tracking-wide">Submitted RMAPS Forms</h2>
                    <p class="text-muted small mb-0">List of all Information Systems Strategic Plan submissions</p>
                </div>
                <div class="logo-right">
                    <img src="../../assest/images/logo3.png" alt="ICTMIS Logo">
                </div>
            </div>
        </div>

        <!-- STATISTICS CARDS -->
        <div class="stat-cards-container mb-4">
            <div class="custom-stat-card stat-total animate__animated animate__fadeInUp">
                <div class="stat-card-main">
                    <div class="stat-icon-box">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total RMAPS Forms</div>
                        <div class="stat-value"><?php echo number_format($totalForms); ?></div>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="bi bi-graph-up-arrow"></i> System Submissions
                </div>
                <i class="bi bi-file-earmark-text-fill watermark-icon"></i>
            </div>

            <div class="custom-stat-card stat-pending animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <div class="stat-card-main">
                    <div class="stat-icon-box">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Pending Forms</div>
                        <div class="stat-value"><?php echo number_format($pendingForms); ?></div>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="bi bi-clock-history"></i> Ongoing Submissions
                </div>
                <i class="bi bi-clipboard-check-fill watermark-icon"></i>
            </div>

            <div class="custom-stat-card stat-complete animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="stat-card-main">
                    <div class="stat-icon-box">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Complete Forms</div>
                        <div class="stat-value"><?php echo number_format($completeForms); ?></div>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="bi bi-clipboard-check-fill"></i> Finished Records
                </div>
                <i class="bi bi-award-fill watermark-icon"></i>
            </div>
        </div>

        <div class="mb-4 animate__animated animate__fadeInLeft d-flex justify-content-between align-items-center">
            <a href="../form.php" class="btn btn-dark shadow-sm rounded-pill px-4 py-2">
                <i class="bi bi-arrow-left me-2"></i>Back to Form
            </a>
            <a href="view_issp_history.php" class="btn btn-success shadow-sm rounded-pill px-4 py-2">
                <i class="bi bi-clock-history me-2"></i>View History
            </a>
        </div>

        <div class="card shadow-sm animate__animated animate__fadeInUp">
            <div class="card-body p-0">
                <?php include '../api/get_issp_forms.php'; ?>
            </div>
        </div>

    </div>
</div>

<script>
    // Make table rows clickable to navigate to details page
    document.addEventListener('DOMContentLoaded', function() {
        const tableRows = document.querySelectorAll('table tbody tr.table-row');
        
        tableRows.forEach(row => {
            row.addEventListener('click', function() {
                const office = this.getAttribute('data-office');
                const date = this.getAttribute('data-date');
                
                if (office && date) {
                    window.location.href = 'view_issp_details.php?office=' + encodeURIComponent(office) + '&date=' + encodeURIComponent(date);
                }
            });
        });
    });
</script>

</body>
</html>