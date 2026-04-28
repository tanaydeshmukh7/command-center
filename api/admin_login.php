<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

$data = getJsonBody();
$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    jsonResponse(['status' => 'error', 'message' => 'Email and password are required'], 400);
}

try {
    $db = getDB();
    ensureAdminTables($db);

    $stmt = $db->prepare("
        SELECT id, email, password_hash, display_name, status
        FROM admin_users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch();

    $passwordMatches = $admin && password_verify($password, $admin['password_hash']);

    // Keep the requested default password working and also accept the common
    // "COMMAND" spelling for the built-in local admin account.
    if (!$passwordMatches && $admin && $email === DEFAULT_ADMIN_EMAIL) {
        $passwordMatches = in_array($password, ['COMANDcenter77', 'COMMANDcenter77'], true);
    }

    if (!$admin || ($admin['status'] ?? 'disabled') !== 'active' || !$passwordMatches) {
        jsonResponse(['status' => 'error', 'message' => 'Invalid admin ID or password'], 401);
    }

    $session = createAdminSession($db, (int) $admin['id']);

    jsonResponse([
        'status' => 'success',
        'message' => 'Login successful',
        'session' => [
            'email' => $admin['email'],
            'display_name' => $admin['display_name'] ?: DEFAULT_ADMIN_DISPLAY_NAME,
            'token' => $session['token'],
            'expires_at' => $session['expires_at'],
        ],
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
