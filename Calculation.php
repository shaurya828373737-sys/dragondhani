<?php
/**
 * Calculation.php - Core calculation engine
 * Dragon Tiger Master Prediction Tool
 */

class Calculation {
    
    /**
     * Analyze pattern from trends array
     */
    public function analyzePattern($trends) {
        if (empty($trends)) return 'DRAGON';
        
        $dragonCount = count(array_filter($trends, function($t) {
            return $t === 'DRAGON';
        }));
        $tigerCount = count($trends) - $dragonCount;
        $total = count($trends);

        
        // Calculate ratios
        $dragonRatio = $dragonCount / $total;
        $tigerRatio = $tigerCount / $total;
        
        // Check last 3 for streak
        $lastThree = array_slice($trends, -3);
        $allSame = (count(array_unique($lastThree)) === 1);
        
        if ($allSame) {
            // Reversal prediction
            return $lastThree[0] === 'DRAGON' ? 'TIGER' : 'DRAGON';
        }
        
        // Check alternating pattern
        $alternating = true;
        for ($i = 1; $i < $total; $i++) {
            if ($trends[$i] === $trends[$i-1]) {
                $alternating = false;
                break;
            }
        }
        
        if ($alternating) {
            $last = $trends[$total - 1];
            return $last === 'DRAGON' ? 'TIGER' : 'DRAGON';
        }
        
        // Frequency-based prediction (counter-trend)
        if ($dragonRatio > 0.6) return 'TIGER';
        if ($tigerRatio > 0.6) return 'DRAGON';
        
        // Default: predict opposite of last
        $last = $trends[$total - 1];
        return $last === 'DRAGON' ? 'TIGER' : 'DRAGON';
    }
    
    /**
     * Calculate weighted score
     */
    public function weightedScore($trends) {
        $total = count($trends);
        $score = 0;
        
        for ($i = 0; $i < $total; $i++) {
            $weight = ($i + 1) / $total; // More recent = higher weight
            if ($trends[$i] === 'DRAGON') {
                $score -= $weight;
            } else {
                $score += $weight;
            }
        }
        
        return $score;
    }
    
    /**
     * Probability matrix calculation
     */
    public function probabilityMatrix($trends) {
        $transitions = [
            'DD' => 0, 'DT' => 0,
            'TD' => 0, 'TT' => 0
        ];
        
        for ($i = 0; $i < count($trends) - 1; $i++) {
            $key = substr($trends[$i], 0, 1) . substr($trends[$i+1], 0, 1);
            $transitions[$key]++;
        }
        
        $last = $trends[count($trends) - 1];
        
        if ($last === 'DRAGON') {
            $total = $transitions['DD'] + $transitions['DT'];
            if ($total === 0) return 'TIGER';
            return ($transitions['DT'] >= $transitions['DD']) ? 'TIGER' : 'DRAGON';
        } else {
            $total = $transitions['TD'] + $transitions['TT'];
            if ($total === 0) return 'DRAGON';
            return ($transitions['TD'] >= $transitions['TT']) ? 'DRAGON' : 'TIGER';
        }
    }
}
?>
