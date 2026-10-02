<?php
include "../../config.php";

if (!isset($_SESSION['email']) || $_SESSION['role'] != "admin") {
    echo "Unauthorized access";
    exit;
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

if (!$id) {
    echo "ID is required";
    exit;
}

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
    echo "Record not found";
    exit;
}
?>

<form action="action/update_equipment_ajax.php" method="POST" id="editEquipmentForm">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    
    <!-- OFFICE INFORMATION -->
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="fw-bold mb-2"><i class="bi bi-building me-2"></i>Office Information</div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="small">Office Name</label>
                    <input type="text" name="office" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['office_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="small">Date Submitted</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?php echo $equipment['date_submitted']; ?>" required>
                </div>
            </div>
        </div>
    </div>

    <!-- COMPUTER EQUIPMENT -->
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="fw-bold mb-2"><i class="bi bi-pc me-2"></i>Computer Equipment</div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="small">Item</label>
                    <select name="item" class="form-select form-select-sm">
                        <option value="Desktop Computer" <?php echo ($equipment['item'] == 'Desktop Computer') ? 'selected' : ''; ?>>Desktop Computer</option>
                        <option value="Laptop" <?php echo ($equipment['item'] == 'Laptop') ? 'selected' : ''; ?>>Laptop</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small">Units</label>
                    <input type="number" name="units" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['units']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="small">Brand</label>
                    <input type="text" name="brand" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['brand']); ?>">
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-4">
                    <label class="small">Processor</label>
                    <input type="text" name="processor" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['processor']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="small">RAM</label>
                    <input type="text" name="ram" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['ram']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="small">HDD</label>
                    <input type="text" name="hdd" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['hdd']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="small">SSD</label>
                    <input type="text" name="ssd" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['ssd'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- PRINTERS -->
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="fw-bold mb-2"><i class="bi bi-printer me-2"></i>Printers</div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="small">Inkjet (Qty)</label>
                    <input type="number" name="inkjet_printer" class="form-control form-control-sm" value="<?php echo $equipment['inkjet_printer']; ?>" min="0">
                </div>
                <div class="col-md-8">
                    <label class="small">Inkjet Model</label>
                    <input type="text" name="inkjet_printer_model" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['inkjet_printer_model'] ?? ''); ?>">
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="small">Deskjet (Qty)</label>
                    <input type="number" name="deskjet_printer" class="form-control form-control-sm" value="<?php echo $equipment['deskjet_printer']; ?>" min="0">
                </div>
                <div class="col-md-8">
                    <label class="small">Deskjet Model</label>
                    <input type="text" name="deskjet_printer_model" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['deskjet_printer_model'] ?? ''); ?>">
                </div>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="small">Dot Matrix (Qty)</label>
                    <input type="number" name="dot_matrix_printer" class="form-control form-control-sm" value="<?php echo $equipment['dot_matrix_printer']; ?>" min="0">
                </div>
                <div class="col-md-8">
                    <label class="small">Dot Matrix Model</label>
                    <input type="text" name="dot_matrix_printer_model" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['dotmatrix_printer_model'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- NETWORK EQUIPMENT -->
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="fw-bold mb-2"><i class="bi bi-wifi me-2"></i>Network Equipment</div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="small">Switch Hubs (Qty)</label>
                    <input type="number" name="switch_hubs" class="form-control form-control-sm" value="<?php echo $equipment['switch_hubs']; ?>" min="0">
                </div>
                <div class="col-md-8">
                    <label class="small">Switch Hubs Model</label>
                    <input type="text" name="switch_hubs_model" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['switch_hubs_model'] ?? ''); ?>">
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="small">Routers (Qty)</label>
                    <input type="number" name="routers" class="form-control form-control-sm" value="<?php echo $equipment['routers']; ?>" min="0">
                </div>
                <div class="col-md-8">
                    <label class="small">Routers Model</label>
                    <input type="text" name="routers_model" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['routers_model'] ?? ''); ?>">
                </div>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="small">Modem (Qty)</label>
                    <input type="number" name="modem" class="form-control form-control-sm" value="<?php echo $equipment['modem']; ?>" min="0">
                </div>
                <div class="col-md-8">
                    <label class="small">Modem Model</label>
                    <input type="text" name="modem_model" class="form-control form-control-sm" value="<?php echo htmlspecialchars($equipment['modem_model'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- SYSTEMS -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="fw-bold mb-2 small"><i class="bi bi-server me-2"></i>Existing Systems</div>
                    <input type="text" name="system1" class="form-control form-control-sm mb-1" placeholder="System 1" value="<?php echo htmlspecialchars($equipment['system1'] ?? ''); ?>">
                    <input type="text" name="system2" class="form-control form-control-sm mb-1" placeholder="System 2" value="<?php echo htmlspecialchars($equipment['system2'] ?? ''); ?>">
                    <input type="text" name="system3" class="form-control form-control-sm mb-1" placeholder="System 3" value="<?php echo htmlspecialchars($equipment['system3'] ?? ''); ?>">
                    <input type="text" name="system4" class="form-control form-control-sm mb-1" placeholder="System 4" value="<?php echo htmlspecialchars($equipment['system4'] ?? ''); ?>">
                    <input type="text" name="system5" class="form-control form-control-sm mb-1" placeholder="System 5" value="<?php echo htmlspecialchars($equipment['system5'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="fw-bold mb-2 small"><i class="bi bi-lightbulb me-2"></i>Proposed Systems</div>
                    <input type="text" name="proposed_system1" class="form-control form-control-sm mb-1" placeholder="Prop. System 1" value="<?php echo htmlspecialchars($equipment['proposed_system1'] ?? ''); ?>">
                    <input type="text" name="proposed_system2" class="form-control form-control-sm mb-1" placeholder="Prop. System 2" value="<?php echo htmlspecialchars($equipment['proposed_system2'] ?? ''); ?>">
                    <input type="text" name="proposed_system3" class="form-control form-control-sm mb-1" placeholder="Prop. System 3" value="<?php echo htmlspecialchars($equipment['proposed_system3'] ?? ''); ?>">
                    <input type="text" name="proposed_system4" class="form-control form-control-sm mb-1" placeholder="Prop. System 4" value="<?php echo htmlspecialchars($equipment['proposed_system4'] ?? ''); ?>">
                    <input type="text" name="proposed_system5" class="form-control form-control-sm mb-1" placeholder="Prop. System 5" value="<?php echo htmlspecialchars($equipment['proposed_system5'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="submit" class="btn btn-primary btn-sm">Update Record</button>
    </div>
</form>

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