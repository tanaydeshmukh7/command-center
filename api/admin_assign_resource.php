<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Admin: Assign resource to patient (admin override)
 * POST /api/admin_assign_resource.php
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

$data = getJsonBody();
$resourceId = isset($data['resource_id']) ? (int) $data['resource_id'] : 0;
$patientId = isset($data['patient_id']) ? (int) $data['patient_id'] : 0;

if ($resourceId <= 0 || $patientId <= 0) {
    jsonResponse(['status' => 'error', 'message' => 'resource_id and patient_id are required'], 400);
}

try {
    $db = getDB();
    $db->beginTransaction();

    // Verify resource exists
    $resourceStmt = $db->prepare('SELECT * FROM resources WHERE id = :id LIMIT 1 FOR UPDATE');
    $resourceStmt->execute([':id' => $resourceId]);
    $resource = $resourceStmt->fetch();

    if (!$resource) {
        $db->rollBack();
        jsonResponse(['status' => 'error', 'message' => 'Resource not found'], 404);
    }

    // Verify patient exists
    $patientStmt = $db->prepare('SELECT * FROM patients WHERE id = :id LIMIT 1 FOR UPDATE');
    $patientStmt->execute([':id' => $patientId]);
    $patient = $patientStmt->fetch();

    if (!$patient) {
        $db->rollBack();
        jsonResponse(['status' => 'error', 'message' => 'Patient not found'], 404);
    }

    // Check for existing active allocations on this resource
    $existingAllocStmt = $db->prepare("
        SELECT a.id, p.name
        FROM allocations a
        INNER JOIN patients p ON p.id = a.patient_id
        WHERE a.resource_id = :resource_id AND a.status = 'active'
        LIMIT 1
    ");
    $existingAllocStmt->execute([':resource_id' => $resourceId]);
    $existingAlloc = $existingAllocStmt->fetch();

    if ($existingAlloc) {
        $db->rollBack();
        jsonResponse([
            'status' => 'error',
            'message' => "Resource is already assigned to {$existingAlloc['name']}",
        ], 409);
    }

    // Release any current allocation for this patient
    $releaseStmt = $db->prepare("
        UPDATE allocations
        SET status = 'released', released_at = NOW()
        WHERE patient_id = :patient_id AND status = 'active'
    ");
    $releaseStmt->execute([':patient_id' => $patientId]);

    // Create new allocation
    $allocStmt = $db->prepare("
        INSERT INTO allocations (patient_id, resource_id, ai_confidence, ai_explanation, status)
        VALUES (:patient_id, :resource_id, 0, 'Admin override assignment', 'active')
    ");
    $allocStmt->execute([
        ':patient_id' => $patientId,
        ':resource_id' => $resourceId,
    ]);

    // Update resource status to occupied
    $updateStmt = $db->prepare("
        UPDATE resources
        SET status = 'occupied'
        WHERE id = :id
    ");
    $updateStmt->execute([':id' => $resourceId]);

    // Update patient's assigned_resource
    $updatePatientStmt = $db->prepare("
        UPDATE patients
        SET assigned_resource = :resource_code, status = 'admitted'
        WHERE id = :patient_id
    ");
    $updatePatientStmt->execute([
        ':resource_code' => $resource['resource_code'],
        ':patient_id' => $patientId,
    ]);

    $db->commit();

    jsonResponse([
        'status' => 'success',
        'message' => "Assigned {$resource['resource_code']} to {$patient['name']}",
        'allocation' => [
            'resource_id' => $resourceId,
            'patient_id' => $patientId,
            'resource_code' => $resource['resource_code'],
            'patient_name' => $patient['name'],
        ],
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
