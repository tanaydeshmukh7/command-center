<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/** Bias Stats API - GET /api/get_bias_stats.php */
require_once __DIR__ . '/config.php';
requireAdminAccess();

try {
    $db = getDB();

    // Gender allocation distribution
    $gender = $db->query("
        SELECT p.gender, COUNT(*) as count 
        FROM allocations a 
        JOIN patients p ON p.id = a.patient_id 
        WHERE a.status = 'active' 
        GROUP BY p.gender
    ")->fetchAll();

    $total = array_sum(array_column($gender, 'count'));
    $genderStats = [];
    foreach ($gender as $g) {
        $genderStats[$g['gender']] = $total > 0 ? round(($g['count'] / $total) * 100) : 0;
    }

    // Age group distribution
    $ageGroups = $db->query("
        SELECT 
            CASE 
                WHEN p.age BETWEEN 18 AND 35 THEN '18-35'
                WHEN p.age BETWEEN 36 AND 55 THEN '36-55'
                WHEN p.age BETWEEN 56 AND 75 THEN '56-75'
                ELSE '76+' 
            END as age_group,
            COUNT(*) as count,
            AVG(a.ai_confidence) as avg_confidence
        FROM allocations a 
        JOIN patients p ON p.id = a.patient_id 
        WHERE a.status = 'active' 
        GROUP BY age_group
    ")->fetchAll();

    // Bias logs
    $logs = $db->query("SELECT * FROM bias_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();

    // Fairness score (simple: 100 minus gender deviation minus age deviation)
    $maleP = $genderStats['M'] ?? 50;
    $femaleP = $genderStats['F'] ?? 50;
    $genderDev = abs($maleP - $femaleP);
    $fairness = max(0, min(100, 100 - $genderDev - 5));

    jsonResponse([
        'status'       => 'success',
        'gender_stats' => $genderStats,
        'age_groups'   => $ageGroups,
        'fairness_score' => $fairness,
        'bias_logs'    => $logs,
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
