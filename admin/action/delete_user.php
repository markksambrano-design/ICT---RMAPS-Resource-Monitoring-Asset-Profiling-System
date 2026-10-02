<?php
include "../../config.php";
include "../../config/auth_check.php";

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

    $delete = mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    if (!$delete) {
        echo "Error deleting user: " . mysqli_error($conn);
        exit;
    }

    // Rebuild IDs from 1 after delete by temporary table copy
    mysqli_query($conn, "CREATE TEMPORARY TABLE tmp_users LIKE users");
    mysqli_query($conn, "ALTER TABLE tmp_users AUTO_INCREMENT = 1");
    mysqli_query($conn, "INSERT INTO tmp_users (office, first_name, last_name, email, password, created_at) SELECT office, first_name, last_name, email, password, created_at FROM users ORDER BY id");
    mysqli_query($conn, "TRUNCATE TABLE users");
    mysqli_query($conn, "INSERT INTO users (office, first_name, last_name, email, password, created_at) SELECT office, first_name, last_name, email, password, created_at FROM tmp_users");
    mysqli_query($conn, "DROP TEMPORARY TABLE tmp_users");

    header('Location: ../users.php?msg=deleted');
    exit;
}

header('Location: ../users.php');
exit;
