<?php

function validateRequest() {
   
    $current_time = time();
    $request_time = isset($_GET['time']) ? intval($_GET['time'] / 1000) : 0;
    
    
    if (($current_time - $request_time) > 600) {
        return false;
    }
    

    if ($request_time > $current_time) {
        return false;
    }
    

    $required = ['device_id', 'app_name', 'package_name', 'time'];
    foreach ($required as $param) {
        if (empty($_GET[$param])) {
            return false;
        }
    }
    

    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Direct browser access detect karo
    $is_direct_browser = (
        (strpos($user_agent, 'Chrome') !== false || 
         strpos($user_agent, 'Firefox') !== false ||
         strpos($user_agent, 'Safari') !== false) &&
        strpos($user_agent, 'Mobile') === false &&
        empty($referer)
    );
    
    if ($is_direct_browser) {
        return false;
    }
    
    
    $device_id = $_GET['device_id'] ?? '';
    if (!preg_match('/^[A-F0-9]{8}$/', $device_id)) {
        return false;
    }
    
    return true; 
}


$is_verification_request = (
    isset($_GET['device_id']) && 
    isset($_GET['app_name']) && 
    isset($_GET['package_name']) && 
    isset($_GET['time'])
);

// If it's a verification request, apply security and process
if ($is_verification_request) {
    if (!validateRequest()) {
        // Security failed - redirect to failed page
        header("Location: https://rv-modz-ravi7568.wasmer.app/Failed.html");
        exit;
    }

    
    $storage_file = __DIR__ . '/device_verifications.json';

    // Ensure file exists
    if (!file_exists($storage_file)) {
        file_put_contents($storage_file, json_encode([]));
        chmod($storage_file, 0666);
    }

    // Check if file is writable
    if (!is_writable($storage_file)) {
        $error = 'Cannot write to storage file. Check permissions.';
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $error]);
        exit;
    }

    // Get parameters from request
    $device_id = isset($_GET['device_id']) ? trim($_GET['device_id']) : null;
    $mobile_name = isset($_GET['mobile_name']) ? trim($_GET['mobile_name']) : 'Unknown';
    $model = isset($_GET['model']) ? trim($_GET['model']) : 'Unknown';
    $app_name = isset($_GET['app_name']) ? trim($_GET['app_name']) : 'Unknown';
    $package_name = isset($_GET['package_name']) ? trim($_GET['package_name']) : 'Unknown';
    $app_version = isset($_GET['app_version']) ? trim($_GET['app_version']) : 'Unknown';
    $telegram_link = isset($_GET['telegram_link']) ? trim($_GET['telegram_link']) : '';

    // Validate device ID and app_name
    if (!$device_id || !$app_name) {
        $error = 'Device ID or App Name is missing.';
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $error]);
        exit;
    }

    // Load existing data
    $data = file_exists($storage_file) ? json_decode(file_get_contents($storage_file), true) : [];

    // Initialize app-specific data if not exists
    if (!isset($data[$app_name])) {
        $data[$app_name] = [];
    }


    if (!isset($data[$app_name][$device_id])) {
        $created_at = date('Y-m-d H:i:s');
        $expiry_time = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $data[$app_name][$device_id] = [
            'created_at' => $created_at,
            'expiry_time' => $expiry_time,
            'mobile_name' => $mobile_name,
            'model' => $model,
            'app_name' => $app_name,
            'package_name' => $package_name,
            'app_version' => $app_version,
            'telegram_link' => $telegram_link
        ];

        if (!file_put_contents($storage_file, json_encode($data, JSON_PRETTY_PRINT))) {
            $error = 'Failed to save device ID to file.';
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        }
    }

    
    $redirect_url = "https://rv-modz-ravi7568.wasmer.app/verify_success.php?device_id=" . urlencode($device_id) .
                    "&mobile_name=" . urlencode($mobile_name) .
                    "&model=" . urlencode($model) .
                    "&app_name=" . urlencode($app_name) .
                    "&package_name=" . urlencode($package_name) .
                    "&app_version=" . urlencode($app_version) .
                    "&telegram_link=" . urlencode($telegram_link);
    header("Location: $redirect_url");
    exit;
}
// ==================== SECURITY LAYER END ====================

// If no verification parameters, show landing page
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV_RAVI Ads - Premium Verification System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* Disable text selection */
        * {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        :root {
            --primary: #8B5CF6;
            --primary-dark: #7C3AED;
            --primary-light: #A78BFA;
            --secondary: #EC4899;
            --accent: #06B6D4;
            --dark: #0F0F23;
            --darker: #070711;
            --dark-light: #1A1A2E;
            --light: #F8FAFC;
            --gray: #94A3B8;
            --success: #10B981;
            --warning: #F59E0B;
            --glass: rgba(255, 255, 255, 0.07);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-highlight: rgba(255, 255, 255, 0.15);
            --glow-primary: 0 0 60px rgba(139, 92, 246, 0.4);
            --glow-secondary: 0 0 60px rgba(236, 72, 153, 0.3);
            --glow-accent: 0 0 60px rgba(6, 182, 212, 0.3);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--darker), var(--dark), var(--dark-light));
            color: var(--light);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
            font-weight: 400;
        }
        
        /* Cosmic Background */
        .cosmic-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            background: 
                radial-gradient(circle at 20% 30%, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(236, 72, 153, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(6, 182, 212, 0.1) 0%, transparent 50%);
        }
        
        /* Animated Nebula */
        .nebula {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            opacity: 0.6;
        }
        
        .nebula-cloud {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            animation: nebulaFloat 15s ease-in-out infinite;
        }
        
        .cloud-1 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, var(--primary), transparent 70%);
            top: -300px;
            left: -200px;
            animation-delay: 0s;
        }
        
        .cloud-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--secondary), transparent 70%);
            bottom: -200px;
            right: -100px;
            animation-delay: 5s;
        }
        
        .cloud-3 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--accent), transparent 70%);
            top: 40%;
            left: 60%;
            animation-delay: 10s;
        }
        
        @keyframes nebulaFloat {
            0%, 100% { 
                transform: translate(0, 0) scale(1) rotate(0deg);
                opacity: 0.6;
            }
            33% { 
                transform: translate(30px, -20px) scale(1.1) rotate(120deg);
                opacity: 0.8;
            }
            66% { 
                transform: translate(-20px, 15px) scale(0.9) rotate(240deg);
                opacity: 0.4;
            }
        }
        
        /* Stars */
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle var(--duration, 3s) infinite var(--delay, 0s);
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.2; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        /* Header */
        .header {
            text-align: center;
            padding: 6rem 0 4rem;
            position: relative;
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 2rem;
        }
        
        .logo-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, var(--primary), transparent 70%);
            filter: blur(20px);
            opacity: 0.7;
            z-index: -1;
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.2); }
        }
        
        .logo {
            font-size: 5rem;
            color: white; /* White color for rocket emoji */
            filter: drop-shadow(0 0 30px rgba(139, 92, 246, 0.6));
            animation: logoFloat 4s ease-in-out infinite;
            display: inline-block;
            text-shadow: none;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-15px) rotate(3deg); }
            75% { transform: translateY(-8px) rotate(-3deg); }
        }
        
        .title {
            font-size: clamp(3rem, 6vw, 5rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--light), var(--primary-light), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.1;
            text-shadow: 0 0 50px rgba(139, 92, 246, 0.5);
            letter-spacing: -0.02em;
        }
        
        .title-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glow-primary);
        }
        
        .tagline {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            color: var(--gray);
            margin-bottom: 3rem;
            font-weight: 300;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }
        
        /* Cyber Grid */
        .cyber-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(139, 92, 246, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(139, 92, 246, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            mask-image: radial-gradient(circle at center, black 30%, transparent 70%);
            opacity: 0.3;
            z-index: -1;
        }
        
        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
            margin: 6rem 0;
        }
        
        .feature-card {
            background: var(--glass);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 3rem 2.5rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, var(--glass-highlight), transparent);
            transition: left 0.8s;
        }
        
        .feature-card:hover::before {
            left: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-15px) scale(1.02);
            border-color: var(--primary);
            box-shadow: var(--glow-primary);
        }
        
        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 2rem;
            display: inline-block;
            color: white; /* White color for feature emojis */
            filter: drop-shadow(0 0 25px rgba(139, 92, 246, 0.4));
            animation: iconPulse 3s ease-in-out infinite;
            text-shadow: none;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            33% { transform: scale(1.1) rotate(5deg); }
            66% { transform: scale(1.05) rotate(-5deg); }
        }
        
        .feature-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--light);
            letter-spacing: -0.01em;
        }
        
        .feature-desc {
            color: var(--gray);
            line-height: 1.8;
            font-size: 1.05rem;
        }
        
        /* Stats Section */
        .stats-section {
            margin: 6rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
        }
        
        .stat-card {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--glass);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover::after {
            transform: scaleX(1);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary-light);
            box-shadow: var(--glow-primary);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* CTA Section */
        .cta-section {
            text-align: center;
            padding: 6rem 4rem;
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            margin: 6rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .cta-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, var(--primary), var(--secondary), var(--accent), transparent);
            animation: rotate 15s linear infinite;
            z-index: -1;
            opacity: 0.3;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .cta-content {
            position: relative;
            z-index: 2;
        }
        
        .cta-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--light), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }
        
        .cta-desc {
            color: var(--gray);
            margin-bottom: 3rem;
            font-size: 1.2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
        }
        
        .admin-btn {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            padding: 1.2rem 3rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: none;
            cursor: pointer;
            box-shadow: 
                0 10px 40px rgba(139, 92, 246, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .admin-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .admin-btn:hover::before {
            left: 100%;
        }
        
        .admin-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 
                0 20px 50px rgba(139, 92, 246, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
        }
        
        .admin-btn:active {
            transform: translateY(-2px) scale(1.02);
        }
        
        .btn-icon {
            font-size: 1.4rem;
            transition: transform 0.3s ease;
            color: white; /* White color for button icon */
        }
        
        .admin-btn:hover .btn-icon {
            transform: translateX(5px) scale(1.1);
        }
        
        /* Rocket Launch Animation - WHITE COLOR */
        .rocket-launch {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .rocket-launch.active {
            opacity: 1;
        }
        
        .rocket {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 4rem;
            color: white; /* White color for rocket emoji */
            filter: drop-shadow(0 0 25px rgba(139, 92, 246, 0.8));
            animation: rocketFly 2s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
        
        @keyframes rocketFly {
            0% {
                transform: translateX(-50%) translateY(0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translateX(-50%) translateY(-100vh) scale(0.3);
                opacity: 0;
            }
        }
        
        .rocket-trail {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 3px;
            height: 0;
            background: linear-gradient(to top, var(--primary), var(--secondary), transparent);
            filter: blur(2px);
            opacity: 0;
            animation: trailGrow 1.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
        
        @keyframes trailGrow {
            0% {
                height: 0;
                opacity: 0;
            }
            30% {
                height: 80px;
                opacity: 0.9;
            }
            100% {
                height: 120vh;
                opacity: 0;
            }
        }
        
        /* Content Exit Animations */
        .content-exit .feature-card:nth-child(1) {
            animation: slideOutLeft 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.2s forwards;
        }
        
        .content-exit .feature-card:nth-child(2) {
            animation: slideOutRight 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.4s forwards;
        }
        
        .content-exit .feature-card:nth-child(3) {
            animation: slideOutLeft 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.6s forwards;
        }
        
        .content-exit .stat-card:nth-child(1) {
            animation: slideOutRight 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.8s forwards;
        }
        
        .content-exit .stat-card:nth-child(2) {
            animation: slideOutLeft 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 1s forwards;
        }
        
        .content-exit .stat-card:nth-child(3) {
            animation: slideOutRight 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 1.2s forwards;
        }
        
        .content-exit .stat-card:nth-child(4) {
            animation: slideOutLeft 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) 1.4s forwards;
        }
        
        .content-exit .cta-section {
            animation: fadeOutScale 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) 1.6s forwards;
        }
        
        .content-exit .header {
            animation: fadeOutUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) 1.8s forwards;
        }
        
        @keyframes slideOutLeft {
            0% {
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateX(-100px);
                opacity: 0;
            }
        }
        
        @keyframes slideOutRight {
            0% {
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateX(100px);
                opacity: 0;
            }
        }
        
        @keyframes fadeOutScale {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(0.8);
                opacity: 0;
            }
        }
        
        @keyframes fadeOutUp {
            0% {
                transform: translateY(0);
                opacity: 1;
            }
            100% {
                transform: translateY(-50px);
                opacity: 0;
            }
        }

        /* Hidden Copyright - COMPLETELY HIDDEN */
        .hidden-copyright {
            display: none !important;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .container {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 4rem 0 3rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
                margin: 4rem 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
            
            .cta-section {
                padding: 4rem 2rem;
                margin: 4rem 0;
            }
            
            .cta-title {
                font-size: 2.2rem;
            }
            
            .feature-card {
                padding: 2.5rem 2rem;
            }
            
            .rocket {
                font-size: 3rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .logo {
                font-size: 4rem;
            }
            
            .title {
                font-size: 2.5rem;
            }
            
            .admin-btn {
                padding: 1rem 2rem;
                font-size: 1.1rem;
            }
            
            .rocket {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Cosmic Background -->
    <div class="cosmic-bg"></div>
    
    <!-- Animated Nebula -->
    <div class="nebula">
        <div class="nebula-cloud cloud-1"></div>
        <div class="nebula-cloud cloud-2"></div>
        <div class="nebula-cloud cloud-3"></div>
    </div>
    
    <!-- Stars -->
    <div class="stars" id="stars"></div>

    <!-- Rocket Launch Animation -->
    <div class="rocket-launch" id="rocketLaunch">
        <div class="rocket">🚀</div>
        <div class="rocket-trail"></div>
    </div>

    <div class="container" id="mainContainer">
        <!-- Cyber Grid -->
        <div class="cyber-grid"></div>

        <header class="header">
            <div class="logo-wrapper">
                <div class="logo-glow"></div>
                <div class="logo">🚀</div>
            </div>
            <div class="title-badge">PREMIUM VERIFICATION SYSTEM</div>
            <h1 class="title">RV_RAVI Ads</h1>
            <p class="tagline">
                Experience the future of app verification with our cutting-edge platform. 
                Military-grade security meets stunning performance in one seamless package.
            </p>
        </header>

        <section class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3 class="feature-title">Military-Grade Security</h3>
                <p class="feature-desc">Advanced 4-layer protection system with real-time threat detection and automated security protocols</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">Lightning Fast</h3>
                <p class="feature-desc">Ultra-fast verification processing with 8-second timeout protection and optimized performance algorithms</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🌐</div>
                <h3 class="feature-title">Multi-Platform</h3>
                <p class="feature-desc">Seamless support for multiple applications with centralized management and real-time analytics dashboard</p>
            </div>
        </section>

        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number" id="appCount">50+</span>
                    <span class="stat-label">Apps Supported</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="userCount">10K+</span>
                    <span class="stat-label">Verified Users</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="uptime">99.9%</span>
                    <span class="stat-label">System Uptime</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="speed">0.2s</span>
                    <span class="stat-label">Response Time</span>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="cta-glow"></div>
            <div class="cta-content">
                <h2 class="cta-title">Ready to Get Started?</h2>
                <p class="cta-desc">
                    Join thousands of developers who trust RV_RAVI Ads for their app verification needs. 
                    Access powerful analytics, real-time monitoring, and enterprise-grade security features.
                </p>
                <a href="#" class="admin-btn" id="dashboardBtn">
                    <i class='bx bx-rocket btn-icon'></i>
                    Launch Dashboard
                </a>
            </div>
        </section>

        <!-- Footer COMPLETELY REMOVED -->
    </div>

    <script>
        // Create stars
        function createStars() {
            const stars = document.getElementById('stars');
            const starCount = 80;
            
            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                
                const size = Math.random() * 2 + 1;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const duration = Math.random() * 5 + 2;
                const delay = Math.random() * 5;
                
                star.style.width = `${size}px`;
                star.style.height = `${size}px`;
                star.style.left = `${posX}%`;
                star.style.top = `${posY}%`;
                star.style.setProperty('--duration', `${duration}s`);
                star.style.setProperty('--delay', `${delay}s`);
                
                stars.appendChild(star);
            }
        }
        
        // Counter animation
        function animateCounters() {
            animateCounter(document.getElementById('appCount'), 50);
            animateCounter(document.getElementById('userCount'), 10000);
            animateCounter(document.getElementById('speed'), 0.2, 's');
        }
        
        function animateCounter(element, target, suffix = '+', duration = 2000) {
            let start = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    element.textContent = target + suffix;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(start) + suffix;
                }
            }, 16);
        }
        
        // Rocket launch animation
        function initRocketLaunch() {
            const dashboardBtn = document.getElementById('dashboardBtn');
            const rocketLaunch = document.getElementById('rocketLaunch');
            const mainContainer = document.getElementById('mainContainer');
            
            dashboardBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Disable button to prevent multiple clicks
                dashboardBtn.style.pointerEvents = 'none';
                
                // Start rocket animation
                rocketLaunch.classList.add('active');
                
                // Start content exit animations
                mainContainer.classList.add('content-exit');
                
                // Redirect to dashboard after animations complete
                setTimeout(() => {
                    window.location.href = 'Dashboard.php';
                }, 2500);
            });
        }

        // Handle browser back button - refresh page
        function handleBackButton() {
            window.addEventListener('pageshow', function(event) {
                if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                    // Page is loaded from cache (back/forward navigation), refresh it
                    window.location.reload();
                }
            });
        }

        // Set hidden copyright in JavaScript (encoded)
        function setHiddenCopyright() {
            // Base64 encoded copyright: "@RV_RAVIsatpute"
            const encoded = 'QHZhaWJoYXZzYXRwdXRl';
            const decoded = atob(encoded);
            
            // Create hidden element
            const hiddenCopyright = document.createElement('div');
            hiddenCopyright.style.display = 'none';
            hiddenCopyright.style.visibility = 'hidden';
            hiddenCopyright.style.position = 'absolute';
            hiddenCopyright.style.left = '-9999px';
            hiddenCopyright.style.top = '-9999px';
            hiddenCopyright.style.opacity = '0';
            hiddenCopyright.style.pointerEvents = 'none';
            hiddenCopyright.style.userSelect = 'none';
            hiddenCopyright.textContent = decoded;
            
            document.body.appendChild(hiddenCopyright);
        }
        
        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            createStars();
            animateCounters();
            initRocketLaunch();
            setHiddenCopyright();
            handleBackButton();
            
            // Smooth parallax effect
            let ticking = false;
            window.addEventListener('mousemove', function(e) {
                if (!ticking) {
                    requestAnimationFrame(function() {
                        const moveX = (e.clientX - window.innerWidth / 2) * 0.005;
                        const moveY = (e.clientY - window.innerHeight / 2) * 0.005;
                        
                        document.querySelector('.nebula').style.transform = `translate(${moveX}px, ${moveY}px)`;
                        document.querySelector('.stars').style.transform = `translate(${moveX * 0.5}px, ${moveY * 0.5}px)`;
                        
                        ticking = false;
                    });
                    ticking = true;
                }
            });

            // Force refresh on page load to ensure clean state
            if (performance.navigation.type === 2 || performance.getEntriesByType("navigation")[0].type === 'back_forward') {
                window.location.reload();
            }
        });

        // Prevent context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        // Additional back/forward detection
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>

<!-- Footer Section -->
<footer style="text-align: center; padding: 2rem; color: var(--gray); font-size: 0.9rem;">
    <p>© 2024 RV_RAVI Ads Verification. All rights reserved. | Developed by RV_RAVIsatpute</p>
</footer>
</body>
</html>