<?php
session_start();

// Check if user is logged in, if not redirect to login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: Login.php');
    exit;
}

// Rest of your existing Dashboard.php code...
// [Previous content remains exactly the same after this line]
$storage_file = __DIR__ . '/device_verifications.json';
$config_file = __DIR__ . '/config.json';
$updates_file = __DIR__ . '/updates.json';

$counts = [];
$config = ['arlinks_api' => 'https://arlinks.in/api', 'api_token' => ''];

// Load config
if (file_exists($config_file)) {
    $config_data = json_decode(file_get_contents($config_file), true);
    if ($config_data !== null) $config = $config_data;
}

// Handle config update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
    $new_api_url = $_POST['api_url'] ?? '';
    $new_api_token = $_POST['api_token'] ?? '';
    
    if ($new_api_url && $new_api_token) {
        $config_data = [
            'arlinks_api' => $new_api_url . (strpos($new_api_url, '?api=') === false ? '?api=' . $new_api_token : ''),
            'api_token' => $new_api_token
        ];
        file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT));
        $config = $config_data;
    }
}

// Load device data
if (file_exists($storage_file) && is_readable($storage_file)) {
    $data = json_decode(file_get_contents($storage_file), true);
    if ($data !== null) {
        $current_time = time();
        $modified = false;
        
        foreach ($data as $app_name => $devices) {
            $counts[$app_name] = ['total_users' => 0, 'active_users' => 0, 'expired_users' => 0];
            
            foreach ($devices as $device_id => $info) {
                $created_at = strtotime($info['created_at']);
                $expiry_time = strtotime($info['expiry_time']);
                
                if ($current_time - $created_at < 120) $counts[$app_name]['active_users']++;
                if ($current_time - $created_at < 86400) $counts[$app_name]['total_users']++;
                if ($current_time > $expiry_time) $counts[$app_name]['expired_users']++;
            }
            
            if ($counts[$app_name]['total_users'] == 0 && $counts[$app_name]['active_users'] == 0 && $counts[$app_name]['expired_users'] == 0) {
                unset($data[$app_name]);
                $modified = true;
                unset($counts[$app_name]);
            }
        }
        
        if ($modified) file_put_contents($storage_file, json_encode($data, JSON_PRETTY_PRINT));
    } else {
        $error = 'Failed to parse device_verifications.json';
    }
} else {
    $error = 'device_verifications.json not found or unreadable';
}

// Update System Functions
if (!file_exists($updates_file)) file_put_contents($updates_file, json_encode(new stdClass()));

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $updates = json_decode(file_get_contents($updates_file), true) ?: [];
    if (isset($updates[$_GET['delete']])) {
        unset($updates[$_GET['delete']]);
        file_put_contents($updates_file, json_encode($updates, JSON_PRETTY_PRINT));
        echo "<script>alert('Update deleted!');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_submit'])) {
    $required = ['title', 'note', 'package_name', 'version_code'];
    if (!array_diff($required, array_keys($_POST))) {
        $updates = json_decode(file_get_contents($updates_file), true) ?: [];
        $updates[$_POST['package_name']] = [
            'title' => $_POST['title'],
            'note' => $_POST['note'],
            'url' => $_POST['url'] ?? '',
            'force_update' => isset($_POST['force_update']),
            'version_code' => $_POST['version_code'],
            'timestamp' => time()
        ];
        file_put_contents($updates_file, json_encode($updates, JSON_PRETTY_PRINT));
        echo "<script>alert('Update saved!');</script>";
    } else {
        $update_error = "Missing required fields";
    }
}

$all_updates = json_decode(file_get_contents($updates_file), true) ?: [];
$current_update = [];
if (isset($_GET['package_preview']) && isset($all_updates[$_GET['package_preview']])) {
    $current_update = $all_updates[$_GET['package_preview']];
    $current_update['package_name'] = $_GET['package_preview'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEMO DASHBOARD</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Orbitron', sans-serif; 
            background: #0a0a16; 
            color: white; 
            min-height: 100vh; 
            position: relative; 
            overflow-x: hidden;
        }
        
        /* Background Effects */
        .cyber-bg { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            z-index: -1;
            background: radial-gradient(circle at 20% 30%, rgba(0,255,255,0.05) 0%, transparent 25%),
                       radial-gradient(circle at 80% 70%, rgba(255,0,255,0.05) 0%, transparent 25%),
                       linear-gradient(to bottom, #0a0a16, #000);
        }
        .cyber-grid {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(rgba(0,255,255,0.1) 1px, transparent 1px),
                             linear-gradient(90deg, rgba(0,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px; 
            animation: gridMove 20s linear infinite;
        }
        @keyframes gridMove {
            0% { transform: translateY(0) translateX(0); }
            100% { transform: translateY(-50px) translateX(-50px); }
        }
        
        /* Main Container */
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
            position: relative; 
            z-index: 1;
        }
        h1 { 
            text-align: center; 
            margin: 20px 0; 
            font-size: 2rem; 
            color: #00ffff; 
            text-shadow: 0 0 10px #00ffff;
            position: sticky;
            top: 0;
            background: #0a0a16;
            padding: 10px;
            z-index: 2;
        }
        
        /* App Cards Container */
        .apps-container {
            max-height: calc(100vh - 180px);
            overflow-y: auto;
            padding: 10px;
            margin-top: 10px;
        }
        
        /* App Cards Grid */
        .apps-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            gap: 15px; 
        }
        
        /* App Card */
        .app-card { 
            background: rgba(10,10,30,0.7); 
            border: 1px solid #00ffff; 
            border-radius: 10px; 
            padding: 15px;
            height: 100%;
            box-shadow: 0 0 15px rgba(0,255,255,0.2);
        }
        .app-card h2 { 
            font-size: 1.2rem; 
            margin-bottom: 10px; 
            color: #00ffff; 
            text-align: center;
        }
        
        /* Buttons */
        .action-btn {
            background: linear-gradient(45deg, #ff00ff, #00ffff);
            color: white; 
            border: none; 
            padding: 8px 15px; 
            border-radius: 5px;
            font-family: 'Orbitron', sans-serif; 
            cursor: pointer; 
            transition: all 0.3s;
            font-weight: bold; 
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-top: 10px;
        }
        .action-btn:hover { 
            transform: scale(1.03); 
            box-shadow: 0 0 15px rgba(0,255,255,0.5);
        }
        
        /* Modals */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.8); 
            z-index: 100; 
            justify-content: center; 
            align-items: center;
        }
        .modal-content { 
            background: #0a0a20; 
            border: 2px solid var(--border-color); 
            border-radius: 10px;
            padding: 20px; 
            width: 90%; 
            max-width: 500px; 
            animation: modalZoomOut 0.3s ease-out;
            box-shadow: 0 0 30px var(--shadow); 
            position: relative; 
            max-height: 90vh; 
            overflow-y: auto;
        }
        @keyframes modalZoomOut {
            0% { transform: scale(1.2); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .close-btn {
            position: absolute; 
            top: 10px; 
            right: 10px; 
            background: #ff0000; 
            color: white;
            border: none; 
            width: 30px; 
            height: 30px; 
            border-radius: 50%; 
            font-weight: bold;
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .close-btn:hover { background: #ff5555; }
        
        /* Stats Modal */
        .stats-container { display: grid; gap: 10px; }
        .stat-box { 
            background: rgba(0,0,30,0.7); 
            border: 1px solid #444; 
            border-radius: 8px; 
            padding: 12px; 
            text-align: center; 
        }
        .stat-box h3 { margin-bottom: 8px; color: #aaa; font-size: 0.9rem; }
        .stat-box p { font-size: 1.5rem; font-weight: bold; }
        .total-users { color: #00ffff; }
        .active-users { color: #00ff00; }
        .expired-users { color: #ff0000; }
        
        /* Update Modal */
        .update-form { display: flex; flex-direction: column; gap: 8px; }
        .update-form label { color: #ff9900; font-size: 0.9rem; }
        .update-form input, .update-form textarea {
            padding: 8px; 
            border: 1px solid #ff9900; 
            border-radius: 5px;
            background: rgba(0,0,30,0.7); 
            color: white; 
            font-family: 'Orbitron', sans-serif;
        }
        .update-form textarea { min-height: 80px; }
        
        /* Active Updates Button */
        .active-updates-btn {
            background: linear-gradient(45deg, #00ccff, #0066ff);
            color: white; 
            border: none; 
            padding: 10px 15px; 
            border-radius: 5px;
            font-family: 'Orbitron', sans-serif; 
            cursor: pointer; 
            margin-top: 15px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .active-updates-btn:hover { 
            background: linear-gradient(45deg, #0066ff, #00ccff);
        }
        
        /* Active Updates Popup */
        .updates-popup {
            position: fixed; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%);
            background: #0a0a20; 
            border: 2px solid #00ccff; 
            border-radius: 10px;
            width: 90%; 
            max-width: 500px; 
            max-height: 70vh; 
            overflow-y: auto;
            padding: 20px; 
            z-index: 1000; 
            display: none;
            box-shadow: 0 0 30px rgba(0, 204, 255, 0.5);
            animation: modalZoomOut 0.3s ease-out;
        }
        .updates-popup h3 {
            text-align: center; 
            color: #00ccff; 
            margin-bottom: 15px;
            font-size: 1.2rem; 
            border-bottom: 1px solid #00ccff;
            padding-bottom: 10px;
        }
        .update-item { 
            padding: 10px; 
            margin-bottom: 10px; 
            background: rgba(0,0,30,0.7);
            border: 1px solid #444; 
            border-radius: 5px;
        }
        .update-item h4 { color: #00ccff; font-size: 1rem; margin-bottom: 5px; }
        .update-item p { font-size: 0.8rem; margin: 5px 0; }
        .update-actions { display: flex; gap: 8px; margin-top: 8px; }
        .update-actions a { 
            padding: 5px 10px; 
            border-radius: 3px; 
            font-size: 0.8rem; 
            text-decoration: none; 
            color: white;
        }
        .edit-btn { background: #0099ff; }
        .delete-btn { background: #ff3333; }
        
        /* Overlay */
        .overlay {
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            background: rgba(0,0,0,0.8); 
            z-index: 999; 
            display: none;
        }
        
        /* Fixed Action Buttons */
        .fixed-btn {
            position: fixed; 
            right: 20px; 
            background: linear-gradient(45deg, var(--color1), var(--color2));
            color: white; 
            border: none; 
            padding: 8px 15px; 
            border-radius: 5px;
            font-family: 'Orbitron', sans-serif; 
            cursor: pointer; 
            z-index: 10;
            box-shadow: 0 0 15px var(--shadow); 
            transition: all 0.3s;
        }
        .fixed-btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 0 20px var(--shadow); 
        }
        
        /* Logout Button */
        .logout-btn {
            background: linear-gradient(45deg, #ff3333, #ff6600);
            --shadow: rgba(255,51,51,0.3);
        }
        
        @media (max-width: 768px) {
            .apps-grid { grid-template-columns: 1fr; }
            h1 { font-size: 1.5rem; }
            .fixed-btn { 
                padding: 6px 12px; 
                font-size: 0.8rem; 
                right: 10px;
            }
            .modal-content {
                width: 95%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="cyber-bg"><div class="cyber-grid"></div></div>
    
    <button class="fixed-btn" style="--color1: #ff00ff; --color2: #00ffff; --shadow: rgba(0,255,255,0.3); top: 20px;" onclick="showConfigModal()">⚙️ Config</button>
    <button class="fixed-btn" style="--color1: #ff9900; --color2: #ff6600; --shadow: rgba(255,153,0,0.3); top: 60px;" onclick="showUpdateModal()">🔄 Updates</button>
    <button class="fixed-btn logout-btn" style="top: 100px;" onclick="window.location.href='Logout.php'">🚪 Logout</button>
    
    <div class="container">
        <h1>DEMO DASHBOARD</h1>
        
        <div class="apps-container">
            <?php if (isset($error)): ?>
                <div style="color: #ff0000; text-align: center; margin: 20px 0;">Error: <?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <div class="apps-grid">
                    <?php foreach ($counts as $app_name => $stats): ?>
                        <div class="app-card">
                            <h2><?= htmlspecialchars($app_name) ?></h2>
                            <button class="action-btn" onclick="showStats('<?= htmlspecialchars($app_name) ?>', <?= $stats['total_users'] ?>, <?= $stats['active_users'] ?>, <?= $stats['expired_users'] ?>)">
                                🔍 View Stats
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Modal -->
    <div id="statsModal" class="modal">
        <div class="modal-content" style="--border-color: #00ffff; --shadow: rgba(0,255,255,0.5)">
            <button class="close-btn" onclick="closeModal()">✕</button>
            <h2 style="text-align: center; margin-bottom: 15px; color: #00ffff;" id="modalAppName">APP STATS</h2>
            <div class="stats-container">
                <div class="stat-box">
                    <h3>📊 TOTAL USERS (24h)</h3>
                    <p class="total-users" id="totalUsers">0</p>
                </div>
                <div class="stat-box">
                    <h3>🟢 ACTIVE USERS</h3>
                    <p class="active-users" id="activeUsers">0</p>
                </div>
                <div class="stat-box">
                    <h3>🔴 EXPIRED USERS</h3>
                    <p class="expired-users" id="expiredUsers">0</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Config Modal -->
    <div id="configModal" class="modal">
        <div class="modal-content" style="--border-color: #00ffff; --shadow: rgba(0,255,255,0.5)">
            <button class="close-btn" onclick="closeModal()">✕</button>
            <h2 style="text-align: center; margin-bottom: 15px; color: #00ffff;">⚙️ Token Config</h2>
            <form method="POST" class="update-form">
                <label for="api_url">API URL:</label>
                <input type="text" id="api_url" name="api_url" value="<?= htmlspecialchars($config['arlinks_api'] ?? 'https://arlinks.in/api') ?>" required>
                
                <label for="api_token">API Token:</label>
                <input type="text" id="api_token" name="api_token" value="<?= htmlspecialchars($config['api_token'] ?? '') ?>" required>
                
                <button type="submit" name="update_config" class="action-btn" style="margin-top: 10px;">Update Config</button>
            </form>
        </div>
    </div>
    
    <!-- Update System Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content" style="--border-color: #ff9900; --shadow: rgba(255,153,0,0.5); max-width: 500px;">
            <button class="close-btn" onclick="closeModal()">✕</button>
            <h2 style="text-align: center; margin-bottom: 15px; color: #ff9900;">🔄 Update System</h2>
            
            <form method="POST" class="update-form">
                <input type="hidden" name="update_submit" value="1">
                <label for="package_name">Package Name:</label>
                <input type="text" id="package_name" name="package_name" value="<?= htmlspecialchars($current_update['package_name'] ?? '') ?>" required>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label for="version_code">Version Code:</label>
                        <input type="text" id="version_code" name="version_code" value="<?= htmlspecialchars($current_update['version_code'] ?? '') ?>" required>
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" id="force_update" name="force_update" <?= ($current_update['force_update'] ?? false) ? 'checked' : '' ?>>
                        <label for="force_update">Force Update</label>
                    </div>
                </div>
                
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($current_update['title'] ?? '') ?>" required>
                
                <label for="note">Update Note:</label>
                <textarea id="note" name="note" required><?= htmlspecialchars($current_update['note'] ?? '') ?></textarea>
                
                <label for="url">Download URL:</label>
                <input type="url" id="url" name="url" value="<?= htmlspecialchars($current_update['url'] ?? '') ?>">
                
                <button type="submit" class="action-btn" style="background: linear-gradient(45deg, #ff9900, #ff6600); margin-top: 5px;">Save Update</button>
                
                <?php if (isset($update_error)): ?>
                    <div style="color: #ff0000; text-align: center; font-size: 0.8rem;"><?= htmlspecialchars($update_error) ?></div>
                <?php endif; ?>
            </form>
            
            <button class="active-updates-btn" onclick="showActiveUpdates()">
                📋 Active Updates (<?= count($all_updates) ?>)
            </button>
        </div>
    </div>
    
    <!-- Active Updates Popup -->
    <div class="overlay" id="updatesOverlay"></div>
    <div class="updates-popup" id="updatesPopup">
        <button class="close-btn" onclick="hideActiveUpdates()">✕</button>
        <h3>Active Updates (<?= count($all_updates) ?>)</h3>
        
        <?php if (empty($all_updates)): ?>
            <p style="text-align: center; color: #aaa;">No active updates</p>
        <?php else: ?>
            <?php foreach ($all_updates as $package => $update): ?>
                <div class="update-item">
                    <h4><?= htmlspecialchars($package) ?> (v<?= htmlspecialchars($update['version_code']) ?>)</h4>
                    <p><strong>Title:</strong> <?= htmlspecialchars($update['title']) ?></p>
                    <p><strong>Note:</strong> <?= nl2br(htmlspecialchars($update['note'])) ?></p>
                    <?php if (!empty($update['url'])): ?>
                        <p><strong>URL:</strong> <?= htmlspecialchars($update['url']) ?></p>
                    <?php endif; ?>
                    <p><strong>Force Update:</strong> <?= $update['force_update'] ? 'Yes' : 'No' ?></p>
                    <p><small>Last updated: <?= date('Y-m-d H:i:s', $update['timestamp']) ?></small></p>
                    
                    <div class="update-actions">
                        <a href="?package_preview=<?= urlencode($package) ?>" class="edit-btn">Edit</a>
                        <a href="?delete=<?= urlencode($package) ?>" class="delete-btn" onclick="return confirm('Delete this update?')">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        // Show stats popup with zoom-out animation
        function showStats(appName, totalUsers, activeUsers, expiredUsers) {
            document.getElementById('modalAppName').textContent = appName;
            document.getElementById('totalUsers').textContent = totalUsers;
            document.getElementById('activeUsers').textContent = activeUsers;
            document.getElementById('expiredUsers').textContent = expiredUsers;
            document.getElementById('statsModal').style.display = 'flex';
        }
        
        // Show config modal
        function showConfigModal() {
            document.getElementById('configModal').style.display = 'flex';
        }
        
        // Show update modal
        function showUpdateModal() {
            document.getElementById('updateModal').style.display = 'flex';
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('statsModal').style.display = 'none';
            document.getElementById('configModal').style.display = 'none';
            document.getElementById('updateModal').style.display = 'none';
        }
        
        // Show active updates popup with zoom-out animation
        function showActiveUpdates() {
            document.getElementById('updatesOverlay').style.display = 'block';
            document.getElementById('updatesPopup').style.display = 'block';
        }
        
        // Hide active updates popup
        function hideActiveUpdates() {
            document.getElementById('updatesOverlay').style.display = 'none';
            document.getElementById('updatesPopup').style.display = 'none';
        }
        
        // Close when clicking outside modal (disabled for updates popup)
        document.addEventListener('click', function(e) {
            if (e.target === document.getElementById('statsModal') || 
                e.target === document.getElementById('configModal') || 
                e.target === document.getElementById('updateModal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>