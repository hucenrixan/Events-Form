<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'u764136565_wedding_db');
define('DB_USER', 'u764136565_root');
define('DB_PASS', 'Rixan@9688');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("DB connection FAILED: " . $conn->connect_error);
}

echo "DB connection OK ✅";

