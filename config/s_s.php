<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$success = "";
$error = "";

function getDbConnection($createDbIfNeeded = false) {
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'ict_mis';
    
    $conn = mysqli_connect($host, $user, $password);
    
    if (!$conn) {
        return false;
    }
    
    if ($createDbIfNeeded) {
        mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$database`");
    }
    
    mysqli_select_db($conn, $database);
    
    return $conn;
}

if (isset($_POST['backup_database'])) {
    $conn = getDbConnection();
    
    if (!$conn) {
        $error = "Database connection failed. Please make sure the database exists.";
    } else {
        $tables = array();
        $result = mysqli_query($conn, "SHOW TABLES");
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
        
        $return = '';
        foreach ($tables as $table) {
            $result = mysqli_query($conn, "SELECT * FROM $table");
            $num_fields = mysqli_num_fields($result);
            
            $return .= "DROP TABLE IF EXISTS $table;";
            $row2 = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE $table"));
            $return .= "\n\n" . $row2[1] . ";\n\n";
            
            for ($i = 0; $i < $num_fields; $i++) {
                while ($row = mysqli_fetch_row($result)) {
                    $return .= "INSERT INTO $table VALUES(";
                    for ($j = 0; $j < $num_fields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        if (isset($row[$j])) {
                            $return .= '"' . $row[$j] . '"';
                        } else {
                            $return .= '""';
                        }
                        if ($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
            }
            $return .= "\n\n\n";
        }
        
        $filename = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $return;
        exit;
    }
}

if (isset($_POST['import_database'])) {
    $conn = getDbConnection(true);
    
    if (!$conn) {
        $error = "Database connection failed. Please check your database server.";
    } else {
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] == 0) {
            $file_path = $_FILES['sql_file']['tmp_name'];
            $file_content = file_get_contents($file_path);
            
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
            
            $sql_statements = explode(';', $file_content);
            
            foreach ($sql_statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    mysqli_query($conn, $statement);
                }
            }
            
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
            
            $success = "Database imported successfully!";
        } else {
            $error = "Error uploading file. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Secret System Settings | ICT-RMAPS</title>
    <link rel="icon" type="image/png" href="../assest/images/logo3.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            background: #050a15;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../assest/images/mainpage.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: brightness(0.2) grayscale(0.7) contrast(1.3) saturate(0.3);
            z-index: 0;
        }
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(10, 5, 15, 0.95) 0%, rgba(5, 5, 10, 0.98) 100%);
            z-index: 0;
        }
        .main-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 30px;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .header {
            text-align: center;
            margin-bottom: 50px;
            animation: glowPulse 3s ease-in-out infinite;
        }
        .header h1 {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ff0080 0%, #7928ca 50%, #00d4ff 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1rem;
            color: #888;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .secret-card {
            background: rgba(10, 10, 20, 0.85);
            border: 1px solid rgba(100, 50, 200, 0.3);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 40px rgba(100, 50, 200, 0.2);
            animation: fadeInUp 0.8s ease-out;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(100, 50, 200, 0.2);
        }
        .section-title i {
            font-size: 1.8rem;
            color: #ff0080;
        }
        .section-title h2 {
            font-size: 1.5rem;
            color: #fff;
            font-weight: 700;
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: rgba(20, 20, 40, 0.5);
            border-radius: 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(100, 50, 200, 0.1);
            transition: all 0.3s ease;
        }
        .setting-item:hover {
            border-color: rgba(100, 50, 200, 0.4);
            background: rgba(30, 30, 50, 0.6);
            transform: translateX(5px);
        }
        .setting-info h3 {
            color: #fff;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        .setting-info p {
            color: #888;
            font-size: 0.85rem;
        }
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #333;
            transition: 0.4s;
            border-radius: 30px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background: linear-gradient(135deg, #ff0080 0%, #7928ca 100%);
        }
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff0040 0%, #cc0033 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 0, 64, 0.4);
        }
        .btn-back {
            background: rgba(50, 50, 80, 0.5);
            color: #fff;
            padding: 12px 30px;
            border-radius: 10px;
            border: 1px solid rgba(100, 50, 200, 0.3);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-back:hover {
            background: rgba(70, 70, 100, 0.6);
            border-color: rgba(100, 50, 200, 0.5);
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 50px;
            color: #00d4ff;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-indicator .dot {
            width: 10px;
            height: 10px;
            background: #00d4ff;
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        .alert-success {
            background: rgba(0, 255, 100, 0.1);
            border: 1px solid rgba(0, 255, 100, 0.3);
            color: #00ff64;
        }
        .alert-danger {
            background: rgba(255, 0, 64, 0.1);
            border: 1px solid rgba(255, 0, 64, 0.3);
            color: #ff0040;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #fff;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(20, 20, 40, 0.5);
            border: 1px solid rgba(100, 50, 200, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: rgba(100, 50, 200, 0.5);
            background: rgba(30, 30, 50, 0.6);
        }
        .footer-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }
        @keyframes glowPulse {
            0%, 100% {
                filter: drop-shadow(0 0 20px rgba(255, 0, 128, 0.3));
            }
            50% {
                filter: drop-shadow(0 0 40px rgba(121, 40, 202, 0.5));
            }
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
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1><i class="fas fa-terminal"></i> SECRET SYSTEM SETTINGS</h1>
            <p>System Configuration - Restricted Access</p>
        </div>

        <!-- STATUS MESSAGES -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="secret-card">
            <div class="section-title">
                <i class="fas fa-database"></i>
                <h2>Database Management</h2>
            </div>
            
            <!-- DATABASE BACKUP -->
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Backup Database</h3>
                    <p>Download complete SQL backup of your database</p>
                </div>
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="backup_database" class="btn-danger">
                        <i class="fas fa-download"></i> Create Backup
                    </button>
                </form>
            </div>
            
            <!-- DATABASE IMPORT -->
            <div class="setting-item" style="flex-direction: column; align-items: flex-start;">
                <div class="setting-info" style="margin-bottom: 20px;">
                    <h3>Import Database</h3>
                    <p>Upload and restore a SQL backup file</p>
                </div>
                <form method="POST" enctype="multipart/form-data" style="width: 100%;">
                    <div class="form-group">
                        <label for="sql_file">Select SQL File</label>
                        <input type="file" name="sql_file" id="sql_file" class="form-control" accept=".sql" required>
                    </div>
                    <button type="submit" name="import_database" class="btn-danger">
                        <i class="fas fa-upload"></i> Import Database
                    </button>
                </form>
            </div>
        </div>

        <div class="secret-card">
            <div class="section-title">
                <i class="fas fa-shield-alt"></i>
                <h2>System Security Override</h2>
            </div>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Developer Mode</h3>
                    <p>Enable debugging and advanced system tools</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="devMode">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>System Debug Logs</h3>
                    <p>Show real-time system activity logs</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="debugLogs">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="secret-card">
            <div class="section-title">
                <i class="fas fa-info-circle"></i>
                <h2>System Status</h2>
            </div>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Secret Module Status</h3>
                    <p>Current operational status</p>
                </div>
                <span class="status-indicator">
                    <span class="dot"></span> ACTIVE
                </span>
            </div>
        </div>

        <div class="footer-buttons">
            <a href="../main_page.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Main Page
            </a>
        </div>
    </div>

    <script>
        document.getElementById('devMode').addEventListener('change', function() {
            if (this.checked) {
                alert('Developer Mode Enabled!');
            }
        });
        
        document.getElementById('debugLogs').addEventListener('change', function() {
            if (this.checked) {
                alert('Debug Logs Enabled!');
            }
        });
    </script>
</body>
</html>