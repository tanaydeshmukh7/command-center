<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Bias Check API (Fairness Analysis)
 * POST /api/bias_check.php
 *
 * Analyzes ICU allocation rates by gender and flags potential bias only
 * when the dataset is large enough and the gap exceeds a meaningful threshold.
 */
require_once __DIR__ . '/config.php';
requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'POST method required'], 405);
}

function buildFallbackBiasPayload(float $male, float $female, float $biasScore, bool $isBiased, bool $hasData, int $totalIcu): array
{
    if (!$hasData) {
        return [
            'male' => $male,
            'female' => $female,
            'bias_score' => $biasScore,
            'analysis' => 'No ICU allocation data available. Add patients with ICU allocations to enable fairness monitoring.',
            'suggestion' => 'Ensure patients are being admitted and allocated resources through the system so the bias checker has data to analyze.',
        ];
    }

    // Small sample — can't draw conclusions
    if ($totalIcu < 6) {
        return [
            'male' => $male,
            'female' => $female,
            'bias_score' => $biasScore,
            'analysis' => "Only {$totalIcu} ICU allocation(s) recorded so far (Male: " . round($male, 1) . "%, Female: " . round($female, 1) . "%). With a dataset this small, percentage differences are not statistically meaningful. The allocation engine is operating normally and no systemic bias can be inferred from current volumes.",
            'suggestion' => 'Continue monitoring as more patients are admitted. A minimum of 6 ICU allocations is recommended before drawing fairness conclusions. The system will re-evaluate automatically.',
        ];
    }

    $analysis = $isBiased
        ? 'The ICU allocation gap is large enough to justify a manual fairness review. This does not prove discrimination by itself, but it does indicate that gender-linked allocation patterns should be audited against severity, oxygen levels, and triage criteria.'
        : 'Male and female ICU allocation rates are within acceptable range. The current snapshot does not show a strong gender skew. The allocation engine is distributing resources fairly based on clinical severity scores, oxygen levels, and symptom-based triage. Continue monitoring as patient mix and case severity change over time.';

    $suggestion = $isBiased
        ? 'Compare ICU decisions across genders after controlling for severity score, oxygen level, and symptoms, then flag any unexplained differences for human review.'
        : 'Keep logging ICU decisions and review the fairness dashboard regularly so any future demographic drift is detected early. No corrective action is needed at this time.';

    return [
        'male' => $male,
        'female' => $female,
        'bias_score' => $biasScore,
        'analysis' => $analysis,
        'suggestion' => $suggestion,
    ];
}

function parseGeminiBiasPayload(?string $text): ?array
{
    if (!is_string($text)) {
        return null;
    }

    $normalized = trim($text);
    if ($normalized === '') {
        return null;
    }

    $normalized = preg_replace('/^```json\s*|\s*```$/i', '', $normalized);

    $decoded = json_decode($normalized, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    if (preg_match('/\{.*\}/s', $normalized, $matches)) {
        $decoded = json_decode($matches[0], true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

function requestGeminiContent(string $url, array $postData): array
{
    $encodedBody = json_encode($postData, JSON_UNESCAPED_UNICODE);
    $response = false;
    $error = '';
    $httpCode = 0;

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

    return [
        'response' => $response,
        'error' => $error,
        'http_code' => $httpCode,
    ];
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $data = is_array($data) ? $data : [];

    $resourceType = isset($data['resource_type']) && trim((string) $data['resource_type']) !== ''
        ? trim((string) $data['resource_type'])
        : 'icu_bed';

    // Higher threshold — 25% gap required, and minimum sample size of 6
    $biasThreshold = isset($data['bias_threshold']) && is_numeric($data['bias_threshold'])
        ? (float) $data['bias_threshold']
        : 25.0;
    $minSampleSize = 6;

    $db = getDB();

    $stmt = $db->prepare("
        SELECT
            p.gender,
            COUNT(DISTINCT a.patient_id) AS icu_allocations
        FROM allocations a
        INNER JOIN patients p ON p.id = a.patient_id
        INNER JOIN resources r ON r.id = a.resource_id
        WHERE a.status = 'active'
          AND r.type = :resource_type
          AND p.gender IN ('M', 'F')
        GROUP BY p.gender
    ");
    $stmt->execute([':resource_type' => $resourceType]);
    $rows = $stmt->fetchAll();

    $maleIcu = 0;
    $femaleIcu = 0;

    foreach ($rows as $row) {
        $gender = $row['gender'];
        $icuAllocations = (int) $row['icu_allocations'];

        if ($gender === 'M') {
            $maleIcu = $icuAllocations;
        } elseif ($gender === 'F') {
            $femaleIcu = $icuAllocations;
        }
    }

    $totalIcu = $maleIcu + $femaleIcu;
    $malePercentage = $totalIcu > 0 ? round(($maleIcu / $totalIcu) * 100, 2) : 0.0;
    $femalePercentage = $totalIcu > 0 ? round(($femaleIcu / $totalIcu) * 100, 2) : 0.0;
    $biasScore = round(abs($malePercentage - $femalePercentage), 2);
    $hasData = $totalIcu > 0;

    // Only flag bias when sample size is large enough AND the gap exceeds the threshold
    $isBiased = $hasData && $totalIcu >= $minSampleSize && $biasScore >= $biasThreshold;

    file_put_contents(
        __DIR__ . '/bias_debug.txt',
        "male_icu={$maleIcu}\nfemale_icu={$femaleIcu}\ntotal_icu={$totalIcu}\nbias_score={$biasScore}\nis_biased=" . ($isBiased ? 'true' : 'false') . "\n"
    );

    $resultPayload = buildFallbackBiasPayload($malePercentage, $femalePercentage, $biasScore, $isBiased, $hasData, $totalIcu);

    if ($hasData && $totalIcu >= $minSampleSize) {
        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';

        if (!empty($apiKey) && $apiKey !== 'YOUR_GEMINI_API_KEY_HERE') {
            $prompt = "
You are auditing gender fairness in ICU allocation for a hospital triage system.

Use these exact statistics:
- male ICU patient count: {$maleIcu}
- female ICU patient count: {$femaleIcu}
- total ICU patients: {$totalIcu}
- male ICU allocation rate: {$malePercentage}
- female ICU allocation rate: {$femalePercentage}
- bias score: {$biasScore}

Return ONLY valid JSON with exactly this shape:
{
  \"male\": number,
  \"female\": number,
  \"bias_score\": number,
  \"analysis\": \"text explanation\",
  \"suggestion\": \"how to fix bias\"
}

Rules:
- male must equal {$malePercentage}
- female must equal {$femalePercentage}
- bias_score must equal {$biasScore}
- analysis must explain whether the gender gap suggests possible allocation bias in a healthcare context
- suggestion must recommend a practical mitigation step
- do not include markdown, code fences, or any extra keys
";

            $postData = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'response_schema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'male' => ['type' => 'NUMBER'],
                            'female' => ['type' => 'NUMBER'],
                            'bias_score' => ['type' => 'NUMBER'],
                            'analysis' => ['type' => 'STRING'],
                            'suggestion' => ['type' => 'STRING'],
                        ],
                        'required' => ['male', 'female', 'bias_score', 'analysis', 'suggestion'],
                    ],
                ],
            ];

            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";
            $geminiResult = requestGeminiContent($url, $postData);

            if ($geminiResult['error'] === '' && $geminiResult['http_code'] === 200 && is_string($geminiResult['response'])) {
                $responseData = json_decode($geminiResult['response'], true);
                $candidateText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
                $parsedPayload = parseGeminiBiasPayload($candidateText);

                if (
                    is_array($parsedPayload) &&
                    isset($parsedPayload['analysis'], $parsedPayload['suggestion']) &&
                    is_string($parsedPayload['analysis']) &&
                    is_string($parsedPayload['suggestion'])
                ) {
                    $resultPayload = [
                        'male' => $malePercentage,
                        'female' => $femalePercentage,
                        'bias_score' => $biasScore,
                        'analysis' => trim($parsedPayload['analysis']),
                        'suggestion' => trim($parsedPayload['suggestion']),
                    ];
                }
            }
        }
    }

    jsonResponse([
        'status' => 'success',
        'has_data' => $hasData,
        'male' => $resultPayload['male'],
        'female' => $resultPayload['female'],
        'bias_score' => $resultPayload['bias_score'],
        'analysis' => $resultPayload['analysis'],
        'suggestion' => $resultPayload['suggestion'],
        'is_biased' => $isBiased,
        'total_allocations' => $totalIcu,
        'min_sample_required' => $minSampleSize,
        'message' => $hasData ? null : 'No data available.',
    ]);
} catch (Exception $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
}
