<?php
include "../../config.php";
include "../../config/auth_check.php";

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    http_response_code(403);
    exit("Unauthorized");
}

$office = isset($_GET['office']) ? mysqli_real_escape_string($conn, $_GET['office']) : '';
$date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

if ($office === '' || $date === '') {
    exit("Missing report parameters.");
}

// Main query to get form data
$query = mysqli_query($conn, "
    SELECT
        o.office_name,
        f.date_submitted as date_submitted,
        f.id as form_id,
        ce.item,
        ce.number_of_units as units,
        ce.brand,
        ce.processor,
        ce.ram,
        ce.hdd,
        ce.ssd,
        COALESCE(ce.equipment_image, (SELECT equipment_image FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted AND uif.item = ce.item LIMIT 1)) as equipment_image,
        COALESCE(s.system_1, (SELECT system1 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as system1,
        COALESCE(s.system_2, (SELECT system2 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as system2,
        COALESCE(s.system_3, (SELECT system3 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as system3,
        COALESCE(s.system_4, (SELECT system4 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as system4,
        COALESCE(s.system_5, (SELECT system5 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as system5,
        COALESCE(s.proposed_system_1, (SELECT proposed_system1 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as proposed_system1,
        COALESCE(s.proposed_system_2, (SELECT proposed_system2 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as proposed_system2,
        COALESCE(s.proposed_system_3, (SELECT proposed_system3 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as proposed_system3,
        COALESCE(s.proposed_system_4, (SELECT proposed_system4 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as proposed_system4,
        COALESCE(s.proposed_system_5, (SELECT proposed_system5 FROM user_issp_form uif WHERE uif.office_name = f.office_name AND uif.date_submitted = f.date_submitted ORDER BY id ASC LIMIT 1)) as proposed_system5,
        IF(ic.is_centralized_isp = 1, 'Yes', 'No') AS isp_server,
        IF(ic.is_centralized_lan_wan = 1, 'Yes', 'No') AS lan_connection,
        IF(ic.is_centralized_db_server = 1, 'Yes', 'No') AS database_server,
        IF(ic.is_other_isp_via_office_plan = 1, 'Yes', 'No') AS other_isp,
        COALESCE(ic.isp_name, 'N/A') AS isp_name,
        COALESCE(ic.bandwidth, 'N/A') AS bandwidth,
        COALESCE(ic.pabx, 'N/A') AS pabx,
        COALESCE(ic.telephone_numbers, 'N/A') AS telephone_numbers,
        COALESCE(ic.base_radios, 0) AS base_radios,
        COALESCE(ic.handheld_personal, 0) AS handheld_personal,
        COALESCE(ic.handheld_lgu, 0) AS handheld_lgu,
        COALESCE(ic.handheld_total, 0) AS handheld_total
    FROM form f
    JOIN offices o ON f.office_name = o.office_name
    LEFT JOIN computer_equipment ce ON f.id = ce.form_id
    LEFT JOIN systems s ON f.id = s.form_id
    LEFT JOIN internet_connections ic ON f.id = ic.form_id
    WHERE o.office_name = '$office' AND f.date_submitted = '$date'
");

$items = [];
while ($row = mysqli_fetch_assoc($query)) {
    $items[] = $row;
}

if (empty($items)) {
    exit("No report data found.");
}

// Fetch form ID
$form_id_query = mysqli_query($conn, "SELECT id FROM form WHERE office_name = '$office' AND date_submitted = '$date' LIMIT 1");
$form_row = mysqli_fetch_assoc($form_id_query);
$form_id = $form_row ? $form_row['id'] : 0;

// Fetch Printer Devices
$printer_devices = [];
if ($form_id) {
    $printers_query = mysqli_query($conn, "SELECT printer_type, model, quantity, equipment_image FROM printer_devices WHERE form_id = $form_id ORDER BY id");
    while ($p_row = mysqli_fetch_assoc($printers_query)) {
        $printer_devices[] = $p_row;
    }
}

// Fetch Network Devices
$network_devices = [];
if ($form_id) {
    $networks_query = mysqli_query($conn, "SELECT device_type, model, quantity, equipment_image FROM network_devices WHERE form_id = $form_id ORDER BY id");
    while ($n_row = mysqli_fetch_assoc($networks_query)) {
        $network_devices[] = $n_row;
    }
}

// Fallback to old table if new tables are empty
$other_equipment_data = [];
if (empty($printer_devices) && empty($network_devices) && $form_id) {
    $old_equipment_query = mysqli_query($conn, "SELECT * FROM other_ict_equipment WHERE form_id = $form_id LIMIT 1");
    $old_equipment = mysqli_fetch_assoc($old_equipment_query);
    
    if ($old_equipment) {
        $equipment_list = [
            'inkjet_printer' => 'Inkjet Printer',
            'deskjet_printer' => 'Deskjet Printer',
            'dot_matrix_printer' => 'Dot Matrix Printer',
            'switch_hubs' => 'Switch / Hubs',
            'routers' => 'Routers',
            'modem' => 'Modem'
        ];
        
        foreach ($equipment_list as $db_key => $display_name) {
            $units = (int)($old_equipment[$db_key] ?? 0);
            $models_raw = $old_equipment[$db_key . '_model'] ?? '';
            
            if ($units > 0) {
                if (!empty($models_raw) && strpos($models_raw, ',') !== false) {
                    $model_array = explode(',', $models_raw);
                    $units_per_model = floor($units / count($model_array));
                    $remainder = $units % count($model_array);
                    
                    foreach ($model_array as $index => $model_name) {
                        $other_equipment_data[] = [
                            'type' => $display_name,
                            'units' => $units_per_model + ($index < $remainder ? 1 : 0),
                            'model' => trim($model_name),
                            'image' => null
                        ];
                    }
                } else {
                    $other_equipment_data[] = [
                        'type' => $display_name,
                        'units' => $units,
                        'model' => !empty($models_raw) ? $models_raw : 'N/A',
                        'image' => null
                    ];
                }
            }
        }
    }
} else {
    // Combine new data
    foreach ($printer_devices as $p) {
        $other_equipment_data[] = [
            'type' => $p['printer_type'],
            'units' => $p['quantity'],
            'model' => $p['model'],
            'image' => $p['equipment_image']
        ];
    }
    foreach ($network_devices as $n) {
        $other_equipment_data[] = [
            'type' => $n['device_type'],
            'units' => $n['quantity'],
            'model' => $n['model'],
            'image' => $n['equipment_image']
        ];
    }
}

$head = $items[0];
function esc($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Prepare existing and proposed systems arrays
$existing = [];
for ($i = 1; $i <= 5; $i++) {
    if (!empty($head["system$i"])) {
        $existing[] = $head["system$i"];
    }
}

$proposed = [];
for ($i = 1; $i <= 5; $i++) {
    if (!empty($head["proposed_system$i"])) {
        $proposed[] = $head["proposed_system$i"];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMAPS Report Preview</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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
        body { background: #cbd5e0; margin: 0; font-family: 'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; color: var(--text-dark); -webkit-print-color-adjust: exact; }
        .sheet { background: #fff; margin: 10px auto; width: 98%; max-width: 1000px; min-height: auto; padding: 30px 40px; box-shadow: 0 0 40px rgba(0,0,0,0.15); position: relative; border-radius: 4px; border: 1px solid #d1d5db; }
        
        .top-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px double var(--primary-system); padding-bottom: 15px; margin-bottom: 20px; gap: 20px; }
        .top-head img { width: 70px; height: 70px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); }
        .title-container { text-align: center; flex-grow: 1; }
        .title-container .sub-text { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .title-container h1 { margin: 0; font-size: 18px; font-weight: 900; color: var(--primary-system); line-height: 1.1; letter-spacing: -0.5px; }
        .title-container .year-range { font-size: 14px; font-weight: 700; color: #334155; margin-top: 6px; display: inline-block; padding: 2px 12px; background: var(--secondary-system); border-radius: 20px; }

        .section-bar { 
            background: var(--section-bg); 
            border-bottom: 2px solid var(--primary-system);
            padding: 8px 0; 
            font-weight: 800; 
            font-size: 13px; 
            color: var(--primary-system); 
            margin-top: 15px; 
            margin-bottom: 10px; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
            page-break-after: avoid;
        }
        
        .equip-table, .systems-container, .conn-grid {
            page-break-inside: avoid;
        }

        .sheet > div, .sheet > table {
            page-break-inside: avoid;
        }
        .section-bar::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 18px;
            background: var(--primary-system);
            border-radius: 2px;
        }
        
        .info-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; padding: 5px 0; }
        .info-item { display: flex; flex-direction: column; border-bottom: 1px dashed var(--border-color); padding-bottom: 4px; }
        .info-label { font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px; }
        .info-value { font-size: 14px; font-weight: 700; color: var(--text-dark); }

        .equip-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 5px; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; }
        .equip-table th { background: var(--primary-system); color: #fff; font-weight: 700; padding: 10px 8px; text-align: center; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; border-right: 1px solid rgba(255,255,255,0.1); }
        .equip-table td { padding: 8px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: middle; font-size: 12px; color: #334155; }
        .equip-table td:last-child { border-right: none; }
        .equip-table tr:last-child td { border-bottom: none; }
        .equip-table tr:nth-child(even) { background-color: #f8fafc; }
        .equip-table .total-row { background: #f1f5f9; font-weight: 800; }
        .equip-table .total-row td { border-top: 2px solid var(--primary-system); }

        .systems-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 5px; }
        .system-list { margin: 0; padding: 0; list-style: none; }
        .system-list li { padding: 6px 12px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px; font-weight: 600; color: #475569; font-size: 12px; }
        .system-list li:last-child { border-bottom: none; }
        .system-list li svg { color: var(--accent-blue); flex-shrink: 0; }
        .system-list li.empty-list { color: var(--text-muted); font-style: italic; font-weight: normal; }

        .conn-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 5px; }
        .conn-item { background: #fff; border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; transition: all 0.2s; display: flex; flex-direction: column; gap: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .conn-item:hover { border-color: var(--accent-blue); transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .conn-item .info-label { border-bottom: 1px solid var(--secondary-system); padding-bottom: 3px; margin-bottom: 3px; }
        .conn-item .info-value { font-size: 12px; color: var(--primary-system); }
        
        .footer-note { margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 15px; font-size: 9px; color: var(--text-muted); text-align: center; font-style: italic; letter-spacing: 0.5px; }

        .actions { position: fixed; bottom: 30px; right: 30px; display: flex; gap: 15px; z-index: 100; }
        .btn-action { border: none; border-radius: 8px; color: #fff; font-size: 13px; font-weight: 700; padding: 12px 24px; cursor: pointer; display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .btn-excel { background: #059669; }
        .btn-print { background: var(--primary-system); }

        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { background: #fff; padding: 0; margin: 0; }
            .sheet { margin: 0; width: 100%; max-width: none; box-shadow: none; border: none; padding: 15mm 15mm; }
            .actions { display: none !important; }
            .conn-item:hover { transform: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="top-head">
            <img src="../../assest/images/logo1.png" alt="LGU Logo">
            <div class="title-container">
                <div class="sub-text">Republic of the Philippines</div>
                <div class="province-text">PROVINCE OF PANGASINAN - MUNICIPALITY OF MANGALDAN</div>
                <h1>DATA FOR THE FORMULATION OF INFORMATION SYSTEMS  
                       STRATEGIC PLANNING 2026–2030</h1>
            </div>
            <img src="../../assest/images/logo3.png" alt="ICT Logo">
        </div>

        <div class="section-bar">Office Information</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Department / Office</span>
                <span class="info-value"><?= esc($head['office_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Date of Submission</span>
                <span class="info-value"><?= date('F d, Y', strtotime($head['date_submitted'])); ?></span>
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
                $totalUnits = 0;
                foreach ($items as $it): 
                    $totalUnits += (int)$it['units'];
                ?>
                    <tr>
                        <td class="text-center">
                            <?php if (!empty($it['equipment_image'])): ?>
                                <?php 
                                    $imagePath = $it['equipment_image'];
                                    if (strpos($imagePath, 'uploads/') === false) {
                                        $imagePath = 'uploads/equipment_images/' . $imagePath;
                                    }
                                ?>
                                <img src="../../<?= esc($imagePath); ?>" alt="Equipment Image" style="max-width: 100px; max-height: 70px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 12px;">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold"><?= esc($it['item']); ?></td>
                        <td class="text-center fw-bold" style="color: var(--primary-system);"><?= esc($it['units']); ?></td>
                        <td><?= esc($it['brand'] ?: '-'); ?></td>
                        <td><?= esc($it['processor'] ?: '-'); ?></td>
                        <td class="text-center"><?= esc($it['ram'] ?: '-'); ?></td>
                        <td>
                            <?php 
                                $storage = [];
                                if(!empty($it['hdd'])) $storage[] = esc($it['hdd']);
                                if(!empty($it['ssd'])) $storage[] = esc($it['ssd']);
                                echo !empty($storage) ? implode(" / ", $storage) : "-";
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-end fw-bold">TOTAL UNITS</td>
                    <td class="text-center" style="color: var(--primary-system); font-size: 16px; font-weight: 800;"><?= $totalUnits; ?></td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>

        <!-- OTHER ICT EQUIPMENT - Display each model on its own row (NO TOTAL ROWS) -->
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
                if (!empty($other_equipment_data)):
                    foreach ($other_equipment_data as $row):
                ?>
                    <tr>
                        <td class="text-center">
                            <?php if (!empty($row['image'])): ?>
                                <?php 
                                    $imagePath = $row['image'];
                                    if (strpos($imagePath, 'uploads/') === false) {
                                        $imagePath = 'uploads/equipment_images/' . $imagePath;
                                    }
                                ?>
                                <img src="../../<?= esc($imagePath); ?>" alt="Equipment Image" style="max-width: 100px; max-height: 70px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 12px;">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <strong><?= esc($row['type']); ?></strong>
                        </td>
                        <td class="text-center fw-bold" style="color: var(--primary-system); vertical-align: middle;">
                            <?= esc($row['units']); ?>
                        </td>
                        <td style="vertical-align: middle;">
                            <?= esc($row['model'] ?: 'N/A'); ?>
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

        <div class="systems-container">
            <div>
                <div class="section-bar">Existing Systems</div>
                <ul class="system-list">
                    <?php
                    if (empty($existing)) {
                        echo '<li class="empty-list">📋 No existing systems recorded.</li>';
                    } else {
                        foreach ($existing as $sys) echo '<li>
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg>
                            ' . esc($sys) . '
                        </li>';
                    }
                    ?>
                </ul>
            </div>
            <div>
                <div class="section-bar">Proposed Systems</div>
                <ul class="system-list">
                    <?php
                    if (empty($proposed)) {
                        echo '<li class="empty-list">💡 No proposed systems recorded.</li>';
                    } else {
                        foreach ($proposed as $sys) echo '<li>
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                            ' . esc($sys) . '
                        </li>';
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- Wrap last sections to keep them together on one page if possible -->
        <div style="page-break-inside: avoid; page-break-before: always;">
            <div class="section-bar">Network & Communication</div>
            <div class="conn-grid">
                <div class="conn-item">
                    <span class="info-label">ISP Server</span>
                    <div class="info-value"><?= esc($head['isp_server'] ?? 'N/A'); ?></div>
                </div>
                <div class="conn-item">
                    <span class="info-label">LAN Connection</span>
                    <div class="info-value"><?= esc($head['lan_connection'] ?? 'N/A'); ?></div>
                </div>
                <div class="conn-item">
                    <span class="info-label">Database Server</span>
                    <div class="info-value"><?= esc($head['database_server'] ?? 'N/A'); ?></div>
                </div>
                <div class="conn-item">
                    <span class="info-label">Other ISP</span>
                    <div class="info-value"><?= esc($head['other_isp'] ?? 'N/A'); ?></div>
                </div>
                <div class="conn-item">
                    <span class="info-label">ISP Provider Name</span>
                    <div class="info-value"><?= esc($head['isp_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="conn-item">
                    <span class="info-label">Bandwidth (Mbps)</span>
                    <div class="info-value"><?= esc($head['bandwidth'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <div class="systems-container">
                <div>
                    <div class="section-bar">Communication</div>
                    <div class="conn-grid" style="grid-template-columns: 1fr;">
                        <div class="conn-item">
                            <span class="info-label">PBAX System</span>
                            <div class="info-value"><?= esc($head['pabx'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="conn-item">
                            <span class="info-label">Telephone Numbers</span>
                            <div class="info-value"><?= esc($head['telephone_numbers'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="section-bar">Radio Communication Equipment</div>
                    <div class="conn-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="conn-item">
                            <span class="info-label">Base Radios</span>
                            <div class="info-value"><?= esc($head['base_radios'] ?? 0); ?></div>
                        </div>
                        <div class="conn-item">
                            <span class="info-label">Handheld Personal</span>
                            <div class="info-value"><?= esc($head['handheld_personal'] ?? 0); ?></div>
                        </div>
                        <div class="conn-item">
                            <span class="info-label">Handheld LGU</span>
                            <div class="info-value"><?= esc($head['handheld_lgu'] ?? 0); ?></div>
                        </div>
                        <div class="conn-item">
                            <span class="info-label">Total</span>
                            <div class="info-value"><?= esc($head['handheld_total'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-note">
            DATA FOR THE FORMULATION OF MANGALDAN RMAPS 2026-2030<br>
            <span style="color: red; font-weight: bold;">PLEASE SUBMIT THIS FORM TO ICTMIS</span><br> 
            Generated on: <?= date('F d, Y h:i A'); ?>
        </div>
    </div>

    <div class="actions">
        <button class="btn-action btn-print" id="btnPrint">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/><path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/></svg>
            Print Report
        </button>
    </div>

    <script>
        document.getElementById('btnPrint').addEventListener('click', function () {
            window.print();
        });

        // Auto-print if parameter is present
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'true') {
                setTimeout(() => {
                    window.print();
                }, 800);
            }
        });
    </script>
</body>
</html>