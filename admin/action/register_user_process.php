<?php
include __DIR__ . '/../../config.php';

$errors = [];
$form_data = [];

$office = trim($_POST['office'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$form_data['office'] = $office;
$form_data['first_name'] = $first_name;
$form_data['last_name'] = $last_name;
$form_data['email'] = $email;

if ($office === '') {
    $errors[] = "Office is required.";
}
if ($first_name === '') {
    $errors[] = "First name is required.";
}
if ($last_name === '') {
    $errors[] = "Last name is required.";
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required.";
}
if ($password === '') {
    $errors[] = "Password is required.";
}
if ($confirm_password === '') {
    $errors[] = "Confirm password is required.";
}
if ($password !== '' && $confirm_password !== '' && $password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}
if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
}

if (count($errors) > 0) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['form_data'] = $form_data;
    header('Location: ../register_user.php');
    exit;
}

// check existing user (email) in users table
$check_sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $check_sql);
if (!$stmt) {
    $_SESSION['register_errors'] = ["Database error: " . mysqli_error($conn)];
    $_SESSION['form_data'] = $form_data;
    header('Location: ../register_user.php');
    exit;
}
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    $_SESSION['register_errors'] = ["This email is already registered. Please use a different email."];
    $_SESSION['form_data'] = $form_data;
    mysqli_stmt_close($stmt);
    header('Location: ../register_user.php');
    exit;
}
mysqli_stmt_close($stmt);

// check if email exists in admin table
$check_admin_sql = "SELECT id FROM admin WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $check_admin_sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['register_errors'] = ["This email is reserved for the administrator. Please use a different email."];
        $_SESSION['form_data'] = $form_data;
        mysqli_stmt_close($stmt);
        header('Location: ../register_user.php');
        exit;
    }
    mysqli_stmt_close($stmt);
}

// check if office already has an account
$check_office_sql = "SELECT id FROM users WHERE office = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $check_office_sql);
if (!$stmt) {
    $_SESSION['register_errors'] = ["Database error: " . mysqli_error($conn)];
    $_SESSION['form_data'] = $form_data;
    header('Location: ../register_user.php');
    exit;
}
mysqli_stmt_bind_param($stmt, 's', $office);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    $_SESSION['register_errors'] = ["This office already has an account. Please coordinate with your ICT officer."];
    $_SESSION['form_data'] = $form_data;
    mysqli_stmt_close($stmt);
    header('Location: ../register_user.php');
    exit;
}
mysqli_stmt_close($stmt);

// check existing user (first name, last name, and office)
$check_name_sql = "SELECT id FROM users WHERE first_name = ? AND last_name = ? AND office = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $check_name_sql);
if (!$stmt) {
    $_SESSION['register_errors'] = ["Database error: " . mysqli_error($conn)];
    $_SESSION['form_data'] = $form_data;
    header('Location: ../register_user.php');
    exit;
}
mysqli_stmt_bind_param($stmt, 'sss', $first_name, $last_name, $office);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    $_SESSION['register_errors'] = ["There is already an account with the same name in this office."];
    $_SESSION['form_data'] = $form_data;
    mysqli_stmt_close($stmt);
    header('Location: ../register_user.php');
    exit;
}
mysqli_stmt_close($stmt);

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$insert_sql = "INSERT INTO users (office, first_name, last_name, email, password) VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_sql);
if (!$stmt) {
    $_SESSION['register_errors'] = ["Database error: " . mysqli_error($conn)];
    $_SESSION['form_data'] = $form_data;
    header('Location: ../register_user.php');
    exit;
}
mysqli_stmt_bind_param($stmt, 'sssss', $office, $first_name, $last_name, $email, $hashed_password);
$executed = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$executed) {
    $_SESSION['register_errors'] = ["Unable to register user: " . mysqli_error($conn)];
    $_SESSION['form_data'] = $form_data;
    header('Location: ../register_user.php');
    exit;
}

$_SESSION['register_success'] = "Registration successful. The new user has been registered.";
header('Location: ../users.php');
exit;
