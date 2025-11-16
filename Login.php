<?php
session_start();
$password_file = __DIR__ . '/password.json';
$error = '';

// Check if already logged in and redirect to intended page
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['redirect_url']) && !empty($_SESSION['redirect_url'])) {
        $redirect_url = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        header('Location: ' . $redirect_url);
        exit;
    } else {
        header('Location: Dashboard.php');
        exit;
    }
}

// Check for remember me cookie
if (isset($_COOKIE['remember_user']) && !isset($_SESSION['logged_in'])) {
    $cookie_data = json_decode($_COOKIE['remember_user'], true);
    if ($cookie_data && isset($cookie_data['username']) && isset($cookie_data['password'])) {
        if (file_exists($password_file) && is_readable($password_file)) {
            $credentials = json_decode(file_get_contents($password_file), true);
            if ($credentials !== null && isset($credentials['username']) && isset($credentials['password'])) {
                if ($cookie_data['username'] === $credentials['username'] && $cookie_data['password'] === $credentials['password']) {
                    $_SESSION['logged_in'] = true;
                    
                    if (isset($_SESSION['redirect_url']) && !empty($_SESSION['redirect_url'])) {
                        $redirect_url = $_SESSION['redirect_url'];
                        unset($_SESSION['redirect_url']);
                        header('Location: ' . $redirect_url);
                        exit;
                    } else {
                        header('Location: Dashboard.php');
                        exit;
                    }
                }
            }
        }
    }
}

// Store the intended redirect URL before login
if (!isset($_SESSION['redirect_url'])) {
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        $_SESSION['redirect_url'] = $_GET['redirect'];
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        $current_page = $_SERVER['PHP_SELF'];
        
        // Only store if coming from another page on same domain and not login page
        if (strpos($referer, $_SERVER['HTTP_HOST']) !== false && 
            basename($referer) !== basename($current_page) &&
            strpos($referer, 'Login.php') === false) {
            $_SESSION['redirect_url'] = $referer;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $input_username = $_POST['username'] ?? '';
    $input_password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (file_exists($password_file) && is_readable($password_file)) {
        $credentials = json_decode(file_get_contents($password_file), true);
        
        if ($credentials !== null && isset($credentials['username']) && isset($credentials['password'])) {
            if ($input_username === $credentials['username'] && $input_password === $credentials['password']) {
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Set remember me cookie if checked
                if ($remember_me) {
                    $cookie_data = json_encode([
                        'username' => $credentials['username'],
                        'password' => $credentials['password']
                    ]);
                    setcookie('remember_user', $cookie_data, time() + (30 * 24 * 60 * 60), "/"); // 30 days
                } else {
                    // Clear remember me cookie if not checked
                    setcookie('remember_user', '', time() - 3600, "/");
                }
                
                // Redirect to intended page or dashboard
                if (isset($_SESSION['redirect_url']) && !empty($_SESSION['redirect_url'])) {
                    $redirect_url = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    header('Location: ' . $redirect_url);
                    exit;
                } else {
                    header('Location: Dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Failed to read credentials';
        }
    } else {
        $error = 'Credentials file not found';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RV_MODZ</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #333;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* Lines Animation CSS - Same as style.css */
        .lines {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            overflow: hidden;
        }

        .line {
            position: absolute;
            width: 1px;
            height: 100%;
            top: 0;
            left: 50%;
            background: rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }

        .line::after {
            content: '';
            display: block;
            position: absolute;
            height: 15vh;
            width: 100%;
            top: -50%;
            left: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, #e0e0e0 75%, #e0e0e0 100%);
            animation: drop 7s 0s infinite;
            animation-fill-mode: forwards;
            animation-timing-function: cubic-bezier(0.4, 0.26, 0, 0.97);
        }

        .line:nth-child(1) { margin-left: -30%; }
        .line:nth-child(1)::after { animation-delay: 1.2s; }
        .line:nth-child(2) { margin-left: -20%; }
        .line:nth-child(2)::after { animation-delay: 0.8s; }
        .line:nth-child(3) { margin-left: -10%; }
        .line:nth-child(3)::after { animation-delay: 1.5s; }
        .line:nth-child(4) { margin-left: 0%; }
        .line:nth-child(4)::after { animation-delay: 0.5s; }
        .line:nth-child(5) { margin-left: 10%; }
        .line:nth-child(5)::after { animation-delay: 1.8s; }
        .line:nth-child(6) { margin-left: 20%; }
        .line:nth-child(6)::after { animation-delay: 1.0s; }
        .line:nth-child(7) { margin-left: 30%; }
        .line:nth-child(7)::after { animation-delay: 2.2s; }

        @keyframes drop {
            0% { top: -50%; }
            100% { top: 110%; }
        }
        /* End Lines Animation CSS */

        /* Background pattern */
        .background-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -2;
            opacity: 0.1;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.2) 2px, transparent 0),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.2) 2px, transparent 0);
            background-size: 50px 50px;
            background-position: 0 0, 25px 25px;
        }

        /* Transparent Login Container */
        .login-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(25px);
            border-radius: 20px;
            padding: 50px 40px;
            width: 90%;
            max-width: 400px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 10;
            animation: containerAppear 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Logo */
        .logo {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Title */
        h1 {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            font-size: 32px;
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Form Styling */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .login-form label {
            color: white;
            font-size: 14px;
            font-weight: 500;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
        }

        .login-form input {
            padding: 16px 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .login-form input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .login-form input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Remember Me Checkbox */
        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: rgba(255, 255, 255, 0.7);
        }

        .remember-me label {
            color: white;
            font-size: 14px;
            cursor: pointer;
        }

        /* Login Button */
        .login-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.4);
            padding: 18px;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.6);
        }

        .login-btn:active {
            transform: translateY(-1px);
        }

        /* Error Message */
        .error {
            color: #ff6b6b;
            text-align: center;
            font-size: 14px;
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
        }

        /* Animations */
        @keyframes containerAppear {
            0% { 
                transform: scale(0.9) translateY(30px); 
                opacity: 0; 
            }
            100% { 
                transform: scale(1) translateY(0); 
                opacity: 1; 
            }
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                padding: 40px 25px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .logo {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Lines Animation - Same as style.css -->
    <div class="lines">
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
        <div class="line"></div>
    </div>

    <!-- Subtle background pattern -->
    <div class="background-pattern"></div>

    <!-- Transparent Login Container -->
    <div class="login-container">
        <div class="logo">*RV-Modz*</div>
        <h1>Login</h1>
        
        <form method="POST" class="login-form">
            <div class="input-group">
                <label for="username">Email</label>
                <input type="text" id="username" name="username" placeholder="username@gmail.com" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Remember this device</label>
            </div>
            
            <button type="submit" name="login" class="login-btn">Sign in</button>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>