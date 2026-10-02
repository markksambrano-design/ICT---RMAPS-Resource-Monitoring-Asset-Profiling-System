<?php
include "../config.php";

// Helper function to get equipment image URL
function getEquipmentImageUrl($image_filename) {
    if (empty($image_filename)) {
        return '../assest/images/no-image.png';
    }
    if (strpos($image_filename, 'uploads/') !== false) {
        $image_filename = basename($image_filename);
    }
    $base_url = '../uploads/equipment_images/';
    return $base_url . $image_filename;
}

// Create necessary tables if they don't exist
$create_form_table = "CREATE TABLE IF NOT EXISTS form (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_id INT NOT NULL,
    computer_equipment ENUM('desktop computer', 'laptop', 'both') NOT NULL,
    desktop_computer_units INT DEFAULT 0,
    laptop_units INT DEFAULT 0,
    date_submitted DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_form_table);

$create_computer_equipment = "CREATE TABLE IF NOT EXISTS computer_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    office_name VARCHAR(150),
    item VARCHAR(100),
    number_of_units INT,
    brand VARCHAR(100),
    processor VARCHAR(100),
    ram VARCHAR(50),
    hdd VARCHAR(50),
    ssd VARCHAR(50),
    equipment_image VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES form(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_computer_equipment);

$create_other_equipment = "CREATE TABLE IF NOT EXISTS other_ict_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_id INT NOT NULL,
    form_id INT DEFAULT NULL,
    inkjet_printer INT DEFAULT 0,
    inkjet_printer_model VARCHAR(100),
    dot_matrix_printer INT DEFAULT 0,
    dot_matrix_printer_model VARCHAR(100),
    routers INT DEFAULT 0,
    routers_model VARCHAR(100),
    deskjet_printer INT DEFAULT 0,
    deskjet_printer_model VARCHAR(100),
    switch_hubs INT DEFAULT 0,
    switch_hubs_model VARCHAR(100),
    modem INT DEFAULT 0,
    modem_model VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE,
    FOREIGN KEY (form_id) REFERENCES form(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_other_equipment);

$create_systems = "CREATE TABLE IF NOT EXISTS systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    system_1 TEXT,
    system_2 TEXT,
    system_3 TEXT,
    system_4 TEXT,
    system_5 TEXT,
    proposed_system_1 TEXT,
    proposed_system_2 TEXT,
    proposed_system_3 TEXT,
    proposed_system_4 TEXT,
    proposed_system_5 TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES form(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_systems);

$create_internet_connections = "CREATE TABLE IF NOT EXISTS internet_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(150) NOT NULL,
    form_id INT DEFAULT NULL,
    is_centralized_isp BOOLEAN DEFAULT FALSE,
    is_centralized_lan_wan BOOLEAN DEFAULT FALSE,
    is_centralized_db_server BOOLEAN DEFAULT FALSE,
    is_other_isp_via_office_plan BOOLEAN DEFAULT FALSE,
    isp_name VARCHAR(255) NULL,
    bandwidth VARCHAR(100) NULL,
    other_internet_source TEXT NULL,
    pabx VARCHAR(50) DEFAULT 'No',
    telephone_numbers VARCHAR(255) DEFAULT 'N/A',
    base_radios INT DEFAULT 0,
    handheld_personal INT DEFAULT 0,
    handheld_lgu INT DEFAULT 0,
    handheld_total INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (office_name) REFERENCES offices(office_name) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (form_id) REFERENCES form(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_internet_connections);

$create_ict_forms = "CREATE TABLE IF NOT EXISTS ict_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(150) NOT NULL,
    date_submitted DATE NOT NULL,
    item VARCHAR(150),
    units INT,
    brand VARCHAR(100),
    processor VARCHAR(100),
    ram VARCHAR(50),
    hdd VARCHAR(50),
    ssd VARCHAR(50),
    form_status VARCHAR(50) DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_ict_forms);

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
    inkjet_printer_model VARCHAR(100),
    deskjet_printer INT DEFAULT 0,
    deskjet_printer_model VARCHAR(100),
    dotmatrix_printer INT DEFAULT 0,
    dotmatrix_printer_model VARCHAR(100),
    switch_hubs INT DEFAULT 0,
    switch_hubs_model VARCHAR(100),
    routers INT DEFAULT 0,
    routers_model VARCHAR(100),
    modem INT DEFAULT 0,
    modem_model VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES form(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_ict_equipment_inventory);

// Create new table for network devices with detailed fields
$create_network_devices = "CREATE TABLE IF NOT EXISTS network_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    office_id INT NOT NULL,
    device_type VARCHAR(100),
    model VARCHAR(100),
    brand VARCHAR(100),
    quantity INT DEFAULT 1,
    equipment_image VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES form(id) ON DELETE CASCADE,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_network_devices);

// Create new table for printer devices with detailed fields
$create_printer_devices = "CREATE TABLE IF NOT EXISTS printer_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    office_id INT NOT NULL,
    printer_type VARCHAR(100),
    model VARCHAR(100),
    brand VARCHAR(100),
    quantity INT DEFAULT 1,
    equipment_image VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES form(id) ON DELETE CASCADE,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_printer_devices);

$columns_to_check = [
    'system1', 'system2', 'system3', 'system4', 'system5',
    'proposed_system1', 'proposed_system2', 'proposed_system3', 'proposed_system4', 'proposed_system5',
    'inkjet_printer_model', 'dotmatrix_printer_model', 'deskjet_printer_model', 'routers_model', 'switch_hubs_model', 'modem_model',
    'form_status', 'ssd', 'pabx', 'telephone_numbers', 'base_radios', 'handheld_personal', 'handheld_lgu', 'handheld_total'
];

foreach ($columns_to_check as $column) {
    // Check in ict_forms
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM ict_forms LIKE '$column'");
    if ($check_column && mysqli_num_rows($check_column) == 0) {
        @mysqli_query($conn, "ALTER TABLE ict_forms ADD COLUMN $column TEXT");
    }
    // Also check in internet_connections for the new fields
    if (in_array($column, ['pabx', 'telephone_numbers', 'base_radios', 'handheld_personal', 'handheld_lgu', 'handheld_total'])) {
        $check_ic = mysqli_query($conn, "SHOW COLUMNS FROM internet_connections LIKE '$column'");
        if ($check_ic && mysqli_num_rows($check_ic) == 0) {
            $type = (strpos($column, 'radios') !== false || strpos($column, 'handheld') !== false) ? "INT DEFAULT 0" : "VARCHAR(255) DEFAULT 'N/A'";
            @mysqli_query($conn, "ALTER TABLE internet_connections ADD COLUMN $column $type");
        }
    }
}

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit;
}

if(isset($_POST['submit'])){
    $office = mysqli_real_escape_string($conn, $_POST['office'] ?? '');
    $date = mysqli_real_escape_string($conn, $_POST['date'] ?? '');
    
    // Check if form already exists for this office in the same year
    $submission_year = date('Y', strtotime($date));
    $office_trimmed = trim($office);
    $check_admin = mysqli_query($conn, "SELECT id FROM form WHERE LOWER(TRIM(office_name)) = LOWER('$office_trimmed') AND YEAR(date_submitted) = '$submission_year'");
    $check_user = mysqli_query($conn, "SELECT id FROM user_issp_form WHERE LOWER(TRIM(office_name)) = LOWER('$office_trimmed') AND YEAR(date_submitted) = '$submission_year'");
    $check_ict = mysqli_query($conn, "SELECT id FROM ict_forms WHERE LOWER(TRIM(office_name)) = LOWER('$office_trimmed') AND YEAR(date_submitted) = '$submission_year'");

    if (mysqli_num_rows($check_admin) > 0 || mysqli_num_rows($check_user) > 0 || mysqli_num_rows($check_ict) > 0) {
        $all_success = false;
        $error = true;
        $error_msg = "The office '" . $office . "' has already submitted a form for the year " . $submission_year . ". New submissions are only allowed next year.";
    } else {
        $items = $_POST['item'] ?? [];
    $units_arr = $_POST['units'] ?? [];
    $brands = $_POST['brand'] ?? [];
    $processors = $_POST['processor'] ?? [];
    $rams = $_POST['ram'] ?? [];
    $hdds = $_POST['hdd'] ?? [];
    $ssds = $_POST['ssd'] ?? [];

    // Equipment images for computer equipment
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

    // Process Printer Devices
    $printer_types = $_POST['printer_type'] ?? [];
    $printer_models = $_POST['printer_model'] ?? [];
    $printer_quantities = $_POST['printer_quantity'] ?? [];
    
    // Process Network Devices
    $network_types = $_POST['network_type'] ?? [];
    $network_models = $_POST['network_model'] ?? [];
    $network_quantities = $_POST['network_quantity'] ?? [];

    // Upload printer images
    $printer_images = $_FILES['printer_image'] ?? null;
    $uploaded_printer_images = [];
    
    if ($printer_images && isset($printer_images['name']) && is_array($printer_images['name'])) {
        foreach ($printer_images['name'] as $key => $image_name) {
            $uploaded_printer_images[$key] = null;
            if (!empty($image_name) && isset($printer_images['tmp_name'][$key]) && $printer_images['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $printer_images['tmp_name'][$key];
                $file_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_ext, $allowed_exts)) {
                    $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($image_name, PATHINFO_FILENAME));
                    $new_filename = time() . '_printer_' . $key . '_' . $safe_name . '.' . $file_ext;
                    $target_path = $upload_dir . $new_filename;
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        chmod($target_path, 0644);
                        $uploaded_printer_images[$key] = $new_filename;
                    }
                }
            }
        }
    }
    
    // Upload network images
    $network_images = $_FILES['network_image'] ?? null;
    $uploaded_network_images = [];
    
    if ($network_images && isset($network_images['name']) && is_array($network_images['name'])) {
        foreach ($network_images['name'] as $key => $image_name) {
            $uploaded_network_images[$key] = null;
            if (!empty($image_name) && isset($network_images['tmp_name'][$key]) && $network_images['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $network_images['tmp_name'][$key];
                $file_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_ext, $allowed_exts)) {
                    $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($image_name, PATHINFO_FILENAME));
                    $new_filename = time() . '_network_' . $key . '_' . $safe_name . '.' . $file_ext;
                    $target_path = $upload_dir . $new_filename;
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        chmod($target_path, 0644);
                        $uploaded_network_images[$key] = $new_filename;
                    }
                }
            }
        }
    }

    $isp_server = mysqli_real_escape_string($conn, $_POST['isp_server'] ?? '');
    $lan_connection = mysqli_real_escape_string($conn, $_POST['lan_connection'] ?? '');
    $database_server = mysqli_real_escape_string($conn, $_POST['database_server'] ?? '');
    $other_isp = mysqli_real_escape_string($conn, $_POST['other_isp'] ?? '');
    $isp_name = mysqli_real_escape_string($conn, $_POST['isp_name'] ?? '');
    $bandwidth = mysqli_real_escape_string($conn, $_POST['bandwidth'] ?? '');
    $other_source = mysqli_real_escape_string($conn, $_POST['other_source'] ?? '');
    $pabx = mysqli_real_escape_string($conn, $_POST['pabx'] ?? '');
    $telephone_numbers = mysqli_real_escape_string($conn, $_POST['telephone_numbers'] ?? '');
    $base_radios = isset($_POST['base_radios']) ? (int)$_POST['base_radios'] : 0;
    $handheld_personal = isset($_POST['handheld_personal']) ? (int)$_POST['handheld_personal'] : 0;
    $handheld_lgu = isset($_POST['handheld_lgu']) ? (int)$_POST['handheld_lgu'] : 0;
    $handheld_total = isset($_POST['handheld_total']) ? (int)$_POST['handheld_total'] : 0;

    $systems = $_POST['systems'] ?? [];
    $system1 = isset($systems[0]) ? mysqli_real_escape_string($conn, $systems[0]) : '';
    $system2 = isset($systems[1]) ? mysqli_real_escape_string($conn, $systems[1]) : '';
    $system3 = isset($systems[2]) ? mysqli_real_escape_string($conn, $systems[2]) : '';
    $system4 = isset($systems[3]) ? mysqli_real_escape_string($conn, $systems[3]) : '';
    $system5 = isset($systems[4]) ? mysqli_real_escape_string($conn, $systems[4]) : '';

    $proposed_systems = $_POST['proposed_systems'] ?? [];
    $proposed_system1 = isset($proposed_systems[0]) ? mysqli_real_escape_string($conn, $proposed_systems[0]) : '';
    $proposed_system2 = isset($proposed_systems[1]) ? mysqli_real_escape_string($conn, $proposed_systems[1]) : '';
    $proposed_system3 = isset($proposed_systems[2]) ? mysqli_real_escape_string($conn, $proposed_systems[2]) : '';
    $proposed_system4 = isset($proposed_systems[3]) ? mysqli_real_escape_string($conn, $proposed_systems[3]) : '';
    $proposed_system5 = isset($proposed_systems[4]) ? mysqli_real_escape_string($conn, $proposed_systems[4]) : '';

    $all_success = true;

    if(!is_array($items)) {
        $items = [$items];
        $units_arr = [$units_arr];
        $brands = [$brands];
        $processors = [$processors];
        $rams = [$rams];
        $hdds = [$hdds];
        $ssds = [$ssds];
    }

    $office_query = mysqli_query($conn, "SELECT id FROM offices WHERE office_name = '$office'");
    $office_row = mysqli_fetch_assoc($office_query);
    $office_id = $office_row ? $office_row['id'] : null;

    if(!$office_id) {
        $all_success = false;
        $error = true;
    } else {
        $total_desktop = 0;
        $total_laptop = 0;
        $units_arr = $_POST['units'] ?? [];
        foreach($items as $key => $val) {
            $item_name = strtolower($items[$key] ?? '');
            $u = isset($units_arr[$key]) ? (int)$units_arr[$key] : 1;
            if(strpos($item_name, 'desktop') !== false) {
                $total_desktop += $u;
            } else if(strpos($item_name, 'laptop') !== false) {
                $total_laptop += $u;
            }
        }

        $comp_equip = 'both';
        if($total_desktop > 0 && $total_laptop == 0) $comp_equip = 'desktop computer';
        else if($total_laptop > 0 && $total_desktop == 0) $comp_equip = 'laptop';
        
        $form_insert = mysqli_query($conn, "INSERT INTO form (office_name, office_id, computer_equipment, desktop_computer_units, laptop_units, date_submitted) 
            VALUES ('$office', $office_id, '$comp_equip', $total_desktop, $total_laptop, '$date')");
        $form_id = mysqli_insert_id($conn);

        if($form_id) {
            // Insert Printer Devices
            foreach($printer_types as $key => $type) {
                if(!empty($type)) {
                    $printer_type_val = mysqli_real_escape_string($conn, $type);
                    $printer_model_val = isset($printer_models[$key]) ? mysqli_real_escape_string($conn, $printer_models[$key]) : '';
                    $printer_qty = isset($printer_quantities[$key]) ? (int)$printer_quantities[$key] : 1;
                    $printer_image = isset($uploaded_printer_images[$key]) ? mysqli_real_escape_string($conn, $uploaded_printer_images[$key]) : null;
                    
                    $printer_insert = mysqli_query($conn, "INSERT INTO printer_devices (form_id, office_id, printer_type, model, quantity, equipment_image) 
                        VALUES ($form_id, $office_id, '$printer_type_val', '$printer_model_val', $printer_qty, " . ($printer_image ? "'$printer_image'" : "NULL") . ")");
                    
                    if(!$printer_insert) $all_success = false;
                }
            }
            
            // Insert Network Devices
            foreach($network_types as $key => $type) {
                if(!empty($type)) {
                    $network_type_val = mysqli_real_escape_string($conn, $type);
                    $network_model_val = isset($network_models[$key]) ? mysqli_real_escape_string($conn, $network_models[$key]) : '';
                    $network_qty = isset($network_quantities[$key]) ? (int)$network_quantities[$key] : 1;
                    $network_image = isset($uploaded_network_images[$key]) ? mysqli_real_escape_string($conn, $uploaded_network_images[$key]) : null;
                    
                    $network_insert = mysqli_query($conn, "INSERT INTO network_devices (form_id, office_id, device_type, model, quantity, equipment_image) 
                        VALUES ($form_id, $office_id, '$network_type_val', '$network_model_val', $network_qty, " . ($network_image ? "'$network_image'" : "NULL") . ")");
                    
                    if(!$network_insert) $all_success = false;
                }
            }

            // Legacy other_ict_equipment insert for backward compatibility
            // Calculate totals from printer devices
            $inkjet_printer = 0;
            $deskjet_printer = 0;
            $dotmatrix_printer = 0;
            $inkjet_models = [];
            $deskjet_models = [];
            $dotmatrix_models = [];
            
            foreach($printer_types as $key => $type) {
                $qty = isset($printer_quantities[$key]) ? (int)$printer_quantities[$key] : 1;
                $model = isset($printer_models[$key]) ? $printer_models[$key] : '';
                if(strpos(strtolower($type), 'inkjet') !== false) {
                    $inkjet_printer += $qty;
                    if($model) $inkjet_models[] = $model;
                } elseif(strpos(strtolower($type), 'deskjet') !== false) {
                    $deskjet_printer += $qty;
                    if($model) $deskjet_models[] = $model;
                } elseif(strpos(strtolower($type), 'dot matrix') !== false) {
                    $dotmatrix_printer += $qty;
                    if($model) $dotmatrix_models[] = $model;
                }
            }
            
            // Calculate totals from network devices
            $switch_hubs = 0;
            $routers = 0;
            $modem = 0;
            $switch_models = [];
            $router_models = [];
            $modem_models = [];
            
            foreach($network_types as $key => $type) {
                $qty = isset($network_quantities[$key]) ? (int)$network_quantities[$key] : 1;
                $model = isset($network_models[$key]) ? $network_models[$key] : '';
                if(strpos(strtolower($type), 'switch') !== false) {
                    $switch_hubs += $qty;
                    if($model) $switch_models[] = $model;
                } elseif(strpos(strtolower($type), 'router') !== false) {
                    $routers += $qty;
                    if($model) $router_models[] = $model;
                } elseif(strpos(strtolower($type), 'modem') !== false) {
                    $modem += $qty;
                    if($model) $modem_models[] = $model;
                }
            }
            
            $inkjet_printer_model = mysqli_real_escape_string($conn, implode(", ", $inkjet_models));
            $deskjet_printer_model = mysqli_real_escape_string($conn, implode(", ", $deskjet_models));
            $dotmatrix_printer_model = mysqli_real_escape_string($conn, implode(", ", $dotmatrix_models));
            $switch_hubs_model = mysqli_real_escape_string($conn, implode(", ", $switch_models));
            $routers_model = mysqli_real_escape_string($conn, implode(", ", $router_models));
            $modem_model = mysqli_real_escape_string($conn, implode(", ", $modem_models));
            
            $oie_insert = mysqli_query($conn, "INSERT INTO other_ict_equipment (form_id, office_id, 
                inkjet_printer, inkjet_printer_model, deskjet_printer, deskjet_printer_model, 
                dot_matrix_printer, dot_matrix_printer_model, switch_hubs, switch_hubs_model, 
                routers, routers_model, modem, modem_model) 
                VALUES ($form_id, $office_id, 
                $inkjet_printer, '$inkjet_printer_model', $deskjet_printer, '$deskjet_printer_model', 
                $dotmatrix_printer, '$dotmatrix_printer_model', $switch_hubs, '$switch_hubs_model', 
                $routers, '$routers_model', $modem, '$modem_model')");

            $sys_insert = mysqli_query($conn, "INSERT INTO systems (form_id, office_id, system_1, system_2, system_3, system_4, system_5, proposed_system_1, proposed_system_2, proposed_system_3, proposed_system_4, proposed_system_5) 
                VALUES ($form_id, $office_id, '$system1', '$system2', '$system3', '$system4', '$system5', '$proposed_system1', '$proposed_system2', '$proposed_system3', '$proposed_system4', '$proposed_system5')");

            $is_centralized_isp = ($isp_server == 'Yes') ? 1 : 0;
            $is_centralized_lan_wan = ($lan_connection == 'Yes') ? 1 : 0;
            $is_centralized_db_server = ($database_server == 'Yes') ? 1 : 0;
            $is_other_isp_via_office_plan = ($other_isp == 'Yes') ? 1 : 0;

            $ic_insert = mysqli_query($conn, "INSERT INTO internet_connections (office_name, form_id, is_centralized_isp, is_centralized_lan_wan, is_centralized_db_server, is_other_isp_via_office_plan, isp_name, bandwidth, other_internet_source, pabx, telephone_numbers, base_radios, handheld_personal, handheld_lgu, handheld_total) 
                VALUES ('$office', $form_id, $is_centralized_isp, $is_centralized_lan_wan, $is_centralized_db_server, $is_other_isp_via_office_plan, '$isp_name', '$bandwidth', '$other_source', '$pabx', '$telephone_numbers', $base_radios, $handheld_personal, $handheld_lgu, $handheld_total)");

            $is_first_item = true;
            foreach($items as $key => $val) {
                $item_val = mysqli_real_escape_string($conn, $items[$key] ?? '');
                $units_val = isset($units_arr[$key]) ? (int)$units_arr[$key] : 1;
                $brand_val = mysqli_real_escape_string($conn, $brands[$key] ?? '');
                $processor_val = mysqli_real_escape_string($conn, $processors[$key] ?? '');
                $ram_val = mysqli_real_escape_string($conn, $rams[$key] ?? '');
                $hdd_val = mysqli_real_escape_string($conn, $hdds[$key] ?? '');
                $ssd_val = mysqli_real_escape_string($conn, $ssds[$key] ?? '');
                $image_filename = isset($uploaded_images[$key]) ? mysqli_real_escape_string($conn, $uploaded_images[$key]) : null;

                if(!empty($item_val)) {
                    $ce_insert = mysqli_query($conn, "INSERT INTO computer_equipment (form_id, office_name, item, number_of_units, brand, processor, ram, hdd, ssd, equipment_image) 
                        VALUES ($form_id, '$office', '$item_val', $units_val, '$brand_val', '$processor_val', '$ram_val', '$hdd_val', '$ssd_val', " . ($image_filename ? "'$image_filename'" : "NULL") . ")");
                    
                    $inv_inkjet = $is_first_item ? $inkjet_printer : 0;
                    $inv_deskjet = $is_first_item ? $deskjet_printer : 0;
                    $inv_dotmatrix = $is_first_item ? $dotmatrix_printer : 0;
                    $inv_hubs = $is_first_item ? $switch_hubs : 0;
                    $inv_routers = $is_first_item ? $routers : 0;
                    $inv_modem = $is_first_item ? $modem : 0;
                    
                    $inv_insert = mysqli_query($conn, "INSERT INTO ict_equipment_inventory (office_name, form_id, date_submitted, item, units, brand, processor, ram, hdd, ssd, inkjet_printer, inkjet_printer_model, deskjet_printer, deskjet_printer_model, dotmatrix_printer, dotmatrix_printer_model, switch_hubs, switch_hubs_model, routers, routers_model, modem, modem_model) 
                        VALUES ('$office', $form_id, '$date', '$item_val', $units_val, '$brand_val', '$processor_val', '$ram_val', '$hdd_val', '$ssd_val', $inv_inkjet, '$inkjet_printer_model', $inv_deskjet, '$deskjet_printer_model', $inv_dotmatrix, '$dotmatrix_printer_model', $inv_hubs, '$switch_hubs_model', $inv_routers, '$routers_model', $inv_modem, '$modem_model')");
                    
                    $is_first_item = false;
                }
            }

            if(!$form_insert || !$oie_insert || !$sys_insert || !$ic_insert){
                $all_success = false;
            }
        } else {
            $all_success = false;
        }
    }
    }

    if($all_success){
        $success = true;
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html>
<?php $pageTitle = 'RMAPS Form - ICTMIS'; ?>
<?php include 'components/head.php'; ?>
<link rel="stylesheet" href="../assest/css/admin/form.css">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
.required-asterisk {
    color: #dc3545;
    font-size: 1.1rem;
    font-weight: bold;
    margin-left: 3px;
}
.storage-disabled {
    background-color: #e9ecef !important;
    opacity: 0.7;
    cursor: not-allowed;
}
label.required-label::after {
    content: " *";
    color: #dc3545;
    font-weight: bold;
    font-size: 1rem;
    display: inline;
}
label.required-error::after {
    content: " (Required)" !important;
    color: #dc3545 !important;
    font-weight: 600 !important;
    font-size: 0.8rem !important;
    display: inline !important;
}
.missing-field {
    border: 2px solid #dc3545 !important;
    background-color: #fff8f8 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}
.error-popup {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #dc3545, #b02a37);
    color: white;
    padding: 16px 28px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 500;
    z-index: 9999;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 14px;
    animation: slideDownPopup 0.3s ease;
    border: 1px solid rgba(255,255,255,0.2);
    max-width: 90%;
}
.error-popup .error-icon {
    font-size: 22px;
}
.error-popup .error-message {
    flex: 1;
}
.error-popup .close-btn {
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
    opacity: 0.8;
    transition: all 0.2s;
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}
.error-popup .close-btn:hover {
    opacity: 1;
    background: rgba(255,255,255,0.2);
}
@keyframes slideDownPopup {
    from {
        top: -80px;
        opacity: 0;
        transform: translateX(-50%) scale(0.9);
    }
    to {
        top: 20px;
        opacity: 1;
        transform: translateX(-50%) scale(1);
    }
}
.scroll-top-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #0f172a;
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 999;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: all 0.3s;
    opacity: 0;
    visibility: hidden;
}
.scroll-top-btn.show {
    opacity: 1;
    visibility: visible;
}
.scroll-top-btn:hover {
    background: #dc3545;
    transform: scale(1.1);
}
.ram-input {
    text-transform: uppercase;
}
.ram-invalid {
    border-color: #dc3545 !important;
    background-color: #fff8f8 !important;
}
.office-icon {
    width: 38px;
    height: 38px;
    object-fit: contain;
    display: inline-block;
}
.office-logo-wrapper {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f1f5f9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    flex-shrink: 0;
}
.office-logo-wrapper i {
    font-size: 1.5rem;
    display: inline-block;
    line-height: 1;
    vertical-align: middle;
}
.select2-container--bootstrap-5 .select2-selection {
    border-radius: 0.65rem;
    border: 1px solid #cbd5e1;
    min-height: 60px;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
}
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    display: flex;
    align-items: center;
    gap: 15px;
    color: #1e293b;
    font-size: 1.1rem;
    font-weight: 500;
}
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered > span {
    display: flex;
    align-items: center;
    gap: 15px;
}
.select2-results__option {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 15px !important;
    font-size: 1.05rem;
}
.select2-results__options {
    max-height: 450px !important;
}
.view-forms-card {
    background: #f0fdf4;
    border: 1px solid #dcfce7;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
    transition: all 0.3s ease;
}
.view-forms-card:hover {
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.1);
    border-color: #bbf7d0;
}
.view-forms-left {
    display: flex;
    align-items: center;
    gap: 20px;
}
.view-forms-icon {
    background: #22c55e;
    color: white;
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 4px 10px rgba(34, 197, 94, 0.2);
}
.view-forms-text h3 {
    color: #166534;
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0;
    margin-bottom: 4px;
}
.view-forms-text p {
    color: #3f6212;
    font-size: 0.95rem;
    margin: 0;
    opacity: 0.8;
}
.btn-view-forms {
    background: transparent;
    color: #166534;
    border: 1.5px solid #166534;
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s ease;
    text-decoration: none;
}
.btn-view-forms:hover {
    background: #166534;
    color: white;
}
.tech-card {
    background: #ffffff;
    border-radius: 12px;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}
.tech-card .card-header {
    background: #0f172a !important;
    color: #ffffff !important;
    border-bottom: none !important;
    padding: 12px 20px;
    font-weight: 600;
}
.tech-card .card-body {
    padding: 20px;
}
.tech-title {
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 1px;
    color: #0369a1;
    margin-bottom: 10px;
    text-transform: uppercase;
}
.tech-table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
}
.tech-table td, .tech-table th {
    border: 1px solid #e2e8f0;
    vertical-align: middle;
    padding: 8px 12px;
}
.tech-label {
    font-weight: 600;
    color: #334155;
    width: 180px;
}
.section-box {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    background: #f8fafc;
}
.file-name-display {
    font-size: 11px;
    color: #6c757d;
    margin-top: 4px;
    word-break: break-all;
}
.action-btns {
    width: 90px;
    text-align: center;
    white-space: nowrap;
}
.action-btns button {
    margin: 0 3px;
    padding: 4px 8px;
}
.remove-btn-hidden {
    display: none !important;
}
</style>
</head>

<body>
<div class="d-flex">
    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'components/header.php'; ?>

        <div class="issp-header" id="pageTop">
            <div class="header-container">
                <div class="logo-left">
                    <img src="../assest/images/logo1.png" class="lgu-logo" alt="LGU Logo">
                </div>
                <div class="header-text">
                    <h2>DATA FOR THE FORMULATION OF INFORMATION SYSTEM STRATEGIC PLANNING 2026-2030</h2>
                </div>
                <div class="logo-right">
                    <img src="../assest/images/logo3.png" class="ict-logo" alt="ICTMIS Logo">
                </div>
            </div>
        </div>

        <div class="view-forms-card animate__animated animate__fadeInUp">
            <div class="view-forms-left">
                <div class="view-forms-icon">
                    <i class="bi bi-eye"></i>
                </div>
                <div class="view-forms-text">
                    <h3>View Submitted RMAPS Forms</h3>
                    <p>Check and review previously submitted forms.</p>
                </div>
            </div>
            <a href="modules/view_issp_forms.php" class="btn-view-forms">
                <i class="bi bi-box-arrow-up-right"></i> View Forms <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <form method="POST" class="bordered-form" enctype="multipart/form-data" id="isspForm" novalidate>

            <div class="card mb-4" id="officeSection">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-building me-2"></i>Office Information</div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="required-label">Office Name</label>
                            <select name="office" id="office" class="form-select" required>
                                <option value="">Select Office</option>
                                <?php
                                $offices_result = mysqli_query($conn, "SELECT DISTINCT office FROM users ORDER BY office ASC");
                                if ($offices_result) {
                                    while ($office_row = mysqli_fetch_assoc($offices_result)) {
                                        $office_name = $office_row['office'];
                                        echo '<option value="' . htmlspecialchars($office_name) . '">' . htmlspecialchars($office_name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required-label">Date Submitted</label>
                            <input type="date" name="date" id="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-pc me-2"></i>Computer Equipment</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addEquipment()">
                        <i class="bi bi-plus"></i> Add
                    </button>
                </div>
                <div class="card-body" id="equipmentContainer">
                    <div class="equipment-row mb-3 border p-3 rounded">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Image</label>
                                <input type="file" name="equipment_image[]" class="form-control" accept="image/jpeg,image/png,image/jpg">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Item <span class="text-danger">*</span></label>
                                <select name="item[]" class="form-control item-select" required>
                                    <option value="">Select Item</option>
                                    <option value="Desktop Computer">Desktop Computer</option>
                                    <option value="Laptop">Laptop</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">No. of Units</label>
                                <input type="number" name="units[]" class="form-control units-input" value="1" required min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Brand <span class="text-danger">*</span></label>
                                <input type="text" name="brand[]" class="form-control brand-input" required>
                            </div>
                        </div><br>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Processor <span class="text-danger">*</span></label>
                                <select name="processor[]" class="form-control processor-input" required>
                                    <option value="">-- Select Processor --</option>
                                    <option value="Intel Core i3">Intel Core i3</option>
                                    <option value="Intel Core i5">Intel Core i5</option>
                                    <option value="Intel Core i7">Intel Core i7</option>
                                    <option value="Intel Core i9">Intel Core i9</option>
                                    <option value="AMD Ryzen 3">AMD Ryzen 3</option>
                                    <option value="AMD Ryzen 5">AMD Ryzen 5</option>
                                    <option value="AMD Ryzen 7">AMD Ryzen 7</option>
                                    <option value="AMD Ryzen 9">AMD Ryzen 9</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">RAM <span class="text-danger">*</span></label>
                                <select name="ram[]" class="form-control ram-input" required>
                                    <option value="">-- Select RAM --</option>
                                    <option value="4 GB">4 GB</option>
                                    <option value="8 GB">8 GB</option>
                                    <option value="16 GB">16 GB</option>
                                    <option value="32 GB">32 GB</option>
                                    <option value="64 GB">64 GB</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">HDD</label>
                                <select name="hdd[]" class="form-control hdd-select">
                                    <option value="">-- Select HDD --</option>
                                    <option value="500 GB HDD">500 GB HDD</option>
                                    <option value="1 TB HDD">1 TB HDD</option>
                                    <option value="2 TB HDD">2 TB HDD</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SSD</label>
                                <select name="ssd[]" class="form-control ssd-select">
                                    <option value="">-- Select SSD --</option>
                                    <option value="128 GB SSD">128 GB SSD</option>
                                    <option value="256 GB SSD">256 GB SSD</option>
                                    <option value="512 GB SSD">512 GB SSD</option>
                                    <option value="1 TB SSD">1 TB SSD</option>
                                </select>
                            </div>
                        </div>
                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeRow(this)">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OTHER ICT EQUIPMENT - FIXED TABLE LAYOUT -->
            <div class="card mb-4 tech-card">
                <div class="card-header">
                    <span><i class="bi bi-cpu me-2"></i>Other ICT Equipment</span>
                </div>
                <div class="card-body">
                    <!-- PRINTER DEVICES -->
                    <div class="section-box">
                        <div class="tech-title">
                            <i class="bi bi-printer me-2"></i>PRINTER DEVICES
                        </div>
                        <div class="table-responsive">
                            <table class="table tech-table align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:25%">Upload Image</th>
                                        <th style="width:25%">Printer Type</th>
                                        <th style="width:25%">Model</th>
                                        <th style="width:15%">No. of Units</th>
                                        <th style="width:10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="printer-devices-body">
                                    <tr class="printer-row">
                                        <td>
                                            <input type="file" name="printer_image[]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                                        </td>
                                        <td>
                                            <select name="printer_type[]" class="form-control form-control-sm">
                                                <option value="">Select Type</option>
                                                <option value="Inkjet Printer">Inkjet Printer</option>
                                                <option value="Deskjet Printer">Deskjet Printer</option>
                                                <option value="Dot Matrix Printer">Dot Matrix Printer</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="printer_model[]" class="form-control form-control-sm" placeholder="Model">
                                        </td>
                                        <td>
                                            <input type="number" name="printer_quantity[]" class="form-control form-control-sm units-input" value="1" min="1">
                                        </td>
                                        <td class="action-btns">
                                            <button type="button" class="btn btn-success btn-sm" onclick="addPrinterRow(this)">+</button>
                                            <button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removePrinterRow(this)">-</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- NETWORK DEVICES -->
                    <div class="section-box">
                        <div class="tech-title">
                            <i class="bi bi-hdd-network me-2"></i>NETWORK DEVICES
                        </div>
                        <div class="table-responsive">
                            <table class="table tech-table align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:25%">Upload Image</th>
                                        <th style="width:25%">Device Type</th>
                                        <th style="width:25%">Model</th>
                                        <th style="width:15%">No. of Units</th>
                                        <th style="width:10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="network-devices-body">
                                    <tr class="network-row">
                                        <td>
                                            <input type="file" name="network_image[]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                                        </td>
                                        <td>
                                            <select name="network_type[]" class="form-control form-control-sm">
                                                <option value="">Select Type</option>
                                                <option value="Switch Hubs">Switch Hubs</option>
                                                <option value="Routers">Routers</option>
                                                <option value="Modem">Modem</option>
                                            </select>
                                        </td>
                                         <td>
                                            <input type="text" name="network_model[]" class="form-control form-control-sm" placeholder="Model">
                                        </td>
                                        <td>
                                            <input type="number" name="network_quantity[]" class="form-control form-control-sm units-input" value="1" min="1">
                                        </td>
                                        <td class="action-btns">
                                            <button type="button" class="btn btn-success btn-sm" onclick="addNetworkRow(this)">+</button>
                                            <button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeNetworkRow(this)">-</button>
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
                    <button type="button" class="btn btn-sm btn-primary" onclick="addSystem()"><i class="bi bi-plus"></i> Add</button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered" id="systemTable">
                        <thead><tr><th width="90%">System Name</th><th width="10%">Action</th></tr></thead>
                        <tbody><tr><td><input type="text" name="systems[]" class="form-control" placeholder="Enter System"></td><td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeSystem(this)"><i class="bi bi-trash"></i></button></td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-lightbulb me-2"></i>Proposed Systems</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addProposedSystem()"><i class="bi bi-plus"></i> Add</button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered" id="proposedSystemTable">
                        <thead><tr><th width="90%">Proposed System</th><th width="10%">Action</th></tr></thead>
                        <tbody><tr><td><input type="text" name="proposed_systems[]" class="form-control" placeholder="Enter Proposed System"></td><td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-btn-hidden" onclick="removeProposedSystem(this)"><i class="bi bi-trash"></i></button></td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-wifi me-2"></i>Internet Connections</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th width="70%">Connection Type</th><th class="text-center">YES</th><th class="text-center">NO</th></tr>
                        <tr><td>Connected to CENTRALIZED ISP SERVER</td><td class="text-center"><input type="radio" name="isp_server" value="Yes"></td><td class="text-center"><input type="radio" name="isp_server" value="No"></td></tr>
                        <tr><td>Connected to CENTRALIZED LAN / WAN CONNECTION</td><td class="text-center"><input type="radio" name="lan_connection" value="Yes"></td><td class="text-center"><input type="radio" name="lan_connection" value="No"></td></tr>
                        <tr><td>Connected to CENTRALIZED DATABASE SERVER</td><td class="text-center"><input type="radio" name="database_server" value="Yes"></td><td class="text-center"><input type="radio" name="database_server" value="No"></td></tr>
                        <tr><td>Connected to OTHER ISPs via Office Paid Plan</td><td class="text-center"><input type="radio" name="other_isp" value="Yes"></td><td class="text-center"><input type="radio" name="other_isp" value="No"></td></tr>
                    </table>
                    <div class="row">
                        <div class="col-md-6"><label>ISP Provider Name</label><input type="text" name="isp_name" class="form-control"></div>
                        <div class="col-md-6"><label>Bandwidth (Mbps)</label><div class="input-group"><input type="text" name="bandwidth" class="form-control"><span class="input-group-text">mbps</span></div></div>
                    </div>
                    <br>
                    <label>Other Internet Connection Source</label>
                    <input type="text" name="other_source" class="form-control">
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-telephone me-2"></i>Communication</div>
                <div class="card-body">
                    <label class="form-label">Connected to PBAX System (with Digital Telephone Unit with Local#) (075) 633-7180</label>
                    <div class="form-check"><input class="form-check-input" type="radio" name="pabx" value="Yes" id="pabxYes"><label class="form-check-label" for="pabxYes">Yes</label></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="radio" name="pabx" value="No" id="pabxNo"><label class="form-check-label" for="pabxNo">No</label></div>
                    <label class="form-label">Others, if existing, pls. indicate Office Telephone Number/s:</label>
                    <input type="text" name="telephone_numbers" class="form-control" placeholder="Enter telephone number/s">
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-wifi me-2"></i>Radio Communication Equipment</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><label>Base Radios</label><input type="number" name="base_radios" class="form-control"></div>
                        <div class="col-md-3"><label>Handheld Personal</label><input type="number" name="handheld_personal" class="form-control"></div>
                        <div class="col-md-3"><label>Handheld LGU</label><input type="number" name="handheld_lgu" class="form-control"></div>
                        <div class="col-md-3"><label>Total</label><input type="number" name="handheld_total" class="form-control"></div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" name="submit" class="btn btn-submit" id="submitBtn">
                    <i class="bi bi-check-circle me-2"></i>Submit Form
                </button>
            </div>
        </form>
    </div>
</div>

<div class="scroll-top-btn" id="scrollTopBtn" onclick="scrollToTopOfPage()">
    <i class="bi bi-arrow-up" style="font-size: 20px;"></i>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
<?php if(isset($success) && $success): ?>
Swal.fire({title:'Success!',text:'Form submitted successfully!',icon:'success',confirmButtonColor:'#0f172a'}).then(()=>{window.location='form.php';});
<?php endif; ?>
<?php if(isset($error) && $error): ?>
Swal.fire({title:'Error!',text:'<?php echo isset($error_msg)?$error_msg:"Error submitting form!";?>',icon:'error',confirmButtonColor:'#d33'});
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

// PRINTER ROWS
function addPrinterRow(btn){let tbody=document.getElementById('printer-devices-body');let template=tbody.querySelector('tr.printer-row');let newRow=template.cloneNode(true);newRow.querySelectorAll('input,select').forEach(f=>{if(f.type==='file')f.value='';else if(f.type==='number')f.value='1';else if(f.tagName==='SELECT')f.selectedIndex=0;else f.value='';});tbody.appendChild(newRow);setupQuantityValidation(newRow);toggleRemoveButtons(tbody, '.btn-danger');}
function removePrinterRow(btn){let tbody=document.getElementById('printer-devices-body');let row=btn.closest('tr');if(tbody.querySelectorAll('tr').length>1){row.remove();toggleRemoveButtons(tbody, '.btn-danger');}else showErrorPopup("At least one printer device row is required!");}

// NETWORK ROWS
function addNetworkRow(btn){let tbody=document.getElementById('network-devices-body');let template=tbody.querySelector('tr.network-row');let newRow=template.cloneNode(true);newRow.querySelectorAll('input,select').forEach(f=>{if(f.type==='file')f.value='';else if(f.type==='number')f.value='1';else if(f.tagName==='SELECT')f.selectedIndex=0;else f.value='';});tbody.appendChild(newRow);setupQuantityValidation(newRow);toggleRemoveButtons(tbody, '.btn-danger');}
function removeNetworkRow(btn){let tbody=document.getElementById('network-devices-body');let row=btn.closest('tr');if(tbody.querySelectorAll('tr').length>1){row.remove();toggleRemoveButtons(tbody, '.btn-danger');}else showErrorPopup("At least one network device row is required!");}

// COMPUTER EQUIPMENT ROWS
function addEquipment(){let container=document.getElementById("equipmentContainer");let newRow=document.querySelector(".equipment-row").cloneNode(true);newRow.querySelectorAll("input, select").forEach(f=>{if(f.type==='file')f.value='';else if(f.type==='number')f.value='1';else f.value='';if(f.tagName==='SELECT')f.selectedIndex=0;});container.appendChild(newRow);initStorageMutualExclusivity(newRow);setupQuantityValidation(newRow);toggleRemoveButtons(container, '.btn-danger');}
function removeRow(btn){let container=document.getElementById("equipmentContainer");if(container.children.length>1){btn.closest(".equipment-row").remove();toggleRemoveButtons(container, '.btn-danger');}else showErrorPopup("At least one equipment row is required!");}
function setupQuantityValidation(row){let qtyInput=row.querySelector('.units-input');if(!qtyInput)return;qtyInput.addEventListener('input',function(){let val=parseInt(this.value);if(this.value!==''){if(val<1){this.value=1;showErrorPopup("Quantity cannot be less than 1");}}});}
function initStorageMutualExclusivity(row){let hdd=row.querySelector('select[name="hdd[]"]');let ssd=row.querySelector('select[name="ssd[]"]');if(!hdd||!ssd)return;let hddChange=()=>{if(hdd.value){ssd.disabled=true;ssd.classList.add('storage-disabled');ssd.value='';}else{ssd.disabled=false;ssd.classList.remove('storage-disabled');}};let ssdChange=()=>{if(ssd.value){hdd.disabled=true;hdd.classList.add('storage-disabled');hdd.value='';}else{hdd.disabled=false;hdd.classList.remove('storage-disabled');}};hdd.onchange=hddChange;ssd.onchange=ssdChange;hddChange();ssdChange();}
function initAll(){
    document.querySelectorAll('#equipmentContainer .equipment-row').forEach(row=>{initStorageMutualExclusivity(row);setupQuantityValidation(row);});
    document.querySelectorAll('#printer-devices-body .printer-row').forEach(row=>{setupQuantityValidation(row);});
    document.querySelectorAll('#network-devices-body .network-row').forEach(row=>{setupQuantityValidation(row);});
}
function addSystem(){let t=document.getElementById("systemTable").getElementsByTagName('tbody')[0];if(t.rows.length>=5){Swal.fire({title:'Limit Reached!',text:'You can only add up to 5 systems.',icon:'warning'});return;}let newRow=t.rows[0].cloneNode(true);newRow.querySelector("input").value="";t.appendChild(newRow);toggleRemoveButtons(t, '.btn-danger');}
function removeSystem(btn){let t=document.getElementById("systemTable").getElementsByTagName('tbody')[0];if(t.rows.length>1){btn.closest("tr").remove();toggleRemoveButtons(t, '.btn-danger');}else showErrorPopup("At least one system is required!");}
function addProposedSystem(){let t=document.getElementById("proposedSystemTable").getElementsByTagName('tbody')[0];if(t.rows.length>=5){Swal.fire({title:'Limit Reached!',text:'You can only add up to 5 proposed systems.',icon:'warning'});return;}let newRow=t.rows[0].cloneNode(true);newRow.querySelector("input").value="";t.appendChild(newRow);toggleRemoveButtons(t, '.btn-danger');}
function removeProposedSystem(btn){let t=document.getElementById("proposedSystemTable").getElementsByTagName('tbody')[0];if(t.rows.length>1){btn.closest("tr").remove();toggleRemoveButtons(t, '.btn-danger');}else showErrorPopup("At least one proposed system is required!");}
function showErrorPopup(msg){let p=document.querySelector('.error-popup');if(p)p.remove();let popup=document.createElement('div');popup.className='error-popup';popup.innerHTML=`<span class="error-icon">⚠️</span><span class="error-message">${msg}</span><span class="close-btn" onclick="this.parentElement.remove()">✕</span>`;document.body.appendChild(popup);setTimeout(()=>{if(popup.parentElement)popup.remove();},5000);}
function validateForm(){document.querySelectorAll('.missing-field,.required-error').forEach(el=>el.classList.remove('missing-field','required-error'));let hasErr=false,firstEl=null,firstMsg='';function setErr(el,msg){if(!hasErr){hasErr=true;firstEl=el;firstMsg=msg;if(el.setCustomValidity){el.setCustomValidity(msg);el.reportValidity();el.oninput=()=>el.setCustomValidity('');}}el.classList.add('missing-field');let lbl=el.closest('.col-md-2,.col-md-3,.col-md-6')?.querySelector('label');if(lbl)lbl.classList.add('required-error');}
let office=document.getElementById('office');if(!office.value)setErr(office,'Select Office Name');
let date=document.getElementById('date');if(!date.value)setErr(date,'Select Date');
document.querySelectorAll('#equipmentContainer .equipment-row').forEach((r,i)=>{let n=i+1;let sel=r.querySelector('.item-select');if(!sel.value)setErr(sel,`Row ${n}: Select Item`);let br=r.querySelector('.brand-input');if(!br.value)setErr(br,`Row ${n}: Enter Brand`);let proc=r.querySelector('input[name="processor[]"]');if(!proc.value)setErr(proc,`Row ${n}: Enter Processor`);let ram=r.querySelector('.ram-input');if(!ram.value)setErr(ram,`Row ${n}: Enter RAM`);let qty=r.querySelector('.units-input');if(!qty.value)setErr(qty,`Row ${n}: Enter No. of Units`);});
if(!document.querySelector('input[name="isp_server"]:checked'))setErr(document.querySelector('input[name="isp_server"]'),'Select ISP Server connection');
if(!document.querySelector('input[name="lan_connection"]:checked'))setErr(document.querySelector('input[name="lan_connection"]'),'Select LAN/WAN connection');
if(!document.querySelector('input[name="database_server"]:checked'))setErr(document.querySelector('input[name="database_server"]'),'Select Database Server connection');
if(!document.querySelector('input[name="other_isp"]:checked'))setErr(document.querySelector('input[name="other_isp"]'),'Select Other ISP connection');
if(!document.querySelector('input[name="pabx"]:checked'))setErr(document.querySelector('input[name="pabx"]'),'Select PABX connection');
if(hasErr){showErrorPopup(`❌ ${firstMsg}`);return false;}return true;}
function scrollToTopOfPage(){window.scrollTo({top:0,behavior:'smooth'});}
window.addEventListener('load',()=>{initAll();setTimeout(()=>window.scrollTo({top:0,behavior:'smooth'}),50);});
window.addEventListener('scroll',()=>{let btn=document.getElementById('scrollTopBtn');if(window.scrollY>300)btn.classList.add('show');else btn.classList.remove('show');});
document.getElementById('isspForm').addEventListener('submit',function(e){if(!validateForm()){e.preventDefault();return false;}document.querySelectorAll('select.hdd-select:disabled,select.ssd-select:disabled').forEach(s=>s.disabled=false);});
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function(){
    const icons={
        "Mayor's Office": { type: 'img', src: "../assest/images/offices/mayos office.png" },
        "MDRRMO": { type: 'img', src: "../assest/images/offices/MDRRMO.png" },
        "Bus. Tax Section": { type: 'icon', class: 'bi-bus-front', color: '#0284c7', bg: '#e0f2fe' },
        "Community Affairs Office": { type: 'icon', class: 'bi-people', color: '#4f46e5', bg: '#eef2ff' },
        "General Services Office": { type: 'icon', class: 'bi-gear', color: '#475569', bg: '#f1f5f9' },
        "Human Resource Mgmt Office": { type: 'icon', class: 'bi-person-badge', color: '#059669', bg: '#ecfdf5' },
        "MENRO": { type: 'icon', class: 'bi-recycle', color: '#065f46', bg: '#d1fae5' },
        "MSWD": { type: 'icon', class: 'bi-droplet', color: '#1d4ed8', bg: '#dbeafe' },
        "Municipal Accounting Office": { type: 'icon', class: 'bi-calculator', color: '#6d28d9', bg: '#ede9fe' },
        "Municipal Agriculture Office": { type: 'icon', class: 'bi-flower1', color: '#4d7c0f', bg: '#ecfccb' },
        "Municipal Assessor's Office": { type: 'icon', class: 'bi-graph-up-arrow', color: '#c2410c', bg: '#ffedd5' },
        "Municipal Budget Office": { type: 'icon', class: 'bi-wallet2', color: '#a16207', bg: '#fef9c3' },
        "Municipal Cooperative Office": { type: 'icon', class: 'bi-hand-thumbs-up', color: '#0891b2', bg: '#ecfeff' },
        "Municipal Engineering Office": { type: 'icon', class: 'bi-buildings', color: '#92400e', bg: '#fffbeb' },
        "Municipal Health Office": { type: 'icon', class: 'bi-heart-pulse', color: '#dc2626', bg: '#fef2f2' },
        "Municipal Library": { type: 'icon', class: 'bi-book', color: '#9333ea', bg: '#faf5ff' },
        "Municipal Registration Office": { type: 'icon', class: 'bi-card-list', color: '#2563eb', bg: '#eff6ff' },
        "Municipal Tourism Office": { type: 'icon', class: 'bi-sun', color: '#f59e0b', bg: '#fffbeb' },
        "Municipal Treasury Office": { type: 'icon', class: 'bi-bank', color: '#15803d', bg: '#f0fdf4' },
        "OMPDC": { type: 'icon', class: 'bi-bar-chart', color: '#334155', bg: '#f8fafc' },
        "Office of the SB Secretariat": { type: 'icon', class: 'bi-pen', color: '#4b5563', bg: '#f3f4f6' },
        "Public Market Office": { type: 'icon', class: 'bi-shop', color: '#b91c1c', bg: '#fef2f2' },
        "Slaughterhouse Section": { type: 'icon', class: 'bi-egg', color: '#78350f', bg: '#fffbeb' },
        "Real Property Tax Section": { type: 'icon', class: 'bi-house-lock', color: '#1e40af', bg: '#dbeafe' },
        "POSO": { type: 'icon', class: 'bi-shield-check', color: '#111827', bg: '#f3f4f6' },
        "Public Information Office": { type: 'icon', class: 'bi-megaphone', color: '#be185d', bg: '#fdf2f7' }
    };
    function fmt(state){
        if (!state.id) return state.text;
        
        const d = icons[state.text];
        let $state;

        if (d) {
            if (d.type === 'img') {
                $state = $('<span style="display:flex;align-items:center;gap:15px;"><img src="' + d.src + '" class="office-icon" /> <span>' + state.text + '</span></span>');
            } else {
                $state = $(
                    '<span style="display:flex;align-items:center;gap:15px;">' +
                    '<div class="office-logo-wrapper" style="background-color: ' + d.bg + ' !important">' +
                    '<i class="bi ' + d.class + '" style="color: ' + d.color + ' !important"></i>' +
                    '</div>' +
                    '<span>' + state.text + '</span>' +
                    '</span>'
                );
            }
        } else {
            $state = $('<span style="display:flex;align-items:center;gap:15px;"><div class="office-logo-wrapper"><i class="bi bi-building"></i></div><span>' + state.text + '</span></span>');
        }
        return $state;
    }
    $('#office').select2({theme:'bootstrap-5',placeholder:'Select Office',allowClear:true,templateResult:fmt,templateSelection:fmt});
    
    let submitBtn = document.getElementById('submitBtn');
    let formDisabled = false;
    
    function checkOfficeForm(officeName) {
        if (!officeName) {
            formDisabled = false;
            submitBtn.disabled = false;
            return;
        }
        
        const dateInput = document.getElementById('date');
        const selectedDate = dateInput.value ? new Date(dateInput.value) : new Date();
        const year = selectedDate.getFullYear();
        
        $.ajax({
            url: '../api/check_form_exists.php',
            method: 'POST',
            data: { office: officeName, year: year },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    formDisabled = true;
                    submitBtn.disabled = true;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Form Already Exists!',
                        text: 'The office "' + officeName + '" has already submitted a form for the year ' + year + '. New submissions are only allowed next year.',
                        confirmButtonColor: '#0f172a'
                    });
                } else {
                    formDisabled = false;
                    submitBtn.disabled = false;
                }
            },
            error: function() {
                console.error('Error checking form existence');
            }
        });
    }
    
    $('#office').on('change', function() {
        checkOfficeForm($(this).val());
    });
    
    $('#date').on('change', function() {
        checkOfficeForm($('#office').val());
    });
    
    document.getElementById('isspForm').addEventListener('submit', function(e) {
        if (formDisabled) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Submission Blocked',
                text: 'This office already has a form for this year.',
                confirmButtonColor: '#d33'
            });
            return false;
        }
    });
});
</script>
</body>
</html>