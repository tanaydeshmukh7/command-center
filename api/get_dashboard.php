<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
require_once __DIR__ . '/config.php';
requireAdminAccess();

try {
    $db = getDB();
    $totalPatients = $db->query("SELECT COUNT(*) FROM patients WHERE status != 'discharged'")->fetchColumn();
    $icuTotal     = $db->query("SELECT COUNT(*) FROM resources WHERE type = 'icu_bed'")->fetchColumn();
    $icuAvailable = $db->query("SELECT COUNT(*) FROM resources WHERE type = 'icu_bed' AND status = 'available'")->fetchColumn();
    $criticalCount = $db->query("SELECT COUNT(*) FROM patients WHERE severity = 'critical' AND status != 'discharged'")->fetchColumn();
    $totalBeds    = $db->query("SELECT COUNT(*) FROM resources WHERE type IN ('icu_bed','general_bed')")->fetchColumn();
    $occupiedBeds = $db->query("SELECT COUNT(*) FROM resources WHERE type IN ('icu_bed','general_bed') AND status = 'occupied'")->fetchColumn();
    $utilization  = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0;
    $icuCapPct = $icuTotal > 0 ? round(((($icuTotal - $icuAvailable) / $icuTotal) * 100)) : 0;

    jsonResponse([
        'status' => 'success',
        'stats'  => [
            'total_patients'   => (int)$totalPatients,
            'icu_available'    => (int)$icuAvailable,
            'icu_total'        => (int)$icuTotal,
            'icu_capacity_pct' => $icuCapPct,
            'critical_count'   => (int)$criticalCount,
            'bed_utilization'  => $utilization,
        ],
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
