<?php
/**
 * Master Prediction Tool - Core coordination file
 * Receives results from all three analysis files and determines final prediction
 * Dragon Tiger Master Prediction Tool
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include helper files
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

/**
 * Master Decision Algorithm
 * All three analysis files must agree for highest confidence
 */
function masterDecision($first, $second, $third, $trends, $trendNumber) {
    $predictions = [];
    
    // Extract predictions from each analysis
    if ($first && isset($first['prediction'])) $predictions[] = $first['prediction'];
    if ($second && isset($second['prediction'])) $predictions[] = $second['prediction'];
    if ($third && isset($third['prediction'])) $predictions[] = $third['prediction'];
    
    if (empty($predictions)) {
        // Fallback to internal algorithm
        return runInternalAlgorithm($trends, $trendNumber);
    }
    
    // Count votes
    $dragonVotes = count(array_filter($predictions, function($p) { return $p === 'DRAGON'; }));
    $tigerVotes = count(array_filter($predictions, function($p) { return $p === 'TIGER'; }));
    
    // All three agree - highest confidence
    if ($dragonVotes === 3) {
        return ['prediction' => 'DRAGON', 'confidence' => 99, 'agreement' => 'unanimous'];
    }
    if ($tigerVotes === 3) {
        return ['prediction' => 'TIGER', 'confidence' => 99, 'agreement' => 'unanimous'];
    }
    
    // Two agree - high confidence
    if ($dragonVotes >= 2) {
        return ['prediction' => 'DRAGON', 'confidence' => 95, 'agreement' => 'majority'];
    }
    if ($tigerVotes >= 2) {
        return ['prediction' => 'TIGER', 'confidence' => 95, 'agreement' => 'majority'];
    }
    
    // No agreement - use internal algorithm
    return runInternalAlgorithm($trends, $trendNumber);
}

function runInternalAlgorithm($trends, $trendNumber) {
    $calc = new Calculation();
    $helper = new HelpToTakeAccurate();
    
    $patternResult = $calc->analyzePattern($trends);
    $accurateResult = $helper->refineResult($trends, $patternResult);
    
    return [
        'prediction' => $accurateResult,
        'confidence' => 92,
        'agreement' => 'algorithm'
    ];
}

// Run master decision
$result = masterDecision($firstResult, $secondResult, $thirdResult, $trends, $trendNumber);

// Update JSON with result
$jsonFile = 'get-data.json';
$existingData = json_decode(file_get_contents($jsonFile), true);
$existingData['current_session']['result'] = $result['prediction'];
$existingData['statistics']['total_predictions']++;
file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT));

// Return final prediction
echo json_encode([
    'status' => 'success',
    'prediction' => $result['prediction'],
    'confidence' => $result['confidence'],
    'agreement' => $result['agreement'],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
