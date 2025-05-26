<?php
ob_start();
session_start();

// Function to recursively delete directory
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 0;
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/';
$title = 'BIXA Installer';
$step_title = 'Welcome to Installation';
switch ($step) {
    case 1: $step_title = 'Step 1: Website URL'; break;
    case 2: $step_title = 'Step 2: Database Configuration'; break;
    case 3: $step_title = 'Installation Complete'; break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - <?= $step_title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #eef2ff;
            --secondary: #a855f7;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --light: #f9fafb;
            --dark: #111827;
            --gray: #6b7280;
            --gray-light: #f3f4f6;
            --border: #e5e7eb;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem;
        }
        
        .navbar-container {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-title {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .progress-bar {
            flex: 1;
            max-width: 300px;
            margin-left: 2rem;
        }
        
        .progress-track {
            background-color: var(--gray-light);
            height: 4px;
            border-radius: 2px;
            position: relative;
        }
        
        .progress-fill {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
            flex: 1;
        }
        
        .installer-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid var(--border);
            padding: 1.5rem;
            text-align: center;
        }
        
        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
        }
        
        .btn-primary {
            color: white;
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            color: white;
            text-decoration: none;
        }
        
        .btn-success {
            color: white;
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #059669;
            border-color: #059669;
            color: white;
            text-decoration: none;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .alert-success {
            background-color: var(--success-light);
            color: var(--success);
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background-color: var(--danger-light);
            color: var(--danger);
            border: 1px solid #ef4444;
        }
        
        .alert-warning {
            background-color: var(--warning-light);
            color: var(--warning);
            border: 1px solid #f59e0b;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-content h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .alert-content p {
            margin: 0;
            font-size: 0.875rem;
        }
        
        .welcome-content {
            text-align: center;
            padding: 2rem 0;
        }
        
        .welcome-content p {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: 2rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            color: var(--gray);
        }
        
        .feature-list li i {
            color: var(--success);
        }
        
        .credentials-box {
            background-color: var(--gray-light);
            border-radius: 0.375rem;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .credentials-box ul {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0;
        }
        
        .credentials-box li {
            padding: 0.25rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .credentials-box code {
            background-color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            color: var(--primary);
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .step.active {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .step.completed {
            background-color: var(--success-light);
            color: var(--success);
        }
        
        .step.pending {
            background-color: var(--gray-light);
            color: var(--gray);
        }
        
        .footer {
            background-color: white;
            border-top: 1px solid var(--border);
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
        }
        
        .footer p {
            font-size: 0.875rem;
            color: var(--gray);
            margin: 0;
        }
        
        .footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .feather {
            width: 1em;
            height: 1em;
            vertical-align: -0.125em;
        }
        
        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .progress-bar {
                margin-left: 0;
                width: 100%;
            }
            
            .step-indicator {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-title">
                <i data-feather="package"></i>
                BIXA Installer
            </div>
            <div class="progress-bar">
                <div class="progress-track">
                    <div class="progress-fill" style="width: <?= ($step * 25) ?>%"></div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?= $step >= 0 ? ($step > 0 ? 'completed' : 'active') : 'pending' ?>">
                <i data-feather="home"></i>
                Welcome
            </div>
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending' ?>">
                <i data-feather="globe"></i>
                Website URL
            </div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending' ?>">
                <i data-feather="database"></i>
                Database
            </div>
            <div class="step <?= $step >= 3 ? 'active' : 'pending' ?>">
                <i data-feather="check-circle"></i>
                Complete
            </div>
        </div>

        <div class="installer-card">
            <div class="card-header">
                <h2>
                    <?php if ($step === 0): ?>
                        <i data-feather="rocket"></i>
                        Welcome to BIXA
                    <?php elseif ($step === 1): ?>
                        <i data-feather="globe"></i>
                        Website Configuration
                    <?php elseif ($step === 2): ?>
                        <i data-feather="database"></i>
                        Database Setup
                    <?php else: ?>
                        <i data-feather="check-circle"></i>
                        Installation Complete
                    <?php endif; ?>
                </h2>
            </div>

            <div class="card-body">
                <?php if ($step === 1): ?>
                    <form method="post" action="?step=2">
                        <div class="form-group">
                            <label class="form-label">
                                <i data-feather="globe" style="width: 16px; height: 16px;"></i>
                                Website URL
                            </label>
                            <input type="url" class="form-control" name="site_url" placeholder="https://example.com" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="arrow-right"></i>
                            Continue to Database Setup
                        </button>
                    </form>

                <?php elseif ($step === 2):
                    $_SESSION['site_url'] = $_POST['site_url'] ?? $_SESSION['site_url'] ?? '';
                ?>

                    <?php
                    $error = '';
                    $success = false;

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
                        $_SESSION['db'] = [
                            'host' => $_POST['db_host'],
                            'name' => $_POST['db_name'],
                            'user' => $_POST['db_user'],
                            'pass' => $_POST['db_pass']
                        ];

                        $conn = @mysqli_connect(
                            $_SESSION['db']['host'],
                            $_SESSION['db']['user'],
                            $_SESSION['db']['pass'],
                            $_SESSION['db']['name']
                        );

                        if (!$conn) {
                            $error = 'Database connection failed: ' . mysqli_connect_error();
                        } else {
                            $sql = @file_get_contents('bixa.sql');
                            if ($sql && mysqli_multi_query($conn, $sql)) {
                                // Create .env content
                                $envContent = <<<ENV
APP_NAME=BIXA
APP_ENV=production
APP_KEY=base64:X6uVZYIzgW/iAIqgkg3/AKxZDxvfZuhVm+SiM3UweVg=
APP_DEBUG=false
APP_URL={$_SESSION['site_url']}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST={$_SESSION['db']['host']}
DB_PORT=3306
DB_DATABASE={$_SESSION['db']['name']}
DB_USERNAME={$_SESSION['db']['user']}
DB_PASSWORD={$_SESSION['db']['pass']}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="\${PUSHER_HOST}"
VITE_PUSHER_PORT="\${PUSHER_PORT}"
VITE_PUSHER_SCHEME="\${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"

ACME_EMAIL=hi@bixa.app
ACME_MODE=live
MAXMIND_LICENSE_KEY=
ENV;

                                // Save .env file to root (one level up)
                                $envCreated = file_put_contents(__DIR__ . '/../../.env', $envContent);
                                if ($envCreated !== false) {
                                    $success = true;
                                } else {
                                    $error = '.env file could not be created. Please check file permissions.';
                                }
                            } else {
                                $error = 'Database import failed. Please check your SQL file and database permissions.';
                            }
                        }
                    }
                    ?>

                    <?php if (!$success): ?>
                        <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i data-feather="alert-circle"></i>
                            <div class="alert-content">
                                <h4>Database Error</h4>
                                <p><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" action="?step=2">
                            <div class="form-group">
                                <label class="form-label">
                                    <i data-feather="server" style="width: 16px; height: 16px;"></i>
                                    Database Host
                                </label>
                                <input type="text" class="form-control" name="db_host" value="localhost" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i data-feather="database" style="width: 16px; height: 16px;"></i>
                                    Database Name
                                </label>
                                <input type="text" class="form-control" name="db_name" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i data-feather="user" style="width: 16px; height: 16px;"></i>
                                    Database Username
                                </label>
                                <input type="text" class="form-control" name="db_user" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i data-feather="lock" style="width: 16px; height: 16px;"></i>
                                    Database Password
                                </label>
                                <input type="password" class="form-control" name="db_pass">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i data-feather="database"></i>
                                Install Database
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i data-feather="check-circle"></i>
                            <div class="alert-content">
                                <h4>Installation Successful!</h4>
                                <p>Your database has been configured and the application is ready to use.</p>
                            </div>
                        </div>
                        <a class="btn btn-success" href="?step=3">
                            <i data-feather="arrow-right"></i>
                            Complete Installation
                        </a>
                    <?php endif; ?>

                <?php elseif ($step === 3): 
                    // Auto-delete install directory after completion
                    $installDir = __DIR__;
                    $deleteSuccess = false;
                    
                    // Try to delete the install directory
                    if (deleteDirectory($installDir)) {
                        $deleteSuccess = true;
                    }
                ?>
                    <div class="alert alert-success">
                        <i data-feather="check-circle"></i>
                        <div class="alert-content">
                            <h4>🎉 Installation Complete!</h4>
                            <p>BIXA has been successfully installed and configured.</p>
                        </div>
                    </div>

                    <div class="credentials-box">
                        <h4 style="margin-bottom: 1rem; color: var(--dark);">
                            <i data-feather="key" style="width: 16px; height: 16px;"></i>
                            Default Login Credentials
                        </h4>
                        <ul>
                            <li>
                                <span>Email:</span>
                                <code>demo@bixa.app</code>
                            </li>
                            <li>
                                <span>Password:</span>
                                <code>bixadotapp</code>
                            </li>
                        </ul>
                    </div>

                    <?php if ($deleteSuccess): ?>
                    <div class="alert alert-success">
                        <i data-feather="shield"></i>
                        <div class="alert-content">
                            <h4>🔒 Security Complete</h4>
                            <p>Installation files have been automatically removed for security.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i data-feather="alert-triangle"></i>
                        <div class="alert-content">
                            <h4>⚠️ Manual Action Required</h4>
                            <p>Please manually delete the <code>install</code> directory from your server to secure your installation.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <a class="btn btn-success" href="../">
                        <i data-feather="external-link"></i>
                        Launch BIXA
                    </a>

                <?php else: ?>
                    <div class="welcome-content">
                        <p>Welcome to the BIXA installation wizard. This will guide you through the setup process.</p>
                        
                        <ul class="feature-list">
                            <li>
                                <i data-feather="check"></i>
                                Configure your website URL
                            </li>
                            <li>
                                <i data-feather="check"></i>
                                Set up database connection  
                            </li>
                            <li>
                                <i data-feather="check"></i>
                                Import database structure
                            </li>
                            <li>
                                <i data-feather="check"></i>
                                Generate configuration files
                            </li>
                        </ul>
                    </div>
                    
                    <a class="btn btn-primary" href="?step=1">
                        <i data-feather="play"></i>
                        Start Installation
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>
            Code by <a href="https://github.com/itsmerosu" target="_blank">ITSMEROSU</a>, Theme make by <a href="https://github.com/bixacloud" target="_blank">Bixa</a>
        </p>
    </footer>

    <script>
        // Initialize Feather Icons
        feather.replace();
    </script>
</body>
</html>