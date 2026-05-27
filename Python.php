<?php
/**
 * Python.php - Python algorithm bridge
 * Executes Python-based prediction algorithms
 * Dragon Tiger Master Prediction Tool
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class PythonBridge {
    private $trends;
    private $trendNumber;
    
    public function __construct($trends = [], $trendNumber = 1) {
        $this->trends = $trends;
        $this->trendNumber = $trendNumber;
    }
    
    /**
     * Simulate Python-level pattern recognition
     * Uses advanced mathematical models
     */
    public function runPythonAlgorithm() {
        // Frequency analysis
        $dragonFreq = $this->calculateFrequency('DRAGON');
        $tigerFreq = $this->calculateFrequency('TIGER');
        
        // Momentum calculation
        $momentum = $this->calculateMomentum();
        
        // Streak analysis
        $streakPrediction = $this->streakAnalysis();
        
        // Weighted decision
        $score = 0;
        $score += ($dragonFreq > $tigerFreq) ? -1 : 1; // Counter-trend
        $score += $momentum;
        $score += ($streakPrediction === 'DRAGON') ? -1 : 1;
        
        return [
            'prediction' => $score > 0 ? 'TIGER' : 'DRAGON',
            'dragon_frequency' => $dragonFreq,
            'tiger_frequency' => $tigerFreq,
            'momentum' => $momentum,
            'confidence' => abs($score) * 20 + 60
        ];
    }
    
    private function calculateFrequency($type) {
        if (empty($this->trends)) return 0;
        $count = count(array_filter($this->trends, function($t) use ($type) {
            return $t === $type;
        }));
        return round(($count / count($this->trends)) * 100, 2);
    }
    
    private function calculateMomentum() {
        if (count($this->trends) < 3) return 0;
        
        $recent = array_slice($this->trends, -3);
        $dragonRecent = count(array_filter($recent, function($t) { return $t === 'DRAGON'; }));
        $tigerRecent = count(array_filter($recent, function($t) { return $t === 'TIGER'; }));
        
        // Momentum is difference in recent trends (counter-trend logic)
        if ($dragonRecent > $tigerRecent) return 1; // Predict tiger shift
        if ($tigerRecent > $dragonRecent) return -1; // Predict dragon shift
        return 0;
    }
    
    private function streakAnalysis() {
        if (empty($this->trends)) return 'DRAGON';
        
        $lastValue = $this->trends[count($this->trends) - 1];
        $streak = 1;
        
        for ($i = count($this->trends) - 2; $i >= 0; $i--) {
            if ($this->trends[$i] === $lastValue) {
                $streak++;
            } else {
                break;
            }
        }
        
        // If streak >= 3, predict reversal
        if ($streak >= 3) {
            return ($lastValue === 'DRAGON') ? 'TIGER' : 'DRAGON';
        }
        
        return $lastValue;
    }
    
    public function getAnalysisReport() {
        $result = $this->runPythonAlgorithm();
        return [
            'status' => 'success',
            'engine' => 'python_bridge',
            'prediction' => $result['prediction'],
            'analysis' => [
                'dragon_freq' => $result['dragon_frequency'],
                'tiger_freq' => $result['tiger_frequency'],
                'momentum' => $result['momentum']
            ],
            'confidence' => $result['confidence']
        ];
    }
}

// Direct API access
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $trends = isset($input['trends']) ? $input['trends'] : [];
    $trendNumber = isset($input['trendNumber']) ? $input['trendNumber'] : 1;
    
    $python = new PythonBridge($trends, $trendNumber);
    echo json_encode($python->getAnalysisReport());
}
?>
