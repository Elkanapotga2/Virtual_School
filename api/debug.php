<?php
header("Content-Type: application/json");

$vars = [
    "MYSQLHOST"     => getenv('MYSQLHOST'),
    "MYSQLPORT"     => getenv('MYSQLPORT'),
    "MYSQLUSER"     => getenv('MYSQLUSER'),
    "MYSQLDATABASE" => getenv('MYSQLDATABASE'),
    "MYSQLPASSWORD" => getenv('MYSQLPASSWORD') ? "***défini***" : "❌ NON DÉFINI",
];

echo json_encode($vars, JSON_PRETTY_PRINT);
