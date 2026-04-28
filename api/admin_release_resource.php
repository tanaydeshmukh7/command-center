<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Admin: Release resource from patient (unassign)
 * POST /api/admin_release_resource.php
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

    // Get resource
    $resourceStmt = $db->prepare('SELECT * FROM resources WHERE id = :id LIMIT 1 FOR UPDATE');
    $resourceStmt->execute([':id' => $resourceId]);
    $resource = $resourceStmt->fetch();

    if (!$resource) {
        $db->rollBack();
        jsonResponse(['status' => 'error', 'message' => 'Resource not found'], 404);
    }

    // Get active allocation if any
    $allocStmt = $db->prepare("
        SELECT a.id, a.patient_id, p.name, p.assigned_resource
        FROM allocations a
        INNER JOIN patients p ON p.id = a.patient_id
        WHERE a.resource_id = :resource_id AND a.status = 'active'
        LIMIT 1
        FOR UPDATE
    ");
    $allocStmt->execute([':resource_id' => $resourceId]);
    $allocation = $allocStmt->fetch();

    if (!$allocation) {
        $db->rollBack();
        jsonResponse(['status' => 'error', 'message' => 'No active allocation found for this resource'], 404);
    }

    // Release the allocation
    $releaseStmt = $db->prepare("
        UPDATE allocations
        SET status = 'released', released_at = NOW()
        WHERE id = :id
    ");
    $releaseStmt->execute([':id' => $allocation['id']]);

    // Set resource back to available
    $updateResourceStmt = $db->prepare("
        UPDATE resources
        SET status = 'available'
        WHERE id = :id
    ");
    $updateResourceStmt->execute([':id' => $resourceId]);

    // Clear patient's assigned_resource
    $updatePatientStmt = $db->prepare("
        UPDATE patients
        SET assigned_resource = NULL, status = 'waiting'
        WHERE id = :patient_id
    ");
    $updatePatientStmt->execute([':patient_id' => $allocation['patient_id']]);

    $db->commit();

    jsonResponse([
        'status' => 'success',
        'message' => "Released {$resource['resource_code']} from {$allocation['name']}",
        'resource' => [
            'id' => $resourceId,
            'resource_code' => $resource['resource_code'],
            'status' => 'available',
        ],
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
