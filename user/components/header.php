<?php
// Session should be started in the main file that includes config.php
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userOffice = isset($_SESSION['user_office']) ? $_SESSION['user_office'] : 'Municipal Office';

// Fetch user profile image if logged in
$profileImage = null;
if ($userId) {
    include_once dirname(__DIR__, 2) . "/config.php";
    try {
        $userRes = mysqli_query($conn, "SELECT * FROM users WHERE id = '$userId'");
        if ($userRes && $user = mysqli_fetch_assoc($userRes)) {
            $profileImage = isset($user['profile_image']) ? $user['profile_image'] : null;
        }
    } catch (mysqli_sql_exception $e) {
        // Column might not exist in the database schema yet
        $profileImage = null;
    }
}
?>

<!-- TOP HEADER -->
<div class="top-header">

    <div class="header-left">
        <div class="header-logo-container">
            <i class="bi bi-cpu system-logo-icon"></i>
            <h1 class="system-title">ICT - RMAPS</h1>
        </div>
        <span class="system-tagline">
            Resource Monitoring & Asset Profiling System
        </span>
    </div>

    <div class="header-right">
        <div class="network-status" id="networkStatus">
            <i class="bi bi-wifi network-wifi-icon"></i>
            <div class="network-ping-large" id="networkPing">-- mbps</div>
        </div>
        
        <!-- USER PROFILE DROPDOWN -->
        <div class="dropdown" id="userProfileDropdown">
            <button class="btn btn-link dropdown-toggle d-flex align-items-center text-white text-decoration-none p-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; background: transparent; outline: none; box-shadow: none;">
                <div class="user-profile-info">
                    <div class="user-avatar-icon">
                        <?php if ($profileImage): ?>
                            <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="avatar-img">
                        <?php else: ?>
                            <i class="bi bi-person-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-details text-start">
                        <p class="user-name mb-0"><?php echo htmlspecialchars($userName); ?></p>
                        <small class="user-role"><?php echo htmlspecialchars($userOffice); ?></small>
                    </div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="dropdownMenuButton1" style="z-index: 9999; min-width: 240px; border-radius: 12px; padding: 0.75rem;">
                 <li><h6 class="dropdown-header" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 800; color: #8e99af; padding: 1.25rem 1.5rem 0.75rem;">Manage Account</h6></li>
                 <li><a class="dropdown-item d-flex align-items-center" href="office_profile.php" style="border-radius: 8px; font-size: 1.15rem; font-weight: 600; color: #1e293b; padding: 0.85rem 1.5rem;"><i class="bi bi-person me-3" style="font-size: 1.3rem;"></i> Profile</a></li>
                 <li><a class="dropdown-item d-flex align-items-center" href="setting.php" style="border-radius: 8px; font-size: 1.15rem; font-weight: 600; color: #1e293b; padding: 0.85rem 1.5rem;"><i class="bi bi-gear me-3" style="font-size: 1.3rem;"></i> Settings</a></li>
                 <li><hr class="dropdown-divider" style="margin: 0.5rem 0; border-top: 1px solid #e2e8f0;"></li>
                 <li><a class="dropdown-item d-flex align-items-center text-danger" href="logout.php" style="border-radius: 8px; font-size: 1.15rem; font-weight: 600; padding: 0.85rem 1.5rem;"><i class="bi bi-box-arrow-right me-3" style="font-size: 1.3rem;"></i> Logout</a></li>
             </ul>
        </div>
    </div>

</div>

<script>
(function() {
    const pingEl = document.getElementById('networkPing');
    const dropdownBtn = document.getElementById('dropdownMenuButton1');
    const dropdownMenu = dropdownBtn?.nextElementSibling;

    async function updateNetworkStatus() {
        const speed = (navigator.connection && navigator.connection.downlink) 
            ? navigator.connection.downlink 
            : null;

        if (speed !== null) {
            pingEl.textContent = speed + ' mbps';
        } else {
            pingEl.textContent = '-- mbps';
        }
    }

    updateNetworkStatus();
    setInterval(updateNetworkStatus, 1000);

    // Fallback: Manually handle dropdown if Bootstrap JS fails
    if (dropdownBtn && dropdownMenu) {
        dropdownBtn.addEventListener('click', function(e) {
            // Check if Bootstrap JS already handled it
            if (!this.classList.contains('show')) {
                // If not showing, show it manually
                const isShowing = dropdownMenu.classList.contains('show');
                if (!isShowing) {
                    dropdownMenu.classList.add('show');
                    dropdownMenu.setAttribute('data-bs-popper', 'static');
                } else {
                    dropdownMenu.classList.remove('show');
                    dropdownMenu.removeAttribute('data-bs-popper');
                }
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownMenu.removeAttribute('data-bs-popper');
            }
        });
    }
})();</script>
