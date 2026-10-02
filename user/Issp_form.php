<?php
date_default_timezone_set('Asia/Manila');
include "../config.php";

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Ensure the user_issp_form table exists with ALL required columns
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

// Check and add missing columns if they don't exist
$columns_to_check = [
    'system1', 'system2', 'system3', 'system4', 'system5',
    'proposed_system1', 'proposed_system2', 'proposed_system3', 'proposed_system4', 'proposed_system5',
    'inkjet_printer', 'deskjet_printer', 'dotmatrix_printer', 'switch_hubs', 'routers', 'modem',
    'inkjet_printer_model', 'dotmatrix_printer_model', 'deskjet_printer_model', 'routers_model', 'switch_hubs_model', 'modem_model',
    'form_status', 'ssd', 'status', 'equipment_image'
];

foreach ($columns_to_check as $column) {
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM user_issp_form LIKE '$column'");
    if ($check_column && mysqli_num_rows($check_column) == 0) {
        mysqli_query($conn, "ALTER TABLE user_issp_form ADD COLUMN $column TEXT");
    }
}

$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userOffice = isset($_SESSION['user_office']) ? $_SESSION['user_office'] : 'Municipal Office';

$current_year = date('Y');
$office_trimmed = trim($userOffice);

// Cleanup stale ict_forms entries for the current year if there is no matching user form record
$escapedOffice = mysqli_real_escape_string($conn, $office_trimmed);
$cleanup_ict = mysqli_query($conn, "SELECT id, date_submitted FROM ict_forms WHERE LOWER(TRIM(office_name)) = LOWER('$escapedOffice') AND YEAR(date_submitted) = '$current_year'");
if ($cleanup_ict) {
    while ($ict_row = mysqli_fetch_assoc($cleanup_ict)) {
        $dateSubmitted = mysqli_real_escape_string($conn, $ict_row['date_submitted']);
        $check_match = mysqli_query($conn, "SELECT id FROM user_issp_form WHERE LOWER(TRIM(office_name)) = LOWER('$escapedOffice') AND date_submitted = '$dateSubmitted' LIMIT 1");
        if ($check_match && mysqli_num_rows($check_match) === 0) {
            mysqli_query($conn, "DELETE FROM ict_forms WHERE id = '" . mysqli_real_escape_string($conn, $ict_row['id']) . "'");
        }
    }
}

// Check if office already has a form for the current year in admin, user, or ICT submission tables
$check_admin_open = mysqli_query($conn, "SELECT id FROM form WHERE LOWER(TRIM(office_name)) = LOWER('$escapedOffice') AND YEAR(date_submitted) = '$current_year' LIMIT 1");
$check_user_open = mysqli_query($conn, "SELECT id FROM user_issp_form WHERE LOWER(TRIM(office_name)) = LOWER('$escapedOffice') AND YEAR(date_submitted) = '$current_year' LIMIT 1");
$check_ict_open = mysqli_query($conn, "SELECT id FROM ict_forms WHERE LOWER(TRIM(office_name)) = LOWER('$escapedOffice') AND YEAR(date_submitted) = '$current_year' LIMIT 1");
$has_existing_form = mysqli_num_rows($check_admin_open) > 0 || mysqli_num_rows($check_user_open) > 0 || mysqli_num_rows($check_ict_open) > 0;

if(isset($_POST['submit'])){
    // Prevent submission if a pending open form still exists
    if ($has_existing_form) {
        $error_message = "Your office has already submitted a form. You cannot submit another one unless the previous one is processed and closed.";
        $error = true;
    } else {
        // Sanitize all inputs to prevent SQL injection
        $office = mysqli_real_escape_string($conn, $_POST['office'] ?? '');
        $date = mysqli_real_escape_string($conn, $_POST['date'] ?? '');
        
        // Check if form already exists for this office in the same year
        $submission_year = date('Y', strtotime($date));
        $office_trimmed = trim($office);
        $check_admin = mysqli_query($conn, "SELECT id FROM form WHERE LOWER(TRIM(office_name)) = LOWER('$office_trimmed') AND YEAR(date_submitted) = '$submission_year'");
        $check_user = mysqli_query($conn, "SELECT id FROM user_issp_form WHERE LOWER(TRIM(office_name)) = LOWER('$office_trimmed') AND YEAR(date_submitted) = '$submission_year'");
        $check_ict = mysqli_query($conn, "SELECT id FROM ict_forms WHERE LOWER(TRIM(office_name)) = LOWER('$office_trimmed') AND YEAR(date_submitted) = '$submission_year'");

        if (mysqli_num_rows($check_admin) > 0 || mysqli_num_rows($check_user) > 0 || mysqli_num_rows($check_ict) > 0) {
            $error_message = "Your office '" . $office . "' has already submitted a form for the year " . $submission_year . ". New submissions are only allowed next year.";
            $error = true;
        } else {
            // Process equipment arrays
            $items = $_POST['item'] ?? [];
            $units_arr = $_POST['units'] ?? [];
            $brands = $_POST['brand'] ?? [];
            $processors = $_POST['processor'] ?? [];
            $rams = $_POST['ram'] ?? [];
            $hdds = $_POST['hdd'] ?? [];
            $ssds = $_POST['ssd'] ?? [];
            
            // Equipment images - Store only filename
            $equipment_images = $_FILES['equipment_image'] ?? null;
            $uploaded_images = [];
            $upload_dir = "../uploads/equipment_images/";
            
            if ($equipment_images && isset($equipment_images['name']) && is_array($equipment_images['name'])) {
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                foreach ($equipment_images['name'] as $key => $image_name) {
                    $uploaded_images[$key] = null;
                    if (!empty($image_name) && isset($equipment_images['tmp_name'][$key]) && $equipment_images['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $equipment_images['tmp_name'][$key];
                        $file_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (in_array($file_ext, $allowed_exts)) {
                            $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($image_name, PATHINFO_FILENAME));
                            $new_filename = time() . '_' . $key . '_' . $safe_name . '.' . $file_ext;
                            $target_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($tmp_name, $target_path)) {
                                chmod($target_path, 0644);
                                $uploaded_images[$key] = $new_filename;
                            }
                        }
                    }
                }
            }
            
            // Computerized Systems - Correctly process arrays
            $systems = $_POST['systems'] ?? [];
            $system1 = isset($systems[0]) ? mysqli_real_escape_string($conn, $systems[0]) : '';
            $system2 = isset($systems[1]) ? mysqli_real_escape_string($conn, $systems[1]) : '';
            $system3 = isset($systems[2]) ? mysqli_real_escape_string($conn, $systems[2]) : '';
            $system4 = isset($systems[3]) ? mysqli_real_escape_string($conn, $systems[3]) : '';
            $system5 = isset($systems[4]) ? mysqli_real_escape_string($conn, $systems[4]) : '';

            $proposed_systems_arr = $_POST['proposed_systems'] ?? [];
            $proposed_system1 = isset($proposed_systems_arr[0]) ? mysqli_real_escape_string($conn, $proposed_systems_arr[0]) : '';
            $proposed_system2 = isset($proposed_systems_arr[1]) ? mysqli_real_escape_string($conn, $proposed_systems_arr[1]) : '';
            $proposed_system3 = isset($proposed_systems_arr[2]) ? mysqli_real_escape_string($conn, $proposed_systems_arr[2]) : '';
            $proposed_system4 = isset($proposed_systems_arr[3]) ? mysqli_real_escape_string($conn, $proposed_systems_arr[3]) : '';
            $proposed_system5 = isset($proposed_systems_arr[4]) ? mysqli_real_escape_string($conn, $proposed_systems_arr[4]) : '';

            // Process Printer Devices (Dropdown + Image)
            $printer_types = $_POST['printer_type'] ?? [];
            $printer_models = $_POST['printer_model'] ?? [];
            $printer_quantities = $_POST['printer_quantity'] ?? [];
            $printer_images = $_FILES['printer_image'] ?? null;
            
            // Process printer images upload
            $printer_image_filenames = [];
            if ($printer_images && isset($printer_images['name']) && is_array($printer_images['name'])) {
                foreach ($printer_images['name'] as $key => $img_name) {
                    $printer_image_filenames[$key] = null;
                    if (!empty($img_name) && isset($printer_images['tmp_name'][$key]) && $printer_images['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $printer_images['tmp_name'][$key];
                        $file_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (in_array($file_ext, $allowed_exts)) {
                            $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($img_name, PATHINFO_FILENAME));
                            $new_filename = 'printer_' . time() . '_' . $key . '_' . $safe_name . '.' . $file_ext;
                            $target_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($tmp_name, $target_path)) {
                                chmod($target_path, 0644);
                                $printer_image_filenames[$key] = $new_filename;
                            }
                        }
                    }
                }
            }
            
            // Group printer data by type
            $printer_data = [];
            foreach ($printer_types as $idx => $type) {
                if (!empty($type)) {
                    $qty = isset($printer_quantities[$idx]) ? (int)$printer_quantities[$idx] : 1;
                    $printer_data[$type]['models'][] = ($printer_models[$idx] ?? '') . " ($qty units)";
                    $printer_data[$type]['images'][] = $printer_image_filenames[$idx] ?? null;
                    $printer_data[$type]['total_qty'] = ($printer_data[$type]['total_qty'] ?? 0) + $qty;
                }
            }
            
            // Calculate totals and model/brand strings
            $inkjet_printer = 0;
            $inkjet_printer_label = '';
            $deskjet_printer = 0;
            $deskjet_printer_label = '';
            $dotmatrix_printer = 0;
            $dotmatrix_printer_label = '';
            
            foreach ($printer_data as $type => $data) {
                $total_qty = $data['total_qty'];
                $detail_string = implode(', ', $data['models']);
                
                if (stripos($type, 'Inkjet') !== false) {
                    $inkjet_printer = $total_qty;
                    $inkjet_printer_label = $detail_string;
                } elseif (stripos($type, 'Deskjet') !== false || stripos($type, 'DeskJet') !== false) {
                    $deskjet_printer = $total_qty;
                    $deskjet_printer_label = $detail_string;
                } elseif (stripos($type, 'Dot Matrix') !== false || stripos($type, 'Dotmatrix') !== false) {
                    $dotmatrix_printer = $total_qty;
                    $dotmatrix_printer_label = $detail_string;
                }
            }
            
            // Process Network Devices (Dropdown + Image)
            $network_types = $_POST['network_type'] ?? [];
            $network_models = $_POST['network_model'] ?? [];
            $network_quantities = $_POST['network_quantity'] ?? [];
            $network_images = $_FILES['network_image'] ?? null;
            
            // Process network images upload
            $network_image_filenames = [];
            if ($network_images && isset($network_images['name']) && is_array($network_images['name'])) {
                foreach ($network_images['name'] as $key => $img_name) {
                    $network_image_filenames[$key] = null;
                    if (!empty($img_name) && isset($network_images['tmp_name'][$key]) && $network_images['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $network_images['tmp_name'][$key];
                        $file_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (in_array($file_ext, $allowed_exts)) {
                            $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($img_name, PATHINFO_FILENAME));
                            $new_filename = 'network_' . time() . '_' . $key . '_' . $safe_name . '.' . $file_ext;
                            $target_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($tmp_name, $target_path)) {
                                chmod($target_path, 0644);
                                $network_image_filenames[$key] = $new_filename;
                            }
                        }
                    }
                }
            }
            
            // Group network data by type
            $network_data = [];
            foreach ($network_types as $idx => $type) {
                if (!empty($type)) {
                    $qty = isset($network_quantities[$idx]) ? (int)$network_quantities[$idx] : 1;
                    $network_data[$type]['models'][] = ($network_models[$idx] ?? '') . " ($qty units)";
                    $network_data[$type]['images'][] = $network_image_filenames[$idx] ?? null;
                    $network_data[$type]['total_qty'] = ($network_data[$type]['total_qty'] ?? 0) + $qty;
                }
            }
            
            // Calculate totals for network devices
            $switch_hubs = 0;
            $switch_hubs_label = '';
            $routers = 0;
            $routers_label = '';
            $modem = 0;
            $modem_label = '';
            
            foreach ($network_data as $type => $data) {
                $total_qty = $data['total_qty'];
                $detail_string = implode(', ', $data['models']);
                
                $type_lower = strtolower($type);
                if (strpos($type_lower, 'switch') !== false || strpos($type_lower, 'hub') !== false) {
                    $switch_hubs = $total_qty;
                    $switch_hubs_label = $detail_string;
                } elseif (strpos($type_lower, 'router') !== false) {
                    $routers = $total_qty;
                    $routers_label = $detail_string;
                } elseif (strpos($type_lower, 'modem') !== false) {
                    $modem = $total_qty;
                    $modem_label = $detail_string;
                }
            }

            // Ensure arrays are properly formatted
            if(!is_array($items)) {
                $items = [$items];
                $units_arr = [$units_arr];
                $brands = [$brands];
                $processors = [$processors];
                $rams = [$rams];
                $hdds = [$hdds];
                $ssds = [$ssds];
            }

            $all_success = true;

            // Loop through items to insert into user_issp_form
            foreach($items as $key => $val) {
                $item = mysqli_real_escape_string($conn, $items[$key] ?? '');
                $units = isset($units_arr[$key]) ? (int)$units_arr[$key] : 1;
                $brand = mysqli_real_escape_string($conn, $brands[$key] ?? '');
                $processor = mysqli_real_escape_string($conn, $processors[$key] ?? '');
                $ram = mysqli_real_escape_string($conn, $rams[$key] ?? '');
                $hdd = mysqli_real_escape_string($conn, $hdds[$key] ?? '');
                $ssd = mysqli_real_escape_string($conn, $ssds[$key] ?? '');
                $image_filename = isset($uploaded_images[$key]) ? mysqli_real_escape_string($conn, $uploaded_images[$key]) : null;
                
                $insert = mysqli_query($conn,"INSERT INTO user_issp_form (office_name, date_submitted, item, units, brand, processor, ram, hdd, ssd, equipment_image, system1, system2, system3, system4, system5, proposed_system1, proposed_system2, proposed_system3, proposed_system4, proposed_system5, inkjet_printer, deskjet_printer, dotmatrix_printer, switch_hubs, routers, modem, form_status, inkjet_printer_model, deskjet_printer_model, dotmatrix_printer_model, switch_hubs_model, routers_model, modem_model) 
                VALUES ('$office', '$date', '$item', $units, '$brand', '$processor', '$ram', '$hdd', '$ssd', " . ($image_filename ? "'$image_filename'" : "NULL") . ", '$system1', '$system2', '$system3', '$system4', '$system5', '$proposed_system1', '$proposed_system2', '$proposed_system3', '$proposed_system4', '$proposed_system5', $inkjet_printer, $deskjet_printer, $dotmatrix_printer, $switch_hubs, $routers, $modem, 'Open', '$inkjet_printer_label', '$deskjet_printer_label', '$dotmatrix_printer_label', '$switch_hubs_label', '$routers_label', '$modem_label')");

                if(!$insert){
                    $all_success = false;
                    echo mysqli_error($conn);
                }
            }

            // ALSO INSERT INTO ict_forms ONCE
            $check_ict_form = mysqli_query($conn, "SELECT id FROM ict_forms WHERE office_name = '$office' AND date_submitted = '$date' LIMIT 1");
            if (mysqli_num_rows($check_ict_form) == 0) {
                mysqli_query($conn, "INSERT INTO ict_forms (office_name, date_submitted, form_status) VALUES ('$office', '$date', 'Open')");
            }

            if($all_success){
                $success = true;
                $check_existing = mysqli_query($conn, "SELECT id FROM ict_forms WHERE office_name = '" . mysqli_real_escape_string($conn, $userOffice) . "' AND COALESCE(form_status, 'Open') = 'Open' LIMIT 1");
                $has_existing_form = mysqli_num_rows($check_existing) > 0;
            } else {
                $error = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $pageTitle = 'RMAPS Form - ICTMIS'; ?>
    <?php include 'components/head.php'; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f0f0f0; }
        .main-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); background: #f0f0f0; display: flex; flex-direction: column; }
        .main-content > div:first-child { position: fixed; top: 0; left: var(--sidebar-width); right: 0; z-index: 1000; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-collapsed .main-content > div:first-child { left: var(--sidebar-collapsed-width); }
        .content-wrapper { flex: 1; padding: 30px; padding-top: 70px; overflow-y: auto; margin-top: 0; }
        .issp-header { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); padding: 35px 48px; margin-top: 35px; margin-bottom: 40px; border-radius: 24px; box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.12); border: 1px solid rgba(79, 70, 229, 0.15); position: relative; }
        .issp-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #4f46e5, #818cf8, #c7d2fe, #818cf8, #4f46e5); border-radius: 24px 24px 0 0; }
        .header-container { display: flex; align-items: center; justify-content: space-between; gap: 40px; position: relative; z-index: 1; }
        .logo-left, .logo-right { flex-shrink: 0; display: flex; align-items: center; }
        .lgu-logo, .ict-logo { height: 85px; width: auto; position: relative; top: 18px; transition: transform 0.3s ease; }
        .header-text { flex: 1; text-align: center; }
        .header-text h2 { font-size: 28px; font-weight: 700; background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #1e293b 100%); -webkit-background-clip: text; background-clip: text; color: #1e293b; margin: 0; text-transform: uppercase; }
        .card { background: white; border: none; border-radius: 16px; margin-bottom: 28px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .card-header { background: #0f172a; color: white; padding: 16px 28px; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 12px; }
        .card-body { padding: 28px; }
        .form-control, .form-select { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 14px; transition: all 0.3s ease; }
        .form-control:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); outline: none; }
        .form-label { font-weight: 500; color: #374151; margin-bottom: 8px; font-size: 14px; }
        .btn-submit { background: #4f46e5; color: white; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; transition: all 0.3s ease; }
        .btn-submit:hover { background: #4338ca; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(79, 70, 229, 0.3); }
        .text-center { margin: 30px 0 20px; text-align: center; }
        
        .tech-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .tech-card .card-header { background: #0f172a !important; color: #ffffff !important; }
        .section-box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 22px; margin-bottom: 20px; background: #f8fafc; }
        .tech-title { font-weight: 500; font-size: 14px; letter-spacing: 1px; color: #0369a1; margin-bottom: 16px; text-transform: uppercase; }
        .tech-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 10px; overflow: hidden; }
        .tech-table th { font-weight: 500; font-size: 12px; color: #475569; text-transform: uppercase; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 14px; text-align: left; }
        .tech-table td { border: 1px solid #e2e8f0; vertical-align: middle; padding: 12px 14px; }
        .tech-table td:first-child { width: 200px; padding-left: 18px; }
        .tech-table td:nth-child(2) { width: 200px; }
        .tech-table td:nth-child(3) { width: 200px; }
        .tech-table td:nth-child(4) { width: 130px; }
        .tech-table td:last-child { width: 80px; white-space: nowrap; text-align: center; }
        .tech-label { font-weight: 600; color: #334155; }
        .tech-input { width: 100%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; }
        .image-preview-small { max-width: 80px; max-height: 50px; margin-top: 5px; border-radius: 6px; display: none; }
        .action-btns { display: flex; justify-content: center; align-items: center; gap: 6px; }
        .action-btns button { min-width: 32px; height: 32px; border-radius: 8px; font-size: 14px; }
        .btn-success { background: #22c55e; border: none; color: white; }
        .btn-danger { background: #ef4444; border: none; color: white; }
        .btn-sm { padding: 4px 12px; font-size: 12px; }
        .units-input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
        .missing-field { border: 2px solid #dc3545 !important; background-color: #fff8f8 !important; }
        .storage-disabled { opacity: 0.5; cursor: not-allowed; }
        @media (max-width: 768px) { .tech-table td:first-child { width: 140px; } .tech-table td:nth-child(2), .tech-table td:nth-child(3) { width: 150px; } }
        .remove-btn-hidden {
            display: none !important;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/components/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/components/header.php'; ?>
        <div class="content-wrapper">
            <div class="issp-header">
                <div class="header-container">
                    <div class="logo-left"><img src="../assest/images/logo1.png" class="lgu-logo" alt="LGU Logo"></div>
                    <div class="header-text"><h2>DATA FOR THE FORMULATION OF INFORMATION SYSTEM STRATEGIC PLANNING 2026-2030</h2></div>
                    <div class="logo-right"><img src="../assest/images/logo3.png" class="ict-logo" alt="ICTMIS Logo"></div>
                </div>
            </div>
            <form id="isspForm" method="POST" enctype="multipart/form-data" novalidate>
                <div class="card mb-4">
                    <div class="card-header"><i class="bi bi-building"></i>Office Information</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Office Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userOffice); ?>" readonly>
                                <input type="hidden" name="office" id="office" value="<?php echo htmlspecialchars($userOffice); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date Submitted</label>
                                <input type="date" name="date" id="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- MODIFIED: Computer Equipment with UNITS FIXED TO 1 (readonly) -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-pc me-2"></i>Computer Equipment</span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addEquipment(event)"><i class="bi bi-plus"></i> Add Item</button>
                    </div>
                    <div class="card-body" id="equipmentContainer">
                        <div class="equipment-row mb-3 border p-3 rounded">
                            <div class="row">
                                <div class="col-md-3"><label class="form-label">Image</label><input type="file" name="equipment_image[]" class="form-control" accept="image/jpeg,image/png,image/jpg"></div>
                                <div class="col-md-3"><label class="form-label">Item <span class="text-danger">*</span></label><select name="item[]" class="form-control item-select" required><option value="">Select Item</option><option value="Desktop Computer">Desktop Computer</option><option value="Laptop">Laptop</option></select></div>
                                <div class="col-md-2"><label class="form-label">No. of Units</label><input type="number" name="units[]" class="form-control units-input" value="1" required min="1"></div>
                                <div class="col-md-4"><label class="form-label">Brand <span class="text-danger">*</span></label><input type="text" name="brand[]" class="form-control brand-input" required></div>
                            </div><br>
                            <div class="row">
                            <div class="col-md-3"><label class="form-label">Processor <span class="text-danger">*</span></label><select name="processor[]" class="form-control processor-input" required><option value="">-- Select Processor --</option><option value="Intel Core i3">Intel Core i3</option><option value="Intel Core i5">Intel Core i5</option><option value="Intel Core i7">Intel Core i7</option><option value="Intel Core i9">Intel Core i9</option><option value="AMD Ryzen 3">AMD Ryzen 3</option><option value="AMD Ryzen 5">AMD Ryzen 5</option><option value="AMD Ryzen 7">AMD Ryzen 7</option><option value="AMD Ryzen 9">AMD Ryzen 9</option></select></div>
                            <div class="col-md-3"><label class="form-label">RAM <span class="text-danger">*</span></label><select name="ram[]" class="form-control ram-input" required><option value="">-- Select RAM --</option><option value="4 GB">4 GB</option><option value="8 GB">8 GB</option><option value="16 GB">16 GB</option><option value="32 GB">32 GB</option><option value="64 GB">64 GB</option></select></div>
                            <div class="col-md-3"><label class="form-label">HDD</label><select name="hdd[]" class="form-control hdd-select"><option value="">-- Select HDD --</option><option value="500 GB HDD">500 GB HDD</option><option value="1 TB HDD">1 TB HDD</option><option value="2 TB HDD">2 TB HDD</option></select></div>
                            <div class="col-md-3"><label class="form-label">SSD</label><select name="ssd[]" class="form-control ssd-select"><option value="">-- Select SSD --</option><option value="128 GB SSD">128 GB SSD</option><option value="256 GB SSD">256 GB SSD</option><option value="512 GB SSD">512 GB SSD</option><option value="1 TB SSD">1 TB SSD</option></select></div>
                            </div>
                            <div class="text-end mt-2"><button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeRow(this)"><i class="bi bi-trash"></i> Remove</button></div>
                        </div>
                    </div>
                </div>
                
                <!-- OTHER ICT EQUIPMENT SECTION -->
                <div class="card mb-4 tech-card">
                    <div class="card-header">
                        <span><i class="bi bi-cpu me-2"></i>Other ICT Equipment</span>
                    </div>
                    <div class="card-body">
                        <!-- PRINTER DEVICES -->
                        <div class="section-box">
                            <div class="tech-title"><i class="bi bi-printer me-2"></i>PRINTER DEVICES</div>
                            <div class="table-responsive">
                                <table class="table tech-table align-middle" id="printerTable">
                                    <thead>
                                        <tr>
                                            <th style="width:25%">UPLOAD IMAGE</th>
                                            <th style="width:25%">PRINTER TYPE</th>
                                            <th style="width:25%">MODEL</th>
                                            <th style="width:15%">NO. OF UNITS</th>
                                            <th style="width:10%">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody id="printer-body">
                                        <tr>
                                            <td>
                                                <input type="file" name="printer_image[]" class="form-control tech-input image-upload" accept="image/jpeg,image/png,image/jpg">
                                                <div class="image-preview-small"></div>
                                            </td>
                                            <td><select name="printer_type[]" class="form-select tech-input" required>
                                                <option value="">Select Type</option>
                                                <option value="Inkjet Printer">Inkjet Printer</option>
                                                <option value="Deskjet Printer">Deskjet Printer</option>
                                                <option value="Dot Matrix Printer">Dot Matrix Printer</option>
                                                <option value="Laser Printer">Laser Printer</option>
                                            </select></td>
                                            <td><input type="text" name="printer_model[]" class="form-control tech-input" placeholder="Model" required></td>
                                            <td><input type="number" name="printer_quantity[]" class="form-control tech-input units-input" value="1" min="1" required></td>
                                            <td class="text-center">
                                                <div class="action-btns">
                                                    <button type="button" class="btn btn-success btn-sm" onclick="addICTRow('printer')">+</button>
                                                    <button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeICTRow(this)">−</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- NETWORK DEVICES -->
                        <div class="section-box">
                            <div class="tech-title"><i class="bi bi-hdd-network me-2"></i>NETWORK DEVICES</div>
                            <div class="table-responsive">
                                <table class="table tech-table align-middle" id="networkTable">
                                    <thead>
                                        <tr>
                                            <th style="width:25%">UPLOAD IMAGE</th>
                                            <th style="width:25%">DEVICE TYPE</th>
                                            <th style="width:25%">MODEL</th>
                                            <th style="width:15%">NO. OF UNITS</th>
                                            <th style="width:10%">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody id="network-body">
                                        <tr>
                                            <td>
                                                <input type="file" name="network_image[]" class="form-control tech-input image-upload" accept="image/jpeg,image/png,image/jpg">
                                                <div class="image-preview-small"></div>
                                            </td>
                                            <td><select name="network_type[]" class="form-select tech-input" required>
                                                <option value="">Select Type</option>
                                                <option value="Switch Hubs">Switch Hubs</option>
                                                <option value="Routers">Routers</option>
                                                <option value="Modem">Modem</option>
                                                <option value="Access Point">Access Point</option>
                                                <option value="Firewall">Firewall</option>
                                            </select></td>
                                            <td><input type="text" name="network_model[]" class="form-control tech-input" placeholder="Model" required></td>
                                            <td><input type="number" name="network_quantity[]" class="form-control tech-input units-input" value="1" min="1" required></td>
                                            <td class="text-center">
                                                <div class="action-btns">
                                                    <button type="button" class="btn btn-success btn-sm" onclick="addICTRow('network')">+</button>
                                                    <button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeICTRow(this)">−</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-pc-display me-2"></i>Existing Computerized Systems</span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addSystem()">+ Add</button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered" id="systemTable">
                            <thead>
                                <tr><th width="90%">System Name</th><th width="10%">Action</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="systems[]" class="form-control" placeholder="Enter System"></td>
                                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeSystem(this)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-lightbulb me-2"></i>Proposed Systems</span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addProposedSystem()">+ Add</button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered" id="proposedSystemTable">
                            <thead>
                                <tr><th width="90%">Proposed System</th><th width="10%">Action</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="proposed_systems[]" class="form-control" placeholder="Enter Proposed System"></td>
                                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeProposedSystem(this)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <?php if ($has_existing_form): ?>
                        <div class="d-flex flex-column align-items-center">
                            <div class="alert alert-warning d-inline-block px-4 py-3 mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Submission Restricted:</strong> Your office already has a pending or submitted form.
                            </div>
                            <button type="button" class="btn btn-secondary px-5" disabled>Submit Form (Disabled)</button>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-center">
                            <button type="submit" name="submit" id="submitBtn" class="btn btn-submit px-5">
                                <i class="bi bi-check-circle"></i> Submit Form
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
<?php if(isset($success) && $success): ?>
Swal.fire({ 
    title: 'Success!', 
    text: 'Form submitted successfully!', 
    icon: 'success', 
    confirmButtonColor: '#4f46e5' 
});
<?php endif; ?>

<?php if(isset($error) && $error): ?>
Swal.fire({ 
    title: 'Error!', 
    text: '<?php echo isset($error_message) ? addslashes($error_message) : "Error submitting form!"; ?>', 
    icon: 'error', 
    confirmButtonColor: '#d33' 
});
<?php endif; ?>

<?php if(isset($has_existing_form) && $has_existing_form): ?>
Swal.fire({
    icon: 'warning',
    title: 'Form Already Exists!',
    text: 'Your office has already submitted a form for the year <?php echo $current_year; ?>. New submissions are only allowed next year.',
    confirmButtonColor: '#4f46e5'
});
<?php endif; ?>

// Helper to show/hide remove buttons
function toggleRemoveButtons(container, removeBtnClass) {
    let items;
    if (container.tagName === 'TBODY') {
        items = container.querySelectorAll('tr');
    } else {
        items = container.children;
    }
    
    let removeBtns = container.querySelectorAll(removeBtnClass);
    
    // Always hide Remove button on first item
    if (removeBtns.length > 0) {
        removeBtns[0].classList.add('remove-btn-hidden');
    }
    
    // Show Remove buttons on other items when there are multiple items
    for (let i = 1; i < removeBtns.length; i++) {
        if (items.length > 1) {
            removeBtns[i].classList.remove('remove-btn-hidden');
        } else {
            removeBtns[i].classList.add('remove-btn-hidden');
        }
    }
}

// Function to add new row for Printer or Network devices
function addICTRow(type) {
    let tbody = (type === 'printer') ? document.getElementById('printer-body') : document.getElementById('network-body');
    if (!tbody) return;
    
    let templateRow = tbody.querySelector('tr');
    if (!templateRow) return;
    
    let newRow = templateRow.cloneNode(true);
    
    // Clear input values
    newRow.querySelectorAll('input, select').forEach(field => {
        if(field.type === 'number') {
            field.value = '1';
        } else if(field.type !== 'file') {
            field.value = '';
        } else {
            field.value = '';
        }
    });
    
    // Clear image preview
    let previewDiv = newRow.querySelector('.image-preview-small');
    if(previewDiv) {
        previewDiv.innerHTML = '';
        previewDiv.style.display = 'none';
    }
    
    tbody.appendChild(newRow);
    attachImagePreview(newRow);
    setupQuantityValidation(newRow);
    toggleRemoveButtons(tbody, '.btn-danger');
}

// Function to remove row
function removeICTRow(btn) {
    let row = btn.closest('tr');
    let tbody = row.closest('tbody');
    if(tbody && tbody.querySelectorAll('tr').length > 1) {
        row.remove();
        toggleRemoveButtons(tbody, '.btn-danger');
    } else {
        alert("At least one row is required.");
    }
}

// Function to attach image preview
function attachImagePreview(row) {
    let fileInput = row.querySelector('.image-upload');
    let previewDiv = row.querySelector('.image-preview-small');
    
    if(fileInput && previewDiv) {
        fileInput.addEventListener('change', function(e) {
            if(e.target.files && e.target.files[0]) {
                let reader = new FileReader();
                reader.onload = function(ev) {
                    previewDiv.innerHTML = `<img src="${ev.target.result}" style="max-width:70px; max-height:45px; border-radius:6px; margin-top:5px;">`;
                    previewDiv.style.display = 'block';
                };
                reader.readAsDataURL(e.target.files[0]);
            } else {
                previewDiv.innerHTML = '';
                previewDiv.style.display = 'none';
            }
        });
    }
}

// Attach image preview to existing rows
document.querySelectorAll('#printer-body tr, #network-body tr').forEach(row => attachImagePreview(row));

// Equipment functions
function addEquipment(e) {
    if(e) e.preventDefault();
    let container = document.getElementById("equipmentContainer");
    let template = document.querySelector(".equipment-row");
    let newRow = template.cloneNode(true);
    newRow.querySelectorAll("input,select").forEach(field => {
        if(field.type === 'number') {
            field.value = '1';
        } else if(field.type !== 'file') {
            field.value = '';
        }
        if(field.tagName === 'SELECT') {
            field.selectedIndex = 0;
        }
        if(field.classList && (field.classList.contains('hdd-select') || field.classList.contains('ssd-select'))) {
            field.disabled = false;
            field.value = '';
        }
    });
    container.appendChild(newRow);
    initStorageMutualExclusivity(newRow);
    setupQuantityValidation(newRow);
    toggleRemoveButtons(container, '.btn-danger');
}

function initStorageMutualExclusivity(row) {
    let hdd = row.querySelector('select[name="hdd[]"]');
    let ssd = row.querySelector('select[name="ssd[]"]');
    if(!hdd || !ssd) return;
    let hddChange = () => {
        if(hdd.value) {
            ssd.disabled = true;
            ssd.classList.add('storage-disabled');
            ssd.value = '';
        } else {
            ssd.disabled = false;
            ssd.classList.remove('storage-disabled');
        }
    };
    let ssdChange = () => {
        if(ssd.value) {
            hdd.disabled = true;
            hdd.classList.add('storage-disabled');
            hdd.value = '';
        } else {
            hdd.disabled = false;
            hdd.classList.remove('storage-disabled');
        }
    };
    hdd.onchange = hddChange;
    ssd.onchange = ssdChange;
    hddChange();
    ssdChange();
}

function setupQuantityValidation(row) {
    let qtyInput = row.querySelector('.units-input');
    if(!qtyInput) return;
    qtyInput.addEventListener('input', function() {
        let val = parseInt(this.value);
        if(this.value !== '') {
            if(val < 1) {
                this.value = 1;
                alert("Quantity cannot be less than 1");
            }
        }
    });
}

function initAll() {
    document.querySelectorAll('#equipmentContainer .equipment-row').forEach(row => {
        initStorageMutualExclusivity(row);
        setupQuantityValidation(row);
    });
    document.querySelectorAll('#printer-body tr, #network-body tr').forEach(row => {
        setupQuantityValidation(row);
    });
}

function removeRow(btn) {
    let row = btn.closest(".equipment-row");
    let container = document.getElementById("equipmentContainer");
    if(container.children.length > 1) {
        row.remove();
        toggleRemoveButtons(container, '.btn-danger');
    } else {
        alert("At least one equipment row is required!");
    }
}

// System functions
function addSystem() {
    let table = document.getElementById("systemTable").getElementsByTagName('tbody')[0];
    if(table.rows.length >= 5) {
        Swal.fire({
            title: 'Limit Reached!', 
            text: 'You can only add up to 5 systems.', 
            icon: 'warning'
        });
        return;
    }
    let newRow = table.rows[0].cloneNode(true);
    newRow.querySelector("input").value = "";
    table.appendChild(newRow);
    toggleRemoveButtons(table, '.btn-danger');
}

function removeSystem(btn) {
    let row = btn.closest("tr");
    let table = document.getElementById("systemTable").getElementsByTagName('tbody')[0];
    if(table.rows.length > 1) {
        row.remove();
        toggleRemoveButtons(table, '.btn-danger');
    } else {
        alert("At least one system is required!");
    }
}

// Proposed System functions
function addProposedSystem() {
    let table = document.getElementById("proposedSystemTable").getElementsByTagName('tbody')[0];
    if(table.rows.length >= 5) {
        Swal.fire({
            title: 'Limit Reached!', 
            text: 'You can only add up to 5 proposed systems.', 
            icon: 'warning'
        });
        return;
    }
    let newRow = table.rows[0].cloneNode(true);
    newRow.querySelector("input").value = "";
    table.appendChild(newRow);
    toggleRemoveButtons(table, '.btn-danger');
}

function removeProposedSystem(btn) {
    let row = btn.closest("tr");
    let table = document.getElementById("proposedSystemTable").getElementsByTagName('tbody')[0];
    if(table.rows.length > 1) {
        row.remove();
        toggleRemoveButtons(table, '.btn-danger');
    } else {
        alert("At least one proposed system is required!");
    }
}



// Initialize on page load
initAll();

let userOffice = document.getElementById('office')?.value || '';
let submitBtn = document.getElementById('submitBtn');
let formDisabled = false;

function checkOfficeForm() {
    if (!userOffice) return;
    
    const dateInput = document.getElementById('date');
    const selectedDate = dateInput.value ? new Date(dateInput.value) : new Date();
    const year = selectedDate.getFullYear();
    
    fetch('../api/check_form_exists.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'office=' + encodeURIComponent(userOffice) + '&year=' + year
    })
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            formDisabled = true;
            if (submitBtn) submitBtn.disabled = true;
            Swal.fire({
                icon: 'warning',
                title: 'Form Already Exists!',
                text: 'Your office has already submitted a form for the year ' + year + '. New submissions are only allowed next year.',
                confirmButtonColor: '#4f46e5'
            });
        } else {
            formDisabled = false;
            if (submitBtn) submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error checking form existence:', error);
    });
}

const dateInput = document.getElementById('date');
if (dateInput) {
    dateInput.addEventListener('change', function() {
        checkOfficeForm();
    });
}

// Check on page load
window.addEventListener('load', function() {
    checkOfficeForm();
});

// Form validation
document.getElementById('isspForm')?.addEventListener('submit', function(e) {
    if (formDisabled) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Submission Blocked',
            text: 'Your office already has a form for this year.',
            confirmButtonColor: '#d33'
        });
        return false;
    }
    
    let requiredSelects = document.querySelectorAll('select[required]');
    for(let s of requiredSelects) {
        if(!s.value) {
            e.preventDefault();
            alert('Please fill all required fields including printer/network device types.');
            s.focus();
            return false;
        }
    }
});
</script>
</body>
</html>