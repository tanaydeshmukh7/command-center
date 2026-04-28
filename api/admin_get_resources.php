<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Admin: Get all resources with detailed information
 * GET|POST /api/admin_get_resources.php
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    jsonResponse(['status' => 'error', 'message' => 'GET or POST method required'], 405);
}

try {
    $db = getDB();

    $resources = $db->query("
        SELECT
            r.id,
            r.resource_code,
            r.name,
            r.type,
            r.ward,
            r.status,
            r.created_at,
            a.patient_id,
            p.name AS patient_name,
            p.patient_code
        FROM resources r
        LEFT JOIN allocations a ON a.resource_id = r.id AND a.status = 'active'
        LEFT JOIN patients p ON p.id = a.patient_id
        ORDER BY r.type, r.resource_code
    ")->fetchAll();

    // Count statistics
    $stats = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN r.type IN ('icu_bed', 'or_room', 'ventilator') THEN 1 ELSE 0 END) as critical_resources,
            SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_count,
            SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied_count,
            SUM(CASE WHEN r.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count,
            SUM(CASE WHEN r.type = 'icu_bed' THEN 1 ELSE 0 END) as icu_beds,
            SUM(CASE WHEN r.type = 'icu_bed' AND r.status = 'available' THEN 1 ELSE 0 END) as icu_available,
            SUM(CASE WHEN r.type = 'general_bed' THEN 1 ELSE 0 END) as general_beds,
            SUM(CASE WHEN r.type = 'general_bed' AND r.status = 'available' THEN 1 ELSE 0 END) as general_available
        FROM resources r
    ")->fetch();

    jsonResponse([
        'status' => 'success',
        'count' => count($resources),
        'resources' => $resources,
        'stats' => $stats,
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
