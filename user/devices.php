    <?php
    include "../config.php";

    // Ensure price column exists
    $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM user_issp_form LIKE 'price'");
    if ($checkColumn && mysqli_num_rows($checkColumn) === 0) {
        mysqli_query($conn, "ALTER TABLE user_issp_form ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }

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

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    $user_office = trim($_SESSION['user_office'] ?? $_SESSION['office'] ?? '');

    // Handle price update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
        $device_id = isset($_POST['device_id']) ? (int) $_POST['device_id'] : 0;
        $new_price = isset($_POST['price']) ? (float) $_POST['price'] : 0.00;

        $updateSql = "UPDATE user_issp_form SET price = ? WHERE id = ? AND office_name = ?";
        $stmt = $conn->prepare($updateSql);
        if ($stmt) {
            $stmt->bind_param('dis', $new_price, $device_id, $user_office);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = 'Price updated successfully.';
            } else {
                $_SESSION['error_msg'] = 'Unable to update price. Please try again.';
            }
            $stmt->close();
        } else {
            $_SESSION['error_msg'] = 'Database error while updating price.';
        }

        header('Location: devices.php');
        exit;
    }

    // Handle bulk price update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update_price'])) {
        $price_updates = $_POST['prices'] ?? [];
        $success_count = 0;
        $error_count = 0;

        foreach ($price_updates as $device_id => $new_price) {
            $device_id = (int) $device_id;
            $new_price = (float) $new_price;
            
            $updateSql = "UPDATE user_issp_form SET price = ? WHERE id = ? AND office_name = ?";
            $stmt = $conn->prepare($updateSql);
            if ($stmt) {
                $stmt->bind_param('dis', $new_price, $device_id, $user_office);
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
                $stmt->close();
            } else {
                $error_count++;
            }
        }

        if ($success_count > 0) {
            $_SESSION['success_msg'] = "$success_count device(s) updated successfully.";
        }
        if ($error_count > 0) {
            $_SESSION['error_msg'] = "$error_count device(s) failed to update.";
        }

        header('Location: devices.php');
        exit;
    }

    // Handle device deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_device'])) {
        $device_id = isset($_POST['device_id']) ? (int) $_POST['device_id'] : 0;
        $device_type = isset($_POST['device_type']) ? trim($_POST['device_type']) : 'user';

        if ($device_type === 'user') {
            $deleteSql = "DELETE FROM user_issp_form WHERE id = ? AND office_name = ?";
            $stmt = $conn->prepare($deleteSql);
            if ($stmt) {
                $stmt->bind_param('is', $device_id, $user_office);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = 'Device deleted successfully.';
                } else {
                    $_SESSION['error_msg'] = 'Unable to delete device.';
                }
                $stmt->close();
            }
        }

        header('Location: devices.php');
        exit;
    }

    // Get filter parameters
    $filter_status = strtolower(trim($_GET['status'] ?? ''));
    $filter_item = trim($_GET['item'] ?? '');
    $search_query = trim($_GET['search'] ?? '');
    $sort_by = trim($_GET['sort_by'] ?? 'date_desc');
    $view_mode = trim($_GET['view'] ?? 'grouped');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 10; // Increased to 10 forms per page

    $validStatuses = ['operational', 'non-operational'];
    if (!in_array($filter_status, $validStatuses, true)) {
        $filter_status = '';
    }

    $status_counts = ['operational' => 0, 'non-operational' => 0, 'total' => 0];
    $item_counts = [];
    $forms = [];

    if ($user_office !== '') {
        // Get status counts
        $statusSql = "SELECT LOWER(status) AS status_lower, COUNT(*) AS count FROM user_issp_form WHERE office_name = ? GROUP BY LOWER(status)";
        $statusStmt = $conn->prepare($statusSql);
        if ($statusStmt) {
            $statusStmt->bind_param('s', $user_office);
            $statusStmt->execute();
            $statusResult = $statusStmt->get_result();
            while ($row = $statusResult->fetch_assoc()) {
                $status_counts[$row['status_lower']] = (int) $row['count'];
                $status_counts['total'] += (int) $row['count'];
            }
            $statusStmt->close();
        }

        // Get admin equipment count
        $adminCountSql = "SELECT COUNT(*) AS count FROM computer_equipment WHERE office_name = ?";
        $adminCountStmt = $conn->prepare($adminCountSql);
        if ($adminCountStmt) {
            $adminCountStmt->bind_param('s', $user_office);
            $adminCountStmt->execute();
            $adminCountResult = $adminCountStmt->get_result();
            if ($adminCountRow = $adminCountResult->fetch_assoc()) {
                $status_counts['total'] += (int) $adminCountRow['count'];
            }
            $adminCountStmt->close();
        }

        // Get item counts for filter
        $itemSql = "SELECT item_name, SUM(item_count) AS count FROM (
                        SELECT COALESCE(NULLIF(TRIM(item), ''), 'Unknown') AS item_name, COUNT(*) AS item_count
                        FROM user_issp_form WHERE office_name = ? GROUP BY item_name
                        UNION ALL
                        SELECT COALESCE(NULLIF(TRIM(item), ''), 'Unknown') AS item_name, COUNT(*) AS item_count
                        FROM computer_equipment WHERE office_name = ? GROUP BY item_name
                    ) AS combined
                    GROUP BY item_name
                    ORDER BY count DESC";
        $itemStmt = $conn->prepare($itemSql);
        if ($itemStmt) {
            $itemStmt->bind_param('ss', $user_office, $user_office);
            $itemStmt->execute();
            $itemResult = $itemStmt->get_result();
            while ($row = $itemResult->fetch_assoc()) {
                $item_counts[$row['item_name']] = (int) $row['count'];
            }
            $itemStmt->close();
        }

        // Build the main query with sorting
        $whereClauses = ["u.office_name = ?"];
        $params = [$user_office];
        $types = 's';

        if ($filter_status !== '') {
            $whereClauses[] = "LOWER(u.status) = ?";
            $params[] = $filter_status;
            $types .= 's';
        }

        if ($filter_item !== '') {
            $whereClauses[] = "u.item = ?";
            $params[] = $filter_item;
            $types .= 's';
        }

        $whereSql = implode(' AND ', $whereClauses);
        
        // Get distinct submission dates
        $dateSql = "SELECT DISTINCT date_submitted FROM (
                        SELECT date_submitted, id FROM user_issp_form WHERE office_name = ?
                        UNION
                        SELECT date_submitted, id FROM form WHERE office_name = ?
                    ) AS union_dates
                    ORDER BY date_submitted DESC";
        $dateStmt = $conn->prepare($dateSql);
        if ($dateStmt) {
            $dateStmt->bind_param('ss', $user_office, $user_office);
            $dateStmt->execute();
            $dateResult = $dateStmt->get_result();
            
            $all_devices = [];
            
            while ($row = $dateResult->fetch_assoc()) {
                $date = $row['date_submitted'];

                // Get user devices
                $userSql = "SELECT *, 'user' as source, id as device_id FROM user_issp_form WHERE office_name = ? AND date_submitted = ?";
                $userParams = [$user_office, $date];
                $userTypes = 'ss';
                if ($filter_status !== '') {
                    $userSql .= " AND LOWER(status) = ?";
                    $userParams[] = $filter_status;
                    $userTypes .= 's';
                }
                if ($filter_item !== '') {
                    $userSql .= " AND item = ?";
                    $userParams[] = $filter_item;
                    $userTypes .= 's';
                }
                if ($search_query !== '') {
                    $userSql .= " AND (item LIKE ? OR brand LIKE ? OR processor LIKE ? OR ram LIKE ? OR hdd LIKE ? OR ssd LIKE ? OR serial_number LIKE ?)";
                    $search_param = '%' . $search_query . '%';
                    $userParams = array_merge($userParams, array_fill(0, 7, $search_param));
                    $userTypes .= str_repeat('s', 7);
                }
                $userSql .= " ORDER BY id DESC";
                $userStmt = $conn->prepare($userSql);
                if ($userStmt) {
                    $userStmt->bind_param($userTypes, ...$userParams);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result();
                    while ($device = $userResult->fetch_assoc()) {
                        $device['source'] = 'user';
                        $device['display_status'] = $device['status'];
                        $device['display_units'] = $device['units'];
                        $all_devices[] = $device;
                    }
                    $userStmt->close();
                }

                // Get admin devices
                $adminSql = "SELECT ce.*, f.date_submitted, 'admin' as source, ce.id as device_id FROM computer_equipment ce
                            JOIN form f ON ce.form_id = f.id
                            WHERE ce.office_name = ? AND f.date_submitted = ?";
                $adminParams = [$user_office, $date];
                $adminTypes = 'ss';
                if ($filter_item !== '') {
                    $adminSql .= " AND ce.item = ?";
                    $adminParams[] = $filter_item;
                    $adminTypes .= 's';
                }
                if ($search_query !== '') {
                    $adminSql .= " AND (ce.item LIKE ? OR ce.brand LIKE ? OR ce.processor LIKE ? OR ce.ram LIKE ? OR ce.hdd LIKE ? OR ce.ssd LIKE ? OR ce.serial_number LIKE ?)";
                    $search_param = '%' . $search_query . '%';
                    $adminParams = array_merge($adminParams, array_fill(0, 7, $search_param));
                    $adminTypes .= str_repeat('s', 7);
                }
                $adminSql .= " ORDER BY ce.id DESC";
                $adminStmt = $conn->prepare($adminSql);
                if ($adminStmt) {
                    $adminStmt->bind_param($adminTypes, ...$adminParams);
                    $adminStmt->execute();
                    $adminResult = $adminStmt->get_result();
                    while ($device = $adminResult->fetch_assoc()) {
                        $device['source'] = 'admin';
                        $device['display_status'] = 'Submitted';
                        $device['price'] = 0;
                        $device['display_units'] = $device['number_of_units'] ?? 0;
                        $device['status'] = 'submitted';
                        $all_devices[] = $device;
                    }
                    $adminStmt->close();
                }
            }
            $dateStmt->close();

            // Apply sorting
            switch ($sort_by) {
                case 'item_asc':
                    usort($all_devices, function($a, $b) {
                        return strcmp($a['item'] ?? '', $b['item'] ?? '');
                    });
                    break;
                case 'item_desc':
                    usort($all_devices, function($a, $b) {
                        return strcmp($b['item'] ?? '', $a['item'] ?? '');
                    });
                    break;
                case 'status_asc':
                    usort($all_devices, function($a, $b) {
                        return strcmp($a['display_status'] ?? '', $b['display_status'] ?? '');
                    });
                    break;
                case 'status_desc':
                    usort($all_devices, function($a, $b) {
                        return strcmp($b['display_status'] ?? '', $a['display_status'] ?? '');
                    });
                    break;
                case 'price_asc':
                    usort($all_devices, function($a, $b) {
                        return ($a['price'] ?? 0) - ($b['price'] ?? 0);
                    });
                    break;
                case 'price_desc':
                    usort($all_devices, function($a, $b) {
                        return ($b['price'] ?? 0) - ($a['price'] ?? 0);
                    });
                    break;
                case 'date_asc':
                    usort($all_devices, function($a, $b) {
                        return strtotime($a['date_submitted'] ?? '') - strtotime($b['date_submitted'] ?? '');
                    });
                    break;
                case 'date_desc':
                default:
                    usort($all_devices, function($a, $b) {
                        return strtotime($b['date_submitted'] ?? '') - strtotime($a['date_submitted'] ?? '');
                    });
                    break;
            }

            // Group devices by date
            $grouped_devices = [];
            foreach ($all_devices as $device) {
                $date = $device['date_submitted'];
                if (!isset($grouped_devices[$date])) {
                    $grouped_devices[$date] = [];
                }
                
                if ($view_mode === 'list') {
                    $num_units = max(1, (int)($device['units'] ?? $device['number_of_units'] ?? 1));
                    for ($i = 1; $i <= $num_units; $i++) {
                        $unit_device = $device;
                        $unit_device['unit_no'] = $i;
                        $unit_device['total_units'] = $num_units;
                        $grouped_devices[$date][] = $unit_device;
                    }
                } else {
                    $grouped_devices[$date][] = $device;
                }
            }

            // Convert to forms array
            foreach ($grouped_devices as $date => $devices) {
                $forms[] = [
                    'date' => $date,
                    'devices' => $devices,
                    'count' => count($devices),
                ];
            }
            $total_items = count($forms);
            $offset = ($page - 1) * $per_page;
            $paginated_items = array_slice($forms, $offset, $per_page);
        }
    }

    // Calculate operational percentage
    $operational_percentage = $status_counts['total'] > 0 
        ? round(($status_counts['operational'] / $status_counts['total']) * 100) 
        : 0;

    function generatePagination($total, $per_page, $current_page, $base_url) {
        $total_pages = ceil($total / $per_page);
        if ($total_pages <= 1) return '';

        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        // Previous
        if ($current_page > 1) {
            $prev_url = $base_url . '&page=' . ($current_page - 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . esc($prev_url) . '"><i class="bi bi-chevron-left"></i> Previous</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i> Previous</span></li>';
        }

        // First page
        if ($current_page > 3) {
            $url = $base_url . '&page=1';
            $html .= '<li class="page-item"><a class="page-link" href="' . esc($url) . '">1</a></li>';
            if ($current_page > 4) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Pages around current
        for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
            $url = $base_url . '&page=' . $i;
            $active = $i == $current_page ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . esc($url) . '">' . $i . '</a></li>';
        }

        // Last page
        if ($current_page < $total_pages - 2) {
            if ($current_page < $total_pages - 3) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $url = $base_url . '&page=' . $total_pages;
            $html .= '<li class="page-item"><a class="page-link" href="' . esc($url) . '">' . $total_pages . '</a></li>';
        }

        // Next
        if ($current_page < $total_pages) {
            $next_url = $base_url . '&page=' . ($current_page + 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . esc($next_url) . '">Next <i class="bi bi-chevron-right"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next <i class="bi bi-chevron-right"></i></span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }

    function esc($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    function formatDate($date) {
        if (empty($date)) return 'N/A';
        return date('F j, Y', strtotime($date));
    }

    function formatPrice($value) {
        return '₱' . number_format((float) $value, 2);
    }

    function statusBadgeClass($status) {
        $status = strtolower(trim($status));
        if ($status === 'operational') {
            return 'badge-operational';
        } elseif ($status === 'non-operational') {
            return 'badge-non-operational';
        }
        return 'badge-submitted';
    }

    function getStatusIcon($status) {
        $status = strtolower(trim($status));
        if ($status === 'operational') {
            return '<i class="bi bi-check-circle-fill"></i>';
        } elseif ($status === 'non-operational') {
            return '<i class="bi bi-exclamation-circle-fill"></i>';
        }
        return '<i class="bi bi-clock-fill"></i>';
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <?php $pageTitle = 'Devices - ICTMIS'; ?>
        <?php include 'components/head.php'; ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            :root {
                --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                --success-gradient: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
                --danger-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            }

            body {
                background: #f5f7fb;
            }

            .summary-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.65rem 1rem;
                border-radius: 999px;
                background: linear-gradient(135deg, #f5f7fa 0%, #eef2ff 100%);
                border: 1px solid rgba(79, 70, 229, 0.15);
                color: #312e81;
                font-size: 0.85rem;
                font-weight: 600;
                transition: all 0.2s ease;
            }
            
            .summary-pill:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
            }

            .device-card {
                border-radius: 1rem;
                border: none;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
                overflow: hidden;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .device-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
            }
            
            .device-card .card-header {
                background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
                border-bottom: 2px solid #e2e8f0;
            }
            
            .badge-status {
                padding: 0.4rem 0.85rem;
                border-radius: 999px;
                font-size: 0.78rem;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
            }
            
            .badge-operational { 
                background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
                color: #166534; 
                border: 1px solid #86efac;
            }
            
            .badge-non-operational { 
                background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                color: #991b1b;
                border: 1px solid #fca5a5;
            }
            
            .badge-submitted {
                background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
                color: #1e40af;
                border: 1px solid #93c5fd;
            }
            
            .device-img { 
                width: 64px; 
                height: 64px; 
                object-fit: cover; 
                border-radius: 0.75rem;
                border: 2px solid #e2e8f0;
                transition: transform 0.2s ease;
            }
            
            .device-img:hover {
                transform: scale(1.05);
            }
            
            .device-placeholder {
                width: 64px;
                height: 64px;
                border-radius: 0.75rem;
                background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
                display: grid;
                place-items: center;
                color: #4f46e5;
                font-size: 1.5rem;
            }
            
            .filter-card { 
                border-radius: 1rem; 
                border: none;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
                background: #ffffff;
                transition: box-shadow 0.2s ease;
            }
            
            .filter-card:hover {
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            }
            
            .filter-card label { 
                font-size: 0.8rem; 
                font-weight: 700; 
                color: #475569;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                margin-bottom: 0.5rem;
            }
            
            .filter-card .form-select, 
            .filter-card .form-control { 
                min-height: 44px;
                border-radius: 0.75rem;
                border: 1.5px solid #e2e8f0;
                transition: all 0.2s ease;
            }
            
            .filter-card .form-select:focus,
            .filter-card .form-control:focus {
                border-color: #4f46e5;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }
            
            .stats-card {
                border-radius: 1rem;
                border: none;
                padding: 1.25rem;
                transition: transform 0.2s ease;
                cursor: pointer;
            }
            
            .stats-card:hover {
                transform: translateY(-3px);
            }
            
            .stats-card .stats-number {
                font-size: 2rem;
                font-weight: 800;
                line-height: 1;
            }
            
            .stats-card .stats-label {
                font-size: 0.7rem;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                font-weight: 600;
            }
            
            .spec-box {
                display: grid;
                gap: 0.5rem;
                padding: 0.75rem;
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border: 1px solid #e2e8f0;
                border-radius: 0.85rem;
                min-width: 260px;
            }
            
            .spec-columns {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.5rem;
            }
            
            .spec-column {
                padding: 0.5rem;
                background: white;
                border-radius: 0.6rem;
                text-align: center;
                transition: all 0.2s ease;
            }
            
            .spec-column:hover {
                transform: translateY(-2px);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }
            
            .spec-label {
                font-size: 0.65rem;
                font-weight: 800;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                display: block;
                margin-bottom: 0.25rem;
            }
            
            .spec-value {
                font-size: 0.85rem;
                font-weight: 600;
                color: #0f172a;
                word-break: break-word;
            }
            
            .empty-state {
                padding: 4rem 2rem;
                text-align: center;
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border-radius: 1rem;
            }
            
            .btn-custom-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                color: white;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .btn-custom-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                color: white;
            }
            
            .btn-custom-outline {
                border: 2px solid #e2e8f0;
                background: white;
                transition: all 0.2s ease;
            }
            
            .btn-custom-outline:hover {
                border-color: #4f46e5;
                background: #f8fafc;
                color: #4f46e5;
            }
            
            .table-hover tbody tr:hover {
                background-color: #f8fafc;
            }
            
            .price-input {
                width: 110px;
                padding: 0.35rem 0.5rem;
                border-radius: 0.5rem;
                border: 1.5px solid #e2e8f0;
                text-align: right;
                transition: all 0.2s ease;
            }
            
            .price-input:focus {
                border-color: #4f46e5;
                outline: none;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }
            
            .serial-number {
                font-family: 'Courier New', monospace;
                font-size: 0.75rem;
                background: #f1f5f9;
                padding: 0.2rem 0.4rem;
                border-radius: 0.4rem;
                display: inline-block;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .device-card {
                animation: fadeIn 0.3s ease-out;
            }
            
            @media (max-width: 768px) {
                .spec-columns {
                    grid-template-columns: 1fr;
                    gap: 0.3rem;
                }
                
                .spec-column {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    text-align: left;
                    padding: 0.4rem 0.6rem;
                }
                
                .spec-label {
                    margin-bottom: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="dashboard-container">
            <?php include __DIR__ . '/components/sidebar.php'; ?>
            <main class="main-content">
                <?php include __DIR__ . '/components/header.php'; ?>

                <div class="container-fluid px-4 py-4">
                    <?php if (!empty($_SESSION['success_msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius: 0.75rem;">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo esc($_SESSION['success_msg']); unset($_SESSION['success_msg']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['error_msg'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius: 0.75rem;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo esc($_SESSION['error_msg']); unset($_SESSION['error_msg']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Page Header -->
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-laptop fs-4 text-primary"></i>
                                <h1 class="h3 mb-0 fw-bold">My Devices</h1>
                            </div>
                            <p class="text-muted mb-0">
                                <i class="bi bi-building me-1"></i> <?php echo esc($user_office); ?> 
                                <span class="mx-2">•</span>
                                <i class="bi bi-calendar3 me-1"></i> Last updated: <?php echo date('F j, Y'); ?>
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                             
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="stats-card shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stats-label">Total Devices</div>
                                        <div class="stats-number"><?php echo $status_counts['total']; ?></div>
                                    </div>
                                    <i class="bi bi-database fs-1 opacity-50"></i>
                                </div>
                                <div class="mt-2">
                                    <small>All registered equipment</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card shadow-sm" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); color: #166534;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stats-label">Operational</div>
                                        <div class="stats-number"><?php echo $status_counts['operational']; ?></div>
                                    </div>
                                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                                </div>
                                <div class="mt-2">
                                    <small><?php echo $operational_percentage; ?>% of total devices</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card shadow-sm" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #991b1b;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stats-label">Non-operational</div>
                                        <div class="stats-number"><?php echo $status_counts['non-operational']; ?></div>
                                    </div>
                                    <i class="bi bi-exclamation-circle fs-1 opacity-50"></i>
                                </div>
                                <div class="mt-2">
                                    <small>Requires attention</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search & View Toggle -->
                    <div class="row g-3 mb-4">
                        <div class="col-lg-6">
                            <div class="filter-card p-3">
                                <label class="mb-2"><i class="bi bi-search me-1"></i> Search devices</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" id="search_input" class="form-control border-start-0" placeholder="Search by item, brand, serial #, specs..." value="<?php echo esc($search_query); ?>" onkeyup="debouncedApplyFilters()">
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="filter-card p-3">
                                <label class="mb-2"><i class="bi bi-grid me-1"></i> Display View</label>
                                <div>
                                    <?php if ($view_mode !== 'list'): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'list', 'page' => 1])); ?>" 
                                           class="btn btn-custom-primary w-100 d-flex justify-content-between align-items-center p-3" style="border-radius: 0.85rem;">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-white bg-opacity-20 rounded-circle p-2 d-flex">
                                                    <i class="bi bi-list-task fs-5"></i>
                                                </div>
                                                <div class="text-start">
                                                    <div class="fw-bold">Individual List View</div>
                                                    <div class="small opacity-75">View all devices in a single list</div>
                                                </div>
                                            </div>
                                            <i class="bi bi-chevron-right fs-4"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'grouped', 'page' => 1])); ?>" 
                                           class="btn btn-custom-outline w-100 d-flex align-items-center gap-3 p-3" style="border-radius: 0.85rem;">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex">
                                                <i class="bi bi-arrow-left fs-5"></i>
                                            </div>
                                            <div class="text-start">
                                                <div class="fw-bold">Back to Grouped View</div>
                                                <div class="small text-muted">View devices grouped by date</div>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Device List -->
                    <?php if (empty($all_devices)): ?>
                        <div class="empty-state">
                            <i class="bi bi-laptop fs-1 mb-3 d-block text-muted"></i>
                            <i class="bi bi-emoji-frown fs-4 mb-3 d-block text-muted"></i>
                            <h4 class="mb-2">No devices found</h4>
                            <p class="text-muted mb-3">We couldn't find any devices matching your criteria or your office hasn't submitted any devices yet.</p>
                            <a href="Issp_form.php" class="btn btn-custom-primary">
                                <i class="bi bi-plus-lg me-2"></i> Add Your First Device
                            </a>
                        </div>
                    <?php else: ?>
                        <form id="bulkEditForm" method="POST" action="" style="display: none;">
                            <input type="hidden" name="bulk_update_price" value="1">
                            <div id="bulkEditControls" class="mb-3" style="display: none;">
                                <div class="alert alert-info d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        <strong>Bulk Edit Mode:</strong> Edit prices below and click "Save All Changes"
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="submitBulkEdit()">
                                        <i class="bi bi-save me-1"></i> Save All Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <?php foreach ($paginated_items as $form): ?>
                            <div class="device-card mb-4">
                                <div class="card-header p-3">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-calendar-week text-primary"></i>
                                                <h5 class="mb-0 fw-bold"><?php echo formatDate($form['date']); ?></h5>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                    <i class="bi bi-device-ssd me-1"></i><?php echo $form['count']; ?> row<?php echo $form['count'] === 1 ? '' : 's'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-building me-1"></i> <?php echo esc($user_office); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" style="width: 80px">Image</th>
                                                <th scope="col">Item / Serial</th>
                                                <th scope="col">Brand / Model</th>
                                                <th scope="col" class="text-center">Specifications</th>
                                                <th scope="col" class="text-center">Units</th>
                                                <th scope="col" class="text-center">Status</th>
                                                <th scope="col" class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($form['devices'] as $device): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($device['equipment_image'])): ?>
                                                            <img src="../<?php echo esc($device['equipment_image']); ?>" alt="Device" class="device-img" loading="lazy">
                                                        <?php else: ?>
                                                            <div class="device-placeholder">
                                                                <i class="bi bi-pc-display"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold"><?php echo esc($device['item']); ?></div>
                                                        <?php if (!empty($device['serial_number'])): ?>
                                                            <div class="serial-number mt-1">
                                                                <i class="bi bi-upc-scan"></i> SN: <?php echo esc($device['serial_number']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (isset($device['unit_no'])): ?>
                                                            <div class="mt-1">
                                                                <span class="badge bg-info bg-opacity-10 text-info small">
                                                                    Unit <?php echo $device['unit_no']; ?> of <?php echo $device['total_units']; ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div><?php echo esc($device['brand']); ?></div>
                                                        <?php if (!empty($device['model'])): ?>
                                                            <small class="text-muted"><?php echo esc($device['model']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="spec-box">
                                                            <div class="spec-columns">
                                                                <div class="spec-column">
                                                                    <span class="spec-label"><i class="bi bi-cpu"></i> Processor</span>
                                                                    <span class="spec-value"><?php echo esc($device['processor'] ?? 'N/A'); ?></span>
                                                                </div>
                                                                <div class="spec-column">
                                                                    <span class="spec-label"><i class="bi bi-memory"></i> RAM</span>
                                                                    <span class="spec-value"><?php echo esc($device['ram'] ?? 'N/A'); ?></span>
                                                                </div>
                                                                <div class="spec-column">
                                                                    <span class="spec-label"><i class="bi bi-hdd-stack"></i> Storage</span>
                                                                    <span class="spec-value"><?php
                                                                        $hddSsd = trim(($device['hdd'] ?? '') . (($device['hdd'] && $device['ssd']) ? ' / ' : '') . ($device['ssd'] ?? ''));
                                                                        echo esc($hddSsd ?: 'N/A');
                                                                    ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?php echo isset($device['unit_no']) ? 'bg-secondary' : 'bg-primary'; ?> bg-opacity-10 <?php echo isset($device['unit_no']) ? 'text-secondary' : 'text-primary'; ?> px-3 py-2">
                                                            <?php echo isset($device['unit_no']) ? '1' : (int) ($device['units'] ?? $device['number_of_units'] ?? 1); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge-status <?php echo statusBadgeClass($device['display_status'] ?? $device['status']); ?>">
                                                            <?php echo getStatusIcon($device['display_status'] ?? $device['status']); ?>
                                                            <?php echo ucfirst($device['display_status'] ?? $device['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger report-problem-btn" 
                                                                data-device-id="<?php echo $device['device_id']; ?>" 
                                                                data-source="<?php echo $device['source']; ?>"
                                                                data-item-name="<?php echo esc($device['item']); ?>">
                                                            <i class="bi bi-exclamation-triangle me-1"></i> Report
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php
                        $base_url = 'devices.php?' . http_build_query(array_filter([
                            'search' => $search_query,
                            'view' => $view_mode,
                            'status' => $filter_status,
                            'item' => $filter_item,
                            'sort_by' => $sort_by
                        ]));
                        echo generatePagination($total_items, $per_page, $page, $base_url);
                        ?>
                        
                        <div class="text-center text-muted mt-3">
                            <small>Showing <?php echo count($paginated_items); ?> of <?php echo $total_items; ?> submission groups</small>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>

        <!-- Maintenance Report Modal -->
        <div class="modal fade" id="reportProblemModal" tabindex="-1" aria-labelledby="reportProblemModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title fw-bold" id="reportProblemModalLabel">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Report Device Problem
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="maintenanceReportForm">
                        <div class="modal-body p-4">
                            <input type="hidden" id="report_device_id" name="device_id">
                            <input type="hidden" id="report_source" name="source">
                            <input type="hidden" id="report_item_name" name="item_name">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small text-uppercase">Affected Device</label>
                                <div id="display_item_name" class="p-3 bg-light rounded border fw-bold text-dark"></div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="urgency" class="form-label fw-bold text-muted small text-uppercase">Urgency Level</label>
                                <select class="form-select border-2" id="urgency" name="urgency" required>
                                    <option value="low">Low - Minor issue, still usable</option>
                                    <option value="medium" selected>Medium - Needs attention soon</option>
                                    <option value="high">High - Affecting productivity</option>
                                    <option value="critical">Critical - Device unusable / Work stopped</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="problem_description" class="form-label fw-bold text-muted small text-uppercase">Problem Description</label>
                                <textarea class="form-control border-2" id="problem_description" name="problem_description" rows="4" placeholder="Describe what is wrong with the device..." required></textarea>
                                <div class="form-text mt-2">
                                    <i class="bi bi-info-circle me-1"></i> Be specific about the issue (e.g., "Monitor won't turn on", "Running very slow").
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-top-0 p-3">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">
                                <i class="bi bi-send me-2"></i>Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // Report Problem Logic
            const reportModalEl = document.getElementById('reportProblemModal');
            const reportModal = reportModalEl ? new bootstrap.Modal(reportModalEl) : null;
            const reportForm = document.getElementById('maintenanceReportForm');

            document.querySelectorAll('.report-problem-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('report_device_id').value = this.dataset.deviceId;
                    document.getElementById('report_source').value = this.dataset.source;
                    document.getElementById('report_item_name').value = this.dataset.itemName;
                    document.getElementById('display_item_name').innerText = this.dataset.itemName;
                    document.getElementById('problem_description').value = '';
                    if (reportModal) reportModal.show();
                });
            });

            if (reportForm) {
                reportForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    const originalHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

                    fetch('action/submit_maintenance_report.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Problem reported successfully. The ICT office has been notified.');
                            if (reportModal) reportModal.hide();
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('An error occurred. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    });
                });
            }

            let debounceTimer;
            
            function debouncedApplyFilters() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => applyFilters(), 500);
            }
            
            function getQueryParams() {
                const params = new URLSearchParams(window.location.search);
                return params;
            }
            
            function applyFilters() {
                const params = getQueryParams();
                const search = document.getElementById('search_input').value.trim();
                
                if (search) {
                    params.set('search', search);
                } else {
                    params.delete('search');
                }
                params.delete('page');
                window.location.search = params.toString();
            }
            
            function resetFilters() {
                window.location.href = 'devices.php';
            }
            
            function toggleBulkEdit() {
                const priceInputs = document.querySelectorAll('.price-input');
                const priceDisplays = document.querySelectorAll('.price-display');
                const editBtns = document.querySelectorAll('.edit-price-btn');
                const saveBtns = document.querySelectorAll('.save-price-btn');
                const bulkControls = document.getElementById('bulkEditControls');
                const bulkForm = document.getElementById('bulkEditForm');
                
                priceInputs.forEach(input => input.classList.toggle('d-none'));
                priceDisplays.forEach(display => display.classList.toggle('d-none'));
                editBtns.forEach(btn => btn.classList.toggle('d-none'));
                saveBtns.forEach(btn => btn.classList.toggle('d-none'));
                
                if (bulkControls) {
                    bulkControls.style.display = bulkControls.style.display === 'none' ? 'block' : 'none';
                }
                if (bulkForm) {
                    bulkForm.style.display = bulkForm.style.display === 'none' ? 'block' : 'none';
                }
            }
            
            function submitBulkEdit() {
                const form = document.getElementById('bulkEditForm');
                if (form) {
                    form.submit();
                }
            }
            
            // Individual price edit
            document.querySelectorAll('.edit-price-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const deviceId = this.dataset.deviceId;
                    const container = this.closest('tr').querySelector('.price-container');
                    const display = container.querySelector('.price-display');
                    const input = container.querySelector('.price-input');
                    const editBtn = this;
                    const saveBtn = container.closest('tr').querySelector('.save-price-btn');
                    
                    display.classList.add('d-none');
                    input.classList.remove('d-none');
                    editBtn.classList.add('d-none');
                    if (saveBtn) saveBtn.classList.remove('d-none');
                });
            });
            
            document.querySelectorAll('.save-price-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const deviceId = this.dataset.deviceId;
                    const container = this.closest('tr').querySelector('.price-container');
                    const display = container.querySelector('.price-display');
                    const input = container.querySelector('.price-input');
                    const editBtn = container.closest('tr').querySelector('.edit-price-btn');
                    const saveBtn = this;
                    const newPrice = parseFloat(input.value);
                    const originalPrice = parseFloat(input.dataset.originalPrice);
                    
                    if (newPrice !== originalPrice) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '';
                        
                        const priceInput = document.createElement('input');
                        priceInput.type = 'hidden';
                        priceInput.name = 'price';
                        priceInput.value = newPrice;
                        
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'device_id';
                        idInput.value = deviceId;
                        
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'update_price';
                        actionInput.value = '1';
                        
                        form.appendChild(priceInput);
                        form.appendChild(idInput);
                        form.appendChild(actionInput);
                        document.body.appendChild(form);
                        form.submit();
                    } else {
                        display.classList.remove('d-none');
                        input.classList.add('d-none');
                        editBtn.classList.remove('d-none');
                        saveBtn.classList.add('d-none');
                    }
                });
            });
            
            // Delete confirmation
            document.querySelectorAll('.delete-device-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to delete this device? This action cannot be undone.')) {
                        this.closest('form').submit();
                    }
                });
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    setTimeout(() => bsAlert.close(), 5000);
                });
            }, 1000);
            
            // Tooltip initialization
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        </script>
    </body>
    </html>