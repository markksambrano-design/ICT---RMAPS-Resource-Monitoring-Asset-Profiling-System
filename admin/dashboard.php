<?php
include "../config.php";
include "../config/functions.php";
include "../config/auth_check.php";

// Get admin name from session or database
$adminName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// Get selected year from URL, default to 2026 as requested
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 2026;
$availableYears = [2026, 2027, 2028, 2029, 2030];

/* ===============================
   DASHBOARD STATISTICS
================================*/

// Total Forms (Filtered by Year)
$formsCountResult = safeQuery($conn, "SELECT COUNT(*) AS total FROM form WHERE YEAR(date_submitted) = $selectedYear");
$totalForms = ($formsCountResult && ($row = mysqli_fetch_assoc($formsCountResult))) ? (int)$row['total'] : 0;

// Total Users (registered user accounts only - Not typically year filtered)
$totalUsers = safeCount($conn, 'users');

// Total Inventory (sum of units from computer and other equipment - Filtered by Year)
$compUnitsQuery = safeQuery($conn, "SELECT SUM(ce.number_of_units) as total FROM computer_equipment ce JOIN form f ON ce.form_id = f.id WHERE YEAR(f.date_submitted) = $selectedYear");
$compUnits = ($compUnitsQuery && $row = mysqli_fetch_assoc($compUnitsQuery)) ? (int)$row['total'] : 0;

$otherUnitsQuery = safeQuery($conn, "SELECT 
    SUM(inkjet_printer + deskjet_printer + dot_matrix_printer + switch_hubs + routers + modem) as total 
    FROM other_ict_equipment oie
    JOIN form f ON oie.form_id = f.id
    WHERE YEAR(f.date_submitted) = $selectedYear");
$otherUnits = ($otherUnitsQuery && $row = mysqli_fetch_assoc($otherUnitsQuery)) ? (int)$row['total'] : 0;

$totalInventory = $compUnits + $otherUnits;

// Total Laptop/Desktop (Filtered by Year)
$laptopDesktopQuery = safeQuery($conn, "SELECT SUM(ce.number_of_units) as total FROM computer_equipment ce JOIN form f ON ce.form_id = f.id WHERE (LOWER(ce.item) LIKE '%laptop%' OR LOWER(ce.item) LIKE '%desktop%' OR LOWER(ce.item) LIKE '%computer%' OR LOWER(ce.item) LIKE '%notebook%') AND YEAR(f.date_submitted) = $selectedYear");
$totalLaptopDesktop = ($laptopDesktopQuery && $row = mysqli_fetch_assoc($laptopDesktopQuery)) ? (int)$row['total'] : 0;

// Total Printers (Filtered by Year)
$printersQuery = safeQuery($conn, "SELECT SUM(inkjet_printer + deskjet_printer + dot_matrix_printer) as total FROM other_ict_equipment oie JOIN form f ON oie.form_id = f.id WHERE YEAR(f.date_submitted) = $selectedYear");
$totalPrinters = ($printersQuery && $row = mysqli_fetch_assoc($printersQuery)) ? (int)$row['total'] : 0;

// Total Networking (Filtered by Year)
$networkingQuery = safeQuery($conn, "SELECT SUM(switch_hubs + routers + modem) as total FROM other_ict_equipment oie JOIN form f ON oie.form_id = f.id WHERE YEAR(f.date_submitted) = $selectedYear");
$totalNetworking = ($networkingQuery && $row = mysqli_fetch_assoc($networkingQuery)) ? (int)$row['total'] : 0;

// Get recent forms (Filtered by Year)
$recentFormsResult = safeQuery($conn, "
    SELECT 
        f.office_name, 
        f.date_submitted, 
        GROUP_CONCAT(DISTINCT ce.item SEPARATOR ', ') as items, 
        SUM(ce.number_of_units) as total_units,
        f.id 
    FROM form f
    LEFT JOIN computer_equipment ce ON f.id = ce.form_id
    WHERE YEAR(f.date_submitted) = $selectedYear
    GROUP BY f.id
    ORDER BY f.date_submitted DESC, f.id DESC
    LIMIT 10
");
$recentForms = $recentFormsResult ?: null;

// Get recent systems (Filtered by Year)
$recentSystemsResult = safeQuery($conn, "
    SELECT 
        f.office_name, 
        f.date_submitted, 
        s.system_1,
        s.system_2,
        s.system_3,
        s.system_4,
        s.system_5
    FROM form f
    JOIN systems s ON f.id = s.form_id
    WHERE (s.system_1 != '' OR s.system_2 != '' OR s.system_3 != '' OR s.system_4 != '' OR s.system_5 != '')
    AND YEAR(f.date_submitted) = $selectedYear
    ORDER BY f.date_submitted DESC, f.id DESC
    LIMIT 10
");
$recentSystems = $recentSystemsResult ?: null;

/* ===============================
   CHART DATA FETCHING
================================*/

// 1. Yearly Equipment Activity Rating per Office (replacing Monthly Activity)
$officeEquipmentQuery = safeQuery($conn, "
    SELECT 
        o.office_name,
        COALESCE(SUM(CASE 
            WHEN (LOWER(ce.item) LIKE '%laptop%' OR LOWER(ce.item) LIKE '%desktop%' OR LOWER(ce.item) LIKE '%computer%' OR LOWER(ce.item) LIKE '%notebook%')
            AND YEAR(f.date_submitted) = $selectedYear
            THEN ce.number_of_units 
            ELSE 0 
        END), 0) as total_equipment
    FROM offices o
    LEFT JOIN computer_equipment ce ON ce.office_name = o.office_name
    LEFT JOIN form f ON ce.form_id = f.id
    GROUP BY o.office_name
    ORDER BY total_equipment DESC
");

$officeNames = [];
$officeEquipmentTotals = [];

if ($officeEquipmentQuery) {
    while ($row = mysqli_fetch_assoc($officeEquipmentQuery)) {
        $officeNames[] = $row['office_name'];
        $officeEquipmentTotals[] = (int)$row['total_equipment'];
    }
}

$highestActivityValue = !empty($officeEquipmentTotals) ? max($officeEquipmentTotals) : 0;
$highestActivityIndex = !empty($officeEquipmentTotals) ? array_search($highestActivityValue, $officeEquipmentTotals) : false;
$highestActivityOffice = $highestActivityIndex !== false ? $officeNames[$highestActivityIndex] : 'None';

$totalOfficesCount = count($officeNames);
$activeOfficesCount = count(array_filter($officeEquipmentTotals, function($v) { return $v > 0; }));
$totalEquipmentAllOffices = array_sum($officeEquipmentTotals);
$averagePerOffice = $totalOfficesCount > 0 ? round($totalEquipmentAllOffices / $totalOfficesCount, 2) : 0;

$sortedTotals = $officeEquipmentTotals;
sort($sortedTotals);
$medianPerOffice = 0;
if ($totalOfficesCount > 0) {
    $mid = floor($totalOfficesCount / 2);
    if ($totalOfficesCount % 2 == 0) {
        $medianPerOffice = round(($sortedTotals[$mid - 1] + $sortedTotals[$mid]) / 2, 1);
    } else {
        $medianPerOffice = $sortedTotals[$mid];
    }
}

// 2. Equipment Distribution Data (Filtered by Year)
$equipmentCounts = [
    'Desktop' => 0,
    'Laptop' => 0,
    'Switch Hubs' => 0,
    'Routers' => 0,
    'Modem' => 0,
    'Inkjet' => 0,
    'Deskjet' => 0,
    'Dot Matrix' => 0
];

$mainItemsQuery = safeQuery($conn, "
    SELECT ce.item, SUM(ce.number_of_units) as total_units 
    FROM computer_equipment ce
    JOIN form f ON ce.form_id = f.id
    WHERE YEAR(f.date_submitted) = $selectedYear
    GROUP BY ce.item");
if ($mainItemsQuery) {
    while ($row = mysqli_fetch_assoc($mainItemsQuery)) {
        $item = strtolower($row['item']);
        $units = (int)$row['total_units'];
        
        if (strpos($item, 'desktop') !== false || strpos($item, 'computer') !== false) {
            $equipmentCounts['Desktop'] += $units;
        } elseif (strpos($item, 'laptop') !== false || strpos($item, 'notebook') !== false) {
            $equipmentCounts['Laptop'] += $units;
        }
    }
}

$networkQuery = safeQuery($conn, "SELECT 
    SUM(switch_hubs) as total_switches, 
    SUM(routers) as total_routers, 
    SUM(modem) as total_modems,
    SUM(inkjet_printer) as total_inkjet,
    SUM(deskjet_printer) as total_deskjet,
    SUM(dot_matrix_printer) as total_dotmatrix
    FROM other_ict_equipment oie
    JOIN form f ON oie.form_id = f.id
    WHERE YEAR(f.date_submitted) = $selectedYear");

if ($networkQuery && $row = mysqli_fetch_assoc($networkQuery)) {
    $equipmentCounts['Switch Hubs'] = (int)$row['total_switches'];
    $equipmentCounts['Routers'] = (int)$row['total_routers'];
    $equipmentCounts['Modem'] = (int)$row['total_modems'];
    $equipmentCounts['Inkjet'] = (int)$row['total_inkjet'];
    $equipmentCounts['Deskjet'] = (int)$row['total_deskjet'];
    $equipmentCounts['Dot Matrix'] = (int)$row['total_dotmatrix'];
}

$compTotal = $equipmentCounts['Desktop'] + $equipmentCounts['Laptop'];
$desktopPct = $compTotal > 0 ? round(($equipmentCounts['Desktop'] / $compTotal) * 100, 1) : 0;
$laptopPct = $compTotal > 0 ? round(($equipmentCounts['Laptop'] / $compTotal) * 100, 1) : 0;

$netTotal = $equipmentCounts['Switch Hubs'] + $equipmentCounts['Routers'] + $equipmentCounts['Modem'];
$switchPct = $netTotal > 0 ? round(($equipmentCounts['Switch Hubs'] / $netTotal) * 100, 1) : 0;
$routerPct = $netTotal > 0 ? round(($equipmentCounts['Routers'] / $netTotal) * 100, 1) : 0;
$modemPct = $netTotal > 0 ? round(($equipmentCounts['Modem'] / $netTotal) * 100, 1) : 0;

$printerTotal = $equipmentCounts['Inkjet'] + $equipmentCounts['Deskjet'] + $equipmentCounts['Dot Matrix'];
$inkjetPct = $printerTotal > 0 ? round(($equipmentCounts['Inkjet'] / $printerTotal) * 100, 1) : 0;
$deskjetPct = $printerTotal > 0 ? round(($equipmentCounts['Deskjet'] / $printerTotal) * 100, 1) : 0;
$dotMatrixPct = $printerTotal > 0 ? round(($equipmentCounts['Dot Matrix'] / $printerTotal) * 100, 1) : 0;

/* ===============================
   YEARLY COMPUTER DATA
================================*/

$computerDataByYear = [];
$yearQuery = safeQuery($conn,"
    SELECT YEAR(f.date_submitted) as year, SUM(ce.number_of_units) as total
    FROM form f
    JOIN computer_equipment ce ON f.id = ce.form_id
    WHERE LOWER(ce.item) LIKE '%computer%' 
       OR LOWER(ce.item) LIKE '%desktop%' 
       OR LOWER(ce.item) LIKE '%laptop%' 
       OR LOWER(ce.item) LIKE '%notebook%'
    GROUP BY YEAR(f.date_submitted)
");

if ($yearQuery) {
    while ($row = mysqli_fetch_assoc($yearQuery)) {
        $computerDataByYear[(int)$row['year']] = (int)$row['total'];
    }
}

$startYear = 2026;
$endYear = 2030;

$yearLabels = [];
$computerData = [];

for ($year = $startYear; $year <= $endYear; $year++) {
    $yearLabels[] = (string)$year;
    $computerData[] = isset($computerDataByYear[$year]) ? $computerDataByYear[$year] : 0;
}

$totalYearlyDevices = array_sum($computerData);
$averagePerYear = count($computerData) > 0 ? round($totalYearlyDevices / count($computerData), 1) : 0;

$highestYearValue = max($computerData);
$highestYearIndex = array_search($highestYearValue, $computerData);
$highestYear = isset($yearLabels[$highestYearIndex]) ? $yearLabels[$highestYearIndex] : '-';

$lastYearValue = end($computerData);
$prevYearValue = prev($computerData);

if ($prevYearValue > 0) {
    $growthPercent = round((($lastYearValue - $prevYearValue) / $prevYearValue) * 100, 1);
    $growthText = ($growthPercent > 0 ? '+' : '') . $growthPercent . '%';
    $growthDiff = abs($lastYearValue - $prevYearValue);
    $growthDirText = $growthPercent >= 0 ? 'increased' : 'decreased';
    $growthStatus = $growthPercent >= 0 ? 'positive' : 'negative';
} else {
    $growthPercent = $lastYearValue > 0 ? 100 : 0;
    $growthText = $lastYearValue > 0 ? '+100%' : '0%';
    $growthDiff = $lastYearValue;
    $growthDirText = 'increased';
    $growthStatus = 'positive';
}

$lastYearLabel = end($yearLabels);
$prevYearLabel = prev($yearLabels);

// 5. Office Submission Status (Compliance)
$officesQuery = safeQuery($conn, "SELECT office_name FROM offices ORDER BY office_name ASC");
$allOffices = [];
if ($officesQuery) {
    while ($row = mysqli_fetch_assoc($officesQuery)) {
        $allOffices[] = $row['office_name'];
    }
}

$submittedOfficesQuery = safeQuery($conn, "SELECT DISTINCT office_name FROM form WHERE YEAR(date_submitted) = $selectedYear");
$submittedOffices = [];
if ($submittedOfficesQuery) {
    while ($row = mysqli_fetch_assoc($submittedOfficesQuery)) {
        $submittedOffices[] = $row['office_name'];
    }
}

$nonSubmittedOffices = array_diff($allOffices, $submittedOffices);
$complianceRate = count($allOffices) > 0 ? round((count($submittedOffices) / count($allOffices)) * 100, 1) : 0;

// 6. Equipment Maintenance Alerts
$maintenanceAlerts = [];
$maintenanceQuery = safeQuery($conn, "
    SELECT f.office_name, ce.item, ce.brand, f.date_submitted, ce.number_of_units
    FROM form f
    JOIN computer_equipment ce ON f.id = ce.form_id
    WHERE YEAR(f.date_submitted) <= (" . date('Y') . " - 3)
    ORDER BY f.date_submitted ASC
    LIMIT 5
");

if ($maintenanceQuery) {
    while ($row = mysqli_fetch_assoc($maintenanceQuery)) {
        $maintenanceAlerts[] = $row;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'Admin Dashboard | ICTMIS'; ?>
<?php include 'components/head.php'; ?>
<style>
    /* Modern Reset & Base Styles */
    :root {
        --primary: #4f46e5;
        --primary-light: #818cf8;
        --secondary: #0f172a;
        --accent: #00f2ff;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #1e293b;
        --light: #f8fafc;
        --glass-bg: rgba(255, 255, 255, 0.95);
        --glass-border: rgba(15, 23, 42, 0.08);
        --card-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.05);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    html.dark-mode {
        --primary: #00f2ff;
        --primary-light: #67e8f9;
        --secondary: #020617;
        --glass-bg: rgba(7, 19, 44, 0.85);
        --glass-border: rgba(0, 242, 255, 0.15);
        --card-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.3);
        --dark: #e2e8f0;
        --light: #0f172a;
    }

    html.dark-mode .text-muted {
        color: #94a3b8 !important;
    }

    body {
        background: var(--light);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        transition: var(--transition);
    }

    /* Futuristic Glass Card */
    .glass-card-modern {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        border-radius: 28px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        overflow: hidden;
    }

    .glass-card-modern:hover {
        transform: translateY(-4px);
        border-color: rgba(79, 70, 229, 0.3);
        box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.15);
    }

    /* Stat Cards Modern */
    .stat-card {
        background: var(--glass-bg);
        border-radius: 20px;
        padding: 1.25rem;
        transition: var(--transition);
        border: 1px solid var(--glass-border);
        position: relative;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: rgba(79, 70, 229, 0.4);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(16, 185, 129, 0.08);
        color: #10b981;
        padding: 4px 10px;
        border-radius: 30px;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid rgba(16, 185, 129, 0.15);
    }

    .live-dot {
        width: 6px;
        height: 6px;
        background: #10b981;
        border-radius: 50%;
        animation: livePulse 1.5s ease-in-out infinite;
    }
    
    @keyframes livePulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.8); }
    }

    .stat-number {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.2;
        color: #0f172a;
        margin-bottom: 2px;
    }

    html.dark-mode .stat-number {
        color: #f8fafc;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 0;
    }

    .stat-sublabel {
        font-size: 0.7rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: auto;
        padding-top: 15px;
    }

    .badge-system-existing {
        background-color: #e0e7ff !important;
        color: #3730a3 !important;
        border: 1px solid #c7d2fe !important;
    }
    .badge-system-proposed {
        background-color: #dcfce7 !important;
        color: #166534 !important;
        border: 1px solid #bbf7d0 !important;
    }

    html.dark-mode .badge-system-existing {
        background-color: rgba(79, 70, 229, 0.2) !important;
        color: #818cf8 !important;
        border-color: rgba(79, 70, 229, 0.3) !important;
    }
    html.dark-mode .badge-system-proposed {
        background-color: rgba(16, 185, 129, 0.2) !important;
        color: #6ee7b7 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }

    .data-table-modern {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .data-table-modern th {
        background: rgba(79, 70, 229, 0.05);
        padding: 1rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 1px solid var(--glass-border);
    }

    .data-table-modern td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        color: var(--dark);
        font-weight: 500;
    }

    html.dark-mode .data-table-modern td {
        border-bottom-color: rgba(255, 255, 255, 0.05);
    }

    .btn-modern {
        padding: 0.6rem 1.5rem;
        border-radius: 40px;
        font-weight: 600;
        transition: var(--transition);
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        border: none;
        color: white;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
        color: white;
    }

    /* Systems Table Custom Design */
    .systems-row-card {
        background: white;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 16px;
        margin-bottom: 12px;
        transition: var(--transition);
    }

    .systems-row-card:hover {
        border-color: var(--primary-light);
        box-shadow: 0 8px 20px rgba(0,0,0,0.04);
    }

    .office-icon-container {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: white;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
    }

    .system-badge-modern {
        background: #f5f3ff;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
        padding: 0.5rem 0.8rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .proposed-badge-modern {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
        padding: 0.5rem 0.8rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .data-table-modern thead th {
        background: #f8fafc;
        border: none;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        padding: 1.25rem 1.5rem;
    }

    .data-table-modern tbody td {
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        padding: 1.25rem 1.5rem;
    }

    /* DataTables Overrides to match screenshot */
    .dataTables_wrapper .dataTables_length, 
    .dataTables_wrapper .dataTables_filter {
        padding: 1.5rem;
        color: #64748b;
        font-weight: 500;
    }

    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' class='bi bi-search' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 12px center;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding: 1.5rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 10px !important;
        border: none !important;
        background: #f1f5f9 !important;
        margin: 0 4px;
        padding: 0.5rem 1rem !important;
        font-weight: 600;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
    }

    .chart-modern {
        background: var(--glass-bg);
        border-radius: 24px;
        padding: 1.5rem;
        border: 1px solid var(--glass-border);
        transition: var(--transition);
        height: 100%;
    }

    .chart-modern:hover {
        border-color: rgba(79, 70, 229, 0.3);
        box-shadow: 0 15px 30px -12px rgba(0, 0, 0, 0.1);
    }

    .dashboard-badge {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: white;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        border: none;
    }

    .year-filter-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
    }

    .year-select-modern {
        background: white;
        border: 1.5px solid #e2e8f0;
        padding: 0.5rem 2.5rem 0.5rem 1.25rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.2s ease;
        appearance: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        line-height: 1.2;
    }

    .year-select-modern:hover {
        border-color: #4f46e5;
        background: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .year-select-modern:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .select-icon-wrapper {
        position: absolute;
        right: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .submission-lists::-webkit-scrollbar {
        width: 5px;
    }
    .submission-lists::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .submission-lists::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .submission-lists::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .office-item {
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    .office-item:hover {
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-color: #e2e8f0;
    }

    .compliance-donut-container {
        width: 140px;
        height: 140px;
        position: relative;
        margin: 0 auto;
    }
    .compliance-percentage-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        z-index: 1;
    }

    .office-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 0.75rem;
    }

    .office-status-pill {
        padding: 0.6rem 0.8rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: white;
        border: 1px solid #eef2f6;
        cursor: default;
    }

    .office-status-pill.submitted {
        border-left: 4px solid #10b981;
        color: #065f46;
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.05);
    }

    .office-status-pill.pending {
        border-left: 4px solid #e2e8f0;
        color: #64748b;
        background: #f8fafc;
    }

    .office-status-pill:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 2;
    }

    .office-status-pill.submitted:hover {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .office-status-pill.pending:hover {
        border-color: #94a3b8;
        background: white;
    }

    .status-section-header {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94a3b8;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-top: 0.5rem;
    }

    .status-section-header::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, #e2e8f0, transparent);
    }

    .office-search-wrapper {
        position: relative;
        margin-bottom: 1.25rem;
    }
    .office-search-input {
        width: 100%;
        padding: 0.5rem 1rem 0.5rem 2.2rem;
        border-radius: 12px;
        border: 1.5px solid #e2e8f0;
        font-size: 0.8rem;
        font-weight: 500;
        transition: all 0.2s ease;
        background: #f8fafc;
    }
    .office-search-input:focus {
        outline: none;
        border-color: var(--primary);
        background: white;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }
    .office-search-icon {
        position: absolute;
        left: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.85rem;
    }

    @keyframes pendingPulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.5); opacity: 0.5; }
        100% { transform: scale(1); opacity: 1; }
    }
    .pending-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #cbd5e1;
        position: relative;
    }
    .office-status-pill.pending:hover .pending-dot {
        background: #94a3b8;
    }
    .pending-dot::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: inherit;
        animation: pendingPulse 2s infinite;
    }
    
    .welcome-header-modern {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        border: 1px solid #edf2f7;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.02);
        position: relative;
        overflow: hidden;
    }

    .welcome-header-modern::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 300px;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(79, 70, 229, 0.03));
        pointer-events: none;
    }

    @media (max-width: 768px) {
        .stat-card { padding: 1rem; }
        .chart-modern { padding: 1rem; }
        .welcome-header-modern { padding: 1.5rem; }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-8px); }
    }

    .float-animation {
        animation: float 4s ease-in-out infinite;
    }

    @keyframes slideUpFade {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-in {
        animation: slideUpFade 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        opacity: 0;
    }

    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }
    .delay-5 { animation-delay: 0.5s; }

    .stat-card:hover .icon-box-solid {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }

    .icon-box-solid {
        transition: var(--transition);
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white !important;
    }

    .count-up {
        display: inline-block;
    }

    .total-badge-modern {
        background: rgba(79, 70, 229, 0.08);
        color: var(--primary);
        padding: 0.4rem 0.8rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .icon-box-primary { background: linear-gradient(135deg, #6366f1, #4f46e5); }
    .icon-box-success { background: linear-gradient(135deg, #10b981, #059669); }
    .icon-box-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .icon-box-info { background: linear-gradient(135deg, #06b6d4, #0891b2); }
    .icon-box-danger { background: linear-gradient(135deg, #ef4444, #dc2626); }

    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 10px;
    }
    
    .hover-lift {
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        cursor: pointer;
    }
    .hover-lift:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 30px rgba(79, 70, 229, 0.15);
        border-color: var(--primary) !important;
    }

    @keyframes floating {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
        100% { transform: translateY(0px); }
    }
    .floating-card {
        animation: floating 3s ease-in-out infinite;
    }

    /* ===== FIXED PIE CHART ANIMATIONS ===== */
    
    /* Initial Forming Animation - starts small and spins in */
    @keyframes pieForming {
        0% {
            transform: scale(0) rotate(-180deg);
            opacity: 0;
        }
        30% {
            transform: scale(0.6) rotate(-60deg);
            opacity: 0.3;
        }
        60% {
            transform: scale(1.1) rotate(10deg);
            opacity: 0.8;
        }
        85% {
            transform: scale(0.96) rotate(-3deg);
        }
        100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
        }
    }

    /* Floating Glow effect after formation */
    @keyframes pieFloatingGlow {
        0% {
            filter: drop-shadow(0 5px 15px rgba(79, 70, 229, 0.2));
            transform: translateY(0px);
        }
        50% {
            filter: drop-shadow(0 15px 25px rgba(79, 70, 229, 0.4));
            transform: translateY(-5px);
        }
        100% {
            filter: drop-shadow(0 5px 15px rgba(79, 70, 229, 0.2));
            transform: translateY(0px);
        }
    }

    /* Pulse effect for chart after formation */
    @keyframes chartPulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.02); opacity: 0.95; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* Animation classes */
    .pie-chart-forming {
        animation: pieForming 1.2s cubic-bezier(0.34, 1.2, 0.64, 1) forwards;
        transform-origin: center;
        will-change: transform, opacity;
    }

    .pie-chart-glow {
        animation: pieFloatingGlow 4s ease-in-out infinite;
    }

    .pie-chart-pulse {
        animation: chartPulse 0.6s ease-in-out;
    }

    .pie-canvas-wrapper {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .pie-canvas-wrapper canvas {
        transition: all 0.4s ease;
        max-width: 100%;
        height: auto;
    }
    
    .pie-chart-container {
        opacity: 0;
        animation: slideUpFade 0.5s ease forwards;
    }
    
    .pie-chart-container:nth-child(1) { animation-delay: 0.1s; }
    .pie-chart-container:nth-child(2) { animation-delay: 0.2s; }
    .pie-chart-container:nth-child(3) { animation-delay: 0.3s; }
    
    .pie-chart-modern:hover canvas {
        filter: drop-shadow(0 0 12px rgba(79, 70, 229, 0.5));
        transition: filter 0.3s ease;
    }

    @keyframes bounce {
        0% { transform: scale(0.8); opacity: 0; }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .stat-number-bounce {
        animation: bounce 0.6s ease-out;
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
        
        <!-- WELCOME HEADER MODERN -->
        <div class="welcome-header-modern animate-in delay-1">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="dashboard-badge">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </div>
                        <div class="year-filter-container">
                            <form action="" method="GET" id="yearForm" class="year-filter-wrapper">
                                <select name="year" onchange="document.getElementById('yearForm').submit()" class="year-select-modern">
                                    <?php foreach ($availableYears as $year): ?>
                                        <option value="<?php echo $year; ?>" <?php echo ($selectedYear == $year) ? 'selected' : ''; ?>>
                                            Year <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="select-icon-wrapper">
                                    <i class="bi bi-chevron-down"></i>
                                </div>
                            </form>
                        </div>
                    </div>
                    <h1 class="display-6 fw-bold mb-2" style="color: #0f172a;">
                        Welcome back, <?php echo htmlspecialchars($adminName); ?>!
                    </h1>
                    <p class="text-muted mb-0 fs-5">
                        Manage your ICT Management Information System for <span class="fw-bold text-primary"><?php echo $selectedYear; ?></span>.
                    </p>
                </div>
                <div class="col-md-4 text-md-end d-none d-md-block">
                    <div class="float-animation">
                        <i class="bi bi-cpu display-4" style="color: var(--primary); opacity: 0.5; filter: drop-shadow(0 0 15px rgba(79, 70, 229, 0.2));"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTICS CARDS - MODERN -->
        <div class="row mb-4 g-4 animate-in delay-2">
            <div class="col-md-4 col-lg-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-solid shadow-sm" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); width: 48px; height: 48px; border-radius: 12px;">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                            <div>
                                <h2 class="stat-number count-up mb-0" style="font-size: 1.8rem;" data-target="<?php echo $totalForms; ?>">0</h2>
                                <p class="stat-label">Total RMAPS Forms</p>
                            </div>
                        </div>
                        <div class="live-indicator">
                            <span class="live-dot"></span>
                            <span>Live</span>
                        </div>
                    </div>
                    <div class="stat-sublabel text-success mt-auto pt-2 border-top" style="border-top: 1px solid rgba(0,0,0,0.05) !important;">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span class="fw-bold">System Submissions</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-solid shadow-sm" style="background: linear-gradient(135deg, #06b6d4, #0891b2); width: 48px; height: 48px; border-radius: 12px;">
                                <i class="bi bi-people fs-4"></i>
                            </div>
                            <div>
                                <h2 class="stat-number count-up mb-0" style="font-size: 1.8rem;" data-target="<?php echo $totalUsers; ?>">0</h2>
                                <p class="stat-label">System Users</p>
                            </div>
                        </div>
                        <div class="live-indicator" style="background: rgba(16, 185, 129, 0.08); color: #10b981; border-color: rgba(16, 185, 129, 0.15);">
                            <span class="live-dot" style="background: #10b981;"></span>
                            <span>Active</span>
                        </div>
                    </div>
                    <div class="stat-sublabel text-info mt-auto pt-2 border-top" style="border-top: 1px solid rgba(0,0,0,0.05) !important;">
                        <i class="bi bi-person-check"></i>
                        <span class="fw-bold">Registered Accounts</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-solid shadow-sm" style="background: linear-gradient(135deg, #f59e0b, #d97706); width: 48px; height: 48px; border-radius: 12px;">
                                <i class="bi bi-boxes fs-4"></i>
                            </div>
                            <div>
                                <h2 class="stat-number count-up mb-0" style="font-size: 1.8rem;" data-target="<?php echo $totalInventory; ?>">0</h2>
                                <p class="stat-label">Equipment Items</p>
                            </div>
                        </div>
                        <div class="live-indicator" style="background: rgba(16, 185, 129, 0.08); color: #10b981; border-color: rgba(16, 185, 129, 0.15);">
                            <span class="live-dot" style="background: #10b981;"></span>
                            <span>Stock</span>
                        </div>
                    </div>
                    <div class="stat-sublabel text-warning mt-auto pt-2 border-top" style="border-top: 1px solid rgba(0,0,0,0.05) !important;">
                        <i class="bi bi-database"></i>
                        <span class="fw-bold">Total Inventory</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 g-4 animate-in delay-2">
            <div class="col-md-4 col-lg-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="icon-box-solid icon-box-primary shadow-sm" style="width: 48px; height: 48px; border-radius: 12px;">
                            <i class="bi bi-laptop fs-4"></i>
                        </div>
                        <div>
                            <h2 class="stat-number count-up mb-0" style="font-size: 1.8rem;" data-target="<?php echo $totalLaptopDesktop; ?>">0</h2>
                            <p class="stat-label">Laptop / Desktop</p>
                        </div>
                    </div>
                    <div class="stat-sublabel text-primary mt-auto pt-2 border-top" style="border-top: 1px solid rgba(0,0,0,0.05) !important;">
                        <i class="bi bi-pc-display"></i>
                        <span class="fw-bold">Total Computers</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="icon-box-solid icon-box-success shadow-sm" style="width: 48px; height: 48px; border-radius: 12px;">
                            <i class="bi bi-printer fs-4"></i>
                        </div>
                        <div>
                            <h2 class="stat-number count-up mb-0" style="font-size: 1.8rem;" data-target="<?php echo $totalPrinters; ?>">0</h2>
                            <p class="stat-label">Printers</p>
                        </div>
                    </div>
                    <div class="stat-sublabel text-success mt-auto pt-2 border-top" style="border-top: 1px solid rgba(0,0,0,0.05) !important;">
                        <i class="bi bi-printer-fill"></i>
                        <span class="fw-bold">Total Printers</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="icon-box-solid icon-box-danger shadow-sm" style="width: 48px; height: 48px; border-radius: 12px;">
                            <i class="bi bi-router fs-4"></i>
                        </div>
                        <div>
                            <h2 class="stat-number count-up mb-0" style="font-size: 1.8rem;" data-target="<?php echo $totalNetworking; ?>">0</h2>
                            <p class="stat-label">Networking</p>
                        </div>
                    </div>
                    <div class="stat-sublabel text-danger mt-auto pt-2 border-top" style="border-top: 1px solid rgba(0,0,0,0.05) !important;">
                        <i class="bi bi-hdd-network"></i>
                        <span class="fw-bold">Network Devices</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS SECTION: Distribution with FIXED Animation -->
        <div class="row mb-4 g-4 animate-in delay-3">
            <!-- Computer Distribution -->
            <div class="col-md-6 pie-chart-container">
                <div class="chart-modern pie-chart-modern" data-chart-type="pie">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">Computer Distribution</h5>
                            <p class="text-muted small mb-0">Desktops vs Laptops</p>
                        </div>
                        <div class="live-indicator">
                            <span class="live-dot"></span>
                            <span>LIVE DATA</span>
                        </div>
                    </div>
                    <div class="row align-items-center mb-4">
                        <div class="col-7 pie-canvas-wrapper">
                            <div style="height: 220px;">
                                <canvas id="computerDistributionChart" class="pie-chart-canvas"></canvas>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="d-flex flex-column gap-3">
                                <div class="mb-2">
                                    <div class="total-badge-modern mb-2">
                                        <i class="bi bi-display"></i>
                                        <span>TOTAL: <span class="count-up" data-target="<?php echo $compTotal; ?>">0</span></span>
                                    </div>
                                </div>
                                <div class="stat-number-bounce" style="animation-delay: 0.3s;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #4f46e5;"></span>
                                        <span class="text-muted small fw-medium">Desktops</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fs-4 fw-bold count-up" data-target="<?php echo $equipmentCounts['Desktop']; ?>">0</span>
                                        <span class="text-primary small fw-bold"><?php echo $desktopPct; ?>%</span>
                                    </div>
                                </div>
                                <div class="stat-number-bounce" style="animation-delay: 0.5s;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #10b981;"></span>
                                        <span class="text-muted small fw-medium">Laptops</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fs-4 fw-bold count-up" data-target="<?php echo $equipmentCounts['Laptop']; ?>">0</span>
                                        <span class="text-success small fw-bold"><?php echo $laptopPct; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-light rounded-3 p-3 d-flex gap-3 align-items-center" style="background-color: #f8f9fa!important; border-left: 4px solid #4f46e5;">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-info-circle-fill text-white"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 fs-6 text-primary">Insights</h6>
                            <p class="text-muted small mb-0">
                                <?php 
                                $desktopCount = $equipmentCounts['Desktop'];
                                $laptopCount = $equipmentCounts['Laptop'];

                                if ($desktopCount > $laptopCount): 
                                    echo "The <strong>Desktops</strong> has the highest record with <strong>$desktopCount</strong> units. Laptops currently have $laptopCount units recorded.";
                                elseif ($laptopCount > $desktopCount):
                                    echo "The <strong>Laptops</strong> has the highest record with <strong>$laptopCount</strong> units. Desktops currently have $desktopCount units recorded.";
                                elseif ($compTotal > 0):
                                    echo "Both <strong>Desktops and Laptops</strong> have the same record with <strong>$desktopCount</strong> units each.";
                                else:
                                    echo "No computer equipment recorded for this year.";
                                endif;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Network Distribution -->
            <div class="col-md-6 pie-chart-container">
                <div class="chart-modern pie-chart-modern" data-chart-type="pie">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">Network Distribution</h5>
                            <p class="text-muted small mb-0">Switch Hubs, Routers & Modems</p>
                        </div>
                        <div class="live-indicator">
                            <span class="live-dot"></span>
                            <span>LIVE DATA</span>
                        </div>
                    </div>
                    <div class="row align-items-center mb-4">
                        <div class="col-7 pie-canvas-wrapper">
                            <div style="height: 220px;">
                                <canvas id="networkDistributionChart" class="pie-chart-canvas"></canvas>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="d-flex flex-column gap-3">
                                <div class="mb-2">
                                    <div class="total-badge-modern mb-2">
                                        <i class="bi bi-diagram-3"></i>
                                        <span>TOTAL: <span class="count-up" data-target="<?php echo $netTotal; ?>">0</span></span>
                                    </div>
                                </div>
                                <div class="stat-number-bounce" style="animation-delay: 0.3s;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #f59e0b;"></span>
                                        <span class="text-muted small fw-medium">Switch Hubs</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fs-4 fw-bold count-up" data-target="<?php echo $equipmentCounts['Switch Hubs']; ?>">0</span>
                                        <span class="text-warning small fw-bold"><?php echo $switchPct; ?>%</span>
                                    </div>
                                </div>
                                <div class="stat-number-bounce" style="animation-delay: 0.5s;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #06b6d4;"></span>
                                        <span class="text-muted small fw-medium">Routers</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fs-4 fw-bold count-up" data-target="<?php echo $equipmentCounts['Routers']; ?>">0</span>
                                        <span class="text-info small fw-bold"><?php echo $routerPct; ?>%</span>
                                    </div>
                                </div>
                                <div class="stat-number-bounce" style="animation-delay: 0.7s;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #8b5cf6;"></span>
                                        <span class="text-muted small fw-medium">Modems</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fs-4 fw-bold count-up" data-target="<?php echo $equipmentCounts['Modem']; ?>">0</span>
                                        <span class="small fw-bold" style="color: #8b5cf6;"><?php echo $modemPct; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-light rounded-3 p-3 d-flex gap-3 align-items-center" style="background-color: #f8f9fa!important; border-left: 4px solid #4f46e5;">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-info-circle-fill text-white"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 fs-6 text-primary">Insights</h6>
                            <p class="text-muted small mb-0">
                                <?php 
                                $netCounts = [
                                    'Switch Hubs' => $equipmentCounts['Switch Hubs'],
                                    'Routers' => $equipmentCounts['Routers'],
                                    'Modems' => $equipmentCounts['Modem']
                                ];
                                arsort($netCounts);
                                $highestNet = key($netCounts);
                                $highestNetValue = current($netCounts);

                                if ($netTotal > 0): 
                                    echo "The <strong>$highestNet</strong> has the highest record with <strong>$highestNetValue</strong> units.";
                                else:
                                    echo "No network equipment recorded for this year.";
                                endif;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS SECTION: Part 2 -->
        <div class="row mb-4 g-4 animate-in delay-4">
            <!-- Printer Distribution -->
            <div class="col-md-6 pie-chart-container">
                <div class="chart-modern pie-chart-modern" data-chart-type="pie">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">Printer Distribution</h5>
                            <p class="text-muted small mb-0">Inkjet, Deskjet & Dot Matrix</p>
                        </div>
                        <div class="live-indicator">
                            <span class="live-dot"></span>
                            <span>LIVE DATA</span>
                        </div>
                    </div>
                    <div class="row align-items-center mb-4">
                        <div class="col-7 pie-canvas-wrapper">
                            <div style="height: 220px;">
                                <canvas id="printerDistributionChart" class="pie-chart-canvas"></canvas>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="d-flex flex-column gap-3">
                                <div class="mb-2">
                                    <div class="total-badge-modern mb-2">
                                        <i class="bi bi-printer"></i>
                                        <span>TOTAL: <span class="count-up" data-target="<?php echo $printerTotal; ?>">0</span></span>
                                    </div>
                                </div>
                                <div class="stat-number-bounce" style="animation-delay: 0.3s;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #ef4444;"></span>
                                        <span class="text-muted small fw-medium">Inkjet</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fs-4 fw-bold count-up" data-target="<?php echo $equipmentCounts['Inkjet']; ?>">0</span>
                                        <span class="text-danger small fw-bold"><?php echo $inkjetPct; ?>%</span>
                                    </div>
                                </div>
                                <div class="stat-number-bounce" style="animation-delay: 0.5s;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #3b82f6;"></span>
                                        <span class="text-muted small fw-medium">Deskjet</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fs-4 fw-bold count-up" data-target="<?php echo $equipmentCounts['Deskjet']; ?>">0</span>
                                        <span class="text-primary small fw-bold"><?php echo $deskjetPct; ?>%</span>
                                    </div>
                                </div>
                                <div class="stat-number-bounce" style="animation-delay: 0.7s;">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #10b981;"></span>
                                        <span class="text-muted small fw-medium">Dot Matrix</span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="fs-4 fw-bold count-up" data-target="<?php echo $equipmentCounts['Dot Matrix']; ?>">0</span>
                                        <span class="text-success small fw-bold"><?php echo $dotMatrixPct; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-light rounded-3 p-3 d-flex gap-3 align-items-center" style="background-color: #f8f9fa!important; border-left: 4px solid #4f46e5;">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-info-circle-fill text-white"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 fs-6 text-primary">Insights</h6>
                            <p class="text-muted small mb-0">
                                <?php 
                                $printerCounts = [
                                    'Inkjet' => $equipmentCounts['Inkjet'],
                                    'Deskjet' => $equipmentCounts['Deskjet'],
                                    'Dot Matrix' => $equipmentCounts['Dot Matrix']
                                ];
                                arsort($printerCounts);
                                $highestPrinter = key($printerCounts);
                                $highestPrinterValue = current($printerCounts);

                                if ($printerTotal > 0): 
                                    echo "The <strong>$highestPrinter</strong> has the highest record with <strong>$highestPrinterValue</strong> units.";
                                else:
                                    echo "No printer equipment recorded for this year.";
                                endif;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Office Submission Tracker -->
            <div class="col-md-6">
                <div class="chart-modern h-100 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-solid icon-box-primary shadow-sm" style="width: 42px; height: 42px; border-radius: 12px;">
                                <i class="bi bi-building-check fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Office Submission Tracker</h5>
                                <p class="text-muted small mb-0">Monitoring Year <?php echo $selectedYear; ?></p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 small fw-bold">
                                <i class="bi bi-check-circle-fill me-1"></i> <?php echo count($submittedOffices); ?> Done
                            </span>
                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 small fw-bold">
                                <i class="bi bi-clock-fill me-1"></i> <?php echo count($nonSubmittedOffices); ?> Pending
                            </span>
                        </div>
                    </div>
                    
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-4 text-center border-end">
                            <div class="compliance-donut-container mb-2">
                                <div class="compliance-percentage-center">
                                    <span class="display-6 fw-bold text-primary"><?php echo $complianceRate; ?>%</span>
                                    <div class="small text-muted fw-bold" style="font-size: 0.6rem;">OVERALL</div>
                                </div>
                                <canvas id="complianceDonutChart" class="pie-chart-canvas"></canvas>
                            </div>
                            <p class="small text-muted mb-0 px-2 mt-2">Overall compliance of all offices for the fiscal year.</p>
                        </div>

                        <div class="col-lg-8">
                            <div class="office-search-wrapper">
                                <i class="bi bi-search office-search-icon"></i>
                                <input type="text" id="officeSearch" class="office-search-input" placeholder="Search office name..." onkeyup="filterOffices()">
                            </div>

                            <div id="completedSection" class="mb-3">
                                <div class="status-section-header" id="submittedHeader">
                                    <i class="bi bi-check2-all text-success"></i> COMPLETED
                                </div>
                                <div class="office-grid" id="submittedGrid">
                                    <?php if (empty($submittedOffices)): ?>
                                        <p class="small text-muted italic p-2 no-results-msg">No offices have submitted yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($submittedOffices as $office): ?>
                                            <div class="office-status-pill submitted" data-name="<?php echo strtolower(htmlspecialchars($office)); ?>">
                                                <i class="bi bi-check-circle-fill"></i>
                                                <span class="text-truncate"><?php echo htmlspecialchars($office); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="submission-lists px-2" style="max-height: 180px; overflow-y: auto;" id="officeSubmissionList">
                                <div class="status-section-header" id="pendingHeader">
                                    <i class="bi bi-hourglass-split text-warning"></i> PENDING SUBMISSION
                                </div>
                                <div class="office-grid" id="pendingGrid">
                                    <?php if (empty($nonSubmittedOffices)): ?>
                                        <div class="col-12 text-center py-3 bg-light rounded-3 no-results-msg">
                                            <i class="bi bi-trophy text-primary fs-3"></i>
                                            <p class="small text-muted fw-bold mt-2 mb-0">Outstanding! All offices are compliant.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($nonSubmittedOffices as $office): ?>
                                            <div class="office-status-pill pending" data-name="<?php echo strtolower(htmlspecialchars($office)); ?>">
                                                <div class="pending-dot"></div>
                                                <span class="text-truncate"><?php echo htmlspecialchars($office); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div id="noResults" class="text-center py-4 d-none">
                                    <i class="bi bi-search text-muted display-6 mb-2"></i>
                                    <p class="text-muted small fw-bold">No matching offices found</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>





        <!-- SYSTEMS MINI TABLE SECTION -->
        <div class="row mb-4 animate-in delay-5">
            <div class="col-12">
                <div class="glass-card-modern p-0">
                    <div class="p-4 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-solid icon-box-primary shadow-sm" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; width: 52px; height: 52px; border-radius: 14px;">
                                    <i class="bi bi-cpu fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1" id="systemsTitle" style="font-size: 1.25rem;">Existing Systems</h5>
                                    <p class="text-muted small mb-0">Latest Submissions</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button id="toggleSystemsBtn" class="btn-modern px-4 d-flex align-items-center gap-2" onclick="toggleSystems(true)">
                                    <i class="bi bi-file-earmark-plus"></i> PROPOSED
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="existingSystemsTable" class="table-responsive">
                        <table id="existingSystemsDataTable" class="data-table-modern">
                            <thead>
                                <tr><th>OFFICE</th><th>SYSTEM</th><th>DATE</th><th style="width: 50px;"></th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $systemsQuery = safeQuery($conn, "
                                    SELECT f.office_name, f.date_submitted, 
                                           s.system_1, s.system_2, s.system_3, s.system_4, s.system_5,
                                           s.system_6, s.system_7, s.system_8, s.system_9, s.system_10
                                    FROM form f
                                    JOIN systems s ON f.id = s.form_id
                                    WHERE (s.system_1 != '' OR s.system_2 != '' OR s.system_3 != '' OR s.system_4 != '' OR s.system_5 != ''
                                       OR s.system_6 != '' OR s.system_7 != '' OR s.system_8 != '' OR s.system_9 != '' OR s.system_10 != '')
                                    AND YEAR(f.date_submitted) = $selectedYear
                                    ORDER BY f.office_name ASC, f.date_submitted DESC
                                ");
                                
                                $groupedSystems = [];
                                if ($systemsQuery && $systemsQuery->num_rows > 0) {
                                    while($row = $systemsQuery->fetch_assoc()) {
                                        $office = $row['office_name'];
                                        if (!isset($groupedSystems[$office])) {
                                            $groupedSystems[$office] = [
                                                'office' => $office,
                                                'systems' => [],
                                                'date' => $row['date_submitted']
                                            ];
                                        }
                                        for($i=1; $i<=10; $i++) {
                                            if(!empty($row["system_$i"])) {
                                                $groupedSystems[$office]['systems'][] = $row["system_$i"];
                                            }
                                        }
                                    }
                                }
                                
                                if (count($groupedSystems) > 0): 
                                    foreach($groupedSystems as $officeGroup): 
                                        $officeInitials = strtoupper(substr($officeGroup['office'], 0, 3));
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="office-icon-container">
                                                <i class="bi bi-building"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($officeGroup['office']); ?></div>
                                                <div class="text-muted small fw-medium" style="font-size: 0.65rem;"><?php echo $officeInitials; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach($officeGroup['systems'] as $sysName): ?>
                                                <span class="system-badge-modern">
                                                    <i class="bi bi-display"></i>
                                                    <?php echo htmlspecialchars($sysName); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 text-muted fw-semibold">
                                            <i class="bi bi-calendar3"></i>
                                            <span><?php echo date('M d, Y', strtotime($officeGroup['date'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-muted p-0">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="proposedSystemsTable" class="table-responsive d-none">
                        <table id="proposedSystemsDataTable" class="data-table-modern">
                            <thead><tr><th>OFFICE</th><th>PROPOSED SYSTEM</th><th>DATE</th><th style="width: 50px;"></th></tr></thead>
                            <tbody>
                                <?php 
                                $propQuery = safeQuery($conn, "
                                    SELECT f.office_name, f.date_submitted, 
                                           s.proposed_system_1, s.proposed_system_2, s.proposed_system_3, s.proposed_system_4, s.proposed_system_5,
                                           s.proposed_system_6, s.proposed_system_7, s.proposed_system_8, s.proposed_system_9, s.proposed_system_10
                                    FROM form f
                                    JOIN systems s ON f.id = s.form_id
                                    WHERE (s.proposed_system_1 != '' OR s.proposed_system_2 != '' OR s.proposed_system_3 != '' OR s.proposed_system_4 != '' OR s.proposed_system_5 != ''
                                       OR s.proposed_system_6 != '' OR s.proposed_system_7 != '' OR s.proposed_system_8 != '' OR s.proposed_system_9 != '' OR s.proposed_system_10 != '')
                                    AND YEAR(f.date_submitted) = $selectedYear
                                    ORDER BY f.office_name ASC, f.date_submitted DESC
                                ");
                                
                                $groupedProposed = [];
                                if ($propQuery && $propQuery->num_rows > 0) {
                                    while($row = $propQuery->fetch_assoc()) {
                                        $office = $row['office_name'];
                                        if (!isset($groupedProposed[$office])) {
                                            $groupedProposed[$office] = [
                                                'office' => $office,
                                                'systems' => [],
                                                'date' => $row['date_submitted']
                                            ];
                                        }
                                        for($i=1; $i<=10; $i++) {
                                            if(!empty($row["proposed_system_$i"])) {
                                                $groupedProposed[$office]['systems'][] = $row["proposed_system_$i"];
                                            }
                                        }
                                    }
                                }
                                
                                if (count($groupedProposed) > 0): 
                                    foreach($groupedProposed as $officeGroup): 
                                        $officeInitials = strtoupper(substr($officeGroup['office'], 0, 3));
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="office-icon-container" style="background: linear-gradient(135deg, #10b981, #059669);">
                                                <i class="bi bi-building"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($officeGroup['office']); ?></div>
                                                <div class="text-muted small fw-medium" style="font-size: 0.65rem;"><?php echo $officeInitials; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach($officeGroup['systems'] as $sysName): ?>
                                                <span class="proposed-badge-modern">
                                                    <i class="bi bi-stars"></i>
                                                    <?php echo htmlspecialchars($sysName); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 text-muted fw-semibold">
                                            <i class="bi bi-calendar3"></i>
                                            <span><?php echo date('M d, Y', strtotime($officeGroup['date'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-muted p-0">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT SUBMISSIONS & ACTION ITEMS SECTION -->
        <div class="row mb-4 g-4 animate-in delay-5">
            <!-- Maintenance Alerts Summary -->
            <div class="col-md-6">
                <div class="glass-card-modern h-100 p-4" style="border-top: 5px solid #ef4444;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-solid bg-danger shadow-sm" style="width: 40px; height: 40px; border-radius: 10px;">
                                <i class="bi bi-exclamation-triangle-fill text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-0">Action Items</h5>
                        </div>
                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill">Urgent</span>
                    </div>
                    
                    <div class="maintenance-list pe-2" style="max-height: 400px; overflow-y: auto;">
                        <div class="status-section-header mb-3">EQUIPMENT MAINTENANCE</div>
                        <div class="row g-3">
                            <?php if (empty($maintenanceAlerts)): ?>
                                <div class="col-12 text-center py-5">
                                    <i class="bi bi-shield-check text-success display-4 mb-3"></i>
                                    <p class="text-muted fw-bold">System Health: Optimal</p>
                                    <p class="small text-muted">No equipment requires immediate attention.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($maintenanceAlerts as $alert): ?>
                                    <div class="col-12">
                                        <div class="office-item p-3 rounded-4 mb-0 bg-light border-0 transition-all shadow-sm">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="fw-bold mb-0 text-dark small"><?php echo htmlspecialchars($alert['item']); ?></h6>
                                                <span class="badge bg-warning bg-opacity-25 text-warning-emphasis rounded-pill" style="font-size: 0.6rem;">
                                                    <?php echo (date('Y') - date('Y', strtotime($alert['date_submitted']))); ?> yrs old
                                                </span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 text-muted mb-3" style="font-size: 0.7rem;">
                                                <i class="bi bi-building"></i>
                                                <span><?php echo htmlspecialchars($alert['office_name']); ?></span>
                                            </div>
                                            <button class="btn btn-sm btn-primary rounded-pill w-100 fw-bold" style="font-size: 0.7rem;">
                                                <i class="bi bi-tools me-1"></i> VIEW
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT RMAPS SUBMISSIONS TABLE (Smaller Version) -->
            <div class="col-md-6">
                <div class="glass-card-modern h-100 p-0">
                    <div class="p-4 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box-solid icon-box-primary shadow-sm" style="width: 42px; height: 42px; border-radius: 12px;">
                                    <i class="bi bi-file-earmark-text-fill fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0" style="font-size: 1.1rem;">Recent RMAPS</h5>
                                    <p class="text-muted small mb-0" style="font-size: 0.7rem;">Latest Submissions</p>
                                </div>
                            </div>
                            <a href="inventory.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" style="font-size: 0.65rem;">
                                VIEW ALL
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="formsTable" class="data-table-modern">
                            <thead>
                                <tr><th class="small">OFFICE</th><th class="small">EQUIPMENT</th><th class="small">UNIT</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($recentForms && $recentForms->num_rows > 0): ?>
                                    <?php while($row = $recentForms->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark small"><?php echo htmlspecialchars($row['office_name']); ?></div>
                                            <div class="text-muted" style="font-size: 0.6rem;"><?php echo date('M d, Y', strtotime($row['date_submitted'])); ?></div>
                                        </td>
                                        <td><span class="small fw-medium"><?php echo htmlspecialchars($row['items']); ?></span></td>
                                        <td class="text-center"><span class="badge bg-light text-dark rounded-pill" style="font-size: 0.6rem;"><?php echo htmlspecialchars($row['total_units']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- YEARLY COMPUTER COMPARISON -->
        <div class="row mb-4 g-4 animate-in delay-5">
            <div class="col-md-12">
                <div class="chart-modern floating-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-solid icon-box-primary">
                                <i class="bi bi-bar-chart-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Yearly Computer Comparison</h5>
                                <p class="text-muted small mb-0">Inventory growth and distribution over time</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="btn btn-primary btn-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2">
                                <i class="bi bi-calendar-range"></i> <?php echo $startYear; ?> - <?php echo $endYear; ?> <i class="bi bi-chevron-down ms-1" style="font-size: 10px;"></i>
                            </div>
                            <button class="btn btn-light btn-sm rounded-circle shadow-sm" onclick="refreshTable()" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-primary">
                                    <i class="bi bi-pc-display fs-4"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-0">Total Devices (<?php echo $startYear; ?> - <?php echo $endYear; ?>)</p>
                                    <div class="d-flex align-items-baseline gap-1">
                                        <h3 class="fw-bold mb-0 count-up" data-target="<?php echo $totalYearlyDevices; ?>">0</h3>
                                        <span class="text-muted small">Devices</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-success">
                                    <i class="bi bi-graph-up-arrow fs-4"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-0">Average Per Year</p>
                                    <div class="d-flex align-items-baseline gap-1">
                                        <h3 class="fw-bold mb-0 count-up" data-target="<?php echo $averagePerYear; ?>">0</h3>
                                        <span class="text-muted small">Devices</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-warning">
                                    <i class="bi bi-arrow-up fs-4"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-0">Highest Year</p>
                                    <div class="d-flex align-items-baseline gap-1">
                                        <h3 class="fw-bold mb-0 count-up" data-target="<?php echo $highestYearValue; ?>">0</h3>
                                        <span class="text-muted small">Devices (<?php echo $highestYear; ?>)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-info">
                                    <i class="bi bi-graph-up fs-4"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-0">Growth Trend</p>
                                    <div class="d-flex align-items-baseline gap-1">
                                        <h3 class="fw-bold mb-0 <?php echo $growthStatus == 'positive' ? 'text-success' : 'text-danger'; ?>"><?php echo $growthText; ?></h3>
                                    </div>
                                    <span class="text-muted small" style="font-size: 0.75rem;">vs last year</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <p class="text-muted small mb-0">Number of Devices</p>
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-1" style="width: 12px; height: 12px; background-color: #6366f1;"></span>
                            <span class="text-muted small fw-medium">Total Devices</span>
                        </div>
                    </div>

                    <div style="height: 350px;" class="mb-4">
                        <canvas id="computerYearChart"></canvas>
                    </div>
                    
                    <div class="bg-light rounded-3 p-3 d-flex justify-content-between align-items-center" style="background-color: #f8f9fa!important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-solid icon-box-primary" style="width: 40px; height: 40px; min-width: 40px;">
                                <i class="bi bi-graph-up fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 fs-6" style="color: #1e293b;">Steady growth with <?php echo $growthStatus; ?> trajectory</h6>
                                <p class="text-muted small mb-0">Inventory <?php echo $growthDirText; ?> by <?php echo $growthDiff; ?> devices (<?php echo abs($growthPercent); ?>%) from <?php echo $prevYearLabel; ?> to <?php echo $lastYearLabel; ?>.</p>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill d-flex align-items-center gap-1">
                                <i class="bi bi-arrow-up-right"></i> On Track
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yearly Equipment Activity Rating per Office -->
        <div class="row mb-4 g-4 animate-in delay-5">
            <div class="col-md-12">
                <div class="chart-modern floating-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-solid icon-box-primary">
                                <i class="bi bi-building-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Yearly Equipment Activity Rating per Office</h5>
                                <p class="text-muted small mb-0">Total Laptops & Desktops by Office</p>
                            </div>
                        </div>
                        <div>
                            <div class="border rounded px-3 py-2 d-flex align-items-center gap-2 bg-white">
                                <i class="bi bi-calendar text-muted"></i>
                                <div class="d-flex flex-column" style="line-height: 1;">
                                    <span class="text-muted" style="font-size: 10px;">Year</span>
                                    <span class="fw-bold" style="font-size: 14px;"><?php echo date('Y'); ?></span>
                                </div>
                                <i class="bi bi-chevron-down text-muted ms-2" style="font-size: 12px;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-primary">
                                    <i class="bi bi-laptop fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-0 count-up" data-target="<?php echo $highestActivityValue; ?>">0</h3>
                                    <p class="text-muted small mb-0" style="line-height: 1.2;">Highest Activity</p>
                                    <span class="text-primary small fw-bold" style="font-size: 11px;"><?php echo htmlspecialchars($highestActivityOffice); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-success">
                                    <i class="bi bi-building fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-0 count-up" data-target="<?php echo $activeOfficesCount; ?>">0</h3>
                                    <p class="text-muted small mb-0" style="line-height: 1.2;">Active Offices</p>
                                    <span class="text-success small" style="font-size: 11px;">with equipment</span>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-warning">
                                    <i class="bi bi-display fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-0 count-up" data-target="<?php echo $totalEquipmentAllOffices; ?>">0</h3>
                                    <p class="text-muted small mb-0" style="line-height: 1.2;">Total Equipment</p>
                                    <span class="text-warning small" style="font-size: 11px;">across all offices</span>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-info">
                                    <i class="bi bi-graph-up fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-0 count-up" data-target="<?php echo $averagePerOffice; ?>">0</h3>
                                    <p class="text-muted small mb-0" style="line-height: 1.2;">Average per Office</p>
                                    <span class="text-info small" style="font-size: 11px;">equipment</span>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded-3 p-3 d-flex align-items-center gap-3 h-100 hover-lift">
                                <div class="icon-box-solid icon-box-danger">
                                    <i class="bi bi-pc-display fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-0 count-up" data-target="<?php echo $medianPerOffice; ?>">0</h3>
                                    <p class="text-muted small mb-0" style="line-height: 1.2;">Median per Office</p>
                                    <span class="text-danger small" style="font-size: 11px;">equipment</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h6 class="fw-bold mb-0">Equipment Activity (Total Laptops & Desktops)</h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-pill" style="width: 16px; height: 4px; background-color: #6366f1;"></span>
                            <span class="text-muted small fw-medium">Total Equipment</span>
                        </div>
                    </div>

                    <div style="height: 350px;" class="mb-4">
                        <canvas id="activityChart"></canvas>
                    </div>

                    <div class="bg-light border rounded-3 p-3 d-flex gap-3 align-items-center" style="background-color: #f8f9fa!important;">
                        <div class="icon-box-solid icon-box-primary" style="width: 40px; height: 40px; min-width: 40px; border-radius: 50%;">
                            <i class="bi bi-info-circle fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 fs-6 text-primary">Insights</h6>
                            <p class="text-muted small mb-0">The <strong><?php echo htmlspecialchars($highestActivityOffice); ?></strong> has the highest equipment activity with <strong><?php echo $highestActivityValue; ?></strong> devices. <?php echo $activeOfficesCount > 1 ? ($activeOfficesCount - 1) . ' other offices also have devices recorded.' : 'All other offices currently have 0 devices recorded.'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#formsTable').DataTable({
        responsive: true,
        pageLength: 5,
        ordering: true,
        searching: false,
        lengthChange: false,
        language: {
            paginate: {
                previous: '<i class="bi bi-chevron-left"></i>',
                next: '<i class="bi bi-chevron-right"></i>'
            }
        }
    });

    $('#existingSystemsDataTable').DataTable({
        responsive: true,
        pageLength: 5,
        ordering: true,
        searching: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search systems...",
            lengthMenu: "Show _MENU_ entries",
            paginate: {
                previous: '<i class="bi bi-chevron-left"></i>',
                next: '<i class="bi bi-chevron-right"></i>'
            }
        }
    });

    $('#proposedSystemsDataTable').DataTable({
        responsive: true,
        pageLength: 5,
        ordering: true,
        searching: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search proposed...",
            lengthMenu: "Show _MENU_ entries",
            paginate: {
                previous: '<i class="bi bi-chevron-left"></i>',
                next: '<i class="bi bi-chevron-right"></i>'
            }
        }
    });
});

function refreshTable() {
    location.reload();
}

// Store chart instances for potential cleanup
let chartInstances = {
    computerDistribution: null,
    networkDistribution: null,
    printerDistribution: null,
    complianceDonut: null,
    computerYear: null,
    activity: null
};

// Function to animate charts when they become visible
const animateChartWhenVisible = (chartId, createChartFunction) => {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Stop observing after trigger
                obs.unobserve(entry.target);
                
                // Add forming animation class
                entry.target.classList.add('pie-chart-forming');
                
                // Create the chart
                createChartFunction();
                
                // After animation completes, add glow effect
                setTimeout(() => {
                    entry.target.classList.add('pie-chart-glow');
                }, 1200);
                
                // Remove forming class after animation to allow re-animation on next view
                setTimeout(() => {
                    entry.target.classList.remove('pie-chart-forming');
                }, 1500);
            }
        });
    }, { threshold: 0.2 });
    
    observer.observe(canvas);
};

// Chart Creation Functions
function createComputerDistributionChart() {
    const ctx = document.getElementById('computerDistributionChart').getContext('2d');
    if (chartInstances.computerDistribution) {
        chartInstances.computerDistribution.destroy();
    }
    const isDarkMode = document.documentElement.classList.contains('dark-mode');
    chartInstances.computerDistribution = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Desktop', 'Laptop'],
            datasets: [{
                data: [<?php echo $equipmentCounts['Desktop']; ?>, <?php echo $equipmentCounts['Laptop']; ?>],
                backgroundColor: [isDarkMode ? '#00f2ff' : '#4f46e5', isDarkMode ? '#bc13fe' : '#10b981'],
                borderWidth: 2,
                borderColor: isDarkMode ? '#1e293b' : '#ffffff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1000,
                easing: 'easeOutCubic'
            },
            plugins: {
                legend: { display: false },
                tooltip: { 
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1e293b',
                    bodyColor: '#1e293b',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    cornerRadius: 12,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let total = context.dataset.data.reduce((a,b) => a + b, 0);
                            let percent = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return `${context.label}: ${context.parsed} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });
}

function createNetworkDistributionChart() {
    const ctx = document.getElementById('networkDistributionChart').getContext('2d');
    if (chartInstances.networkDistribution) {
        chartInstances.networkDistribution.destroy();
    }
    chartInstances.networkDistribution = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Switch Hubs', 'Routers', 'Modems'],
            datasets: [{
                data: [<?php echo $equipmentCounts['Switch Hubs']; ?>, <?php echo $equipmentCounts['Routers']; ?>, <?php echo $equipmentCounts['Modem']; ?>],
                backgroundColor: ['#f59e0b', '#06b6d4', '#8b5cf6'],
                borderWidth: 2,
                borderColor: document.documentElement.classList.contains('dark-mode') ? '#1e293b' : '#ffffff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1000,
                easing: 'easeOutCubic'
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1e293b',
                    bodyColor: '#1e293b',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    cornerRadius: 12,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let total = context.dataset.data.reduce((a,b) => a + b, 0);
                            let percent = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return `${context.label}: ${context.parsed} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });
}

function createPrinterDistributionChart() {
    const ctx = document.getElementById('printerDistributionChart').getContext('2d');
    if (chartInstances.printerDistribution) {
        chartInstances.printerDistribution.destroy();
    }
    chartInstances.printerDistribution = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Inkjet', 'Deskjet', 'Dot Matrix'],
            datasets: [{
                data: [<?php echo $equipmentCounts['Inkjet']; ?>, <?php echo $equipmentCounts['Deskjet']; ?>, <?php echo $equipmentCounts['Dot Matrix']; ?>],
                backgroundColor: ['#ef4444', '#3b82f6', '#10b981'],
                borderWidth: 2,
                borderColor: document.documentElement.classList.contains('dark-mode') ? '#1e293b' : '#ffffff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1000,
                easing: 'easeOutCubic'
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1e293b',
                    bodyColor: '#1e293b',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    cornerRadius: 12,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let total = context.dataset.data.reduce((a,b) => a + b, 0);
                            let percent = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return `${context.label}: ${context.parsed} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });
}

function createComplianceDonutChart() {
    const ctx = document.getElementById('complianceDonutChart').getContext('2d');
    if (chartInstances.complianceDonut) {
        chartInstances.complianceDonut.destroy();
    }
    chartInstances.complianceDonut = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Submitted', 'Pending'],
            datasets: [{
                data: [<?php echo count($submittedOffices); ?>, <?php echo count($nonSubmittedOffices); ?>],
                backgroundColor: ['#10b981', '#f1f5f9'],
                hoverBackgroundColor: ['#059669', '#e2e8f0'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: { legend: { display: false } },
            animation: {
                animateScale: true,
                duration: 1000,
                easing: 'easeOutCubic'
            }
        }
    });
}

function createComputerYearChart() {
    const ctx = document.getElementById('computerYearChart').getContext('2d');
    if (chartInstances.computerYear) {
        chartInstances.computerYear.destroy();
    }
    const isDarkMode = document.documentElement.classList.contains('dark-mode');
    const textColor = isDarkMode ? '#94a3b8' : '#475569';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0,0,0,0.03)';
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, '#6366f1');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.4)');
    
    chartInstances.computerYear = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($yearLabels); ?>,
            datasets: [{
                label: 'Total Computers',
                data: <?php echo json_encode($computerData); ?>,
                backgroundColor: gradient,
                borderRadius: 12,
                barThickness: 60,
                order: 2,
                datalabels: {
                    display: true,
                    color: '#6366f1',
                    align: 'top',
                    anchor: 'end',
                    offset: 4,
                    font: { weight: 'bold', size: 14 },
                    formatter: (value) => value > 0 ? value : ''
                }
            },
            {
                type: 'line',
                label: 'Trend',
                data: <?php echo json_encode($computerData); ?>,
                borderColor: 'rgba(99, 102, 241, 0.8)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                order: 1,
                datalabels: { display: false }
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart',
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1e293b',
                    bodyColor: '#1e293b',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => `Total Devices: ${context.parsed.y}`
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    min: 0,
                    max: 100,
                    grid: { color: gridColor, drawBorder: false, borderDash: [5, 5] },
                    ticks: { font: { weight: '600' }, color: textColor }
                },
                x: { 
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { weight: '600' }, color: textColor }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function createActivityChart() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    if (chartInstances.activity) {
        chartInstances.activity.destroy();
    }
    const isDarkMode = document.documentElement.classList.contains('dark-mode');
    const textColor = isDarkMode ? '#94a3b8' : '#475569';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0,0,0,0.03)';
    const gradient = ctx.createLinearGradient(0, 0, 0, 350);
    gradient.addColorStop(0, 'rgba(139, 92, 246, 0.8)');
    gradient.addColorStop(1, 'rgba(139, 92, 246, 0.1)');
    
    chartInstances.activity = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($officeNames); ?>,
            datasets: [{
                label: 'Total Equipment',
                data: <?php echo json_encode($officeEquipmentTotals); ?>,
                backgroundColor: gradient,
                borderRadius: 8,
                barThickness: 24,
                datalabels: {
                    display: true,
                    color: isDarkMode ? '#c4b5fd' : '#4f46e5',
                    align: 'top',
                    anchor: 'end',
                    offset: 4,
                    font: { weight: 'bold', size: 11 },
                    formatter: (value) => value
                }
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeOutQuart',
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1e293b',
                    bodyColor: '#1e293b',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 12,
                    displayColors: false
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    min: 0,
                    max: 100,
                    grid: { color: gridColor, drawBorder: false, borderDash: [5, 5] }, 
                    ticks: { color: textColor, font: { weight: '600' } } 
                },
                x: { 
                    grid: { display: false, drawBorder: false }, 
                    ticks: { maxRotation: 45, minRotation: 45, font: { size: 10, weight: '600' }, color: textColor } 
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

// Initialize charts with intersection observer for pie charts
document.addEventListener('DOMContentLoaded', function() {
    // Use intersection observer for pie charts
    animateChartWhenVisible('computerDistributionChart', createComputerDistributionChart);
    animateChartWhenVisible('networkDistributionChart', createNetworkDistributionChart);
    animateChartWhenVisible('printerDistributionChart', createPrinterDistributionChart);
    animateChartWhenVisible('complianceDonutChart', createComplianceDonutChart);
    
    // For other charts, create immediately (or with observer as well)
    const yearChartCanvas = document.getElementById('computerYearChart');
    const activityChartCanvas = document.getElementById('activityChart');
    
    if (yearChartCanvas) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    createComputerYearChart();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        observer.observe(yearChartCanvas);
    }
    
    if (activityChartCanvas) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    createActivityChart();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        observer.observe(activityChartCanvas);
    }

    // Count Up Animation for numbers
    const countUpElements = document.querySelectorAll('.count-up');
    const animateCountUp = (el) => {
        const target = parseFloat(el.getAttribute('data-target'));
        let current = 0;
        const duration = 1500;
        const stepTime = 20;
        const totalSteps = duration / stepTime;
        const increment = target / totalSteps;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                el.innerText = target.toLocaleString();
                clearInterval(timer);
            } else {
                el.innerText = Math.floor(current).toLocaleString();
            }
        }, stepTime);
    };
    
    const countUpObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCountUp(entry.target);
                countUpObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    countUpElements.forEach(el => countUpObserver.observe(el));

    // Auto-scroll logic for systems tables
    const initAutoScroll = () => {
        const tables = [
            document.getElementById('existingSystemsTable'),
            document.getElementById('proposedSystemsTable')
        ];

        tables.forEach(table => {
            if (!table) return;

            let isHovered = false;
            table.addEventListener('mouseenter', () => isHovered = true);
            table.addEventListener('mouseleave', () => isHovered = false);

            const scroll = () => {
                 if (!table.classList.contains('d-none') && !isHovered) {
                     if (table.scrollHeight > table.clientHeight) {
                         if (Math.ceil(table.scrollTop + table.clientHeight) >= table.scrollHeight) {
                             if (!table.isResetting) {
                                 table.isResetting = true;
                                 setTimeout(() => {
                                     table.scrollTop = 0;
                                     table.isResetting = false;
                                 }, 1500);
                             }
                         } else {
                             table.scrollTop += 0.8;
                         }
                     }
                 }
                 requestAnimationFrame(scroll);
             };
            requestAnimationFrame(scroll);
        });
    };
    initAutoScroll();

    // Auto-toggle systems tables every 20 seconds
    window.systemsInterval = setInterval(toggleSystems, 20000);
});

function toggleSystems(manual = false) {
    const existingTable = document.getElementById('existingSystemsTable');
    const proposedTable = document.getElementById('proposedSystemsTable');
    const btn = document.getElementById('toggleSystemsBtn');
    const title = document.getElementById('systemsTitle');

    if (!existingTable || !proposedTable) return;

    if (manual) {
        clearInterval(window.systemsInterval);
        window.systemsInterval = setInterval(toggleSystems, 20000);
    }

    if (existingTable.classList.contains('d-none')) {
        existingTable.classList.remove('d-none');
        proposedTable.classList.add('d-none');
        btn.innerHTML = '<i class="bi bi-file-earmark-plus"></i> PROPOSED';
        btn.style.background = 'linear-gradient(135deg, var(--primary), var(--primary-light))';
        title.textContent = 'Existing Systems';
        $('#existingSystemsDataTable').DataTable().columns.adjust().responsive.recalc();
    } else {
        existingTable.classList.add('d-none');
        proposedTable.classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-file-earmark-check"></i> EXISTING';
        btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        title.textContent = 'Proposed Systems';
        $('#proposedSystemsDataTable').DataTable().columns.adjust().responsive.recalc();
    }
}

function filterOffices() {
    const input = document.getElementById('officeSearch');
    const filter = input.value.toLowerCase();
    const list = document.getElementById('officeSubmissionList');
    const completedSection = document.getElementById('completedSection');
    const items = document.querySelectorAll('.office-status-pill');
    const noResults = document.getElementById('noResults');
    const submittedHeader = document.getElementById('submittedHeader');
    const pendingHeader = document.getElementById('pendingHeader');
    const submittedGrid = document.getElementById('submittedGrid');
    const pendingGrid = document.getElementById('pendingGrid');
    const emptyMsgs = document.querySelectorAll('.no-results-msg');

    let hasVisibleSubmitted = false;
    let hasVisiblePending = false;

    for (let i = 0; i < items.length; i++) {
        const name = items[i].getAttribute('data-name');
        if (name && name.includes(filter)) {
            items[i].style.display = 'flex';
            if (items[i].classList.contains('submitted')) hasVisibleSubmitted = true;
            if (items[i].classList.contains('pending')) hasVisiblePending = true;
        } else {
            items[i].style.display = 'none';
        }
    }

    const showSubmitted = (hasVisibleSubmitted || (filter === '' && submittedGrid.querySelector('.no-results-msg')));
    completedSection.style.display = showSubmitted ? 'block' : 'none';
    if (submittedHeader) submittedHeader.style.display = showSubmitted ? 'flex' : 'none';
    if (submittedGrid) submittedGrid.style.display = showSubmitted ? 'grid' : 'none';
    
    const showPending = (hasVisiblePending || (filter === '' && pendingGrid && pendingGrid.querySelector('.no-results-msg')));
    if (pendingHeader) pendingHeader.style.display = showPending ? 'flex' : 'none';
    if (pendingGrid) pendingGrid.style.display = showPending ? 'grid' : 'none';
    
    if (list) list.style.display = (showPending || filter !== '') ? 'block' : 'none';

    for (let msg of emptyMsgs) {
        if (msg) msg.style.display = (filter === '') ? 'block' : 'none';
    }

    if (!hasVisibleSubmitted && !hasVisiblePending && filter !== '' && noResults) {
        noResults.classList.remove('d-none');
        if (list) list.style.display = 'block';
    } else if (noResults) {
        noResults.classList.add('d-none');
    }
}
</script>

</body>
</html>