<?php
/**
 * Get Data PHP - Receives trend data from frontend and stores it
 * Dragon Tiger Master Prediction Tool
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Read incoming data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['trends'])) {
    echo json_encode(['status' => 'error', 'message' => 'No trend data received']);
    exit();
}

$trends = $data['trends'];
$trendNumber = isset($data['trendNumber']) ? $data['trendNumber'] : 1;

// Load existing JSON data
$jsonFile = 'get-data.json';
$existingData = json_decode(file_get_contents($jsonFile), true);

// Update current session
$existingData['current_session'] = [
    'trends' => $trends,
    'trend_number' => $trendNumber,
    'timestamp' => date('Y-m-d H:i:s'),
    'result' => '',
    'feedback' => ''
];

$existingData['last_updated'] = date('Y-m-d H:i:s');

// Calculate pattern data
$dragonCount = count(array_filter($trends, function($t) { return $t === 'DRAGON'; }));
$tigerCount = count(array_filter($trends, function($t) { return $t === 'TIGER'; }));

$existingData['patterns']['dragon_frequency'] = $dragonCount;
$existingData['patterns']['tiger_frequency'] = $tigerCount;

// Detect streaks
$streaks = [];
$currentStreak = 1;
for ($i = 1; $i < count($trends); $i++) {
    if ($trends[$i] === $trends[$i-1]) {
        $currentStreak++;
    } else {
        $streaks[] = ['value' => $trends[$i-1], 'length' => $currentStreak];
        $currentStreak = 1;
    }
}
$streaks[] = ['value' => $trends[count($trends)-1], 'length' => $currentStreak];
$existingData['patterns']['streak_data'] = $streaks;

// Check alternating pattern
$alternating = true;
for ($i = 1; $i < count($trends); $i++) {
    if ($trends[$i] === $trends[$i-1]) {
        $alternating = false;
        break;
    }
}
$existingData['patterns']['alternating_count'] = $alternating ? count($trends) : 0;

// Save data
file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT));

// Return success response
echo json_encode([
    'status' => 'success',
    'message' => 'Data received and stored',
    'trends_count' => count($trends),
    'dragon_count' => $dragonCount,
    'tiger_count' => $tigerCount,
    'patterns' => $existingData['patterns']
]);
?>
