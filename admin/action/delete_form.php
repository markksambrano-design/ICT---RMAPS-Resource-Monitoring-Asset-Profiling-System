<?php
// Start output buffering to prevent any premature output from interfering with JSON response
ob_start();

// Enable detailed error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    include "../../config.php";

    // Use more robust session check
    if (!isset($_SESSION['email']) || $_SESSION['role'] != "admin") {
        if (isset($_REQUEST['ajax'])) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        header("Location: ../login.php?error=session_expired");
        exit;
    }

    $id = isset($_REQUEST['id']) ? mysqli_real_escape_string($conn, $_REQUEST['id']) : null;
    $ajax = isset($_REQUEST['ajax']);
    $source = isset($_REQUEST['source']) ? mysqli_real_escape_string($conn, $_REQUEST['source']) : '';

    if (!$id) {
        throw new Exception("Missing ID parameter");
    }

    $allowed_sources = ['ict_forms', 'user_issp_form', 'ict_equipment_inventory', 'maintenance_reports', 'form'];

    if (!in_array($source, $allowed_sources)) {
        // Detect source if missing
        $checkForm = mysqli_query($conn, "SELECT id FROM form WHERE id = '$id'");
        if (mysqli_num_rows($checkForm) > 0) {
            $source = 'form';
        } else {
            // Check other tables
            $checkICT = mysqli_query($conn, "SELECT id FROM ict_forms WHERE id = '$id'");
            if (mysqli_num_rows($checkICT) > 0) {
                $source = 'ict_forms';
            } else {
                $source = 'form'; // Default to form if not found anywhere, will be handled by formData check
            }
        }
    }

    if ($source === 'form' || $source === 'ict_forms') {
        // Fetch office and date to delete from non-cascading tables
        if ($source === 'form') {
            $formRow = mysqli_query($conn, "SELECT office_name, date_submitted FROM form WHERE id = '$id'");
        } else {
            $formRow = mysqli_query($conn, "SELECT office_name, date_submitted FROM ict_forms WHERE id = '$id'");
        }
        
        $formData = mysqli_fetch_assoc($formRow);

        if (!$formData) {
            throw new Exception("Record not found in " . $source);
        }

        $office = mysqli_real_escape_string($conn, $formData['office_name']);
        $date = mysqli_real_escape_string($conn, $formData['date_submitted']);

        mysqli_begin_transaction($conn);

        // 1. Delete from related tables that might not have form_id or ON DELETE CASCADE
        mysqli_query($conn, "DELETE FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$date'");
        mysqli_query($conn, "DELETE FROM ict_forms WHERE office_name = '$office' AND date_submitted = '$date'");
        
        // Also manually delete from ict_equipment_inventory if it's not linked by form_id or if cascade fails
        mysqli_query($conn, "DELETE FROM ict_equipment_inventory WHERE office_name = '$office' AND date_submitted = '$date'");
        
        // 2. Delete from 'form' table (Cascades to computer_equipment, other_ict_equipment, etc.)
        if ($source === 'form') {
            mysqli_query($conn, "DELETE FROM form WHERE id = '$id'");
        } else {
            // If coming from ict_forms, find the matching form record
            $findForm = mysqli_query($conn, "SELECT id FROM form WHERE office_name = '$office' AND date_submitted = '$date'");
            if ($fRow = mysqli_fetch_assoc($findForm)) {
                $fId = $fRow['id'];
                mysqli_query($conn, "DELETE FROM form WHERE id = '$fId'");
            }
        }

        mysqli_commit($conn);
        
        if ($ajax) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
            exit;
        }
        
        header("Location: ../" . ($source === 'form' ? "inventory.php?view=summary" : "report.php") . "&msg=deleted");
        exit;
    }

    // Handle other sources (like 'maintenance_reports')
    $pk = 'id'; // Most tables use 'id' as primary key
    mysqli_query($conn, "DELETE FROM {$source} WHERE {$pk} = '$id'");
    
    if ($ajax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
        exit;
    }

    header("Location: ../" . ($source === 'form' || $source === 'ict_equipment_inventory' ? "inventory.php?view=summary" : "report.php") . "&msg=deleted");
    exit;

} catch (Exception $e) {
    if (isset($conn)) mysqli_rollback($conn);

    if (isset($_REQUEST['ajax'])) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    $errorMsg = urlencode($e->getMessage());
    $redirect = isset($source) && ($source === 'form' || $source === 'ict_equipment_inventory') ? "inventory.php?view=summary" : "report.php";
    header("Location: ../$redirect&msg=error&reason=exception&details=$errorMsg");
    exit;
}
?>
