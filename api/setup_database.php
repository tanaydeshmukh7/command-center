<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Setup Database — Run once to create tables and seed data.
 * Usage: php api/setup_database.php  OR  visit https://command-center-n9h1.onrender.com/api/setup_database.php
 */
require_once __DIR__ . '/config.php';

try {
    // Connect without DB name first to create the database
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/../smart_hospital.sql';
    if (!file_exists($sqlFile)) {
        jsonResponse(['status' => 'error', 'message' => 'smart_hospital.sql not found'], 500);
    }

    $sql = file_get_contents($sqlFile);

    // Split by semicolons and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && $s !== ''
    );

    $executed = 0;
    foreach ($statements as $stmt) {
        // Skip comments-only statements
        $clean = preg_replace('/--.*$/m', '', $stmt);
        $clean = trim($clean);
        if (empty($clean)) continue;

        $pdo->exec($stmt);
        $executed++;
    }

    jsonResponse([
        'status'  => 'success',
        'message' => "Database setup complete. Executed $executed statements.",
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
