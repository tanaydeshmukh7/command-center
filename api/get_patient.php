<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Get Single Patient with AI Analysis
 * GET /api/get_patient.php?id=1  OR  POST /api/get_patient.php
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_engine.php';
requireAdminAccess();

try {
    $db = getDB();
    $request = getRequestData();

    if (!empty($request['id'])) {
        $where = 'p.id = :val';
        $val   = (int)$request['id'];
    } elseif (!empty($request['code'])) {
        $where = 'p.patient_code = :val';
        $val   = $request['code'];
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Provide ?id= or ?code= parameter'], 400);
    }

    $stmt = $db->prepare("
        SELECT p.*, 
               a.id AS allocation_id, a.resource_id, a.ai_confidence, a.ai_explanation, a.ai_rationale, a.status AS allocation_status,
               r.resource_code, r.name AS resource_name, r.type AS resource_type, r.ward AS resource_ward
        FROM patients p
        LEFT JOIN allocations a ON a.patient_id = p.id AND a.status = 'active'
        LEFT JOIN resources r ON r.id = a.resource_id
        WHERE $where
        LIMIT 1
    ");
    $stmt->execute([':val' => $val]);
    $patient = $stmt->fetch();

    if (!$patient) {
        jsonResponse(['status' => 'error', 'message' => 'Patient not found'], 404);
    }

    // Get AI analysis history
    $stmt2 = $db->prepare('SELECT * FROM ai_analysis WHERE patient_id = :pid ORDER BY created_at DESC');
    $stmt2->execute([':pid' => $patient['id']]);
    $analyses = $stmt2->fetchAll();

    // Generate live AI explanation
    $aiExplanation = null;
    if ($patient['resource_id']) {
        $aiExplanation = explainAllocation(
            $patient,
            ['name' => $patient['resource_name'], 'type' => $patient['resource_type']],
            ['ai_confidence' => $patient['ai_confidence'], 'ai_rationale' => $patient['ai_rationale']]
        );
    }

    jsonResponse([
        'status'         => 'success',
        'patient'        => $patient,
        'ai_explanation' => $aiExplanation,
        'analysis_history' => $analyses,
    ]);

} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
