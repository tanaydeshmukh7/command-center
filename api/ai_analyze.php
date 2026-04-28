<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/** AI Analysis API - GET /api/ai_analyze.php?patient_id=1 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_engine.php';
requireAdminAccess();

$request = getRequestData();
$patientId = $request['patient_id'] ?? null;
if (!$patientId) jsonResponse(['status' => 'error', 'message' => 'patient_id required'], 400);

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, a.resource_id, a.ai_confidence, a.ai_rationale, a.ai_explanation,
               r.resource_code, r.name AS resource_name, r.type AS resource_type, r.ward AS resource_ward
        FROM patients p
        LEFT JOIN allocations a ON a.patient_id = p.id AND a.status = 'active'
        LEFT JOIN resources r ON r.id = a.resource_id
        WHERE p.id = :id LIMIT 1
    ");
    $stmt->execute([':id' => $patientId]);
    $patient = $stmt->fetch();

    if (!$patient) jsonResponse(['status' => 'error', 'message' => 'Patient not found'], 404);

    // Run fresh AI analysis
    $ai = analyzePatient($patient);

    // Generate explanation if allocated
    $explanation = null;
    if ($patient['resource_id']) {
        $explanation = explainAllocation(
            $patient,
            ['name' => $patient['resource_name'], 'type' => $patient['resource_type']],
            ['ai_confidence' => $patient['ai_confidence'], 'ai_rationale' => $patient['ai_rationale']]
        );
    }

    jsonResponse([
        'status'      => 'success',
        'patient'     => $patient,
        'analysis'    => $ai,
        'explanation' => $explanation,
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
