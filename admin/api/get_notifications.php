<?php
include_once dirname(__DIR__, 2) . '/config.php';

header('Content-Type: application/json');

$result = [
    'count' => 0,
    'items' => []
];

// Report only open ISSP submissions grouped by office and date.
$openFormsRes = mysqli_query($conn, "SELECT office_name, date_submitted FROM user_issp_form WHERE COALESCE(form_status, 'Open') = 'Open' GROUP BY office_name, date_submitted ORDER BY date_submitted DESC LIMIT 10");
if ($openFormsRes) {
    $items = [];
    while ($row = mysqli_fetch_assoc($openFormsRes)) {
        $items[] = [
            'office_name' => $row['office_name'],
            'date_submitted' => $row['date_submitted'],
        ];
    }
    $result['items'] = $items;
    $result['count'] = count($items);
}

echo json_encode($result);
