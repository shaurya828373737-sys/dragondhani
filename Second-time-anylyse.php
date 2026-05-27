<?php
/**
 * Second-time-anylyse.php
 * ALGORITHM 2: Streak Analysis + Pattern Repetition Detection
 * Identifies streaks, alternating patterns, and double patterns
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

    $last = $trends[$total - 1];
    $scores = ['DRAGON' => 0, 'TIGER' => 0];

    // === STREAK ANALYSIS ===
    $streak = 1;
    for ($i = $total - 2; $i >= 0; $i--) {
        if ($trends[$i] === $last) $streak++;
        else break;
    }

    // What happened after similar streaks in this data?
    $afterStreakD = 0;
    $afterStreakT = 0;
    $i = 0;
    while ($i < $total) {
        $val = $trends[$i];
        $sLen = 1;
        while ($i + $sLen < $total && $trends[$i + $sLen] === $val) {
            $sLen++;
        }
        // If this streak length matches current and there's a next value
        if ($sLen === $streak && ($i + $sLen) < $total) {
            if ($trends[$i + $sLen] === 'DRAGON') $afterStreakD++;
            else $afterStreakT++;
        }
        $i += $sLen;
    }

    if ($afterStreakD + $afterStreakT > 0) {
        // Use historical data for this streak length
        if ($afterStreakD > $afterStreakT) $scores['DRAGON'] += 35;
        else $scores['TIGER'] += 35;
    } else {
        // No history: short streaks continue, long streaks break
        if ($streak <= 2) {
            $scores[$last] += 30;
        } elseif ($streak >= 4) {
            $opp = $last === 'DRAGON' ? 'TIGER' : 'DRAGON';
            $scores[$opp] += 30;
        } else {
            // streak = 3, slight lean to continue
            $scores[$last] += 15;
        }
    }

    // === ALTERNATING PATTERN CHECK ===
    $altCount = 0;
    for ($i = $total - 1; $i >= 1; $i--) {
        if ($trends[$i] !== $trends[$i - 1]) $altCount++;
        else break;
    }

    if ($altCount >= 3) {
        // Strong alternating - continue the pattern
        $opp = $last === 'DRAGON' ? 'TIGER' : 'DRAGON';
        $scores[$opp] += 40;
    }

    // === DOUBLE PATTERN (AA BB AA BB) ===
    if ($total >= 4) {
        $lastTwo = [$trends[$total - 2], $trends[$total - 1]];
        if ($lastTwo[0] === $lastTwo[1]) {
            // Just completed a pair - check if double-alternating
            $prevPairStart = $total - 4;
            if ($prevPairStart >= 0 && $trends[$prevPairStart] === $trends[$prevPairStart + 1]
                && $trends[$prevPairStart] !== $lastTwo[0]) {
                // DD TT pattern - next should be opposite pair
                $opp = $last === 'DRAGON' ? 'TIGER' : 'DRAGON';
                $scores[$opp] += 35;
            }
        } else {
            // Last two are different - check if we're in middle of pair
            if ($total >= 3 && $trends[$total - 3] === $trends[$total - 2]) {
                // Was AA B - might continue to BB
                $scores[$last] += 25;
            }
        }
    }

    // === TRIPLE PATTERN (AAA BBB) ===
    if ($total >= 6) {
        $last3Same = ($trends[$total-1] === $trends[$total-2] && $trends[$total-2] === $trends[$total-3]);
        if ($last3Same) {
            $prev3Start = $total - 6;
            if ($prev3Start >= 0 && $trends[$prev3Start] === $trends[$prev3Start+1] 
                && $trends[$prev3Start+1] === $trends[$prev3Start+2]
                && $trends[$prev3Start] !== $last) {
                // AAA BBB pattern detected
                $opp = $last === 'DRAGON' ? 'TIGER' : 'DRAGON';
                $scores[$opp] += 30;
            }
        }
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
        'method' => 'streak_pattern',
        'details' => [
            'current_streak' => $streak,
            'streak_value' => $last,
            'alternating_count' => $altCount,
            'scores' => $scores
        ]
    ];
}

$result = secondAnalysis($trends);
$result['analyser'] = 'second';
$result['status'] = 'success';

echo json_encode($result);
?>
