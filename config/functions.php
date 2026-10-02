<?php
/**
 * Safe Database Helper Functions
 * These functions provide secure database queries with error handling
 */

/**
 * Safely count records in a database table
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @return int Count of records
 */
function safeCount($conn, $table) {
    // Validate table name to prevent SQL injection
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM `$table`");
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return (int)$row['count'];
    }
    
    return 0;
}

/**
 * Safely execute a query and return the result
 * @param mysqli $conn Database connection
 * @param string $query SQL query
 * @return mysqli_result|bool Query result
 */
function safeQuery($conn, $query) {
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        // Log error for debugging (in production, you'd want proper logging)
        error_log("Query Error: " . mysqli_error($conn));
        return null;
    }
    
    return $result;
}
?>

