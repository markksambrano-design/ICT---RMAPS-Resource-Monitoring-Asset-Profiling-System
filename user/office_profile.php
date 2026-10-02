<?php
include __DIR__ . '/../config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Ensure extra columns exist in users table
$columns_to_add = [
    'head_of_office' => "VARCHAR(100)",
    'contact_number' => "VARCHAR(20)",
    'office_email' => "VARCHAR(100)",
    'mission_statement' => "TEXT",
    'profile_image' => "VARCHAR(255)"
];

foreach ($columns_to_add as $col => $type) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN $col $type");
    }
}

// Ensure gallery table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS office_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    image_path VARCHAR(255),
    caption VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get user information from session and database
$userId = $_SESSION['user_id'];
$userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = '$userId'");
$userData = mysqli_fetch_assoc($userRes);

$userName = $userData['first_name'] . ' ' . $userData['last_name'];
$userOffice = $userData['office'];
$userEmail = $userData['email'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newFirstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $newLastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    
    // Handle profile image upload
    $profileImageSql = "";
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $targetDir = "../assest/images/profiles/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
        $targetPath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            $profileImageSql = ", profile_image = '$targetPath'";
        }
    }
    
    $updateSql = "UPDATE users SET first_name = '$newFirstName', last_name = '$newLastName' $profileImageSql WHERE id = '$userId'";
    if (mysqli_query($conn, $updateSql)) {
        $_SESSION['user_name'] = $newFirstName . ' ' . $newLastName;
        header("Location: office_profile.php?success=1");
        exit;
    }
}

// Handle office intel update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_intel'])) {
    $head = mysqli_real_escape_string($conn, $_POST['head_of_office']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $offEmail = mysqli_real_escape_string($conn, $_POST['office_email']);
    $mission = mysqli_real_escape_string($conn, $_POST['mission_statement']);
    
    $updateIntelSql = "UPDATE users SET head_of_office = '$head', contact_number = '$contact', office_email = '$offEmail', mission_statement = '$mission' WHERE id = '$userId'";
    if (mysqli_query($conn, $updateIntelSql)) {
        header("Location: office_profile.php?success=intel");
        exit;
    }
}

// Handle gallery upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_gallery'])) {
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === 0) {
        $targetDir = "../assest/images/gallery/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . "_" . basename($_FILES['gallery_image']['name']);
        $targetPath = $targetDir . $fileName;
        $caption = mysqli_real_escape_string($conn, $_POST['caption'] ?? '');
        
        if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $targetPath)) {
            mysqli_query($conn, "INSERT INTO office_gallery (user_id, image_path, caption) VALUES ('$userId', '$targetPath', '$caption')");
            header("Location: office_profile.php?success=gallery");
            exit;
        }
    }
}

// Get office assets count
$userOfficeEscaped = mysqli_real_escape_string($conn, $userOffice);
$assetsRes = mysqli_query($conn, "SELECT COUNT(*) as total FROM ict_forms WHERE office_name = '$userOfficeEscaped'");
$assetsData = mysqli_fetch_assoc($assetsRes);
$totalAssets = $assetsData['total'];

// Get gallery images
$galleryRes = mysqli_query($conn, "SELECT * FROM office_gallery WHERE user_id = '$userId' ORDER BY created_at DESC");


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $pageTitle = 'Office Profile - ICTMIS'; ?>
    <?php include 'components/head.php'; ?>
    <!-- GSAP for Animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-main: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
            background: #f8fafc;
        }

        .main-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            margin-left: 260px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0;
        }

        .sidebar-collapsed .main-content {
            margin-left: 90px;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 90px;
            }
        }

        .content-area { padding: 30px 40px; }

        /* Modern Navigation Tabs */
        .nav-tabs-custom {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.5);
            padding: 8px;
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: fit-content;
        }

        .nav-btn {
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover { background: rgba(255, 255, 255, 0.8); color: var(--primary); }

        .nav-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        /* Profile Card Styling */
        .profile-card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 40px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 35px;
        }

        .icon-box {
            width: 64px; height: 64px;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .info-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 25px;
        }

        .stats-badge {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }

        .stats-num {
            display: block;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }

        .stats-label { font-size: 0.8rem; color: var(--text-muted); }

        /* Gallery */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .gallery-card {
            background: #f8fafc;
            border-radius: 16px;
            aspect-ratio: 4/3;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #cbd5e1;
            color: #94a3b8;
            transition: all 0.3s;
            cursor: pointer;
        }

        .gallery-card:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(79, 70, 229, 0.02);
        }

        .section-content { display: none; opacity: 0; }
        .section-content.active { display: block; opacity: 1; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .content-area { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/components/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/components/header.php'; ?>

            <div class="content-area">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h1 class="fw-bold mb-1">Office Hub</h1>
                        <p class="text-muted mb-0">Management portal for <?php echo htmlspecialchars($userOffice); ?></p>
                    </div>
                    <?php if(isset($_GET['success'])): ?>
                        <?php 
                        $msg = "Profile updated successfully!";
                        if($_GET['success'] === 'intel') $msg = "Office details updated!";
                        if($_GET['success'] === 'gallery') $msg = "Image uploaded to gallery!";
                        ?>
                        <div class="alert alert-success py-2 px-3 mb-0 rounded-pill shadow-sm animate__animated animate__fadeInRight" style="font-size: 0.85rem;">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="nav-tabs-custom">
                    <button class="nav-btn active" data-target="profile">
                        <i class="bi bi-person-circle"></i> User Profile
                    </button>
                    <button class="nav-btn" data-target="office">
                        <i class="bi bi-building"></i> Office Intel
                    </button>
                    <button class="nav-btn" data-target="gallery">
                        <i class="bi bi-images"></i> Gallery
                    </button>
                </div>

                <!-- Profile Section -->
                <div id="profile" class="section-content active">
                    <div class="profile-card">
                        <div class="section-header">
                            <div class="icon-box"><i class="bi bi-shield-lock"></i></div>
                            <div>
                                <h3 class="mb-0 fw-bold">Personal Information</h3>
                                <p class="text-muted mb-0">Details linked to your system account</p>
                            </div>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row align-items-center mb-4">
                                <div class="col-md-auto text-center mb-3 mb-md-0">
                                    <div class="position-relative d-inline-block">
                                        <?php 
                                        $profilePic = $userData['profile_image'] ?: 'https://ui-avatars.com/api/?name='.urlencode($userName).'&background=4f46e5&color=fff&size=128';
                                        ?>
                                        <img src="<?php echo $profilePic; ?>" alt="Profile" class="rounded-circle border border-3 border-white shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                                        <label for="profile_image" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 shadow-sm cursor-pointer" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-camera-fill small"></i>
                                            <input type="file" id="profile_image" name="profile_image" class="d-none" accept="image/*">
                                        </label>
                                    </div>
                                </div>
                                <div class="col">
                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($userName); ?></h5>
                                    <p class="text-muted small mb-0">Update your personal photo and name details.</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-label">First Name</div>
                                    <input type="text" name="first_name" class="form-control mb-3 rounded-3" value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Last Name</div>
                                    <input type="text" name="last_name" class="form-control mb-3 rounded-3" value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Email Address</div>
                                    <div class="info-value bg-light p-2 rounded-3"><?php echo htmlspecialchars($userEmail); ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">Account Role</div>
                                    <div class="info-value bg-light p-2 rounded-3">Office Representative</div>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" name="update_profile" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                                        <i class="bi bi-save me-2"></i> Update Profile
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Office Details Section -->
                <div id="office" class="section-content">
                    <div class="profile-card">
                        <div class="section-header">
                            <div class="icon-box"><i class="bi bi-info-square"></i></div>
                            <div>
                                <h3 class="mb-0 fw-bold">Office Specifications</h3>
                                <p class="text-muted mb-0">Official information about <?php echo htmlspecialchars($userOffice); ?></p>
                            </div>
                        </div>

                        <form action="" method="POST">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Assigned Office</div>
                                            <div class="info-value bg-light p-2 rounded-3"><?php echo htmlspecialchars($userOffice); ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Head of Office</div>
                                            <input type="text" name="head_of_office" class="form-control rounded-3" value="<?php echo htmlspecialchars($userData['head_of_office'] ?? ''); ?>" placeholder="Enter Head of Office">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Office Email</div>
                                            <input type="email" name="office_email" class="form-control rounded-3" value="<?php echo htmlspecialchars($userData['office_email'] ?? ''); ?>" placeholder="office@municipality.gov">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="info-label">Contact Number</div>
                                            <input type="text" name="contact_number" class="form-control rounded-3" value="<?php echo htmlspecialchars($userData['contact_number'] ?? ''); ?>" placeholder="09XX XXX XXXX">
                                        </div>
                                        <div class="col-12 mb-3">
                                            <div class="info-label">Mission Statement</div>
                                            <textarea name="mission_statement" class="form-control rounded-3" rows="4" placeholder="Enter your office's mission statement..."><?php echo htmlspecialchars($userData['mission_statement'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" name="update_intel" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                                                <i class="bi bi-check2-circle me-2"></i> Save Office Intel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stats-badge mb-3 shadow-sm border-0">
                                        <span class="stats-num"><?php echo $totalAssets; ?></span>
                                        <span class="stats-label">Tracked Assets</span>
                                    </div>
                                    <div class="stats-badge shadow-sm border-0">
                                        <span class="stats-num text-success">Active</span>
                                        <span class="stats-label">Office Status</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Gallery Section -->
                <div id="gallery" class="section-content">
                    <div class="profile-card">
                        <div class="section-header">
                            <div class="icon-box"><i class="bi bi-camera"></i></div>
                            <div>
                                <h3 class="mb-0 fw-bold">Visual Documentation</h3>
                                <p class="text-muted mb-0">Upload and manage office-related media</p>
                            </div>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data" class="mb-5 p-4 border rounded-4 bg-light">
                            <h5 class="fw-bold mb-3">Add New Photo</h5>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <input type="file" name="gallery_image" class="form-control rounded-3" accept="image/*" required>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="caption" class="form-control rounded-3" placeholder="Image caption (optional)">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="upload_gallery" class="btn btn-primary w-100 rounded-pill">
                                        <i class="bi bi-upload me-2"></i> Upload
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="gallery-grid">
                            <?php if (mysqli_num_rows($galleryRes) > 0): ?>
                                <?php while ($img = mysqli_fetch_assoc($galleryRes)): ?>
                                    <div class="gallery-card position-relative overflow-hidden shadow-sm" style="background-image: url('<?php echo $img['image_path']; ?>'); background-size: cover; border: none; aspect-ratio: 1/1;">
                                        <div class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-50 text-white small text-center">
                                            <?php echo htmlspecialchars($img['caption'] ?: 'No caption'); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="gallery-card">
                                    <i class="bi bi-image mb-2" style="font-size: 2rem;"></i>
                                    <span class="fw-bold">No Photos Yet</span>
                                    <span class="small">Upload your first office photo above</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Tab switching logic
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = btn.dataset.target;
                    
                    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    const activeSection = document.querySelector('.section-content.active');
                    const nextSection = document.getElementById(target);

                    if (activeSection === nextSection) return;

                    gsap.to(activeSection, {
                        opacity: 0,
                        y: 10,
                        duration: 0.3,
                        onComplete: () => {
                            activeSection.classList.remove('active');
                            activeSection.style.display = 'none';
                            
                            nextSection.style.display = 'block';
                            nextSection.classList.add('active');
                            gsap.fromTo(nextSection, { opacity: 0, y: 10 }, { opacity: 1, y: 0, duration: 0.4 });
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
