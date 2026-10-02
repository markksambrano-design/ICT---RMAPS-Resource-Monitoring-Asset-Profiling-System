<?php
include "../config.php";
include "../config/auth_check.php";

// Ensure form table has all required columns
$form_columns_check = [
    'office_id' => "INT NOT NULL",
    'computer_equipment' => "ENUM('desktop computer', 'laptop', 'both') NOT NULL",
    'desktop_computer_units' => "INT DEFAULT 0",
    'laptop_units' => "INT DEFAULT 0"
];

foreach ($form_columns_check as $col => $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM form LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "ALTER TABLE form ADD COLUMN $col $definition");
    }
}

// Create ict_equipment_inventory table if it doesn't exist
$create_ict_equipment_inventory = "CREATE TABLE IF NOT EXISTS ict_equipment_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    form_id INT,
    date_submitted DATE,
    item VARCHAR(100),
    units INT DEFAULT 0,
    brand VARCHAR(100),
    processor VARCHAR(100),
    ram VARCHAR(50),
    hdd VARCHAR(50),
    ssd VARCHAR(50),
    inkjet_printer INT DEFAULT 0,
    inkjet_printer_model TEXT,
    deskjet_printer INT DEFAULT 0,
    deskjet_printer_model TEXT,
    dotmatrix_printer INT DEFAULT 0,
    dotmatrix_printer_model TEXT,
    switch_hubs INT DEFAULT 0,
    switch_hubs_model TEXT,
    routers INT DEFAULT 0,
    routers_model TEXT,
    modem INT DEFAULT 0,
    modem_model TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES form(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_ict_equipment_inventory);

// Create maintenance_reports table
$create_maintenance_reports = "CREATE TABLE IF NOT EXISTS maintenance_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    source ENUM('user', 'admin') NOT NULL,
    office_name VARCHAR(255) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    problem_description TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'cancelled') DEFAULT 'pending',
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    reported_by VARCHAR(255),
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    admin_remarks TEXT
)";
mysqli_query($conn, $create_maintenance_reports);

// Ensure computer_equipment has status column
$checkStatus = mysqli_query($conn, "SHOW COLUMNS FROM computer_equipment LIKE 'status'");
if ($checkStatus && mysqli_num_rows($checkStatus) === 0) {
    mysqli_query($conn, "ALTER TABLE computer_equipment ADD COLUMN status VARCHAR(50) DEFAULT 'operational'");
}

// Ensure ict_equipment_inventory table has all required columns
$inventory_columns_check = [
    'office_name' => "VARCHAR(255) NOT NULL",
    'form_id' => "INT",
    'date_submitted' => "DATE",
    'item' => "VARCHAR(100)",
    'units' => "INT DEFAULT 0",
    'brand' => "VARCHAR(100)",
    'processor' => "VARCHAR(100)",
    'ram' => "VARCHAR(50)",
    'hdd' => "VARCHAR(50)",
    'ssd' => "VARCHAR(50)",
    'inkjet_printer' => "INT DEFAULT 0",
    'inkjet_printer_model' => "TEXT",
    'deskjet_printer' => "INT DEFAULT 0",
    'deskjet_printer_model' => "TEXT",
    'dotmatrix_printer' => "INT DEFAULT 0",
    'dotmatrix_printer_model' => "TEXT",
    'switch_hubs' => "INT DEFAULT 0",
    'switch_hubs_model' => "TEXT",
    'routers' => "INT DEFAULT 0",
    'routers_model' => "TEXT",
    'modem' => "INT DEFAULT 0",
    'modem_model' => "TEXT"
];

foreach ($inventory_columns_check as $col => $definition) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM ict_equipment_inventory LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        @mysqli_query($conn, "ALTER TABLE ict_equipment_inventory ADD COLUMN $col $definition");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'Equipment Inventory - ICTMIS'; ?>
<?php include 'components/head.php'; ?>
<link rel="stylesheet" href="../assest/css/admin/inventory.css">
<style>
    .bg-soft-success {
        background-color: #e6fffa;
    }
    .text-success {
        color: #2c7a7b !important;
    }
    .badge.bg-info {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
    }
    .badge.bg-primary {
        background-color: #ebf8ff !important;
        color: #2b6cb0 !important;
    }
    .badge.bg-warning {
        background-color: #fffaf0 !important;
        color: #c05621 !important;
    }

    /* Dark Mode Overrides for soft backgrounds */
    html.dark-mode .bg-soft-success {
        background-color: rgba(16, 185, 129, 0.1) !important;
    }
    html.dark-mode .bg-light {
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
    html.dark-mode .text-success {
        color: #34d399 !important;
    }
    html.dark-mode .badge.bg-info {
        background-color: rgba(6, 182, 212, 0.1) !important;
        color: #22d3ee !important;
    }
    html.dark-mode .badge.bg-primary {
        background-color: rgba(79, 70, 229, 0.1) !important;
        color: #818cf8 !important;
    }
    html.dark-mode .badge.bg-warning {
        background-color: rgba(245, 158, 11, 0.1) !important;
        color: #fbbf24 !important;
    }

    /* Aggressive Table Dark Mode Fix */
    html.dark-mode #inventoryTable,
    html.dark-mode #inventoryTable tr,
    html.dark-mode #inventoryTable td,
    html.dark-mode #inventoryTable th,
    html.dark-mode #formSummaryTable,
    html.dark-mode #formSummaryTable tr,
    html.dark-mode #formSummaryTable td,
    html.dark-mode #formSummaryTable th {
        background-color: transparent !important;
        color: #f8fafc !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    html.dark-mode #inventoryTable thead th,
    html.dark-mode #formSummaryTable thead th,
    html.dark-mode .second-header th {
        background-color: rgba(0, 242, 255, 0.05) !important;
        color: var(--neon-cyan) !important;
        border-color: rgba(0, 242, 255, 0.1) !important;
    }

    html.dark-mode .bg-soft-primary {
        background-color: rgba(79, 70, 229, 0.2) !important;
        color: #818cf8 !important;
    }

    html.dark-mode .bg-soft-warning {
        background-color: rgba(245, 158, 11, 0.2) !important;
        color: #fbbf24 !important;
    }

    html.dark-mode #inventoryTable tbody tr:hover td,
    html.dark-mode #formSummaryTable tbody tr:hover td {
        background-color: rgba(0, 242, 255, 0.02) !important;
    }

    /* DataTables Pagination Dark Mode Fix */
    html.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
        border-radius: 8px !important;
        padding: 5px 12px !important;
        margin-left: 5px !important;
    }

    html.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    html.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: var(--neon-cyan) !important;
        color: #000 !important;
        border-color: var(--neon-cyan) !important;
        font-weight: bold !important;
    }

    html.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: rgba(0, 242, 255, 0.2) !important;
        color: var(--neon-cyan) !important;
        border-color: var(--neon-cyan) !important;
    }

    html.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    html.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: rgba(255, 255, 255, 0.02) !important;
        color: #475569 !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
    }

    html.dark-mode .dataTables_wrapper .dataTables_info {
        color: #94a3b8 !important;
    }

    html.dark-mode .dataTables_wrapper .dataTables_filter input {
        background-color: rgba(0, 0, 0, 0.2) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
    }

    html.dark-mode .card-header.bg-white {
        background-color: rgba(255, 255, 255, 0.02) !important;
        border-bottom-color: rgba(255, 255, 255, 0.05) !important;
    }

    html.dark-mode .text-dark {
        color: #f8fafc !important;
    }

    html.dark-mode .text-muted {
        color: #94a3b8 !important;
    }

    html.dark-mode .border,
    html.dark-mode .border-end,
    html.dark-mode .border-start,
    html.dark-mode .border-top,
    html.dark-mode .border-bottom {
        border-color: rgba(255, 255, 255, 0.05) !important;
    }

    html.dark-mode #filter_year {
        color: #f8fafc !important;
    }

</style>
</head>

<body>
<div class="d-flex">
    <!-- SIDEBAR -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <?php include 'components/header.php'; ?>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
                <i class="bi bi-check-circle me-2"></i>Record deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
            <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>Error deleting record!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- WELCOME HEADER -->
        <div class="welcome-header animate__animated animate__fadeInDown">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="bi bi-box-seam me-2"></i>Equipment Inventory</h1>
                    <p class="mb-0 text-muted">Manage and monitor all ICT equipment across different offices.</p>
                </div>
                <div class="col-md-4 text-md-end d-flex gap-2 justify-content-end">
                    <button type="button" id="toggleViewBtn" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-table me-2"></i>Form Summary
                    </button>
                </div>
            </div>
        </div>

        <div id="equipmentInventoryCard" class="card shadow animate__animated animate__fadeInUp delay-1">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary fw-bold">
                    <i class="bi bi-list-ul me-2"></i>
                    ICT Equipment List
                </h5>
                <div class="d-flex align-items-center gap-3">
                    <div id="headerSearchContainer">
                        <!-- DataTables search will be moved here via JS -->
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="date-filter">
                        <form id="dateFilterForm" method="GET" class="d-flex align-items-center gap-3">
                            <div class="date-filter-container d-flex align-items-center gap-2">
                                <label for="filter_date" class="text-muted small fw-bold mb-0">
                                    <i class="bi bi-calendar-event me-1"></i>
                                </label>
                                <input type="date" name="date" id="filter_date" 
                                       value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>" 
                                       onchange="this.form.submit()">
                            </div>

                            <div class="date-filter-container d-flex align-items-center gap-2">
                                <label for="filter_year" class="text-muted small fw-bold mb-0">
                                    <i class="bi bi-calendar3 me-1"></i>
                                </label>
                                <select name="year" id="filter_year" class="form-select-sm border-0 bg-transparent fw-bold text-dark" onchange="this.form.submit()" style="cursor: pointer; outline: none;">
                                    <option value="">All Years</option>
                                    <?php 
                                    $currentYear = date('Y');
                                    for($y = 2024; $y <= $currentYear + 2; $y++) {
                                        $selected = (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : '';
                                        echo "<option value='$y' $selected>$y</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <a href="inventory.php" class="btn btn-sm btn-light border-1 px-3 fw-bold text-primary" style="border-radius: 10px;">
                                <i class="bi bi-grid-fill me-1"></i> Show All
                            </a>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="inventoryTable" class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th rowspan="2" class="border-0"><i class="bi bi-building me-1"></i> Office</th>
                                <th rowspan="2" class="border-0 text-center"><i class="bi bi-calendar3 me-1"></i> Date</th>
                                <th colspan="7" class="text-center bg-soft-primary group-start"><i class="bi bi-cpu me-1"></i> PC Specifications</th>
                                <th colspan="3" class="text-center bg-soft-success group-start"><i class="bi bi-printer me-1"></i> Printers</th>
                                <th colspan="3" class="text-center bg-soft-warning group-start"><i class="bi bi-globe me-1"></i> Networking</th>
                            </tr>
                            <tr class="second-header">
                                <th class="border-0 group-start">Item</th>
                                <th class="border-0 text-center">Units</th>
                                <th class="border-0">Brand</th>
                                <th class="border-0 v-line">Processor</th>
                                <th class="border-0 v-line">RAM</th>
                                <th class="border-0 v-line">HDD</th>
                                <th class="border-0 v-line">SSD</th>
                                <th class="border-0 text-center group-start">Inkjet</th>
                                <th class="border-0 text-center v-line">Deskjet</th>
                                <th class="border-0 text-center v-line">Dot Matrix</th>
                                <th class="border-0 text-center group-start">Hubs</th>
                                <th class="border-0 text-center v-line">Routers</th>
                                <th class="border-0 text-center v-line">Modem</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $filterDate = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
                            $filterYear = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : '';

                            $sql = "SELECT 
                                        f.office_name, 
                                        f.date_submitted, 
                                        ce.item, 
                                        ce.number_of_units as number_of_units, 
                                        ce.brand, 
                                        ce.processor, 
                                        ce.ram, 
                                        ce.hdd, 
                                        ce.ssd, 
                                        COALESCE(oe.inkjet_printer, 0) as inkjet_printer, 
                                        COALESCE(oe.deskjet_printer, 0) as deskjet_printer, 
                                        COALESCE(oe.dot_matrix_printer, 0) as dotmatrix_printer, 
                                        COALESCE(oe.switch_hubs, 0) as switch_hubs, 
                                        COALESCE(oe.routers, 0) as routers, 
                                        COALESCE(oe.modem, 0) as modem, 
                                        ce.id,
                                        f.id as form_id
                                    FROM
                                        form f
                                    LEFT JOIN computer_equipment ce ON f.id = ce.form_id
                                    LEFT JOIN other_ict_equipment oe ON f.id = oe.form_id";

                            $conditions = [];

                            if ($filterDate != '') {
                                $conditions[] = "f.date_submitted = '$filterDate'";
                            }

                            if ($filterYear != '') {
                                $conditions[] = "YEAR(f.date_submitted) = '$filterYear'";
                            }

                            if (count($conditions) > 0) {
                                $sql .= " WHERE " . implode(' AND ', $conditions);
                            }

                            $sql .= " ORDER BY f.office_name ASC, f.date_submitted DESC, f.id DESC";
                            
                            $query = mysqli_query($conn, $sql);
                            $totalRows = mysqli_num_rows($query);
                            
                            if($totalRows > 0) {
                                $rows = [];
                                while($row = mysqli_fetch_assoc($query)) {
                                    $rows[] = $row;
                                }

                                // Pre-calculate rowspan for office + date
                                $rowspans = [];
                                for($i = 0; $i < count($rows); $i++) {
                                    $key = $rows[$i]['office_name'] . '_' . $rows[$i]['date_submitted'];
                                    if (!isset($rowspans[$i])) {
                                        $count = 1;
                                        for($j = $i + 1; $j < count($rows); $j++) {
                                            $nextKey = $rows[$j]['office_name'] . '_' . $rows[$j]['date_submitted'];
                                            if ($key === $nextKey) {
                                                $count++;
                                                $rowspans[$j] = 0; // Mark as skipped
                                            } else {
                                                break;
                                            }
                                        }
                                        $rowspans[$i] = $count;
                                    }
                                }

                                foreach($rows as $index => $row) {
                                    $span = $rowspans[$index];
                                    echo "<tr>";
                                    
                                    if ($span > 0) {
                                        echo "<td rowspan='$span' class='align-middle border-end'>
                                            <div class='d-flex align-items-center'>
                                                <div class='avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2' style='width: 32px; height: 32px; border: 1px solid #e2e8f0;'>
                                                    <i class='bi bi-building text-primary small'></i>
                                                </div>
                                                <span class='fw-bold text-dark'>".htmlspecialchars($row['office_name'])."</span>
                                            </div>
                                          </td>
                                        <td rowspan='$span' class='text-center v-line align-middle border-end'>
                                            <span class='badge bg-light text-dark border'>
                                                <i class='bi bi-calendar3 me-1'></i>
                                                ".date('M d, Y', strtotime($row['date_submitted']))."
                                            </span>
                                          </td>";
                                    }

                                    echo "<td class='group-start'><span class='fw-bold text-dark'>".htmlspecialchars($row['item'] ?? 'N/A')."</span></td>
                                    <td class='text-center v-line'><span class='fw-bold text-dark'>".htmlspecialchars($row['number_of_units'] ?? '0')." UNIT(S)</span></td>
                                    <td class='v-line'><span class='fw-bold text-dark'>".htmlspecialchars($row['brand'] ?: 'N/A')."</span></td>
                                    <td class='v-line'><span class='text-muted small fw-bold'>".htmlspecialchars($row['processor'] ?? '')."</span></td>
                                    <td class='v-line'><span class='text-muted small fw-bold'>".htmlspecialchars($row['ram'] ?? '')."</span></td>
                                    <td class='v-line'><span class='text-muted small fw-bold'>".htmlspecialchars($row['hdd'] ?? '')."</span></td>
                                    <td class='v-line'><span class='text-muted small fw-bold'>".htmlspecialchars($row['ssd'] ?? '')."</span></td>";

                                    if ($span > 0) {
                                        echo "<td rowspan='$span' class='text-center group-start align-middle border-end'><span class='fw-bold text-dark'>".htmlspecialchars($row['inkjet_printer'] ?? 0)."</span></td>
                                        <td rowspan='$span' class='text-center v-line align-middle border-end'><span class='fw-bold text-dark'>".htmlspecialchars($row['deskjet_printer'] ?? 0)."</span></td>
                                        <td rowspan='$span' class='text-center v-line align-middle border-end'><span class='fw-bold text-dark'>".htmlspecialchars($row['dotmatrix_printer'] ?? 0)."</span></td>
                                        <td rowspan='$span' class='text-center group-start align-middle border-end'><span class='fw-bold text-dark'>".htmlspecialchars($row['switch_hubs'] ?? 0)."</span></td>
                                        <td rowspan='$span' class='text-center v-line align-middle border-end'><span class='fw-bold text-dark'>".htmlspecialchars($row['routers'] ?? 0)."</span></td>
                                        <td rowspan='$span' class='text-center v-line align-middle border-end'><span class='fw-bold text-dark'>".htmlspecialchars($row['modem'] ?? 0)."</span></td>";
                                    }
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- End of Equipment Inventory Card -->

        <!-- FORM SUMMARY CARD (Hidden by default) -->
        <div id="formSummaryCard" class="card shadow animate__animated animate__fadeInUp d-none">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-success fw-bold">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Form Submission Summary
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="formSummaryTable" class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Office Name</th>
                                <th>Office ID</th>
                                <th>Computer Equipment</th>
                                <th class="text-center">Desktop Units</th>
                                <th class="text-center">Laptop Units</th>
                                <th class="text-center">Date Submitted</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="formSummaryBody">
                            <?php
                            $form_sql = "SELECT id, office_name, office_id, computer_equipment, desktop_computer_units, laptop_units, date_submitted FROM form ORDER BY id DESC";
                            $form_query = mysqli_query($conn, $form_sql);
                            $totalForms = mysqli_num_rows($form_query);
                            
                            if($totalForms > 0) {
                                while($form_row = mysqli_fetch_assoc($form_query)){
                                    $equipment_badge = '';
                                    $desktop_units = (int)$form_row['desktop_computer_units'];
                                    $laptop_units = (int)$form_row['laptop_units'];

                                    if($desktop_units > 0 && $laptop_units > 0) {
                                        $equipment_badge = '<span class="badge bg-info">both</span>';
                                    } elseif($desktop_units > 0) {
                                        $equipment_badge = '<span class="badge bg-primary">desktop</span>';
                                    } elseif($laptop_units > 0) {
                                        $equipment_badge = '<span class="badge bg-warning">laptop</span>';
                                    } else {
                                        if($form_row['computer_equipment'] == 'both') {
                                            $equipment_badge = '<span class="badge bg-info">both</span>';
                                        } elseif($form_row['computer_equipment'] == 'desktop computer') {
                                            $equipment_badge = '<span class="badge bg-primary">desktop</span>';
                                        } else {
                                            $equipment_badge = '<span class="badge bg-warning">laptop</span>';
                                        }
                                    }

                                    echo "<tr>
                                        <td class='fw-bold'>".htmlspecialchars($form_row['office_name'])."</td>
                                        <td>".htmlspecialchars($form_row['office_id'])."</td>
                                        <td>".$equipment_badge."</td>
                                        <td class='text-center'>".htmlspecialchars($form_row['desktop_computer_units'])."</td>
                                        <td class='text-center'>".htmlspecialchars($form_row['laptop_units'])."</td>
                                        <td class='text-center'>
                                            <span class='badge bg-light text-dark border'>
                                                <i class='bi bi-calendar3 me-1'></i>
                                                ".date('M d, Y', strtotime($form_row['date_submitted']))."
                                            </span>
                                        </td>
                                        <td class='text-center'>
                                            <form action='action/delete_form.php' method='POST' class='d-inline delete-form-wrapper'>
                                                <input type='hidden' name='id' value='".$form_row['id']."'>
                                                <input type='hidden' name='source' value='form'>
                                                <input type='hidden' name='ajax' value='1'>
                                                <button type='submit' class='btn btn-sm btn-danger delete-form' data-id='".$form_row['id']."' data-office='".htmlspecialchars($form_row['office_name'])."'>
                                                    <i class='bi bi-trash'></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    console.log("Inventory page ready.");
    // Suppress DataTables warning popups
    $.fn.dataTable.ext.errMode = 'none';

    // Toggle View logic
    $('#toggleViewBtn').on('click', function(e) {
        e.preventDefault();
        var equipmentCard = $('#equipmentInventoryCard');
        var formSummaryCard = $('#formSummaryCard');
        var btn = $(this);
        
        if (formSummaryCard.hasClass('d-none')) {
            // Show Form Summary
            equipmentCard.addClass('d-none');
            formSummaryCard.removeClass('d-none');
            btn.html('<i class="bi bi-cpu me-2"></i>Equipment Inventory');
            btn.removeClass('btn-primary').addClass('btn-success');
            $('.welcome-header h1').html('<i class="bi bi-file-earmark-text me-2"></i>Form Summary');
            $('.welcome-header p').text('Summary of all submitted ICT equipment forms.');
            
            // Adjust DataTables
            if ($.fn.DataTable.isDataTable('#formSummaryTable')) {
                $('#formSummaryTable').DataTable().columns.adjust().draw();
            }
        } else {
            // Show Equipment Inventory
            formSummaryCard.addClass('d-none');
            equipmentCard.removeClass('d-none');
            btn.html('<i class="bi bi-table me-2"></i>Form Summary');
            btn.removeClass('btn-success').addClass('btn-primary');
            $('.welcome-header h1').html('<i class="bi bi-box-seam me-2"></i>Equipment Inventory');
            $('.welcome-header p').text('Manage and monitor all ICT equipment across different offices.');
            
            // Adjust DataTables
            if ($.fn.DataTable.isDataTable('#inventoryTable')) {
                $('#inventoryTable').DataTable().columns.adjust().draw();
            }
        }
    });

    // Initialize Inventory Table without destroying existing one
    if (!$.fn.DataTable.isDataTable('#inventoryTable')) {
        var inventoryTable = $('#inventoryTable').DataTable({
            responsive: true,
            order: [[1, 'desc']], 
            pageLength: 10,
            dom: '<"d-flex justify-content-between align-items-center mb-3"<"search-box"f><"length-menu"l>>rt<"d-flex justify-content-between align-items-center mt-3"<"info"i><"pagination"p>>',
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>'
                }
            },
            initComplete: function() {
                $('.search-box input').addClass('form-control rounded-pill');
                $('.search-box').appendTo('#headerSearchContainer');
                $('.search-box').removeClass('mb-3');
            }
        });
    }

    // Initialize Form Summary Table without destroying existing one
    if (!$.fn.DataTable.isDataTable('#formSummaryTable')) {
        var formSummaryTable = $('#formSummaryTable').DataTable({
            responsive: true,
            order: [[5, 'desc']],
            pageLength: 10,
            dom: '<"d-flex justify-content-between align-items-center mb-3"<"search-box-summary"f><"length-menu"l>>rt<"d-flex justify-content-between align-items-center mt-3"<"info"i><"pagination"p>>',
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search forms...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>'
                }
            },
            initComplete: function() {
                $('.search-box-summary input').addClass('form-control rounded-pill');
            }
        });
    }

    // Custom filter handling
    $('#filter_date').on('change', function(){
        this.form.submit();
    });

    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Check if we should start with Form Summary view
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('view') === 'summary' || (urlParams.has('msg') && urlParams.get('msg') === 'deleted')) {
        setTimeout(function() {
            if ($('#formSummaryCard').hasClass('d-none')) {
                $('#toggleViewBtn').click();
            }
        }, 100);
    }

    // Define deleteForm function globally
    window.deleteForm = function(button) {
        var $btn = $(button);
        if ($btn.attr('data-deleting')) {
            return false;
        }
        $btn.attr('data-deleting', '1');

        var formId = $btn.attr('data-id');
        var officeName = $btn.attr('data-office');
        var source = $btn.attr('data-source') || 'form';

        console.log("Delete button clicked. ID:", formId, "Office:", officeName, "Source:", source);

        if (!formId) {
            console.error("No form ID found on clicked element:", button);
            Swal.fire('Error', 'Invalid form ID. Please refresh the page.', 'error');
            $btn.removeAttr('data-deleting');
            return false;
        }

        Swal.fire({
            title: 'Delete Form?',
            text: `Are you sure you want to delete the form for "${officeName || 'this office'}"? This action cannot be undone and will delete all associated equipment records.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the record.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'action/delete_form.php',
                    method: 'POST',
                    data: {
                        id: formId,
                        source: source,
                        ajax: 1
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("AJAX Response:", response);

                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message || 'Record has been deleted successfully.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = 'inventory.php?view=summary&msg=deleted';
                            });
                        } else {
                            $btn.removeAttr('data-deleting');
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'Failed to delete the record. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        console.error("Response Text:", xhr.responseText);
                        $btn.removeAttr('data-deleting');
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while trying to delete the record. Please check the console for details.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            } else {
                $btn.removeAttr('data-deleting');
            }
        });

        return false;
    }

    // Handle delete form submission through jQuery
    $(document).on('submit', '.delete-form-wrapper', function(e) {
        if (typeof window.deleteForm === 'function') {
            e.preventDefault();
            e.stopPropagation();
            var button = $(this).find('.delete-form')[0];
            if (button) {
                window.deleteForm(button);
            }
        }
    });
});

// Fallback raw DOM click handler for delete buttons (only if JS partial load occurs)
document.addEventListener('click', function(event) {
    var button = event.target.closest('.delete-form');
    if (!button) return;
    if (typeof window.deleteForm !== 'function') {
        return;
    }
    event.preventDefault();
    event.stopPropagation();
    window.deleteForm(button);
});
</script>
</body>
</html>