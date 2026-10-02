<?php
// Common head section for user pages
// Set $pageTitle before including for a custom title
if (!isset($pageTitle)) {
    $pageTitle = 'ICTMIS - Municipal Portal';
}

// Set default asset path if not already set by the including page
if (!isset($assetPath)) {
    $assetPath = '/assest/css/user';
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>

<?php
// Set favicon path
$faviconPath = '/assest/images/logo3.png';
?>

<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<script>
    /**
     * ULTRA BACK-BUTTON KILLER
     * ------------------------
     * More aggressive script to prevent leaving the current session 
     * using the browser's back button.
     */
    (function() {
        // Prevent backward navigation by forcing history forward
        function preventBack() {
            window.history.forward();
        }
        
        // Initial forward push
        setTimeout(preventBack, 0);
        
        // Push a null state immediately to handle future back clicks
        window.history.pushState(null, null, window.location.href);
        
        // On any back button press, push forward again
        window.onpopstate = function() {
            window.history.pushState(null, null, window.location.href);
            preventBack();
        };

        // Handle page reload/cache scenarios
        window.onunload = function() { null };
        
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    })();
</script>
<link rel="icon" type="image/png" href="<?php echo $faviconPath; ?>">

<!-- BOOTSTRAP 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- BOOTSTRAP ICONS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- GOOGLE FONTS -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- ANIMATE.CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<!-- COMMON STYLES -->
<link rel="stylesheet" href="<?php echo $assetPath; ?>/sidebar.css">
<link rel="stylesheet" href="<?php echo $assetPath; ?>/header.css">

<style>
    html, body, input, textarea, select, button, a, p, span, div, label {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    }
</style>

<!-- COMMON SCRIPTS (placed in head for convenience) -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Theme and Sidebar State Handler -->
<script>
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.documentElement.classList.add('sidebar-collapsed');
    }
    const savedTheme = localStorage.getItem('theme') || localStorage.getItem('darkMode');
    if (savedTheme === 'dark' || savedTheme === 'enabled') {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark-mode');
    }
</script>

<!-- BOOTSTRAP BUNDLE JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
