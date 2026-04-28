<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Admin: Add new resource
 * POST /api/admin_add_resource.php
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

$data = getJsonBody();
$resourceCode = isset($data['resource_code']) ? trim((string) $data['resource_code']) : '';
$name = isset($data['name']) ? trim((string) $data['name']) : '';
$type = isset($data['type']) ? trim((string) $data['type']) : '';
$ward = isset($data['ward']) ? trim((string) $data['ward']) : '';

if (!$resourceCode || !$name || !$type || !$ward) {
    jsonResponse(['status' => 'error', 'message' => 'resource_code, name, type, and ward are required'], 400);
}

// Validate type
$validTypes = ['icu_bed', 'general_bed', 'or_room', 'ventilator', 'monitor', 'wheelchair', 'discharge_lounge'];
if (!in_array($type, $validTypes)) {
    jsonResponse([
        'status' => 'error',
        'message' => 'Invalid type. Must be one of: ' . implode(', ', $validTypes),
    ], 400);
}

try {
    $db = getDB();

    // Check if resource code already exists
    $checkStmt = $db->prepare('SELECT id FROM resources WHERE resource_code = :code LIMIT 1');
    $checkStmt->execute([':code' => $resourceCode]);
    if ($checkStmt->fetch()) {
        jsonResponse(['status' => 'error', 'message' => "Resource code '{$resourceCode}' already exists"], 409);
    }

    // Insert new resource
    $insertStmt = $db->prepare("
        INSERT INTO resources (resource_code, name, type, ward, status, created_at)
        VALUES (:code, :name, :type, :ward, 'available', NOW())
    ");
    $insertStmt->execute([
        ':code' => $resourceCode,
        ':name' => $name,
        ':type' => $type,
        ':ward' => $ward,
    ]);

    $resourceId = $db->lastInsertId();

    jsonResponse([
        'status' => 'success',
        'message' => "Resource '{$resourceCode}' created successfully",
        'resource' => [
            'id' => $resourceId,
            'resource_code' => $resourceCode,
            'name' => $name,
            'type' => $type,
            'ward' => $ward,
            'status' => 'available',
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
