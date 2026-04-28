<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/** Get Resources API - GET /api/get_resources.php */
require_once __DIR__ . '/config.php';
requireAdminAccess();

try {
    $db = getDB();
    $resources = $db->query("
        SELECT r.*, 
               a.patient_id, a.ai_confidence,
               p.name AS patient_name, p.patient_code, p.severity
        FROM resources r
        LEFT JOIN allocations a ON a.resource_id = r.id AND a.status = 'active'
        LEFT JOIN patients p ON p.id = a.patient_id
        ORDER BY r.type, r.resource_code
    ")->fetchAll();

    $summary = $db->query("
        SELECT type, 
               COUNT(*) as total, 
               SUM(status='available') as available, 
               SUM(status='occupied') as occupied
        FROM resources GROUP BY type
    ")->fetchAll();

    jsonResponse(['status' => 'success', 'resources' => $resources, 'summary' => $summary]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
