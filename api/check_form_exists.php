<?php
include "../config.php";

header('Content-Type: application/json');

$office = isset($_POST['office']) ? trim($_POST['office']) : '';
$year = isset($_POST['year']) ? trim($_POST['year']) : date('Y');

if (empty($office)) {
    echo json_encode(['exists' => false, 'error' => 'Office name is required']);
    exit;
}

$escapedOffice = mysqli_real_escape_string($conn, $office);
$escapedYear = mysqli_real_escape_string($conn, $year);

// Check in all relevant tables
$check_admin = mysqli_query($conn, "SELECT id FROM form WHERE LOWER(TRIM(office_name)) = LOWER('$escapedOffice') AND YEAR(date_submitted) = '$escapedYear' LIMIT 1");
$check_user = mysqli_query($conn, "SELECT id FROM user_issp_form WHERE LOWER(TRIM(office_name)) = LOWER('$escapedOffice') AND YEAR(date_submitted) = '$escapedYear' LIMIT 1");
$check_ict = mysqli_query($conn, "SELECT id FROM ict_forms WHERE LOWER(TRIM(office_name)) = LOWER('$escapedOffice') AND YEAR(date_submitted) = '$escapedYear' LIMIT 1");

$exists = (mysqli_num_rows($check_admin) > 0 || mysqli_num_rows($check_user) > 0 || mysqli_num_rows($check_ict) > 0);

echo json_encode(['exists' => $exists]);
