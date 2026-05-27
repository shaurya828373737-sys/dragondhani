<?php
/**
 * Master Prediction Tool - Final Decision Engine
 * Combines all 3 analysis results using weighted voting
 * If all 3 agree = highest confidence
 * If 2 agree = high confidence (use majority)
 * If all disagree = use strongest confidence score
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'Calculation.php';
require_once 'Help-to-take-accurate.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit();
}

$firstResult = isset($data['first']) ? $data['first'] : null;
$secondResult = isset($data['second']) ? $data['second'] : null;
$thirdResult = isset($data['third']) ? $data['third'] : null;
$trends = isset($data['trends']) ? $data['trends'] : [];
$trendNumber = isset($data['trendNumber']) ? $data['trendNumber'] : 1;

function masterDecision($first, $second, $third, $trends) {
    $predictions = [];
    $confidences = [];

    if ($first && isset($first['prediction'])) {
        $predictions[] = $first['prediction'];
        $confidences[] = isset($first['confidence']) ? $first['confidence'] : 60;
    }
    if ($second && isset($second['prediction'])) {
        $predictions[] = $second['prediction'];
        $confidences[] = isset($second['confidence']) ? $second['confidence'] : 60;
    }
    if ($third && isset($third['prediction'])) {
        $predictions[] = $third['prediction'];
        $confidences[] = isset($third['confidence']) ? $third['confidence'] : 60;
    }

    if (empty($predictions)) {
        return fallbackAlgorithm($trends);
    }

    // Count votes weighted by confidence
    $dragonScore = 0;
    $tigerScore = 0;

    for ($i = 0; $i < count($predictions); $i++) {
        $weight = $confidences[$i] / 100;
        if ($predictions[$i] === 'DRAGON') {
            $dragonScore += $weight;
        } else {
            $tigerScore += $weight;
        }
    }

    // Count raw votes
    $dragonVotes = count(array_filter($predictions, function($p) { return $p === 'DRAGON'; }));
    $tigerVotes = count($predictions) - $dragonVotes;

    // All three agree = highest confidence
    if ($dragonVotes === 3 || $tigerVotes === 3) {
        $prediction = ($dragonVotes === 3) ? 'DRAGON' : 'TIGER';
        return ['prediction' => $prediction, 'confidence' => 95, 'agreement' => 'unanimous'];
    }

    // Two agree = use majority with weighted confidence
    if ($dragonVotes >= 2) {
        return ['prediction' => 'DRAGON', 'confidence' => 85, 'agreement' => 'majority'];
    }
    if ($tigerVotes >= 2) {
        return ['prediction' => 'TIGER', 'confidence' => 85, 'agreement' => 'majority'];
    }

    // Use weighted score
    $prediction = ($dragonScore >= $tigerScore) ? 'DRAGON' : 'TIGER';
    return ['prediction' => $prediction, 'confidence' => 70, 'agreement' => 'weighted'];
}

function fallbackAlgorithm($trends) {
    $total = count($trends);
    if ($total === 0) return ['prediction' => 'DRAGON', 'confidence' => 50, 'agreement' => 'fallback'];

    $calc = new Calculation();
    $helper = new HelpToTakeAccurate();

    $calcResult = $calc->analyzePattern($trends);
    $refined = $helper->refineResult($trends, $calcResult);

    return ['prediction' => $refined, 'confidence' => 72, 'agreement' => 'fallback'];
}

// Run master decision
$result = masterDecision($firstResult, $secondResult, $thirdResult, $trends);

// Save to JSON
$jsonFile = 'get-data.json';
if (file_exists($jsonFile)) {
    $existingData = json_decode(file_get_contents($jsonFile), true);
    if ($existingData) {
        $existingData['current_session']['result'] = $result['prediction'];
        $existingData['statistics']['total_predictions']++;
        file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT));
    }
}

echo json_encode([
    'status' => 'success',
    'prediction' => $result['prediction'],
    'confidence' => $result['confidence'],
    'agreement' => $result['agreement'],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
