<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

try {
    $db = getDB();
    revokeAdminSession($db, getAdminTokenFromRequest());

    jsonResponse([
        'status' => 'success',
        'message' => 'Logged out successfully',
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
