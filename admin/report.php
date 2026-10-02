<?php
include "../config.php";
include "../config/auth_check.php";

// Get spreadsheet filter values
$spreadsheet_start_date = isset($_GET['spreadsheet_start_date']) && !empty($_GET['spreadsheet_start_date']) ? mysqli_real_escape_string($conn, $_GET['spreadsheet_start_date']) : '';
$spreadsheet_end_date = isset($_GET['spreadsheet_end_date']) && !empty($_GET['spreadsheet_end_date']) ? mysqli_real_escape_string($conn, $_GET['spreadsheet_end_date']) : '';

// Get paper size preference from session or default to A4
if (!isset($_SESSION['paper_size'])) {
    $_SESSION['paper_size'] = 'A4';
}
$selected_paper_size = $_SESSION['paper_size'];

// Handle paper size change via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paper_size'])) {
    $_SESSION['paper_size'] = $_POST['paper_size'];
    $selected_paper_size = $_SESSION['paper_size'];
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Build date filter condition for spreadsheet summary
$spreadsheet_date_condition = "";
if ($spreadsheet_start_date && $spreadsheet_end_date) {
    $spreadsheet_date_condition = "AND f.date_submitted BETWEEN '$spreadsheet_start_date' AND '$spreadsheet_end_date'";
} elseif ($spreadsheet_start_date) {
    $spreadsheet_date_condition = "AND f.date_submitted >= '$spreadsheet_start_date'";
} elseif ($spreadsheet_end_date) {
    $spreadsheet_date_condition = "AND f.date_submitted <= '$spreadsheet_end_date'";
}

if (isset($_GET['year']) && !empty($_GET['year'])) {
    $year_filter = mysqli_real_escape_string($conn, $_GET['year']);
    $spreadsheet_date_condition .= " AND YEAR(f.date_submitted) = '$year_filter'";
}

// Fetch all form data grouped by office for spreadsheet view - FIXED for systems
$spreadsheetQuery = mysqli_query($conn, "
    SELECT 
        COALESCE(o.office_name, f.office_name) as office_name,
        f.date_submitted,
        ce.item,
        ce.number_of_units as units,
        ce.brand,
        ce.processor,
        ce.ram,
        ce.hdd,
        ce.ssd,
        COALESCE(ce.equipment_image, (SELECT equipment_image FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted AND uif.item = ce.item LIMIT 1)) as equipment_image,
        oie.inkjet_printer,
        oie.deskjet_printer,
        oie.dot_matrix_printer,
        oie.switch_hubs,
        oie.routers,
        oie.modem,
        -- Use MAX with GROUP BY to get systems data once per submission
        MAX(COALESCE(s.system_1, (SELECT system1 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) AS system_1,
        MAX(COALESCE(s.system_2, (SELECT system2 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) AS system_2,
        MAX(COALESCE(s.system_3, (SELECT system3 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) AS system_3,
        MAX(COALESCE(s.system_4, (SELECT system4 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) AS system_4,
        MAX(COALESCE(s.proposed_system_1, (SELECT proposed_system1 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) AS proposed_system_1,
        MAX(COALESCE(s.proposed_system_2, (SELECT proposed_system2 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) AS proposed_system_2,
        MAX(COALESCE(s.proposed_system_3, (SELECT proposed_system3 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) AS proposed_system_3,
        MAX(COALESCE(s.proposed_system_4, (SELECT proposed_system4 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) AS proposed_system_4,
        f.id as form_id
    FROM form f
    LEFT JOIN offices o ON f.office_name = o.office_name
    LEFT JOIN computer_equipment ce ON f.id = ce.form_id
    LEFT JOIN other_ict_equipment oie ON f.id = oie.form_id
    LEFT JOIN systems s ON f.id = s.form_id
    WHERE 1=1 $spreadsheet_date_condition
    GROUP BY f.id, ce.id, oie.id
    ORDER BY office_name ASC, f.date_submitted DESC, f.id DESC
");

$spreadsheetRecords = [];
if ($spreadsheetQuery) {
    while ($row = mysqli_fetch_assoc($spreadsheetQuery)) {
        $spreadsheetRecords[] = $row;
    }
}

// Calculate rowspan for grouping
$groupedSpreadsheetRecords = [];
$rowIndex = 0;
while ($rowIndex < count($spreadsheetRecords)) {
    $currentRow = $spreadsheetRecords[$rowIndex];
    $officeName = $currentRow['office_name'];
    $dateSubmitted = $currentRow['date_submitted'];
    $formId = $currentRow['form_id'];
    $rowspan = 1;

    for ($i = $rowIndex + 1; $i < count($spreadsheetRecords); $i++) {
        if ($spreadsheetRecords[$i]['office_name'] === $officeName && $spreadsheetRecords[$i]['date_submitted'] === $dateSubmitted && $spreadsheetRecords[$i]['form_id'] === $formId) {
            $rowspan++;
        } else {
            break;
        }
    }

    $currentRow['rowspan'] = $rowspan;
    $groupedSpreadsheetRecords[] = $currentRow;

    for ($i = 1; $i < $rowspan; $i++) {
        $nextRow = $spreadsheetRecords[$rowIndex + $i];
        $nextRow['grouped'] = true;
        $groupedSpreadsheetRecords[] = $nextRow;
    }

    $rowIndex += $rowspan;
}

// Original query for flat records
$base_query = "
    SELECT 
        o.office_name,
        f.date_submitted as date_submitted,
        ce.item,
        ce.number_of_units as units,
        ce.brand,
        ce.processor,
        ce.ram,
        ce.hdd,
        ce.ssd,
        MAX(COALESCE(s.system_1, (SELECT system1 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) as system1,
        MAX(COALESCE(s.system_2, (SELECT system2 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) as system2,
        MAX(COALESCE(s.system_3, (SELECT system3 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) as system3,
        MAX(COALESCE(s.system_4, (SELECT system4 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) as system4,
        MAX(COALESCE(s.proposed_system_1, (SELECT proposed_system1 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) as proposed_system1,
        MAX(COALESCE(s.proposed_system_2, (SELECT proposed_system2 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) as proposed_system2,
        MAX(COALESCE(s.proposed_system_3, (SELECT proposed_system3 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) as proposed_system3,
        MAX(COALESCE(s.proposed_system_4, (SELECT proposed_system4 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1))) as proposed_system4,
        MAX(IF(ic.is_centralized_isp, 'Yes', 'No')) AS isp_server,
        MAX(IF(ic.is_centralized_lan_wan, 'Yes', 'No')) AS lan_connection,
        MAX(IF(ic.is_centralized_db_server, 'Yes', 'No')) AS database_server,
        MAX(IF(ic.is_other_isp_via_office_plan, 'Yes', 'No')) AS other_isp,
        MAX(ic.isp_name) AS isp_name,
        MAX(ic.bandwidth) AS bandwidth,
        MAX(ic.other_internet_source) AS other_source,
        'N/A' AS pabx,
        'N/A' AS telephone_numbers,
        0 AS base_radios,
        0 AS handheld_personal,
        0 AS handheld_lgu,
        0 AS handheld_total,
        COALESCE(ce.equipment_image, (SELECT equipment_image FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted AND uif.item = ce.item LIMIT 1)) as equipment_image,
        MAX(oie.inkjet_printer) as inkjet_printer,
        MAX(oie.deskjet_printer) as deskjet_printer,
        MAX(oie.dot_matrix_printer) as dotmatrix_printer,
        MAX(oie.switch_hubs) as switch_hubs,
        MAX(oie.routers) as routers,
        MAX(oie.modem) as modem,
        f.id as id,
        'form' AS source
    FROM form f
    JOIN offices o ON f.office_name = o.office_name
    LEFT JOIN computer_equipment ce ON f.id = ce.form_id
    LEFT JOIN other_ict_equipment oie ON f.id = oie.form_id
    LEFT JOIN systems s ON f.id = s.form_id
    LEFT JOIN internet_connections ic ON f.id = ic.form_id
";

$filters = [];
if(isset($_GET['search']) && $_GET['search'] != ''){
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $filters[] = "o.office_name LIKE '%$search%'";
}

if(isset($_GET['year']) && $_GET['year'] != ''){
    $year = mysqli_real_escape_string($conn, $_GET['year']);
    $filters[] = "YEAR(f.date_submitted) = '$year'";
}

$base_filters = ['(ce.id IS NOT NULL OR oie.id IS NOT NULL OR s.id IS NOT NULL)'];
$all_filters = array_merge($base_filters, $filters);
$where_sql = ' WHERE ' . implode(' AND ', $all_filters);
$query = mysqli_query($conn, $base_query . $where_sql . " GROUP BY f.id, ce.id ORDER BY o.office_name ASC, f.id DESC");

$flat_records = [];
$grouped_records = [];

if(mysqli_num_rows($query) > 0){
    while($row = mysqli_fetch_assoc($query)){
        $flat_records[] = $row;
        
        $form_id = $row['id'];
        if(!isset($grouped_records[$form_id])){
            $grouped_records[$form_id] = $row;
            $grouped_records[$form_id]['equipment_items'] = [];
        }
        
        if(!empty($row['item'])){
            $grouped_records[$form_id]['equipment_items'][] = [
                'item' => $row['item'],
                'units' => $row['units'],
                'brand' => $row['brand'],
                'processor' => $row['processor'],
                'ram' => $row['ram'],
                'hdd' => $row['hdd'],
                'ssd' => $row['ssd'],
                'equipment_image' => $row['equipment_image']
            ];
        }
    }
}

// Fetch summary data for the new Summary View
$summary_date_condition = "";
if ($spreadsheet_start_date && $spreadsheet_end_date) {
    $summary_date_condition = "AND f.date_submitted BETWEEN '$spreadsheet_start_date' AND '$spreadsheet_end_date'";
} elseif ($spreadsheet_start_date) {
    $summary_date_condition = "AND f.date_submitted >= '$spreadsheet_start_date'";
} elseif ($spreadsheet_end_date) {
    $summary_date_condition = "AND f.date_submitted <= '$spreadsheet_end_date'";
}

if (isset($_GET['year']) && !empty($_GET['year'])) {
    $year_filter = mysqli_real_escape_string($conn, $_GET['year']);
    $summary_date_condition .= " AND YEAR(f.date_submitted) = '$year_filter'";
}

// Calculate Dashboard Stats
$stats_year_cond = (isset($_GET['year']) && !empty($_GET['year'])) ? " WHERE YEAR(date_submitted) = '" . mysqli_real_escape_string($conn, $_GET['year']) . "'" : "";
$totalReportsResult = mysqli_query($conn, "SELECT COUNT(DISTINCT id) as total FROM form $stats_year_cond");
$totalReports = mysqli_fetch_assoc($totalReportsResult)['total'];

$stats_year_cond_alt = (isset($_GET['year']) && !empty($_GET['year'])) ? " AND YEAR(date_submitted) = '" . mysqli_real_escape_string($conn, $_GET['year']) . "'" : "";
$completedResult = mysqli_query($conn, "SELECT COUNT(DISTINCT office_name, date_submitted) as total FROM user_issp_form WHERE form_status = 'Completed' $stats_year_cond_alt");
$completedReports = mysqli_fetch_assoc($completedResult)['total'] ?: 0;

$inProgressResult = mysqli_query($conn, "SELECT COUNT(DISTINCT office_name, date_submitted) as total FROM user_issp_form WHERE (form_status = 'Open' OR form_status = 'In Progress') $stats_year_cond_alt");
$inProgressReports = mysqli_fetch_assoc($inProgressResult)['total'] ?: 0;

// Overdue: Not completed and older than 30 days
$overdueResult = mysqli_query($conn, "SELECT COUNT(DISTINCT office_name, date_submitted) as total FROM user_issp_form WHERE form_status != 'Completed' AND date_submitted < DATE_SUB(CURDATE(), INTERVAL 30 DAY) $stats_year_cond_alt");
$overdueReports = mysqli_fetch_assoc($overdueResult)['total'] ?: 0;

// Get status for each grouped record
foreach ($grouped_records as $id => $record) {
    $statusQuery = mysqli_query($conn, "SELECT form_status FROM user_issp_form WHERE office_name = '" . mysqli_real_escape_string($conn, $record['office_name']) . "' AND date_submitted = '" . $record['date_submitted'] . "' LIMIT 1");
    $statusRow = mysqli_fetch_assoc($statusQuery);
    $grouped_records[$id]['status'] = $statusRow['form_status'] ?? 'In Progress';
}

// Fixed Summary Query to avoid duplication from JOINs
$summaryQuery = mysqli_query($conn, "
    SELECT 
        o.office_name,
        COALESCE(comp.desktop_count, 0) as desktop_count,
        COALESCE(comp.laptop_count, 0) as laptop_count,
        COALESCE(other.modem_count, 0) as modem_count,
        COALESCE(other.printer_count, 0) as printer_count,
        COALESCE(other.router_count, 0) as router_count,
        COALESCE(other.switch_count, 0) as switch_count,
        COALESCE(internet.server_room_count, 0) as server_room_count,
        COALESCE(internet.conn_yes, 0) as conn_yes,
        COALESCE(internet.conn_no, 0) as conn_no
    FROM offices o
    LEFT JOIN (
        SELECT f.office_name,
            SUM(CASE WHEN ce.item LIKE '%desktop%' THEN ce.number_of_units ELSE 0 END) as desktop_count,
            SUM(CASE WHEN ce.item LIKE '%laptop%' THEN ce.number_of_units ELSE 0 END) as laptop_count
        FROM form f
        JOIN computer_equipment ce ON f.id = ce.form_id
        WHERE 1=1 $summary_date_condition
        GROUP BY f.office_name
    ) comp ON o.office_name = comp.office_name
    LEFT JOIN (
        SELECT f.office_name,
            SUM(oie.modem) as modem_count,
            SUM(COALESCE(oie.deskjet_printer, 0) + COALESCE(oie.dot_matrix_printer, 0) + COALESCE(oie.inkjet_printer, 0)) as printer_count,
            SUM(oie.routers) as router_count,
            SUM(oie.switch_hubs) as switch_count
        FROM form f
        JOIN other_ict_equipment oie ON f.id = oie.form_id
        WHERE 1=1 $summary_date_condition
        GROUP BY f.office_name
    ) other ON o.office_name = other.office_name
    LEFT JOIN (
        SELECT f.office_name,
            SUM(ic.is_centralized_db_server) as server_room_count,
            SUM(CASE WHEN ic.is_centralized_isp = 1 THEN 1 ELSE 0 END) as conn_yes,
            SUM(CASE WHEN ic.is_centralized_isp = 0 OR ic.is_centralized_isp IS NULL THEN 1 ELSE 0 END) as conn_no
        FROM form f
        JOIN internet_connections ic ON f.id = ic.form_id
        WHERE 1=1 $summary_date_condition
        GROUP BY f.office_name
    ) internet ON o.office_name = internet.office_name
    WHERE (comp.office_name IS NOT NULL OR other.office_name IS NOT NULL OR internet.office_name IS NOT NULL)
    ORDER BY o.office_name ASC
");

$summaryRecords = [];
if ($summaryQuery) {
    while ($row = mysqli_fetch_assoc($summaryQuery)) {
        $summaryRecords[] = $row;
    }
}

// Calculate equipment totals for chart
$totalComputers = 0;
$totalPrinters = 0;
$totalNetworking = 0;
foreach ($summaryRecords as $row) {
    $totalComputers += ($row['desktop_count'] + $row['laptop_count']);
    $totalPrinters += $row['printer_count'];
    $totalNetworking += ($row['modem_count'] + $row['router_count'] + $row['switch_count']);
}
$totalICTEquipment = $totalComputers + $totalPrinters + $totalNetworking;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'Reports - ICTMIS'; ?>
<?php include 'components/head.php'; ?>
<link rel="stylesheet" href="../assest/css/admin/form.css">
<link rel="stylesheet" href="../assest/css/admin/report.css">

<style>
/* Modern Dashboard Styles - Precise Match */
:root {
    --primary: #4f46e5;
    --primary-light: #818cf8;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --accent: #00f2ff;
    --bg-light: #f1f5f9;
    --card-bg: #ffffff;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --glass-border: rgba(15, 23, 42, 0.08);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
    --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
}

body {
    background-color: var(--bg-light);
    color: var(--text-main);
    font-family: 'Inter', system-ui, sans-serif;
}

/* Header Styling */
.dashboard-header {
    margin: 0 0 2.5rem;
    padding: 1.5rem 2rem;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    border-radius: 0 0 1.5rem 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-top: none;
    box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05);
}

.dashboard-title {
    font-size: 1.85rem;
    font-weight: 800;
    letter-spacing: -0.025em;
    color: #0f172a;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.dashboard-title i {
    font-size: 1.6rem;
    padding: 10px;
    background: linear-gradient(135deg, #fff, #f8fafc);
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    color: var(--primary);
    border: 1px solid rgba(255, 255, 255, 0.8);
    animation: iconPulse 3s infinite;
}

@keyframes iconPulse {
    0% { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 0 0 0 rgba(79, 70, 229, 0.2); }
    70% { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 0 0 10px rgba(79, 70, 229, 0); }
    100% { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 0 0 0 rgba(79, 70, 229, 0); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.animate-fade-in-scale {
    animation: fadeInScale 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }

@keyframes dotPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(79, 70, 229, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
}

.legend-dot-pulse {
    animation: dotPulse 2s infinite;
}

@keyframes smoothReveal {
    0% {
        transform: scale(0.9);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-smooth-reveal {
    animation: smoothReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.dashboard-subtitle {
    color: #64748b;
    font-size: 0.95rem;
    font-weight: 500;
    margin-left: 3.5rem;
}

.top-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.search-container {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.5rem 1.15rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 320px;
    transition: var(--transition);
}

.search-container:focus-within {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    width: 350px;
}

.search-input {
    border: none;
    outline: none;
    width: 100%;
    font-size: 0.9rem;
    color: #1e293b;
    font-weight: 600;
    background: transparent;
}

.btn-modern {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    transition: var(--transition);
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-spreadsheet {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
}

.btn-summary {
    background: linear-gradient(135deg, #4f46e5, #3730a3);
    color: white;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
}

.btn-grid-toggle {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: #fff;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    border: 1.5px solid #e2e8f0;
    transition: var(--transition);
}

.btn-grid-toggle:hover {
    background: #f8fafc;
    color: var(--primary);
    border-color: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.btn-grid-toggle.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
    filter: brightness(1.05);
}

.btn-modern:active {
    transform: translateY(0);
}

.active-btn {
    opacity: 1 !important;
    transform: scale(1.02);
    box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.3) !important;
}

.btn-modern:not(.active-btn) {
    opacity: 0.85;
}

/* Dashboard Content Layout */
.dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 2.5fr) minmax(350px, 1fr);
    gap: 2rem;
    align-items: start;
}

.dashboard-content-wrapper {
    max-width: 100%;
    margin: 0;
    width: 100%;
    padding: 0 2rem 3rem;
}

/* Fix for zoom stability */
@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

.glass-card {
    background: #fff;
    border-radius: 1.5rem;
    border: 1px solid rgba(0,0,0,0.02);
    box-shadow: var(--shadow);
    padding: 2rem;
    transition: var(--transition);
}

.glass-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.card-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    color: #1e293b;
}

.card-title i {
    font-size: 1.2rem;
    color: #1e293b;
    padding: 8px;
    background: #f8fafc;
    border-radius: 8px;
}

/* Modern Table */
.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.75rem;
}

.modern-table th {
    color: #475569;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: capitalize;
    padding: 0 1rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.modern-table td {
    padding: 1rem;
    background: #fff;
    vertical-align: middle;
    border-top: 1px solid #f8fafc;
    border-bottom: 1px solid #f8fafc;
}

.modern-table tr td:first-child {
    border-left: 1px solid #f8fafc;
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
}

.modern-table tr td:last-child {
    border-right: 1px solid #f8fafc;
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
}

.report-name-cell {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.report-icon {
    width: 36px;
    height: 36px;
    background: #f1f5f9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    border: none;
}

.report-name-text {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.95rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 800;
}

.status-completed { background: #ecfdf5; color: #10b981; }
.status-in-progress { background: #eff6ff; color: #3b82f6; }
.status-overdue { background: #fef2f2; color: #ef4444; }

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #f1f5f9;
    transition: var(--transition);
}

.action-btn:hover {
    background: #fff;
    color: #1e293b;
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.action-btn.pdf-report-btn:hover {
    color: #dc2626;
    border-color: #fca5a5;
    background: #fef2f2;
}

/* Dashboard Title Icon */
.dashboard-title i {
    background: linear-gradient(135deg, #4f46e5, #818cf8);
    color: white;
}

/* Search Icon */
.search-container i {
    color: #6366f1;
}

/* Button Icons */
.btn-spreadsheet i {
    color: white;
}
.btn-summary i {
    color: white;
}
.btn-grid-toggle i {
    color: #64748b;
}
.btn-grid-toggle.active i {
    color: white;
}

/* Card Title Icons */
.card-title i {
    color: #4f46e5;
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
}

/* Action Button Icons */
.report-clickable i {
    color: #3b82f6;
}
.action-btn:nth-child(2) i {
    color: #f59e0b;
}
.action-btn:nth-child(3) i {
    color: #10b981;
}
.pdf-report-btn i {
    color: #ef4444;
}

/* Equipment Overview Icon */
.card-title .bi-pc-display {
    color: #8b5cf6;
    background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
}

/* Export Button Icons */
.btn-export-excel i {
    color: white;
}
.btn-export-pdf i {
    color: white;
}
.btn-export-download i {
    color: white;
}

/* No Results Icon */
.no-results-cell i {
    color: #94a3b8;
}

/* Pagination Icons */
.page-link i {
    color: #64748b;
}

/* Chart Section */
.chart-container {
    height: 260px;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 2.5rem;
}

.chart-center-text {
    position: absolute;
    text-align: center;
}

.chart-center-value {
    font-size: 2.25rem;
    font-weight: 800;
    color: #1e293b;
    display: block;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.chart-center-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #94a3b8;
}

.chart-legend {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.9rem;
    font-weight: 700;
}

.legend-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #1e293b;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-value {
    color: #94a3b8;
    font-weight: 600;
}

.pagination-modern .page-link {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50% !important;
    margin: 0 4px;
    font-weight: 700;
    font-size: 0.85rem;
    border: none;
    color: #1e293b;
    transition: var(--transition);
}

.pagination-modern .page-link:hover:not(.active) {
    transform: scale(1.15);
    background: #f1f5f9;
}

.pagination-modern .page-item.active .page-link {
    background-color: #2563eb !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.modern-table th {
    color: #94a3b8;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0 1rem 0.5rem;
    border-bottom: 1px solid #f1f5f9;
}

.modern-table td {
    padding: 1.15rem 1rem;
    background: #fff;
    vertical-align: middle;
    border-top: 1px solid #f8fafc;
    border-bottom: 1px solid #f8fafc;
}

.modern-table tr td:first-child {
    border-left: 1px solid #f8fafc;
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
}

.modern-table tr td:last-child {
    border-right: 1px solid #f8fafc;
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
}

.report-name-cell {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.report-icon {
    width: 38px;
    height: 38px;
    background: #f8fafc;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    border: 1px solid #f1f5f9;
    transition: var(--transition);
}

.report-icon:hover {
    transform: scale(1.15) rotate(10deg);
    background: #fff;
    color: var(--primary);
    border-color: var(--primary-light);
}

.report-name-text {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 800;
}

.status-completed { background: #f0fdf4; color: #16a34a; }
.status-in-progress { background: #eff6ff; color: #2563eb; }
.status-overdue { background: #fef2f2; color: #ef4444; }

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #f1f5f9;
    transition: var(--transition);
}

.action-btn:hover {
    background: #fff;
    color: #1e293b;
    border-color: #cbd5e1;
    transform: translateY(-2px);
}

/* Chart Section */
.chart-container {
    height: 240px;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 2rem;
}

.chart-center-text {
    position: absolute;
    text-align: center;
}

.chart-center-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #1e293b;
    display: block;
    line-height: 1;
}

.chart-center-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #94a3b8;
}

.chart-legend {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.85rem;
    font-weight: 700;
}

.legend-label {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: #1e293b;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.legend-value {
    color: #94a3b8;
}

.spreadsheet-summary-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    overflow-x: auto;
    margin-bottom: 25px;
    border: 1px solid #e2e8f0;
    display: none;
    transition: var(--transition);
    opacity: 0;
    transform: translateY(20px);
}

.spreadsheet-summary-container.active { 
    display: block;
    animation: fadeInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.spreadsheet-header {
    padding: 30px;
    background: #f8fafc;
    color: #1e293b;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
}

.header-logo-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.header-text {
    flex: none;
    text-align: center;
}

.header-logo {
    width: 90px;
    height: auto;
    object-fit: contain;
}

html.dark-mode .spreadsheet-header {
    background: rgba(16, 20, 28, 0.4) !important;
    border-bottom: 1px solid rgba(0, 242, 255, 0.1) !important;
    color: #ffffff !important;
}

.spreadsheet-title-1, .spreadsheet-title-2, .spreadsheet-title-3 {
    color: #1e293b;
    font-weight: bold;
    margin-bottom: 5px;
}

.spreadsheet-title-1 { font-size: 16px; }
.spreadsheet-title-2 { font-size: 18px; }
.spreadsheet-title-3 { font-size: 16px; margin-bottom: 0; }

html.dark-mode .spreadsheet-title-1,
html.dark-mode .spreadsheet-title-2,
html.dark-mode .spreadsheet-title-3 {
    color: var(--neon-cyan) !important;
    text-shadow: 0 0 10px rgba(0, 242, 255, 0.3);
}

.export-buttons-bar {
    padding: 16px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

html.dark-mode .export-buttons-bar {
    background: rgba(16, 20, 28, 0.6) !important;
    border-bottom: 1px solid rgba(0, 242, 255, 0.1) !important;
}

.spreadsheet-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

#spreadsheetTable {
    min-width: 1300px;
}

#summaryTable {
    min-width: 100%;
}

.spreadsheet-table th,
.spreadsheet-table td {
    border: 1px solid #e2e8f0;
    padding: 8px 12px;
    vertical-align: middle;
    color: #475569;
}

html.dark-mode .spreadsheet-table th,
html.dark-mode .spreadsheet-table td {
    border-color: rgba(255, 255, 255, 0.05) !important;
    color: #cbd5e1 !important;
}

.spreadsheet-table thead th {
    background: #f1f5f9;
    color: #475569;
    font-weight: 800;
    text-transform: uppercase;
    text-align: center;
}

html.dark-mode .spreadsheet-table thead th {
    background: rgba(0, 242, 255, 0.05) !important;
    color: var(--neon-cyan) !important;
}

.office-name-cell {
    font-weight: 700;
    color: #1e293b;
    background: #f8fafc;
    position: sticky;
    left: 0;
    z-index: 10;
    vertical-align: middle;
    text-align: center;
}

html.dark-mode .office-name-cell {
    background: #0b0e14 !important;
    color: #ffffff !important;
}

.grand-total-row {
    background: #f1f5f9;
    font-weight: 800;
}

html.dark-mode .grand-total-row {
    background: rgba(0, 242, 255, 0.03) !important;
    color: #ffffff !important;
}

.report-note {
    padding: 20px;
    font-style: italic;
    font-size: 12px;
    color: #475569;
}

html.dark-mode .report-note {
    color: #94a3b8 !important;
}

.conn-yes { color: #ea580c; }
.conn-no { color: #000; }

html.dark-mode .conn-yes { color: var(--neon-cyan) !important; }
html.dark-mode .conn-no { color: #94a3b8 !important; }

.spreadsheet-title {
    font-weight: 800;
    font-size: 20px;
    letter-spacing: 0.5px;
    margin: 0;
    text-transform: uppercase;
}

html.dark-mode .spreadsheet-title {
    color: var(--neon-cyan) !important;
}

.spreadsheet-subtitle {
    font-size: 13px;
    margin-top: 8px;
    opacity: 0.8;
    font-style: italic;
}

html.dark-mode .spreadsheet-subtitle {
    color: #cbd5e1 !important;
}

.btn-spreadsheet-view {
    background: #4f46e5;
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

html.dark-mode .btn-spreadsheet-view {
    background: rgba(79, 70, 229, 0.2) !important;
    border: 1px solid var(--neon-cyan) !important;
    color: var(--neon-cyan) !important;
}

.btn-spreadsheet-view:hover {
    background: #4338ca;
    transform: translateY(-1px);
}

html.dark-mode .btn-spreadsheet-view:hover {
    background: var(--neon-cyan) !important;
    color: #000 !important;
    box-shadow: 0 0 15px var(--neon-cyan) !important;
}

.btn-spreadsheet-view.active {
    background: #1e293b;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
}

html.dark-mode .btn-spreadsheet-view.active {
    background: var(--neon-cyan) !important;
    color: #000 !important;
}

.btn-export {
    border: none;
    color: white;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-export-excel { background: #16a34a; }
.btn-export-pdf { background: #dc2626; }
.btn-export-download { background: #2563eb; }

html.dark-mode .btn-export {
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

html.dark-mode .btn-export:hover {
    transform: translateY(-2px);
    filter: brightness(1.1);
}

.table-scroll-summary {
    overflow-x: auto;
    max-height: 70vh;
}

.spreadsheet-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.spreadsheet-table th,
.spreadsheet-table td {
    border: 1px solid #e2e8f0;
    padding: 8px 12px;
    vertical-align: middle;
}

.spreadsheet-table td {
    text-align: left;
}

.spreadsheet-table thead tr:first-child th {
    height: 40px;
    font-weight: 800;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: center;
    vertical-align: middle;
    background: #1e293b;
}

html.dark-mode .spreadsheet-table thead tr:first-child th {
    background: #0f172a !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.spreadsheet-table thead tr:last-child th {
    background: #4b5563;
    color: white;
    font-weight: 700;
    text-align: center;
    white-space: nowrap;
}

html.dark-mode .spreadsheet-table thead tr:last-child th {
    background: #1f2937 !important;
    color: #cbd5e1 !important;
    border-color: rgba(255, 255, 255, 0.05) !important;
}

.th-office, .th-computer, .th-printer, .th-network, .th-systems { 
    background: #1f2937 !important; 
    color: white !important; 
}

html.dark-mode .th-office, 
html.dark-mode .th-computer, 
html.dark-mode .th-printer, 
html.dark-mode .th-network, 
html.dark-mode .th-systems { 
    background: rgba(0, 242, 255, 0.1) !important; 
    color: var(--neon-cyan) !important; 
    border-color: rgba(0, 242, 255, 0.2) !important;
}

.office-name-cell {
    font-weight: 700;
    color: #1e293b;
    background: #f8fafc;
    position: sticky;
    left: 0;
    z-index: 10;
    vertical-align: middle;
    text-align: center;
}

html.dark-mode .office-name-cell {
    background: #0b0e14 !important;
    color: #ffffff !important;
    border-right: 1px solid rgba(0, 242, 255, 0.1) !important;
}

.item-cell {
    text-align: left !important;
    padding-left: 10px !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    min-width: 150px !important;
}

.systems-text {
    text-align: center !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    min-width: 120px !important;
}

.text-center { text-align: center; }

.no-results-cell {
    padding: 60px !important;
}

.no-results-cell i {
    font-size: 48px;
    color: #cbd5e0;
    margin-bottom: 15px;
    display: block;
}

html.dark-mode .no-results-cell i {
    color: rgba(255, 255, 255, 0.1) !important;
}

html.dark-mode .no-results-cell p {
    color: #94a3b8 !important;
}

.min-w-180 { min-width: 180px; }
.min-w-120 { min-width: 120px; }
.min-w-100 { min-width: 100px; }
.min-w-400 { min-width: 400px; }
.min-w-200 { min-width: 200px; }
.min-w-250 { min-width: 250px; }

@media print {
    @page {
        size: landscape;
        margin: 0.2cm;
    }

    .sidebar, .top-header, .welcome-header, .stat-card, .chart-container, 
    .glass-card, .report-clickable, .filter-bar, .layout-btn, .dropdown,
    .btn-print-summary, .btn-export-summary, .btn-filter, .btn-reset, .btn-spreadsheet-view,
    .card-header .btn, .chart-container, .row.mb-4, .reports-grid, #reportsGrid,
    #tableView, .excel-table-container, .modal, .modal-backdrop, .d-none,
    .paper-size-selector, .export-buttons-bar, .main-content > .top-bar,
    .dashboard-header, .dashboard-content-wrapper {
        display: none !important;
    }
    
    .spreadsheet-summary-container {
        display: none !important;
    }
    
    .spreadsheet-summary-container.active {
        display: block !important;
        position: relative !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: none !important;
        animation: none !important;
    }
    
    .spreadsheet-summary-container.active,
    .spreadsheet-summary-container.active *,
    html.dark-mode .spreadsheet-summary-container.active,
    html.dark-mode .spreadsheet-summary-container.active * {
        color: #000000 !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .spreadsheet-header {
        background: white !important;
        color: #000 !important;
        padding: 0 !important;
        margin: 0 0 10px 0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        text-align: center !important;
        opacity: 1 !important;
    }

    .header-logo-container {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 20px !important;
        width: 100% !important;
        margin: 0 0 15px 0 !important;
    }

    .header-logo {
        width: 70px !important;
        height: 70px !important;
        object-fit: contain !important;
    }

    .header-text {
        flex: none !important;
        text-align: center !important;
    }
    
    .spreadsheet-title-1, .spreadsheet-title-2, .spreadsheet-title-3,
    .sub-text, .province-text, .spreadsheet-title, .spreadsheet-subtitle,
    .spreadsheet-summary-line,
    html.dark-mode .spreadsheet-title-1, html.dark-mode .spreadsheet-title-2, 
    html.dark-mode .spreadsheet-title-3, html.dark-mode .sub-text,
    html.dark-mode .province-text, html.dark-mode .spreadsheet-title,
    html.dark-mode .spreadsheet-subtitle {
        color: #000 !important;
        display: block !important;
        margin-bottom: 2px !important;
        font-weight: bold !important;
        text-align: center !important;
        opacity: 1 !important;
        text-shadow: none !important;
    }
    
    .spreadsheet-title-1, .sub-text { font-size: 11pt !important; }
    .spreadsheet-title-2, .province-text { font-size: 12pt !important; }
    .spreadsheet-title-3, .spreadsheet-subtitle { font-size: 10pt !important; font-weight: normal !important; }
    .spreadsheet-title { font-size: 14pt !important; font-weight: bold !important; }
    
    .spreadsheet-table {
        width: 100% !important;
        min-width: auto !important;
        table-layout: fixed !important;
        font-size: 4.5pt !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin-bottom: 20px !important;
        border: 1px solid #000 !important;
        overflow: visible !important;
    }

    .th-systems { 
        font-size: 4.5pt !important;
    }
    
    .spreadsheet-table th, .spreadsheet-table td,
    html.dark-mode .spreadsheet-table th, html.dark-mode .spreadsheet-table td {
        border: 1px solid #000 !important;
        padding: 1px !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
        white-space: normal !important;
        color: #000 !important;
        background-color: transparent !important;
        opacity: 1 !important;
    }

    /* Override for specific cells that NEED to wrap */
    .item-cell, .th-item-header {
        white-space: normal !important;
        word-break: break-word !important;
    }

    .spreadsheet-table thead tr:first-child th,
    .th-office, .th-computer, .th-printer, .th-network, .th-systems,
    html.dark-mode .spreadsheet-table thead tr:first-child th,
    html.dark-mode .th-office, html.dark-mode .th-computer, 
    html.dark-mode .th-printer, html.dark-mode .th-network, 
    html.dark-mode .th-systems {
        color: #ffffff !important;
        background-color: #1e293b !important;
        opacity: 1 !important;
        border: 1px solid #334155 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .spreadsheet-table thead tr:last-child th,
    .office-name-cell, .item-cell, .units-cell, .brand-cell, .processor-cell, 
    .ram-cell, .hdd-cell, .ssd-cell, .printer-cell, .network-cell, .systems-text,
    html.dark-mode .spreadsheet-table thead tr:last-child th,
    html.dark-mode .office-name-cell, 
    html.dark-mode .item-cell, html.dark-mode .units-cell, 
    html.dark-mode .brand-cell, html.dark-mode .processor-cell, 
    html.dark-mode .ram-cell, html.dark-mode .hdd-cell, 
    html.dark-mode .ssd-cell, html.dark-mode .printer-cell, 
    html.dark-mode .network-cell, html.dark-mode .systems-text {
        color: #ffffff !important;
        background-color: #4b5563 !important;
        opacity: 1 !important;
        border: 1px solid #334155 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Specific column widths for 23-column layout to force fit */
    #spreadsheetTable th:nth-child(1) { width: 8%; } /* Office */
    #spreadsheetTable th:nth-child(2) { width: 5%; }  /* Submission */
    
    /* System columns (last 8 columns of the second header row) */
    #spreadsheetTable thead tr:last-child th:nth-last-child(-n+8) {
        width: 4% !important;
        white-space: normal !important;
        word-break: break-word !important;
        font-size: 3.8pt !important;
        padding: 0.2px !important;
    }

    /* Printer and Network columns */
    #spreadsheetTable thead tr:last-child th:nth-last-child(n+9):nth-last-child(-n+14) {
        width: 3.5% !important;
    }

    /* Printer and Network columns */
    #spreadsheetTable thead tr:last-child th:nth-child(1) { width: 45px !important; } /* Item */
    #spreadsheetTable thead tr:last-child th:nth-child(2) { width: 3% !important; } /* Units */
    
    /* Brand, Processor, RAM specifically */
    #spreadsheetTable thead tr:last-child th:nth-child(3) { width: 10% !important; } /* Brand */
    #spreadsheetTable thead tr:last-child th:nth-child(4) { width: 12% !important; } /* Processor */
    #spreadsheetTable thead tr:last-child th:nth-child(5) { width: 6% !important; } /* RAM */

    /* Column specific alignment using classes */
    .brand-cell, .processor-cell, .ram-cell, .hdd-cell, .ssd-cell, .units-cell, .printer-cell, .network-cell {
        text-align: center !important;
        white-space: nowrap !important;
        padding: 1.5px !important;
    }

    .item-cell {
        text-align: left !important;
        padding: 1.5px 3px !important;
        white-space: normal !important;
        word-break: break-word !important;
        width: 45px !important;
        max-width: 45px !important;
    }

    /* Ensure HDD and SSD columns are wide enough for one line */
    #spreadsheetTable th:nth-child(8), 
    #spreadsheetTable th:nth-child(9) { 
        width: 7% !important; 
        white-space: nowrap !important;
    }
    
    #spreadsheetTable td:nth-child(8), 
    #spreadsheetTable td:nth-child(9) { 
        white-space: nowrap !important;
    }

    /* Other columns will divide the remaining space */
    .systems-text { 
        font-size: 4.5pt !important; 
        white-space: normal !important;
        word-wrap: break-word !important;
        text-align: center !important;
    }

    .spreadsheet-table th, .spreadsheet-table td {
        box-sizing: border-box !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .table-scroll-summary {
        overflow: visible !important;
        display: block !important;
        width: 100% !important;
        padding: 1px !important;
    }

    .spreadsheet-table th {
        background: #1e293b !important;
        color: #ffffff !important;
        word-wrap: break-word !important;
        white-space: normal !important;
        padding: 1.5px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        text-align: center !important;
        vertical-align: middle !important;
        font-size: 5pt !important;
        font-weight: bold !important;
        border: 1px solid #334155 !important;
    }
    
    .spreadsheet-table td {
        padding: 1.5px !important;
        word-wrap: normal !important;
        vertical-align: middle !important;
        font-size: 5pt !important;
    }
    
    .spreadsheet-table td.text-center,
    .spreadsheet-table td:nth-child(2),
    .spreadsheet-table td:nth-child(4),
    .spreadsheet-table td:nth-child(8),
    .spreadsheet-table td:nth-child(9),
    .spreadsheet-table td:nth-child(10),
    .spreadsheet-table td:nth-child(11),
    .spreadsheet-table td:nth-child(12),
    .spreadsheet-table td:nth-child(13),
    .spreadsheet-table td:nth-child(14),
    .spreadsheet-table td:nth-child(15) {
        text-align: center !important;
    }

    .min-w-180, .min-w-120, .min-w-100, .min-w-400, .min-w-200, .min-w-250 {
        min-width: auto !important;
    }

    #summaryTable th:nth-child(1) { width: 18%; }
    #summaryTable th:nth-child(2) { width: 8%; }
    #summaryTable th:nth-child(3) { width: 8%; }
    #summaryTable th:nth-child(4) { width: 9%; }
    #summaryTable th:nth-child(5) { width: 9%; }
    #summaryTable th:nth-child(6) { width: 8%; }
    #summaryTable th:nth-child(7) { width: 8%; }
    #summaryTable th:nth-child(8) { width: 15%; }
    #summaryTable th:nth-child(9) { width: 17%; }

    .office-name-cell {
        background: white !important;
        color: black !important;
        position: static !important;
        font-size: 8pt !important;
    }

    .grand-total-row {
        background: #f0f0f0 !important;
        font-weight: bold !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    html, body {
        margin: 0;
        padding: 0;
        background: white !important;
        width: 100% !important;
        overflow-x: hidden !important;
        height: auto !important;
        min-height: auto !important;
        display: block !important;
    }
    
    .d-flex {
        display: block !important;
    }
    
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        display: block !important;
    }

    .report-note {
        font-size: 8pt !important;
        margin-top: 10px !important;
    }

    /* Force wrapping for Item column in Print */
    #spreadsheetTable td.item-cell, 
    #spreadsheetTable th.th-item-header {
        white-space: normal !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        width: 60px !important;
        max-width: 60px !important;
        line-height: 1.2 !important;
        font-size: 4.5pt !important;
        display: table-cell !important;
    }
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

<div class="dashboard-content-wrapper">
    <div class="dashboard-header animate-fade-in-up">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="dashboard-title">
                    <i class="bi bi-file-earmark-text"></i> RMAPS Reports
                </h1>
                <p class="dashboard-subtitle">View, analyze and manage reports across all offices.</p>
            </div>
            <div class="top-actions">
                <div class="search-container">
                    <form method="GET" id="searchForm" class="w-100 d-flex align-items-center">
                        <i class="bi bi-search text-muted me-2"></i>
                        <?php if(isset($_GET['year'])): ?>
                            <input type="hidden" name="year" value="<?php echo htmlspecialchars($_GET['year']); ?>">
                        <?php endif; ?>
                        <input type="text" name="search" class="search-input" placeholder="Search Office..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </form>
                </div>
                <button id="spreadsheetViewBtn" class="btn-modern btn-spreadsheet">
                    <i class="bi bi-grid-3x3-gap"></i> Spreadsheet View
                </button>
                <button id="summaryViewBtn" class="btn-modern btn-summary">
                    <i class="bi bi-bar-chart-line"></i> Summary
                </button>
                <button id="dashboardViewBtn" class="btn-grid-toggle active">
                    <i class="bi bi-columns-gap"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Left Column: Latest Submissions Table -->
        <div class="glass-card animate-fade-in-up delay-1">
            <div class="card-title-row">
                <h3 class="card-title"><i class="bi bi-clipboard-data"></i> Latest Office Submissions</h3>
                <select class="form-select form-select-sm border-0 fw-bold text-muted shadow-none" style="width: auto; font-size: 0.85rem; background: transparent; cursor: pointer;" onchange="location.href='?year=' + this.value + '<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>'">
                    <option value="">Select Year (2026-2030)</option>
                    <?php for($y = 2026; $y <= 2030; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Office</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($grouped_records)): 
                            $counter = 0;
                            foreach($grouped_records as $row): 
                                if($counter >= 5) break; // Limit to 5 for "Recent"
                                $counter++;
                        ?>
                                <tr>
                                    <td><span class="fw-bold" style="color: #1e293b; font-size: 0.85rem;"><?php echo htmlspecialchars($row['office_name']); ?></span></td>
                                    <td><span class="text-muted" style="font-size: 0.85rem; font-weight: 500;"><?php echo date('M d, Y', strtotime($row['date_submitted'])); ?></span></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="#" class="action-btn report-clickable" data-office="<?php echo htmlspecialchars($row['office_name']); ?>" data-date="<?php echo htmlspecialchars($row['date_submitted']); ?>" title="View Report">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="action/edit_report.php?id=<?php echo $row['id']; ?>" class="action-btn" title="Edit Report">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="#" class="action-btn print-report-btn" data-office="<?php echo htmlspecialchars($row['office_name']); ?>" data-date="<?php echo htmlspecialchars($row['date_submitted']); ?>" title="Print Report">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <a href="#" class="action-btn pdf-report-btn" data-office="<?php echo htmlspecialchars($row['office_name']); ?>" data-date="<?php echo htmlspecialchars($row['date_submitted']); ?>" title="Download PDF">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5">No reports found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 d-flex justify-content-between align-items-center">
                <span class="text-muted fw-bold" style="font-size: 0.85rem;">Showing 1 to <?php echo min(5, count($grouped_records)); ?> of <?php echo $totalReports; ?> reports</span>
                <nav>
                    <ul class="pagination pagination-sm m-0">
                        <li class="page-item disabled"><a class="page-link border-0 bg-transparent text-muted" href="#"><i class="bi bi-chevron-left"></i></a></li>
                        <li class="page-item active"><a class="page-link rounded-circle mx-1 border-0" href="#" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; background: #2563eb;">1</a></li>
                        <li class="page-item"><a class="page-link rounded-circle mx-1 border-0 text-dark" href="#" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">2</a></li>
                        <li class="page-item"><a class="page-link rounded-circle mx-1 border-0 text-dark" href="#" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">3</a></li>
                        <li class="page-item disabled"><span class="page-link border-0 bg-transparent text-muted">...</span></li>
                        <li class="page-item"><a class="page-link rounded-circle mx-1 border-0 text-dark" href="#" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">26</a></li>
                        <li class="page-item"><a class="page-link border-0 bg-transparent text-dark" href="#"><i class="bi bi-chevron-right"></i></a></li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Right Column: Chart -->
        <div class="side-column animate-fade-in-up delay-2">
            <div class="glass-card">
                <div class="card-title-row">
                    <h3 class="card-title"><i class="bi bi-pc-display"></i> Equipment Overview</h3>
                    <i class="bi bi-three-dots-vertical text-muted"></i>
                </div>
                <div class="chart-container animate-smooth-reveal">
                    <canvas id="equipmentChart"></canvas>
                    <div class="chart-center-text">
                        <span class="chart-center-value" data-value="<?php echo $totalICTEquipment; ?>">0</span>
                        <span class="chart-center-label">Total Units</span>
                    </div>
                </div>
                <div class="chart-legend">
                    <div class="legend-item animate-fade-in-up delay-1">
                        <div class="legend-label">
                            <div class="legend-dot legend-dot-pulse" style="background: #4f46e5; animation-delay: 0.1s;"></div>
                            <span>Computers</span>
                        </div>
                        <span class="legend-value"><?php echo $totalComputers; ?> (<?php echo $totalICTEquipment > 0 ? round(($totalComputers/$totalICTEquipment)*100, 1) : 0; ?>%)</span>
                    </div>
                    <div class="legend-item animate-fade-in-up delay-2">
                        <div class="legend-label">
                            <div class="legend-dot legend-dot-pulse" style="background: #10b981; animation-delay: 0.3s;"></div>
                            <span>Printers</span>
                        </div>
                        <span class="legend-value"><?php echo $totalPrinters; ?> (<?php echo $totalICTEquipment > 0 ? round(($totalPrinters/$totalICTEquipment)*100, 1) : 0; ?>%)</span>
                    </div>
                    <div class="legend-item animate-fade-in-up delay-3">
                        <div class="legend-label">
                            <div class="legend-dot legend-dot-pulse" style="background: #f59e0b; animation-delay: 0.5s;"></div>
                            <span>Networking</span>
                        </div>
                        <span class="legend-value"><?php echo $totalNetworking; ?> (<?php echo $totalICTEquipment > 0 ? round(($totalNetworking/$totalICTEquipment)*100, 1) : 0; ?>%)</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div> <!-- .dashboard-content-wrapper -->

<!-- SUMMARY VIEW CONTAINER -->
<div id="summaryViewContainer" class="spreadsheet-summary-container">
    <div class="export-buttons-bar">
        <button class="btn-export btn-export-excel" id="exportSummaryExcelBtn">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
        </button>
        <button class="btn-export btn-export-pdf" id="exportSummaryPdfBtn">
            <i class="bi bi-printer"></i> Print
        </button>
        <button class="btn-export btn-export-download" id="downloadSummaryPdfBtn">
            <i class="bi bi-download"></i> Download PDF
        </button>
    </div>
    <div class="spreadsheet-header">
        <div class="header-logo-container">
            <img src="../assest/images/logo1.png" alt="Logo" class="header-logo">
            <div class="header-text">
                <div class="spreadsheet-title-1">LOCAL GOVERNMENT UNIT OF MANGALDAN</div>
                <div class="spreadsheet-title-2">SUMMARY - INVENTORY ON OFFICE DEVICES AND EQUIPMENTS</div>
                <div class="spreadsheet-title-3">FOR RMAPS (DIGITAL GOVERNANCE READINESS)</div>
            </div>
            <img src="../assest/images/logo3.png" alt="Logo" class="header-logo">
        </div>
    </div>
    
    <div class="table-scroll-summary">
        <table class="spreadsheet-table" id="summaryTable">
            <thead>
                <tr>
                    <th class="text-center">OFFICE</th>
                    <th class="text-center">DESKTOP</th>
                    <th class="text-center">LAPTOP</th>
                    <th class="text-center">WITH MODEM</th>
                    <th class="text-center">SWITCH HUB</th>
                    <th class="text-center">PRINTER</th>
                    <th class="text-center">ROUTERS</th>
                    <th class="text-center">NETWORK SERVER ROOM (EQUIPPED)</th>
                    <th class="text-center">CONNECTED TO ICT-MIS SERVER</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grandTotalDesktop = 0;
                $grandTotalLaptop = 0;
                $grandTotalModem = 0;
                $grandTotalServerRoom = 0;
                $grandTotalPrinter = 0;
                $grandTotalRouters = 0;
                $grandTotalSwitch = 0;
                $grandTotalYes = 0;
                $grandTotalNo = 0;
                
                if (!empty($summaryRecords)): 
                    foreach ($summaryRecords as $row): 
                        $grandTotalDesktop += $row['desktop_count'];
                        $grandTotalLaptop += $row['laptop_count'];
                        $grandTotalModem += $row['modem_count'];
                        $grandTotalServerRoom += $row['server_room_count'];
                        $grandTotalPrinter += $row['printer_count'];
                        $grandTotalRouters += $row['router_count'];
                        $grandTotalSwitch += $row['switch_count'];
                        $grandTotalYes += $row['conn_yes'];
                        $grandTotalNo += $row['conn_no'];
                ?>
                    <tr>
                        <td class="office-name-cell" style="text-align: left; padding-left: 15px;">
                            <?php echo htmlspecialchars(strtoupper($row['office_name'])); ?>
                        </td>
                        <td class="text-center"><?php echo $row['desktop_count'] ?: ''; ?></td>
                        <td class="text-center"><?php echo $row['laptop_count'] ?: ''; ?></td>
                        <td class="text-center"><?php echo $row['modem_count'] ?: ''; ?></td>
                        <td class="text-center"><?php echo $row['switch_count'] ?: ''; ?></td>
                        <td class="text-center"><?php echo $row['printer_count'] ?: ''; ?></td>
                        <td class="text-center"><?php echo $row['router_count'] ?: ''; ?></td>
                        <td class="text-center"><?php echo $row['server_room_count'] ?: ''; ?></td>
                        <td class="text-center" style="font-weight: bold;">
                            <?php 
                            if ($row['conn_yes'] > 0 && $row['conn_no'] > 0) {
                                echo "<span class='conn-yes'>{$row['conn_yes']} (YES)</span> <span class='conn-no'>{$row['conn_no']} (NO)</span>";
                            } else if ($row['conn_yes'] > 0) {
                                echo "<span class='conn-yes'>YES</span>";
                            } else {
                                echo "NO";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="grand-total-row">
                        <td style="padding-left: 15px;">Grand Total</td>
                        <td class="text-center"><?php echo $grandTotalDesktop; ?></td>
                        <td class="text-center"><?php echo $grandTotalLaptop; ?></td>
                        <td class="text-center"><?php echo $grandTotalModem; ?></td>
                        <td class="text-center"><?php echo $grandTotalSwitch; ?></td>
                        <td class="text-center"><?php echo $grandTotalPrinter; ?></td>
                        <td class="text-center"><?php echo $grandTotalRouters; ?></td>
                        <td class="text-center"><?php echo $grandTotalServerRoom; ?></td>
                        <td class="text-center">
                            <span class="conn-yes"><?php echo $grandTotalYes; ?> (YES)</span> 
                            <span class="conn-no"><?php echo $grandTotalNo; ?> (NO)</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 40px;">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="report-note">
            Note: Excluding Celphones and Devices connected to Municipal Wifi (ICT-MIS) and inter-office routers
        </div>
    </div>
</div>

<!-- SPREADSHEET SUMMARY TABLE VIEW - WITH FIXED SYSTEMS COLUMNS -->
<div id="spreadsheetSummaryContainer" class="spreadsheet-summary-container">
    <div class="export-buttons-bar">
        <button class="btn-export btn-export-excel" id="exportExcelBtn">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
        </button>
        <button class="btn-export btn-export-pdf" id="exportPdfBtn">
            <i class="bi bi-printer"></i> Print
        </button>
        <button class="btn-export btn-export-download" id="downloadPdfBtn">
            <i class="bi bi-download"></i> Download PDF
        </button>
    </div>
    <div class="spreadsheet-header">
        <div class="header-logo-container">
            <img src="../assest/images/logo1.png" alt="Logo" class="header-logo">
            <div class="header-text">
                <div class="sub-text">Republic of the Philippines</div>
                <div class="province-text">PROVINCE OF PANGASINAN - MUNICIPALITY OF MANGALDAN</div>
                <div class="spreadsheet-title">DATA FOR THE FORMULATION OF INFORMATION SYSTEMS STRATEGIC PLANNING<br>2026–2030</div>
                <div class="spreadsheet-subtitle spreadsheet-summary-line">Complete Form Data Summary</div>
            </div>
            <img src="../assest/images/logo3.png" alt="Logo" class="header-logo">
        </div>
    </div>
    
    <div class="table-scroll-summary">
        <table class="spreadsheet-table" id="spreadsheetTable">
            <thead>
                <tr>
                    <th rowspan="2" class="th-office min-w-180">Office</th>
                    <th rowspan="2" class="th-submission min-w-120">Submission</th>
                    <th colspan="2" class="th-office min-w-100">Item Details</th>
                    <th colspan="5" class="th-computer min-w-400">Computer Equipment</th>
                    <th colspan="3" class="th-printer min-w-180">Printer Devices</th>
                    <th colspan="3" class="th-network min-w-200">Network Devices</th>
                    <th colspan="8" class="th-systems min-w-400">SYSTEMS (EXISTING & PROPOSED)</th>
                </tr>
                <tr>
                    <th class="th-item-header">Item</th>
                    <th class="th-units">Units</th>
                    <th class="th-brand">Brand</th>
                    <th class="th-processor">Processor</th>
                    <th class="th-ram">RAM</th>
                    <th class="th-hdd">HDD</th>
                    <th class="th-ssd">SSD</th>
                    <th class="th-printer-inkjet">Inkjet</th>
                    <th class="th-printer-deskjet">Deskjet</th>
                    <th class="th-printer-dot">Dot</th>
                    <th class="th-network-switch">Switch</th>
                    <th class="th-network-router">Router</th>
                    <th class="th-network-modem">Modem</th>
                    <th class="th-system-ex1">EXISTING 1</th>
                    <th class="th-system-ex2">EXISTING 2</th>
                    <th class="th-system-ex3">EXISTING 3</th>
                    <th class="th-system-ex4">EXISTING 4</th>
                    <th class="th-system-pr1">PROPOSED 1</th>
                    <th class="th-system-pr2">PROPOSED 2</th>
                    <th class="th-system-pr3">PROPOSED 3</th>
                    <th class="th-system-pr4">PROPOSED 4</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($groupedSpreadsheetRecords)): ?>
                    <?php 
                    $currentFormId = null;
                    foreach ($groupedSpreadsheetRecords as $record): 
                        $isFirstRowOfSubmission = ($currentFormId != $record['form_id']);
                        if ($isFirstRowOfSubmission) {
                            $currentFormId = $record['form_id'];
                        }
                    ?>
                        <tr>
                            <?php if (!isset($record['grouped'])): ?>
                                <td class="office-name-cell" rowspan="<?php echo $record['rowspan']; ?>">
                                    <?php echo htmlspecialchars($record['office_name']); ?>
                                </td>
                                <td class="submission-cell text-center" rowspan="<?php echo $record['rowspan']; ?>">
                                    <?php echo date('Y-m-d', strtotime($record['date_submitted'])); ?>
                                </td>
                            <?php endif; ?>
                            <td class="item-cell"><?php echo htmlspecialchars($record['item'] ?: '—'); ?></td>
                            <td class="text-center units-cell"><?php echo htmlspecialchars($record['units'] ?: '0'); ?></td>
                            <td class="text-center brand-cell"><?php echo htmlspecialchars($record['brand'] ?: '—'); ?></td>
                            <td class="text-center processor-cell"><?php echo htmlspecialchars($record['processor'] ?: '—'); ?></td>
                            <td class="text-center ram-cell"><?php echo htmlspecialchars($record['ram'] ?: '—'); ?></td>
                            <td class="text-center hdd-cell"><?php echo htmlspecialchars($record['hdd'] ?: '—'); ?></td>
                            <td class="text-center ssd-cell"><?php echo htmlspecialchars($record['ssd'] ?: '—'); ?></td>
                            <td class="text-center printer-cell printer-inkjet-cell"><?php echo htmlspecialchars($record['inkjet_printer'] ?: '0'); ?></td>
                            <td class="text-center printer-cell printer-deskjet-cell"><?php echo htmlspecialchars($record['deskjet_printer'] ?: '0'); ?></td>
                            <td class="text-center printer-cell printer-dot-cell"><?php echo htmlspecialchars($record['dot_matrix_printer'] ?: '0'); ?></td>
                            <td class="text-center network-cell network-switch-cell"><?php echo htmlspecialchars($record['switch_hubs'] ?: '0'); ?></td>
                            <td class="text-center network-cell network-router-cell"><?php echo htmlspecialchars($record['routers'] ?: '0'); ?></td>
                            <td class="text-center network-cell network-modem-cell"><?php echo htmlspecialchars($record['modem'] ?: '0'); ?></td>
                            
                            <!-- Systems columns - only show on first row of each submission with rowspan -->
                            <?php if ($isFirstRowOfSubmission): ?>
                                <td class="systems-text system-ex1-cell" rowspan="<?php echo $record['rowspan']; ?>"><?php echo htmlspecialchars($record['system_1'] ?: '—'); ?></td>
                                <td class="systems-text system-ex2-cell" rowspan="<?php echo $record['rowspan']; ?>"><?php echo htmlspecialchars($record['system_2'] ?: '—'); ?></td>
                                <td class="systems-text system-ex3-cell" rowspan="<?php echo $record['rowspan']; ?>"><?php echo htmlspecialchars($record['system_3'] ?: '—'); ?></td>
                                <td class="systems-text system-ex4-cell" rowspan="<?php echo $record['rowspan']; ?>"><?php echo htmlspecialchars($record['system_4'] ?: '—'); ?></td>
                                <td class="systems-text system-pr1-cell" rowspan="<?php echo $record['rowspan']; ?>"><?php echo htmlspecialchars($record['proposed_system_1'] ?: '—'); ?></td>
                                <td class="systems-text system-pr2-cell" rowspan="<?php echo $record['rowspan']; ?>"><?php echo htmlspecialchars($record['proposed_system_2'] ?: '—'); ?></td>
                                <td class="systems-text system-pr3-cell" rowspan="<?php echo $record['rowspan']; ?>"><?php echo htmlspecialchars($record['proposed_system_3'] ?: '—'); ?></td>
                                <td class="systems-text system-pr4-cell" rowspan="<?php echo $record['rowspan']; ?>"><?php echo htmlspecialchars($record['proposed_system_4'] ?: '—'); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="23" class="text-center no-results-cell">
                            <i class="bi bi-inbox"></i>
                            <p class="mt-3 text-muted">No records found for the selected date range.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div> <!-- .main-content -->

</div> <!-- .d-flex -->

<!-- Modal for popup report detail -->
<style>
  #reportDetailModal {
    --bs-modal-width: 92vw;
  }
  #reportDetailModal .modal-dialog {
    width: 92vw !important;
    max-width: 92vw !important;
    height: 92vh !important;
    margin: 4vh auto !important;
  }
  #reportDetailModal .modal-content {
    height: 92vh !important;
    border-radius: 16px;
    overflow: hidden !important;
  }
  #reportDetailModal .modal-body {
    height: 92vh !important;
    padding: 0 !important;
    background: #f8f9fa !important;
  }
  #reportDetailModal .modal-header {
    position: absolute !important;
    right: 14px;
    top: 10px;
    z-index: 1000;
  }
  #reportDetailFrame {
    display: block;
    width: 100% !important;
    height: 100% !important;
    border: 0 !important;
  }
</style>
<div class="modal fade" id="reportDetailModal" tabindex="-1" aria-labelledby="reportDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close bg-white shadow-sm rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <iframe id="reportDetailFrame" src=""></iframe>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reportsGrid = document.querySelector('.dashboard-grid');
    const statsGrid = document.querySelector('.stats-grid');
    const spreadsheetViewBtn = document.getElementById('spreadsheetViewBtn');
    const summaryViewBtn = document.getElementById('summaryViewBtn');
    const dashboardViewBtn = document.getElementById('dashboardViewBtn');
    const summaryViewShortcut = document.getElementById('summaryViewShortcut');
    const spreadsheetContainer = document.getElementById('spreadsheetSummaryContainer');
    const summaryContainer = document.getElementById('summaryViewContainer');
    const tableView = document.getElementById('tableView');

    // Initialize Chart
    const ctx = document.getElementById('equipmentChart').getContext('2d');
    const equipmentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Computers', 'Printers', 'Networking'],
            datasets: [{
                data: [<?php echo $totalComputers; ?>, <?php echo $totalPrinters; ?>, <?php echo $totalNetworking; ?>],
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b'],
                hoverBackgroundColor: ['#4338ca', '#059669', '#d97706'],
                borderWidth: 0,
                cutout: '78%',
                borderRadius: 12,
                spacing: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 15
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    padding: 14,
                    titleFont: { size: 14, weight: '800', family: "'Inter', sans-serif" },
                    bodyFont: { size: 13, family: "'Inter', sans-serif" },
                    displayColors: true,
                    cornerRadius: 12,
                    boxPadding: 8,
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 1800,
                easing: 'easeOutExpo'
            },
            transitions: {
                active: {
                    animation: {
                        duration: 400
                    }
                }
            },
            hover: {
                mode: 'nearest',
                intersect: true
            }
        }
    });

    // Animate Total Units Counter
    const counterElement = document.querySelector('.chart-center-value');
    if (counterElement) {
        const targetValue = parseInt(counterElement.getAttribute('data-value'));
        const duration = 1500;
        const delay = 200; // Small delay for visual sync
        let startTime = null;

        function updateCounter(currentTime) {
            if (!startTime) startTime = currentTime;
            const elapsed = currentTime - (startTime + delay);
            
            if (elapsed < 0) {
                requestAnimationFrame(updateCounter);
                return;
            }

            const progress = Math.min(elapsed / duration, 1);
            const easeOut = 1 - Math.pow(2, -10 * progress); // easeOutExpo matching chart
            const currentValue = Math.floor(easeOut * targetValue);
            
            counterElement.innerText = currentValue;

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                counterElement.innerText = targetValue;
            }
        }
        requestAnimationFrame(updateCounter);
    }

    function showDashboard() {
        // Ensure both grids are shown
        if (reportsGrid) reportsGrid.classList.remove('d-none');
        if (statsGrid) statsGrid.classList.remove('d-none');
        
        // Hide containers
        if (spreadsheetContainer) spreadsheetContainer.classList.remove('active');
        if (summaryContainer) summaryContainer.classList.remove('active');
        
        // Reset button styles
        if (spreadsheetViewBtn) spreadsheetViewBtn.classList.remove('active-btn');
        if (summaryViewBtn) summaryViewBtn.classList.remove('active-btn');
        if (dashboardViewBtn) dashboardViewBtn.classList.add('active');

        // Emergency cleanup for stuck backdrops or dimming
        document.body.classList.remove('modal-open');
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(b => b.remove());
        document.body.style.overflow = 'auto';
        document.body.style.paddingRight = '0';
    }

    // Modal Handling with cleanup
    const reportDetailModalElement = document.getElementById('reportDetailModal');
    const reportDetailModal = reportDetailModalElement ? new bootstrap.Modal(reportDetailModalElement) : null;
    const reportDetailFrame = document.getElementById('reportDetailFrame');

    if (reportDetailModalElement) {
        reportDetailModalElement.addEventListener('hidden.bs.modal', function () {
            if (reportDetailFrame) reportDetailFrame.src = ''; // Clear iframe to stop loading
            // Ensure no backdrop is left behind
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(b => b.remove());
            document.body.style.overflow = 'auto';
        });
    }

    if (spreadsheetViewBtn) {
        spreadsheetViewBtn.addEventListener('click', function() {
            if (spreadsheetContainer && spreadsheetContainer.classList.contains('active')) {
                showDashboard();
            } else {
                if (reportsGrid) reportsGrid.classList.add('d-none');
                if (statsGrid) statsGrid.classList.add('d-none');
                if (summaryContainer) summaryContainer.classList.remove('active');
                if (spreadsheetContainer) spreadsheetContainer.classList.add('active');
                
                this.classList.add('active-btn');
                if (summaryViewBtn) summaryViewBtn.classList.remove('active-btn');
                if (dashboardViewBtn) dashboardViewBtn.classList.remove('active');
            }
        });
    }

    if (summaryViewBtn) {
        summaryViewBtn.addEventListener('click', function() {
            if (summaryContainer && summaryContainer.classList.contains('active')) {
                showDashboard();
            } else {
                if (reportsGrid) reportsGrid.classList.add('d-none');
                if (statsGrid) statsGrid.classList.add('d-none');
                if (spreadsheetContainer) spreadsheetContainer.classList.remove('active');
                if (summaryContainer) summaryContainer.classList.add('active');
                
                this.classList.add('active-btn');
                if (spreadsheetViewBtn) spreadsheetViewBtn.classList.remove('active-btn');
                if (dashboardViewBtn) dashboardViewBtn.classList.remove('active');
            }
        });
    }

    if (dashboardViewBtn) {
        dashboardViewBtn.addEventListener('click', function() {
            showDashboard();
        });
    }

    const reportClickables = document.querySelectorAll('.report-clickable');
    reportClickables.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const office = this.getAttribute('data-office');
            const date = this.getAttribute('data-date');
            reportDetailFrame.src = 'action/view_report_detail.php?office=' + encodeURIComponent(office) + '&date=' + encodeURIComponent(date);
            reportDetailModal.show();
        });
    });

    // SUMMARY EXPORT BUTTONS
    const exportSummaryExcelBtn = document.getElementById('exportSummaryExcelBtn');
    if (exportSummaryExcelBtn) {
        exportSummaryExcelBtn.addEventListener('click', function() {
            exportSummaryToExcel();
        });
    }

    const exportSummaryPdfBtn = document.getElementById('exportSummaryPdfBtn');
    if (exportSummaryPdfBtn) {
        exportSummaryPdfBtn.addEventListener('click', function() {
            window.print();
        });
    }

    function exportSummaryToExcel() {
        const table = document.getElementById('summaryTable');
        if (!table) return;
        
        const filename = 'RMAPS_Summary_Report_' + new Date().toISOString().slice(0, 10) + '.xls';
        
        let excelHtml = `<!DOCTYPE html><html><head><meta charset="UTF-8">
            <style>
                .header-container { text-align: center; margin-bottom: 20px; }
                .header-1 { font-size: 14pt; font-weight: bold; }
                .header-2 { font-size: 16pt; font-weight: bold; }
                .header-3 { font-size: 14pt; font-weight: bold; }
            </style>
        </head><body>`;
        excelHtml += `<div class="header-container">`;
        excelHtml += `<div class="header-1">LOCAL GOVERNMENT UNIT OF MANGALDAN</div>`;
        excelHtml += `<div class="header-2">SUMMARY - INVENTORY ON OFFICE DEVICES AND EQUIPMENTS</div>`;
        excelHtml += `<div class="header-3">FOR RMAPS (DIGITAL GOVERNANCE READINESS)</div>`;
        excelHtml += `</div><br>`;
        excelHtml += `<table border="1">`;
        excelHtml += table.innerHTML;
        excelHtml += `</table></body></html>`;
        
        const blob = new Blob([excelHtml], { type: 'application/vnd.ms-excel' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }

    function updateLayout(cols) {
        if (!reportsGrid || !tableView) return;
        
        if (cols === 'table') {
            exportSpreadsheetToExcel();
            return;
        } else {
            reportsGrid.classList.remove('d-none');
            tableView.classList.add('d-none');
            reportsGrid.classList.remove('cols-2', 'cols-3', 'cols-4');
            reportsGrid.classList.add('cols-' + cols);
        }
        
        layoutBtns.forEach(btn => {
            if (btn.getAttribute('data-cols') === cols) {
                btn.classList.add('active');
                if (layoutDropdownBtn) {
                    const iconClass = layoutIcons[cols] || 'bi-grid-3x3-gap';
                    layoutDropdownBtn.innerHTML = `<i class="bi ${iconClass}"></i>`;
                }
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // EXCEL EXPORT FUNCTION - MATCHING THE SPREADSHEET VIEW EXACTLY
    function exportSpreadsheetToExcel() {
        const originalTable = document.getElementById('spreadsheetTable');
        if (!originalTable) return;

        const headerLine1 = document.querySelector('.spreadsheet-header .sub-text')?.innerText || 'Republic of the Philippines';
        const headerLine2 = document.querySelector('.spreadsheet-header .province-text')?.innerText || 'PROVINCE OF PANGASINAN - MUNICIPALITY OF MANGALDAN';
        const headerTitle = document.querySelector('.spreadsheet-title')?.innerText || 'DATA FOR THE FORMULATION OF INFORMATION SYSTEMS STRATEGIC PLANNING\n2026–2030';
        const headerSummary = document.querySelector('.spreadsheet-summary-line')?.innerText || 'Complete Form Data Summary';
        const generatedDate = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const exportHeaders = [
            'Office', 'Submission', 'Item', 'Units',
            'Brand', 'Processor', 'RAM', 'HDD', 'SSD',
            'Inkjet', 'Deskjet', 'Dot', 'Switch', 'Router', 'Modem',
            'Existing 1', 'Existing 2', 'Existing 3', 'Existing 4',
            'Proposed 1', 'Proposed 2', 'Proposed 3', 'Proposed 4'
        ];

        const exportTable = document.createElement('table');
        exportTable.style.borderCollapse = 'collapse';
        exportTable.style.width = 'auto';
        exportTable.style.tableLayout = 'auto';

        const thead = document.createElement('thead');
        const headerRow1 = document.createElement('tr');
        const headerGroups = [
            { text: 'Office', rowspan: 2 },
            { text: 'Submission', rowspan: 2 },
            { text: 'Item Details', colspan: 2 },
            { text: 'Computer Equipment', colspan: 5 },
            { text: 'Printer Devices', colspan: 3 },
            { text: 'Network Devices', colspan: 3 },
            { text: 'SYSTEMS (Existing & Proposed)', colspan: 8 }
        ];

        headerGroups.forEach(group => {
            const th = document.createElement('th');
            th.textContent = group.text;
            if (group.colspan) th.setAttribute('colspan', group.colspan);
            if (group.rowspan) th.setAttribute('rowspan', group.rowspan);
            th.style.border = '1px solid #000000';
                th.style.padding = '8px 10px';
                th.style.backgroundColor = '#d9d9d9'; // Gray background for headers
                th.style.color = '#000000';
                th.style.fontWeight = 'bold';
                th.style.textAlign = 'center';
                th.style.verticalAlign = 'middle';
                headerRow1.appendChild(th);
            });
            thead.appendChild(headerRow1);

            const headerRow2 = document.createElement('tr');
            const subHeaders = [
                'Item', 'Units', 'Brand', 'Processor', 'RAM', 'HDD', 'SSD',
                'Inkjet', 'Deskjet', 'Dot', 'Switch', 'Router', 'Modem',
                'Existing 1', 'Existing 2', 'Existing 3', 'Existing 4',
                'Proposed 1', 'Proposed 2', 'Proposed 3', 'Proposed 4'
            ];
            subHeaders.forEach(text => {
                const th = document.createElement('th');
                th.textContent = text;
                th.style.border = '1px solid #000000';
                th.style.padding = '8px 10px';
                th.style.backgroundColor = '#d9d9d9'; // Gray background for headers
                th.style.color = '#000000';
                th.style.fontWeight = 'bold';
                th.style.textAlign = 'center';
                th.style.verticalAlign = 'middle';
                headerRow2.appendChild(th);
        });
        thead.appendChild(headerRow2);
        exportTable.appendChild(thead);

        const tbody = document.createElement('tbody');
        const originalRows = Array.from(originalTable.querySelectorAll('tbody tr'));

        originalRows.forEach(originalRow => {
            const cells = Array.from(originalRow.querySelectorAll('td'));
            const newRow = document.createElement('tr');

            const hasOfficeCell = originalRow.querySelector('.office-name-cell') !== null;
            if (hasOfficeCell) {
                const officeCell = cells.shift();
                const submissionCell = cells.shift();
                
                // Get rowspans
                const rowspan = officeCell.getAttribute('rowspan') || 1;
                
                const officeTd = document.createElement('td');
                officeTd.textContent = officeCell.textContent.trim();
                officeTd.setAttribute('rowspan', rowspan); // Preserve rowspan
                officeTd.style.border = '1px solid #000000';
                officeTd.style.padding = '8px 10px';
                officeTd.style.backgroundColor = '#ffffff';
                officeTd.style.verticalAlign = 'middle';
                officeTd.style.fontWeight = 'bold';
                
                const submissionTd = document.createElement('td');
                submissionTd.textContent = submissionCell.textContent.trim();
                submissionTd.setAttribute('rowspan', rowspan); // Preserve rowspan
                submissionTd.style.border = '1px solid #000000';
                submissionTd.style.padding = '8px 10px';
                submissionTd.style.backgroundColor = '#ffffff';
                submissionTd.style.verticalAlign = 'middle';
                submissionTd.style.textAlign = 'center';
                
                newRow.appendChild(officeTd);
                newRow.appendChild(submissionTd);
            }

            cells.forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell.textContent.trim();
                
                // Preserve rowspans for systems columns
                if (cell.getAttribute('rowspan')) {
                    td.setAttribute('rowspan', cell.getAttribute('rowspan'));
                    td.style.verticalAlign = 'middle';
                }
                
                td.style.border = '1px solid #000000';
                td.style.padding = '8px 10px';
                td.style.backgroundColor = '#ffffff';
                td.style.whiteSpace = 'nowrap';
                
                if (cell.classList && cell.classList.contains('text-center')) {
                    td.style.textAlign = 'center';
                }
                newRow.appendChild(td);
            });

            tbody.appendChild(newRow);
        });

        exportTable.appendChild(tbody);

        const excelHtml = `<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>RMAPS_Complete_Report_${new Date().toISOString().slice(0, 10)}</title>
            <style>
                @page {
                    size: landscape;
                    margin: 0.3in;
                }
                body {
                    margin: 0;
                    padding: 20px;
                    font-family: 'Segoe UI', Arial, sans-serif;
                    background: white;
                }
                .report-header {
                    text-align: center;
                    margin-bottom: 25px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #333;
                }
                .report-header h1 {
                    font-size: 18pt;
                    font-weight: bold;
                    margin: 0;
                    padding: 0;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    white-space: pre-line;
                }
                .report-header p.report-line1,
                .report-header p.report-line2 {
                    margin: 0;
                    font-size: 10pt;
                    font-weight: bold;
                    color: #000;
                    line-height: 1.2;
                }
                .report-header h2 {
                    font-size: 14pt;
                    font-weight: bold;
                    margin: 8px 0 0 0;
                    padding: 0;
                }
                .report-header p {
                    font-size: 11pt;
                    font-style: italic;
                    margin: 5px 0 0 0;
                    color: #555;
                }
                .generated-info {
                    text-align: center;
                    font-size: 9pt;
                    font-style: italic;
                    margin-top: 20px;
                    padding-top: 10px;
                    border-top: 1px solid #ccc;
                    color: #666;
                }
                table {
                    border-collapse: collapse;
                    width: auto !important;
                    table-layout: auto !important;
                    margin-top: 10px;
                    mso-table-lspace: 0pt;
                    mso-table-rspace: 0pt;
                }
                th, td {
                    border: 1px solid #000000;
                    padding: 8px 10px;
                    font-size: 10pt;
                    vertical-align: top;
                    white-space: nowrap;
                }
                th {
                    font-weight: bold;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <p class="report-line1">${escapeHtml(headerLine1)}</p>
                <p class="report-line2">${escapeHtml(headerLine2)}</p>
                <h1>${escapeHtml(headerTitle)}</h1>
                <p>${escapeHtml(headerSummary)}</p>
            </div>
            ${exportTable.outerHTML}
            <div class="generated-info">
                Generated by ICTMIS System - LGU Mangaldan | Date: ${generatedDate}
            </div>
        </body>
        </html>`;

        const blob = new Blob([excelHtml], { type: 'application/vnd.ms-excel' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'RMAPS_Complete_Report_' + new Date().toISOString().slice(0, 10) + '.xls';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        }).replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, function(c) {
            return c;
        });
    }

    function exportSpreadsheetToPDF() {
        const source = document.getElementById('spreadsheetSummaryContainer');
        if (!source) return;

        const worker = document.createElement('div');
        const style = document.createElement('style');
        style.innerHTML = `
            .pdf-worker-wrapper { padding: 8mm; background: white !important; color: black !important; font-family: Arial, sans-serif; }
            .spreadsheet-header { text-align: center !important; margin-bottom: 15px !important; border-bottom: 2px solid #000 !important; }
            .spreadsheet-title { font-size: 16pt !important; font-weight: bold !important; color: black !important; text-transform: uppercase !important; margin-bottom: 6px !important; }
            .spreadsheet-subtitle, .spreadsheet-summary-line { font-size: 10pt !important; font-style: italic !important; color: #333 !important; margin-top: 0 !important; }
            .spreadsheet-table { 
                width: 100% !important; 
                border-collapse: collapse !important; 
                table-layout: fixed !important; 
                margin-top: 16px !important; 
                border: 1px solid #000 !important;
                min-width: 0 !important;
            }
            .spreadsheet-table th, .spreadsheet-table td { 
                border: 1px solid black !important; 
                padding: 3px 2px !important; 
                font-size: 6.5pt !important; 
                color: black !important; 
                background: white !important; 
                vertical-align: middle !important;
                white-space: normal !important;
                word-break: break-all !important;
                overflow-wrap: break-word !important;
                min-width: 0 !important;
                line-height: 1.1 !important;
            }
            
            /* Override the min-width classes that might be applied */
            .min-w-180, .min-w-120, .min-w-100, .min-w-400, .min-w-200, .min-w-250 {
                min-width: 0 !important;
            }

            .spreadsheet-table thead th { 
                background: #d1d5db !important; 
                font-weight: bold !important; 
                text-align: center !important; 
                vertical-align: middle !important; 
            }

            /* Column Width Allocations using specific classes */
            .th-office, .office-name-cell { width: 8% !important; }
            .th-submission, .submission-cell { width: 6% !important; }
            .th-item-header, .item-cell { width: 6% !important; }
            .th-units, .units-cell { width: 3% !important; }
            .th-brand, .brand-cell { width: 4% !important; }
            .th-processor, .processor-cell { width: 6% !important; }
            .th-ram, .ram-cell { width: 3% !important; }
            .th-hdd, .hdd-cell { width: 3% !important; }
            .th-ssd, .ssd-cell { width: 3% !important; }
            
            .th-printer-inkjet, .printer-inkjet-cell,
            .th-printer-deskjet, .printer-deskjet-cell,
            .th-printer-dot, .printer-dot-cell,
            .th-network-switch, .network-switch-cell,
            .th-network-router, .network-router-cell,
            .th-network-modem, .network-modem-cell { width: 3% !important; }
            
            .systems-text, 
            .th-system-ex1, .system-ex1-cell,
            .th-system-ex2, .system-ex2-cell,
            .th-system-ex3, .system-ex3-cell,
            .th-system-ex4, .system-ex4-cell,
            .th-system-pr1, .system-pr1-cell,
            .th-system-pr2, .system-pr2-cell,
            .th-system-pr3, .system-pr3-cell,
            .th-system-pr4, .system-pr4-cell { 
                width: 5% !important; 
                font-size: 6pt !important;
            }

            .text-center { text-align: center !important; }
            .office-name-cell { font-weight: bold !important; background: white !important; }
            .export-buttons-bar { display: none !important; }
            .table-scroll-summary { overflow: visible !important; width: auto !important; }
            .pdf-footer { margin-top: 15px !important; text-align: center !important; font-size: 8pt !important; color: #444 !important; border-top: 1px solid #000 !important; }
        `;

        const content = source.cloneNode(true);
        const buttons = content.querySelector('.export-buttons-bar');
        if (buttons) buttons.remove();

        const footer = document.createElement('div');
        footer.className = 'pdf-footer';
        footer.innerHTML = `Generated by ICTMIS System | Date: ${new Date().toLocaleDateString()}`;

        content.style.display = 'block';
        content.style.opacity = '1';
        content.style.transform = 'none';
        content.style.visibility = 'visible';

        worker.className = 'pdf-worker-wrapper';
        worker.appendChild(style);
        worker.appendChild(content);
        worker.appendChild(footer);

        const opt = {
            margin: [5, 5, 5, 5],
            filename: 'ISSP_Complete_Report_' + new Date().toISOString().slice(0, 10) + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 3,
                useCORS: true,
                backgroundColor: '#ffffff',
                scrollX: 0,
                scrollY: 0,
                logging: false
            },
            jsPDF: { unit: 'mm', format: 'a3', orientation: 'landscape' },
            pagebreak: { mode: ['css', 'legacy'] }
        };

        html2pdf().set(opt).from(worker).save();
    }
    
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    if (exportExcelBtn) exportExcelBtn.addEventListener('click', exportSpreadsheetToExcel);

    const exportPdfBtn = document.getElementById('exportPdfBtn');
    if (exportPdfBtn) exportPdfBtn.addEventListener('click', function() {
        window.print();
    });

    // DOWNLOAD PDF FUNCTIONS
    const downloadSummaryPdfBtn = document.getElementById('downloadSummaryPdfBtn');
    if (downloadSummaryPdfBtn) {
        downloadSummaryPdfBtn.addEventListener('click', function() {
            const source = document.getElementById('summaryViewContainer');
            if (!source) return;

            const worker = document.createElement('div');
            const style = document.createElement('style');
            style.innerHTML = `
                @page { size: a4 landscape; margin: 0; }
                .pdf-worker-wrapper { padding: 12mm; background: white !important; color: #000000 !important; font-family: Arial, sans-serif; }
                .spreadsheet-header { text-align: center !important; margin-bottom: 20px !important; background: white !important; }
                .header-logo-container { display: flex !important; align-items: center !important; justify-content: center !important; gap: 20px !important; width: 100% !important; margin-bottom: 10px !important; }
                .header-logo { width: 60px !important; height: 60px !important; object-fit: contain !important; }
                .header-text { flex: none !important; text-align: center !important; }
                .spreadsheet-header .sub-text, 
                .spreadsheet-header .province-text, 
                .spreadsheet-header .spreadsheet-title, 
                .spreadsheet-header .spreadsheet-subtitle { color: #000000 !important; display: block !important; font-weight: bold !important; margin: 0 !important; }
                .spreadsheet-title-1 { font-size: 13pt !important; font-weight: bold !important; color: #000000 !important; }
                .spreadsheet-title-2 { font-size: 15pt !important; font-weight: bold !important; margin-top: 5px !important; color: #000000 !important; }
                .spreadsheet-title-3 { font-size: 12pt !important; font-weight: bold !important; margin-top: 5px !important; color: #000000 !important; }
                .spreadsheet-table { width: 100% !important; border-collapse: collapse !important; table-layout: fixed !important; background: white !important; }
                .spreadsheet-table th, .spreadsheet-table td { border: 1px solid #000000 !important; padding: 8px 6px !important; font-size: 9pt !important; color: #000000 !important; background: white !important; word-wrap: break-word !important; opacity: 1 !important; }
                .spreadsheet-table thead tr:first-child th { background: #1e293b !important; color: #ffffff !important; font-weight: bold !important; border: 1px solid #334155 !important; }
                .spreadsheet-table thead tr:last-child th { background: #4b5563 !important; color: #ffffff !important; font-weight: bold !important; border: 1px solid #334155 !important; }
                .text-center { text-align: center !important; color: #000000 !important; }
                .office-name-cell { font-weight: bold !important; text-align: left !important; padding-left: 12px !important; color: #000000 !important; background: white !important; }
                .grand-total-row { font-weight: bold !important; background: #f3f4f6 !important; color: #000000 !important; }
                .grand-total-row td { color: #000000 !important; }
                .export-buttons-bar { display: none !important; }
                .report-note { font-style: italic !important; font-size: 9pt !important; margin-top: 10px !important; color: #000000 !important; opacity: 1 !important; }
                * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            `;

            const content = source.cloneNode(true);
            const buttons = content.querySelector('.export-buttons-bar');
            if (buttons) buttons.remove();
            content.style.display = 'block';
            content.style.visibility = 'visible';

            worker.className = 'pdf-worker-wrapper';
            worker.appendChild(style);
            worker.appendChild(content);

            const opt = {
                margin: 5,
                filename: 'RMAPS_Summary_Report_' + new Date().toISOString().slice(0, 10) + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: {
                    scale: 3,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    scrollX: 0,
                    scrollY: 0,
                    logging: false
                },
                jsPDF: { unit: 'mm', format: 'a3', orientation: 'landscape' },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            html2pdf().set(opt).from(worker).save();
        });
    }

    const downloadPdfBtn = document.getElementById('downloadPdfBtn');
    if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', function() {
            const source = document.getElementById('spreadsheetSummaryContainer');
            if (!source) return;

            const worker = document.createElement('div');
            const style = document.createElement('style');
            style.innerHTML = `
                @page { size: A3 landscape; margin: 0; }
                .pdf-worker-wrapper { 
                    padding: 8mm; 
                    background: #ffffff !important; 
                    color: #000000 !important; 
                    font-family: Arial, sans-serif; 
                }
                /* FORCE BLACK TEXT ON EVERYTHING EXCEPT HEADERS */
                .pdf-worker-wrapper td,
                .pdf-worker-wrapper p,
                .pdf-worker-wrapper span,
                .pdf-worker-wrapper .submission-cell,
                .pdf-worker-wrapper .office-name-cell { 
                    color: #000000 !important; 
                    background-color: #ffffff !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    text-shadow: none !important;
                    -webkit-text-fill-color: #000000 !important;
                    box-shadow: none !important;
                }
                .spreadsheet-header { text-align: center !important; margin-bottom: 15px !important; border-bottom: 2px solid #000 !important; }
                .header-logo-container { display: flex !important; align-items: center !important; justify-content: center !important; gap: 20px !important; width: 100% !important; margin-bottom: 10px !important; }
                .header-logo { width: 60px !important; height: 60px !important; object-fit: contain !important; }
                .header-text { flex: none !important; text-align: center !important; }
                .spreadsheet-header .sub-text, 
                .spreadsheet-header .province-text, 
                .spreadsheet-header .spreadsheet-title, 
                .spreadsheet-header .spreadsheet-subtitle { color: #000000 !important; display: block !important; font-weight: bold !important; margin: 0 !important; }
                .spreadsheet-title { font-size: 16pt !important; font-weight: bold !important; text-transform: uppercase !important; }
                .spreadsheet-subtitle { font-size: 10pt !important; font-style: italic !important; }
                
                .spreadsheet-table { 
                    width: 100% !important; 
                    border-collapse: collapse !important; 
                    table-layout: fixed !important; 
                    border: 1px solid #000 !important; 
                    min-width: 0 !important;
                }
                .spreadsheet-table th, .spreadsheet-table td { 
                    border: 1px solid #000000 !important; 
                    padding: 3px 2px !important; 
                    font-size: 6.5pt !important; 
                    vertical-align: middle !important;
                    white-space: normal !important;
                    word-break: break-all !important;
                    overflow-wrap: break-word !important;
                    min-width: 0 !important;
                    line-height: 1.1 !important;
                }
                
                /* Override the min-width classes that might be applied */
                .min-w-180, .min-w-120, .min-w-100, .min-w-400, .min-w-200, .min-w-250 {
                    min-width: 0 !important;
                }

                .spreadsheet-table thead tr:first-child th { 
                    background-color: #1e293b !important; 
                    color: #ffffff !important;
                    font-weight: bold !important;
                    border: 1px solid #334155 !important;
                }
                .spreadsheet-table thead tr:last-child th { 
                    background-color: #4b5563 !important; 
                    color: #ffffff !important;
                    font-weight: bold !important;
                    border: 1px solid #334155 !important;
                }
                
                /* Column Width Allocations using specific classes */
                .th-office, .office-name-cell { width: 8% !important; }
                .th-submission, .submission-cell { width: 6% !important; }
                .th-item-header, .item-cell { width: 6% !important; }
                .th-units, .units-cell { width: 3% !important; }
                .th-brand, .brand-cell { width: 4% !important; }
                .th-processor, .processor-cell { width: 6% !important; }
                .th-ram, .ram-cell { width: 3% !important; }
                .th-hdd, .hdd-cell { width: 3% !important; }
                .th-ssd, .ssd-cell { width: 3% !important; }
                
                .th-printer-inkjet, .printer-inkjet-cell,
                .th-printer-deskjet, .printer-deskjet-cell,
                .th-printer-dot, .printer-dot-cell,
                .th-network-switch, .network-switch-cell,
                .th-network-router, .network-router-cell,
                .th-network-modem, .network-modem-cell { width: 3% !important; }
                
                .systems-text, 
                .th-system-ex1, .system-ex1-cell,
                .th-system-ex2, .system-ex2-cell,
                .th-system-ex3, .system-ex3-cell,
                .th-system-ex4, .system-ex4-cell,
                .th-system-pr1, .system-pr1-cell,
                .th-system-pr2, .system-pr2-cell,
                .th-system-pr3, .system-pr3-cell,
                .th-system-pr4, .system-pr4-cell { 
                    width: 5% !important; 
                    font-size: 6pt !important;
                }

                .pdf-footer { margin-top: 15px !important; text-align: center !important; font-size: 8pt !important; border-top: 1px solid #000 !important; }
                * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            `;

            const content = source.cloneNode(true);
            const buttons = content.querySelector('.export-buttons-bar');
            if (buttons) buttons.remove();

            const footer = document.createElement('div');
            footer.className = 'pdf-footer';
            footer.innerHTML = `Generated by ICTMIS System | Date: ${new Date().toLocaleDateString()}`;

            content.style.display = 'block';
            content.style.opacity = '1';
            content.style.transform = 'none';
            content.style.visibility = 'visible';

            worker.className = 'pdf-worker-wrapper';
            worker.appendChild(style);
            worker.appendChild(content);
            worker.appendChild(footer);

            const opt = {
                margin: [5, 5, 5, 5],
                filename: 'RMAPS_Complete_Report_' + new Date().toISOString().slice(0, 10) + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: {
                    scale: 3,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    scrollX: 0,
                    scrollY: 0,
                    logging: false
                },
                jsPDF: { unit: 'mm', format: 'a3', orientation: 'landscape' },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            html2pdf().set(opt).from(worker).save();
        });
    }

    // Modal functionality
    const reportModal = new bootstrap.Modal(document.getElementById('reportDetailModal'));
    const iframe = document.getElementById('reportDetailFrame');

    function openReportModal(office, date) {
        iframe.src = '<?php echo $adminBase; ?>modules/view_report_modal.php?office=' + encodeURIComponent(office) + '&date=' + encodeURIComponent(date);
        reportModal.show();
    }

    document.querySelectorAll('.report-clickable').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function(e) {
            if (e.target.closest('.report-box-footer')) return;
            openReportModal(this.getAttribute('data-office'), this.getAttribute('data-date'));
        });
    });

    // Print report handler
    document.querySelectorAll('.print-report-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const office = this.getAttribute('data-office');
            const date = this.getAttribute('data-date');
            const printUrl = 'modules/view_report_modal.php?office=' + encodeURIComponent(office) + '&date=' + encodeURIComponent(date) + '&print=true';
            
            // Open in a new window/tab
            const printWindow = window.open(printUrl, '_blank');
            
            // The view_report_modal.php should handle the print if the parameter is present
            // or we can try to trigger it from here after load
            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.print();
                }, 500);
            };
        });
    });

    // PDF report handler
    document.querySelectorAll('.pdf-report-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const office = this.getAttribute('data-office');
            const date = this.getAttribute('data-date');
            const pdfUrl = 'modules/view_report_modal.php?office=' + encodeURIComponent(office) + '&date=' + encodeURIComponent(date);
            
            // Show loading feedback
            const originalTitle = this.title;
            this.title = 'Processing PDF...';
            this.innerHTML = '<i class="bi bi-hourglass-split"></i>';
            this.style.pointerEvents = 'none';
            this.style.opacity = '0.6';

            // Create a hidden iframe to load the report content
            const hiddenIframe = document.createElement('iframe');
            hiddenIframe.style.display = 'none';
            hiddenIframe.src = pdfUrl;
            document.body.appendChild(hiddenIframe);
            
            // Wait for iframe to load
            hiddenIframe.onload = function() {
                try {
                    const iframeDoc = hiddenIframe.contentDocument || hiddenIframe.contentWindow.document;
                    const sourceElement = iframeDoc.querySelector('.sheet');
                    
                    if (!sourceElement) {
                        alert('Error: Could not find report content.');
                        resetBtn();
                        return;
                    }

                    // Create worker element for PDF generation
                    const workerElement = document.createElement('div');
                    workerElement.style.padding = '10mm';
                    workerElement.style.background = 'white';
                    
                    // Copy all styles from the iframe to the worker
                    const iframeStyles = iframeDoc.querySelectorAll('style, link[rel="stylesheet"]');
                    iframeStyles.forEach(style => {
                        workerElement.appendChild(style.cloneNode(true));
                    });
                    
                    workerElement.innerHTML += sourceElement.innerHTML;

                    // Remove actions/buttons from the worker element
                    const actions = workerElement.querySelector('.actions');
                    if (actions) actions.remove();

                    // Inject styles specifically for PDF output (Match history.php style)
                    const pdfStyle = document.createElement('style');
                    pdfStyle.innerHTML = `
                        @page { size: a4 landscape; margin: 0; }
                        .sheet { padding: 0 !important; width: 100% !important; border: none !important; box-shadow: none !important; margin: 0 !important; min-height: auto !important; }
                        .top-head { margin-bottom: 20px !important; padding-bottom: 15px !important; display: flex !important; align-items: center !important; justify-content: space-between !important; }
                        .top-head img { width: 60px !important; height: 60px !important; object-fit: contain !important; }
                        .title-container h1 { font-size: 14px !important; line-height: 1.2 !important; }
                        .section-bar { 
                            margin-top: 15px !important; 
                            margin-bottom: 8px !important; 
                            padding: 8px 12px !important; 
                            background: #f8fafc !important;
                            font-size: 11px !important;
                            -webkit-print-color-adjust: exact;
                        }
                        .info-grid { gap: 20px !important; padding: 5px 0 !important; display: grid !important; grid-template-columns: 1fr 1fr !important; }
                        .equip-table thead tr:first-child th { 
                            background: #1e293b !important; 
                            color: #ffffff !important; 
                            padding: 6px 4px !important; 
                            font-size: 9px !important; 
                            vertical-align: middle !important; 
                            -webkit-print-color-adjust: exact !important;
                        }
                        .equip-table thead tr:last-child th { 
                            background: #4b5563 !important; 
                            color: #ffffff !important; 
                            padding: 6px 4px !important; 
                            font-size: 9px !important; 
                            vertical-align: middle !important; 
                            -webkit-print-color-adjust: exact !important;
                        }
                        .equip-table td { padding: 6px 4px !important; font-size: 9px !important; vertical-align: middle !important; }
                        .equip-table td.fw-bold { white-space: normal !important; word-break: break-word !important; width: 100px !important; }
                        .equip-table img { max-width: 60px !important; max-height: 45px !important; }
                        .systems-container { gap: 20px !important; margin-top: 5px !important; display: grid !important; grid-template-columns: 1fr 1fr !important; }
                        .system-list li { padding: 4px 10px !important; font-size: 10px !important; }
                        .conn-grid { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
                        .conn-item { padding: 8px !important; }
                        .actions, .btn-action, .issp-footer-actions, .no-print, .close-modal { display: none !important; }
                        .section-bar[style*="page-break-before: always"] {
                            page-break-before: always !important;
                            margin-top: 0 !important;
                        }
                    `;
                    workerElement.appendChild(pdfStyle);

                    // Options for html2pdf (Match history.php options)
                    const opt = {
                        margin: 5,
                        filename: 'ISSP_Report_' + office.replace(/\s+/g, '_') + '_' + date + '.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { 
                            scale: 2, 
                            useCORS: true,
                            letterRendering: true
                        },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
                        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
                    };

                    // Generate PDF
                    html2pdf().set(opt).from(workerElement).save().then(() => {
                        resetBtn();
                        document.body.removeChild(hiddenIframe);
                    }).catch(err => {
                        console.error('PDF Error:', err);
                        alert('Error generating PDF: ' + err.message);
                        resetBtn();
                        document.body.removeChild(hiddenIframe);
                    });
                } catch (err) {
                    console.error('Error:', err);
                    alert('Error processing PDF: ' + err.message);
                    resetBtn();
                    document.body.removeChild(hiddenIframe);
                }
            };

            function resetBtn() {
                const btn = document.querySelector(`.pdf-report-btn[data-office="${office}"][data-date="${date}"]`);
                if (btn) {
                    btn.title = originalTitle;
                    btn.innerHTML = '<i class="bi bi-file-earmark-pdf"></i>';
                    btn.style.pointerEvents = 'auto';
                    btn.style.opacity = '1';
                }
            }
        });
    });
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>
</html>