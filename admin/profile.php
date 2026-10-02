<?php
include "../config.php";
include "../config/functions.php";
include "../config/auth_check.php";

$email = $_SESSION['email'];
$adminQuery = safeQuery($conn, "SELECT * FROM admin WHERE email = '$email'");
$adminData = mysqli_fetch_assoc($adminQuery);

$success = "";
$error = "";

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);
    $upload_ok = true;
    $profile_image = $adminData['profile_image'];

    // Handle Image Upload
    if (isset($_POST['selected_default_image']) && $_POST['selected_default_image'] != "") {
        $profile_image = mysqli_real_escape_string($conn, $_POST['selected_default_image']);
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../assest/images/admin _profile/";
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "admin_" . $adminData['id'] . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                // Delete old image if exists
                if ($profile_image && file_exists($target_dir . $profile_image)) {
                    unlink($target_dir . $profile_image);
                }
                $profile_image = $new_filename;
            } else {
                $error = "Sorry, there was an error uploading your file.";
                $upload_ok = false;
            }
        } else {
            $error = "File is not an image.";
            $upload_ok = false;
        }
    }

    if ($upload_ok) {
        // Email duplication check
        $checkEmail = mysqli_query($conn, "SELECT * FROM admin WHERE email = '$new_email' AND id != '{$adminData['id']}'");
        if (mysqli_num_rows($checkEmail) > 0) {
            $error = "Email address is already in use by another account.";
        } else {
            $updateQuery = "UPDATE admin SET name = '$name', email = '$new_email', profile_image = '$profile_image' WHERE id = '{$adminData['id']}'";
            if (mysqli_query($conn, $updateQuery)) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $new_email;
                $_SESSION['profile_image'] = $profile_image;
                $success = "Profile information updated successfully!";
                // Refresh local data
                $adminQuery = safeQuery($conn, "SELECT * FROM admin WHERE id = '{$adminData['id']}'");
                $adminData = mysqli_fetch_assoc($adminQuery);
            } else {
                $error = "System error: Failed to update profile.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'My Profile | ICTMIS'; ?>
<?php include 'components/head.php'; ?>
<link rel="stylesheet" href="../assest/css/admin/setting.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>

<body>

<div class="d-flex">
    <!-- SIDEBAR -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP BAR -->
        <?php include 'components/header.php'; ?>
        
        <!-- PAGE HEADER -->
        <div class="welcome-header futuristic-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="page-title">My Profile</h1>
                    <p class="page-subtitle mb-0">Manage your personal information and account identity.</p>
                </div>
                <div class="col-md-4 text-md-end d-none d-md-block">
                    <i class="bi bi-person-bounding-box header-icon"></i>
                </div>
            </div>
        </div>

        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-lg-11">
                    <!-- STATUS MESSAGES -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 animate__animated animate__fadeInDown" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 animate__animated animate__fadeInDown" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="security-card profile-card-bg animate__animated animate__fadeInUp">
                        <div class="d-flex align-items-center mb-4">
                            <div class="security-icon-wrapper me-3">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Personal Information</h5>
                                <p class="text-muted mb-0 small">Update your account profile details.</p>
                            </div>
                        </div>
                        <hr class="mb-5">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row g-4">
                                <div class="col-md-12 text-center mb-5">
                                    <div class="profile-avatar-setup mx-auto mb-3">
                                        <?php if ($adminData['profile_image']): ?>
                                            <img src="../assest/images/admin _profile/<?php echo htmlspecialchars($adminData['profile_image']); ?>" alt="Profile" class="main-avatar-img">
                                        <?php else: ?>
                                            <i class="bi bi-person-circle main-icon"></i>
                                        <?php endif; ?>
                                        <input type="file" name="profile_image" id="profile_image_input" style="display: none;" accept="image/*" onchange="previewImage(this)">
                                        <input type="hidden" name="selected_default_image" id="selected_default_image">
                                        <button type="button" class="btn edit-btn shadow-sm" data-bs-toggle="modal" data-bs-target="#profileSelectModal">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                    </div>
                                    <p class="text-muted small fw-medium">Profile Avatar</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-uppercase tracking-wider opacity-75">Full Name</label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($adminData['name'] ?? ''); ?>" placeholder="Enter full name">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-uppercase tracking-wider opacity-75">Email Address</label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($adminData['email']); ?>" placeholder="Enter email address">
                                    </div>
                                </div>
                                <div class="col-12 pt-4">
                                    <button type="submit" name="update_profile" class="btn btn-primary-custom px-4">
                                        <i class="bi bi-box-arrow-in-down"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PROFILE SELECTION MODAL -->
<div class="modal fade" id="profileSelectModal" tabindex="-1" aria-labelledby="profileSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg profile-modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="profileSelectModalLabel">Select Profile Picture</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-4 text-center">Choose from our defaults or upload your own photo.</p>
                
                <div class="row g-4 mb-5 px-2">
                    <?php 
                    $defaults = ['admin1.png', 'admin2.png', 'admin3.png', 'admin4.png'];
                    foreach($defaults as $img): 
                        $isSelected = ($adminData['profile_image'] == $img) ? 'active' : '';
                    ?>
                    <div class="col-3 text-center">
                        <div class="default-img-option <?php echo $isSelected; ?>" onclick="selectDefault('<?php echo $img; ?>', this)">
                            <div class="img-wrapper shadow-sm">
                                <img src="../assest/images/admin _profile/<?php echo $img; ?>" alt="Default" class="img-fluid rounded-circle">
                                <div class="check-badge">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="upload-section mt-2">
                    <div class="hr-divider mb-4">
                        <span>OR</span>
                    </div>
                    <button type="button" class="btn btn-upload-custom w-100" onclick="document.getElementById('profile_image_input').click(); bootstrap.Modal.getInstance(document.getElementById('profileSelectModal')).hide();">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="bi bi-cloud-arrow-up fs-5 me-2"></i>
                            <span class="fw-semibold">Upload Custom Photo</span>
                        </div>
                    </button>
                    <p class="text-center text-muted mt-2 mb-0" style="font-size: 0.75rem;">JPG, PNG or WEBP. Max 2MB</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-modal-content {
    border-radius: 24px !important;
    background: #ffffff;
}
.main-avatar-img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 5px solid #fff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.profile-avatar-setup {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto;
}
.profile-avatar-setup .main-icon {
    font-size: 150px;
    color: #f1f5f9;
    line-height: 1;
}
.profile-avatar-setup .edit-btn {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: #4f46e5;
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #fff;
    transition: all 0.2s ease;
}
.profile-avatar-setup .edit-btn:hover {
    background: #4338ca;
    transform: scale(1.1);
    color: white;
}

/* Modal Specifics */
.img-wrapper {
    position: relative;
    border-radius: 50%;
    padding: 3px;
    background: #fff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.default-img-option {
    cursor: pointer;
}
.default-img-option img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    border-radius: 50%;
    transition: all 0.3s ease;
}
.check-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #10b981;
    color: white;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 2px solid #fff;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.default-img-option.active .img-wrapper {
    background: #4f46e5;
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2) !important;
}
.default-img-option.active .check-badge {
    opacity: 1;
    transform: scale(1);
}
.default-img-option:not(.active):hover .img-wrapper {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
}

.hr-divider {
    display: flex;
    align-items: center;
    text-align: center;
    color: #cbd5e1;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
}
.hr-divider::before, .hr-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #f1f5f9;
}
.hr-divider span {
    padding: 0 15px;
}

.btn-upload-custom {
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    color: #475569;
    padding: 14px;
    border-radius: 16px;
    transition: all 0.2s ease;
}
.btn-upload-custom:hover {
    background: #f1f5f9;
    border-color: #4f46e5;
    color: #4f46e5;
}
</style>

<script>
function selectDefault(filename, element) {
    // Remove active class from all
    document.querySelectorAll('.default-img-option').forEach(opt => opt.classList.remove('active'));
    // Add active to current
    element.classList.add('active');
    
    document.getElementById('selected_default_image').value = filename;
    
    // Preview the selection
    let avatarContainer = document.querySelector('.profile-avatar-setup');
    let existingImg = avatarContainer.querySelector('img');
    let existingIcon = avatarContainer.querySelector('.main-icon');
    let src = '../assest/images/admin _profile/' + filename;

    if (existingImg) {
        existingImg.src = src;
    } else if (existingIcon) {
        existingIcon.style.display = 'none';
        let img = document.createElement('img');
        img.src = src;
        img.className = 'main-avatar-img';
        avatarContainer.insertBefore(img, avatarContainer.firstChild);
    }

    // Auto close with small delay for visual feedback
    setTimeout(() => {
        bootstrap.Modal.getInstance(document.getElementById('profileSelectModal')).hide();
    }, 400);
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        // Clear active states in modal
        document.querySelectorAll('.default-img-option').forEach(opt => opt.classList.remove('active'));
        document.getElementById('selected_default_image').value = "";
        
        var reader = new FileReader();
        reader.onload = function(e) {
            let avatarContainer = document.querySelector('.profile-avatar-setup');
            let existingImg = avatarContainer.querySelector('img');
            let existingIcon = avatarContainer.querySelector('.main-icon');
            
            if (existingImg) {
                existingImg.src = e.target.result;
            } else if (existingIcon) {
                existingIcon.style.display = 'none';
                let img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'main-avatar-img';
                avatarContainer.insertBefore(img, avatarContainer.firstChild);
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
