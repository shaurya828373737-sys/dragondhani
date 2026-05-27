<?php
/**
 * Python-algo.php - Core prediction algorithm
 * Implements mathematical probability and pattern matching
 * Dragon Tiger Master Prediction Tool
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class PythonAlgo {
    private $trends;
    private $trendNumber;
    private $weights;
    
    public function __construct($trends = [], $trendNumber = 1) {
        $this->trends = $trends;
        $this->trendNumber = $trendNumber;
        $this->weights = [
            'frequency' => 0.25,
            'streak' => 0.30,
            'alternation' => 0.20,
            'position' => 0.15,
            'momentum' => 0.10
        ];
    }
    
    /**
     * Main algorithm runner
     */
    public function runAlgorithm() {
        $scores = $this->calculateScores();
        $prediction = $scores['dragon_score'] > $scores['tiger_score'] ? 'DRAGON' : 'TIGER';
        $confidence = $this->calculateConfidence($scores);
        
        return [
            'prediction' => $prediction,
            'confidence' => $confidence,
            'scores' => $scores,
            'method' => 'weighted_algorithm'
        ];
    }
    
    private function calculateScores() {
        $dragonScore = 0;
        $tigerScore = 0;
        $total = count($this->trends);
        
        if ($total === 0) {
            return ['dragon_score' => 50, 'tiger_score' => 50];
        }
        
        // 1. Frequency Analysis (counter-trend)
        $dragonCount = count(array_filter($this->trends, function($t) { return $t === 'DRAGON'; }));
        $tigerCount = $total - $dragonCount;
        
        // Counter-trend: if dragon appears more, tiger is more likely next
        $freqDragon = ($tigerCount / $total) * 100 * $this->weights['frequency'];
        $freqTiger = ($dragonCount / $total) * 100 * $this->weights['frequency'];
        $dragonScore += $freqDragon;
        $tigerScore += $freqTiger;
        
        // 2. Streak Analysis
        $streakResult = $this->analyzeStreak();
        if ($streakResult === 'DRAGON') {
            $dragonScore += 100 * $this->weights['streak'];
        } else {
            $tigerScore += 100 * $this->weights['streak'];
        }
        
        // 3. Alternation Pattern
        $altResult = $this->checkAlternation();
        if ($altResult === 'DRAGON') {
            $dragonScore += 100 * $this->weights['alternation'];
        } else {
            $tigerScore += 100 * $this->weights['alternation'];
        }
        
        // 4. Position Weight (last 3 entries matter more)
        $posResult = $this->positionWeight();
        if ($posResult === 'DRAGON') {
            $dragonScore += 100 * $this->weights['position'];
        } else {
            $tigerScore += 100 * $this->weights['position'];
        }
        
        // 5. Momentum
        $momResult = $this->momentumCalc();
        if ($momResult === 'DRAGON') {
            $dragonScore += 100 * $this->weights['momentum'];
        } else {
            $tigerScore += 100 * $this->weights['momentum'];
        }
        
        return [
            'dragon_score' => round($dragonScore, 2),
            'tiger_score' => round($tigerScore, 2)
        ];
    }
    
    private function analyzeStreak() {
        if (empty($this->trends)) return 'DRAGON';
        
        $last = $this->trends[count($this->trends) - 1];
        $streak = 1;
        
        for ($i = count($this->trends) - 2; $i >= 0; $i--) {
            if ($this->trends[$i] === $last) {
                $streak++;
            } else {
                break;
            }
        }
        
        // Streak of 3+ means reversal likely
        if ($streak >= 3) {
            return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        }
        // Streak of 2 means continuation possible
        if ($streak === 2) {
            return $last;
        }
        
        return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
    }
    
    private function checkAlternation() {
        if (count($this->trends) < 2) return 'DRAGON';
        
        $alternating = true;
        for ($i = 1; $i < count($this->trends); $i++) {
            if ($this->trends[$i] === $this->trends[$i-1]) {
                $alternating = false;
                break;
            }
        }
        
        if ($alternating) {
            $last = $this->trends[count($this->trends) - 1];
            return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        }
        
        // Check partial alternation in last 4
        $lastFour = array_slice($this->trends, -4);
        $altCount = 0;
        for ($i = 1; $i < count($lastFour); $i++) {
            if ($lastFour[$i] !== $lastFour[$i-1]) $altCount++;
        }
        
        if ($altCount >= 2) {
            $last = $this->trends[count($this->trends) - 1];
            return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        }
        
        return $this->trends[count($this->trends) - 1];
    }
    
    private function positionWeight() {
        $lastThree = array_slice($this->trends, -3);
        $d = count(array_filter($lastThree, function($t) { return $t === 'DRAGON'; }));
        $t = count($lastThree) - $d;
        
        // Counter-trend from recent positions
        return ($d > $t) ? 'TIGER' : 'DRAGON';
    }
    
    private function momentumCalc() {
        if (count($this->trends) < 5) {
            return $this->trends[count($this->trends) - 1] === 'DRAGON' ? 'TIGER' : 'DRAGON';
        }
        
        $firstHalf = array_slice($this->trends, 0, 5);
        $secondHalf = array_slice($this->trends, 5);
        
        $firstDragon = count(array_filter($firstHalf, function($t) { return $t === 'DRAGON'; }));
        $secondDragon = count(array_filter($secondHalf, function($t) { return $t === 'DRAGON'; }));
        
        // If dragon increasing in second half, tiger more likely next
        if ($secondDragon > $firstDragon) {
            return 'TIGER';
        }
        return 'DRAGON';
    }
    
    private function calculateConfidence($scores) {
        $diff = abs($scores['dragon_score'] - $scores['tiger_score']);
        $maxScore = max($scores['dragon_score'], $scores['tiger_score']);
        
        if ($maxScore === 0) return 50;
        
        $confidence = min(99, 60 + ($diff / $maxScore) * 40);
        return round($confidence);
    }
}

// API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $trends = isset($input['trends']) ? $input['trends'] : [];
    $trendNumber = isset($input['trendNumber']) ? $input['trendNumber'] : 1;
    
    $algo = new PythonAlgo($trends, $trendNumber);
    echo json_encode($algo->runAlgorithm());
}
?>
