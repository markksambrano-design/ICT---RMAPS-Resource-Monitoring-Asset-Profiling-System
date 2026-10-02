<?php
include __DIR__ . '/../../config.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['login_error'] = 'Please enter a valid email and password.';
    $_SESSION['new_user_email'] = $email;
    header('Location: ../login.php');
    exit;
}

$sql = "SELECT id, office, first_name, last_name, password FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    $_SESSION['login_error'] = 'Database error: ' . mysqli_error($conn);
    header('Location: ../login.php');
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) !== 1) {
    $_SESSION['login_error'] = 'Email or password is incorrect.';
    $_SESSION['new_user_email'] = $email;
    mysqli_stmt_close($stmt);
    header('Location: ../login.php');
    exit;
}

mysqli_stmt_bind_result($stmt, $id, $office, $first_name, $last_name, $hash_password);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!password_verify($password, $hash_password)) {
    $_SESSION['login_error'] = 'Email or password is incorrect.';
    $_SESSION['new_user_email'] = $email;
    header('Location: ../login.php');
    exit;
}

// Success login
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $id;
$_SESSION['office'] = $office;
$_SESSION['user_office'] = $office; // user/profile component uses this key
$_SESSION['user_name'] = $first_name . ' ' . $last_name;

// Use JS to replace login history with dashboard
echo '<script>window.location.replace("../dashboard.php");</script>';
exit;
