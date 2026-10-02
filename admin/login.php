<?php
include "../config.php";

// if already logged in, send user to admin dashboard directly
if(isset($_SESSION['email'])){
    header("Location: dashboard.php");
    exit;
}

$error="";

if(isset($_POST['login'])){

    // sanitize inputs to prevent SQL injection
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    // stored passwords are MD5 hashed in database
    $pass = md5($_POST['password']);

    $q = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email' AND password='$pass'");

    if(mysqli_num_rows($q)>0){

        $row=mysqli_fetch_assoc($q);

        $_SESSION['email']=$row['email'];
        $_SESSION['role']=$row['role'];
        $_SESSION['name']=isset($row['name']) ? $row['name'] : '';

        // Redirect to admin dashboard
        header("Location: dashboard.php");
        exit;

    }else{
        $error="Invalid Email or Password";
    }

}
?>

<!DOCTYPE html>
<html>
<head>

<title>ICTMIS Login</title>

<link rel="icon" type="image/png" href="../assest/images/logo3.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary-gradient: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    --header-bg: #0f172a;
    --text-main: #2d3748;
    --text-secondary: #718096;
    --accent-color: #0f172a;
}

body {
    background-color: #1a2a3a;
    background: linear-gradient(135deg, #162e4a 0%, #1e3a5f 100%);
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    display: flex;
    margin: 0;
}

.login-container {
    background: #fff;
    width: 100%;
    height: 100vh;
    display: flex;
    overflow: hidden;
    animation: slideUp 0.8s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-illustration {
    background: var(--primary-gradient);
    padding: 60px 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    flex: 1;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.login-illustration::before {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    top: -120px;
    left: -120px;
}

.login-illustration h2 {
    font-size: 64px;
    font-weight: 800;
    margin-bottom: 25px;
    letter-spacing: 2px;
    z-index: 1;
    text-transform: uppercase;
}

.login-illustration p {
    font-size: 22px;
    line-height: 1.5;
    margin-bottom: 50px;
    opacity: 0.8;
    z-index: 1;
    max-width: 450px;
}

.login-illustration .illustration-img {
    width: 100%;
    max-width: 500px;
    z-index: 1;
    filter: drop-shadow(0 20px 30px rgba(0,0,0,0.2));
}

.login-form-side {
    padding: 60px;
    flex: 1.2;
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.login-form-content {
    width: 100%;
    max-width: 450px;
}

.brand-header {
    margin-bottom: 40px;
}

.brand-header h3 {
    font-weight: 700;
    color: #1e293b;
    font-size: 28px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-header h3 i {
    color: #0f172a;
}

.input-box {
    margin-bottom: 25px;
    position: relative;
}

.input-box label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.input-box input {
    width: 100%;
    padding: 14px 20px;
    border-radius: 12px;
    border: 2px solid #edf2f7;
    background: #f8fafc;
    transition: all 0.3s;
    font-size: 15px;
}

.input-box input:focus {
    border-color: #0f172a;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.1);
    outline: none;
}

.input-box i {
    position: absolute;
    right: 15px;
    top: 40px;
    color: #cbd5e0;
}

.btn-login-gradient {
    background: #0f172a;
    color: white;
    border: none;
    padding: 16px;
    border-radius: 12px;
    width: 100%;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.3);
    margin-top: 10px;
}

.btn-login-gradient:hover {
    background: #1e293b;
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(15, 23, 42, 0.4);
}

.options {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin-bottom: 30px;
}

.options a {
    color: #0f172a;
    text-decoration: none;
    font-weight: 500;
}

@media (max-width: 850px) {
    .login-illustration {
        display: none;
    }
    .login-form-side {
        width: 100%;
        padding: 40px 20px;
    }
}
</style>

</head>

<body>

<div class="login-container">
    <div class="login-illustration">
        <h2>ICTMIS</h2>
        <p>Information Systems Strategic Plan<br>Data Collection System</p>
        <!-- Using local image as requested -->
        <img src="../assest/images/admin.png" alt="Illustration" class="illustration-img">
    </div>

    <div class="login-form-side">
        <div class="login-form-content">
            <div class="brand-header">
                <a href="../main_page.php" class="btn btn-outline-secondary mb-4" style="border-radius: 10px; padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px; border-width: 2px; font-weight: 500;">
                    <i class="bi bi-arrow-left-circle"></i> Back to Main Page
                </a>
                <h3><i class="bi bi-shield-lock-fill"></i> Admin Login</h3>
                <p style="color: #718096; margin-top: 5px;">ICTMIS Management System</p>
            </div>

            <?php if($error!=""){ ?>
                <div class="alert alert-danger" style="border-radius: 12px; border: none; background: #fff5f5; color: #c53030; font-size: 14px; margin-bottom: 25px;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?>
                </div>
            <?php } ?>

            <form method="POST">
                <div class="input-box">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="name@company.com">
                    <i class="bi bi-envelope-fill"></i>
                </div>

                <div class="input-box">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                    <i class="bi bi-lock-fill"></i>
                </div>

                <div class="options">
                    <label style="display: flex; align-items: center; gap: 8px; color: #718096;">
                        <input type="checkbox" style="width: auto;"> Remember me
                    </label>
                    <a href="#">Forgot password?</a>
                </div>

                <button name="login" type="submit" class="btn-login-gradient">Sign In</button>
            </form>
        </div>
    </div>
</div>

<script>
// Keyboard shortcut: Ctrl+Shift+Z to navigate to user login page
document.addEventListener('keydown', function(event) {
    if (event.ctrlKey && event.shiftKey && event.key === 'Z') {
        event.preventDefault();
        window.location.href = '../user/login.php';
    }
});
</script>

</body>

</html>

