<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Get Patients API
 * GET /api/get_patients.php
 * Optional query params: ?status=admitted&severity=critical&ward=ICU
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

try {
    $db = getDB();
    ensurePatientAssignmentColumns($db);

    $sql = "
        SELECT
            p.id,
            p.patient_code,
            p.name,
            p.age,
            p.gender,
            p.symptoms,
            p.oxygen_level,
            p.severity,
            p.severity_score,
            p.allocation,
            p.assigned_resource,
            p.ward,
            p.status,
            p.created_at,
            a.ai_confidence,
            a.ai_rationale,
            r.name AS resource_name,
            r.ward AS resource_ward
        FROM patients p
        LEFT JOIN allocations a ON a.patient_id = p.id AND a.status = 'active'
        LEFT JOIN resources r ON r.id = a.resource_id
        ORDER BY
            FIELD(p.severity, 'critical', 'moderate', 'stable'),
            p.severity_score DESC,
            p.created_at DESC
    ";
    $stmt = $db->query($sql);
    $patients = $stmt->fetchAll();

    jsonResponse([
        'status'   => 'success',
        'count'    => count($patients),
        'patients' => $patients,
    ]);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
