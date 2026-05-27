<?php
/**
 * Python-get-prediction.php - Retrieves prediction from Python algorithm
 * Acts as getter for processed prediction data
 * Dragon Tiger Master Prediction Tool
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'Python.php';
require_once 'Python-algo.php';

class PredictionGetter {
    private $trends;
    private $trendNumber;
    
    public function __construct($trends, $trendNumber) {
        $this->trends = $trends;
        $this->trendNumber = $trendNumber;
    }
    
    /**
     * Get prediction by combining Python bridge and algo results
     */
    public function getPrediction() {
        // Get Python bridge result
        $pythonBridge = new PythonBridge($this->trends, $this->trendNumber);
        $bridgeResult = $pythonBridge->runPythonAlgorithm();
        
        // Get Python algo result
        $algo = new PythonAlgo($this->trends, $this->trendNumber);
        $algoResult = $algo->runAlgorithm();
        
        // Combine results
        $bridgePred = $bridgeResult['prediction'];
        $algoPred = $algoResult['prediction'];
        
        // If both agree, high confidence
        if ($bridgePred === $algoPred) {
            return [
                'status' => 'success',
                'prediction' => $bridgePred,
                'confidence' => 97,
                'source' => 'combined_agreement',
                'details' => [
                    'bridge_prediction' => $bridgePred,
                    'algo_prediction' => $algoPred,
                    'bridge_confidence' => $bridgeResult['confidence'],
                    'algo_confidence' => $algoResult['confidence']
                ]
            ];
        }
        
        // If disagree, use higher confidence one
        if ($bridgeResult['confidence'] > $algoResult['confidence']) {
            $finalPred = $bridgePred;
            $finalConf = $bridgeResult['confidence'] - 10;
        } else {
            $finalPred = $algoPred;
            $finalConf = $algoResult['confidence'] - 10;
        }
        
        return [
            'status' => 'success',
            'prediction' => $finalPred,
            'confidence' => $finalConf,
            'source' => 'highest_confidence',
            'details' => [
                'bridge_prediction' => $bridgePred,
                'algo_prediction' => $algoPred
            ]
        ];
    }
}

// API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $trends = isset($input['trends']) ? $input['trends'] : [];
    $trendNumber = isset($input['trendNumber']) ? $input['trendNumber'] : 1;
    
    $getter = new PredictionGetter($trends, $trendNumber);
    echo json_encode($getter->getPrediction());
}
?>
