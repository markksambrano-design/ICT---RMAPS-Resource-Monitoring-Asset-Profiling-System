<?php
include "../config.php";
include "../config/auth_check.php";

$errors = isset($_SESSION['register_errors']) ? $_SESSION['register_errors'] : [];
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
$success = isset($_SESSION['register_success']) ? $_SESSION['register_success'] : '';

// Clear messages after displaying
unset($_SESSION['register_errors']);
unset($_SESSION['form_data']);
unset($_SESSION['register_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php $pageTitle = 'Add New User - ICTMIS'; ?>
    <?php include 'components/head.php'; ?>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(145deg, #f5f7fb 0%, #e2e8f0 100%);
        }
        .main-content {
            background-color: #f8fafc;
            min-height: 100vh;
            flex-grow: 1;
        }
        .page-title-box {
            padding: 1.5rem 1.5rem 0 1.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .page-title-box h4 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
        }
        .page-title-box .page-subtitle {
            color: #64748b;
            margin-top: 0.35rem;
            font-size: 0.9rem;
        }
        .register-card {
            max-width: 850px;
            margin: 1rem auto 2rem;
            border: 1px solid rgba(30, 41, 59, .15);
            border-radius: 18px;
            box-shadow: 0 20px 35px rgba(41, 53, 74, 0.15);
            overflow: hidden;
        }
        .register-card .card-header {
            background-image: linear-gradient(150deg, #0f172a, #1e293b);
            color: #eef2ff;
            border: none;
            min-height: 84px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            box-shadow: inset 0 -1px 0 rgba(255,255,255,0.1);
        }
        .register-card h3 {
            font-size: 1.35rem;
            margin: 0;
            font-weight: 700;
        }
        .register-card .card-body {
            padding: 2rem;
            background: #ffffff;
            transition: background 0.3s ease;
        }
        .form-label {
            font-weight: 600;
            color: #1e293b;
            transition: color 0.3s ease;
        }

        /* Dark Mode Compatibility */
        html.dark-mode .register-card {
            background: var(--card-bg);
            border: 1px solid rgba(0, 242, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        html.dark-mode .register-card .card-body {
            background: var(--card-bg);
            color: #ffffff;
        }
        html.dark-mode .form-label {
            color: var(--neon-cyan);
        }
        html.dark-mode .form-control, 
        html.dark-mode .form-select {
            background-color: rgba(0, 0, 0, 0.3) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
        html.dark-mode .form-control:focus, 
        html.dark-mode .form-select:focus {
            border-color: var(--neon-cyan) !important;
            box-shadow: 0 0 15px rgba(0, 242, 255, 0.2) !important;
        }
        html.dark-mode .form-text {
            color: #94a3b8;
        }
        html.dark-mode .btn-outline-secondary {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }
        html.dark-mode .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        html.dark-mode .breadcrumb-item.active {
            color: #cbd5e1;
        }
        html.dark-mode .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.3);
        }
        html.dark-mode .btn-register {
            background: transparent !important;
            border: 1px solid var(--neon-cyan) !important;
            color: var(--neon-cyan) !important;
            box-shadow: 0 0 10px rgba(0, 242, 255, 0.1);
        }
        html.dark-mode .btn-register:hover {
            background: var(--neon-cyan) !important;
            color: #000 !important;
            box-shadow: 0 0 20px var(--neon-cyan);
        }
        .form-control, .form-select {
            border: 1px solid #cbd5e1;
            border-radius: 0.65rem;
            box-shadow: none;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.2);
        }
        .alert {
            border-radius: 0.75rem;
            font-size: 0.95rem;
        }
        .btn-register {
            background: linear-gradient(120deg, #2563eb, #1d4ed8);
            color: #fff;
            padding: 12px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .btn-register:hover {
            background: linear-gradient(120deg, #1d4ed8, #2563eb);
            box-shadow: 0 12px 20px rgba(37, 99, 235, 0.3);
            transform: translateY(-2px);
        }
        .btn-outline-secondary {
            border-radius: 10px;
        }
        .form-text {
            color: #64748b;
        }

        /* Select2 Custom Styling */
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 0.65rem;
            border: 1px solid #cbd5e1;
            min-height: 60px; /* Increased height */
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            display: flex;
            align-items: center;
            gap: 15px; /* Increased gap */
            color: #1e293b;
            font-size: 1.1rem; /* Increased font size */
            font-weight: 500;
        }
        .office-icon {
            width: 38px;
            height: 38px;
            object-fit: contain;
            display: inline-block;
        }
        .office-logo-wrapper {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f1f5f9;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }
        .office-logo-wrapper i {
            font-size: 1.5rem; /* Increased size */
            display: inline-block;
            line-height: 1;
            vertical-align: middle;
        }
        .select2-results__option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px !important;
            font-size: 1.05rem;
        }
        
        /* Increase the height of the dropdown list to show more items */
        .select2-results__options {
            max-height: 450px !important; /* Increased from default ~200px */
        }
        
        /* Dark mode for Select2 */
        html.dark-mode .select2-container--bootstrap-5 .select2-selection {
            background-color: rgba(0, 0, 0, 0.3) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        html.dark-mode .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            color: #ffffff !important;
        }
        html.dark-mode .select2-dropdown {
            background-color: #1e293b;
            border-color: var(--neon-cyan);
            color: #ffffff;
        }
        html.dark-mode .select2-results__option--highlighted[aria-selected] {
            background-color: var(--neon-cyan) !important;
            color: #000 !important;
        }
        html.dark-mode .select2-search__field {
            background-color: rgba(0, 0, 0, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }

        @media (max-width: 768px) {
            .register-card .card-body {
                padding: 1.25rem;
            }
            .register-card .card-header {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- SIDEBAR -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <?php include 'components/header.php'; ?>

        <div class="container-fluid px-0">
            <!-- Breadcrumb & Header -->
            <div class="page-title-box">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="users.php" class="text-decoration-none text-muted">Users</a></li>
                            <li class="breadcrumb-item active fw-medium" aria-current="page">Add New User</li>
                        </ol>
                    </nav>
                    <h4 class="mb-0 text-dark fw-bold">Register New System User</h4>
                    <p class="page-subtitle mb-0">Add a new user account to the ICTMIS administration portal.</p>
                </div>
            </div>

            <div class="px-4 pb-4">
                <div class="card register-card animate__animated animate__fadeInUp">
                <div class="card-header">
                    <h3 class="mb-0"><i class="bi bi-person-plus me-2"></i> Register New System User</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form action="action/register_user_process.php" method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="office" class="form-label">Office/Department</label>
                                <select class="form-select" id="office" name="office" required>
                                    <option value="">Select Office/Department</option>
                                    <?php
                                    // Check if offices exist, if not, insert defaults
                                    $check_offices = mysqli_query($conn, "SELECT COUNT(*) as count FROM offices");
                                    $count = 0;
                                    if ($check_offices) {
                                        $count_row = mysqli_fetch_assoc($check_offices);
                                        $count = $count_row['count'];
                                    }

                                    if ($count == 0) {
                                        $default_offices = [
                                            "Mayor's Office", "OMPDC", "Office of the SB Secretariat", 
                                            "Municipal Budget Office", "Municipal Agriculture Office", 
                                            "Municipal Health Office", "Municipal Assessor's Office", 
                                            "Municipal Engineering Office", "Human Resource Mgmt Office", 
                                            "Municipal Accounting Office", "MENRO", "Municipal Registration Office", 
                                            "Municipal Cooperative Office", "MSWD", "Public Market Office", 
                                            "General Services Office", "Municipal Treasury Office", 
                                            "MDRRMO", "Slaughterhouse Section", "Real Property Tax Section", 
                                            "Bus. Tax Section", "Municipal Library", 
                                            "Municipal Tourism Office", "POSO", "Community Affairs Office", 
                                            "Public Information Office"
                                        ];
                                        foreach ($default_offices as $office) {
                                            $escaped = mysqli_real_escape_string($conn, $office);
                                            mysqli_query($conn, "INSERT INTO offices (office_name) VALUES ('$escaped') ON DUPLICATE KEY UPDATE office_name=VALUES(office_name)");
                                        }
                                    }

                                    $offices_query = mysqli_query($conn, "SELECT office_name FROM offices WHERE office_name NOT IN ('ICTMIS', 'ICT Office') ORDER BY office_name ASC");
                                    while ($off = mysqli_fetch_assoc($offices_query)) {
                                        $selected = (isset($form_data['office']) && $form_data['office'] == $off['office_name']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($off['office_name']) . '" ' . $selected . '>' . htmlspecialchars($off['office_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-register">
                                <i class="bi bi-person-check me-2"></i> Register User
                            </button>
                            <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    const officeIcons = {
        "Mayor's Office": { type: 'img', src: "../assest/images/offices/mayos office.png" },
        "MDRRMO": { type: 'img', src: "../assest/images/offices/MDRRMO.png" },
        "Bus. Tax Section": { type: 'icon', class: 'bi-bus-front', color: '#0284c7', bg: '#e0f2fe' },
        "Community Affairs Office": { type: 'icon', class: 'bi-people', color: '#4f46e5', bg: '#eef2ff' },
        "General Services Office": { type: 'icon', class: 'bi-gear', color: '#475569', bg: '#f1f5f9' },
        "Human Resource Mgmt Office": { type: 'icon', class: 'bi-person-badge', color: '#059669', bg: '#ecfdf5' },
        "MENRO": { type: 'icon', class: 'bi-recycle', color: '#065f46', bg: '#d1fae5' },
        "MSWD": { type: 'icon', class: 'bi-droplet', color: '#1d4ed8', bg: '#dbeafe' },
        "Municipal Accounting Office": { type: 'icon', class: 'bi-calculator', color: '#6d28d9', bg: '#ede9fe' },
        "Municipal Agriculture Office": { type: 'icon', class: 'bi-flower1', color: '#4d7c0f', bg: '#ecfccb' },
        "Municipal Assessor's Office": { type: 'icon', class: 'bi-graph-up-arrow', color: '#c2410c', bg: '#ffedd5' },
        "Municipal Budget Office": { type: 'icon', class: 'bi-wallet2', color: '#a16207', bg: '#fef9c3' },
        "Municipal Cooperative Office": { type: 'icon', class: 'bi-hand-thumbs-up', color: '#0891b2', bg: '#ecfeff' },
        "Municipal Engineering Office": { type: 'icon', class: 'bi-buildings', color: '#92400e', bg: '#fffbeb' },
        "Municipal Health Office": { type: 'icon', class: 'bi-heart-pulse', color: '#dc2626', bg: '#fef2f2' },
        "Municipal Library": { type: 'icon', class: 'bi-book', color: '#9333ea', bg: '#faf5ff' },
        "Municipal Registration Office": { type: 'icon', class: 'bi-card-list', color: '#2563eb', bg: '#eff6ff' },
        "Municipal Tourism Office": { type: 'icon', class: 'bi-sun', color: '#f59e0b', bg: '#fffbeb' },
        "Municipal Treasury Office": { type: 'icon', class: 'bi-bank', color: '#15803d', bg: '#f0fdf4' },
        "OMPDC": { type: 'icon', class: 'bi-bar-chart', color: '#334155', bg: '#f8fafc' },
        "Office of the SB Secretariat": { type: 'icon', class: 'bi-pen', color: '#4b5563', bg: '#f3f4f6' },
        "Public Market Office": { type: 'icon', class: 'bi-shop', color: '#b91c1c', bg: '#fef2f2' },
        "Slaughterhouse Section": { type: 'icon', class: 'bi-egg', color: '#78350f', bg: '#fffbeb' },
        "Real Property Tax Section": { type: 'icon', class: 'bi-house-lock', color: '#1e40af', bg: '#dbeafe' },
        "POSO": { type: 'icon', class: 'bi-shield-check', color: '#111827', bg: '#f3f4f6' },
        "Public Information Office": { type: 'icon', class: 'bi-megaphone', color: '#be185d', bg: '#fdf2f7' }
    };

    function formatOffice(state) {
        if (!state.id) return state.text;
        
        const data = officeIcons[state.text];
        let $state;

        if (data) {
            if (data.type === 'img') {
                $state = $(
                    '<span><img src="' + data.src + '" class="office-icon" /> ' + state.text + '</span>'
                );
            } else {
                $state = $(
                    '<span class="d-flex align-items-center gap-3">' +
                    '<div class="office-logo-wrapper" style="background-color: ' + data.bg + ' !important">' +
                    '<i class="bi ' + data.class + '" style="color: ' + data.color + ' !important"></i>' +
                    '</div>' +
                    '<span>' + state.text + '</span>' +
                    '</span>'
                );
            }
        } else {
            $state = $(
                '<span class="d-flex align-items-center gap-3">' +
                '<div class="office-logo-wrapper"><i class="bi bi-building"></i></div>' +
                '<span>' + state.text + '</span>' +
                '</span>'
            );
        }
        return $state;
    }

    $('#office').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Office/Department',
        allowClear: true,
        templateResult: formatOffice,
        templateSelection: formatOffice
    });
});
</script>
</body>
</html>
