<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/** Allocate Resource API - POST /api/allocate_resource.php */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_engine.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST required'], 405);
}

$data = getJsonBody();
$patientId = $data['patient_id'] ?? null;

if (!$patientId) {
    jsonResponse(['status' => 'error', 'message' => 'patient_id required'], 400);
}

try {
    $db = getDB();
    ensurePatientAssignmentColumns($db);
    $patient = $db->prepare('SELECT * FROM patients WHERE id = :id');
    $patient->execute([':id' => $patientId]);
    $patient = $patient->fetch();

    if (!$patient) jsonResponse(['status' => 'error', 'message' => 'Patient not found'], 404);

    // Run AI analysis
    $ai = analyzePatient($patient);

    // Find best available resource
    $res = $db->prepare("SELECT * FROM resources WHERE type = :type AND status = 'available' LIMIT 1");
    $res->execute([':type' => $ai['resource_type']]);
    $resource = $res->fetch();

    if (!$resource) {
        // Fallback: find any available resource
        $res2 = $db->prepare("SELECT * FROM resources WHERE status = 'available' ORDER BY FIELD(type,'icu_bed','monitor','general_bed','discharge_lounge') LIMIT 1");
        $res2->execute();
        $resource = $res2->fetch();
    }

    if (!$resource) {
        jsonResponse(['status' => 'error', 'message' => 'No available resources'], 503);
    }

    $rationale = json_encode($ai['rationale']);
    $explanation = "Patient {$patient['name']} allocated to {$resource['name']}: {$ai['rationale']['markers'][0]['detail']}";

    // Release existing allocation
    $db->prepare("UPDATE allocations SET status='released', released_at=NOW() WHERE patient_id=:pid AND status='active'")
       ->execute([':pid' => $patientId]);

    // Create new allocation
    $ins = $db->prepare("INSERT INTO allocations (patient_id, resource_id, ai_confidence, ai_explanation, ai_rationale, status) VALUES (:pid,:rid,:conf,:expl,:rat,'active')");
    $ins->execute([':pid' => $patientId, ':rid' => $resource['id'], ':conf' => $ai['confidence'], ':expl' => $explanation, ':rat' => $rationale]);

    // Update resource status
    $db->prepare("UPDATE resources SET status='occupied' WHERE id=:rid")->execute([':rid' => $resource['id']]);

    // Update patient
    $db->prepare("UPDATE patients SET severity=:sev, severity_score=:score, ward=:ward, assigned_resource=:assigned_resource, status='admitted' WHERE id=:id")
       ->execute([
           ':sev' => $ai['severity'],
           ':score' => $ai['severity_score'],
           ':ward' => $resource['ward'],
           ':assigned_resource' => $resource['resource_code'],
           ':id' => $patientId,
       ]);

    jsonResponse([
        'status'      => 'success',
        'message'     => "Allocated {$resource['name']} to {$patient['name']}",
        'allocation'  => ['resource' => $resource, 'ai' => $ai],
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
