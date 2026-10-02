<?php
include "../../config.php";

header('Content-Type: application/json');

// Ensure tables and columns exist
$create_maintenance_reports = "CREATE TABLE IF NOT EXISTS maintenance_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    source ENUM('user', 'admin') NOT NULL,
    office_name VARCHAR(255) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    problem_description TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'cancelled') DEFAULT 'pending',
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    reported_by VARCHAR(255),
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    admin_remarks TEXT
)";
mysqli_query($conn, $create_maintenance_reports);

$checkStatus = mysqli_query($conn, "SHOW COLUMNS FROM computer_equipment LIKE 'status'");
if ($checkStatus && mysqli_num_rows($checkStatus) === 0) {
    mysqli_query($conn, "ALTER TABLE computer_equipment ADD COLUMN status VARCHAR(50) DEFAULT 'operational'");
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_id = isset($_POST['device_id']) ? (int)$_POST['device_id'] : 0;
    $source = isset($_POST['source']) ? mysqli_real_escape_string($conn, $_POST['source']) : '';
    $office_name = isset($_SESSION['user_office']) ? $_SESSION['user_office'] : ($_SESSION['office'] ?? '');
    $item_name = isset($_POST['item_name']) ? mysqli_real_escape_string($conn, $_POST['item_name']) : '';
    $problem_description = isset($_POST['problem_description']) ? mysqli_real_escape_string($conn, $_POST['problem_description']) : '';
    $urgency = isset($_POST['urgency']) ? mysqli_real_escape_string($conn, $_POST['urgency']) : 'medium';
    $reported_by = $_SESSION['user_name'] ?? 'Unknown User';

    if (!$device_id || !$problem_description) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }

    $query = "INSERT INTO maintenance_reports (device_id, source, office_name, item_name, problem_description, urgency, reported_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssss", $device_id, $source, $office_name, $item_name, $problem_description, $urgency, $reported_by);

    if ($stmt->execute()) {
        // Optionally update device status to 'non-operational' or similar if it's critical
        if ($urgency === 'critical' || $urgency === 'high') {
            if ($source === 'user') {
                $update_sql = "UPDATE user_issp_form SET status = 'non-operational' WHERE id = ?";
            } else {
                $update_sql = "UPDATE computer_equipment SET status = 'non-operational' WHERE id = ?";
            }
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $device_id);
            $update_stmt->execute();
        }

        echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
