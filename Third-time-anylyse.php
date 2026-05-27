<?php
/**
 * Third-time-anylyse.php
 * ALGORITHM 3: Weighted Sequence Matching + Frequency Counter-trend
 * Looks at sub-sequences and predicts based on what followed them historically
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

    $scores = ['DRAGON' => 0, 'TIGER' => 0];

    // === SUB-SEQUENCE MATCHING (last 2 entries) ===
    $last2 = $trends[$total - 2] . ',' . $trends[$total - 1];
    $match2D = 0;
    $match2T = 0;
    for ($i = 0; $i < $total - 2; $i++) {
        $pair = $trends[$i] . ',' . $trends[$i + 1];
        if ($pair === $last2) {
            if ($trends[$i + 2] === 'DRAGON') $match2D++;
            else $match2T++;
        }
    }
    if ($match2D + $match2T > 0) {
        if ($match2D > $match2T) $scores['DRAGON'] += 40;
        elseif ($match2T > $match2D) $scores['TIGER'] += 40;
        // If equal, no points
    }

    // === SUB-SEQUENCE MATCHING (last 3 entries) ===
    if ($total >= 4) {
        $last3 = $trends[$total - 3] . ',' . $trends[$total - 2] . ',' . $trends[$total - 1];
        $match3D = 0;
        $match3T = 0;
        for ($i = 0; $i < $total - 3; $i++) {
            $triple = $trends[$i] . ',' . $trends[$i + 1] . ',' . $trends[$i + 2];
            if ($triple === $last3 && ($i + 3) < $total) {
                if ($trends[$i + 3] === 'DRAGON') $match3D++;
                else $match3T++;
            }
        }
        if ($match3D + $match3T > 0) {
            // 3-gram match is more reliable than 2-gram
            if ($match3D > $match3T) $scores['DRAGON'] += 50;
            elseif ($match3T > $match3D) $scores['TIGER'] += 50;
        }
    }

    // === FREQUENCY COUNTER-TREND ===
    // In short windows, if one side dominates, other becomes more likely
    $last5 = array_slice($trends, -5);
    $d5 = count(array_filter($last5, function($t) { return $t === 'DRAGON'; }));
    $t5 = count($last5) - $d5;

    if ($d5 >= 4) {
        $scores['TIGER'] += 25; // Dragon dominated, tiger likely
    } elseif ($t5 >= 4) {
        $scores['DRAGON'] += 25; // Tiger dominated, dragon likely
    }

    // === POSITION WEIGHT (recent entries matter more) ===
    $weightedD = 0;
    $weightedT = 0;
    for ($i = 0; $i < $total; $i++) {
        $weight = ($i + 1) / $total; // 0.1 to 1.0 scale
        if ($trends[$i] === 'DRAGON') $weightedD += $weight;
        else $weightedT += $weight;
    }

    // Counter-trend on weighted
    if ($weightedD > $weightedT * 1.3) {
        $scores['TIGER'] += 20;
    } elseif ($weightedT > $weightedD * 1.3) {
        $scores['DRAGON'] += 20;
    }

    // === RHYTHM DETECTION ===
    // Check if there's a rhythm (e.g., every 2nd or 3rd is same)
    $rhythm2 = detectRhythm($trends, 2);
    $rhythm3 = detectRhythm($trends, 3);

    if ($rhythm2 !== null) {
        $scores[$rhythm2] += 30;
    }
    if ($rhythm3 !== null) {
        $scores[$rhythm3] += 20;
    }

    // Final decision
    $prediction = $scores['DRAGON'] >= $scores['TIGER'] ? 'DRAGON' : 'TIGER';
    $maxScore = max($scores['DRAGON'], $scores['TIGER']);
    $totalScore = $scores['DRAGON'] + $scores['TIGER'];
    $confidence = $totalScore > 0 ? round(55 + ($maxScore / $totalScore) * 35) : 55;
    $confidence = min(95, max(50, $confidence));

    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'method' => 'sequence_frequency',
        'details' => [
            'pair_matches' => ['dragon' => $match2D, 'tiger' => $match2T],
            'scores' => $scores,
            'weighted' => ['dragon' => round($weightedD, 3), 'tiger' => round($weightedT, 3)]
        ]
    ];
}

function detectRhythm($trends, $interval) {
    $total = count($trends);
    if ($total < $interval * 3) return null;

    // Check if position % interval has a pattern
    $nextPos = $total; // The position we're predicting
    $posInCycle = $nextPos % $interval;

    // Count what appeared at this position in cycle
    $dCount = 0;
    $tCount = 0;
    for ($i = $posInCycle; $i < $total; $i += $interval) {
        if ($trends[$i] === 'DRAGON') $dCount++;
        else $tCount++;
    }

    $totalAtPos = $dCount + $tCount;
    if ($totalAtPos < 2) return null;

    // Only return if there's a clear bias (>= 70%)
    if ($dCount / $totalAtPos >= 0.7) return 'DRAGON';
    if ($tCount / $totalAtPos >= 0.7) return 'TIGER';

    return null;
}

$result = thirdAnalysis($trends);
$result['analyser'] = 'third';
$result['status'] = 'success';

echo json_encode($result);
?>
