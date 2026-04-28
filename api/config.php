<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Smart Hospital — Database Configuration
 * Fill in your MySQL password below.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_hospital');
define('DB_USER', 'root');
define('DB_PASS', 'INTLECOREI9tanay');  // <-- Fill your MySQL root password here
define('ADMIN_SESSION_TTL_SECONDS', 43200);
define('DEFAULT_ADMIN_EMAIL', 'admin@test');
define('DEFAULT_ADMIN_DISPLAY_NAME', 'Command Center Admin');
define('DEFAULT_ADMIN_PASSWORD_HASH', '$2y$12$.hjrbdCZJFc3bkZwDVFE2eMrwbfzaCJ3dV81okwMXi1WWC4LRXA.K');

// Gemini API
define('GEMINI_API_KEY', 'AIzaSyALWy7LxqWj8MzEFZ5xybHanECVMmiAw28');

// CORS headers for frontend access
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Token');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Get a PDO database connection
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/**
 * Send JSON response
 */
function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Read JSON body from POST
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Read request data from a JSON body first, then fall back to form or query params.
 */
function getRequestData(): array {
    $json = getJsonBody();
    if (!empty($json)) {
        return $json;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    return $_GET;
}

/**
 * Check whether a table column exists.
 */
function columnExists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ");
    $stmt->execute([
        ':table' => $table,
        ':column' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Check whether a table exists.
 */
function tableExists(PDO $db, string $table): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
    ");
    $stmt->execute([':table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Ensure patient resource-assignment columns exist for mixed old/new schemas.
 */
function ensurePatientAssignmentColumns(PDO $db): void {
    if (!columnExists($db, 'patients', 'allocation')) {
        $db->exec("ALTER TABLE patients ADD COLUMN allocation VARCHAR(50) NULL");
    }

    if (!columnExists($db, 'patients', 'assigned_resource')) {
        $db->exec("ALTER TABLE patients ADD COLUMN assigned_resource VARCHAR(50) NULL");
    }

    if (!columnExists($db, 'patients', 'allocation_time')) {
        $db->exec("ALTER TABLE patients ADD COLUMN allocation_time DATETIME NULL");
    }

    if (!columnExists($db, 'patients', 'icu_required')) {
        $db->exec("ALTER TABLE patients ADD COLUMN icu_required TINYINT(1) NOT NULL DEFAULT 0");
    }
}

/**
 * Ensure admin auth tables and the default admin record exist.
 */
function ensureAdminTables(PDO $db): void {
    static $ensured = false;

    if ($ensured) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(120) NULL,
            status ENUM('active','disabled') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_sessions (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            admin_user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            last_used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_sessions_user (admin_user_id),
            INDEX idx_admin_sessions_expires (expires_at),
            CONSTRAINT fk_admin_sessions_user
                FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $seedStmt = $db->prepare("
        INSERT INTO admin_users (email, password_hash, display_name, status)
        VALUES (:email, :password_hash, :display_name, 'active')
        ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            display_name = VALUES(display_name),
            status = 'active'
    ");
    $seedStmt->execute([
        ':email' => DEFAULT_ADMIN_EMAIL,
        ':password_hash' => DEFAULT_ADMIN_PASSWORD_HASH,
        ':display_name' => DEFAULT_ADMIN_DISPLAY_NAME,
    ]);

    $ensured = true;
}

function hashAdminToken(string $token): string {
    return hash('sha256', $token);
}

function getRequestHeaderValue(string $name): ?string {
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    $value = $_SERVER[$serverKey] ?? null;

    if (is_string($value) && trim($value) !== '') {
        return trim($value);
    }

    return null;
}

function extractBearerToken(?string $header): ?string {
    if (!$header) {
        return null;
    }

    if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

function getAdminTokenFromRequest(): ?string {
    $bearerToken = extractBearerToken(getRequestHeaderValue('Authorization'));
    if ($bearerToken) {
        return $bearerToken;
    }

    return getRequestHeaderValue('X-Admin-Token');
}

function createAdminSession(PDO $db, int $adminUserId): array {
    ensureAdminTables($db);

    $db->prepare("DELETE FROM admin_sessions WHERE expires_at <= UTC_TIMESTAMP()")->execute();

    $token = bin2hex(random_bytes(32));
    $expiresAtUnix = time() + ADMIN_SESSION_TTL_SECONDS;
    $expiresAtDb = gmdate('Y-m-d H:i:s', $expiresAtUnix);

    $stmt = $db->prepare("
        INSERT INTO admin_sessions (admin_user_id, token_hash, expires_at, last_used_at)
        VALUES (:admin_user_id, :token_hash, :expires_at, UTC_TIMESTAMP())
    ");
    $stmt->execute([
        ':admin_user_id' => $adminUserId,
        ':token_hash' => hashAdminToken($token),
        ':expires_at' => $expiresAtDb,
    ]);

    return [
        'token' => $token,
        'expires_at' => gmdate(DATE_ATOM, $expiresAtUnix),
    ];
}

function revokeAdminSession(PDO $db, ?string $token): void {
    ensureAdminTables($db);

    if (!$token) {
        return;
    }

    $stmt = $db->prepare("DELETE FROM admin_sessions WHERE token_hash = :token_hash");
    $stmt->execute([':token_hash' => hashAdminToken($token)]);
}

function getAuthenticatedAdmin(PDO $db): ?array {
    static $resolved = false;
    static $admin = null;

    if ($resolved) {
        return $admin;
    }

    $resolved = true;
    ensureAdminTables($db);

    $token = getAdminTokenFromRequest();
    if (!$token) {
        return null;
    }

    $stmt = $db->prepare("
        SELECT
            u.id,
            u.email,
            u.display_name,
            u.status,
            s.id AS session_id,
            s.expires_at
        FROM admin_sessions s
        INNER JOIN admin_users u ON u.id = s.admin_user_id
        WHERE s.token_hash = :token_hash
          AND s.expires_at > UTC_TIMESTAMP()
        LIMIT 1
    ");
    $stmt->execute([':token_hash' => hashAdminToken($token)]);
    $admin = $stmt->fetch();

    if (!$admin || ($admin['status'] ?? 'disabled') !== 'active') {
        $admin = null;
        return null;
    }

    $touchStmt = $db->prepare("UPDATE admin_sessions SET last_used_at = UTC_TIMESTAMP() WHERE id = :id");
    $touchStmt->execute([':id' => $admin['session_id']]);

    return $admin;
}

function requireAdminAccess(): array {
    $db = getDB();
    $admin = getAuthenticatedAdmin($db);

    if (!$admin) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Authentication required. Please log in to continue.',
        ], 401);
    }

    return $admin;
}
