<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Analyze Patient API (Live Gemini AI Integration)
 * POST /api/analyze_patient.php
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_engine.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$data = is_array($data) ? $data : [];

$age = isset($data['age']) ? (int) $data['age'] : 30;
$gender = isset($data['gender']) ? trim((string) $data['gender']) : '';
$symptoms = isset($data['symptoms']) && trim((string) $data['symptoms']) !== ''
    ? trim((string) $data['symptoms'])
    : 'None reported';
$oxygen = isset($data['oxygen_level']) && $data['oxygen_level'] !== ''
    ? (float) $data['oxygen_level']
    : 98.0;
// 1. Rule-based deterministic allocation
$patientForLogic = ['age' => $age, 'oxygen_level' => $oxygen, 'symptoms' => $symptoms];
$severityData = getSeverity($patientForLogic);
$allocation = allocate($patientForLogic);

// 2. Map string allocation to DB resource type
$resourceType = 'general_bed';
if ($allocation === 'ICU Bed') {
    $resourceType = 'icu_bed';
}

$dbSeverity = 'stable';
if ($severityData['label'] === 'Critical' || $severityData['label'] === 'High') {
    $dbSeverity = 'critical';
} elseif ($severityData['label'] === 'Moderate') {
    $dbSeverity = 'moderate';
}

// 3. Gemini AI call for explanation
$prompt = "
You are a clinical decision support AI.

Analyze the patient and explain the condition, treatment approach, and resource allocation.

Patient:
- Age: $age
- Gender: $gender
- Symptoms: $symptoms
- Oxygen Level: $oxygen%
- Assigned Resource: $allocation

Clinical Rules:
- SpO2 < 92% → respiratory risk
- SpO2 ≥ 96% → stable
- Age > 60 → moderate risk

Instructions:
1. Identify possible condition (not final diagnosis)
2. Explain what symptoms indicate
3. Suggest how the condition can be managed (cure/treatment)
4. Mention general medicine types (no dosage)
5. Justify why $allocation is appropriate
6. Keep answer under 120 words

IMPORTANT:
- Do NOT give prescriptions or dosage
- Do NOT claim certainty
- Use medical reasoning (cause → effect)
- Use simple clinical language

Output format:

Possible Condition:
<short answer>

Symptom Analysis:
<what symptoms mean>

Suggested Care:
<how to treat / manage>

Medication Type:
<example: antipyretics, antibiotics, oxygen therapy>

Resource Justification:
<why this allocation is correct>
";

$apiKey = defined('GEMINI_API_KEY') && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE'
    ? GEMINI_API_KEY
    : '';

$riskLevel = 'Low';
if ($oxygen < 92) {
    $riskLevel = 'High';
} elseif ($age > 60 || $oxygen < 96) {
    $riskLevel = 'Moderate';
}

$fallbackReasoning = $oxygen < 92
    ? "Oxygen level of {$oxygen}% indicates respiratory compromise, which raises the risk of rapid deterioration."
    : ($age > 60
        ? "Oxygen level of {$oxygen}% is not severely low, but age above 60 increases the need for closer monitoring."
        : "Oxygen level of {$oxygen}% suggests stable oxygenation, so immediate respiratory escalation is less likely.");
$fallbackConclusion = "The assigned resource {$allocation} matches the current risk profile and monitoring needs.";
$fallbackExplanation = "Reasoning: {$fallbackReasoning}\n\nRisk Level:\n{$riskLevel}\n\nConclusion: {$fallbackConclusion}";

$aiExplanation = '';

if (empty($apiKey)) {
    $aiExplanation = $fallbackExplanation;
} else {
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=$apiKey";

    $postData = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt],
                ],
            ],
        ],
    ];

    $response = false;
    $error = '';
    $httpCode = 0;
    $encodedBody = json_encode($postData, JSON_UNESCAPED_UNICODE);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedBody);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $encodedBody,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = 'HTTP request failed and cURL is unavailable.';
        }

        $responseHeaders = function_exists('http_get_last_response_headers')
            ? http_get_last_response_headers()
            : [];

        if (!empty($responseHeaders) && preg_match('/\s(\d{3})\s/', $responseHeaders[0], $matches)) {
            $httpCode = (int) $matches[1];
        }
    }

    if ($error || $httpCode !== 200) {
        file_put_contents(__DIR__ . '/debug.txt', $error !== '' ? $error : "Gemini request failed with HTTP $httpCode");
        $aiExplanation = $fallbackExplanation;
    } else {
        $result = json_decode($response, true);
        $explanation = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No AI response';
        $explanation = trim(preg_replace("/\r\n?/", "\n", $explanation));

        file_put_contents(__DIR__ . '/debug.txt', $explanation);

        $aiExplanation = ($explanation !== '' && $explanation !== 'No AI response')
            ? $explanation
            : $fallbackExplanation;
    }
}

// 4. Return output as proper JSON
jsonResponse([
    'status' => 'success',
    'explanation' => $aiExplanation,
]);
