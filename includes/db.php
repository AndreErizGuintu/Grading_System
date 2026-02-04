<?php
$config = require __DIR__ . '/config.php';
$dbcfg = $config['db'];

$mysqli = new mysqli(
    $dbcfg['host'],
    $dbcfg['user'],
    $dbcfg['pass'],
    $dbcfg['name'],
    (int)$dbcfg['port']
);

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo 'Database connection failed.';
    exit;
}

$mysqli->set_charset('utf8mb4');
