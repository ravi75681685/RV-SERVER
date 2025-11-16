<?php
$storage_file = __DIR__ . '/device_verifications.json';

// Log request for debugging
file_put_contents(__DIR__ . '/debug.log', "Delete Request: " . print_r($_GET, true) . "\n", FILE_APPEND);

// Check if file exists and is writable
if (!file_exists($storage_file)) {
    file_put_contents(__DIR__ . '/debug.log', "Error: JSON file does not exist\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'error' => 'JSON file not found']);
    exit;
}

if (!is_writable($storage_file)) {
    file_put_contents(__DIR__ . '/debug.log', "Error: JSON file not writable\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'error' => 'JSON file not writable']);
    exit;
}

// Get device ID and app_name from URL
$device_id = isset($_GET['device_id']) ? trim($_GET['device_id']) : null;
$app_name = isset($_GET['app_name']) ? trim($_GET['app_name']) : null;

if (!$device_id || !$app_name) {
    file_put_contents(__DIR__ . '/debug.log', "Error: No device ID or app_name provided for deletion\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'error' => 'No device ID or app_name provided']);
    exit;
}

// Load JSON file
$data = json_decode(file_get_contents($storage_file), true);

if ($data === null) {
    file_put_contents(__DIR__ . '/debug.log', "Error: Failed to parse JSON file\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'error' => 'Failed to parse JSON file']);
    exit;
}

// Delete device ID if exists
if (isset($data[$app_name][$device_id])) {
    unset($data[$app_name][$device_id]);
    if (file_put_contents($storage_file, json_encode($data, JSON_PRETTY_PRINT))) {
        file_put_contents(__DIR__ . '/debug.log', "Success: Device ID $device_id deleted for app $app_name\n", FILE_APPEND);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'deleted']);
    } else {
        file_put_contents(__DIR__ . '/debug.log', "Error: Failed to save JSON file after deletion\n", FILE_APPEND);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'error' => 'Failed to save JSON file']);
    }
} else {
    file_put_contents(__DIR__ . '/debug.log', "Error: Device ID $device_id not found for app $app_name\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'error' => 'Device ID not found']);
}
?>