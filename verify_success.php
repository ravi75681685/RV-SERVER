<?php
$storage_file = __DIR__ . '/device_verifications.json';

// Check if file exists and is readable
if (!file_exists($storage_file) || !is_readable($storage_file)) {
    header("Location: https://rv-modz-ravi7568.wasmer.app/Failed.html");
    exit;
}

// Get parameters from URL
$device_id = isset($_GET['device_id']) ? trim($_GET['device_id']) : null;
$mobile_name = isset($_GET['mobile_name']) ? urldecode(trim($_GET['mobile_name'])) : 'Unknown';
$model = isset($_GET['model']) ? urldecode(trim($_GET['model'])) : 'Unknown';
$app_name = isset($_GET['app_name']) ? urldecode(trim($_GET['app_name'])) : 'Unknown';
$package_name = isset($_GET['package_name']) ? urldecode(trim($_GET['package_name'])) : 'Unknown';
$app_version = isset($_GET['app_version']) ? urldecode(trim($_GET['app_version'])) : 'Unknown';
$telegram_link = isset($_GET['telegram_link']) ? urldecode(trim($_GET['telegram_link'])) : '';

// Validate device ID
if ($device_id) {
    $data = json_decode(file_get_contents($storage_file), true);
    $device_verified = false;
    $created_at = null;
    
    foreach ($data as $current_app => $devices) {
        if (isset($devices[$device_id])) {
            $expiry_time = strtotime($devices[$device_id]['expiry_time']);
            if (time() <= $expiry_time) {
                $device_verified = true;
                $created_at = strtotime($devices[$device_id]['created_at']);
                break;
            }
        }
    }
    
    if (!$device_verified) {
        header("Location: https://rv-modz-ravi7568.wasmer.app/Failed.html");
        exit;
    }
} else {
    header("Location: https://rv-modz-ravi7568.wasmer.app/Failed.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Access Verified - <?php echo htmlspecialchars($app_name); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 30px 25px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            text-align: center;
        }
        .logo {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            margin-bottom: 15px;
            background: #222;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }
        .verified-badge {
            display: inline-flex;
            align-items: center;
            background: #00e676;
            color: #000;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .status-box {
            background: rgba(0, 255, 128, 0.1);
            border: 1px solid rgba(0,255,128,0.3);
            padding: 15px;
            border-radius: 14px;
            margin-bottom: 20px;
        }
        .status-box h3 {
            color: #00e676;
            margin: 0 0 8px 0;
            font-size: 16px;
        }
        .timer-box {
            background: rgba(255, 255, 255, 0.08);
            padding: 15px;
            border-radius: 14px;
            margin-bottom: 20px;
        }
        .timer-label {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 5px;
        }
        .timer {
            font-size: 26px;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }
        .button {
            display: block;
            width: 100%;
            margin: 10px 0;
            padding: 14px 0;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
        }
        .primary-btn {
            background: linear-gradient(135deg, #ff6ec4 0%, #7873f5 50%, #4ADEDE 100%);
            color: #fff;
            box-shadow: 0 0 15px rgba(122, 110, 255, 0.5);
        }
        .telegram-btn {
            background: linear-gradient(135deg, #0088cc, #00b7ff);
            color: #fff;
            font-weight: 600;
        }
        .details-btn {
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            color: #fff;
            font-weight: 600;
        }
        .details-btn:hover {
            opacity: 0.9;
        }
        .device-id {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            word-break: break-word;
        }
        .device-id-status {
            font-size: 10px;
            color: #ccc;
            margin-top: 5px;
        }
        .details-box {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 14px;
            margin-top: 20px;
            text-align: left;
        }
        .details-box p {
            margin: 5px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">✔</div>
        <h2>Premium Access Verification - <?php echo htmlspecialchars($app_name); ?></h2>
        <div class="verified-badge">Premium Active</div>

        <button class="button details-btn" onclick="toggleDetails()">Show Device Details</button>
        <div class="details-box" id="detailsBox">
            <p><strong>Mobile Name:</strong> <?php echo htmlspecialchars($mobile_name); ?></p>
            <p><strong>Model:</strong> <?php echo htmlspecialchars($model); ?></p>
            <p><strong>App Name:</strong> <?php echo htmlspecialchars($app_name); ?></p>
            <p><strong>Package Name:</strong> <?php echo htmlspecialchars($package_name); ?></p>
            <p><strong>App Version:</strong> <?php echo htmlspecialchars($app_version); ?></p>
            <p><strong>Device ID:</strong> <?php echo htmlspecialchars(substr($device_id, 0, 8) . '...'); ?></p>
        </div>

        <div class="status-box">
            <h3>Your premium access is active</h3>
            <p>All features are unlocked. Enjoy your experience!</p>
        </div>

        <div class="timer-box">
            <div class="timer-label">PREMIUM ACCESS EXPIRES IN:</div>
            <div class="timer" id="timer">24:00:00</div>
        </div>

        <a href="android-app://<?php echo urlencode($package_name); ?>" class="button primary-btn">← Return to App</a>
        <?php if (!empty($telegram_link)) { ?>
            <a href="<?php echo htmlspecialchars($telegram_link); ?>" class="button telegram-btn">📲 Join Telegram</a>
        <?php } ?>

        <div class="device-id">Device ID: <?php echo substr($device_id, 0, 4) . '...' . substr($device_id, -4); ?></div>
        <div class="device-id-status">Verification in progress...</div>
    </div>

    <script>
        function toggleDetails() {
            const detailsBox = document.getElementById('detailsBox');
            if (detailsBox.style.display === 'none' || detailsBox.style.display === '') {
                detailsBox.style.display = 'block';
            } else {
                detailsBox.style.display = 'none';
            }
        }

        function openApp(event) {
            event.preventDefault();
            const packageName = "<?php echo $package_name; ?>";
            
            // Direct app open using package name
            window.location.href = `intent://${packageName}/#Intent;package=${packageName};scheme=package;end`;
            
            // Fallback after delay
            setTimeout(() => {
                // Alternative method
                window.location.href = `intent://#Intent;package=${packageName};end`;
            }, 500);
        }

        const createdAt = new Date(<?php echo $created_at * 1000; ?>).getTime();
        const twentyFourHours = 24 * 60 * 60 * 1000;
        const targetTime = createdAt + twentyFourHours;

        function updateTimer() {
            const now = new Date().getTime();
            const distance = targetTime - now;

            if (distance <= 0) {
                document.getElementById("timer").innerHTML = "00:00:00";
                document.getElementById("timer").style.color = '#FF0000';
                clearInterval(timerInterval);
                fetch('https://rv-modz-ravi7568.wasmer.app/delete_device.php?device_id=<?php echo urlencode($device_id); ?>&app_name=<?php echo urlencode($app_name); ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'deleted') {
                            console.log('Device ID deleted');
                        }
                    })
                    .catch(error => console.error('Deletion error:', error));
                return;
            }

            let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            let seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("timer").innerHTML = 
                (hours < 10 ? "0" + hours : hours) + ":" + 
                (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                (seconds < 10 ? "0" + seconds : seconds);
        }

        const timerInterval = setInterval(updateTimer, 1000);
        updateTimer();
    </script>
</body>
</html>