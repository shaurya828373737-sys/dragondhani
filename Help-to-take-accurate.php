<?php
/**
 * Help-to-take-accurate.php
 * Refines prediction for maximum accuracy
 * Dragon Tiger Master Prediction Tool
 */

class HelpToTakeAccurate {
    
    /**
     * Refine result using multiple checks
     */
    public function refineResult($trends, $initialPrediction) {
        // Apply 3 refinement layers
        $layer1 = $this->layer1_streakCheck($trends, $initialPrediction);
        $layer2 = $this->layer2_patternMatch($trends, $layer1);
        $layer3 = $this->layer3_finalVerify($trends, $layer2);
        
        return $layer3;
    }

    
    private function layer1_streakCheck($trends, $prediction) {
        $last = $trends[count($trends) - 1];
        $streak = 1;
        
        for ($i = count($trends) - 2; $i >= 0; $i--) {
            if ($trends[$i] === $last) {
                $streak++;
            } else {
                break;
            }
        }
        
        // Strong streak override
        if ($streak >= 4) {
            return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        }
        
        return $prediction;
    }
    
    private function layer2_patternMatch($trends, $prediction) {
        // Check for double-double pattern (DD TT DD TT)
        if (count($trends) >= 4) {
            $lastFour = array_slice($trends, -4);
            if ($lastFour[0] === $lastFour[1] && $lastFour[2] === $lastFour[3] 
                && $lastFour[0] !== $lastFour[2]) {
                return $lastFour[0]; // Continue the double pattern
            }
        }
        return $prediction;
    }
    
    private function layer3_finalVerify($trends, $prediction) {
        // Final confidence check
        $total = count($trends);
        $predCount = count(array_filter($trends, function($t) use ($prediction) {
            return $t === $prediction;
        }));
        
        $ratio = $predCount / $total;
        
        // If predicted value already dominates heavily, switch
        if ($ratio > 0.7) {
            return ($prediction === 'DRAGON') ? 'TIGER' : 'DRAGON';
        }
        
        return $prediction;
    }
}

// API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $trends = isset($input['trends']) ? $input['trends'] : [];
    
    $helper = new HelpToTakeAccurate();
    $result = $helper->refineResult($trends, 'DRAGON');
    
    echo json_encode([
        'status' => 'success',
        'refined_prediction' => $result
    ]);
}
?>
