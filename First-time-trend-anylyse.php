<?php
/**
 * First-time-trend-anylyse.php
 * ALGORITHM 1: Markov Chain Transition Matrix
 * Calculates transition probabilities from the data
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
    if ($total < 2) {
        return ['prediction' => 'DRAGON', 'confidence' => 50];
    }

    $last = $trends[$total - 1];

    // === MARKOV CHAIN: Build transition matrix ===
    $trans = [
        'DRAGON' => ['DRAGON' => 0, 'TIGER' => 0],
        'TIGER' => ['DRAGON' => 0, 'TIGER' => 0]
    ];

    for ($i = 0; $i < $total - 1; $i++) {
        $trans[$trends[$i]][$trends[$i + 1]]++;
    }

    // Calculate probability of next state given current state
    $fromLast = $trans[$last];
    $totalFromLast = $fromLast['DRAGON'] + $fromLast['TIGER'];

    if ($totalFromLast === 0) {
        // No data for this state, use overall frequency
        $dCount = count(array_filter($trends, function($t) { return $t === 'DRAGON'; }));
        $prediction = ($dCount > $total / 2) ? 'TIGER' : 'DRAGON';
        $confidence = 55;
    } else {
        $probDragon = $fromLast['DRAGON'] / $totalFromLast;
        $probTiger = $fromLast['TIGER'] / $totalFromLast;

        if ($probDragon > $probTiger) {
            $prediction = 'DRAGON';
            $confidence = round(60 + ($probDragon - 0.5) * 60);
        } elseif ($probTiger > $probDragon) {
            $prediction = 'TIGER';
            $confidence = round(60 + ($probTiger - 0.5) * 60);
        } else {
            // Equal probability - use 2nd order Markov
            $prediction = secondOrderMarkov($trends);
            $confidence = 62;
        }
    }

    // === 2nd ORDER MARKOV (last 2 states) for extra accuracy ===
    if ($total >= 3) {
        $second = secondOrderMarkov($trends);
        // If 2nd order disagrees with 1st order and has enough data, trust 2nd order
        $pair = $trends[$total - 2] . '_' . $trends[$total - 1];
        $pairCount = 0;
        for ($i = 0; $i < $total - 2; $i++) {
            if ($trends[$i] . '_' . $trends[$i + 1] === $pair) {
                $pairCount++;
            }
        }
        if ($pairCount >= 2 && $second !== $prediction) {
            $prediction = $second;
            $confidence = max($confidence, 70);
        }
    }

    $confidence = min(95, max(50, $confidence));

    return [
        'prediction' => $prediction,
        'confidence' => $confidence,
        'method' => 'markov_chain',
        'details' => [
            'transitions' => $trans,
            'last_state' => $last
        ]
    ];
}

function secondOrderMarkov($trends) {
    $total = count($trends);
    if ($total < 3) return $trends[$total - 1];

    $lastPair = $trends[$total - 2] . '_' . $trends[$total - 1];
    $nextD = 0;
    $nextT = 0;

    for ($i = 0; $i < $total - 2; $i++) {
        $pair = $trends[$i] . '_' . $trends[$i + 1];
        if ($pair === $lastPair) {
            if ($trends[$i + 2] === 'DRAGON') $nextD++;
            else $nextT++;
        }
    }

    if ($nextD + $nextT === 0) {
        return $trends[$total - 1] === 'DRAGON' ? 'TIGER' : 'DRAGON';
    }

    return $nextD >= $nextT ? 'DRAGON' : 'TIGER';
}

$result = firstAnalysis($trends);
$result['analyser'] = 'first';
$result['status'] = 'success';

echo json_encode($result);
?>
