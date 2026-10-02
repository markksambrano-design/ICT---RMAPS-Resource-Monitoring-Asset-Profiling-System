<?php
include "../../config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['email']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$office = isset($_GET['office']) ? mysqli_real_escape_string($conn, $_GET['office']) : '';
$date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';

if (!$office || !$date) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$query = mysqli_query($conn, "SELECT * FROM user_issp_form WHERE office_name = '$office' AND date_submitted = '$date' LIMIT 1");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    $query = mysqli_query($conn, "SELECT * FROM ict_forms WHERE office_name = '$office' AND date_submitted = '$date' LIMIT 1");
    $data = mysqli_fetch_assoc($query);
}

if (!$data) {
    echo json_encode(['error' => 'Form not found']);
    exit;
}

// Simplified detail output
$detail = [
    'office_name' => $data['office_name'] ?? '',
    'date_submitted' => isset($data['date_submitted']) ? date('F d, Y', strtotime($data['date_submitted'])) : '',
    'status' => $data['form_status'] ?? 'Open',
    'item' => $data['item'] ?? '',
    'units' => $data['units'] ?? '',
    'brand' => $data['brand'] ?? '',
    'processor' => $data['processor'] ?? '',
    'ram' => $data['ram'] ?? '',
    'hdd' => $data['hdd'] ?? '',
    'ssd' => $data['ssd'] ?? '',
    'isp_server' => $data['isp_server'] ?? '',
    'lan_connection' => $data['lan_connection'] ?? '',
];

echo json_encode(['success' => true, 'detail' => $detail]);
exit;
