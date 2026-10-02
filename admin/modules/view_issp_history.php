<?php
include "../../config.php";
if (!isset($_SESSION['email'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SESSION['role'] != "admin") {
    // redirect unauthorized users back to login
    header("Location: ../../login.php");
    exit;
}

$officeFilter = "";
$officeFilter_and = "";
if ($_SESSION['role'] == "user") {
    $officeName = mysqli_real_escape_string($conn, $_SESSION['user_office'] ?? '');
    if ($officeName !== '') {
        $officeFilter = " WHERE office_name = '$officeName'";
        $officeFilter_and = " AND office_name = '$officeName'";
    }
}

// Fetch ISSP History Statistics
// Total unique office/date combinations (All)
$totalQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM (SELECT office_name, date_submitted FROM user_issp_form{$officeFilter} GROUP BY office_name, date_submitted) as t");
$totalForms = mysqli_fetch_assoc($totalQuery)['total'] ?? 0;

// Total Completed unique forms (where status IS 'Closed')
$completeQuery = mysqli_query($conn, "SELECT COUNT(*) as complete FROM (SELECT office_name, date_submitted FROM user_issp_form WHERE form_status = 'Closed'{$officeFilter_and} GROUP BY office_name, date_submitted) as t");
$completeForms = mysqli_fetch_assoc($completeQuery)['complete'] ?? 0;

// This Year Completed
$yearQuery = mysqli_query($conn, "SELECT COUNT(*) as total_year FROM (SELECT office_name, date_submitted FROM user_issp_form WHERE form_status = 'Closed' AND YEAR(date_submitted) = YEAR(CURDATE()){$officeFilter_and} GROUP BY office_name, date_submitted) as t");
$yearForms = mysqli_fetch_assoc($yearQuery)['total_year'] ?? 0;

// Completion Rate
$completionRate = ($totalForms > 0) ? round(($completeForms / $totalForms) * 100) : 0;
?>
<!DOCTYPE html>
<html>
<?php $pageTitle = 'RMAPS Submission History - ICTMIS'; ?>
<?php $assetPath = '../../assest/css/admin'; ?>
<?php include '../components/head.php'; ?>
<link rel="stylesheet" href="../../assest/css/admin/form.css">
<style>
    /* Additional styles for history page to match the image */
    .stat-rate .stat-icon-box { background-color: #f5f3ff; color: #7c3aed; }
    .stat-rate .stat-footer { color: #8b5cf6; }
    
    .history-header-icon {
        width: 60px;
        height: 60px;
        background: #f5f3ff;
        color: #7c3aed;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 20px;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.1);
    }
    
    .history-header-container {
        display: flex;
        align-items: center;
        background: #fff;
        padding: 25px 35px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        margin-bottom: 30px;
        border: 1px solid rgba(0,0,0,0.02);
    }
    
    .history-header-text h2 {
        font-weight: 800;
        font-size: 1.8rem;
        color: #0f172a;
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .history-header-text p {
        color: #94a3b8;
        font-weight: 500;
        margin-bottom: 0;
        font-size: 0.95rem;
    }

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
    .stat-complete .stat-icon-box i { color: #16a34a !important; }
    .stat-rate .stat-icon-box i { color: #7c3aed !important; }
    
    .stat-total .stat-footer i { color: #2563eb !important; }
    .stat-complete .stat-footer i { color: #16a34a !important; }
    .stat-rate .stat-footer i { color: #7c3aed !important; }
</style>
</head>
<body>
<div class="d-flex">
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        
        <!-- Header Section -->
        <div class="history-header-container animate__animated animate__fadeInDown">
            <div class="history-header-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="history-header-text">
                <h2>RMAPS Submission History</h2>
                <p>List of all completed Information Systems Strategic Plan submissions</p>
            </div>
        </div>

        <div class="mb-4 animate__animated animate__fadeInLeft">
            <a href="view_issp_forms.php" class="btn btn-light shadow-sm rounded-pill px-4 py-2 border">
                <i class="bi bi-arrow-left me-2"></i>Back to Pending List
            </a>
        </div>

        <!-- STATISTICS CARDS -->
        <div class="stat-cards-container mb-4">
            <div class="custom-stat-card stat-total animate__animated animate__fadeInUp">
                <div class="stat-card-main">
                    <div class="stat-icon-box">
                        <i class="bi bi-file-earmark-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Completed Forms</div>
                        <div class="stat-value"><?php echo number_format($completeForms); ?></div>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="bi bi-check-all"></i> All Completed Records
                </div>
                <i class="bi bi-file-earmark-check-fill watermark-icon"></i>
            </div>

            <div class="custom-stat-card stat-complete animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <div class="stat-card-main">
                    <div class="stat-icon-box">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">This Year</div>
                        <div class="stat-value"><?php echo number_format($yearForms); ?></div>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="bi bi-graph-up-arrow"></i> Current Year Submissions
                </div>
                <i class="bi bi-calendar3-fill watermark-icon"></i>
            </div>

            <div class="custom-stat-card stat-rate animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="stat-card-main">
                    <div class="stat-icon-box">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Completion Rate</div>
                        <div class="stat-value"><?php echo $completionRate; ?>%</div>
                    </div>
                </div>
                <div class="stat-footer">
                    <i class="bi bi-pie-chart-fill"></i> Overall Efficiency
                </div>
                <i class="bi bi-trophy-fill watermark-icon"></i>
            </div>
        </div>

        <div class="card shadow-sm border-0 animate__animated animate__fadeInUp overflow-hidden" style="border-radius: 20px;">
            <div class="card-body p-0">
                <?php include '../api/get_issp_history.php'; ?>
            </div>
        </div>

    </div>
</div>

<script>
    // Make table rows clickable to navigate to details page
    document.addEventListener('DOMContentLoaded', function() {
        const tableRows = document.querySelectorAll('table tbody tr.table-row');
        
        tableRows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking on a button or inside an action cell
                if (e.target.closest('.btn') || e.target.closest('td:last-child')) {
                    return;
                }

                const office = this.getAttribute('data-office');
                const date = this.getAttribute('data-date');
                
                if (office && date) {
                    window.location.href = 'view_issp_details.php?office=' + encodeURIComponent(office) + '&date=' + encodeURIComponent(date);
                }
            });
        });

        // Handle Delete History Button with SweetAlert
        const deleteBtns = document.querySelectorAll('.delete-history-btn');
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const deleteUrl = this.getAttribute('href');
                const office = this.getAttribute('data-office');
                const date = this.getAttribute('data-date');

                Swal.fire({
                    title: 'Delete History Record?',
                    text: `Are you sure you want to delete the record for "${office}" dated ${date}? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = deleteUrl;
                    }
                });
            });
        });
    });
</script>

</body>
</html>