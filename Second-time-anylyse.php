<?php
/**
 * Second-time-anylyse.php
 * Second layer analysis - Pattern Recognition & Transition Matrix
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

function secondAnalysis($trends) {
    $total = count($trends);
    if ($total < 2) {
        return ['prediction' => 'TIGER', 'confidence' => 50];
    }

    
    // Build transition matrix
    $transitions = ['DD' => 0, 'DT' => 0, 'TD' => 0, 'TT' => 0];
    for ($i = 0; $i < $total - 1; $i++) {
        $from = ($trends[$i] === 'DRAGON') ? 'D' : 'T';
        $to = ($trends[$i+1] === 'DRAGON') ? 'D' : 'T';
        $transitions[$from . $to]++;
    }
    
    // Check alternating pattern
    $alternating = true;
    for ($i = 1; $i < $total; $i++) {
        if ($trends[$i] === $trends[$i-1]) {
            $alternating = false;
            break;
        }
    }
    
    // Check double pattern (AA BB AA BB)
    $doublePattern = false;
    if ($total >= 4) {
        $pairs = [];
        for ($i = 0; $i < $total - 1; $i += 2) {
            if ($trends[$i] === $trends[$i+1]) {
                $pairs[] = $trends[$i];
            }
        }
        if (count($pairs) >= 2) {
            $pairAlternating = true;
            for ($j = 1; $j < count($pairs); $j++) {
                if ($pairs[$j] === $pairs[$j-1]) {
                    $pairAlternating = false;
                    break;
                }
            }
            $doublePattern = $pairAlternating;
        }
    }
    
    // Decision based on patterns
    $prediction = 'DRAGON';
    $confidence = 70;
    $last = $trends[$total - 1];
    
    if ($alternating) {
        // Pure alternation - continue pattern
        $prediction = ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        $confidence = 90;
    } elseif ($doublePattern) {
        // Double pattern detected
        $lastTwo = array_slice($trends, -2);
        if ($lastTwo[0] === $lastTwo[1]) {
            // Just had a pair, next should be opposite
            $prediction = ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        } else {
            // In middle of pair, continue
            $prediction = $last;
        }
        $confidence = 85;
    } else {
        // Use transition matrix
        if ($last === 'DRAGON') {
            $totalFromD = $transitions['DD'] + $transitions['DT'];
            if ($totalFromD > 0) {
                $prediction = ($transitions['DT'] >= $transitions['DD']) ? 'TIGER' : 'DRAGON';
                $confidence = 75 + abs($transitions['DT'] - $transitions['DD']) * 5;
            }
        } else {
            $totalFromT = $transitions['TD'] + $transitions['TT'];
            if ($totalFromT > 0) {
                $prediction = ($transitions['TD'] >= $transitions['TT']) ? 'DRAGON' : 'TIGER';
                $confidence = 75 + abs($transitions['TD'] - $transitions['TT']) * 5;
            }
        }
    }
    
    $confidence = min(95, $confidence);
    
    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'method' => 'pattern_transition',
        'details' => [
            'transitions' => $transitions,
            'is_alternating' => $alternating,
            'is_double_pattern' => $doublePattern
        ]
    ];
}

$result = secondAnalysis($trends);
$result['analyser'] = 'second';
$result['status'] = 'success';

echo json_encode($result);
?>
