<?php
include "../config.php";

// If already logged in, redirect to dashboard immediately
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error_message = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
$success_message = isset($_SESSION['register_success']) ? $_SESSION['register_success'] : '';
$new_user_email = isset($_SESSION['new_user_email']) ? $_SESSION['new_user_email'] : '';

// Clear messages after displaying
unset($_SESSION['login_error']);
unset($_SESSION['register_success']);
unset($_SESSION['new_user_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>User Login - ICTMIS</title>
    <link rel="icon" type="image/png" href="../assest/images/logo3.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            --header-bg: #0f172a;
            --text-main: #2d3748;
            --text-secondary: #718096;
            --accent-color: #0f172a;
        }
        html, body {
            background-color: #1a2a3a;
            background: linear-gradient(135deg, #162e4a 0%, #1e3a5f 100%);
            margin: 0;
            padding: 0;
            height: 100%;
        }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        .login-container {
            background: #fff;
            width: 100%;
            height: 100vh;
            display: flex;
            overflow: hidden;
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
            animation: slideInLeft 0.8s ease-out;
        }
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
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
            animation: glowText 1.5s ease-in-out;
        }
        @keyframes glowText {
            0% {
                text-shadow: 0 0 0 rgba(255,255,255,0);
            }
            50% {
                text-shadow: 0 0 20px rgba(255,255,255,0.5);
            }
            100% {
                text-shadow: 0 0 0 rgba(255,255,255,0);
            }
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
            animation: floatImage 3s ease-in-out infinite;
        }
        @keyframes floatImage {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }
        .login-form-side {
            padding: 60px;
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            animation: slideInRight 0.8s ease-out;
        }
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .login-form-content {
            width: 100%;
            max-width: 450px;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            animation: bounceIcon 1s ease-in-out;
        }
        @keyframes bounceIcon {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        .input-box {
            margin-bottom: 25px;
            position: relative;
            animation: slideUp 0.5s ease-out backwards;
        }
        .input-box:nth-child(1) { animation-delay: 0.1s; }
        .input-box:nth-child(2) { animation-delay: 0.2s; }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .input-box label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            transition: color 0.3s;
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
            transform: translateY(-2px);
        }
        .input-box i {
            position: absolute;
            right: 15px;
            top: 40px;
            color: #cbd5e0;
            transition: all 0.3s;
        }
        .input-box input:focus + i {
            color: #0f172a;
            transform: scale(1.1);
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
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.5s ease-out 0.3s both;
        }
        .btn-login-gradient:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(15, 23, 42, 0.4);
        }
        .btn-login-gradient:active {
            transform: translateY(0);
        }
        .options {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 30px;
            animation: fadeInUp 0.5s ease-out 0.25s both;
        }
        .options a {
            color: #0f172a;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .options a:hover {
            color: #3b4b6e;
            transform: translateX(3px);
            display: inline-block;
        }
        .sign-up-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b7280;
            animation: fadeInUp 0.5s ease-out 0.35s both;
        }
        .sign-up-link a {
            color: #0f172a;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
        }
        .sign-up-link a:hover {
            color: #3b4b6e;
            letter-spacing: 0.5px;
        }
        .alert {
            animation: shakeAlert 0.5s ease-out;
        }
        @keyframes shakeAlert {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-5px);
            }
            75% {
                transform: translateX(5px);
            }
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
    <div class="login-form-side">
        <div class="login-form-content">
            <div class="brand-header">
                <a href="../main_page.php" class="btn btn-outline-secondary mb-4" style="border-radius: 10px; padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px; border-width: 2px; font-weight: 500;">
                    <i class="bi bi-arrow-left-circle"></i> Back to Main Page
                </a>
                <h3><i class="bi bi-person-fill"></i> User Login</h3>
                <p style="color: #718096; margin-top: 5px;">ICTMIS Management System</p>
            </div>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" style="border-radius: 12px; border: none; background: #fff5f5; color: #c53030; font-size: 14px; margin-bottom: 25px;">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" style="border-radius: 12px; border: none; background: #f0fff4; color: #2f855a; font-size: 14px; margin-bottom: 25px;">
                 <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="action/login_process.php">
                <div class="input-box">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="name@company.com" value="<?php echo !empty($new_user_email) ? htmlspecialchars($new_user_email) : ''; ?>">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div class="input-box">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••" value="">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <div class="options">
                    <label style="display: flex; align-items: center; gap: 8px; color: #718096;">
                        <input type="checkbox" id="remember" name="remember" style="width: auto;"> Remember me
                    </label>
                    <a href="#">Forgot password?</a>
                </div>
                <button name="login" type="submit" class="btn-login-gradient">Sign In</button>
            </form>
            <div class="sign-up-link">
                Contact the ICT Office to request an account.
            </div>
        </div>
    </div>
    <div class="login-illustration">
        <h2>ICTMIS</h2>
        <p>Information Systems Strategic Plan<br>Data Collection System</p>
        <img src="../assest/images/login.png" alt="Illustration" class="illustration-img">
    </div>
</div>
<script>
        document.addEventListener('keydown', function(event) {
        if (event.ctrlKey && event.shiftKey && event.key === 'V') {
            window.location.href = '../admin/login.php';
        }
    });
    
    // Add hover animation to input boxes
    document.querySelectorAll('.input-box input').forEach(input => {
        input.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        input.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
</script>
</body>
</html>