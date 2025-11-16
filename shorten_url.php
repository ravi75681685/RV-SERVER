<?php
$config_file = __DIR__ . '/config.json';

// Check config file
if (!file_exists($config_file)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Config file not found']);
    exit;
}

$config = json_decode(file_get_contents($config_file), true);
if ($config === null || !isset($config['arlinks_api']) || !isset($config['api_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid config']);
    exit;
}

// Get long URL
$long_url = isset($_GET['long_url']) ? urldecode($_GET['long_url']) : '';
if (empty($long_url)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No URL provided']);
    exit;
}

// Call ARLinks API
$api_url = $config['arlinks_api'] . '&url=' . urlencode($long_url) . '&alias=v' . substr(md5(time()), 0, 8);

try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('CURL error: ' . curl_error($ch));
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        throw new Exception('API returned HTTP ' . $http_code);
    }
    
    curl_close($ch);
    
    // Validate response
    $json = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response');
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'shortenedUrl' => $json['shortenedUrl'] ?? $json['shortened'] ?? ''
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>