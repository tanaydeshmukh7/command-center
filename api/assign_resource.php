<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Manual resource assignment API.
 * POST /api/assign_resource.php
 * Body: { patient_id, resource_name }
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

$data = getJsonBody();
$patientId = isset($data['patient_id']) ? (int) $data['patient_id'] : 0;
$resourceName = isset($data['resource_name']) ? trim((string) $data['resource_name']) : '';

if ($patientId <= 0 || $resourceName === '') {
    jsonResponse(['status' => 'error', 'message' => 'patient_id and resource_name are required'], 400);
}

try {
    $db = getDB();
    ensurePatientAssignmentColumns($db);
    $db->beginTransaction();

    $patientStmt = $db->prepare('SELECT * FROM patients WHERE id = :id LIMIT 1 FOR UPDATE');
    $patientStmt->execute([':id' => $patientId]);
    $patient = $patientStmt->fetch();

    if (!$patient) {
        $db->rollBack();
        jsonResponse(['status' => 'error', 'message' => 'Patient not found'], 404);
    }

    $resourceStmt = $db->prepare("
        SELECT *
        FROM resources
        WHERE resource_code = :resource_code OR name = :resource_name
        LIMIT 1
        FOR UPDATE
    ");
    $resourceStmt->execute([
        ':resource_code' => $resourceName,
        ':resource_name' => $resourceName,
    ]);
    $resource = $resourceStmt->fetch();

    if (!$resource) {
        $db->rollBack();
        jsonResponse(['status' => 'error', 'message' => 'Resource not found'], 404);
    }

    $activeResourceStmt = $db->prepare("
        SELECT a.id, p.name AS patient_name
        FROM allocations a
        INNER JOIN patients p ON p.id = a.patient_id
        WHERE a.resource_id = :resource_id
          AND a.status = 'active'
          AND a.patient_id <> :patient_id
        LIMIT 1
    ");
    $activeResourceStmt->execute([
        ':resource_id' => $resource['id'],
        ':patient_id' => $patientId,
    ]);
    $activeResource = $activeResourceStmt->fetch();

    if ($activeResource) {
        $db->rollBack();
        jsonResponse([
            'status' => 'error',
            'message' => "{$resource['resource_code']} is already assigned to {$activeResource['patient_name']}.",
        ], 409);
    }

    $currentAllocationStmt = $db->prepare("
        SELECT a.id, a.resource_id, r.resource_code
        FROM allocations a
        INNER JOIN resources r ON r.id = a.resource_id
        WHERE a.patient_id = :patient_id
          AND a.status = 'active'
        LIMIT 1
        FOR UPDATE
    ");
    $currentAllocationStmt->execute([':patient_id' => $patientId]);
    $currentAllocation = $currentAllocationStmt->fetch();

    if ($resource['status'] !== 'available' && (!$currentAllocation || (int) $currentAllocation['resource_id'] !== (int) $resource['id'])) {
        $db->rollBack();
        jsonResponse([
            'status' => 'error',
            'message' => "{$resource['resource_code']} is not available for assignment.",
        ], 409);
    }

    if ($currentAllocation && (int) $currentAllocation['resource_id'] === (int) $resource['id']) {
        $updatePatientStmt = $db->prepare("
            UPDATE patients
            SET assigned_resource = :assigned_resource,
                ward = :ward,
                status = 'admitted'
            WHERE id = :patient_id
        ");
        $updatePatientStmt->execute([
            ':assigned_resource' => $resource['resource_code'],
            ':ward' => $resource['ward'],
            ':patient_id' => $patientId,
        ]);

        $db->commit();

        jsonResponse([
            'status' => 'success',
            'message' => "{$patient['name']} is already assigned to {$resource['resource_code']}.",
            'patient_id' => $patientId,
            'assigned_resource' => $resource['resource_code'],
            'resource' => [
                'resource_code' => $resource['resource_code'],
                'name' => $resource['name'],
                'ward' => $resource['ward'],
                'type' => $resource['type'],
            ],
        ]);
    }

    if ($currentAllocation) {
        $releaseStmt = $db->prepare("
            UPDATE allocations
            SET status = 'released',
                released_at = NOW()
            WHERE id = :allocation_id
        ");
        $releaseStmt->execute([':allocation_id' => $currentAllocation['id']]);

        $freeResourceStmt = $db->prepare("
            UPDATE resources
            SET status = 'available'
            WHERE id = :resource_id
        ");
        $freeResourceStmt->execute([':resource_id' => $currentAllocation['resource_id']]);
    }

    $insertAllocationStmt = $db->prepare("
        INSERT INTO allocations (patient_id, resource_id, ai_confidence, ai_explanation, ai_rationale, status)
        VALUES (:patient_id, :resource_id, :ai_confidence, :ai_explanation, :ai_rationale, 'active')
    ");
    $insertAllocationStmt->execute([
        ':patient_id' => $patientId,
        ':resource_id' => $resource['id'],
        ':ai_confidence' => 0,
        ':ai_explanation' => 'Manual resource assignment',
        ':ai_rationale' => json_encode([
            'markers' => [[
                'title' => 'Manual Assignment',
                'icon' => 'touch_app',
                'color' => 'cyan',
                'detail' => "Assigned manually to {$resource['resource_code']}.",
            ]],
        ], JSON_UNESCAPED_UNICODE),
    ]);

    $occupyResourceStmt = $db->prepare("
        UPDATE resources
        SET status = 'occupied'
        WHERE id = :resource_id
    ");
    $occupyResourceStmt->execute([':resource_id' => $resource['id']]);

    $updatePatientStmt = $db->prepare("
        UPDATE patients
        SET assigned_resource = :assigned_resource,
            ward = :ward,
            status = 'admitted'
        WHERE id = :patient_id
    ");
    $updatePatientStmt->execute([
        ':assigned_resource' => $resource['resource_code'],
        ':ward' => $resource['ward'],
        ':patient_id' => $patientId,
    ]);

    $db->commit();

    jsonResponse([
        'status' => 'success',
        'message' => "Assigned {$resource['resource_code']} to {$patient['name']}.",
        'patient_id' => $patientId,
        'assigned_resource' => $resource['resource_code'],
        'resource' => [
            'resource_code' => $resource['resource_code'],
            'name' => $resource['name'],
            'ward' => $resource['ward'],
            'type' => $resource['type'],
        ],
    ]);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
