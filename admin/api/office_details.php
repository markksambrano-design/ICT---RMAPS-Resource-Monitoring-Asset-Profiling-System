<?php
ob_start();
// Ginamit ang absolute path para siguradong mahanap ang config
include_once(dirname(__FILE__) . "/../../config.php");

// Set JSON header
header('Content-Type: application/json');

if (isset($_GET['name'])) {
    if (ob_get_length()) ob_clean();
    
    $officeName = mysqli_real_escape_string($conn, $_GET['name']);
    
    // 1. Get Office Profile from offices table
    $profileQuery = mysqli_query($conn, "SELECT head_of_office, contact_number, office_email FROM offices WHERE office_name LIKE '$officeName' LIMIT 1");
    $officeData = mysqli_fetch_assoc($profileQuery);
    
    $headName = 'Not Set';
    $contact = 'Not Set';
    $email = 'Not Set';

    if ($officeData) {
        $headName = $officeData['head_of_office'] ?: 'Not Set';
        $contact = $officeData['contact_number'] ?: 'Not Set';
        $email = $officeData['office_email'] ?: 'Not Set';
    } else {
        // Fallback to users table if not in offices
        $userQuery = mysqli_query($conn, "SELECT first_name, last_name, email FROM users WHERE office LIKE '$officeName' LIMIT 1");
        $userData = mysqli_fetch_assoc($userQuery);
        if ($userData) {
            $headName = $userData['first_name'] . ' ' . $userData['last_name'];
            $email = $userData['email'];
        }
    }

    $profile = [
        'head_of_office' => $headName,
        'contact_number' => $contact,
        'office_email' => $email
    ];

    // 2. Get Maintenance Reports (Alerts)
    $reports = [];
    // Safeguard: Create table if not exists to prevent query failure
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS maintenance_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        source ENUM('user', 'admin') NOT NULL,
        office_name VARCHAR(255) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        problem_description TEXT NOT NULL,
        status ENUM('pending', 'in_progress', 'resolved', 'cancelled') DEFAULT 'pending',
        urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        reported_by VARCHAR(255),
        reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        admin_remarks TEXT
    )");
    
    $reportsQuery = mysqli_query($conn, "SELECT * FROM maintenance_reports WHERE office_name LIKE '$officeName' AND status != 'resolved' ORDER BY reported_at DESC");
    while ($r = mysqli_fetch_assoc($reportsQuery)) {
        $reports[] = $r;
    }

    // 3. Get Equipment with Specs
    $equipment = [];

    // Combine results from ict_forms and computer_equipment
    $sql = "SELECT item, units as total, brand, processor, ram, hdd, ssd FROM ict_forms WHERE office_name LIKE '$officeName' AND item IS NOT NULL AND item != ''
            UNION ALL
            SELECT item, number_of_units as total, brand, processor, ram, hdd, ssd FROM computer_equipment WHERE office_name LIKE '$officeName' AND item IS NOT NULL AND item != ''
            ORDER BY item ASC";
            
    $equipQuery = mysqli_query($conn, $sql);
    
    if ($equipQuery) {
        while ($row = mysqli_fetch_assoc($equipQuery)) {
            $count = (int)$row['total'];
            
            // If count is more than 1, we duplicate the entry for each unit
            // as per user request to see individual units
            for ($i = 1; $i <= $count; $i++) {
                 $equipment[] = [
                     'item' => $row['item'],
                     'unit_no' => $count > 1 ? $i : null,
                     'count' => 1, // Each entry is now 1 unit
                     'brand' => $row['brand'] ?: 'N/A',
                     'processor' => $row['processor'] ?: 'N/A',
                     'ram' => $row['ram'] ?: 'N/A',
                     'hdd' => $row['hdd'] ?: 'N/A',
                     'ssd' => $row['ssd'] ?: 'N/A'
                 ];
             }
        }
    }

    echo json_encode([
        'profile' => $profile,
        'equipment' => $equipment,
        'reports' => $reports
    ]);
} else {
    echo json_encode(['error' => 'Missing office name']);
}
exit;
?>