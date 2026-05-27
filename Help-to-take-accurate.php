<?php
/**
 * Help-to-take-accurate.php
 * Refinement layer - applies final checks on prediction
 */

class HelpToTakeAccurate {

    public function refineResult($trends, $initialPrediction) {
        $total = count($trends);
        if ($total < 3) return $initialPrediction;

        // Layer 1: Extreme streak override
        $last = $trends[$total - 1];
        $streak = 1;
        for ($i = $total - 2; $i >= 0; $i--) {
            if ($trends[$i] === $last) $streak++;
            else break;
        }

        if ($streak >= 5) {
            // Very long streak - definitely predict reversal
            return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        }

        // Layer 2: Perfect alternation override
        $alternating = true;
        for ($i = 1; $i < $total; $i++) {
            if ($trends[$i] === $trends[$i - 1]) {
                $alternating = false;
                break;
            }
        }
        if ($alternating) {
            return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        }

        // Layer 3: Extreme frequency imbalance
        $dCount = count(array_filter($trends, function($t) { return $t === 'DRAGON'; }));
        $tCount = $total - $dCount;

        // If prediction matches the already dominant side by a lot, flip it
        if ($initialPrediction === 'DRAGON' && $dCount >= $total * 0.8) {
            return 'TIGER';
        }
        if ($initialPrediction === 'TIGER' && $tCount >= $total * 0.8) {
            return 'DRAGON';
        }

        return $initialPrediction;
    }
}

// API endpoint
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $input = json_decode(file_get_contents('php://input'), true);
        $trends = isset($input['trends']) ? $input['trends'] : [];

        $helper = new HelpToTakeAccurate();
        $result = $helper->refineResult($trends, 'DRAGON');

        echo json_encode(['status' => 'success', 'refined_prediction' => $result]);
    }
}
?>
