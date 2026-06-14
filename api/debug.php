<?php
header("Content-Type: application/json");

echo json_encode([
    "pdo_loaded"       => extension_loaded('pdo'),
    "pdo_mysql_loaded" => extension_loaded('pdo_mysql'),
    "extensions"       => get_loaded_extensions(),
    "MYSQLHOST"        => getenv('MYSQLHOST') ?: "❌ NON DÉFINI",
    "MYSQLPORT"        => getenv('MYSQLPORT') ?: "❌ NON DÉFINI",
    "MYSQLUSER"        => getenv('MYSQLUSER') ?: "❌ NON DÉFINI",
    "MYSQLDATABASE"    => getenv('MYSQLDATABASE') ?: "❌ NON DÉFINI",
    "MYSQLPASSWORD"    => getenv('MYSQLPASSWORD') ? "✅ défini" : "❌ NON DÉFINI",
], JSON_PRETTY_PRINT);
