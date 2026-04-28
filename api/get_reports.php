<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/** Reports API - GET /api/get_reports.php */
require_once __DIR__ . '/config.php';
requireAdminAccess();

try {
    $db = getDB();

    $totalDecisions = $db->query("SELECT COUNT(*) FROM allocations")->fetchColumn();
    $activeAlerts = $db->query("SELECT COUNT(*) FROM patients WHERE severity = 'critical' AND status != 'discharged'")->fetchColumn();

    $totalAlloc = $db->query("SELECT COUNT(*) FROM allocations WHERE status = 'active'")->fetchColumn();
    $efficiency = $totalAlloc > 0 ? round(($db->query("SELECT COUNT(*) FROM allocations WHERE ai_confidence > 85 AND status='active'")->fetchColumn() / $totalAlloc) * 100, 1) : 0;

    // Ward breakdown
    $wards = $db->query("
        SELECT r.ward, COUNT(*) as count 
        FROM allocations a 
        JOIN resources r ON r.id = a.resource_id 
        WHERE a.status = 'active' 
        GROUP BY r.ward 
        ORDER BY count DESC
    ")->fetchAll();

    jsonResponse([
        'status' => 'success',
        'reports' => [
            'total_decisions' => (int)$totalDecisions,
            'resource_efficiency' => $efficiency,
            'active_alerts' => (int)$activeAlerts,
            'ward_breakdown' => $wards,
        ],
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
