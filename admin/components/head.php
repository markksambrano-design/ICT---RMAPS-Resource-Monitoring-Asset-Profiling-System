<?php
// Common head section for admin pages
// Set $pageTitle before including for a custom title
if (!isset($pageTitle)) {
    $pageTitle = 'ICTMIS';
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>

<?php
// Set favicon path based on assetPath
if (!isset($assetPath)) {
    $tempAssetPath = '../assest/css/admin';
} else {
    $tempAssetPath = $assetPath;
}
$faviconPath = str_replace('css/admin', 'images/logo3.png', $tempAssetPath);
?>

<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<script>
    /**
     * ADVANCED BACK-BUTTON TRAP
     * ------------------------
     * Prevents the browser's 'Back' button from leaving the current page
     * while the user is logged in. This forces the user to use the 
     * explicit 'Logout' button to go back to the login page.
     */
    (function() {
        // First push
        window.history.pushState(null, null, window.location.href);
        
        // Listen for 'Back' click (popstate)
        window.onpopstate = function() {
            // Immediately push back the state to "cancel" the back navigation
            window.history.pushState(null, null, window.location.href);
        };

        // Extra layer: Handle the 'pageshow' event for bfcache (back-forward cache)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (typeof window.performance != "undefined" && window.performance.getEntriesByType("navigation")[0].type === "back_forward")) {
                window.location.reload();
            }
        });
    })();
</script>

<link rel="icon" type="image/png" href="<?php echo $faviconPath; ?>">

<!-- BOOTSTRAP 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- DATATABLES (optional, safe to include every page) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

<!-- BOOTSTRAP ICONS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- GOOGLE FONTS -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- ANIMATE.CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<!-- SWEETALERT2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- COMMON STYLES -->
<?php
// Allow pages to override if they live in a deeper directory
if (!isset($assetPath)) {
    $assetPath = '../assest/css/admin';
}
?>
<link rel="stylesheet" href="<?php echo $assetPath; ?>/sidebar.css">
<link rel="stylesheet" href="<?php echo $assetPath; ?>/admin-layout.css">

<!-- COMMON SCRIPTS (placed in head for convenience) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>

<!-- Theme and Sidebar State Handler -->
<script>
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.documentElement.classList.add('sidebar-collapsed');
    }
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.documentElement.classList.add('dark-mode');
    }
</script>

<!-- BOOTSTRAP BUNDLE JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
