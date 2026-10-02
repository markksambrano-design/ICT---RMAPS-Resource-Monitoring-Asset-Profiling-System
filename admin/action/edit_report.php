<?php
include "../../config.php";

if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: ../report.php");
    exit;
}

// Fetch main form
$form_res = mysqli_query($conn, "SELECT * FROM form WHERE id = $id");
$form_data = mysqli_fetch_assoc($form_res);

if (!$form_data) {
    header("Location: ../report.php");
    exit;
}

// Fetch computer equipment
$equipment_data = [];
$equip_res = mysqli_query($conn, "SELECT * FROM computer_equipment WHERE form_id = $id");
while ($row = mysqli_fetch_assoc($equip_res)) {
    $equipment_data[] = $row;
}

// Fetch printer devices
$printer_devices = [];
$printer_res = mysqli_query($conn, "SELECT * FROM printer_devices WHERE form_id = $id");
while ($row = mysqli_fetch_assoc($printer_res)) {
    $printer_devices[] = $row;
}

// Fetch network devices
$network_devices = [];
$network_res = mysqli_query($conn, "SELECT * FROM network_devices WHERE form_id = $id");
while ($row = mysqli_fetch_assoc($network_res)) {
    $network_devices[] = $row;
}

// Fetch other equipment (for backward compatibility)
$other_res = mysqli_query($conn, "SELECT * FROM other_ict_equipment WHERE form_id = $id");
$other_equipment_data = mysqli_fetch_assoc($other_res);

// Fetch internet connectivity
$internet_res = mysqli_query($conn, "SELECT * FROM internet_connections WHERE form_id = $id");
$internet_data = mysqli_fetch_assoc($internet_res);

// Fetch systems
$systems_res = mysqli_query($conn, "SELECT * FROM systems WHERE form_id = $id");
$systems_data = mysqli_fetch_assoc($systems_res);

// Helper function to get equipment image URL
function getEquipmentImageUrl($image_filename) {
    if (empty($image_filename)) {
        return '../../assest/images/no-image.png';
    }
    if (strpos($image_filename, 'uploads/') !== false) {
        $image_filename = basename($image_filename);
    }
    $base_url = '../../uploads/equipment_images/';
    return $base_url . $image_filename;
}

if(isset($_POST['submit'])){
    $office = mysqli_real_escape_string($conn, $_POST['office'] ?? '');
    $date = mysqli_real_escape_string($conn, $_POST['date'] ?? '');

    // Equipment images for computer equipment
    $equipment_images = $_FILES['equipment_image'] ?? null;
    $uploaded_images = [];
    $existing_images = $_POST['existing_equipment_image'] ?? [];
    $upload_dir = '../../uploads/equipment_images/';

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
    $existing_printer_images = $_POST['existing_printer_image'] ?? [];
    $uploaded_printer_images = [];

    if ($printer_images && isset($printer_images['name']) && is_array($printer_images['name'])) {
        foreach ($printer_images['name'] as $key => $image_name) {
            $uploaded_printer_images[$key] = $existing_printer_images[$key] ?? null;
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
    $existing_network_images = $_POST['existing_network_image'] ?? [];
    $uploaded_network_images = [];

    if ($network_images && isset($network_images['name']) && is_array($network_images['name'])) {
        foreach ($network_images['name'] as $key => $image_name) {
            $uploaded_network_images[$key] = $existing_network_images[$key] ?? null;
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

    $items = $_POST['item'] ?? [];
    $units_arr = $_POST['units'] ?? [];
    $brands = $_POST['brand'] ?? [];
    $processors = $_POST['processor'] ?? [];
    $rams = $_POST['ram'] ?? [];
    $hdds = $_POST['hdd'] ?? [];
    $ssds = $_POST['ssd'] ?? [];

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

        // Update form
        $form_update = mysqli_query($conn, "UPDATE form SET office_name = '$office', office_id = $office_id, computer_equipment = '$comp_equip', desktop_computer_units = $total_desktop, laptop_units = $total_laptop, date_submitted = '$date' WHERE id = $id");

        if($form_update) {
            // Delete existing records
            mysqli_query($conn, "DELETE FROM computer_equipment WHERE form_id = $id");
            mysqli_query($conn, "DELETE FROM printer_devices WHERE form_id = $id");
            mysqli_query($conn, "DELETE FROM network_devices WHERE form_id = $id");
            mysqli_query($conn, "DELETE FROM other_ict_equipment WHERE form_id = $id");
            mysqli_query($conn, "DELETE FROM systems WHERE form_id = $id");
            mysqli_query($conn, "DELETE FROM internet_connections WHERE form_id = $id");
            mysqli_query($conn, "DELETE FROM ict_equipment_inventory WHERE form_id = $id");

            // Insert Printer Devices
            foreach($printer_types as $key => $type) {
                if(!empty($type)) {
                    $printer_type_val = mysqli_real_escape_string($conn, $type);
                    $printer_model_val = isset($printer_models[$key]) ? mysqli_real_escape_string($conn, $printer_models[$key]) : '';
                    $printer_qty = isset($printer_quantities[$key]) ? (int)$printer_quantities[$key] : 1;
                    $printer_image = isset($uploaded_printer_images[$key]) ? mysqli_real_escape_string($conn, $uploaded_printer_images[$key]) : null;

                    $printer_insert = mysqli_query($conn, "INSERT INTO printer_devices (form_id, office_id, printer_type, model, quantity, equipment_image)
                        VALUES ($id, $office_id, '$printer_type_val', '$printer_model_val', $printer_qty, " . ($printer_image ? "'$printer_image'" : "NULL") . ")");

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
                        VALUES ($id, $office_id, '$network_type_val', '$network_model_val', $network_qty, " . ($network_image ? "'$network_image'" : "NULL") . ")");

                    if(!$network_insert) $all_success = false;
                }
            }

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
                VALUES ($id, $office_id,
                $inkjet_printer, '$inkjet_printer_model', $deskjet_printer, '$deskjet_printer_model',
                $dotmatrix_printer, '$dotmatrix_printer_model', $switch_hubs, '$switch_hubs_model',
                $routers, '$routers_model', $modem, '$modem_model')");

            $sys_insert = mysqli_query($conn, "INSERT INTO systems (form_id, office_id, system_1, system_2, system_3, system_4, system_5, proposed_system_1, proposed_system_2, proposed_system_3, proposed_system_4, proposed_system_5)
                VALUES ($id, $office_id, '$system1', '$system2', '$system3', '$system4', '$system5', '$proposed_system1', '$proposed_system2', '$proposed_system3', '$proposed_system4', '$proposed_system5')");

            $is_centralized_isp = ($isp_server == 'Yes') ? 1 : 0;
            $is_centralized_lan_wan = ($lan_connection == 'Yes') ? 1 : 0;
            $is_centralized_db_server = ($database_server == 'Yes') ? 1 : 0;
            $is_other_isp_via_office_plan = ($other_isp == 'Yes') ? 1 : 0;

            $ic_insert = mysqli_query($conn, "INSERT INTO internet_connections (office_name, form_id, is_centralized_isp, is_centralized_lan_wan, is_centralized_db_server, is_other_isp_via_office_plan, isp_name, bandwidth, other_internet_source, pabx, telephone_numbers, base_radios, handheld_personal, handheld_lgu, handheld_total)
                VALUES ('$office', $id, $is_centralized_isp, $is_centralized_lan_wan, $is_centralized_db_server, $is_other_isp_via_office_plan, '$isp_name', '$bandwidth', '$other_source', '$pabx', '$telephone_numbers', $base_radios, $handheld_personal, $handheld_lgu, $handheld_total)");

            $is_first_item = true;
            foreach($items as $key => $val) {
                $item_val = mysqli_real_escape_string($conn, $items[$key] ?? '');
                $units_val = isset($units_arr[$key]) ? (int)$units_arr[$key] : 1;
                $brand_val = mysqli_real_escape_string($conn, $brands[$key] ?? '');
                $processor_val = mysqli_real_escape_string($conn, $processors[$key] ?? '');
                $ram_val = mysqli_real_escape_string($conn, $rams[$key] ?? '');
                $hdd_val = mysqli_real_escape_string($conn, $hdds[$key] ?? '');
                $ssd_val = mysqli_real_escape_string($conn, $ssds[$key] ?? '');
                $image_filename = null;
                if (isset($uploaded_images[$key]) && !empty($uploaded_images[$key])) {
                    $image_filename = $uploaded_images[$key];
                } elseif (isset($existing_images[$key]) && !empty($existing_images[$key])) {
                    $image_filename = mysqli_real_escape_string($conn, $existing_images[$key]);
                }

                if(!empty($item_val)) {
                    $ce_insert = mysqli_query($conn, "INSERT INTO computer_equipment (form_id, office_name, item, number_of_units, brand, processor, ram, hdd, ssd, equipment_image)
                        VALUES ($id, '$office', '$item_val', $units_val, '$brand_val', '$processor_val', '$ram_val', '$hdd_val', '$ssd_val', " . ($image_filename ? "'$image_filename'" : "NULL") . ")");

                    $inv_inkjet = $is_first_item ? $inkjet_printer : 0;
                    $inv_deskjet = $is_first_item ? $deskjet_printer : 0;
                    $inv_dotmatrix = $is_first_item ? $dotmatrix_printer : 0;
                    $inv_hubs = $is_first_item ? $switch_hubs : 0;
                    $inv_routers = $is_first_item ? $routers : 0;
                    $inv_modem = $is_first_item ? $modem : 0;

                    $inv_insert = mysqli_query($conn, "INSERT INTO ict_equipment_inventory (office_name, form_id, date_submitted, item, units, brand, processor, ram, hdd, ssd, inkjet_printer, inkjet_printer_model, deskjet_printer, deskjet_printer_model, dotmatrix_printer, dotmatrix_printer_model, switch_hubs, switch_hubs_model, routers, routers_model, modem, modem_model)
                        VALUES ('$office', $id, '$date', '$item_val', $units_val, '$brand_val', '$processor_val', '$ram_val', '$hdd_val', '$ssd_val', $inv_inkjet, '$inkjet_printer_model', $inv_deskjet, '$deskjet_printer_model', $inv_dotmatrix, '$dotmatrix_printer_model', $inv_hubs, '$switch_hubs_model', $inv_routers, '$routers_model', $inv_modem, '$modem_model')");

                    $is_first_item = false;
                }
            }

            if(!$form_update || !$oie_insert || !$sys_insert || !$ic_insert){
                $all_success = false;
            }
        } else {
            $all_success = false;
        }
    }

    if($all_success){
        $success = true;
        header("Location: ../report.php?msg=updated");
        exit;
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php $pageTitle = 'Edit Report - ICTMIS'; ?>
    <?php $assetPath = '../../assest/css/admin'; ?>
    <?php include '../components/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0 30px 30px 30px;
        }

        /* Fix header - make it sticky */
        .top-header {
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            margin: 0 -30px 20px -30px !important;
        }

        .edit-container {
            width: 100%;
        }

        .edit-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            color: white;
        }

        .header-text h1 {
            color: #2d3748;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header-text p {
            color: #718096;
            font-size: 14px;
        }

        .back-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(240, 147, 251, 0.4);
        }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            width: 100%;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }

        .section-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .section-title h2 {
            color: #2d3748;
            font-size: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group label .required {
            color: #e53e3e;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f7fafc;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .equipment-row {
            background: linear-gradient(135deg, #f6f8ff 0%, #f0f4ff 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 2px solid #e2e8f0;
        }

        .row-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .row-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-danger-sm {
            padding: 8px 15px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger-sm:hover {
            transform: scale(1.05);
        }

        .btn-add {
            padding: 12px 25px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(17, 153, 142, 0.4);
        }

        .devices-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        .devices-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .devices-table th {
            padding: 16px;
            color: white;
            font-weight: 600;
            text-align: left;
            font-size: 14px;
        }

        .devices-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .devices-table tbody tr:hover {
            background: #f7fafc;
        }

        .table-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            margin: 0 3px;
            transition: all 0.2s ease;
        }

        .table-btn-add {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .table-btn-remove {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            color: white;
        }

        .radio-group {
            display: flex;
            gap: 30px;
            margin-bottom: 15px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .radio-item input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: #667eea;
        }

        .radio-item span {
            font-size: 15px;
            color: #4a5568;
            font-weight: 500;
        }

        .systems-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .system-item {
            display: flex;
            gap: 10px;
        }

        .system-item input {
            flex: 1;
        }

        .btn-submit {
            width: 100%;
            padding: 18px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 30px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .image-preview {
            margin-top: 10px;
        }

        .image-preview img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
        }

        .image-preview small {
            display: block;
            margin-top: 5px;
            color: #718096;
            font-size: 12px;
        }

        .remove-btn-hidden {
            display: none !important;
        }

        .input-group-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
        }

        .input-group {
            display: flex;
        }

        .input-group .form-control {
            border-radius: 12px 0 0 12px;
        }

        .input-group .input-group-text {
            border-radius: 0 12px 12px 0;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include '../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include '../components/header.php'; ?>
        <div class="edit-container">
        <div class="edit-header">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="header-text">
                    <h1>Edit ISSP Report</h1>
                    <p><strong>Office:</strong> <?php echo htmlspecialchars($form_data['office_name']); ?></p>
                </div>
            </div>
            <a href="../report.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" id="editReportForm">
            <!-- Office Information -->
            <div class="form-card">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h2>Office Information</h2>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Office Name <span class="required">*</span></label>
                        <select name="office" class="form-select" disabled>
                            <option value=""><?php echo htmlspecialchars($form_data['office_name']); ?></option>
                        </select>
                        <input type="hidden" name="office" value="<?php echo htmlspecialchars($form_data['office_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Date Submitted <span class="required">*</span></label>
                        <input type="date" name="date" class="form-control" value="<?php echo $form_data['date_submitted']; ?>" required>
                    </div>
                </div>
            </div>

            <!-- Computer Equipment -->
            <div class="form-card">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h2>Computer Equipment</h2>
                </div>
                <button type="button" class="btn-add" onclick="addEquipment()">
                    <i class="fas fa-plus"></i> Add Equipment
                </button>
                <div id="equipmentContainer">
                    <?php foreach ($equipment_data as $index => $equip): ?>
                    <div class="equipment-row">
                        <div class="row-header">
                            <span class="row-number">Equipment #<?php echo $index + 1; ?></span>
                            <button type="button" class="btn-danger-sm remove-btn-hidden" onclick="removeRow(this)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Image</label>
                                <input type="file" name="equipment_image[]" class="form-control" accept="image/jpeg,image/png,image/jpg">
                                <?php if (!empty($equip['equipment_image'])): ?>
                                    <div class="image-preview">
                                        <img src="<?php echo getEquipmentImageUrl($equip['equipment_image']); ?>" alt="Current">
                                        <small>Current image</small>
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="existing_equipment_image[]" value="<?php echo htmlspecialchars($equip['equipment_image']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Item <span class="required">*</span></label>
                                <select name="item[]" class="form-select" required>
                                    <option value="">Select Item</option>
                                    <option value="Desktop Computer" <?php echo ($equip['item'] == 'Desktop Computer') ? 'selected' : ''; ?>>Desktop Computer</option>
                                    <option value="Laptop" <?php echo ($equip['item'] == 'Laptop') ? 'selected' : ''; ?>>Laptop</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>No. of Units</label>
                                <input type="number" name="units[]" class="form-control" value="<?php echo htmlspecialchars($equip['number_of_units']); ?>" required min="1">
                            </div>
                            <div class="form-group">
                                <label>Brand <span class="required">*</span></label>
                                <input type="text" name="brand[]" class="form-control" value="<?php echo htmlspecialchars($equip['brand']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Processor <span class="required">*</span></label>
                                <select name="processor[]" class="form-select" required>
                                    <option value="">-- Select Processor --</option>
                                    <option value="Intel Core i3" <?php echo ($equip['processor'] == 'Intel Core i3') ? 'selected' : ''; ?>>Intel Core i3</option>
                                    <option value="Intel Core i5" <?php echo ($equip['processor'] == 'Intel Core i5') ? 'selected' : ''; ?>>Intel Core i5</option>
                                    <option value="Intel Core i7" <?php echo ($equip['processor'] == 'Intel Core i7') ? 'selected' : ''; ?>>Intel Core i7</option>
                                    <option value="Intel Core i9" <?php echo ($equip['processor'] == 'Intel Core i9') ? 'selected' : ''; ?>>Intel Core i9</option>
                                    <option value="AMD Ryzen 3" <?php echo ($equip['processor'] == 'AMD Ryzen 3') ? 'selected' : ''; ?>>AMD Ryzen 3</option>
                                    <option value="AMD Ryzen 5" <?php echo ($equip['processor'] == 'AMD Ryzen 5') ? 'selected' : ''; ?>>AMD Ryzen 5</option>
                                    <option value="AMD Ryzen 7" <?php echo ($equip['processor'] == 'AMD Ryzen 7') ? 'selected' : ''; ?>>AMD Ryzen 7</option>
                                    <option value="AMD Ryzen 9" <?php echo ($equip['processor'] == 'AMD Ryzen 9') ? 'selected' : ''; ?>>AMD Ryzen 9</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>RAM <span class="required">*</span></label>
                                <select name="ram[]" class="form-select" required>
                                    <option value="">-- Select RAM --</option>
                                    <option value="4 GB" <?php echo ($equip['ram'] == '4 GB') ? 'selected' : ''; ?>>4 GB</option>
                                    <option value="8 GB" <?php echo ($equip['ram'] == '8 GB') ? 'selected' : ''; ?>>8 GB</option>
                                    <option value="16 GB" <?php echo ($equip['ram'] == '16 GB') ? 'selected' : ''; ?>>16 GB</option>
                                    <option value="32 GB" <?php echo ($equip['ram'] == '32 GB') ? 'selected' : ''; ?>>32 GB</option>
                                    <option value="64 GB" <?php echo ($equip['ram'] == '64 GB') ? 'selected' : ''; ?>>64 GB</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>HDD</label>
                                <select name="hdd[]" class="form-select">
                                    <option value="">-- Select HDD --</option>
                                    <option value="500 GB HDD" <?php echo ($equip['hdd'] == '500 GB HDD') ? 'selected' : ''; ?>>500 GB HDD</option>
                                    <option value="1 TB HDD" <?php echo ($equip['hdd'] == '1 TB HDD') ? 'selected' : ''; ?>>1 TB HDD</option>
                                    <option value="2 TB HDD" <?php echo ($equip['hdd'] == '2 TB HDD') ? 'selected' : ''; ?>>2 TB HDD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>SSD</label>
                                <select name="ssd[]" class="form-select">
                                    <option value="">-- Select SSD --</option>
                                    <option value="128 GB SSD" <?php echo ($equip['ssd'] == '128 GB SSD') ? 'selected' : ''; ?>>128 GB SSD</option>
                                    <option value="256 GB SSD" <?php echo ($equip['ssd'] == '256 GB SSD') ? 'selected' : ''; ?>>256 GB SSD</option>
                                    <option value="512 GB SSD" <?php echo ($equip['ssd'] == '512 GB SSD') ? 'selected' : ''; ?>>512 GB SSD</option>
                                    <option value="1 TB SSD" <?php echo ($equip['ssd'] == '1 TB SSD') ? 'selected' : ''; ?>>1 TB SSD</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Other ICT Equipment -->
            <div class="form-card">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h2>Other ICT Equipment</h2>
                </div>

                <!-- Printer Devices -->
                <h3 style="margin-bottom: 20px; color: #4a5568; font-size: 18px;"><i class="fas fa-print" style="color: #667eea;"></i> Printer Devices</h3>
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>Printer Type</th>
                            <th>Model</th>
                            <th>Quantity</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="printer-devices-body">
                        <?php 
                        $printers_to_show = count($printer_devices) > 0 ? $printer_devices : [['printer_type' => '', 'model' => '', 'quantity' => 1, 'equipment_image' => '']];
                        foreach ($printers_to_show as $index => $printer): ?>
                        <tr class="printer-row">
                            <td>
                                <select name="printer_type[]" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="Inkjet Printer" <?php echo ($printer['printer_type'] == 'Inkjet Printer') ? 'selected' : ''; ?>>Inkjet Printer</option>
                                    <option value="Deskjet Printer" <?php echo ($printer['printer_type'] == 'Deskjet Printer') ? 'selected' : ''; ?>>Deskjet Printer</option>
                                    <option value="Dot Matrix Printer" <?php echo ($printer['printer_type'] == 'Dot Matrix Printer') ? 'selected' : ''; ?>>Dot Matrix Printer</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="printer_model[]" class="form-control" placeholder="Model" value="<?php echo htmlspecialchars($printer['model']); ?>">
                            </td>
                            <td>
                                <input type="number" name="printer_quantity[]" class="form-control" value="<?php echo htmlspecialchars($printer['quantity']); ?>" min="1">
                            </td>
                            <td>
                                <input type="file" name="printer_image[]" class="form-control" accept="image/jpeg,image/png,image/jpg">
                                <?php if (!empty($printer['equipment_image'])): ?>
                                    <div class="image-preview">
                                        <img src="<?php echo getEquipmentImageUrl($printer['equipment_image']); ?>" alt="Current">
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="existing_printer_image[]" value="<?php echo htmlspecialchars($printer['equipment_image']); ?>">
                            </td>
                            <td>
                                <button type="button" class="table-btn table-btn-add" onclick="addPrinterRow(this)">+</button>
                                <button type="button" class="table-btn table-btn-remove remove-btn-hidden" onclick="removePrinterRow(this)">-</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <br><br>

                <!-- Network Devices -->
                <h3 style="margin-bottom: 20px; color: #4a5568; font-size: 18px;"><i class="fas fa-network-wired" style="color: #667eea;"></i> Network Devices</h3>
                <table class="devices-table">
                    <thead>
                        <tr>
                            <th>Device Type</th>
                            <th>Model</th>
                            <th>Quantity</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="network-devices-body">
                        <?php 
                        $networks_to_show = count($network_devices) > 0 ? $network_devices : [['device_type' => '', 'model' => '', 'quantity' => 1, 'equipment_image' => '']];
                        foreach ($networks_to_show as $index => $network): ?>
                        <tr class="network-row">
                            <td>
                                <select name="network_type[]" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="Switch Hubs" <?php echo ($network['device_type'] == 'Switch Hubs') ? 'selected' : ''; ?>>Switch Hubs</option>
                                    <option value="Routers" <?php echo ($network['device_type'] == 'Routers') ? 'selected' : ''; ?>>Routers</option>
                                    <option value="Modem" <?php echo ($network['device_type'] == 'Modem') ? 'selected' : ''; ?>>Modem</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="network_model[]" class="form-control" placeholder="Model" value="<?php echo htmlspecialchars($network['model']); ?>">
                            </td>
                            <td>
                                <input type="number" name="network_quantity[]" class="form-control" value="<?php echo htmlspecialchars($network['quantity']); ?>" min="1">
                            </td>
                            <td>
                                <input type="file" name="network_image[]" class="form-control" accept="image/jpeg,image/png,image/jpg">
                                <?php if (!empty($network['equipment_image'])): ?>
                                    <div class="image-preview">
                                        <img src="<?php echo getEquipmentImageUrl($network['equipment_image']); ?>" alt="Current">
                                    </div>
                                <?php endif; ?>
                                <input type="hidden" name="existing_network_image[]" value="<?php echo htmlspecialchars($network['equipment_image']); ?>">
                            </td>
                            <td>
                                <button type="button" class="table-btn table-btn-add" onclick="addNetworkRow(this)">+</button>
                                <button type="button" class="table-btn table-btn-remove remove-btn-hidden" onclick="removeNetworkRow(this)">-</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Existing Computerized Systems -->
            <div class="form-card">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <h2>Existing Computerized Systems</h2>
                </div>
                <button type="button" class="btn-add" onclick="addSystem()">
                    <i class="fas fa-plus"></i> Add System
                </button>
                <div class="systems-list" id="systemList">
                    <?php for($i=1; $i<=5; $i++): 
                        $sys = $systems_data["system_$i"] ?? '';
                        if(!empty($sys)): ?>
                        <div class="system-item">
                            <input type="text" name="systems[]" class="form-control" value="<?php echo htmlspecialchars($sys); ?>" placeholder="System Name">
                            <button type="button" class="btn-danger-sm remove-btn-hidden" onclick="this.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endif; endfor; ?>
                </div>
            </div>

            <!-- Proposed Systems -->
            <div class="form-card">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h2>Proposed Systems</h2>
                </div>
                <button type="button" class="btn-add" onclick="addProposedSystem()">
                    <i class="fas fa-plus"></i> Add Proposed System
                </button>
                <div class="systems-list" id="proposedSystemList">
                    <?php for($i=1; $i<=5; $i++): 
                        $sys = $systems_data["proposed_system_$i"] ?? '';
                        if(!empty($sys)): ?>
                        <div class="system-item">
                            <input type="text" name="proposed_systems[]" class="form-control" value="<?php echo htmlspecialchars($sys); ?>" placeholder="Proposed System">
                            <button type="button" class="btn-danger-sm remove-btn-hidden" onclick="this.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endif; endfor; ?>
                </div>
            </div>

            <!-- Internet Connections -->
            <div class="form-card">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h2>Internet Connections</h2>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Connected to CENTRALIZED ISP SERVER</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="isp_server" value="Yes" <?php echo (isset($internet_data['is_centralized_isp']) && $internet_data['is_centralized_isp'] == 1) ? 'checked' : ''; ?>>
                                <span>Yes</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="isp_server" value="No" <?php echo (!isset($internet_data['is_centralized_isp']) || $internet_data['is_centralized_isp'] == 0) ? 'checked' : ''; ?>>
                                <span>No</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Connected to CENTRALIZED LAN / WAN CONNECTION</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="lan_connection" value="Yes" <?php echo (isset($internet_data['is_centralized_lan_wan']) && $internet_data['is_centralized_lan_wan'] == 1) ? 'checked' : ''; ?>>
                                <span>Yes</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="lan_connection" value="No" <?php echo (!isset($internet_data['is_centralized_lan_wan']) || $internet_data['is_centralized_lan_wan'] == 0) ? 'checked' : ''; ?>>
                                <span>No</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Connected to CENTRALIZED DATABASE SERVER</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="database_server" value="Yes" <?php echo (isset($internet_data['is_centralized_db_server']) && $internet_data['is_centralized_db_server'] == 1) ? 'checked' : ''; ?>>
                                <span>Yes</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="database_server" value="No" <?php echo (!isset($internet_data['is_centralized_db_server']) || $internet_data['is_centralized_db_server'] == 0) ? 'checked' : ''; ?>>
                                <span>No</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Connected to OTHER ISPs via Office Paid Plan</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="other_isp" value="Yes" <?php echo (isset($internet_data['is_other_isp_via_office_plan']) && $internet_data['is_other_isp_via_office_plan'] == 1) ? 'checked' : ''; ?>>
                                <span>Yes</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="other_isp" value="No" <?php echo (!isset($internet_data['is_other_isp_via_office_plan']) || $internet_data['is_other_isp_via_office_plan'] == 0) ? 'checked' : ''; ?>>
                                <span>No</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ISP Provider Name</label>
                        <input type="text" name="isp_name" class="form-control" value="<?php echo htmlspecialchars($internet_data['isp_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Bandwidth (Mbps)</label>
                        <div class="input-group">
                            <input type="text" name="bandwidth" class="form-control" value="<?php echo htmlspecialchars($internet_data['bandwidth'] ?? ''); ?>">
                            <span class="input-group-text">mbps</span>
                        </div>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Other Internet Connection Source</label>
                        <input type="text" name="other_source" class="form-control" value="<?php echo htmlspecialchars($internet_data['other_internet_source'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Communication -->
            <div class="form-card">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h2>Communication</h2>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Connected to PABX System</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="pabx" value="Yes" <?php echo (isset($internet_data['pabx']) && $internet_data['pabx'] == 'Yes') ? 'checked' : ''; ?>>
                                <span>Yes</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="pabx" value="No" <?php echo (!isset($internet_data['pabx']) || $internet_data['pabx'] == 'No') ? 'checked' : ''; ?>>
                                <span>No</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Office Telephone Number/s</label>
                        <input type="text" name="telephone_numbers" class="form-control" value="<?php echo htmlspecialchars($internet_data['telephone_numbers'] ?? 'N/A'); ?>" placeholder="Enter telephone number/s">
                    </div>
                </div>
            </div>

            <!-- Radio Communication Equipment -->
            <div class="form-card">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-broadcast-tower"></i>
                    </div>
                    <h2>Radio Communication Equipment</h2>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Base Radios</label>
                        <input type="number" name="base_radios" class="form-control" value="<?php echo htmlspecialchars($internet_data['base_radios'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label>Handheld Personal</label>
                        <input type="number" name="handheld_personal" class="form-control" value="<?php echo htmlspecialchars($internet_data['handheld_personal'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label>Handheld LGU</label>
                        <input type="number" name="handheld_lgu" class="form-control" value="<?php echo htmlspecialchars($internet_data['handheld_lgu'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label>Total</label>
                        <input type="number" name="handheld_total" class="form-control" value="<?php echo htmlspecialchars($internet_data['handheld_total'] ?? 0); ?>">
                    </div>
                </div>
            </div>

            <button type="submit" name="submit" class="btn-submit">
                <i class="fas fa-save"></i> Save All Changes
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if(isset($success) && $success): ?>
        Swal.fire({title:'Success!',text:'Report updated successfully!',icon:'success',confirmButtonColor:'#667eea'}).then(()=>{window.location='../report.php';});
        <?php endif; ?>
        <?php if(isset($error) && $error): ?>
        Swal.fire({title:'Error!',text:'Error updating report!',icon:'error',confirmButtonColor:'#ff6b6b'});
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
        function addPrinterRow(btn){
            let tbody = document.getElementById('printer-devices-body');
            let template = tbody.querySelector('tr.printer-row');
            let newRow = template.cloneNode(true);
            newRow.querySelectorAll('input,select').forEach(f => {
                if(f.type==='file') f.value='';
                else if(f.type==='hidden' && f.name==='existing_printer_image[]') f.value='';
                else if(f.tagName==='SELECT') f.selectedIndex=0;
                else if(f.type==='number') f.value='1';
                else f.value='';
            });
            // Remove image preview if exists
            let preview = newRow.querySelector('.image-preview');
            if(preview) preview.remove();
            
            tbody.appendChild(newRow);
            toggleRemoveButtons(tbody, '.remove-btn-hidden');
        }
        function removePrinterRow(btn){
            let tbody = document.getElementById('printer-devices-body');
            let row = btn.closest('tr');
            if(tbody.querySelectorAll('tr').length>1){
                row.remove();
                toggleRemoveButtons(tbody, '.remove-btn-hidden');
            }
        }

        // NETWORK ROWS
        function addNetworkRow(btn){
            let tbody = document.getElementById('network-devices-body');
            let template = tbody.querySelector('tr.network-row');
            let newRow = template.cloneNode(true);
            newRow.querySelectorAll('input,select').forEach(f => {
                if(f.type==='file') f.value='';
                else if(f.type==='hidden' && f.name==='existing_network_image[]') f.value='';
                else if(f.tagName==='SELECT') f.selectedIndex=0;
                else if(f.type==='number') f.value='1';
                else f.value='';
            });
            // Remove image preview if exists
            let preview = newRow.querySelector('.image-preview');
            if(preview) preview.remove();

            tbody.appendChild(newRow);
            toggleRemoveButtons(tbody, '.remove-btn-hidden');
        }
        function removeNetworkRow(btn){
            let tbody = document.getElementById('network-devices-body');
            let row = btn.closest('tr');
            if(tbody.querySelectorAll('tr').length>1){
                row.remove();
                toggleRemoveButtons(tbody, '.remove-btn-hidden');
            }
        }

        // COMPUTER EQUIPMENT ROWS
        function addEquipment(){
            let container = document.getElementById("equipmentContainer");
            let newRow = document.querySelector(".equipment-row").cloneNode(true);
            newRow.querySelectorAll("input, select").forEach(f => {
                if(f.type==='file') f.value='';
                else if(f.type==='number') f.value='1';
                else f.value='';
                if(f.tagName==='SELECT') f.selectedIndex=0;
            });
            // Update equipment number
            let count = container.querySelectorAll(".equipment-row").length + 1;
            newRow.querySelector(".row-number").textContent = "Equipment #" + count;
            container.appendChild(newRow);
            toggleRemoveButtons(container, '.remove-btn-hidden');
        }
        function removeRow(btn){
            let container = document.getElementById("equipmentContainer");
            if(container.children.length>1){
                btn.closest(".equipment-row").remove();
                // Re-number remaining rows
                container.querySelectorAll(".equipment-row").forEach((row, index) => {
                    row.querySelector(".row-number").textContent = "Equipment #" + (index + 1);
                });
                toggleRemoveButtons(container, '.remove-btn-hidden');
            }
        }

        function addSystem(){
            let container = document.getElementById("systemList");
            if(container.children.length >= 5) {
                Swal.fire({title:'Limit Reached!',text:'You can only add up to 5 systems.',icon:'warning'});
                return;
            }
            let div = document.createElement("div");
            div.className = "system-item";
            div.innerHTML = `
                <input type="text" name="systems[]" class="form-control" placeholder="System Name">
                <button type="button" class="btn-danger-sm" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
            toggleRemoveButtons(container, '.remove-btn-hidden');
        }

        function addProposedSystem(){
            let container = document.getElementById("proposedSystemList");
            if(container.children.length >= 5) {
                Swal.fire({title:'Limit Reached!',text:'You can only add up to 5 proposed systems.',icon:'warning'});
                return;
            }
            let div = document.createElement("div");
            div.className = "system-item";
            div.innerHTML = `
                <input type="text" name="proposed_systems[]" class="form-control" placeholder="Proposed System">
                <button type="button" class="btn-danger-sm" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
            toggleRemoveButtons(container, '.remove-btn-hidden');
        }

        window.addEventListener('load', () => {
            // Initialize remove buttons
            toggleRemoveButtons(document.getElementById('equipmentContainer'), '.remove-btn-hidden');
            toggleRemoveButtons(document.getElementById('printer-devices-body'), '.remove-btn-hidden');
            toggleRemoveButtons(document.getElementById('network-devices-body'), '.remove-btn-hidden');
            toggleRemoveButtons(document.getElementById('systemList'), '.remove-btn-hidden');
            toggleRemoveButtons(document.getElementById('proposedSystemList'), '.remove-btn-hidden');
        });
    </script>
        </div>
    </div>
</div>
</body>
</html>
