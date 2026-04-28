<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Allocation listing API.
 * GET|POST /api/get_allocations.php
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    jsonResponse(['status' => 'error', 'message' => 'GET or POST method required'], 405);
}

try {
    $db = getDB();
    ensurePatientAssignmentColumns($db);

    // Backfill legacy rows so manual and smart allocations share the same patient field.
    $db->exec("
        UPDATE patients p
        INNER JOIN allocations a ON a.patient_id = p.id AND a.status = 'active'
        INNER JOIN resources r ON r.id = a.resource_id
        SET p.assigned_resource = r.resource_code,
            p.ward = COALESCE(r.ward, p.ward)
        WHERE p.assigned_resource IS NULL OR p.assigned_resource = ''
    ");

    $allocations = $db->query("
        SELECT
            p.id AS patient_id,
            p.patient_code,
            p.name AS patient_name,
            p.severity,
            p.status AS patient_status,
            p.allocation AS recommended_resource,
            p.assigned_resource,
            r.resource_code,
            r.name AS resource_name,
            r.ward,
            r.type AS resource_type,
            r.status AS resource_status,
            a.ai_confidence
        FROM patients p
        LEFT JOIN resources r ON r.resource_code = p.assigned_resource
        LEFT JOIN allocations a ON a.patient_id = p.id AND a.status = 'active' AND (r.id IS NULL OR a.resource_id = r.id)
        WHERE p.assigned_resource IS NOT NULL
          AND p.assigned_resource <> ''
        ORDER BY p.updated_at DESC, p.created_at DESC
    ")->fetchAll();

    $unassignedPatients = $db->query("
        SELECT
            id AS patient_id,
            patient_code,
            name AS patient_name,
            severity,
            status AS patient_status,
            allocation AS recommended_resource
        FROM patients
        WHERE (assigned_resource IS NULL OR assigned_resource = '')
          AND status <> 'discharged'
        ORDER BY created_at DESC
        LIMIT 6
    ")->fetchAll();

    jsonResponse([
        'status' => 'success',
        'count' => count($allocations),
        'allocations' => $allocations,
        'unassigned_patients' => $unassignedPatients,
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
