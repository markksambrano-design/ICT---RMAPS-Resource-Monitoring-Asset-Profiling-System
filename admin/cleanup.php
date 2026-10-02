<?php
include "../config.php";

echo "<pre>";

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Find duplicate offices
    $find_duplicates_sql = "SELECT office_name, MIN(id) as correct_id, GROUP_CONCAT(id) as all_ids, COUNT(*) as count 
                            FROM offices 
                            GROUP BY office_name 
                            HAVING COUNT(*) > 1";
    
    $duplicates_result = mysqli_query($conn, $find_duplicates_sql);

    if (mysqli_num_rows($duplicates_result) > 0) {
        echo "Found duplicates. Starting cleanup...\n\n";

        while ($row = mysqli_fetch_assoc($duplicates_result)) {
            $office_name = $row['office_name'];
            $correct_id = $row['correct_id'];
            $all_ids = explode(',', $row['all_ids']);
            
            // Get the IDs to be updated (all except the correct one)
            $ids_to_update = array_diff($all_ids, [$correct_id]);

            if (empty($ids_to_update)) {
                continue;
            }

            echo "- Processing office: $office_name\n";
            echo "  Correct ID: $correct_id\n";
            echo "  Duplicate IDs to fix: " . implode(', ', $ids_to_update) . "\n";

            $ids_to_update_str = implode(',', $ids_to_update);

            // Delete duplicate office entries
            $delete_sql = "DELETE FROM offices WHERE id IN ($ids_to_update_str)";
            if (mysqli_query($conn, $delete_sql)) {
                $deleted_count = mysqli_affected_rows($conn);
                echo "  - Deleted $deleted_count duplicate entries from `offices` table.\n\n";
            } else {
                throw new Exception("Error deleting duplicates from offices: " . mysqli_error($conn));
            }
        }

    } else {
        echo "No duplicate offices found. Database is clean.\n";
    }

    // If all successful, commit
    mysqli_commit($conn);
    echo "\nCleanup successful! Database transaction committed.";

} catch (Exception $e) {
    // If any error, rollback
    mysqli_rollback($conn);
    echo "\nAn error occurred: " . $e->getMessage();
    echo "\nTransaction rolled back. No changes were made to the database.";
}

echo "</pre>";
?>