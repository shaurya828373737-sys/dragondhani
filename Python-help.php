<?php
/**
 * Python-help.php - Helper utilities for Python algorithm
 * Provides supporting functions for pattern detection
 * Dragon Tiger Master Prediction Tool
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class PythonHelper {
    
    /**
     * Detect zigzag pattern (alternating DRAGON/TIGER)
     */
    public static function detectZigzag($trends) {
        if (count($trends) < 3) return false;
        
        $zigzagCount = 0;
        for ($i = 1; $i < count($trends); $i++) {
            if ($trends[$i] !== $trends[$i-1]) {
                $zigzagCount++;
            }
        }
        
        return $zigzagCount >= (count($trends) - 1) * 0.7;
    }
    
    /**
     * Detect dominant trend
     */
    public static function getDominantTrend($trends) {
        $dragonCount = count(array_filter($trends, function($t) { return $t === 'DRAGON'; }));
        $tigerCount = count($trends) - $dragonCount;
        
        if ($dragonCount > $tigerCount) return 'DRAGON';
        if ($tigerCount > $dragonCount) return 'TIGER';
        return 'EQUAL';
    }
    
    /**
     * Calculate streak length from end
     */
    public static function getEndStreak($trends) {
        if (empty($trends)) return ['value' => null, 'length' => 0];
        
        $last = $trends[count($trends) - 1];
        $streak = 1;
        
        for ($i = count($trends) - 2; $i >= 0; $i--) {
            if ($trends[$i] === $last) {
                $streak++;
            } else {
                break;
            }
        }
        
        return ['value' => $last, 'length' => $streak];
    }
    
    /**
     * Find repeating sub-patterns
     */
    public static function findSubPattern($trends) {
        $len = count($trends);
        if ($len < 4) return null;
        
        // Check for 2-length pattern
        $pattern2 = array_slice($trends, 0, 2);
        $matches2 = 0;
        for ($i = 0; $i < $len - 1; $i += 2) {
            if ($trends[$i] === $pattern2[0] && isset($trends[$i+1]) && $trends[$i+1] === $pattern2[1]) {
                $matches2++;
            }
        }
        if ($matches2 >= 3) {
            return ['type' => 'repeating_2', 'pattern' => $pattern2, 'next' => $pattern2[0]];
        }
        
        // Check for 3-length pattern
        if ($len >= 6) {
            $pattern3 = array_slice($trends, 0, 3);
            $matches3 = 0;
            for ($i = 0; $i < $len - 2; $i += 3) {
                if ($trends[$i] === $pattern3[0] && 
                    isset($trends[$i+1]) && $trends[$i+1] === $pattern3[1] &&
                    isset($trends[$i+2]) && $trends[$i+2] === $pattern3[2]) {
                    $matches3++;
                }
            }
            if ($matches3 >= 2) {
                $nextIndex = $len % 3;
                return ['type' => 'repeating_3', 'pattern' => $pattern3, 'next' => $pattern3[$nextIndex]];
            }
        }
        
        return null;
    }
    
    /**
     * Calculate probability based on Bayesian inference
     */
    public static function bayesianProbability($trends) {
        $total = count($trends);
        if ($total === 0) return ['dragon' => 0.5, 'tiger' => 0.5];
        
        $dragonCount = count(array_filter($trends, function($t) { return $t === 'DRAGON'; }));
        $tigerCount = $total - $dragonCount;
        
        // Prior: 0.5 each (fair game)
        $priorDragon = 0.5;
        $priorTiger = 0.5;
        
        // Likelihood based on frequency
        $likelihoodDragon = ($dragonCount + 1) / ($total + 2); // Laplace smoothing
        $likelihoodTiger = ($tigerCount + 1) / ($total + 2);
        
        // Posterior
        $posteriorDragon = $priorDragon * $likelihoodDragon;
        $posteriorTiger = $priorTiger * $likelihoodTiger;
        
        // Normalize
        $sum = $posteriorDragon + $posteriorTiger;
        
        return [
            'dragon' => round($posteriorDragon / $sum, 4),
            'tiger' => round($posteriorTiger / $sum, 4)
        ];
    }
    
    /**
     * Get analysis summary
     */
    public static function getAnalysisSummary($trends) {
        return [
            'zigzag' => self::detectZigzag($trends),
            'dominant' => self::getDominantTrend($trends),
            'end_streak' => self::getEndStreak($trends),
            'sub_pattern' => self::findSubPattern($trends),
            'probability' => self::bayesianProbability($trends)
        ];
    }
}

// API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $trends = isset($input['trends']) ? $input['trends'] : [];
    
    echo json_encode([
        'status' => 'success',
        'analysis' => PythonHelper::getAnalysisSummary($trends)
    ]);
}
?>
