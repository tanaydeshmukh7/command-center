<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Admin: Update resource status
 * POST /api/admin_update_status.php
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

$data = getJsonBody();
$resourceId = isset($data['resource_id']) ? (int) $data['resource_id'] : 0;
$status = isset($data['status']) ? trim((string) $data['status']) : '';

if ($resourceId <= 0 || !$status) {
    jsonResponse(['status' => 'error', 'message' => 'resource_id and status are required'], 400);
}

$validStatuses = ['available', 'occupied', 'maintenance'];
if (!in_array($status, $validStatuses)) {
    jsonResponse(['status' => 'error', 'message' => 'Invalid status. Must be: available, occupied, or maintenance'], 400);
}

try {
    $db = getDB();

    // Get current resource
    $resourceStmt = $db->prepare('SELECT * FROM resources WHERE id = :id LIMIT 1');
    $resourceStmt->execute([':id' => $resourceId]);
    $resource = $resourceStmt->fetch();

    if (!$resource) {
        jsonResponse(['status' => 'error', 'message' => 'Resource not found'], 404);
    }

    // If changing to maintenance, release any active allocation
    if ($status === 'maintenance') {
        $releaseStmt = $db->prepare("
            UPDATE allocations
            SET status = 'released', released_at = NOW()
            WHERE resource_id = :resource_id AND status = 'active'
        ");
        $releaseStmt->execute([':resource_id' => $resourceId]);
    }

    // Update resource status
    $updateStmt = $db->prepare("
        UPDATE resources
        SET status = :status
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':status' => $status,
        ':id' => $resourceId,
    ]);

    jsonResponse([
        'status' => 'success',
        'message' => "Resource status updated to '{$status}'",
        'resource' => [
            'id' => $resource['id'],
            'resource_code' => $resource['resource_code'],
            'status' => $status,
        ],
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
