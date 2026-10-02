<?php
// config.php should contain database connection and session start
include "../config.php";

// Authentication Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

/**
 * Equipment Dashboard Controller
 * Handles all data retrieval and processing for the dashboard
 */
class DashboardController {
    private $conn;
    private $userOffice;
    private $officeParam;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->userOffice = trim($_SESSION['user_office'] ?? $_SESSION['office'] ?? '');
        $this->officeParam = mb_strtolower($this->userOffice);
        $this->ensureTablesExist();
    }
    
    /**
     * Create necessary tables if they don't exist
     */
    private function ensureTablesExist() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS user_issp_form (
                id INT AUTO_INCREMENT PRIMARY KEY,
                office_name VARCHAR(150),
                date_submitted DATE,
                item VARCHAR(100),
                units INT DEFAULT 0,
                brand VARCHAR(100),
                processor VARCHAR(100),
                ram VARCHAR(50),
                hdd VARCHAR(50),
                ssd VARCHAR(50),
                equipment_image TEXT,
                system1 TEXT,
                system2 TEXT,
                system3 TEXT,
                system4 TEXT,
                system5 TEXT,
                proposed_system1 TEXT,
                proposed_system2 TEXT,
                proposed_system3 TEXT,
                proposed_system4 TEXT,
                proposed_system5 TEXT,
                form_status VARCHAR(20) DEFAULT 'Open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $sql) {
            mysqli_query($this->conn, $sql);
        }
    }
    
    /**
     * Get equipment data organized by year and category
     */
    public function getEquipmentData() {
        if (empty($this->userOffice)) {
            return $this->getEmptyDataStructure();
        }
        
        $data = $this->getEmptyDataStructure();
        
        // 1. Fetch from user_issp_form
        $this->fetchUserEquipmentData($data);
        
        // 2. Fetch from admin computer_equipment
        $this->fetchAdminComputerData($data);
        
        // 3. Fetch from admin ict_equipment_inventory
        $this->fetchAdminIctData($data);
        
        return $this->formatDataForOutput($data);
    }
    
    /**
     * Fetch equipment data from user_issp_form table
     */
    private function fetchUserEquipmentData(&$data) {
        $sql = "SELECT 
                    YEAR(date_submitted) as year,
                    item,
                    SUM(COALESCE(units, 0)) as total,
                    SUM(CASE WHEN LOWER(status) = 'operational' THEN COALESCE(units, 0) ELSE 0 END) as operational
                FROM user_issp_form 
                WHERE LOWER(TRIM(office_name)) = ? 
                    AND item IS NOT NULL
                    AND item != ''
                GROUP BY YEAR(date_submitted), item";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $this->officeParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $this->appendResultsToData($result, $data);
            $stmt->close();
        }
    }
    
    /**
     * Fetch computer equipment from admin tables
     */
    private function fetchAdminComputerData(&$data) {
        $sql = "SELECT YEAR(f.date_submitted) as year, ce.item, SUM(ce.number_of_units) as total, 
                  SUM(ce.number_of_units) as operational
                  FROM computer_equipment ce 
                  JOIN form f ON f.id = ce.form_id 
                  WHERE LOWER(TRIM(f.office_name)) = ? 
                  GROUP BY YEAR(f.date_submitted), ce.item";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $this->officeParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $this->appendResultsToData($result, $data);
            $stmt->close();
        }
    }

    /**
     * Fetch ICT equipment from admin tables
     */
    private function fetchAdminIctData(&$data) {
        $sql = "SELECT YEAR(date_submitted) as year, 
                          SUM(inkjet_printer) as inkjet, 
                          SUM(deskjet_printer) as deskjet, 
                          SUM(dotmatrix_printer) as dotmatrix, 
                          SUM(switch_hubs) as switch_hubs, 
                          SUM(routers) as routers, 
                          SUM(modem) as modem 
                   FROM ict_equipment_inventory 
                   WHERE LOWER(TRIM(office_name)) = ? 
                   GROUP BY YEAR(date_submitted)";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $this->officeParam);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $year = $row['year'];
                $data['years'][$year] = $year;
                
                $mapping = [
                    'inkjet' => (int)$row['inkjet'],
                    'deskjet' => (int)$row['deskjet'],
                    'dotmatrix' => (int)$row['dotmatrix'],
                    'switch_hubs' => (int)$row['switch_hubs'],
                    'routers' => (int)$row['routers'],
                    'modem' => (int)$row['modem']
                ];
                
                foreach ($mapping as $cat => $val) {
                    if ($val > 0) {
                        if (!isset($data['categories'][$cat][$year])) {
                            $data['categories'][$cat][$year] = ['total' => 0, 'operational' => 0];
                        }
                        $data['categories'][$cat][$year]['total'] += $val;
                        $data['categories'][$cat][$year]['operational'] += $val;
                    }
                }
            }
            $stmt->close();
        }
    }
    
    /**
     * Helper to append generic item results to the data structure
     */
    private function appendResultsToData($result, &$data) {
        while ($row = $result->fetch_assoc()) {
            $year = $row['year'];
            $item = strtolower(trim($row['item']));
            $total = (int)$row['total'];
            $operational = (int)($row['operational'] ?? $total);
            
            if ($total <= 0) continue;
            
            $data['years'][$year] = $year;
            
            // Map items to categories
            $categoryMap = [
                'desktop' => ['desktop computer', 'desktop', 'desktop pc'],
                'laptop' => ['laptop', 'laptop computer', 'notebook'],
                'inkjet' => ['inkjet printer', 'inkjet'],
                'deskjet' => ['deskjet printer', 'deskjet'],
                'dotmatrix' => ['dot matrix printer', 'dotmatrix', 'dot matrix'],
                'switch_hubs' => ['switch hub', 'switch', 'switch_hub', 'hub'],
                'routers' => ['router', 'wifi router'],
                'modem' => ['modem', 'dsl modem']
            ];
            
            foreach ($categoryMap as $category => $keywords) {
                if (in_array($item, $keywords)) {
                    if (!isset($data['categories'][$category][$year])) {
                        $data['categories'][$category][$year] = ['total' => 0, 'operational' => 0];
                    }
                    $data['categories'][$category][$year]['total'] += $total;
                    $data['categories'][$category][$year]['operational'] += $operational;
                    break;
                }
            }
        }
    }
    
    /**
     * Format raw data into the final output structure
     */
    private function formatDataForOutput($data) {
        ksort($data['years']);
        $years = array_values($data['years']);
        
        $output = [
            'years' => $years,
            'categories' => []
        ];
        
        $categoryDefinitions = [
            'desktop' => 'Desktop Computers',
            'laptop' => 'Laptops',
            'inkjet' => 'Inkjet Printers',
            'deskjet' => 'Deskjet Printers',
            'dotmatrix' => 'Dot Matrix Printers',
            'switch_hubs' => 'Switch Hubs',
            'routers' => 'Routers',
            'modem' => 'Modems'
        ];
        
        foreach ($categoryDefinitions as $key => $label) {
            $totalData = [];
            $operationalData = [];
            $totalUnits = 0;
            $totalOperational = 0;
            
            // Ensure we have data for all years to keep array lengths consistent for JS
            foreach ($years as $year) {
                $entry = $data['categories'][$key][$year] ?? ['total' => 0, 'operational' => 0];
                $totalData[] = $entry['total'];
                $operationalData[] = $entry['operational'];
                $totalUnits += $entry['total'];
                $totalOperational += $entry['operational'];
            }
            
            $output['categories'][$key] = [
                'label' => $label,
                'data' => $totalData,
                'operational_data' => $operationalData,
                'total' => $totalUnits,
                'total_operational' => $totalOperational
            ];
        }
        
        return $output;
    }
    
    /**
     * Get submission tracking data
     */
    public function getSubmissionData() {
        if (empty($this->userOffice)) {
            return [];
        }
        
        $submissions = $this->fetchUserSubmissions();
        
        if (empty($submissions)) {
            $submissions = $this->fetchAdminSubmissions();
        }
        
        return $submissions;
    }
    
    /**
     * Fetch submissions from user table
     */
    private function fetchUserSubmissions() {
        $sql = "SELECT 
                    DATE(date_submitted) as submission_date,
                    COUNT(*) as total_entries,
                    SUM(COALESCE(units, 0)) as total_units
                FROM user_issp_form 
                WHERE LOWER(TRIM(office_name)) = ? 
                GROUP BY DATE(date_submitted)
                ORDER BY submission_date DESC
                LIMIT 30";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $this->officeParam);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $submissions[] = [
                'date' => $row['submission_date'],
                'entries' => (int)$row['total_entries'],
                'units' => (int)$row['total_units']
            ];
        }
        
        return $submissions;
    }
    
    /**
     * Fetch submissions from admin tables
     */
    private function fetchAdminSubmissions() {
        $sql = "SELECT 
                    DATE(f.date_submitted) as submission_date,
                    COUNT(DISTINCT f.id) as total_entries,
                    SUM(COALESCE(ce.number_of_units, 0)) as total_units
                FROM form f
                LEFT JOIN computer_equipment ce ON ce.form_id = f.id
                WHERE LOWER(TRIM(f.office_name)) = ?
                GROUP BY DATE(f.date_submitted)
                ORDER BY submission_date DESC
                LIMIT 30";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $this->officeParam);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $submissions = [];
            while ($row = $result->fetch_assoc()) {
                $submissions[] = [
                    'date' => $row['submission_date'],
                    'entries' => (int)$row['total_entries'],
                    'units' => (int)$row['total_units']
                ];
            }
            
            return $submissions;
        }
        
        return [];
    }
    
    /**
     * Get overview statistics
     */
    public function getOverviewStats() {
        if (empty($this->userOffice)) {
            return [
                'total_units' => 0,
                'total_submissions' => 0,
                'last_submission' => 'N/A',
                'categories_count' => 0
            ];
        }
        
        $stats = $this->fetchUserStats();
        
        if ($stats['total_submissions'] === 0) {
            $adminStats = $this->fetchAdminStats();
            $stats['total_units'] = max($stats['total_units'], $adminStats['total_units']);
            $stats['total_submissions'] = max($stats['total_submissions'], $adminStats['total_submissions']);
            $stats['last_submission'] = $stats['last_submission'] ?? $adminStats['last_submission'];
        }
        
        return [
            'total_units' => $stats['total_units'],
            'total_submissions' => $stats['total_submissions'],
            'last_submission' => $stats['last_submission'] ? date('M j, Y', strtotime($stats['last_submission'])) : 'None yet',
            'categories_count' => 8
        ];
    }
    
    /**
     * Fetch user stats
     */
    private function fetchUserStats() {
        $sql = "SELECT 
                    COUNT(DISTINCT DATE(date_submitted)) as total_submissions,
                    SUM(COALESCE(units, 0)) as total_units,
                    MAX(date_submitted) as last_submission
                FROM user_issp_form 
                WHERE LOWER(TRIM(office_name)) = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $this->officeParam);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        
        return [
            'total_units' => (int)($stats['total_units'] ?? 0),
            'total_submissions' => (int)($stats['total_submissions'] ?? 0),
            'last_submission' => $stats['last_submission'] ?? null
        ];
    }
    
    /**
     * Fetch admin stats
     */
    private function fetchAdminStats() {
        $sql = "SELECT 
                    COUNT(DISTINCT DATE(f.date_submitted)) as total_submissions,
                    SUM(COALESCE(ce.number_of_units, 0)) as total_units,
                    MAX(f.date_submitted) as last_submission
                FROM form f
                LEFT JOIN computer_equipment ce ON ce.form_id = f.id
                WHERE LOWER(TRIM(f.office_name)) = ?";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $this->officeParam);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            
            return [
                'total_units' => (int)($stats['total_units'] ?? 0),
                'total_submissions' => (int)($stats['total_submissions'] ?? 0),
                'last_submission' => $stats['last_submission'] ?? null
            ];
        }
        
        return ['total_units' => 0, 'total_submissions' => 0, 'last_submission' => null];
    }
    
    /**
     * Get empty data structure
     */
    private function getEmptyDataStructure() {
        return [
            'years' => [],
            'categories' => [
                'desktop' => ['label' => 'Desktop Computers', 'data' => [], 'total' => 0],
                'laptop' => ['label' => 'Laptops', 'data' => [], 'total' => 0],
                'inkjet' => ['label' => 'Inkjet Printers', 'data' => [], 'total' => 0],
                'deskjet' => ['label' => 'Deskjet Printers', 'data' => [], 'total' => 0],
                'dotmatrix' => ['label' => 'Dot Matrix Printers', 'data' => [], 'total' => 0],
                'switch_hubs' => ['label' => 'Switch Hubs', 'data' => [], 'total' => 0],
                'routers' => ['label' => 'Routers', 'data' => [], 'total' => 0],
                'modem' => ['label' => 'Modems', 'data' => [], 'total' => 0]
            ]
        ];
    }
}

// Initialize controller and get data
$dashboard = new DashboardController($conn);
$equipmentData = $dashboard->getEquipmentData();
$submissions = $dashboard->getSubmissionData();
$overviewStats = $dashboard->getOverviewStats();

// Prepare data for JavaScript
$yearsJson = json_encode($equipmentData['years']);
$categoriesJson = json_encode($equipmentData['categories']);
$submissionsJson = json_encode($submissions);

// User info
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$userFirstName = htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]);
$userOffice = htmlspecialchars($_SESSION['user_office'] ?? $_SESSION['office'] ?? 'Municipal Office');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
    $pageTitle = 'Dashboard - ICT Equipment Management';
    include __DIR__ . '/components/head.php'; 
    ?>
    
    <style>
        :root {
            /* Primary Colors */
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            
            /* Status Colors */
            --success: #10b981;
            --success-light: #34d399;
            --warning: #f59e0b;
            --warning-light: #fbbf24;
            --danger: #ef4444;
            --info: #3b82f6;
            
            /* Theme Colors */
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            
            /* Border Radius */
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            
            /* Transitions */
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: #334155;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            transition: background var(--transition), color var(--transition);
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--text-muted);
            border-radius: 4px;
        }
        
        /* Layout */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: margin-left var(--transition);
        }
        
        .sidebar-collapsed .main-content {
            margin-left: 80px;
        }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #c026d3 100%);
            margin: 1.5rem;
            padding: 2.5rem 3rem;
            border-radius: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(79, 70, 229, 0.4);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            filter: blur(40px);
        }
        
        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: 10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            z-index: 0;
            filter: blur(30px);
        }

        .welcome-deco-circle {
            position: absolute;
            top: 20%;
            left: 40%;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            z-index: 1;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: float-illustration 8s ease-in-out infinite;
        }
        
        .welcome-content {
            position: relative;
            z-index: 2;
            max-width: 65%;
        }
        
        .welcome-illustration {
            position: relative;
            z-index: 2;
            width: 30%;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .welcome-illustration .main-icon {
            font-size: 7rem;
            color: rgba(200, 200, 200, 0.3);
            filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.1));
            animation: float-illustration 6s ease-in-out infinite;
            position: relative;
            z-index: 2;
        }

        .welcome-illustration .sub-icon {
            position: absolute;
            font-size: 2.5rem;
            color: rgba(200, 200, 200, 0.2);
            filter: blur(1px);
        }

        .sub-icon-1 { top: 0; right: 0; animation: float-illustration 7s ease-in-out infinite reverse; }
        .sub-icon-2 { bottom: 0; left: -10px; animation: float-illustration 5s ease-in-out infinite; }
        
        .welcome-greeting {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 800;
            opacity: 0.9;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.625rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .welcome-title {
            font-size: 2.75rem;
            font-weight: 900;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
            line-height: 1.1;
            background: linear-gradient(to bottom, #ffffff 60%, rgba(255, 255, 255, 0.7));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 1.75rem;
            font-weight: 500;
            max-width: 500px;
        }
        
        .welcome-badges {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            padding: 0.625rem 1.25rem;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
        }

        .badge i {
            font-size: 1rem;
            opacity: 0.9;
        }

        .badge:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        @keyframes float-illustration {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        /* Stats Grid */
        .stats-container {
            padding: 0 1.5rem 1.5rem;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
            transition: all var(--transition);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }

        .stat-card-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .stat-info-group {
            flex: 1;
        }
        
        .stat-icon {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(-5deg);
        }
        
        .stat-icon.purple { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .stat-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        
        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }
        
        .stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-sparkline {
            width: 80px;
            height: 40px;
        }

        .stat-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .stat-trend {
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        
        /* Breakdown Section Split Layout */
        .breakdown-grid-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 1.5rem;
        }

        .donut-chart-container {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            min-height: 400px;
        }

        .donut-wrapper {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 1.5rem 0;
        }

        .donut-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .donut-center-value {
            font-size: 2.25rem;
            font-weight: 800;
            display: block;
            line-height: 1;
            color: var(--text-primary);
        }

        .donut-center-label {
            font-size: 0.625rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .donut-legend {
            width: 100%;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .legend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .legend-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .legend-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        /* Category Cards in Breakdown */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        @media (max-width: 1600px) {
            .category-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 1200px) {
            .category-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .category-item-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            position: relative;
        }

        .category-item-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-light);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
        }

        .cat-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .cat-icon-box {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .cat-stats-top {
            text-align: right;
        }

        .cat-value-top {
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--text-primary);
            line-height: 1;
        }

        .cat-unit-top {
            font-size: 0.625rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cat-name-mid {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-top: 0.25rem;
        }

        .cat-progress-container {
            margin-top: 0.25rem;
        }

        .cat-progress-bar {
            height: 6px;
            background: var(--bg-secondary);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .cat-progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .cat-progress-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--text-muted);
        }
        
        /* Charts Section */
        .charts-section {
            padding: 0 1.5rem 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .chart-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .chart-subtitle {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        /* Yearly Breakdown */
        .breakdown-section {
            padding: 0 1.5rem 1.5rem;
        }
        
        .breakdown-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .breakdown-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .breakdown-title {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .year-filter {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .year-select {
            padding: 0.625rem 2.5rem 0.625rem 1.25rem;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-primary);
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236366f1' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
            min-width: 120px;
            box-shadow: var(--shadow-sm);
        }
        
        .year-select:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        
        .year-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        .breakdown-content {
            padding: 1.5rem;
        }
        
        /* Ultra Modern Grid Breakdown */
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            padding: 0.5rem;
        }
        
        @media (max-width: 1400px) {
            .equipment-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 1100px) {
            .equipment-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 600px) {
            .equipment-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .equipment-item-card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            box-shadow: var(--shadow-sm);
        }
        
        .equipment-item-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }
        
        .equipment-item-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle at top right, var(--card-color-alpha), transparent 70%);
            opacity: 0.5;
            transition: opacity 0.3s ease;
        }
        
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            z-index: 1;
        }
        
        .card-icon-wrapper {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            background: var(--card-bg-alpha);
            color: var(--card-color);
            box-shadow: 0 8px 16px -4px var(--card-color-alpha);
            transition: all 0.3s ease;
        }
        
        .equipment-item-card:hover .card-icon-wrapper {
            transform: rotate(-10deg) scale(1.1);
            box-shadow: 0 12px 20px -4px var(--card-color-alpha);
        }
        
        .card-value-wrapper {
            text-align: right;
        }
        
        .card-main-value {
            font-size: 2.25rem;
            font-weight: 900;
            color: var(--text-primary);
            line-height: 1;
            letter-spacing: -0.02em;
        }
        
        .card-unit-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .card-info {
            z-index: 1;
        }
        
        .card-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .card-description {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        
        .card-progress-wrapper {
            margin-top: 0.5rem;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-secondary);
        }
        
        .ultra-progress {
            height: 8px;
            background: var(--bg-secondary);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .ultra-progress-fill {
            height: 100%;
            border-radius: 10px;
            background: var(--card-gradient);
            transition: width 1.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }
        
        .ultra-progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            animation: ultra-shine 3s infinite linear;
        }
        
        @keyframes ultra-shine {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        
        .total-footer-card {
             background: var(--bg-secondary);
             margin-top: 1.5rem;
             padding: 1.25rem 2rem;
             border-radius: var(--radius-lg);
             border: 1px solid var(--border-color);
             display: flex;
             justify-content: space-between;
             align-items: center;
             color: var(--text-primary);
         }
         
         .total-label-group h4 {
             font-size: 1.125rem;
             font-weight: 700;
             margin: 0;
             color: var(--text-primary);
         }
         
         .total-label-group p {
             font-size: 0.8125rem;
             color: var(--text-muted);
             margin: 0;
         }
         
         .total-value-large {
             font-size: 2rem;
             font-weight: 800;
             color: var(--primary);
         }
        
        /* Submissions Table */
        .submissions-section {
            padding: 0 1.5rem 1.5rem;
        }
        
        .submissions-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .submissions-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            transition: all var(--transition);
            text-decoration: none;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
            box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
        }
        
        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.23);
        }

        .table-hover tbody tr:hover {
            background-color: var(--bg-secondary) !important;
            transform: scale(1.002);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
            
            .welcome-title {
                font-size: 1.75rem;
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .welcome-section {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .equipment-table {
                font-size: 0.875rem;
            }
            
            .equipment-table th,
            .equipment-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php include __DIR__ . '/components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php include __DIR__ . '/components/header.php'; ?>
            
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-deco-circle"></div>
                <div class="welcome-content">
                    <div class="welcome-greeting">
                        <span>WELCOME BACK!</span>
                        <span>👋</span>
                    </div>
                    <div class="welcome-title">
                        <?php echo $userFirstName; ?>'s Dashboard
                    </div>
                    <div class="welcome-subtitle">
                        Here's what's happening with your equipment inventory today.
                    </div>
                    <div class="welcome-badges">
                        <div class="badge">
                            <i class="bi bi-building"></i>
                            <?php echo $userOffice; ?>
                        </div>
                        <div class="badge">
                            <i class="bi bi-calendar3"></i>
                            <?php echo date('F j, Y'); ?>
                        </div>
                        <div class="badge">
                            <i class="bi bi-clock"></i>
                            Last active: Today
                        </div>
                    </div>
                </div>
                <div class="welcome-illustration">
                    <i class="bi bi-pc-display-horizontal main-icon"></i>
                    <i class="bi bi-cpu sub-icon sub-icon-1"></i>
                    <i class="bi bi-gpu-card sub-icon sub-icon-2"></i>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="bi bi-pc-display"></i>
                    </div>
                    <div class="stat-card-main">
                        <div class="stat-info-group">
                            <div class="stat-label">Total Equipment Units</div>
                            <div class="stat-value"><?php echo number_format($overviewStats['total_units']); ?></div>
                        </div>
                        <div class="stat-sparkline">
                            <canvas id="sparkline1"></canvas>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <div class="stat-trend trend-up">
                            <i class="bi bi-arrow-up"></i> 33% <span style="color: var(--text-muted); font-weight: 400;">from last year</span>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                    <div class="stat-card-main">
                        <div class="stat-info-group">
                            <div class="stat-label">Total Submissions</div>
                            <div class="stat-value"><?php echo number_format($overviewStats['total_submissions']); ?></div>
                        </div>
                        <div class="stat-sparkline">
                            <canvas id="sparkline2"></canvas>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <div class="stat-trend trend-up">
                            <i class="bi bi-check-circle"></i> Tracked submissions
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="stat-card-main">
                        <div class="stat-info-group">
                            <div class="stat-label">Latest Submission</div>
                            <div class="stat-value" style="font-size: 1.5rem;"><?php echo $overviewStats['last_submission']; ?></div>
                        </div>
                        <div class="stat-sparkline">
                            <i class="bi bi-calendar3" style="font-size: 2rem; color: var(--warning-light); opacity: 0.5;"></i>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <div class="stat-trend">
                            <i class="bi bi-info-circle"></i> Most recent entry date
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </div>
                    <div class="stat-card-main">
                        <div class="stat-info-group">
                            <div class="stat-label">Equipment Categories</div>
                            <div class="stat-value"><?php echo $overviewStats['categories_count']; ?></div>
                        </div>
                        <div class="stat-sparkline">
                            <i class="bi bi-layers" style="font-size: 2rem; color: var(--info); opacity: 0.3;"></i>
                        </div>
                    </div>
                    <div class="stat-footer">
                        <div class="stat-trend">
                            <i class="bi bi-list-check"></i> Different equipment types
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Yearly Equipment Breakdown -->
            <div class="breakdown-section">
                <div class="breakdown-card">
                    <div class="breakdown-header">
                        <div>
                            <div class="breakdown-title">Detailed Equipment Breakdown</div>
                            <div class="chart-subtitle">View detailed breakdown by category</div>
                        </div>
                        <div class="year-filter" id="yearFilter">
                            <!-- Dynamically populated -->
                        </div>
                    </div>
                    <div class="breakdown-content" id="breakdownContent">
                        <!-- Dynamically populated -->
                    </div>
                </div>
            </div>
            
            <!-- Submission History -->
            <div class="submissions-section">
                <div class="submissions-card">
                    <div class="submissions-header">
                        <div>
                            <div class="breakdown-title">Submission History</div>
                            <div class="chart-subtitle">Recent form submissions with equipment counts</div>
                        </div>
                        <a href="Issp_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            New Submission
                        </a>
                    </div>
                    
                    <div id="submissionsContent">
                        <!-- Dynamically populated -->
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize data from PHP
        const equipmentYears = <?php echo $yearsJson; ?>;
        const equipmentCategories = <?php echo $categoriesJson; ?>;
        const submissionData = <?php echo $submissionsJson; ?>;
        
        // Category definitions with colors and icons
        const categoryStyles = {
            desktop: { color: '#6366f1', gradient: 'linear-gradient(90deg, #6366f1, #818cf8)', icon: 'bi-pc-display', bgColor: 'rgba(99, 102, 241, 0.1)' },
            laptop: { color: '#10b981', gradient: 'linear-gradient(90deg, #10b981, #34d399)', icon: 'bi-laptop', bgColor: 'rgba(16, 185, 129, 0.1)' },
            inkjet: { color: '#f59e0b', gradient: 'linear-gradient(90deg, #f59e0b, #fbbf24)', icon: 'bi-printer', bgColor: 'rgba(245, 158, 11, 0.1)' },
            deskjet: { color: '#f97316', gradient: 'linear-gradient(90deg, #f97316, #fb923c)', icon: 'bi-printer-fill', bgColor: 'rgba(249, 115, 22, 0.1)' },
            dotmatrix: { color: '#ef4444', gradient: 'linear-gradient(90deg, #ef4444, #f87171)', icon: 'bi-printer', bgColor: 'rgba(239, 68, 68, 0.1)' },
            switch_hubs: { color: '#8b5cf6', gradient: 'linear-gradient(90deg, #8b5cf6, #a78bfa)', icon: 'bi-ethernet', bgColor: 'rgba(139, 92, 246, 0.1)' },
            routers: { color: '#3b82f6', gradient: 'linear-gradient(90deg, #3b82f6, #60a5fa)', icon: 'bi-router', bgColor: 'rgba(59, 130, 246, 0.1)' },
            modem: { color: '#d946ef', gradient: 'linear-gradient(90deg, #d946ef, #e879f9)', icon: 'bi-broadcast-pin', bgColor: 'rgba(217, 70, 239, 0.1)' }
        };
        
        // Render Yearly Breakdown
        function renderYearlyBreakdown(selectedYear = null) {
            const yearFilter = document.getElementById('yearFilter');
            const breakdownContent = document.getElementById('breakdownContent');
            
            // Set default selected year
            if (!selectedYear) {
                selectedYear = equipmentYears.length > 0 
                    ? equipmentYears[equipmentYears.length - 1] 
                    : new Date().getFullYear();
            }
            
            // Create year dropdown
            const startYear = 2024;
            const endYear = 2030;
            let options = '';
            
            for (let y = startYear; y <= endYear; y++) {
                const isSelected = y == selectedYear ? 'selected' : '';
                options += `<option value="${y}" ${isSelected}>${y}</option>`;
            }
            
            yearFilter.innerHTML = `
                <div style="position: relative; display: flex; align-items: center;">
                    <i class="bi bi-calendar3" style="position: absolute; left: 1.25rem; color: var(--text-muted); z-index: 1;"></i>
                    <select class="year-select" style="padding-left: 3rem;" onchange="renderYearlyBreakdown(this.value)">
                        ${options}
                    </select>
                </div>
            `;
            
            // Get data for selected year
            const yearIndex = equipmentYears.indexOf(Number(selectedYear));
            const yearData = [];
            let yearTotal = 0;
            
            Object.keys(equipmentCategories).forEach(key => {
                const total = yearIndex !== -1 ? equipmentCategories[key].data[yearIndex] : 0;
                const operational = yearIndex !== -1 ? equipmentCategories[key].operational_data[yearIndex] : 0;
                yearData.push({ 
                    key, 
                    label: equipmentCategories[key].label, 
                    total, 
                    operational,
                    style: categoryStyles[key]
                });
                yearTotal += total;
            });
            
            // Render Split Layout
            breakdownContent.innerHTML = `
                <div class="breakdown-grid-layout">
                    <div class="donut-chart-container">
                        <div class="donut-wrapper">
                            <canvas id="equipmentDonutChart"></canvas>
                            <div class="donut-center-text">
                                <span class="donut-center-value">${yearTotal}</span>
                                <span class="donut-center-label">Total Units</span>
                            </div>
                        </div>
                        <div class="donut-legend">
                            <div class="legend-item">
                                <div class="legend-label">
                                    <span class="legend-dot" style="background: var(--primary);"></span>
                                    Total Equipment Inventory
                                </div>
                                <span class="legend-value">${yearTotal}</span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; font-weight: 500;">
                                Aggregated units for the year ${selectedYear}
                            </div>
                        </div>
                    </div>
                    
                    <div class="category-grid">
                        ${yearData.map(item => {
                            const percent = yearTotal > 0 ? Math.round((item.total / yearTotal) * 100) : 0;
                            return `
                                <div class="category-item-card">
                                    <div class="cat-card-top">
                                        <div class="cat-icon-box" style="background: ${item.style.bgColor}; color: ${item.style.color};">
                                            <i class="bi ${item.style.icon}"></i>
                                        </div>
                                        <div class="cat-stats-top">
                                            <div class="cat-value-top">${item.total}</div>
                                            <div class="cat-unit-top">Units</div>
                                        </div>
                                    </div>
                                    <div class="cat-name-mid">${item.label}</div>
                                    <div class="cat-progress-container">
                                        <div class="cat-progress-bar">
                                            <div class="cat-progress-fill" style="width: ${percent}%; background: ${item.style.color};"></div>
                                        </div>
                                        <div class="cat-progress-labels">
                                            <span>${percent}% of total</span>
                                            <span>${item.operational} Operational</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;

            // Initialize Donut Chart
             const ctx = document.getElementById('equipmentDonutChart').getContext('2d');
             new Chart(ctx, {
                 type: 'doughnut',
                 data: {
                     labels: yearData.map(d => d.label),
                     datasets: [{
                         data: yearData.map(d => d.total),
                         backgroundColor: yearData.map(d => d.style.color),
                         borderWidth: 0,
                         hoverOffset: 15,
                         borderRadius: 5
                     }]
                 },
                 options: {
                     cutout: '82%',
                     plugins: {
                         legend: { display: false },
                         tooltip: {
                             padding: 12,
                             backgroundColor: 'rgba(0,0,0,0.8)',
                             titleFont: { size: 14, weight: 'bold' },
                             bodyFont: { size: 13 },
                             callbacks: {
                                 label: function(context) {
                                     const value = context.raw;
                                     const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                     const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                     return ` ${context.label}: ${value} Units (${percentage}%)`;
                                 }
                             }
                         }
                     },
                     maintainAspectRatio: false,
                     animation: {
                         animateScale: true,
                         animateRotate: true
                     }
                 }
             });
        }
        
        // Render Submissions Table
        function renderSubmissions() {
            const submissionsContent = document.getElementById('submissionsContent');
            
            if (submissionData.length === 0) {
                submissionsContent.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar-x"></i>
                        </div>
                        <h4>No submissions yet</h4>
                        <p>Start by submitting your first equipment form.</p>
                        <a href="Issp_form.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="bi bi-plus-lg"></i>
                            Create First Submission
                        </a>
                    </div>
                `;
                return;
            }
            
            submissionsContent.innerHTML = `
                <div class="table-responsive" style="padding: 0 1.5rem 1.5rem;">
                    <table class="table table-hover align-middle mb-0" style="border-collapse: separate; border-spacing: 0 0.75rem;">
                        <thead class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <tr>
                                <th class="border-0 px-4">Date</th>
                                <th class="border-0">Submitted By</th>
                                <th class="border-0">Units</th>
                                <th class="border-0">Categories</th>
                                <th class="border-0">Status</th>
                                <th class="border-0 text-end px-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${submissionData.map(sub => `
                                <tr style="background: var(--bg-card); box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s ease; cursor: pointer;" onclick="window.location.href='history.php'">
                                    <td class="px-4 py-3" style="border-radius: 10px 0 0 10px; border: 1px solid var(--border-color); border-right: none;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.1); color: #6366f1; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">${formatDate(sub.date)}</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">10:24 AM</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3" style="border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                                        <div class="fw-bold text-dark"><?php echo $userName; ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Administrator</div>
                                    </td>
                                    <td class="py-3" style="border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                                        <div class="fw-bold text-dark">${sub.units}</div>
                                    </td>
                                    <td class="py-3" style="border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                                        <div class="fw-bold text-dark">${sub.entries} categories</div>
                                        <a href="history.php" class="text-primary" style="font-size: 0.75rem; text-decoration: none;">View details</a>
                                    </td>
                                    <td class="py-3" style="border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                                        <span class="badge bg-success-subtle text-success border-0 px-3 py-2" style="font-size: 0.75rem;">
                                            <i class="bi bi-check-circle-fill me-1"></i> Completed
                                        </span>
                                    </td>
                                    <td class="text-end px-4 py-3" style="border-radius: 0 10px 10px 0; border: 1px solid var(--border-color); border-left: none;">
                                        <i class="bi bi-three-dots-vertical text-muted"></i>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        // Initialize sparklines
        function initSparklines() {
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } },
                elements: { point: { radius: 0 }, line: { tension: 0.4, borderWidth: 2 } }
            };

            new Chart(document.getElementById('sparkline1').getContext('2d'), {
                type: 'line',
                data: {
                    labels: [1, 2, 3, 4, 5, 6, 7],
                    datasets: [{
                        data: [30, 45, 35, 60, 50, 70, 65],
                        borderColor: '#10b981',
                        fill: false
                    }]
                },
                options: commonOptions
            });

            new Chart(document.getElementById('sparkline2').getContext('2d'), {
                type: 'line',
                data: {
                    labels: [1, 2, 3, 4, 5, 6, 7],
                    datasets: [{
                        data: [20, 40, 30, 50, 40, 60, 55],
                        borderColor: '#6366f1',
                        fill: false
                    }]
                },
                options: commonOptions
            });
        }
        
        // Format date helper
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }
        
        // Initialize everything
        function initializeDashboard() {
            renderYearlyBreakdown();
            renderSubmissions();
            initSparklines();
        }
        
        // Start the dashboard
        document.addEventListener('DOMContentLoaded', initializeDashboard);
        
        // Listen for theme changes
        window.addEventListener('storage', (e) => {
            if (e.key === 'theme') {
                document.documentElement.setAttribute('data-theme', e.newValue);
            }
        });
    </script>
</body>
</html>