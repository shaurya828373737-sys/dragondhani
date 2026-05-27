<?php
/**
 * Calculation.php - Advanced calculation engine
 * Used as fallback by Master-prediction-tool.php
 */

class Calculation {

    public function analyzePattern($trends) {
        if (empty($trends)) return 'DRAGON';

        $total = count($trends);
        $last = $trends[$total - 1];

        // Markov transition
        $trans = ['DRAGON' => ['DRAGON' => 0, 'TIGER' => 0], 'TIGER' => ['DRAGON' => 0, 'TIGER' => 0]];
        for ($i = 0; $i < $total - 1; $i++) {
            $trans[$trends[$i]][$trends[$i + 1]]++;
        }

        $fromLast = $trans[$last];
        $totalT = $fromLast['DRAGON'] + $fromLast['TIGER'];

        if ($totalT > 0) {
            return ($fromLast['DRAGON'] >= $fromLast['TIGER']) ? 'DRAGON' : 'TIGER';
        }

        // Streak check
        $streak = 1;
        for ($i = $total - 2; $i >= 0; $i--) {
            if ($trends[$i] === $last) $streak++;
            else break;
        }

        if ($streak >= 4) return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
        if ($streak <= 2) return $last;

        return ($last === 'DRAGON') ? 'TIGER' : 'DRAGON';
    }
}
?>
