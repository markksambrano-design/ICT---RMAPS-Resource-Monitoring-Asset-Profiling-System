<?php
include "../config.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['can_submit' => false, 'message' => 'Not logged in']);
    exit;
}

$userOffice = isset($_SESSION['user_office']) ? $_SESSION['user_office'] : '';

// Check if office has a pending open form in either table
function office_has_open_form($conn, $officeName) {
    $officeEscaped = mysqli_real_escape_string($conn, $officeName);

    $check_existing = mysqli_query($conn, "SELECT id FROM user_issp_form WHERE office_name = '$officeEscaped' AND COALESCE(form_status, 'Open') = 'Open' LIMIT 1");
    if (mysqli_num_rows($check_existing) > 0) {
        return true;
    }

    $check_existing = mysqli_query($conn, "SELECT id, date_submitted FROM ict_forms WHERE office_name = '$officeEscaped' AND COALESCE(form_status, 'Open') = 'Open'");
    while ($row = mysqli_fetch_assoc($check_existing)) {
        $dateEscaped = mysqli_real_escape_string($conn, $row['date_submitted']);
        $matching = mysqli_query($conn, "SELECT id FROM user_issp_form WHERE office_name = '$officeEscaped' AND date_submitted = '$dateEscaped' LIMIT 1");
        if (mysqli_num_rows($matching) > 0) {
            return true;
        }
        mysqli_query($conn, "DELETE FROM ict_forms WHERE office_name = '$officeEscaped' AND date_submitted = '$dateEscaped' AND COALESCE(form_status, 'Open') = 'Open'");
    }

    return false;
}

$officeEscaped = mysqli_real_escape_string($conn, $userOffice);
$has_existing_form = office_has_open_form($conn, $userOffice);

echo json_encode([
    'can_submit' => !$has_existing_form,
    'has_pending_form' => $has_existing_form
]);
