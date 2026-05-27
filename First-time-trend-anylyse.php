<?php
/**
 * First-time-trend-anylyse.php
 * First layer analysis - Frequency & Streak Detection
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

$input = json_decode(file_get_contents('php://input'), true);
$trends = isset($input['trends']) ? $input['trends'] : [];
$trendNumber = isset($input['trendNumber']) ? $input['trendNumber'] : 1;

function firstAnalysis($trends) {
    $total = count($trends);
    if ($total === 0) {
        return ['prediction' => 'DRAGON', 'confidence' => 50];
    }

    
    // Count frequencies
    $dragonCount = 0;
    $tigerCount = 0;
    foreach ($trends as $t) {
        if ($t === 'DRAGON') $dragonCount++;
        else $tigerCount++;
    }
    
    // Detect end streak
    $lastVal = $trends[$total - 1];
    $streak = 1;
    for ($i = $total - 2; $i >= 0; $i--) {
        if ($trends[$i] === $lastVal) $streak++;
        else break;
    }
    
    // Decision logic
    $prediction = 'DRAGON';
    $confidence = 70;
    
    // Rule 1: Long streak reversal
    if ($streak >= 3) {
        $prediction = ($lastVal === 'DRAGON') ? 'TIGER' : 'DRAGON';
        $confidence = 88;
    }
    // Rule 2: Medium streak continuation
    elseif ($streak === 2) {
        $prediction = $lastVal;
        $confidence = 72;
    }
    // Rule 3: Frequency imbalance
    elseif ($dragonCount > $tigerCount + 2) {
        $prediction = 'TIGER';
        $confidence = 80;
    }
    elseif ($tigerCount > $dragonCount + 2) {
        $prediction = 'DRAGON';
        $confidence = 80;
    }
    // Rule 4: Nearly equal - predict opposite of last
    else {
        $prediction = ($lastVal === 'DRAGON') ? 'TIGER' : 'DRAGON';
        $confidence = 65;
    }
    
    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'method' => 'frequency_streak',
        'details' => [
            'dragon_count' => $dragonCount,
            'tiger_count' => $tigerCount,
            'end_streak' => $streak,
            'streak_value' => $lastVal
        ]
    ];
}

$result = firstAnalysis($trends);
$result['analyser'] = 'first';
$result['status'] = 'success';

echo json_encode($result);
?>
