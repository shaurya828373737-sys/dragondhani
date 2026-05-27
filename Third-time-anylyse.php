<?php
/**
 * Third-time-anylyse.php
 * Third layer analysis - Weighted Position & Momentum
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

function thirdAnalysis($trends) {
    $total = count($trends);
    if ($total < 3) {
        return ['prediction' => 'DRAGON', 'confidence' => 50];
    }

    
    // Weighted position analysis (recent entries weight more)
    $weightedDragon = 0;
    $weightedTiger = 0;
    for ($i = 0; $i < $total; $i++) {
        $weight = ($i + 1) / $total; // 0.1 to 1.0
        if ($trends[$i] === 'DRAGON') {
            $weightedDragon += $weight;
        } else {
            $weightedTiger += $weight;
        }
    }
    
    // Momentum: compare first half vs second half
    $midpoint = intval($total / 2);
    $firstHalf = array_slice($trends, 0, $midpoint);
    $secondHalf = array_slice($trends, $midpoint);
    
    $firstDragon = count(array_filter($firstHalf, function($t) { return $t === 'DRAGON'; }));
    $secondDragon = count(array_filter($secondHalf, function($t) { return $t === 'DRAGON'; }));
    
    $firstTiger = count($firstHalf) - $firstDragon;
    $secondTiger = count($secondHalf) - $secondDragon;
    
    // Determine momentum direction
    $dragonMomentum = $secondDragon - $firstDragon;
    $tigerMomentum = $secondTiger - $firstTiger;
    
    // Recent 3 analysis
    $lastThree = array_slice($trends, -3);
    $recentDragon = count(array_filter($lastThree, function($t) { return $t === 'DRAGON'; }));
    $recentTiger = 3 - $recentDragon;
    
    // Scoring system
    $dragonScore = 0;
    $tigerScore = 0;
    
    // Weight factor (counter-trend: if weighted high, predict opposite)
    if ($weightedDragon > $weightedTiger) {
        $tigerScore += 30;
    } else {
        $dragonScore += 30;
    }
    
    // Momentum factor (if dragon increasing, tiger likely next)
    if ($dragonMomentum > 0) {
        $tigerScore += 25;
    } elseif ($tigerMomentum > 0) {
        $dragonScore += 25;
    }
    
    // Recent dominance (counter-trend)
    if ($recentDragon > $recentTiger) {
        $tigerScore += 20;
    } else {
        $dragonScore += 20;
    }
    
    // Last entry factor
    $last = $trends[$total - 1];
    if ($last === 'DRAGON') {
        $tigerScore += 15;
    } else {
        $dragonScore += 15;
    }
    
    // Final decision
    $prediction = ($dragonScore >= $tigerScore) ? 'DRAGON' : 'TIGER';
    $totalScore = $dragonScore + $tigerScore;
    $confidence = 60 + (abs($dragonScore - $tigerScore) / max($totalScore, 1)) * 35;
    $confidence = min(95, round($confidence));
    
    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'method' => 'weighted_momentum',
        'details' => [
            'weighted_dragon' => round($weightedDragon, 3),
            'weighted_tiger' => round($weightedTiger, 3),
            'dragon_momentum' => $dragonMomentum,
            'tiger_momentum' => $tigerMomentum,
            'dragon_score' => $dragonScore,
            'tiger_score' => $tigerScore
        ]
    ];
}

$result = thirdAnalysis($trends);
$result['analyser'] = 'third';
$result['status'] = 'success';

echo json_encode($result);
?>
