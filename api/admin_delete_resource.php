<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Admin: Delete resource
 * POST /api/admin_delete_resource.php
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

$data = getJsonBody();
$resourceId = isset($data['resource_id']) ? (int) $data['resource_id'] : 0;

if ($resourceId <= 0) {
    jsonResponse(['status' => 'error', 'message' => 'resource_id is required'], 400);
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Get resource info
    $resourceStmt = $db->prepare('SELECT * FROM resources WHERE id = :id LIMIT 1 FOR UPDATE');
    $resourceStmt->execute([':id' => $resourceId]);
    $resource = $resourceStmt->fetch();

    if (!$resource) {
        $db->rollBack();
        jsonResponse(['status' => 'error', 'message' => 'Resource not found'], 404);
    }

    // Check if resource is currently occupied
    $allocStmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM allocations
        WHERE resource_id = :resource_id AND status = 'active'
    ");
    $allocStmt->execute([':resource_id' => $resourceId]);
    $allocResult = $allocStmt->fetch();

    if ($allocResult['count'] > 0) {
        $db->rollBack();
        jsonResponse([
            'status' => 'error',
            'message' => 'Cannot delete resource that is currently assigned to a patient. Release it first.',
        ], 409);
    }

    // Release all allocations for this resource (should be none, but just in case)
    $releaseStmt = $db->prepare("
        UPDATE allocations
        SET status = 'released', released_at = NOW()
        WHERE resource_id = :resource_id
    ");
    $releaseStmt->execute([':resource_id' => $resourceId]);

    // Delete resource
    $deleteStmt = $db->prepare('DELETE FROM resources WHERE id = :id');
    $deleteStmt->execute([':id' => $resourceId]);

    $db->commit();

    jsonResponse([
        'status' => 'success',
        'message' => "Resource '{$resource['resource_code']}' deleted successfully",
        'resource_id' => $resourceId,
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
