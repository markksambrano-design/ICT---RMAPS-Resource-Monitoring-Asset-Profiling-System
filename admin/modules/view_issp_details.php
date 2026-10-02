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

$office = isset($_GET['office']) ? mysqli_real_escape_string($conn, $_GET['office']) : '';
$date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

// Function to ensure columns exist
function ensureColumnsExist($conn, $table, $columns) {
    foreach ($columns as $column => $type) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        if (mysqli_num_rows($check) == 0) {
            mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $type");
        }
    }
}

$requiredColumns = [
    'isp_server' => 'TEXT',
    'lan_connection' => 'TEXT',
    'database_server' => 'TEXT',
    'other_isp' => 'TEXT',
    'isp_name' => 'VARCHAR(100)',
    'bandwidth' => 'VARCHAR(50)',
    'other_source' => 'TEXT',
    'pabx' => 'VARCHAR(50)',
    'telephone_numbers' => 'VARCHAR(50)',
    'base_radios' => 'INT DEFAULT 0',
    'handheld_personal' => 'INT DEFAULT 0',
    'handheld_lgu' => 'INT DEFAULT 0',
    'handheld_total' => 'INT DEFAULT 0',
    'equipment_image' => 'TEXT',
    'system1' => 'TEXT',
    'system2' => 'TEXT',
    'system3' => 'TEXT',
    'system4' => 'TEXT',
    'system5' => 'TEXT',
    'proposed_system1' => 'TEXT',
    'proposed_system2' => 'TEXT',
    'proposed_system3' => 'TEXT',
    'proposed_system4' => 'TEXT',
    'proposed_system5' => 'TEXT',
    'form_status' => "VARCHAR(20) DEFAULT 'Open'"
];

ensureColumnsExist($conn, 'user_issp_form', $requiredColumns);
ensureColumnsExist($conn, 'ict_forms', $requiredColumns);
ensureColumnsExist($conn, 'computer_equipment', ['equipment_image' => 'TEXT']);

// Handle form update or completion
if (isset($_POST['update_details']) || isset($_POST['complete_form'])) {
    $new_date_submitted = mysqli_real_escape_string($conn, $_POST['date_submitted'] ?? $date);
    $isp_server = mysqli_real_escape_string($conn, $_POST['isp_server'] ?? '');
    $lan_connection = mysqli_real_escape_string($conn, $_POST['lan_connection'] ?? '');
    $database_server = mysqli_real_escape_string($conn, $_POST['database_server'] ?? '');
    $other_isp = mysqli_real_escape_string($conn, $_POST['other_isp'] ?? '');
    $isp_name = mysqli_real_escape_string($conn, $_POST['isp_name'] ?? '');
    $bandwidth = mysqli_real_escape_string($conn, $_POST['bandwidth'] ?? '');
    $other_source = mysqli_real_escape_string($conn, $_POST['other_source'] ?? '');
    $pabx = mysqli_real_escape_string($conn, $_POST['pabx'] ?? '');
    $telephone_numbers = mysqli_real_escape_string($conn, $_POST['telephone_numbers'] ?? '');
    $base_radios = (int)($_POST['base_radios'] ?? 0);
    $handheld_personal = (int)($_POST['handheld_personal'] ?? 0);
    $handheld_lgu = (int)($_POST['handheld_lgu'] ?? 0);
    $handheld_total = (int)($_POST['handheld_total'] ?? 0);

    // Check if user_issp_form record exists to decide between INSERT or UPDATE
    $check_exists = mysqli_query($conn, "SELECT id FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$date' LIMIT 1");
    
    if (mysqli_num_rows($check_exists) == 0) {
        // Insert new record if none exists
        $update_user_issp = mysqli_query($conn, "INSERT INTO user_issp_form (
            office_name, date_submitted, isp_server, lan_connection, database_server, 
            other_isp, isp_name, bandwidth, other_source, pabx, telephone_numbers, 
            base_radios, handheld_personal, handheld_lgu, handheld_total, form_status
        ) VALUES (
            '$office', '$new_date_submitted', '$isp_server', '$lan_connection', '$database_server', 
            '$other_isp', '$isp_name', '$bandwidth', '$other_source', '$pabx', '$telephone_numbers', 
            $base_radios, $handheld_personal, $handheld_lgu, $handheld_total, 'Open'
        )");
    } else {
        // Update primary forms data and handle date update
        $update_user_issp = mysqli_query($conn, "UPDATE user_issp_form SET 
            date_submitted = '$new_date_submitted', 
            isp_server = '$isp_server', lan_connection = '$lan_connection', database_server = '$database_server', 
            other_isp = '$other_isp', isp_name = '$isp_name', bandwidth = '$bandwidth', other_source = '$other_source', 
            pabx = '$pabx', telephone_numbers = '$telephone_numbers', base_radios = '$base_radios', 
            handheld_personal = '$handheld_personal', handheld_lgu = '$handheld_lgu', handheld_total = '$handheld_total' 
            WHERE office_name = '$office' AND date_submitted = '$date'");
    }

    $update_ict_forms = mysqli_query($conn, "UPDATE ict_forms SET 
        date_submitted = '$new_date_submitted', 
        isp_server = '$isp_server', lan_connection = '$lan_connection', database_server = '$database_server', 
        other_isp = '$other_isp', isp_name = '$isp_name', bandwidth = '$bandwidth', other_source = '$other_source', 
        pabx = '$pabx', telephone_numbers = '$telephone_numbers', base_radios = '$base_radios', 
        handheld_personal = '$handheld_personal', handheld_lgu = '$handheld_lgu', handheld_total = '$handheld_total' 
        WHERE office_name = '$office' AND date_submitted = '$date'");

    $update_inventory = mysqli_query($conn, "UPDATE ict_equipment_inventory SET 
        date_submitted = '$new_date_submitted', 
        inkjet_printer = (SELECT inkjet_printer FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$new_date_submitted' LIMIT 1),
        deskjet_printer = (SELECT deskjet_printer FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$new_date_submitted' LIMIT 1),
        dotmatrix_printer = (SELECT dotmatrix_printer FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$new_date_submitted' LIMIT 1),
        switch_hubs = (SELECT switch_hubs FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$new_date_submitted' LIMIT 1),
        routers = (SELECT routers FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$new_date_submitted' LIMIT 1),
        modem = (SELECT modem FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$new_date_submitted' LIMIT 1)
        WHERE office_name = '$office' AND date_submitted = '$date'");

    // Update or insert internet_connections data
    $is_centralized_isp = ($isp_server == 'Yes') ? 1 : 0;
    $is_centralized_lan_wan = ($lan_connection == 'Yes') ? 1 : 0;
    $is_centralized_db_server = ($database_server == 'Yes') ? 1 : 0;
    $is_other_isp_via_office_plan = ($other_isp == 'Yes') ? 1 : 0;

    // Check if internet_connections record exists
    $check_internet = mysqli_query($conn, "SELECT id FROM internet_connections WHERE office_name = '$office' LIMIT 1");
    if (mysqli_num_rows($check_internet) > 0) {
        // Update existing record
        $update_internet = mysqli_query($conn, "UPDATE internet_connections SET 
            is_centralized_isp = $is_centralized_isp, 
            is_centralized_lan_wan = $is_centralized_lan_wan, 
            is_centralized_db_server = $is_centralized_db_server, 
            is_other_isp_via_office_plan = $is_other_isp_via_office_plan, 
            isp_name = '$isp_name', 
            bandwidth = '$bandwidth', 
            other_internet_source = '$other_source',
            pabx = '$pabx',
            telephone_numbers = '$telephone_numbers',
            base_radios = $base_radios,
            handheld_personal = $handheld_personal,
            handheld_lgu = $handheld_lgu,
            handheld_total = $handheld_total
            WHERE office_name = '$office'");
    } else {
        // Insert new record
        $update_internet = mysqli_query($conn, "INSERT INTO internet_connections (office_name, is_centralized_isp, is_centralized_lan_wan, is_centralized_db_server, is_other_isp_via_office_plan, isp_name, bandwidth, other_internet_source, pabx, telephone_numbers, base_radios, handheld_personal, handheld_lgu, handheld_total) 
            VALUES ('$office', $is_centralized_isp, $is_centralized_lan_wan, $is_centralized_db_server, $is_other_isp_via_office_plan, '$isp_name', '$bandwidth', '$other_source', '$pabx', '$telephone_numbers', $base_radios, $handheld_personal, $handheld_lgu, $handheld_total)");
    }

    if (isset($_POST['update_details'])) {
        if ($update_user_issp || $update_ict_forms || $update_inventory || $update_internet) {
            $success_update = true;
            // keep the variable for redirection/display consistent with new date
            $date = $new_date_submitted;
            $displayData['date_submitted'] = $new_date_submitted;
        }
    }
}

// Handle form completion
if (isset($_POST['complete_form'])) {
    $update_user_issp = mysqli_query($conn, "UPDATE user_issp_form SET form_status = 'Closed' WHERE office_name = '$office' AND date_submitted = '$date'");
    $update_ict_forms = mysqli_query($conn, "UPDATE ict_forms SET form_status = 'Closed' WHERE office_name = '$office' AND date_submitted = '$date'");

    // Get office_id
    $office_query = mysqli_query($conn, "SELECT id FROM offices WHERE office_name = '$office'");
    $office_row = mysqli_fetch_assoc($office_query);
    $office_id = $office_row['id'];

    // Ensure this record is written to the admin equipment form tables for inventory/report
    $form_id = null;
    $check_form = mysqli_query($conn, "SELECT id FROM form WHERE office_name = '$office' AND date_submitted = '$date' LIMIT 1");
    if ($check_form && mysqli_num_rows($check_form) > 0) {
        $row = mysqli_fetch_assoc($check_form);
        $form_id = $row['id'];
    } else {
        mysqli_query($conn, "INSERT INTO form (office_name, office_id, computer_equipment, date_submitted) VALUES ('$office', $office_id, 'both', '$date')");
        $form_id = mysqli_insert_id($conn);
    }

    if ($form_id) {
        // Reset existing detail rows (avoid duplicates on repeated complete clicks)
        mysqli_query($conn, "DELETE FROM computer_equipment WHERE form_id = $form_id");
        mysqli_query($conn, "DELETE FROM other_ict_equipment WHERE form_id = $form_id");
        mysqli_query($conn, "DELETE FROM systems WHERE form_id = $form_id");
        mysqli_query($conn, "DELETE FROM printer_devices WHERE form_id = $form_id");
        mysqli_query($conn, "DELETE FROM network_devices WHERE form_id = $form_id");

        // Insert computer equipment lines
        $user_items = mysqli_query($conn, "SELECT item, units, brand, processor, ram, hdd, ssd, equipment_image FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$date'");
        while ($item_row = mysqli_fetch_assoc($user_items)) {
            $item = mysqli_real_escape_string($conn, $item_row['item']);
            $units = (int)$item_row['units'];
            $brand = mysqli_real_escape_string($conn, $item_row['brand']);
            $processor = mysqli_real_escape_string($conn, $item_row['processor']);
            $ram = mysqli_real_escape_string($conn, $item_row['ram']);
            $hdd = mysqli_real_escape_string($conn, $item_row['hdd']);
            $ssd = mysqli_real_escape_string($conn, $item_row['ssd']);
            $equipment_image = mysqli_real_escape_string($conn, $item_row['equipment_image'] ?? '');
            mysqli_query($conn, "INSERT INTO computer_equipment (form_id, office_name, item, number_of_units, brand, processor, ram, hdd, ssd, equipment_image) VALUES ($form_id, '$office', '$item', $units, '$brand', '$processor', '$ram', '$hdd', '$ssd', '$equipment_image')");
        }

        // Insert Printer Devices from user submission
        $user_p_res = mysqli_query($conn, "SELECT printer_type, model, equipment_image FROM printer_devices WHERE form_id IN (SELECT id FROM form WHERE office_name = '$office' AND date_submitted = '$date')");
        // Wait, the printer_devices might already be in the table if they came from the user.
        // Let's re-think: the user's printer data is stored in `user_issp_form` in aggregated columns AND in `printer_devices` (if the user form does that).
        // Let's check user/Issp_form.php to see how it saves printer data.
        // Actually, user/Issp_form.php doesn't seem to save to printer_devices table yet, it saves to user_issp_form columns.
        
        // Let's just transfer the data from the $printer_devices array we already loaded.
        foreach ($printer_devices as $p) {
            if (!empty($p['printer_type'])) {
                $p_type = mysqli_real_escape_string($conn, $p['printer_type']);
                $p_model = mysqli_real_escape_string($conn, $p['model'] ?? '');
                $p_img = mysqli_real_escape_string($conn, $p['equipment_image'] ?? '');
                mysqli_query($conn, "INSERT INTO printer_devices (form_id, office_id, printer_type, model, quantity, equipment_image) 
                    VALUES ($form_id, $office_id, '$p_type', '$p_model', 1, " . ($p_img ? "'$p_img'" : "NULL") . ")");
            }
        }

        foreach ($network_devices as $n) {
            if (!empty($n['device_type'])) {
                $n_type = mysqli_real_escape_string($conn, $n['device_type']);
                $n_model = mysqli_real_escape_string($conn, $n['model'] ?? '');
                $n_img = mysqli_real_escape_string($conn, $n['equipment_image'] ?? '');
                mysqli_query($conn, "INSERT INTO network_devices (form_id, office_id, device_type, model, quantity, equipment_image) 
                    VALUES ($form_id, $office_id, '$n_type', '$n_model', 1, " . ($n_img ? "'$n_img'" : "NULL") . ")");
            }
        }

        // insert other_ict_equipment (take from first row in user_issp_form, with user-entry printer/network counts)
        $other = mysqli_query($conn, "SELECT inkjet_printer, inkjet_printer_model, deskjet_printer, deskjet_printer_model, dotmatrix_printer, dotmatrix_printer_model, switch_hubs, switch_hubs_model, routers, routers_model, modem, modem_model FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$date' ORDER BY id ASC LIMIT 1");
        if ($other && mysqli_num_rows($other) > 0) {
            $o = mysqli_fetch_assoc($other);
            $inkjet_printer = (int)$o['inkjet_printer'];
            $inkjet_printer_model = mysqli_real_escape_string($conn, $o['inkjet_printer_model']);
            $deskjet_printer = (int)$o['deskjet_printer'];
            $deskjet_printer_model = mysqli_real_escape_string($conn, $o['deskjet_printer_model']);
            $dotmatrix_printer = (int)$o['dotmatrix_printer'];
            $dotmatrix_printer_model = mysqli_real_escape_string($conn, $o['dotmatrix_printer_model']);
            $switch_hubs = (int)$o['switch_hubs'];
            $switch_hubs_model = mysqli_real_escape_string($conn, $o['switch_hubs_model']);
            $routers = (int)$o['routers'];
            $routers_model = mysqli_real_escape_string($conn, $o['routers_model']);
            $modem = (int)$o['modem'];
            $modem_model = mysqli_real_escape_string($conn, $o['modem_model']);

            mysqli_query($conn, "INSERT INTO other_ict_equipment (office_id, form_id, inkjet_printer, inkjet_printer_model, dot_matrix_printer, dot_matrix_printer_model, routers, routers_model, deskjet_printer, deskjet_printer_model, switch_hubs, switch_hubs_model, modem, modem_model) VALUES ($office_id, $form_id, $inkjet_printer, '$inkjet_printer_model', $dotmatrix_printer, '$dotmatrix_printer_model', $routers, '$routers_model', $deskjet_printer, '$deskjet_printer_model', $switch_hubs, '$switch_hubs_model', $modem, '$modem_model')");
        }

        // insert systems from first row
        $sys = mysqli_query($conn, "SELECT system1, system2, system3, system4, system5, proposed_system1, proposed_system2, proposed_system3, proposed_system4, proposed_system5 FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$date' ORDER BY id ASC LIMIT 1");
        if ($sys && mysqli_num_rows($sys) > 0) {
            $s = mysqli_fetch_assoc($sys);
            $system1 = mysqli_real_escape_string($conn, $s['system1']);
            $system2 = mysqli_real_escape_string($conn, $s['system2']);
            $system3 = mysqli_real_escape_string($conn, $s['system3']);
            $system4 = mysqli_real_escape_string($conn, $s['system4']);
            $system5 = mysqli_real_escape_string($conn, $s['system5']);
            $proposed_system1 = mysqli_real_escape_string($conn, $s['proposed_system1']);
            $proposed_system2 = mysqli_real_escape_string($conn, $s['proposed_system2']);
            $proposed_system3 = mysqli_real_escape_string($conn, $s['proposed_system3']);
            $proposed_system4 = mysqli_real_escape_string($conn, $s['proposed_system4']);
            $proposed_system5 = mysqli_real_escape_string($conn, $s['proposed_system5']);

            mysqli_query($conn, "INSERT INTO systems (office_id, form_id, system_1, system_2, system_3, system_4, system_5, proposed_system_1, proposed_system_2, proposed_system_3, proposed_system_4, proposed_system_5) VALUES ($office_id, $form_id, '$system1', '$system2', '$system3', '$system4', '$system5', '$proposed_system1', '$proposed_system2', '$proposed_system3', '$proposed_system4', '$proposed_system5')");
        }

        // Ensure internet_connections record exists for this form
        $check_internet = mysqli_query($conn, "SELECT id FROM internet_connections WHERE office_name = '$office' LIMIT 1");
        if (mysqli_num_rows($check_internet) == 0) {
            // Create a default record if none exists
            mysqli_query($conn, "INSERT INTO internet_connections (office_name, form_id, is_centralized_isp, is_centralized_lan_wan, is_centralized_db_server, is_other_isp_via_office_plan, pabx, telephone_numbers, base_radios, handheld_personal, handheld_lgu, handheld_total) 
                VALUES ('$office', $form_id, 0, 0, 0, 0, 'No', 'N/A', 0, 0, 0, 0)");
        } else {
            // Update the form_id if it exists
            $internet_row = mysqli_fetch_assoc($check_internet);
            mysqli_query($conn, "UPDATE internet_connections SET form_id = $form_id WHERE office_name = '$office'");
        }
    }

    if ($update_user_issp || $update_ict_forms) {
        $success_complete = true;
        // Update displayData status so button disappears
        $displayData['form_status'] = 'Closed';
    }
}

// Get data from user_issp_form for computer equipment and systems
$result = mysqli_query($conn, "SELECT * FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$date'");
$formData = [];
while($row = mysqli_fetch_assoc($result)){
    $formData[] = $row;
}

// If no data in user_issp_form, try ict_forms
if (empty($formData)) {
    $result = mysqli_query($conn, "SELECT * FROM ict_forms WHERE office_name = '$office' AND date_submitted = '$date'");
    while($row = mysqli_fetch_assoc($result)){
        $formData[] = $row;
    }
}

// Get data from ict_equipment_inventory for other equipment quantities
$inventory_result = mysqli_query($conn, "SELECT * FROM ict_equipment_inventory WHERE office_name = '$office' AND date_submitted = '$date' LIMIT 1");
$inventoryData = mysqli_fetch_assoc($inventory_result);

// Get data from internet_connections for network & communication data
$internet_result = mysqli_query($conn, "SELECT * FROM internet_connections WHERE office_name = '$office' LIMIT 1");
$internetData = mysqli_fetch_assoc($internet_result);

// Combine all items into a single record for display
$displayData = [];
if (!empty($formData)) {
    // Start with data from user_issp_form
    $displayData = $formData[0]; 
    
    // Add internet_connections data (network & communication)
    if ($internetData) {
        $displayData['isp_server'] = $internetData['is_centralized_isp'] ? 'Yes' : 'No';
        $displayData['lan_connection'] = $internetData['is_centralized_lan_wan'] ? 'Yes' : 'No';
        $displayData['database_server'] = $internetData['is_centralized_db_server'] ? 'Yes' : 'No';
        $displayData['other_isp'] = $internetData['is_other_isp_via_office_plan'] ? 'Yes' : 'No';
        $displayData['isp_name'] = $internetData['isp_name'] ?? '';
        $displayData['bandwidth'] = $internetData['bandwidth'] ?? '';
        $displayData['other_source'] = $internetData['other_internet_source'] ?? '';
        $displayData['pabx'] = $internetData['pabx'] ?? 'No';
        $displayData['telephone_numbers'] = $internetData['telephone_numbers'] ?? 'N/A';
        $displayData['base_radios'] = $internetData['base_radios'] ?? 0;
        $displayData['handheld_personal'] = $internetData['handheld_personal'] ?? 0;
        $displayData['handheld_lgu'] = $internetData['handheld_lgu'] ?? 0;
        $displayData['handheld_total'] = $internetData['handheld_total'] ?? 0;
    }
    
    // Add inventory data if it exists, but ONLY for keys that are empty or not set in displayData
    if ($inventoryData) {
        foreach($inventoryData as $key => $value) {
            $targetKey = $key;
            if (empty($displayData[$targetKey]) && !empty($value)) {
                $displayData[$targetKey] = $value;
            } else if (!isset($displayData[$targetKey])) {
                $displayData[$targetKey] = $value;
            }
        }
    }

    $displayData['items'] = [];
    foreach($formData as $row) {
        $displayData['items'][] = [
            'id' => $row['id'],
            'item' => $row['item'],
            'units' => $row['units'],
            'brand' => $row['brand'],
            'processor' => $row['processor'],
            'ram' => $row['ram'],
            'hdd' => $row['hdd'],
            'ssd' => $row['ssd'],
            'equipment_image' => $row['equipment_image'] ?? ''
        ];
    }
}

// Get printer and network devices
$printer_devices = [];
$network_devices = [];

// Try to get by office and date if form_id is not yet set
$office_id_query = mysqli_query($conn, "SELECT id FROM offices WHERE office_name = '$office'");
$office_id_row = mysqli_fetch_assoc($office_id_query);
$office_id = $office_id_row ? $office_id_row['id'] : 0;

// First, check if there's a form_id associated with this submission
$check_form = mysqli_query($conn, "SELECT id FROM form WHERE office_name = '$office' AND date_submitted = '$date' LIMIT 1");
if ($check_form && mysqli_num_rows($check_form) > 0) {
    $form_row = mysqli_fetch_assoc($check_form);
    $form_id = $form_row['id'];
    
    $p_res = mysqli_query($conn, "SELECT * FROM printer_devices WHERE form_id = $form_id");
    while($p = mysqli_fetch_assoc($p_res)) $printer_devices[] = $p;
    
    $n_res = mysqli_query($conn, "SELECT * FROM network_devices WHERE form_id = $form_id");
    while($n = mysqli_fetch_assoc($n_res)) $network_devices[] = $n;
}

// Fallback: If no printer devices found but we have legacy model strings, parse them
if (empty($printer_devices) && !empty($displayData)) {
    $types = [
        'Inkjet Printer' => ['qty' => 'inkjet_printer', 'model' => 'inkjet_printer_model'],
        'Deskjet Printer' => ['qty' => 'deskjet_printer', 'model' => 'deskjet_printer_model'],
        'Dot Matrix Printer' => ['qty' => 'dotmatrix_printer', 'model' => 'dotmatrix_printer_model']
    ];
    
    foreach ($types as $type => $cols) {
        $qty = (int)($displayData[$cols['qty']] ?? 0);
        $models_str = $displayData[$cols['model']] ?? '';
        if ($qty > 0) {
            $models = explode(', ', $models_str);
            for ($i = 0; $i < $qty; $i++) {
                $printer_devices[] = [
                    'printer_type' => $type,
                    'model' => $models[$i] ?? ($models[0] ?? ''),
                    'equipment_image' => null
                ];
            }
        }
    }
}

// Fallback for network devices
if (empty($network_devices) && !empty($displayData)) {
    $types = [
        'Switch Hubs' => ['qty' => 'switch_hubs', 'model' => 'switch_hubs_model'],
        'Routers' => ['qty' => 'routers', 'model' => 'routers_model'],
        'Modem' => ['qty' => 'modem', 'model' => 'modem_model']
    ];
    
    foreach ($types as $type => $cols) {
        $qty = (int)($displayData[$cols['qty']] ?? 0);
        $models_str = $displayData[$cols['model']] ?? '';
        if ($qty > 0) {
            $models = explode(', ', $models_str);
            for ($i = 0; $i < $qty; $i++) {
                $network_devices[] = [
                    'device_type' => $type,
                    'model' => $models[$i] ?? ($models[0] ?? ''),
                    'equipment_image' => null
                ];
            }
        }
    }
}

// Ensure at least one empty row for UI if still empty
if (empty($printer_devices)) $printer_devices[] = ['printer_type' => '', 'model' => '', 'equipment_image' => ''];
if (empty($network_devices)) $network_devices[] = ['device_type' => '', 'model' => '', 'equipment_image' => ''];

?>
<!DOCTYPE html>
<html>
<?php 
$pageTitle = 'RMAPS Form Details - ICTMIS';
$assetPath = '../../assest/css/admin';
?>
<?php include '../components/head.php'; ?>
<link rel="stylesheet" href="../../assest/css/admin/form.css">
<style>
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
.image-preview-small {
    max-width: 80px;
    max-height: 50px;
    margin-top: 5px;
    border-radius: 6px;
}
</style>
<?php if (isset($success_update) && $success_update): ?>
<script>
    $(document).ready(function() {
        Swal.fire({
            icon: 'success',
            title: 'Updated Successfully',
            text: 'Form details and images have been saved.',
            confirmButtonColor: '#0f172a'
        });
    });
</script>
<?php endif; ?>
</head>
<body>
<div class="d-flex">
    <?php 
    $adminBase = '../';
    include '../components/sidebar.php'; 
    ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <div class="issp-header animate__animated animate__fadeInDown">
            <div class="header-container shadow-sm border rounded">
                <div class="logo-left animate__animated animate__zoomIn">
                    <img src="../../assest/images/logo1.png" class="lgu-logo" alt="LGU Logo">
                </div>
                <div class="header-text">
                    <h2 class="animate__animated animate__fadeIn">DATA FOR THE FORMULATION OF INFORMATION SYSTEM STRATEGIC PLANNING 2026-2030</h2>
                </div>
                <div class="logo-right animate__animated animate__zoomIn">
                    <img src="../../assest/images/logo3.png" class="ict-logo" alt="ICTMIS Logo">
                </div>
            </div>
        </div>

        <div class="mb-3 animate__animated animate__fadeInLeft">
            <a href="view_issp_forms.php" class="btn btn-outline-dark shadow-sm">
                <i class="bi bi-arrow-left-circle me-2"></i>Back to List
            </a>
        </div>

        <div class="card shadow-sm border-0 animate__animated animate__fadeInUp">
<form method="POST" class="bordered-form" enctype="multipart/form-data">

<!-- OFFICE INFORMATION -->
<div class="card mb-4 border shadow-sm animate__animated animate__fadeInUp">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white" style="color: white !important;"><i class="bi bi-building me-2 text-white" style="color: white !important;"></i>Office Information</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small">Office Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border"><i class="bi bi-geo-alt"></i></span>
                    <input type="text" class="form-control bg-white border" value="<?php echo htmlspecialchars($displayData['office_name'] ?? ''); ?>" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small">Date Submitted</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border"><i class="bi bi-calendar-check"></i></span>
                    <input type="date" class="form-control" name="date_submitted" value="<?php echo htmlspecialchars($displayData['date_submitted'] ?? ''); ?>" required>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- COMPUTER EQUIPMENT -->
<div class="card mb-4 border shadow-sm animate__animated animate__fadeInUp">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white" style="color: white !important;"><i class="bi bi-pc me-2 text-white" style="color: white !important;"></i>Computer Equipment</span>
    </div>
    <div class="card-body" id="equipmentContainer">
        <?php foreach($displayData['items'] as $item): ?>
        <div class="equipment-row mb-3 border bg-light p-3 rounded shadow-sm">
            <div class="row">
                <!-- IMAGE COLUMN -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Image</label>
                    <div class="input-group input-group-sm">
                        <span class="form-control bg-white border">
                            <?php 
                                $image_filename = 'No file chosen';
                                if (!empty($item['equipment_image'])) {
                                    $image_filename = basename($item['equipment_image']);
                                }
                                echo htmlspecialchars($image_filename);
                            ?>
                        </span>
                    </div>
                </div>

                <!-- ITEM DETAILS -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Item</label>
                    <input type="text" class="form-control bg-white border" value="<?php echo htmlspecialchars($item['item']); ?>" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">No. of Units</label>
                    <input type="number" class="form-control bg-white border" value="<?php echo htmlspecialchars($item['units']); ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Brand</label>
                    <input type="text" class="form-control bg-white border" value="<?php echo htmlspecialchars($item['brand']); ?>" readonly>
                </div>
            </div>

            <!-- ROW 2: SPECS -->
            <div class="row mt-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Processor</label>
                    <input type="text" class="form-control bg-white border" value="<?php echo htmlspecialchars($item['processor']); ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">RAM</label>
                    <input type="text" class="form-control bg-white border" value="<?php echo htmlspecialchars($item['ram']); ?>" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">HDD</label>
                    <input type="text" class="form-control bg-white border" value="<?php echo htmlspecialchars($item['hdd'] ?? '-'); ?>" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">SSD</label>
                    <input type="text" class="form-control bg-white border" value="<?php echo htmlspecialchars($item['ssd'] ?? '-'); ?>" readonly>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- OTHER ICT EQUIPMENT -->
<div class="card mb-4 tech-card shadow-sm animate__animated animate__fadeInUp">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white" style="color: white !important;"><i class="bi bi-cpu me-2 text-white" style="color: white !important;"></i>Other ICT Equipment</span>
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
                            <th style="width:30%">Equipment Image</th>
                            <th style="width:25%">Printer Type</th>
                            <th style="width:25%">Model</th>
                            <th style="width:20%">No. of Units</th>
                        </tr>
                    </thead>
                    <tbody id="printer-devices-body">
                        <?php foreach($printer_devices as $p): ?>
                        <tr class="printer-row">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if(!empty($p['equipment_image'])): ?>
                                        <img src="../../uploads/equipment_images/<?php echo htmlspecialchars($p['equipment_image']); ?>" class="image-preview-small" alt="Preview">
                                        <div class="printer-file-name file-name-display"><?php echo basename($p['equipment_image']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">No image uploaded</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm bg-white border" value="<?php echo htmlspecialchars($p['printer_type'] ?? ''); ?>" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm bg-white border" value="<?php echo htmlspecialchars($p['model'] ?? ''); ?>" readonly>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm bg-white border" value="<?php echo htmlspecialchars($p['quantity'] ?? 1); ?>" readonly>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
                            <th style="width:30%">Equipment Image</th>
                            <th style="width:25%">Device Type</th>
                            <th style="width:25%">Model</th>
                            <th style="width:20%">No. of Units</th>
                        </tr>
                    </thead>
                    <tbody id="network-devices-body">
                        <?php foreach($network_devices as $n): ?>
                        <tr class="network-row">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if(!empty($n['equipment_image'])): ?>
                                        <img src="../../uploads/equipment_images/<?php echo htmlspecialchars($n['equipment_image']); ?>" class="image-preview-small" alt="Preview">
                                        <div class="network-file-name file-name-display"><?php echo basename($n['equipment_image']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">No image uploaded</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm bg-white border" value="<?php echo htmlspecialchars($n['device_type'] ?? ''); ?>" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm bg-white border" value="<?php echo htmlspecialchars($n['model'] ?? ''); ?>" readonly>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm bg-white border" value="<?php echo htmlspecialchars($n['quantity'] ?? 1); ?>" readonly>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- EXISTING COMPUTERIZED SYSTEMS -->
<div class="card mb-4 border shadow-sm animate__animated animate__fadeInUp">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white" style="color: white !important;"><i class="bi bi-pc-display me-2 text-white" style="color: white !important;"></i>Existing Computerized Systems</span>
    </div>
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th class="px-4 py-3">System Name</th>
                </tr>
            </thead>
            <tbody>
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <?php if(!empty($displayData['system'.$i])): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($displayData['system'.$i]); ?>" readonly>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- PROPOSED SYSTEMS -->
<div class="card mb-4 border shadow-sm animate__animated animate__fadeInUp">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white" style="color: white !important;"><i class="bi bi-lightbulb me-2 text-white" style="color: white !important;"></i>Proposed Systems</span>
    </div>
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th class="px-4 py-3">Proposed System</th>
                </tr>
            </thead>
            <tbody>
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <?php if(!empty($displayData['proposed_system'.$i])): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($displayData['proposed_system'.$i]); ?>" readonly>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- FILL IN INSTRUCTION -->
<div class="mb-3 animate__animated animate__fadeInLeft">
    <h5 class="text-secondary fw-bold text-uppercase small" style="letter-spacing: 1px;">
        <i class="bi bi-pencil-square me-2"></i>Please fill in the details below:
    </h5>
</div>

<!-- INTERNET CONNECTION -->
<div class="card mb-4 border shadow-sm animate__animated animate__fadeInUp">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white" style="color: white !important;"><i class="bi bi-wifi me-2 text-white" style="color: white !important;"></i>Internet Connections</span>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="70%">Connection Type</th>
                    <th class="text-center">YES</th>
                    <th class="text-center">NO</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Connected to CENTRALIZED ISP SERVER</td>
                    <td class="text-center">
                        <input type="radio" name="isp_server" value="Yes" <?php echo ($displayData['isp_server'] ?? '') == 'Yes' ? 'checked' : ''; ?>>
                    </td>
                    <td class="text-center">
                        <input type="radio" name="isp_server" value="No" <?php echo ($displayData['isp_server'] ?? '') == 'No' ? 'checked' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <td>Connected to CENTRALIZED LAN / WAN CONNECTION</td>
                    <td class="text-center">
                        <input type="radio" name="lan_connection" value="Yes" <?php echo ($displayData['lan_connection'] ?? '') == 'Yes' ? 'checked' : ''; ?>>
                    </td>
                    <td class="text-center">
                        <input type="radio" name="lan_connection" value="No" <?php echo ($displayData['lan_connection'] ?? '') == 'No' ? 'checked' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <td>Connected to CENTRALIZED DATABASE SERVER</td>
                    <td class="text-center">
                        <input type="radio" name="database_server" value="Yes" <?php echo ($displayData['database_server'] ?? '') == 'Yes' ? 'checked' : ''; ?>>
                    </td>
                    <td class="text-center">
                        <input type="radio" name="database_server" value="No" <?php echo ($displayData['database_server'] ?? '') == 'No' ? 'checked' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <td>Connected to OTHER ISPs via Office Paid Plan</td>
                    <td class="text-center">
                        <input type="radio" name="other_isp" value="Yes" <?php echo ($displayData['other_isp'] ?? '') == 'Yes' ? 'checked' : ''; ?>>
                    </td>
                    <td class="text-center">
                        <input type="radio" name="other_isp" value="No" <?php echo ($displayData['other_isp'] ?? '') == 'No' ? 'checked' : ''; ?>>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="row mt-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small">ISP Name</label>
                <input type="text" name="isp_name" class="form-control" value="<?php echo htmlspecialchars($displayData['isp_name'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-muted small">Bandwidth</label>
                <div class="input-group">
                    <input type="text" name="bandwidth" class="form-control" value="<?php echo htmlspecialchars($displayData['bandwidth'] ?? ''); ?>">
                    <span class="input-group-text bg-light">mbps</span>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <label class="form-label fw-semibold text-muted small">Other Internet Connection Source</label>
            <input type="text" name="other_source" class="form-control" value="<?php echo htmlspecialchars($displayData['other_source'] ?? ''); ?>">
        </div>
    </div>
</div>

<!-- COMMUNICATION -->
<div class="card mb-4 border shadow-sm animate__animated animate__fadeInUp">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white" style="color: white !important;"><i class="bi bi-telephone me-2 text-white" style="color: white !important;"></i>Communication</span>
    </div>
    <div class="card-body">
        <label class="form-label">Connected to PBAX System (with Digital Telephone Unit with Local#) (075) 633-7180</label>
        <div class="d-flex gap-4 mb-3">
            <div class="form-check">
                <input type="radio" name="pabx" value="Yes" class="form-check-input" <?php echo ($displayData['pabx'] ?? '') == 'Yes' ? 'checked' : ''; ?>>
                <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check">
                <input type="radio" name="pabx" value="No" class="form-check-input" <?php echo ($displayData['pabx'] ?? '') == 'No' ? 'checked' : ''; ?>>
                <label class="form-check-label">No</label>
            </div>
        </div>
        
        <label class="form-label fw-semibold text-muted small">Others, if existing, pls. indicate Office Telephone Number/s:</label>
        <input type="text" name="telephone_numbers" class="form-control" value="<?php echo htmlspecialchars($displayData['telephone_numbers'] ?? ''); ?>">
    </div>
</div>

<!-- RADIO COMMUNICATION EQUIPMENT -->
<div class="card mb-4 border shadow-sm animate__animated animate__fadeInUp">
    <div class="card-header bg-dark d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white" style="color: white !important;"><i class="bi bi-broadcast me-2 text-white" style="color: white !important;"></i>Radio Communication Equipment</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Base Radios</label>
                <input type="number" name="base_radios" class="form-control" value="<?php echo htmlspecialchars($displayData['base_radios'] ?? 0); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Handheld Personal</label>
                <input type="number" name="handheld_personal" class="form-control" value="<?php echo htmlspecialchars($displayData['handheld_personal'] ?? 0); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Handheld LGU</label>
                <input type="number" name="handheld_lgu" class="form-control" value="<?php echo htmlspecialchars($displayData['handheld_lgu'] ?? 0); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted small">Total</label>
                <input type="number" name="handheld_total" class="form-control" value="<?php echo htmlspecialchars($displayData['handheld_total'] ?? 0); ?>">
            </div>
        </div>
    </div>
</div>

<!-- SAVE AND COMPLETE BUTTONS -->
<div class="text-center mb-4 d-flex justify-content-center gap-3">
    <button type="submit" name="update_details" class="btn btn-primary shadow-sm px-4 rounded-pill">
        <i class="bi bi-save me-2"></i>Save Changes
    </button>
    <?php if (($displayData['form_status'] ?? '') != 'Closed'): ?>
    <button type="submit" name="complete_form" id="btnCompleteForm" class="btn btn-success shadow-sm px-4 rounded-pill">
        <i class="bi bi-check-circle me-2"></i>Mark as Complete
    </button>
    <?php else: ?>
    <button type="button" class="btn btn-outline-success shadow-sm px-4 rounded-pill" disabled>
        <i class="bi bi-check-circle-fill me-2"></i>Form Completed
    </button>
    <?php endif; ?>
</div>



</form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
function addPrinterRow(btn){/* Read-only in admin view */}
function removePrinterRow(btn){/* Read-only in admin view */}
function triggerPrinterImageUpload(btn){/* Read-only in admin view */}

// NETWORK ROWS
function addNetworkRow(btn){/* Read-only in admin view */}
function removeNetworkRow(btn){/* Read-only in admin view */}
function triggerNetworkImageUpload(btn){/* Read-only in admin view */}

$(document).ready(function() {
    $('#btnCompleteForm').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        
        Swal.fire({
            title: 'Mark as Complete?',
            text: 'Are you sure you want to mark this form as complete? It will be removed from the pending list and added to history.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, complete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Add a hidden input to simulate the button click for PHP
                $('<input>').attr({
                    type: 'hidden',
                    name: 'complete_form',
                    value: '1'
                }).appendTo(form);
                form.submit();
            }
        });
    });
});

<?php if(isset($success_update) && $success_update): ?>
Swal.fire({
    title: 'Success!',
    text: 'Details updated successfully!',
    icon: 'success',
    confirmButtonColor: '#0f172a',
    confirmButtonText: 'OK'
});
<?php endif; ?>

<?php if(isset($success_complete) && $success_complete): ?>
Swal.fire({
    title: 'Form Completed!',
    text: 'This form has been marked as complete and will be removed from the pending list.',
    icon: 'success',
    confirmButtonColor: '#10b981',
    confirmButtonText: 'Great!'
});
<?php endif; ?>
</script>
</body>
</html>