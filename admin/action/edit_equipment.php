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
    header("Location: ../report.php");
    exit;
}

// Fetch from ict_equipment_inventory and join ict_forms or user_issp_form
$query = mysqli_query($conn, "
    SELECT i.*, 
           COALESCE(f.isp_server, u.isp_server) as isp_server,
           COALESCE(f.lan_connection, u.lan_connection) as lan_connection,
           COALESCE(f.database_server, u.database_server) as database_server,
           COALESCE(f.other_isp, u.other_isp) as other_isp,
           COALESCE(f.isp_name, u.isp_name) as isp_name,
           COALESCE(f.bandwidth, u.bandwidth) as bandwidth,
           COALESCE(f.other_source, u.other_source) as other_source,
           COALESCE(f.pabx, u.pabx) as pabx,
           COALESCE(f.telephone_numbers, u.telephone_numbers) as telephone_numbers,
           COALESCE(f.base_radios, u.base_radios) as base_radios,
           COALESCE(f.handheld_personal, u.handheld_personal) as handheld_personal,
           COALESCE(f.handheld_lgu, u.handheld_lgu) as handheld_lgu,
           COALESCE(f.handheld_total, u.handheld_total) as handheld_total,
           COALESCE(f.system1, u.system1) as system1,
           COALESCE(f.system2, u.system2) as system2,
           COALESCE(f.system3, u.system3) as system3,
           COALESCE(f.system4, u.system4) as system4,
           COALESCE(f.system5, u.system5) as system5,
           COALESCE(f.proposed_system1, u.proposed_system1) as proposed_system1,
           COALESCE(f.proposed_system2, u.proposed_system2) as proposed_system2,
           COALESCE(f.proposed_system3, u.proposed_system3) as proposed_system3,
           COALESCE(f.proposed_system4, u.proposed_system4) as proposed_system4,
           COALESCE(f.proposed_system5, u.proposed_system5) as proposed_system5
    FROM ict_equipment_inventory i
    LEFT JOIN ict_forms f ON i.office_name = f.office_name AND i.date_submitted = f.date_submitted
    LEFT JOIN user_issp_form u ON i.office_name = u.office_name AND i.date_submitted = u.date_submitted
    WHERE i.id = '$id'
");
$equipment = mysqli_fetch_assoc($query);

if (!$equipment) {
    header("Location: ../inventory.php");
    exit;
}

// Handle form submission
if(isset($_POST['submit'])){
    $office = mysqli_real_escape_string($conn, $_POST['office']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $item = mysqli_real_escape_string($conn, $_POST['item']);
    $units = mysqli_real_escape_string($conn, $_POST['units']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $processor = mysqli_real_escape_string($conn, $_POST['processor']);
    $ram = mysqli_real_escape_string($conn, $_POST['ram']);
    $hdd = mysqli_real_escape_string($conn, $_POST['hdd']);
    $ssd = mysqli_real_escape_string($conn, $_POST['ssd'] ?? '');
    


    // Internet Connection
    $isp_server = mysqli_real_escape_string($conn, $_POST['isp_server'] ?? '');
    $lan_connection = mysqli_real_escape_string($conn, $_POST['lan_connection'] ?? '');
    $database_server = mysqli_real_escape_string($conn, $_POST['database_server'] ?? '');
    $other_isp = mysqli_real_escape_string($conn, $_POST['other_isp'] ?? '');
    $isp_name = mysqli_real_escape_string($conn, $_POST['isp_name'] ?? '');
    $bandwidth = mysqli_real_escape_string($conn, $_POST['bandwidth'] ?? '');
    $other_source = mysqli_real_escape_string($conn, $_POST['other_source'] ?? '');

    // Communication
    $pabx = mysqli_real_escape_string($conn, $_POST['pabx'] ?? '');
    $telephone_numbers = mysqli_real_escape_string($conn, $_POST['telephone_numbers'] ?? '');

    // Radio Communication
    $base_radios = (int)($_POST['base_radios'] ?? 0);
    $handheld_personal = (int)($_POST['handheld_personal'] ?? 0);
    $handheld_lgu = (int)($_POST['handheld_lgu'] ?? 0);
    $handheld_total = (int)($_POST['handheld_total'] ?? 0);

    // Existing Systems
    $system1 = mysqli_real_escape_string($conn, $_POST['system1'] ?? '');
    $system2 = mysqli_real_escape_string($conn, $_POST['system2'] ?? '');
    $system3 = mysqli_real_escape_string($conn, $_POST['system3'] ?? '');
    $system4 = mysqli_real_escape_string($conn, $_POST['system4'] ?? '');
    $system5 = mysqli_real_escape_string($conn, $_POST['system5'] ?? '');

    // Proposed Systems
    $proposed_system1 = mysqli_real_escape_string($conn, $_POST['proposed_system1'] ?? '');
    $proposed_system2 = mysqli_real_escape_string($conn, $_POST['proposed_system2'] ?? '');
    $proposed_system3 = mysqli_real_escape_string($conn, $_POST['proposed_system3'] ?? '');
    $proposed_system4 = mysqli_real_escape_string($conn, $_POST['proposed_system4'] ?? '');
    $proposed_system5 = mysqli_real_escape_string($conn, $_POST['proposed_system5'] ?? '');

    // ICT Equipment values
    $inkjet_printer = isset($_POST['inkjet_printer']) ? (int)$_POST['inkjet_printer'] : 0;
    $inkjet_printer_model = mysqli_real_escape_string($conn, $_POST['inkjet_printer_model'] ?? '');
    
    $deskjet_printer = isset($_POST['deskjet_printer']) ? (int)$_POST['deskjet_printer'] : 0;
    $deskjet_printer_model = mysqli_real_escape_string($conn, $_POST['deskjet_printer_model'] ?? '');
    
    $dotmatrix_printer = isset($_POST['dotmatrix_printer']) ? (int)$_POST['dotmatrix_printer'] : 0;
    $dotmatrix_printer_model = mysqli_real_escape_string($conn, $_POST['dotmatrix_printer_model'] ?? '');
    
    $switch_hubs = isset($_POST['switch_hubs']) ? (int)$_POST['switch_hubs'] : 0;
    $switch_hubs_model = mysqli_real_escape_string($conn, $_POST['switch_hubs_model'] ?? '');
    
    $routers = isset($_POST['routers']) ? (int)$_POST['routers'] : 0;
    $routers_model = mysqli_real_escape_string($conn, $_POST['routers_model'] ?? '');
    
    $modem = isset($_POST['modem']) ? (int)$_POST['modem'] : 0;
    $modem_model = mysqli_real_escape_string($conn, $_POST['modem_model'] ?? '');

    // Update ict_forms
    $update_forms = mysqli_query($conn, "UPDATE ict_forms SET 
        office_name = '$office', 
        date_submitted = '$date',
        item = '$item',
        units = '$units',
        brand = '$brand',
        processor = '$processor',
        ram = '$ram',
        hdd = '$hdd',
        ssd = '$ssd',

        isp_server = '$isp_server',
        lan_connection = '$lan_connection',
        database_server = '$database_server',
        other_isp = '$other_isp',
        isp_name = '$isp_name',
        bandwidth = '$bandwidth',
        other_source = '$other_source',
        pabx = '$pabx',
        telephone_numbers = '$telephone_numbers',
        base_radios = $base_radios,
        handheld_personal = $handheld_personal,
        handheld_lgu = $handheld_lgu,
        handheld_total = $handheld_total,
        system1 = '$system1',
        system2 = '$system2',
        system3 = '$system3',
        system4 = '$system4',
        system5 = '$system5',
        proposed_system1 = '$proposed_system1',
        proposed_system2 = '$proposed_system2',
        proposed_system3 = '$proposed_system3',
        proposed_system4 = '$proposed_system4',
        proposed_system5 = '$proposed_system5',
        inkjet_printer_model = '$inkjet_printer_model',
        dotmatrix_printer_model = '$dotmatrix_printer_model',
        deskjet_printer_model = '$deskjet_printer_model',
        routers_model = '$routers_model',
        switch_hubs_model = '$switch_hubs_model',
        modem_model = '$modem_model'
        WHERE office_name = '".mysqli_real_escape_string($conn, $equipment['office_name'])."' 
        AND date_submitted = '".$equipment['date_submitted']."'");

    // Update ict_equipment_inventory
    $update_inventory = mysqli_query($conn, "UPDATE ict_equipment_inventory SET 
        office_name = '$office', 
        date_submitted = '$date', 
        item = '$item',
        units = '$units',
        brand = '$brand',
        processor = '$processor',
        ram = '$ram',
        hdd = '$hdd',
        ssd = '$ssd',
        inkjet_printer = $inkjet_printer, 
        deskjet_printer = $deskjet_printer, 
        dotmatrix_printer = $dotmatrix_printer, 
        switch_hubs = $switch_hubs, 
        routers = $routers, 
        modem = $modem,
        system1 = '$system1',
        system2 = '$system2',
        system3 = '$system3',
        system4 = '$system4',
        system5 = '$system5',
        proposed_system1 = '$proposed_system1',
        proposed_system2 = '$proposed_system2',
        proposed_system3 = '$proposed_system3',
        proposed_system4 = '$proposed_system4',
        proposed_system5 = '$proposed_system5',
        inkjet_printer_model = '$inkjet_printer_model',
        dotmatrix_printer_model = '$dotmatrix_printer_model',
        deskjet_printer_model = '$deskjet_printer_model',
        routers_model = '$routers_model',
        switch_hubs_model = '$switch_hubs_model',
        modem_model = '$modem_model'
        WHERE id = '$id'");

    if($update_inventory) {
        echo "<script>alert('Record updated successfully!'); window.location='../inventory.php';</script>";
    } else {
        echo "<script>alert('Error updating record: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'Edit Equipment - ICTMIS'; ?>
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
                    <h1>Edit Equipment</h1>
                    <p class="mb-0">Modify ICT equipment details for <?php echo htmlspecialchars($equipment['office_name']); ?>.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../report.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i> Back to Reports
                    </a>
                </div>
            </div>
        </div>

        <div class="card animate__animated animate__fadeIn">
                        <div class="card-body">
                            <form method="POST" class="bordered-form">
                                <!-- OFFICE INFORMATION -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-building me-2"></i>Office Information</div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label>Office Name</label>
                                                <input type="text" name="office" class="form-control" value="<?php echo htmlspecialchars($equipment['office_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Date Submitted</label>
                                                <input type="date" name="date" class="form-control" value="<?php echo $equipment['date_submitted']; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- COMPUTER EQUIPMENT -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-pc me-2"></i>Computer Equipment</div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label>Item</label>
                                                <select name="item" class="form-control">
                                                    <option value="Desktop Computer" <?php echo ($equipment['item'] == 'Desktop Computer') ? 'selected' : ''; ?>>Desktop Computer</option>
                                                    <option value="Laptop" <?php echo ($equipment['item'] == 'Laptop') ? 'selected' : ''; ?>>Laptop</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label>No. of Units</label>
                                                <input type="number" name="units" class="form-control" value="<?php echo htmlspecialchars($equipment['units']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Brand</label>
                                                <input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($equipment['brand']); ?>">
                                            </div>
                                        </div>
                                        <br>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label>Processor</label>
                                                <input type="text" name="processor" class="form-control" value="<?php echo htmlspecialchars($equipment['processor']); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>RAM</label>
                                                <input type="text" name="ram" class="form-control" value="<?php echo htmlspecialchars($equipment['ram']); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>HDD</label>
                                                <input type="text" name="hdd" class="form-control" value="<?php echo htmlspecialchars($equipment['hdd']); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>SSD</label>
                                                <input type="text" name="ssd" class="form-control" value="<?php echo htmlspecialchars($equipment['ssd'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PRINTERS -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-printer me-2"></i>Printers</div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label>Inkjet Printer (Qty)</label>
                                                <input type="number" name="inkjet_printer" class="form-control" value="<?php echo $equipment['inkjet_printer']; ?>" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Inkjet Printer Model</label>
                                                <input type="text" name="inkjet_printer_model" class="form-control" value="<?php echo htmlspecialchars($equipment['inkjet_printer_model'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label>Deskjet Printer (Qty)</label>
                                                <input type="number" name="deskjet_printer" class="form-control" value="<?php echo $equipment['deskjet_printer']; ?>" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Deskjet Printer Model</label>
                                                <input type="text" name="deskjet_printer_model" class="form-control" value="<?php echo htmlspecialchars($equipment['deskjet_printer_model'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label>Dot Matrix Printer (Qty)</label>
                                                <input type="number" name="dotmatrix_printer" class="form-control" value="<?php echo $equipment['dotmatrix_printer']; ?>" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Dot Matrix Printer Model</label>
                                                <input type="text" name="dotmatrix_printer_model" class="form-control" value="<?php echo htmlspecialchars($equipment['dotmatrix_printer_model'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- NETWORK EQUIPMENT -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-wifi me-2"></i>Network Equipment</div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label>Switch Hubs (Qty)</label>
                                                <input type="number" name="switch_hubs" class="form-control" value="<?php echo $equipment['switch_hubs']; ?>" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Switch Hubs Model</label>
                                                <input type="text" name="switch_hubs_model" class="form-control" value="<?php echo htmlspecialchars($equipment['switch_hubs_model'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label>Routers (Qty)</label>
                                                <input type="number" name="routers" class="form-control" value="<?php echo $equipment['routers']; ?>" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Routers Model</label>
                                                <input type="text" name="routers_model" class="form-control" value="<?php echo htmlspecialchars($equipment['routers_model'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label>Modem (Qty)</label>
                                                <input type="number" name="modem" class="form-control" value="<?php echo $equipment['modem']; ?>" min="0">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Modem Model</label>
                                                <input type="text" name="modem_model" class="form-control" value="<?php echo htmlspecialchars($equipment['modem_model'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- EXISTING COMPUTERIZED SYSTEMS -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-server me-2"></i>Existing Computerized Systems</div>
                                        <div class="mb-2">
                                            <input type="text" name="system1" class="form-control mb-2" placeholder="System 1" value="<?php echo htmlspecialchars($equipment['system1'] ?? ''); ?>">
                                            <input type="text" name="system2" class="form-control mb-2" placeholder="System 2" value="<?php echo htmlspecialchars($equipment['system2'] ?? ''); ?>">
                                            <input type="text" name="system3" class="form-control mb-2" placeholder="System 3" value="<?php echo htmlspecialchars($equipment['system3'] ?? ''); ?>">
                                            <input type="text" name="system4" class="form-control mb-2" placeholder="System 4" value="<?php echo htmlspecialchars($equipment['system4'] ?? ''); ?>">
                                            <input type="text" name="system5" class="form-control mb-2" placeholder="System 5" value="<?php echo htmlspecialchars($equipment['system5'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- PROPOSED SYSTEMS -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-lightbulb me-2"></i>Proposed Systems</div>
                                        <div class="mb-2">
                                            <input type="text" name="proposed_system1" class="form-control mb-2" placeholder="Proposed System 1" value="<?php echo htmlspecialchars($equipment['proposed_system1'] ?? ''); ?>">
                                            <input type="text" name="proposed_system2" class="form-control mb-2" placeholder="Proposed System 2" value="<?php echo htmlspecialchars($equipment['proposed_system2'] ?? ''); ?>">
                                            <input type="text" name="proposed_system3" class="form-control mb-2" placeholder="Proposed System 3" value="<?php echo htmlspecialchars($equipment['proposed_system3'] ?? ''); ?>">
                                            <input type="text" name="proposed_system4" class="form-control mb-2" placeholder="Proposed System 4" value="<?php echo htmlspecialchars($equipment['proposed_system4'] ?? ''); ?>">
                                            <input type="text" name="proposed_system5" class="form-control mb-2" placeholder="Proposed System 5" value="<?php echo htmlspecialchars($equipment['proposed_system5'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- INTERNET CONNECTION -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-wifi me-2"></i>Internet Connections</div>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="70%">Connection Type</th>
                                                <th class="text-center">YES</th>
                                                <th class="text-center">NO</th>
                                            </tr>
                                            <tr>
                                                <td>Connected to CENTRALIZED ISP SERVER</td>
                                                <td class="text-center"><input type="radio" name="isp_server" value="Yes" <?php echo ($equipment['isp_server'] == 'Yes') ? 'checked' : ''; ?>></td>
                                                <td class="text-center"><input type="radio" name="isp_server" value="No" <?php echo ($equipment['isp_server'] == 'No') ? 'checked' : ''; ?>></td>
                                            </tr>
                                            <tr>
                                                <td>Connected to CENTRALIZED LAN / WAN CONNECTION</td>
                                                <td class="text-center"><input type="radio" name="lan_connection" value="Yes" <?php echo ($equipment['lan_connection'] == 'Yes') ? 'checked' : ''; ?>></td>
                                                <td class="text-center"><input type="radio" name="lan_connection" value="No" <?php echo ($equipment['lan_connection'] == 'No') ? 'checked' : ''; ?>></td>
                                            </tr>
                                            <tr>
                                                <td>Connected to CENTRALIZED DATABASE SERVER</td>
                                                <td class="text-center"><input type="radio" name="database_server" value="Yes" <?php echo ($equipment['database_server'] == 'Yes') ? 'checked' : ''; ?>></td>
                                                <td class="text-center"><input type="radio" name="database_server" value="No" <?php echo ($equipment['database_server'] == 'No') ? 'checked' : ''; ?>></td>
                                            </tr>
                                            <tr>
                                                <td>Connected to OTHER ISPs via Office Paid Plan</td>
                                                <td class="text-center"><input type="radio" name="other_isp" value="Yes" <?php echo ($equipment['other_isp'] == 'Yes') ? 'checked' : ''; ?> onclick="toggleISP()"></td>
                                                <td class="text-center"><input type="radio" name="other_isp" value="No" <?php echo ($equipment['other_isp'] == 'No') ? 'checked' : ''; ?> onclick="toggleISP()"></td>
                                            </tr>
                                        </table>

                                        <div id="ispDetails" style="<?php echo ($equipment['other_isp'] == 'Yes') ? 'display:block;' : 'display:none;'; ?>">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>ISP Name</label>
                                                    <input type="text" name="isp_name" class="form-control" value="<?php echo htmlspecialchars($equipment['isp_name']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Bandwidth (Mbps)</label>
                                                    <input type="text" name="bandwidth" class="form-control" value="<?php echo htmlspecialchars($equipment['bandwidth']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <br>
                                        <label>Other Internet Connection Source</label>
                                        <input type="text" name="other_source" class="form-control" value="<?php echo htmlspecialchars($equipment['other_source']); ?>">
                                    </div>
                                </div>

                                <!-- COMMUNICATION -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-telephone me-2"></i>Communication</div>
                                        <label>PABX</label>
                                        <select name="pabx" class="form-control mb-2">
                                            <option value="Yes" <?php echo ($equipment['pabx'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                            <option value="No" <?php echo ($equipment['pabx'] == 'No') ? 'selected' : ''; ?>>No</option>
                                        </select>
                                        <label>Telephone Numbers</label>
                                        <input type="text" name="telephone_numbers" class="form-control" value="<?php echo htmlspecialchars($equipment['telephone_numbers']); ?>">
                                    </div>
                                </div>

                                <!-- RADIO -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="section-title"><i class="bi bi-wifi me-2"></i>Radio Communication Equipment</div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label>Base Radios</label>
                                                <input type="number" name="base_radios" class="form-control" value="<?php echo $equipment['base_radios']; ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Handheld Personal</label>
                                                <input type="number" name="handheld_personal" class="form-control" value="<?php echo $equipment['handheld_personal']; ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Handheld LGU</label>
                                                <input type="number" name="handheld_lgu" class="form-control" value="<?php echo $equipment['handheld_lgu']; ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Total</label>
                                                <input type="number" name="handheld_total" class="form-control" value="<?php echo $equipment['handheld_total']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- BUTTONS -->
                                <div class="d-flex gap-2">
                                    <button type="submit" name="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Update Record
                                    </button>
                                    <a href="../report.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
        </div>
    </div>
</div>

    <script>
    function toggleISP() {
        var otherISP = document.querySelector('input[name="other_isp"]:checked');
        var ispDetails = document.getElementById('ispDetails');
        if(otherISP && otherISP.value == "Yes"){
            ispDetails.style.display = "block";
        }else{
            ispDetails.style.display = "none";
        }
    }
    </script>
</body>
</html>
