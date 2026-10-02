<?php
include "../../config.php";

// Check if user is authenticated
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Check if office and date parameters are provided
if (isset($_GET['office']) && isset($_GET['date'])) {
    $office = mysqli_real_escape_string($conn, $_GET['office']);
    $date = mysqli_real_escape_string($conn, $_GET['date']);
    $userOffice = $_SESSION['user_office'] ?? '';

    // Verify that the user is deleting their own office's submission
    if ($office !== $userOffice) {
        header("Location: ../history.php?msg=error");
        exit;
    }

    // Start transaction for atomic delete operations
    mysqli_begin_transaction($conn);
    
    try {
        // Delete from user_issp_form (main history)
        $query1 = "DELETE FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$date'";
        
        // Delete related inventory/equipment records
        $query2 = "DELETE FROM ict_equipment_inventory WHERE office_name = '$office' AND date_submitted = '$date'";
        
        // Delete related reports
        $query3 = "DELETE FROM reports WHERE office_name = '$office' AND date_submitted = '$date'";
        
        // Execute all deletes
        if (!mysqli_query($conn, $query1)) {
            throw new Exception("Failed to delete from user_issp_form");
        }
        
        if (!mysqli_query($conn, $query2)) {
            throw new Exception("Failed to delete from ict_equipment_inventory");
        }
        
        if (!mysqli_query($conn, $query3)) {
            throw new Exception("Failed to delete from reports");
        }
        
        // Commit transaction
        mysqli_commit($conn);
        header("Location: ../history.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        header("Location: ../history.php?msg=error");
        exit;
    }
} else {
    header("Location: ../history.php?msg=error");
    exit;
}
?>
