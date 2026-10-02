<?php
include "../../config.php";

if (!isset($_SESSION['email']) || $_SESSION['role'] != "admin") {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if(isset($_POST['id'])){
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    
    // Fetch original record to get office and date for joining other tables
    $orig_query = mysqli_query($conn, "SELECT office_name, date_submitted FROM ict_equipment_inventory WHERE id = '$id'");
    $orig = mysqli_fetch_assoc($orig_query);
    
    if(!$orig) {
        echo json_encode(['status' => 'error', 'message' => 'Record not found']);
        exit;
    }

    $office = mysqli_real_escape_string($conn, $_POST['office']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $item = mysqli_real_escape_string($conn, $_POST['item']);
    $units = mysqli_real_escape_string($conn, $_POST['units']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $processor = mysqli_real_escape_string($conn, $_POST['processor']);
    $ram = mysqli_real_escape_string($conn, $_POST['ram']);
    $hdd = mysqli_real_escape_string($conn, $_POST['hdd'] ?? '');
    $ssd = mysqli_real_escape_string($conn, $_POST['ssd'] ?? '');
    
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

    // Update ict_forms (if exists)
    mysqli_query($conn, "UPDATE ict_forms SET 
        office_name = '$office', 
        date_submitted = '$date',
        item = '$item',
        units = '$units',
        brand = '$brand',
        processor = '$processor',
        ram = '$ram',
        hdd = '$hdd',
        ssd = '$ssd',
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
        WHERE office_name = '".mysqli_real_escape_string($conn, $orig['office_name'])."' 
        AND date_submitted = '".$orig['date_submitted']."'");

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
        echo json_encode(['status' => 'success', 'message' => 'Record updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating record: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>