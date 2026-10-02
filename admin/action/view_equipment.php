<?php
include "../../config.php";

if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] != "admin") {
    // redirect unauthorized users back to login
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

if (!$id) {
    header("Location: ../inventory.php");
    exit;
}

$query = mysqli_query($conn, "SELECT * FROM ict_equipment_inventory WHERE id = '$id'");
$equipment = mysqli_fetch_assoc($query);

if (!$equipment) {
    header("Location: ../inventory.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'View Equipment - ICTMIS'; ?>
<?php $assetPath = '../../assest/css/admin'; ?>
<?php include '../components/head.php'; ?>
</head>
<body>
<div class="d-flex">
    <!-- SIDEBAR -->
    <?php include '../components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <?php include '../components/header.php'; ?>

        <!-- WELCOME HEADER -->
        <div class="welcome-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>View Equipment Details</h1>
                    <p class="mb-0">Detailed view of ICT equipment for <?php echo htmlspecialchars($equipment['office_name']); ?>.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../inventory.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i> Back to Inventory
                    </a>
                </div>
            </div>
        </div>

        <div class="card animate__animated animate__fadeIn">
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Office Name</label>
                                        <p class="form-control-plaintext border-bottom pb-2">
                                            <i class="bi bi-building me-2" style="color: #667eea;"></i>
                                            <?php echo htmlspecialchars($equipment['office_name']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Date Submitted</label>
                                        <p class="form-control-plaintext border-bottom pb-2">
                                            <i class="bi bi-calendar me-2" style="color: #667eea;"></i>
                                            <?php echo date('M d, Y', strtotime($equipment['date_submitted'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- COMPUTER EQUIPMENT SECTION -->
                            <div class="card mb-4" style="background-color: #f8f9fa;">
                                <div class="card-header" style="background-color: #667eea; color: white;">
                                    <i class="bi bi-pc me-2"></i>Computer Equipment
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Item Type</label>
                                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($equipment['item']); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Units</label>
                                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($equipment['units']); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Brand</label>
                                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($equipment['brand'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Processor</label>
                                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($equipment['processor'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">RAM</label>
                                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($equipment['ram'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">HDD</label>
                                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($equipment['hdd'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">SSD</label>
                                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($equipment['ssd'] ?? 'N/A'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4" style="background-color: #f8f9fa;">
                                <div class="card-header" style="background-color: #667eea; color: white;">
                                    <i class="bi bi-printer me-2"></i>Printers
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6 class="text-muted">Inkjet Printer</h6>
                                                <p class="display-6 mb-1">
                                                    <span class="badge bg-info">
                                                        <i class="bi bi-printer me-1"></i>
                                                        <?php echo $equipment['inkjet_printer']; ?>
                                                    </span>
                                                </p>
                                                <small class="text-muted"><?php echo htmlspecialchars($equipment['inkjet_printer_model'] ?: 'No model'); ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6 class="text-muted">Deskjet Printer</h6>
                                                <p class="display-6 mb-1">
                                                    <span class="badge bg-info">
                                                        <i class="bi bi-printer me-1"></i>
                                                        <?php echo $equipment['deskjet_printer']; ?>
                                                    </span>
                                                </p>
                                                <small class="text-muted"><?php echo htmlspecialchars($equipment['deskjet_printer_model'] ?: 'No model'); ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6 class="text-muted">Dot Matrix Printer</h6>
                                                <p class="display-6 mb-1">
                                                    <span class="badge bg-info">
                                                        <i class="bi bi-printer me-1"></i>
                                                        <?php echo $equipment['dotmatrix_printer']; ?>
                                                    </span>
                                                </p>
                                                <small class="text-muted"><?php echo htmlspecialchars($equipment['dotmatrix_printer_model'] ?: 'No model'); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card" style="background-color: #f8f9fa;">
                                <div class="card-header" style="background-color: #667eea; color: white;">
                                    <i class="bi bi-wifi me-2"></i>Network Equipment
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6 class="text-muted">Switch Hubs</h6>
                                                <p class="display-6 mb-1">
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-wifi me-1"></i>
                                                        <?php echo $equipment['switch_hubs']; ?>
                                                    </span>
                                                </p>
                                                <small class="text-muted"><?php echo htmlspecialchars($equipment['switch_hubs_model'] ?: 'No model'); ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6 class="text-muted">Routers</h6>
                                                <p class="display-6 mb-1">
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-diagram-3 me-1"></i>
                                                        <?php echo $equipment['routers']; ?>
                                                    </span>
                                                </p>
                                                <small class="text-muted"><?php echo htmlspecialchars($equipment['routers_model'] ?: 'No model'); ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <h6 class="text-muted">Modem</h6>
                                                <p class="display-6 mb-1">
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-modem me-1"></i>
                                                        <?php echo $equipment['modem']; ?>
                                                    </span>
                                                </p>
                                                <small class="text-muted"><?php echo htmlspecialchars($equipment['modem_model'] ?: 'No model'); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="text-muted small">
                                    <i class="bi bi-clock-history me-1"></i>
                                    Created: <?php echo $equipment['created_at']; ?> | 
                                    Last Updated: <?php echo $equipment['updated_at']; ?>
                                </p>
                            </div>
                        </div>
        </div>
    </div>
</div>

</body>
</html>
