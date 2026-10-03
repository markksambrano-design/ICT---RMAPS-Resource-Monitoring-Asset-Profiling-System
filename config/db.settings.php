<?php
$dbConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'ict_mis',
];

$localConfigPath = __DIR__ . '/db.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $dbConfig = array_merge($dbConfig, $localConfig);
    }
}

return $dbConfig;