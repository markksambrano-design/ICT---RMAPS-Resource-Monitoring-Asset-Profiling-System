<?php
include "../../config.php";

if (!isset($_SESSION['email']) || $_SESSION['role'] != "admin") {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['source']) || !isset($_GET['status'])) {
    header("Location: ../report.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$source = mysqli_real_escape_string($conn, $_GET['source']);
$status = mysqli_real_escape_string($conn, $_GET['status']);

$allowed_sources = ['ict_forms', 'user_issp_form'];
$allowed_status = ['Open', 'Closed'];

if (!in_array($source, $allowed_sources) || !in_array($status, $allowed_status)) {
    header("Location: ../report.php");
    exit;
}

$query = "UPDATE {$source} SET form_status = '{$status}' WHERE id = '{$id}'";
if (mysqli_query($conn, $query)) {
    header("Location: ../report.php");
} else {
    echo "Error updating status: " . mysqli_error($conn);
}
