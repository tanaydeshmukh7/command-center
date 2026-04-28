<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Add Patient API
 * POST /api/add_patient.php
 * Body: { name, age, gender, symptoms, oxygen_level, severity }
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_engine.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

$data = getJsonBody();

// Validate required fields
$required = ['name', 'age'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        jsonResponse(['status' => 'error', 'message' => "Missing required field: $field"], 400);
    }
}

try {
    $db = getDB();
    ensurePatientAssignmentColumns($db);
    $db->beginTransaction();

    // Generate unique patient code
    $code = strtoupper(substr(md5(uniqid()), 0, 3)) . '-' . strtoupper(substr(md5(time()), 0, 3));

    $age = (int)$data['age'];
    $oxygen = isset($data['oxygen_level']) && $data['oxygen_level'] !== '' ? (float)$data['oxygen_level'] : 98.0;
    $patientStatus = inferPatientStatus($data);

    // Run AI severity analysis to enrich the automatic allocation decision.
    $ai = analyzePatient($data);
    $severityForDb = $patientStatus === 'CRITICAL' ? 'critical' : 'stable';
    $allocationRecommendation = $patientStatus === 'CRITICAL' ? 'ICU BED' : 'GENERAL BED';

    $stmt = $db->prepare('INSERT INTO patients (patient_code, name, age, gender, symptoms, oxygen_level, severity, allocation, severity_score, status) 
                          VALUES (:code, :name, :age, :gender, :symptoms, :oxygen, :severity, :allocation, :score, :status)');

    $stmt->execute([
        ':code'       => $code,
        ':name'       => trim($data['name']),
        ':age'        => $age,
        ':gender'     => $data['gender'] ?? 'O',
        ':symptoms'   => $data['symptoms'] ?? null,
        ':oxygen'     => $oxygen,
        ':severity'   => $severityForDb,
        ':allocation' => $allocationRecommendation,
        ':score'      => $ai['severity_score'],
        ':status'     => 'waiting',
    ]);

    $patientId = $db->lastInsertId();

    // Store AI analysis
    $stmt2 = $db->prepare('INSERT INTO ai_analysis (patient_id, severity_score, recommended_resource, confidence, rationale)
                           VALUES (:pid, :score, :resource, :conf, :rationale)');
    $stmt2->execute([
        ':pid'       => $patientId,
        ':score'     => $ai['severity_score'],
        ':resource'  => $ai['recommended_resource'],
        ':conf'      => $ai['confidence'],
        ':rationale' => json_encode($ai['rationale']),
    ]);

    $selectResource = function (string $type) use ($db) {
        $stmt = $db->prepare("
            SELECT *
            FROM resources
            WHERE type = :type AND status = 'available'
            ORDER BY id ASC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([':type' => $type]);
        return $stmt->fetch();
    };

    $resource = null;
    $icuRequired = 0;
    $resourceAssigned = null;

    if ($patientStatus === 'CRITICAL') {
        $resource = $selectResource('icu_bed');
        $resourceAssigned = 'ICU BED';

        if (!$resource) {
            $resource = $selectResource('general_bed');
            $resourceAssigned = $resource ? 'GENERAL BED' : null;
            $icuRequired = $resource ? 1 : 0;
        }
    } else {
        $resource = $selectResource('general_bed');
        $resourceAssigned = $resource ? 'GENERAL BED' : null;
    }

    if (!$resource) {
        $db->rollBack();
        jsonResponse([
            'success' => false,
            'message' => 'No resources available',
        ], 503);
    }

    $explanation = $ai['rationale']['markers'][0]['detail'] ?? 'Auto-assigned based on patient severity.';
    if ($icuRequired) {
        $explanation .= ' ICU required, but patient was placed in a general bed because no ICU bed was available.';
    }

    $allocationStmt = $db->prepare("
        INSERT INTO allocations (patient_id, resource_id, ai_confidence, ai_explanation, ai_rationale, status)
        VALUES (:patient_id, :resource_id, :confidence, :explanation, :rationale, 'active')
    ");
    $allocationStmt->execute([
        ':patient_id' => $patientId,
        ':resource_id' => $resource['id'],
        ':confidence' => $ai['confidence'],
        ':explanation' => $explanation,
        ':rationale' => json_encode($ai['rationale']),
    ]);

    $db->prepare("UPDATE resources SET status = 'occupied' WHERE id = :id")
        ->execute([':id' => $resource['id']]);

    $allocationTime = date('Y-m-d H:i:s');
    $db->prepare("
        UPDATE patients
        SET assigned_resource = :assigned_resource,
            allocation = :allocation,
            ward = :ward,
            status = 'admitted',
            allocation_time = :allocation_time,
            icu_required = :icu_required
        WHERE id = :id
    ")->execute([
        ':assigned_resource' => $resource['resource_code'],
        ':allocation' => $resourceAssigned,
        ':ward' => $resource['ward'],
        ':allocation_time' => $allocationTime,
        ':icu_required' => $icuRequired,
        ':id' => $patientId,
    ]);

    $db->commit();

    jsonResponse([
        'success' => true,
        'status' => 'success',
        'message' => $icuRequired
            ? 'Resource assigned automatically based on patient condition. ICU bed required, general bed assigned as fallback.'
            : 'Resource assigned automatically based on patient condition.',
        'patient_id' => $patientId,
        'patient_code' => $code,
        'patient_status' => $patientStatus,
        'resource_assigned' => $resourceAssigned,
        'assigned_resource' => $resource['resource_code'],
        'icu_required' => (bool) $icuRequired,
        'allocation_time' => $allocationTime,
        'ai_analysis' => $ai,
    ]);

} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
