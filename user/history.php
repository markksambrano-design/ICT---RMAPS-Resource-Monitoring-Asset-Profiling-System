<?php
// User history page: show all submitted ISSP form data for the current office.
include __DIR__ . '/../config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$userOffice = $_SESSION['user_office'] ?? '';
if (empty($userOffice)) {
    die('Office information is missing. Please login again.');
}

$sql = "SELECT * FROM user_issp_form WHERE office_name = ? ORDER BY date_submitted DESC, id ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Database error: ' . $conn->error);
}

$stmt->bind_param('s', $userOffice);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    die('Query failed: ' . $conn->error);
}

$submissions = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['date_submitted'] . '|' . $row['office_name'];

    if (!isset($submissions[$key])) {
        $submissions[$key] = [
            'office_name' => $row['office_name'],
            'date_submitted' => $row['date_submitted'],
            'form_status' => $row['form_status'] ?? 'Open',
            'created_at' => null,
            'items' => [],
            'system' => array_filter([
                $row['system1'] ?? '',
                $row['system2'] ?? '',
                $row['system3'] ?? '',
                $row['system4'] ?? '',
                $row['system5'] ?? ''
            ]),
            'proposed_system' => array_filter([
                $row['proposed_system1'] ?? '',
                $row['proposed_system2'] ?? '',
                $row['proposed_system3'] ?? '',
                $row['proposed_system4'] ?? '',
                $row['proposed_system5'] ?? ''
            ]),
            'printer_devices' => [],
            'network_devices' => [],
            'connectivity' => [
                'isp_server' => $row['isp_server'] ?? 'No',
                'lan_connection' => $row['lan_connection'] ?? 'No',
                'database_server' => $row['database_server'] ?? 'No',
                'other_isp' => $row['other_isp'] ?? 'No',
                'isp_name' => $row['isp_name'] ?? 'N/A',
                'bandwidth' => $row['bandwidth'] ?? 'N/A',
                'pabx' => $row['pabx'] ?? 'N/A',
                'telephone_numbers' => $row['telephone_numbers'] ?? 'N/A',
                'base_radios' => $row['base_radios'] ?? 0,
                'handheld_personal' => $row['handheld_personal'] ?? 0,
                'handheld_lgu' => $row['handheld_lgu'] ?? 0,
                'handheld_total' => $row['handheld_total'] ?? 0
            ],
            'item_count' => 0,
            'equipment_total' => 0,
            'raw_items' => [] // Store raw items before consolidation
        ];
    }

    // Populate printer and network devices from user_issp_form columns
    if (empty($submissions[$key]['printer_devices'])) {
        $printer_types = [
            'inkjet_printer' => 'Inkjet Printer',
            'deskjet_printer' => 'Deskjet Printer',
            'dotmatrix_printer' => 'Dot Matrix Printer'
        ];
        foreach ($printer_types as $db_key => $display_name) {
            $units = (int)($row[$db_key] ?? 0);
            $models_raw = $row[$db_key . '_model'] ?? '';
            if ($units > 0) {
                $models = array_filter(array_map('trim', explode(',', $models_raw)));
                if (!empty($models)) {
                    $distributed = distributeUnits($units, $models);
                    foreach ($distributed as $dist) {
                        $submissions[$key]['printer_devices'][] = [
                            'type' => $display_name,
                            'model' => $dist['model'],
                            'units' => $dist['quantity'],
                            'image' => null
                        ];
                    }
                } else {
                    $submissions[$key]['printer_devices'][] = [
                        'type' => $display_name,
                        'model' => $models_raw,
                        'units' => $units,
                        'image' => null
                    ];
                }
            }
        }
    }
    
    if (empty($submissions[$key]['network_devices'])) {
        $network_types = [
            'switch_hubs' => 'Switch / Hubs',
            'routers' => 'Routers',
            'modem' => 'Modem'
        ];
        foreach ($network_types as $db_key => $display_name) {
            $units = (int)($row[$db_key] ?? 0);
            $models_raw = $row[$db_key . '_model'] ?? '';
            if ($units > 0) {
                $models = array_filter(array_map('trim', explode(',', $models_raw)));
                if (!empty($models)) {
                    $distributed = distributeUnits($units, $models);
                    foreach ($distributed as $dist) {
                        $submissions[$key]['network_devices'][] = [
                            'type' => $display_name,
                            'model' => $dist['model'],
                            'units' => $dist['quantity'],
                            'image' => null
                        ];
                    }
                } else {
                    $submissions[$key]['network_devices'][] = [
                        'type' => $display_name,
                        'model' => $models_raw,
                        'units' => $units,
                        'image' => null
                    ];
                }
            }
        }
    }

    $submissions[$key]['raw_items'][] = [
        'item' => $row['item'] ?? '',
        'units' => (int)($row['units'] ?? 0),
        'brand' => $row['brand'] ?? '',
        'processor' => $row['processor'] ?? '',
        'ram' => $row['ram'] ?? '',
        'hdd' => $row['hdd'] ?? '',
        'ssd' => $row['ssd'] ?? '',
        'equipment_image' => $row['equipment_image'] ?? ''
    ];
    
    $submissions[$key]['items'][] = [
        'item' => $row['item'] ?? '',
        'units' => (int)($row['units'] ?? 0),
        'brand' => $row['brand'] ?? '',
        'processor' => $row['processor'] ?? '',
        'ram' => $row['ram'] ?? '',
        'hdd' => $row['hdd'] ?? '',
        'ssd' => $row['ssd'] ?? '',
        'equipment_image' => $row['equipment_image'] ?? ''
    ];
    $submissions[$key]['item_count']++;
    $submissions[$key]['equipment_total'] += (int)($row['units'] ?? 0);
}

$stmt->close();

// Also fetch admin form submissions for this office
$sql_form = "SELECT * FROM form WHERE office_name = ? ORDER BY date_submitted DESC, id ASC";
$stmt_form = $conn->prepare($sql_form);
if ($stmt_form) {
    $stmt_form->bind_param('s', $userOffice);
    $stmt_form->execute();
    $result_form = $stmt_form->get_result();
    while ($row_form = $result_form->fetch_assoc()) {
        $key = $row_form['date_submitted'] . '|' . $row_form['office_name'];
        if (!isset($submissions[$key])) {
            $submissions[$key] = [
                'office_name' => $row_form['office_name'],
                'date_submitted' => $row_form['date_submitted'],
                'form_status' => 'Admin Form',
                'created_at' => $row_form['created_at'] ?? null,
                'items' => [],
                'raw_items' => [],
                'system' => [],
                'proposed_system' => [],
                'printer_devices' => [],
                'network_devices' => [],
                'connectivity' => [
                    'isp_server' => 'N/A',
                    'lan_connection' => 'N/A',
                    'database_server' => 'N/A',
                    'other_isp' => 'N/A',
                    'isp_name' => 'N/A',
                    'bandwidth' => 'N/A',
                    'pabx' => 'N/A',
                    'telephone_numbers' => 'N/A',
                    'base_radios' => 0,
                    'handheld_personal' => 0,
                    'handheld_lgu' => 0,
                    'handheld_total' => 0
                ],
                'item_count' => 0,
                'equipment_total' => 0
            ];
        }
        // Fetch items from computer_equipment
        $sql_items = "SELECT * FROM computer_equipment WHERE form_id = ?";
        $stmt_items = $conn->prepare($sql_items);
        if ($stmt_items) {
            $stmt_items->bind_param('i', $row_form['id']);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            while ($item_row = $result_items->fetch_assoc()) {
                $itemData = [
                    'item' => $item_row['item'] ?? '',
                    'units' => (int)($item_row['number_of_units'] ?? 0),
                    'brand' => $item_row['brand'] ?? '',
                    'processor' => $item_row['processor'] ?? '',
                    'ram' => $item_row['ram'] ?? '',
                    'hdd' => $item_row['hdd'] ?? '',
                    'ssd' => $item_row['ssd'] ?? '',
                    'equipment_image' => $item_row['equipment_image'] ?? ''
                ];
                $submissions[$key]['raw_items'][] = $itemData;
                $submissions[$key]['items'][] = $itemData;
                $submissions[$key]['item_count']++;
                $submissions[$key]['equipment_total'] += (int)($item_row['number_of_units'] ?? 0);
            }
            $stmt_items->close();
        }
        
        // Fetch printer devices from new table
        $sql_printers = "SELECT * FROM printer_devices WHERE form_id = ?";
        $stmt_printers = $conn->prepare($sql_printers);
        if ($stmt_printers) {
            $stmt_printers->bind_param('i', $row_form['id']);
            $stmt_printers->execute();
            $result_printers = $stmt_printers->get_result();
            $submissions[$key]['printer_devices'] = [];
            while ($printer_row = $result_printers->fetch_assoc()) {
                $submissions[$key]['printer_devices'][] = [
                    'type' => $printer_row['printer_type'],
                    'model' => $printer_row['model'],
                    'units' => (int)$printer_row['quantity'],
                    'image' => $printer_row['equipment_image']
                ];
            }
            $stmt_printers->close();
        }

        // Fetch network devices from new table
        $sql_networks = "SELECT * FROM network_devices WHERE form_id = ?";
        $stmt_networks = $conn->prepare($sql_networks);
        if ($stmt_networks) {
            $stmt_networks->bind_param('i', $row_form['id']);
            $stmt_networks->execute();
            $result_networks = $stmt_networks->get_result();
            $submissions[$key]['network_devices'] = [];
            while ($network_row = $result_networks->fetch_assoc()) {
                $submissions[$key]['network_devices'][] = [
                    'type' => $network_row['device_type'],
                    'model' => $network_row['model'],
                    'units' => (int)$network_row['quantity'],
                    'image' => $network_row['equipment_image']
                ];
            }
            $stmt_networks->close();
        }
        
        // Fetch other equipment from other_ict_equipment as fallback
        $sql_other = "SELECT * FROM other_ict_equipment WHERE form_id = ?";
        $stmt_other = $conn->prepare($sql_other);
        if ($stmt_other) {
            $stmt_other->bind_param('i', $row_form['id']);
            $stmt_other->execute();
            $result_other = $stmt_other->get_result();
            if ($other_row = $result_other->fetch_assoc()) {
                // If new tables are empty, populate from old table
                if (empty($submissions[$key]['printer_devices'])) {
                    $printer_types = [
                        'inkjet_printer' => 'Inkjet Printer',
                        'deskjet_printer' => 'Deskjet Printer',
                        'dotmatrix_printer' => 'Dot Matrix Printer'
                    ];
                    foreach ($printer_types as $db_key => $display_name) {
                        $units = (int)($other_row[$db_key] ?? 0);
                        $models_raw = $other_row[$db_key . '_model'] ?? '';
                        if ($units > 0) {
                            $models = array_filter(array_map('trim', explode(',', $models_raw)));
                            if (!empty($models)) {
                                $distributed = distributeUnits($units, $models);
                                foreach ($distributed as $dist) {
                                    $submissions[$key]['printer_devices'][] = [
                                        'type' => $display_name,
                                        'model' => $dist['model'],
                                        'units' => $dist['quantity'],
                                        'image' => null
                                    ];
                                }
                            } else {
                                $submissions[$key]['printer_devices'][] = [
                                    'type' => $display_name,
                                    'model' => $models_raw,
                                    'units' => $units,
                                    'image' => null
                                ];
                            }
                        }
                    }
                }
                
                if (empty($submissions[$key]['network_devices'])) {
                    $network_types = [
                        'switch_hubs' => 'Switch / Hubs',
                        'routers' => 'Routers',
                        'modem' => 'Modem'
                    ];
                    foreach ($network_types as $db_key => $display_name) {
                        $units = (int)($other_row[$db_key] ?? 0);
                        $models_raw = $other_row[$db_key . '_model'] ?? '';
                        if ($units > 0) {
                            $models = array_filter(array_map('trim', explode(',', $models_raw)));
                            if (!empty($models)) {
                                $distributed = distributeUnits($units, $models);
                                foreach ($distributed as $dist) {
                                    $submissions[$key]['network_devices'][] = [
                                        'type' => $display_name,
                                        'model' => $dist['model'],
                                        'units' => $dist['quantity'],
                                        'image' => null
                                    ];
                                }
                            } else {
                                $submissions[$key]['network_devices'][] = [
                                    'type' => $display_name,
                                    'model' => $models_raw,
                                    'units' => $units,
                                    'image' => null
                                ];
                            }
                        }
                    }
                }
            }
            $stmt_other->close();
        }

        // Fetch connectivity from internet_connections if available
        $sql_internet = "SELECT * FROM internet_connections WHERE form_id = ?";
        $stmt_internet = $conn->prepare($sql_internet);
        if ($stmt_internet) {
            $stmt_internet->bind_param('i', $row_form['id']);
            $stmt_internet->execute();
            $result_internet = $stmt_internet->get_result();
            if ($internet_row = $result_internet->fetch_assoc()) {
                $submissions[$key]['connectivity'] = [
                    'isp_server' => ($internet_row['is_centralized_isp'] == 1) ? 'Yes' : 'No',
                    'lan_connection' => ($internet_row['is_centralized_lan_wan'] == 1) ? 'Yes' : 'No',
                    'database_server' => ($internet_row['is_centralized_db_server'] == 1) ? 'Yes' : 'No',
                    'other_isp' => ($internet_row['is_other_isp_via_office_plan'] == 1) ? 'Yes' : 'No',
                    'isp_name' => $internet_row['isp_name'] ?? 'N/A',
                    'bandwidth' => $internet_row['bandwidth'] ?? 'N/A',
                    'pabx' => $internet_row['pabx'] ?? 'N/A',
                    'telephone_numbers' => $internet_row['telephone_numbers'] ?? 'N/A',
                    'base_radios' => $internet_row['base_radios'] ?? 0,
                    'handheld_personal' => $internet_row['handheld_personal'] ?? 0,
                    'handheld_lgu' => $internet_row['handheld_lgu'] ?? 0,
                    'handheld_total' => $internet_row['handheld_total'] ?? 0
                ];
            }
            $stmt_internet->close();
        }

        // Fetch systems from systems table if available
        $sql_systems = "SELECT * FROM systems WHERE form_id = ?";
        $stmt_systems = $conn->prepare($sql_systems);
        if ($stmt_systems) {
            $stmt_systems->bind_param('i', $row_form['id']);
            $stmt_systems->execute();
            $result_systems = $stmt_systems->get_result();
            if ($systems_row = $result_systems->fetch_assoc()) {
                // Fetch systems 1-5 and proposed systems 1-5
                $submissions[$key]['system'] = array_filter([
                    $systems_row['system_1'] ?? '',
                    $systems_row['system_2'] ?? '',
                    $systems_row['system_3'] ?? '',
                    $systems_row['system_4'] ?? '',
                    $systems_row['system_5'] ?? ''
                ]);
                $submissions[$key]['proposed_system'] = array_filter([
                    $systems_row['proposed_system_1'] ?? '',
                    $systems_row['proposed_system_2'] ?? '',
                    $systems_row['proposed_system_3'] ?? '',
                    $systems_row['proposed_system_4'] ?? '',
                    $systems_row['proposed_system_5'] ?? ''
                ]);
            }
            $stmt_systems->close();
        }
    }
    $stmt_form->close();
}

// ============================================
// IMPROVED CONSOLIDATION FUNCTION - Groups by item name AND specs
// ============================================
function consolidateEquipmentItems($items) {
    $consolidated = [];
    
    foreach ($items as $item) {
        // Create a unique key based on ALL specifications
        // This ensures different specs are shown as separate rows
        $specKey = md5(
            strtolower(trim($item['item'])) . '|' . 
            strtolower(trim($item['brand'])) . '|' . 
            strtolower(trim($item['processor'])) . '|' . 
            strtolower(trim($item['ram'])) . '|' . 
            strtolower(trim($item['hdd'])) . '|' . 
            strtolower(trim($item['ssd']))
        );
        
        if (!isset($consolidated[$specKey])) {
            $consolidated[$specKey] = [
                'item' => $item['item'],
                'brand' => $item['brand'],
                'processor' => $item['processor'],
                'ram' => $item['ram'],
                'hdd' => $item['hdd'],
                'ssd' => $item['ssd'],
                'equipment_image' => $item['equipment_image'] ?? '',
                'units' => (int)$item['units']
            ];
        } else {
            // Sum units for identical items
            $consolidated[$specKey]['units'] += (int)$item['units'];
        }
    }
    
    // Re-index and sort by item name
    $consolidated = array_values($consolidated);
    usort($consolidated, function($a, $b) {
        return strcmp($a['item'], $b['item']);
    });
    
    return $consolidated;
}

// Apply consolidation to all submissions for detailed view
foreach ($submissions as $key => $submission) {
    // Keep raw items for summary calculations
    if (!isset($submission['raw_items']) || empty($submission['raw_items'])) {
        $submission['raw_items'] = $submission['items'];
    }
    
    // Consolidate for detailed display
    $submissions[$key]['items'] = consolidateEquipmentItems($submission['items']);
    $submissions[$key]['item_count'] = count($submissions[$key]['items']);
    
    // Keep equipment_total as the RAW sum (not consolidated)
    // This ensures the card shows 4 units total
}

// Calculate summary by item name (groups same items regardless of specs)
foreach ($submissions as $key => $submission) {
    $summaryByItem = [];
    $totalRawUnits = 0;
    
    foreach ($submission['raw_items'] as $item) {
        $itemName = $item['item'];
        if (!isset($summaryByItem[$itemName])) {
            $summaryByItem[$itemName] = 0;
        }
        $summaryByItem[$itemName] += $item['units'];
        $totalRawUnits += $item['units'];
    }
    
    $submissions[$key]['summary_by_item'] = $summaryByItem;
    $submissions[$key]['raw_total_units'] = $totalRawUnits;
}

$submissions = array_values($submissions);

function formatDate($date)
{
    return $date ? date('F d, Y', strtotime($date)) : 'N/A';
}

function renderBadgeClass($status)
{
    $status = strtolower(trim($status));
    if ($status === 'approved') {
        return 'success';
    }
    if ($status === 'closed') {
        return 'secondary';
    }
    if ($status === 'rejected') {
        return 'danger';
    }
    return 'warning';
}

function displayValue($value)
{
    return trim((string)$value) !== '' ? htmlspecialchars($value) : 'N/A';
}

// Helper function to distribute units across models
function distributeUnits($totalUnits, $modelsArray) {
    if (empty($modelsArray) || $totalUnits <= 0) {
        return [];
    }
    $modelCount = count($modelsArray);
    $perModelQty = floor($totalUnits / $modelCount);
    $remainder = $totalUnits % $modelCount;
    
    $distribution = [];
    foreach ($modelsArray as $index => $model) {
        $qty = $perModelQty + ($index < $remainder ? 1 : 0);
        if ($qty > 0) {
            $distribution[] = ['model' => $model, 'quantity' => $qty];
        }
    }
    return $distribution;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php $pageTitle = 'Submission History - ICTMIS'; ?>
    <?php $assetPath = '../assest/css/user'; ?>
    <?php include __DIR__ . '/components/head.php'; ?>
    <style>
/* ============================================ */
/* DASHBOARD LAYOUT - SIDEBAR + MAIN CONTENT   */
/* ============================================ */

.dashboard-container {
    display: flex;
    min-height: 100vh;
    width: 100%;
    background: #f8fafc;
}

.main-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    margin-left: 260px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-collapsed .main-content {
    margin-left: 90px;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: 90px;
    }
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }
}

/* ============================================ */
/* MODERN REPORT CARD STYLES                    */
/* ============================================ */

.history-panel {
    padding: 30px 40px;
    margin-top: 20px;
    width: 100%;
    max-width: 100%;
    margin-left: 0;
    margin-right: 0;
}

.page-header-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 40px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 24px;
}

.page-header-title {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 15px;
    margin-bottom: 8px;
    text-align: left;
}

.page-header-title i {
    font-size: 40px;
    color: #4f46e5;
    background: #eef2ff;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
}

.page-header-title h1 {
    font-size: 32px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.page-subtitle {
    color: #64748b;
    font-size: 16px;
    margin: 0;
    text-align: left;
}

.search-filter-container {
    display: flex;
    gap: 12px;
}

.search-input-group {
    position: relative;
    width: 300px;
}

.search-input-group i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.search-control {
    width: 100%;
    padding: 12px 16px 12px 45px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.2s;
}

.search-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

.submissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 32px;
}

.history-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    flex-direction: column;
}

.history-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px -12px rgba(79, 70, 229, 0.12);
    border-color: #cbd5e1;
}

.history-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 6px;
    width: 100%;
    background: linear-gradient(90deg, #4f46e5, #818cf8);
}

.card-header-main {
    padding: 20px 24px 14px 24px;
}

.office-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.office-info i {
    font-size: 24px;
    color: #1e293b;
}

.office-name-display {
    font-size: 20px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.date-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #94a3b8;
    font-size: 14px;
    margin-bottom: 16px;
}

.status-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.warning {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

.status-badge.success {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.status-badge.danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.status-badge.secondary {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.stats-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #f8fafc;
    margin: 0 24px;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
}

.stat-item {
    padding: 16px;
    text-align: center;
}

.stat-item:first-child {
    border-right: 1px solid #f1f5f9;
}

.stat-value {
    display: block;
    font-size: 22px;
    font-weight: 800;
    color: #4f46e5;
    margin-bottom: 2px;
}

.stat-label {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 500;
}

.equipment-summary-section {
    padding: 20px 24px;
    flex-grow: 1;
}

.summary-title {
    font-size: 12px;
    font-weight: 800;
    color: #6366f1;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 16px;
    display: block;
}

.equipment-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.equipment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px dashed #e2e8f0;
}

.equipment-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.eq-name {
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
}

.eq-units {
    color: #4f46e5;
    font-weight: 700;
    font-size: 14px;
}

.card-footer-btn {
    padding: 14px 24px 20px 24px;
}

.view-history-btn {
    width: 100%;
    padding: 14px;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.view-history-btn:hover {
    background: #4338ca;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25);
}

.view-history-btn i {
    font-size: 18px;
}

/* Original detail section (hidden initially) */
.detail-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(8px);
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.detail-modal {
    background: white;
    width: 100%;
    max-width: 1000px;
    max-height: 95vh;
    border-radius: 12px;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.issp-form-container {
    padding: 0;
    background: white;
}

:root {
    --primary-system: #1e40af;
    --secondary-system: #f1f5f9;
    --accent-blue: #3b82f6;
    --border-color: #e2e8f0;
    --text-dark: #0f172a;
    --text-muted: #64748b;
    --section-bg: #f8fafc;
}

.sheet { 
    background: #fff; 
    margin: 0; 
    width: 100%; 
    padding: 40px; 
    position: relative; 
    border-radius: 4px; 
}

.top-head { 
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
    border-bottom: 3px double var(--primary-system); 
    padding-bottom: 25px; 
    margin-bottom: 40px; 
    gap: 30px; 
}

.top-head img { 
    width: 85px; 
    height: 85px; 
    object-fit: contain; 
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); 
}

.title-container { 
    text-align: center; 
    flex-grow: 1; 
}

.title-container .sub-text { 
    font-size: 13px; 
    font-weight: 700; 
    color: var(--text-muted); 
    text-transform: uppercase; 
    letter-spacing: 2px; 
    margin-bottom: 6px; 
}

.title-container h1 { 
    margin: 0; 
    font-size: 20px; 
    font-weight: 900; 
    color: var(--primary-system); 
    line-height: 1.2; 
    letter-spacing: -0.5px; 
}

.title-container .year-range { 
    font-size: 16px; 
    font-weight: 700; 
    color: #334155; 
    margin-top: 8px; 
    display: inline-block; 
    padding: 2px 15px; 
    background: var(--secondary-system); 
    border-radius: 20px; 
}

.section-bar { 
    background: var(--section-bg); 
    border-bottom: 2px solid var(--primary-system); 
    padding: 12px 15px; 
    font-weight: 800; 
    font-size: 14px; 
    color: var(--primary-system); 
    margin-top: 35px; 
    margin-bottom: 20px; 
    text-transform: uppercase; 
    letter-spacing: 1px; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
}

.section-bar::before { 
    content: ""; 
    display: inline-block; 
    width: 4px; 
    height: 18px; 
    background: var(--primary-system); 
    border-radius: 2px; 
}

.info-grid { 
    display: grid; 
    grid-template-columns: 1.5fr 1fr; 
    gap: 40px; 
    padding: 10px 0; 
}

.info-item { 
    display: flex; 
    flex-direction: column; 
    border-bottom: 1px dashed var(--border-color); 
    padding-bottom: 8px; 
}

.info-label { 
    font-size: 10px; 
    font-weight: 800; 
    color: var(--text-muted); 
    text-transform: uppercase; 
    margin-bottom: 4px; 
    letter-spacing: 0.5px; 
}

.info-value { 
    font-size: 16px; 
    font-weight: 700; 
    color: var(--text-dark); 
}

.equip-table { 
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 0; 
    margin-top: 10px; 
    border: 1px solid var(--border-color); 
    border-radius: 6px; 
    overflow: hidden; 
}

.equip-table th { 
    background: var(--primary-system); 
    color: #fff; 
    font-weight: 700; 
    padding: 14px 12px; 
    text-align: center; 
    font-size: 11px; 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
    border-right: 1px solid rgba(255,255,255,0.1); 
}

.equip-table th:last-child { 
    border-right: none; 
}

.equip-table td { 
    padding: 12px; 
    border-bottom: 1px solid var(--border-color); 
    border-right: 1px solid var(--border-color); 
    vertical-align: middle; 
    font-size: 13px; 
    color: #334155; 
}

.equip-table td:last-child { 
    border-right: none; 
}

.equip-table tr:last-child td { 
    border-bottom: none; 
}

.equip-table tr:nth-child(even) { 
    background-color: #f8fafc; 
}

.equip-table .total-row { 
    background: #f1f5f9; 
    font-weight: 800; 
}

.systems-container { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 40px; 
    margin-top: 10px; 
}

.system-list { 
    margin: 0; 
    padding: 0; 
    list-style: none; 
}

.system-list li { 
    padding: 10px 15px; 
    border-bottom: 1px solid var(--border-color); 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    font-weight: 600; 
    color: #475569; 
}

.system-list li:last-child { 
    border-bottom: none; 
}

.system-list li svg { 
    color: var(--accent-blue); 
    flex-shrink: 0; 
}

.system-list li.empty-list { 
    color: var(--text-muted); 
    font-style: italic; 
    font-weight: normal; 
}

.conn-grid { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 15px; 
    margin-top: 10px; 
}

.conn-item { 
    background: #fff; 
    border: 1px solid var(--border-color); 
    padding: 15px; 
    border-radius: 8px; 
    transition: all 0.2s; 
    display: flex; 
    flex-direction: column; 
    gap: 5px; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.02); 
}

.conn-item:hover { 
    border-color: var(--accent-blue); 
    transform: translateY(-2px); 
    box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
}

.conn-item .info-label { 
    border-bottom: 1px solid var(--secondary-system); 
    padding-bottom: 5px; 
    margin-bottom: 5px; 
}

.conn-item .info-value { 
    font-size: 14px; 
    color: var(--primary-system); 
    font-weight: 700;
}

.footer-note { 
    margin-top: 60px; 
    border-top: 1px solid var(--border-color); 
    padding-top: 25px; 
    font-size: 10px; 
    color: var(--text-muted); 
    text-align: center; 
    font-style: italic; 
    letter-spacing: 0.5px; 
}

.issp-footer-actions {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 20px 40px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin: 40px -40px -40px -40px;
    border-radius: 0 0 12px 12px;
    z-index: 10;
}

.issp-btn {
    padding: 10px 24px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.issp-btn-print { background: var(--primary-system); color: white; }
.issp-btn-print:hover { background: #1e3a8a; }
.issp-btn-pdf { background: #dc2626; color: white; }
.issp-btn-pdf:hover { background: #b91c1c; }


@media (max-width: 768px) {
    .info-grid, .systems-container, .conn-grid { grid-template-columns: 1fr; }
    .sheet { padding: 20px; }
}

@media print {
    .issp-footer-actions, .close-modal { display: none !important; }
    .detail-modal { box-shadow: none; max-height: none; overflow: visible; }
    .sheet { padding: 0; margin: 0; }
}

.close-modal {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s;
}

.close-modal:hover {
    background: #e2e8f0;
    transform: rotate(90deg);
}

@media (max-width: 768px) {
    .history-panel {
        padding: 20px;
    }
    
    .submissions-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header-title h1 {
        font-size: 24px;
    }
}
</style>
</head>
<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/components/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/components/header.php'; ?>

            <div class="history-panel">
                <div class="page-header-container">
                    <div>
                        <div class="page-header-title">
                            <i class="bi bi-file-earmark-text-fill"></i>
                            <h1>Recent ICT Equipment Inventory Submission</h1>
                        </div>
                        <p class="page-subtitle">Review and monitor your recent ICT equipment inventory submissions</p>
                    </div>
                    <div class="search-filter-container">
                        <div class="search-input-group">
                            <i class="bi bi-search"></i>
                            <input type="text" id="historySearch" class="search-control" placeholder="Search submissions...">
                        </div>
                    </div>
                </div>

                <?php if (empty($submissions)): ?>
                    <div class="card history-card no-submissions" style="text-align: center; padding: 80px 40px; border-style: dashed; background: transparent; box-shadow: none;">
                        <div style="background: #f1f5f9; width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                            <i class="bi bi-folder2-open" style="font-size: 48px; color: #94a3b8;"></i>
                        </div>
                        <h3 style="color: #1e293b; font-weight: 800; margin-bottom: 12px;">No History Yet</h3>
                        <p style="color: #64748b; margin-bottom: 0;">Submit your first RMAPS form to see it here.</p>
                    </div>
                <?php else: ?>
                    <div class="submissions-grid" id="submissionsGrid">
                        <?php foreach ($submissions as $idx => $submission): ?>
                            <div class="history-card" data-card-index="<?php echo $idx; ?>">
                                <div class="card-header-main">
                                    <div class="office-info">
                                        <i class="bi bi-building"></i>
                                        <h4 class="office-name-display"><?php echo htmlspecialchars($submission['office_name']); ?></h4>
                                    </div>
                                    <div class="date-info">
                                        <i class="bi bi-calendar3"></i>
                                        <span><?php echo formatDate($submission['date_submitted']); ?></span>
                                    </div>
                                    <span class="status-badge <?php echo renderBadgeClass($submission['form_status'] ?? 'Open'); ?>">
                                        <?php echo htmlspecialchars($submission['form_status'] ?? 'OPEN'); ?>
                                    </span>
                                </div>

                                <div class="stats-container">
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo $submission['item_count']; ?></span>
                                        <span class="stat-label">Equipment Types</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo $submission['raw_total_units'] ?? $submission['equipment_total']; ?></span>
                                        <span class="stat-label">Total Units</span>
                                    </div>
                                </div>

                                <div class="equipment-summary-section">
                                    <span class="summary-title">Equipment Summary</span>
                                    <div class="equipment-list">
                                        <?php 
                                        // Display summary by item name (grouped, showing correct totals)
                                        $summaryDisplay = $submission['summary_by_item'] ?? [];
                                        arsort($summaryDisplay); // Sort by highest units first
                                        
                                        $displayLimit = 3;
                                        $counter = 0;
                                        foreach ($summaryDisplay as $itemName => $totalUnits):
                                            if ($counter >= $displayLimit) break;
                                        ?>
                                            <div class="equipment-item">
                                                <span class="eq-name"><?php echo htmlspecialchars($itemName); ?></span>
                                                <span class="eq-units"><?php echo $totalUnits; ?> units</span>
                                            </div>
                                        <?php 
                                            $counter++;
                                        endforeach; 
                                        ?>
                                        
                                        <?php if (count($summaryDisplay) > $displayLimit): ?>
                                            <div class="text-center mt-2">
                                                <small class="text-muted">+ <?php echo count($summaryDisplay) - $displayLimit; ?> more equipment types</small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (empty($summaryDisplay)): ?>
                                            <div class="text-muted text-center py-2">
                                                <small>No equipment records</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card-footer-btn">
                                    <button class="view-history-btn" onclick="showSubmissionDetails(<?php echo $idx; ?>)">
                                        <i class="bi bi-eye"></i>
                                        View Complete Details
                                    </button>
                                </div>
                                
                                <!-- HIDDEN DATA FOR MODAL -->
                                <div id="submission-details-<?php echo $idx; ?>" style="display: none;">
                                    <style>
                                        :root {
                                            --primary-system: #1e40af;
                                            --secondary-system: #f1f5f9;
                                            --accent-blue: #3b82f6;
                                            --border-color: #e2e8f0;
                                            --text-dark: #0f172a;
                                            --text-muted: #64748b;
                                            --section-bg: #f8fafc;
                                        }
                                        .sheet { background: #fff; margin: 0; width: 100%; padding: 40px; position: relative; border-radius: 4px; }
                                        .top-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px double var(--primary-system); padding-bottom: 20px; margin-bottom: 30px; gap: 30px; }
                                        .top-head img { width: 85px; height: 85px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); }
                                        .title-container { text-align: center; flex-grow: 1; }
                                        .title-container .sub-text { font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px; }
                                        .title-container h1 { margin: 0; font-size: 22px; font-weight: 900; color: var(--primary-system); line-height: 1.1; letter-spacing: -0.5px; }
                                        .title-container .year-range { font-size: 18px; font-weight: 700; color: #334155; margin-top: 8px; display: inline-block; padding: 2px 15px; background: var(--secondary-system); border-radius: 20px; }
                                        .section-bar { background: var(--section-bg); border-bottom: 2px solid var(--primary-system); padding: 12px 15px; font-weight: 800; font-size: 14px; color: var(--primary-system); margin-top: 25px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 10px; }
                                        .section-bar::before { content: ""; display: inline-block; width: 4px; height: 18px; background: var(--primary-system); border-radius: 2px; }
                                        .info-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; padding: 5px 0; }
                                        .info-item { display: flex; flex-direction: column; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px; }
                                        .info-label { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }
                                        .info-value { font-size: 16px; font-weight: 700; color: var(--text-dark); }
                                        .equip-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; }
                                        .equip-table th { background: var(--primary-system); color: #fff; font-weight: 700; padding: 10px 8px; text-align: center; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-right: 1px solid rgba(255,255,255,0.1); }
                                        .equip-table th:last-child { border-right: none; }
                                        .equip-table td { padding: 10px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: middle; font-size: 13px; color: #334155; }
                                        .equip-table td:last-child { border-right: none; }
                                        .equip-table tr:last-child td { border-bottom: none; }
                                        .equip-table tr:nth-child(even) { background-color: #f8fafc; }
                                        .systems-container { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 5px; }
                                        .system-list { margin: 0; padding: 0; list-style: none; }
                                        .system-list li { padding: 8px 15px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; font-weight: 600; color: #475569; }
                                        .system-list li:last-child { border-bottom: none; }
                                        .system-list li svg { color: var(--accent-blue); flex-shrink: 0; }
                                        .system-list li.empty-list { color: var(--text-muted); font-style: italic; font-weight: normal; }
                                        .conn-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px; }
                                        .conn-item { background: #fff; border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; transition: all 0.2s; display: flex; flex-direction: column; gap: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
                                        .conn-item:hover { border-color: var(--accent-blue); transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
                                        .conn-item .info-label { border-bottom: 1px solid var(--secondary-system); padding-bottom: 5px; margin-bottom: 5px; }
                                        .conn-item .info-value { font-size: 14px; color: var(--primary-system); }
                                        .footer-note { margin-top: 40px; border-top: 1px solid var(--border-color); padding-top: 20px; font-size: 10px; color: var(--text-muted); text-align: center; font-style: italic; letter-spacing: 0.5px; }
                                        .fw-bold { font-weight: 700; }
                                        .text-center { text-align: center; }
                                        .text-end { text-align: right; }
                                        .text-muted { color: var(--text-muted); }
                                        @media print {
                                            body { background: #fff; padding: 0; }
                                            .sheet { margin: 0; width: 100%; max-width: none; box-shadow: none; border: none; padding: 0; }
                                            .conn-item:hover { transform: none; box-shadow: none; }
                                            .section-bar { margin-top: 20px; margin-bottom: 10px; break-inside: avoid; }
                                            .section-bar[style*="page-break-before: always"] { break-before: page; margin-top: 0 !important; }
                                            .top-head { margin-bottom: 20px; padding-bottom: 15px; }
                                            .info-grid { padding: 0; }
                                            .footer-note { margin-top: 30px; }
                                            .equip-table, .systems-container, .conn-grid { break-inside: avoid; }
                                        }
                                    </style>
                                    <div class="sheet">
                                        <div class="top-head">
                                            <img src="../assest/images/logo1.png" alt="LGU Logo" onerror="this.style.display='none'">
                                            <div class="title-container">
                                                <div class="sub-text">Republic of the Philippines</div>
                                                <h1>DATA FOR THE FORMULATION OF INFORMATION SYSTEMS STRATEGIC PLANNING 2026–2030</h1>
                                                <div class="year-range">LGU MANGALDAN RMAPS</div>
                                            </div>
                                            <img src="../assest/images/logo3.png" alt="ICT Logo" onerror="this.style.display='none'">
                                        </div>

                                        <div class="section-bar">Office Information</div>
                                        <div class="info-grid">
                                            <div class="info-item">
                                                <span class="info-label">Department / Office</span>
                                                <span class="info-value"><?php echo htmlspecialchars($submission['office_name']); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Date of Submission</span>
                                                <span class="info-value"><?php echo formatDate($submission['date_submitted']); ?></span>
                                            </div>
                                        </div>

                                        <div class="section-bar">Computer Equipment Inventory</div>
                                        <table class="equip-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 15%;">Image</th>
                                                    <th style="width: 20%;">Item Description</th>
                                                    <th style="width: 8%;">Units</th>
                                                    <th>Brand / Model</th>
                                                    <th>Processor</th>
                                                    <th>RAM</th>
                                                    <th>Storage (HDD/SSD)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                // Use consolidated items for detailed view (shows each unique spec)
                                                $consolidatedItems = $submission['items'];
                                                
                                                if (!empty($consolidatedItems)):
                                                    foreach ($consolidatedItems as $item): 
                                                ?>
                                                    <tr>
                                                        <td class="text-center">
                                                            <?php if (!empty($item['equipment_image'])): ?>
                                                                <img src="../uploads/equipment_images/<?php echo htmlspecialchars($item['equipment_image']); ?>" alt="Equipment Image" style="max-width: 100px; max-height: 70px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                                            <?php else: ?>
                                                                <span style="color: #94a3b8; font-size: 12px;">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="fw-bold"><?php echo displayValue($item['item']); ?></td>
                                                        <td class="text-center fw-bold" style="color: var(--primary-system);"><?php echo $item['units']; ?></td>
                                                        <td><?php echo displayValue($item['brand']); ?></td>
                                                        <td><?php echo displayValue($item['processor']); ?></td>
                                                        <td class="text-center"><?php echo displayValue($item['ram']); ?></td>
                                                        <td>
                                                            <?php 
                                                                $storage = [];
                                                                if(!empty($item['hdd'])) $storage[] = displayValue($item['hdd']);
                                                                if(!empty($item['ssd'])) $storage[] = displayValue($item['ssd']);
                                                                echo !empty($storage) ? implode(" / ", $storage) : "-";
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php 
                                                    endforeach;
                                                else: 
                                                ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">No computer equipment records found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="total-row">
                                                    <td colspan="2" class="text-end fw-bold">TOTAL UNITS</td>
                                                    <td class="text-center" style="color: var(--primary-system); font-size: 16px; font-weight: 800;"><?php echo $submission['raw_total_units'] ?? $submission['equipment_total']; ?></td>
                                                    <td colspan="4"></td>
                                                </tr>
                                            </tfoot>
                                        </table>

                                        <div class="section-bar">Other ICT Equipment</div>
                                        <table class="equip-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 15%;">Image</th>
                                                    <th style="width: 25%;">Equipment Type</th>
                                                    <th style="width: 15%;" class="text-center">Number of Units</th>
                                                    <th style="width: 45%;">Model(s)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $other_data = array_merge($submission['printer_devices'] ?? [], $submission['network_devices'] ?? []);
                                                if (!empty($other_data)):
                                                    foreach ($other_data as $row):
                                                ?>
                                                    <tr>
                                                        <td class="text-center">
                                                            <?php if (!empty($row['image'])): ?>
                                                                <?php 
                                                                    $imagePath = $row['image'];
                                                                    if (strpos($imagePath, 'uploads/') === false) {
                                                                        $imagePath = '../uploads/equipment_images/' . $imagePath;
                                                                    } else {
                                                                        $imagePath = '../' . $imagePath;
                                                                    }
                                                                ?>
                                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Equipment Image" style="max-width: 100px; max-height: 70px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                                            <?php else: ?>
                                                                <span style="color: #94a3b8; font-size: 12px;">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="vertical-align: middle;">
                                                            <strong><?php echo htmlspecialchars($row['type']); ?></strong>
                                                        </td>
                                                        <td class="text-center fw-bold" style="color: var(--primary-system); vertical-align: middle;">
                                                            <?php echo $row['units']; ?>
                                                        </td>
                                                        <td style="vertical-align: middle;">
                                                            <?php echo displayValue($row['model']); ?>
                                                        </td>
                                                    </tr>
                                                <?php 
                                                    endforeach;
                                                else:
                                                ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No other ICT equipment recorded.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>

                                        <div class="systems-container" style="margin-bottom: 10px;">
                                            <div>
                                                <div class="section-bar">Existing Systems</div>
                                                <ul class="system-list">
                                                    <?php
                                                    $existing = $submission['system'];
                                                    if (empty($existing)) {
                                                        echo '<li class="empty-list">No existing systems recorded.</li>';
                                                    } else {
                                                        foreach ($existing as $sys) echo '<li>
                                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
                                                            ' . htmlspecialchars($sys) . '
                                                        </li>';
                                                    }
                                                    ?>
                                                </ul>
                                            </div>
                                            <div>
                                                <div class="section-bar">Proposed Systems</div>
                                                <ul class="system-list">
                                                    <?php
                                                    $proposed = $submission['proposed_system'];
                                                    if (empty($proposed)) {
                                                        echo '<li class="empty-list">No proposed systems recorded.</li>';
                                                    } else {
                                                        foreach ($proposed as $sys) echo '<li>
                                                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                                                            ' . htmlspecialchars($sys) . '
                                                        </li>';
                                                    }
                                                    ?>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="section-bar" style="page-break-before: always; margin-top: 0;">Network & Communication</div>
                                        <div class="conn-grid">
                                            <div class="conn-item">
                                                <span class="info-label">ISP Server</span>
                                                <div class="info-value"><?php echo displayValue($submission['connectivity']['isp_server']); ?></div>
                                            </div>
                                            <div class="conn-item">
                                                <span class="info-label">LAN Connection</span>
                                                <div class="info-value"><?php echo displayValue($submission['connectivity']['lan_connection']); ?></div>
                                            </div>
                                            <div class="conn-item">
                                                <span class="info-label">Database Server</span>
                                                <div class="info-value"><?php echo displayValue($submission['connectivity']['database_server']); ?></div>
                                            </div>
                                            <div class="conn-item">
                                                <span class="info-label">Other ISP</span>
                                                <div class="info-value"><?php echo displayValue($submission['connectivity']['other_isp']); ?></div>
                                            </div>
                                            <div class="conn-item">
                                                <span class="info-label">ISP Provider Name</span>
                                                <div class="info-value"><?php echo displayValue($submission['connectivity']['isp_name']); ?></div>
                                            </div>
                                            <div class="conn-item">
                                                <span class="info-label">Bandwidth (Mbps)</span>
                                                <div class="info-value"><?php echo displayValue($submission['connectivity']['bandwidth']); ?></div>
                                            </div>
                                        </div>

                                        <div class="systems-container">
                                            <div>
                                                <div class="section-bar">Communication</div>
                                                <div class="conn-grid" style="grid-template-columns: 1fr;">
                                                    <div class="conn-item">
                                                        <span class="info-label">PABX System</span>
                                                        <div class="info-value"><?php echo displayValue($submission['connectivity']['pabx']); ?></div>
                                                    </div>
                                                    <div class="conn-item">
                                                        <span class="info-label">Telephone Numbers</span>
                                                        <div class="info-value"><?php echo displayValue($submission['connectivity']['telephone_numbers']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="section-bar">Radio Communication Equipment</div>
                                                <div class="conn-grid" style="grid-template-columns: 1fr 1fr;">
                                                    <div class="conn-item">
                                                        <span class="info-label">Base Radios</span>
                                                        <div class="info-value"><?php echo displayValue($submission['connectivity']['base_radios']); ?></div>
                                                    </div>
                                                    <div class="conn-item">
                                                        <span class="info-label">Handheld Personal</span>
                                                        <div class="info-value"><?php echo displayValue($submission['connectivity']['handheld_personal']); ?></div>
                                                    </div>
                                                    <div class="conn-item">
                                                        <span class="info-label">Handheld LGU</span>
                                                        <div class="info-value"><?php echo displayValue($submission['connectivity']['handheld_lgu']); ?></div>
                                                    </div>
                                                    <div class="conn-item">
                                                        <span class="info-label">Total</span>
                                                        <div class="info-value"><?php echo displayValue($submission['connectivity']['handheld_total']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="footer-note">
                                            DATA FOR THE FORMULATION OF MANGALDAN RMAPS 2026-2030 <br>
                                            <span style="color: red; font-weight: bold;">PLEASE SUBMIT THIS FORM TO ICTMIS</span><br>
                                            Generated on: <?php echo date('F d, Y h:i A'); ?>
                                        </div>
                                    </div>
                                    
                                    <!-- ACTIONS FOOTER -->
                                    <div class="issp-footer-actions no-print">
                                        <button class="issp-btn issp-btn-pdf" onclick="generateRMAPSPDF(<?php echo $idx; ?>)">
                                            <i class="bi bi-file-earmark-pdf"></i> Download PDF
                                        </button>
                                        <button class="issp-btn issp-btn-print" onclick="printSingleCardFromModal(<?php echo $idx; ?>)">
                                            <i class="bi bi-printer"></i> Print Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- DETAIL OVERLAY MODAL -->
            <div id="detailOverlay" class="detail-overlay" onclick="closeSubmissionDetails(event)">
                <div class="detail-modal" onclick="event.stopPropagation()">
                    <button class="close-modal" onclick="closeSubmissionDetails()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div id="modalContent"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- PDF LIBRARY DIRECTLY HERE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        // NEW SIMPLIFIED PDF FUNCTION
        function generateRMAPSPDF(idx) {
            // 1. Immediate Feedback
            alert('Pindot detected! Sinisimulan ang PDF process...');

            try {
                // 2. Check Library
                if (typeof html2pdf === 'undefined') {
                    alert('Library Error: Hindi mahanap ang html2pdf. Pakisubukang i-refresh (Ctrl+F5).');
                    return;
                }

                // 3. Get Content
                var sourceElement = document.getElementById('submission-details-' + idx);
                if (!sourceElement) {
                    alert('Error: Hindi mahanap ang submission details.');
                    return;
                }

                // 4. Create Clean Copy for PDF
                var workerElement = document.createElement('div');
                workerElement.style.padding = '10mm';
                workerElement.style.background = 'white';
                workerElement.innerHTML = sourceElement.innerHTML;
                
                // Add internal styles to force layout in PDF
                var pdfStyle = document.createElement('style');
                pdfStyle.innerHTML = `
                    .sheet { padding: 0 !important; width: 100% !important; border: none !important; }
                    .top-head { margin-bottom: 20px !important; padding-bottom: 15px !important; }
                    .section-bar { 
                        margin-top: 15px !important; 
                        margin-bottom: 8px !important; 
                        padding: 8px 12px !important; 
                        background: #f8fafc !important;
                        -webkit-print-color-adjust: exact;
                    }
                    .info-grid { gap: 20px !important; padding: 5px 0 !important; }
                    .equip-table th, .equip-table td { padding: 6px 4px !important; font-size: 10px !important; }
                    .systems-container { gap: 20px !important; margin-top: 5px !important; }
                    .system-list li { padding: 4px 10px !important; font-size: 11px !important; }
                    .issp-footer-actions, .no-print, .close-modal { display: none !important; }
                    .section-bar[style*="page-break-before: always"] {
                        page-break-before: always !important;
                        margin-top: 0 !important;
                    }
                `;
                workerElement.appendChild(pdfStyle);

                // 5. Options
                var opt = {
                    margin: 5,
                    filename: 'RMAPS_Report_' + idx + '.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { 
                        scale: 2, 
                        useCORS: true,
                        letterRendering: true
                    },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
                    pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
                };

                // 6. EXECUTE
                alert('Generating PDF... Pakihintay ng ilang segundo.');
                
                html2pdf().set(opt).from(workerElement).save().then(function() {
                    alert('Download Successful!');
                }).catch(function(err) {
                    alert('PDF Error: ' + err);
                });

            } catch (e) {
                alert('System Error: ' + e.message);
            }
        }

        function showSubmissionDetails(idx) {
            const detailsContent = document.getElementById('submission-details-' + idx).innerHTML;
            document.getElementById('modalContent').innerHTML = detailsContent;
            document.getElementById('detailOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeSubmissionDetails(event) {
            if (event && event.target !== event.currentTarget && event.target !== document.getElementById('detailOverlay')) {
                return;
            }
            document.getElementById('detailOverlay').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function printSingleCardFromModal(idx) {
            const originalTitle = document.title;
            document.title = 'RMAPS Form - Official Submission';
            
            const printDiv = document.createElement('div');
            printDiv.innerHTML = document.getElementById('submission-details-' + idx).innerHTML;
            
            const actions = printDiv.querySelector('.issp-footer-actions');
            if (actions) actions.remove();

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>RMAPS Official Form</title>
                    <meta charset="UTF-8">
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
                    <style>
                        :root {
                            --primary-system: #1e40af;
                            --secondary-system: #f1f5f9;
                            --accent-blue: #3b82f6;
                            --border-color: #e2e8f0;
                            --text-dark: #0f172a;
                            --text-muted: #64748b;
                            --section-bg: #f8fafc;
                        }
                        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; color: #1e293b; background: white; }
                        .sheet { background: #fff; width: 100%; padding: 0; position: relative; }
                        .top-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px double var(--primary-system); padding-bottom: 25px; margin-bottom: 40px; gap: 30px; }
                        .top-head img { width: 85px; height: 85px; object-fit: contain; }
                        .title-container { text-align: center; flex-grow: 1; }
                        .title-container .sub-text { font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px; }
                        .title-container h1 { margin: 0; font-size: 20px; font-weight: 900; color: var(--primary-system); line-height: 1.2; }
                        .title-container .year-range { font-size: 16px; font-weight: 700; color: #334155; margin-top: 8px; display: inline-block; padding: 2px 15px; background: var(--secondary-system); border-radius: 20px; }
                        .section-bar { background: var(--section-bg); border-bottom: 2px solid var(--primary-system); padding: 12px 15px; font-weight: 800; font-size: 14px; color: var(--primary-system); margin-top: 35px; margin-bottom: 20px; text-transform: uppercase; display: flex; align-items: center; gap: 10px; }
                        .section-bar::before { content: ""; display: inline-block; width: 4px; height: 18px; background: var(--primary-system); border-radius: 2px; }
                        .info-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; padding: 10px 0; }
                        .info-item { display: flex; flex-direction: column; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px; }
                        .info-label { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
                        .info-value { font-size: 16px; font-weight: 700; color: var(--text-dark); }
                        .equip-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; }
                        .equip-table th { background: var(--primary-system); color: #fff; font-weight: 700; padding: 14px 12px; text-align: center; font-size: 11px; text-transform: uppercase; }
                        .equip-table td { padding: 12px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); font-size: 13px; }
                        .equip-table tr:nth-child(even) { background-color: #f8fafc; }
                        .equip-table .total-row { background: #f1f5f9; font-weight: 800; }
                        .systems-container { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 10px; }
                        .system-list { margin: 0; padding: 0; list-style: none; }
                        .system-list li { padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; font-weight: 600; color: #475569; }
                        .system-list li svg { color: var(--accent-blue); }
                        .conn-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px; }
                        .conn-item { background: #fff; border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; display: flex; flex-direction: column; gap: 5px; }
                        .conn-item .info-label { border-bottom: 1px solid var(--secondary-system); padding-bottom: 5px; margin-bottom: 5px; }
                        .conn-item .info-value { font-size: 14px; color: var(--primary-system); font-weight: 700; }
                        .footer-note { margin-top: 60px; border-top: 1px solid var(--border-color); padding-top: 25px; font-size: 10px; color: var(--text-muted); text-align: center; font-style: italic; }
                        @media print { 
                            body { padding: 0; background: white; }
                            .sheet { 
                                width: 210mm; 
                                min-height: 297mm;
                                padding: 10mm 15mm;
                                margin: 0;
                                box-shadow: none;
                                position: relative;
                                font-size: 12px;
                            }
                            .section-bar {
                                -webkit-print-color-adjust: exact;
                                break-inside: avoid;
                                padding: 8px 12px;
                                margin-top: 15px;
                                margin-bottom: 8px;
                            }
                            .section-bar[style*="page-break-before: always"] {
                                break-before: page;
                                margin-top: 0 !important;
                            }
                            .systems-container {
                                gap: 20px;
                                margin-top: 5px;
                            }
                            .systems-container > div {
                                break-inside: avoid;
                            }
                            .equip-table, .conn-grid {
                                break-inside: avoid;
                            }
                            .system-list li {
                                padding: 6px 10px;
                                font-size: 12px;
                            }
                            .top-head {
                                margin-bottom: 20px;
                                padding-bottom: 15px;
                            }
                            .info-grid {
                                gap: 20px;
                            }
                            .equip-table th, .equip-table td {
                                padding: 8px 6px;
                                font-size: 11px;
                            }
                            @page { 
                                size: A4; 
                                margin: 0; 
                            }
                            .no-print { display: none !important; }
                        }
                    </style>
                </head>
                <body>
                    <div class="sheet">
                        ${printDiv.innerHTML}
                    </div>
                    <script>
                        window.onload = function() { 
                            setTimeout(() => {
                                window.print(); 
                                window.close(); 
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
            document.title = originalTitle;
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeSubmissionDetails();
            }
        });

        document.getElementById('historySearch')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.history-card:not(.no-submissions)');
            let hasResults = false;

            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'flex';
                    hasResults = true;
                } else {
                    card.style.display = 'none';
                }
            });

            const grid = document.getElementById('submissionsGrid');
            const noResultsMsg = document.getElementById('noSearchResults');
            
            if (!hasResults && searchTerm !== '') {
                if (!noResultsMsg) {
                    const msg = document.createElement('div');
                    msg.id = 'noSearchResults';
                    msg.className = 'text-center py-5 w-100';
                    msg.innerHTML = `
                        <i class="bi bi-search" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i>
                        <h4 style="color: #64748b;">No results found for "${searchTerm}"</h4>
                        <p class="text-muted">Try searching with a different keyword.</p>
                    `;
                    grid.appendChild(msg);
                } else {
                    noResultsMsg.querySelector('h4').textContent = `No results found for "${searchTerm}"`;  
                    noResultsMsg.style.display = 'block';
                }
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        });
    </script> 
</body>
</html>