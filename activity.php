<?php
session_start();

// Check if user is logged in, if not redirect to login with current page as redirect
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: Login.php');
    exit;
}

date_default_timezone_set('Asia/Kolkata');

$storage_file = __DIR__ . '/device_verifications.json';

// Ensure storage file exists
if (!file_exists($storage_file)) {
    file_put_contents($storage_file, json_encode([]));
}

$data = json_decode(file_get_contents($storage_file), true) ?? [];

// Convert old format to new format if needed and ensure consistent structure
$is_old_format = false;
$converted_data = [];

// Check if old format exists (app-specific arrays like 'My Mental', 'NetShare')
foreach ($data as $key => $value) {
    if ($key !== 'Info' && is_array($value)) {
        $is_old_format = true;
        foreach ($value as $device_id => $device_info) {
            $converted_data[$device_id] = $device_info;
        }
    } elseif ($key === 'Info' && is_array($value)) {
        $converted_data = $value;
    }
}

// If old format detected, convert and save
if ($is_old_format) {
    file_put_contents($storage_file, json_encode($converted_data, JSON_PRETTY_PRINT));
    $data = $converted_data;
}

$action_message = '';
if (isset($_GET['action']) && isset($_GET['device_id'])) {
    $device_id = $_GET['device_id'];
    $action = $_GET['action'];
    
    if (isset($data[$device_id])) {
        switch ($action) {
            case 'verify':
                // Extend time by 24 hours from current time
                $new_expiry = date('Y-m-d H:i:s', time() + (24 * 3600));
                $data[$device_id]['expiry_time'] = $new_expiry;
                $data[$device_id]['status'] = 'active';
                file_put_contents($storage_file, json_encode($data, JSON_PRETTY_PRINT));
                $action_message = 'Device verified for 24 hours!';
                // Redirect to active filter after verification
                header('Location: activity.php?filter=active&message=' . urlencode('Device verified for 24 hours!'));
                exit;
                break;
                
            case 'deactivate':
            case 'cancel_verify':
                // Set expiry time to current time - 1 minute (immediately expired)
                $new_expiry = date('Y-m-d H:i:s', time() - 60);
                $data[$device_id]['expiry_time'] = $new_expiry;
                $data[$device_id]['status'] = 'expired';
                file_put_contents($storage_file, json_encode($data, JSON_PRETTY_PRINT));
                $action_message = 'Device verification cancelled!';
                // Redirect to expired filter after cancellation
                header('Location: activity.php?filter=expired&message=' . urlencode('Device verification cancelled!'));
                exit;
                break;
                
            case 'remove_device':
                unset($data[$device_id]);
                file_put_contents($storage_file, json_encode($data, JSON_PRETTY_PRINT));
                $action_message = 'Device removed permanently!';
                // Stay on current page after removal
                header('Location: activity.php?filter=' . urlencode($current_filter) . '&message=' . urlencode('Device removed permanently!'));
                exit;
                break;
        }
    }
    
    // Reload data after changes
    $data = json_decode(file_get_contents($storage_file), true) ?? [];
}

// Check for message from redirect
if (isset($_GET['message'])) {
    $action_message = urldecode($_GET['message']);
}

// Calculate device counts
$total_devices = 0;
$active_devices = 0;
$expired_devices = 0;
$current_time = time();

if (is_array($data)) {
    $total_devices = count($data);
    foreach ($data as $device_id => $device_info) {
        if (isset($device_info['expiry_time'])) {
            $expiry_time = strtotime($device_info['expiry_time']);
            if ($current_time <= $expiry_time) {
                $active_devices++;
            } else {
                $expired_devices++;
            }
        }
    }
}

// Search functionality
$search_results = [];
$search_term = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
if (!empty($search_term) && is_array($data)) {
    foreach ($data as $device_id => $info) {
        $search_fields = [
            $device_id,
            $info['mobile_name'] ?? '',
            $info['app_name'] ?? '',
            $info['model'] ?? '',
            $info['package_name'] ?? ''
        ];
        
        foreach ($search_fields as $field) {
            if (strpos(strtolower($field), $search_term) !== false) {
                $search_results[$device_id] = $info;
                break;
            }
        }
    }
}

$display_data = !empty($search_results) ? $search_results : (is_array($data) ? $data : []);
$current_filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .device-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .device-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .expandable-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease;
        }
        
        .info-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .action-btn {
            transition: all 0.3s ease;
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 20px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .search-box {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 16px;
        }
        
        .filter-tab {
            transition: all 0.3s ease;
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
        }
        
        .filter-tab:hover {
            transform: translateY(-2px);
        }

        .fast-action-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
            font-size: 12px;
        }

        .fast-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(238, 90, 36, 0.4);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-100 min-h-screen">
    <!-- Header -->
    <div class="bg-white/80 backdrop-blur-lg shadow-sm border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-3 rounded-2xl shadow-lg">
                        <i class="fas fa-users-cog text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">User Activity Manager</h1>
                        <p class="text-gray-600 text-sm mt-1">Manage all registered devices and their access</p>
                    </div>
                </div>
                <a href="Dashboard.php" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-8 py-3 rounded-2xl font-semibold flex items-center space-x-3 shadow-lg transition-all duration-300 hover:scale-105">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Action Message -->
    <?php if ($action_message): ?>
    <div class="max-w-7xl mx-auto px-4 mt-6">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-4 rounded-2xl shadow-lg flex items-center space-x-3 animate-pulse">
            <i class="fas fa-check-circle text-2xl"></i>
            <span class="font-semibold text-lg"><?= $action_message ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-6 rounded-2xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-semibold">Active Users</p>
                        <p class="text-3xl font-bold mt-2"><?= $active_devices ?></p>
                    </div>
                    <i class="fas fa-user-check text-3xl opacity-80"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-red-500 to-pink-600 text-white p-6 rounded-2xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-sm font-semibold">Deactivated Users</p>
                        <p class="text-3xl font-bold mt-2"><?= $expired_devices ?></p>
                    </div>
                    <i class="fas fa-user-slash text-3xl opacity-80"></i>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-2xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-semibold">Total Users</p>
                        <p class="text-3xl font-bold mt-2"><?= $total_devices ?></p>
                    </div>
                    <i class="fas fa-users text-3xl opacity-80"></i>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-box p-6 shadow-lg mb-8">
            <form method="GET" class="flex gap-4 items-center">
                <?php if ($current_filter !== 'all'): ?>
                    <input type="hidden" name="filter" value="<?= $current_filter ?>">
                <?php endif; ?>
                <div class="flex-1">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>" 
                               placeholder="Search devices by ID, name, or app..." 
                               class="w-full pl-12 pr-6 py-4 bg-white/50 border border-gray-200 rounded-2xl text-gray-900 placeholder-gray-500 text-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300">
                    </div>
                </div>
                <button type="submit" class="bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white px-8 py-4 rounded-2xl font-semibold flex items-center space-x-3 shadow-lg transition-all duration-300 hover:scale-105">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </button>
                <?php if (!empty($search_term) || $current_filter !== 'active'): ?>
                    <a href="activity.php" class="bg-gray-200 hover:bg-gray-300 px-6 py-4 rounded-2xl text-gray-700 font-semibold flex items-center space-x-3 transition-all duration-300 hover:scale-105">
                        <i class="fas fa-times"></i>
                        <span>Clear</span>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filter Tabs -->
        <div class="flex flex-wrap gap-3 mb-8">
            <a href="?filter=active" class="filter-tab <?= $current_filter === 'active' ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg' : 'bg-white text-gray-700 shadow-md hover:shadow-lg' ?>">
                <i class="fas fa-user-check mr-2"></i>Active (<?= $active_devices ?>)
            </a>
            <a href="?filter=expired" class="filter-tab <?= $current_filter === 'expired' ? 'bg-gradient-to-r from-red-500 to-pink-600 text-white shadow-lg' : 'bg-white text-gray-700 shadow-md hover:shadow-lg' ?>">
                <i class="fas fa-user-slash mr-2"></i>Deactivated (<?= $expired_devices ?>)
            </a>
            <a href="?filter=all" class="filter-tab <?= $current_filter === 'all' ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg' : 'bg-white text-gray-700 shadow-md hover:shadow-lg' ?>">
                <i class="fas fa-users mr-2"></i>All Users (<?= $total_devices ?>)
            </a>
        </div>

        <!-- Devices List -->
        <div class="space-y-6">
            <?php 
            $filtered_data = [];
            foreach ($display_data as $device_id => $info) {
                if (isset($info['expiry_time'])) {
                    $is_expired = time() > strtotime($info['expiry_time']);
                    
                    $include = false;
                    switch ($current_filter) {
                        case 'active': $include = !$is_expired; break;
                        case 'expired': $include = $is_expired; break;
                        default: $include = true; break;
                    }
                    
                    if ($include) {
                        $filtered_data[$device_id] = $info;
                    }
                }
            }
            ?>
            
            <?php if (empty($filtered_data)): ?>
                <div class="text-center py-16">
                    <div class="bg-white/80 backdrop-blur-lg rounded-3xl p-12 shadow-lg max-w-2xl mx-auto">
                        <i class="fas fa-search text-gray-300 text-8xl mb-6"></i>
                        <h3 class="text-2xl font-bold text-gray-700 mb-3">No devices found</h3>
                        <p class="text-gray-500 text-lg mb-6">Try changing your search or filter criteria</p>
                        <a href="activity.php" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-8 py-3 rounded-2xl font-semibold inline-flex items-center space-x-2 shadow-lg transition-all duration-300 hover:scale-105">
                            <i class="fas fa-refresh"></i>
                            <span>Reset Filters</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="device-card p-1">
                    <div class="bg-white/90 backdrop-blur-lg rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-4">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-3 rounded-xl shadow-lg">
                                    <i class="fas fa-mobile-alt text-white text-2xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-800">All Registered Devices</h2>
                                    <p class="text-gray-600 text-sm"><?= count($filtered_data) ?> device<?= count($filtered_data) > 1 ? 's' : '' ?> found</p>
                                </div>
                            </div>
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2 rounded-full text-sm font-semibold">
                                <?= count($filtered_data) ?> Device<?= count($filtered_data) > 1 ? 's' : '' ?>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <?php foreach ($filtered_data as $device_id => $info): ?>
                                <?php
                                $is_expired = time() > strtotime($info['expiry_time']);
                                $status_class = $is_expired ? 'bg-gradient-to-r from-red-500 to-pink-600' : 'bg-gradient-to-r from-green-500 to-emerald-600';
                                $status_text = $is_expired ? 'Deactivated' : 'Active';
                                ?>
                                
                                <div class="info-card p-1">
                                    <div class="bg-white rounded-2xl p-1">
                                        <div class="p-6 flex items-center justify-between cursor-pointer hover:bg-gray-50 rounded-2xl transition-all duration-300"
                                             onclick="toggleDetails('<?= $device_id ?>')">
                                            <div class="flex items-center space-x-4">
                                                <span class="<?= $status_class ?> text-white status-badge shadow-lg">
                                                    <i class="fas <?= $is_expired ? 'fa-times-circle' : 'fa-check-circle' ?> mr-1"></i>
                                                    <?= $status_text ?>
                                                </span>
                                                <div>
                                                    <div class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($info['mobile_name'] ?? 'Unknown Device') ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?= substr($device_id, 0, 12) ?>... | App: <?= htmlspecialchars($info['app_name'] ?? 'Unknown App') ?></div>
                                                </div>
                                            </div>
                                            <div class="text-right flex items-center">
                                                <div>
                                                    <div class="text-lg font-bold text-gray-900">v<?= htmlspecialchars($info['app_version'] ?? '1.0') ?></div>
                                                    <div class="text-sm text-gray-500">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        <?= date('M j, g:i A', strtotime($info['expiry_time'])) ?>
                                                    </div>
                                                </div>
                                                <?php if (!$is_expired): ?>
                                                    <button onclick="event.stopPropagation(); if(confirm('Are you sure you want to cancel verification for this device?')) { window.location.href='?action=cancel_verify&device_id=<?= $device_id ?>'; }" 
                                                            class="fast-action-btn ml-4">
                                                        <i class="fas fa-ban mr-1"></i>Expire Fast
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="expandable-details bg-gray-50/50 rounded-2xl m-4" id="details-<?= $device_id ?>">
                                            <div class="p-6">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                                    <div class="bg-white p-4 rounded-xl border border-gray-200">
                                                        <div class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">MOBILE INFO</div>
                                                        <div class="text-gray-900 font-semibold text-lg"><?= htmlspecialchars($info['mobile_name'] ?? 'Unknown Device') ?></div>
                                                        <div class="text-gray-600 text-sm"><?= htmlspecialchars($info['model'] ?? 'Unknown Model') ?></div>
                                                    </div>
                                                    
                                                    <div class="bg-white p-4 rounded-xl border border-gray-200">
                                                        <div class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">APP INFO</div>
                                                        <div class="text-gray-900 font-semibold text-lg"><?= htmlspecialchars($info['app_name'] ?? 'Unknown App') ?></div>
                                                        <div class="text-gray-600 text-sm">v<?= htmlspecialchars($info['app_version'] ?? '1.0') ?> | <?= htmlspecialchars($info['package_name'] ?? 'Unknown Package') ?></div>
                                                    </div>
                                                    
                                                    <div class="bg-white p-4 rounded-xl border border-gray-200">
                                                        <div class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">TIME INFO</div>
                                                        <div class="text-gray-900 text-sm mb-1">
                                                            <i class="fas fa-plus-circle mr-1 text-green-500"></i>
                                                            Created: <?= date('M j, g:i A', strtotime($info['created_at'] ?? 'now')) ?>
                                                        </div>
                                                        <div class="text-gray-900 text-sm">
                                                            <i class="fas fa-clock mr-1 <?= $is_expired ? 'text-red-500' : 'text-blue-500' ?>"></i>
                                                            Expires: <?= date('M j, g:i A', strtotime($info['expiry_time'])) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    <?php if ($is_expired): ?>
                                                        <!-- Verify Button for Expired Devices -->
                                                        <a href="?action=verify&device_id=<?= $device_id ?>&filter=active" 
                                                           class="action-btn bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white text-center">
                                                           <i class="fas fa-check-circle mr-2"></i>Verify (24 Hours)
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- Cancel Verification Button for Active Devices -->
                                                        <a href="?action=cancel_verify&device_id=<?= $device_id ?>&filter=expired" 
                                                           onclick="return confirm('Are you sure you want to cancel verification for this device?')"
                                                           class="action-btn bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-center">
                                                           <i class="fas fa-ban mr-2"></i>Cancel Verification
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Remove Device Permanently (Only for expired devices) -->
                                                    <?php if ($is_expired): ?>
                                                        <a href="?action=remove_device&device_id=<?= $device_id ?>" 
                                                           onclick="return confirm('⚠️ WARNING: This will permanently remove the device. This action cannot be undone!')"
                                                           class="action-btn bg-gradient-to-r from-gray-500 to-gray-700 hover:from-gray-600 hover:to-gray-800 text-white text-center">
                                                           <i class="fas fa-trash-alt mr-2"></i>Remove Permanently
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDetails(deviceId) {
            const element = document.getElementById('details-' + deviceId);
            const allDetails = document.querySelectorAll('.expandable-details');
            
            // Close all other details
            allDetails.forEach(detail => {
                if (detail.id !== 'details-' + deviceId) {
                    detail.style.maxHeight = '0px';
                }
            });
            
            // Toggle current detail
            if (element.style.maxHeight && element.style.maxHeight !== '0px') {
                element.style.maxHeight = '0px';
            } else {
                element.style.maxHeight = element.scrollHeight + 'px';
            }
        }

        // Initialize all details to be closed
        document.querySelectorAll('.expandable-details').forEach(detail => {
            detail.style.maxHeight = '0px';
            detail.style.transition = 'max-height 0.5s ease';
        });
    </script>
</body>
</html>