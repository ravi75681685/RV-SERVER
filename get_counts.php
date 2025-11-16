<?php
$storage_file = __DIR__ . '/device_verifications.json';
$history_file = __DIR__ . '/history.json';

$counts = [];

if (file_exists($storage_file)) {
    $data = json_decode(file_get_contents($storage_file), true);
    $current_time = time();
    foreach ($data as $app_name => $devices) {
        $counts[$app_name] = [
            'total_users' => 0,
            'active_users' => 0
        ];
        foreach ($devices as $device_id => $info) {
            $created_at = strtotime($info['created_at']);
            $expiry_time = strtotime($info['expiry_time']);
            if ($current_time <= $expiry_time) {
                $counts[$app_name]['total_users']++;
                if ($current_time - $created_at < 120) {
                    $counts[$app_name]['active_users']++;
                }
            }
        }
    }
}

if (file_exists($history_file)) {
    $history = json_decode(file_get_contents($history_file), true);
    foreach ($history as $app_name => $app_history) {
        $counts[$app_name]['expired_users'] = 0;
        foreach ($app_history as $date => $info) {
            $counts[$app_name]['expired_users'] += count($info['deletions']);
        }
    }
}

header('Content-Type: application/json');
echo json_encode($counts);
?>