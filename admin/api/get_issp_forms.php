<?php
require_once __DIR__ . '/../../config.php';

$officeFilter = " WHERE COALESCE(u.form_status, 'Open') != 'Closed'";
if (isset($_SESSION['role']) && $_SESSION['role'] == 'user') {
    $officeName = mysqli_real_escape_string($conn, $_SESSION['user_office'] ?? '');
    if ($officeName !== '') {
        $officeFilter .= " AND u.office_name = '$officeName'";
    }
}

$sql = "
SELECT
    u.office_name,
    u.date_submitted
FROM
    user_issp_form u" . $officeFilter . "
GROUP BY u.office_name, u.date_submitted
ORDER BY u.date_submitted DESC, u.office_name ASC";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo '<div class="table-responsive">';
    echo '<table id="isspFormsTable" class="table table-hover table-striped table-bordered mb-0 shadow-sm">';
    echo '<thead class="bg-dark text-white text-uppercase small fw-bold">';
    echo '<tr>';
    echo '<th class="py-3 px-4 text-center">No.</th>';
    echo '<th class="py-3 px-4">Office Name</th>';
    echo '<th class="py-3 px-4">Date Submitted</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    $rowIndex = 1;
    while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr class="table-row align-middle" data-office="' . htmlspecialchars($row['office_name']) . '" data-date="' . htmlspecialchars($row['date_submitted']) . '">';
        echo '<td class="px-4 text-center">' . ($rowIndex++) . '</td>';
        echo '<td class="px-4">' . htmlspecialchars($row['office_name']) . '</td>';
        echo '<td class="px-4">' . htmlspecialchars($row['date_submitted']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="no-data-card animate__animated animate__fadeInUp border-0 shadow-none bg-transparent" style="padding: 40px 20px;">';
    echo '    <div class="no-data-content text-center">';
    echo '        <div class="no-data-illustration mb-4">';
    echo '            <div class="illustration-wrapper" style="background: #f0f4f8; width: 150px; height: 150px; border-radius: 50%; position: relative; margin: 0 auto; display: flex; align-items: center; justify-content: center;">';
    echo '                <!-- Clipboard Icon with lines -->';
    echo '                <i class="bi bi-clipboard2-data" style="font-size: 5.5rem; color: #94a3b8; opacity: 0.8;"></i>';
    echo '                <!-- Blue Info Badge -->';
    echo '                <div class="info-badge shadow-sm" style="position: absolute; bottom: 10px; right: 10px; background: #4a90e2; width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #fff;">';
    echo '                    <i class="bi bi-info" style="color: #fff; font-size: 1.8rem; font-weight: 900; font-style: italic;"></i>';
    echo '                </div>';
    echo '            </div>';
    echo '        </div>';
    echo '        <h3 class="fw-bold mb-2" style="color: #0f172a; font-size: 1.75rem;">No data found.</h3>';
    echo '        <p class="mb-4" style="color: #64748b; font-size: 1.05rem;">There are no submitted RMAPS forms to display.</p>';
    echo '        <a href="../form.php" class="btn btn-light rounded-pill px-4 py-2 border" style="background: #fff; border: 1.5px solid #e2e8f0; color: #2563eb; font-weight: 700; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">';
    echo '            <i class="bi bi-file-earmark-text"></i> Go to Form';
    echo '        </a>';
    echo '    </div>';
    echo '</div>';
}
?>
