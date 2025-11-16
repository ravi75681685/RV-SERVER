<?php
$storage_file = __DIR__ . '/device_verifications.json';

// Log request for debugging
file_put_contents(__DIR__ . '/debug.log', "Request: " . print_r($_GET, true) . "\n", FILE_APPEND);

// Check if file exists and is readable
if (!file_exists($storage_file)) {
    file_put_contents(__DIR__ . '/debug.log', "Error: JSON file does not exist\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'not_verified', 'error' => 'JSON file not found']);
    exit;
}

if (!is_readable($storage_file)) {
    file_put_contents(__DIR__ . '/debug.log', "Error: JSON file not readable\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'not_verified', 'error' => 'JSON file not readable']);
    exit;
}

// Get device ID and app_name from URL
$device_id = isset($_GET['device_id']) ? trim($_GET['device_id']) : null;
$app_name = isset($_GET['app_name']) ? trim($_GET['app_name']) : null;

if (!$device_id || !$app_name) {
    file_put_contents(__DIR__ . '/debug.log', "Error: No device ID or app_name provided\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'not_verified', 'error' => 'No device ID or app_name provided']);
    exit;
}

// Check if device ID exists and is not expired in any app
$data = json_decode(file_get_contents($storage_file), true);
if ($data === null) {
    file_put_contents(__DIR__ . '/debug.log', "Error: Failed to parse JSON file\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'not_verified', 'error' => 'Failed to parse JSON file']);
    exit;
}

$device_verified = false;
$verified_app = null;
$current_time = time();

foreach ($data as $current_app => $devices) {
    if (isset($devices[$device_id])) {
        $expiry_time = strtotime($devices[$device_id]['expiry_time']);
        if ($current_time <= $expiry_time) {
            $device_verified = true;
            $verified_app = $current_app;
            break;
        }
    }
}

if ($device_verified) {
    // If device is verified in another app, copy verification to current app
    if ($verified_app !== $app_name && !isset($data[$app_name][$device_id])) {
        $data[$app_name][$device_id] = $data[$verified_app][$device_id];
        file_put_contents($storage_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    file_put_contents(__DIR__ . '/debug.log', "Success: Device ID $device_id found and not expired for app $verified_app\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'verified']);
} else {
    file_put_contents(__DIR__ . '/debug.log', "Error: Device ID $device_id not found or expired\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'Frist Verification', 'error' => 'Frist Verification']);
}
?>