<?php
ob_start();
session_start();

// Function to delete directory recursively
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
$host = $_SERVER['HTTP_HOST'];

// Smart root URL detection
$root_url = $protocol . $host;

// Get current script path
$script_path = $_SERVER['SCRIPT_NAME']; // e.g., /public/install/index.php
$request_uri = $_SERVER['REQUEST_URI']; // e.g., /public/install/?step=2

// Try to detect the document root relative to web root
if (strpos($script_path, '/public/install/') !== false) {
    // Laravel public folder structure: /public/install/index.php
    // Root should be: domain.com (no path)
    $root_url = $protocol . $host;
} elseif (strpos($script_path, '/install/') !== false) {
    // Direct install folder: /install/index.php  
    // Root should be: domain.com (no path)
    $root_url = $protocol . $host;
} else {
    // Other configurations - try to detect base path
    $base_dir = dirname(dirname($script_path));
    if ($base_dir && $base_dir !== '/' && $base_dir !== '.') {
        $root_url = $protocol . $host . $base_dir;
    }
}

// Handle non-standard ports
$port = $_SERVER['SERVER_PORT'] ?? 80;
if (($protocol === 'https://' && $port != 443) || ($protocol === 'http://' && $port != 80)) {
    $parsed = parse_url($root_url);
    $root_url = $parsed['scheme'] . '://' . $parsed['host'] . ':' . $port . ($parsed['path'] ?? '');
}

// Clean up URL - ensure no trailing slash for root
$root_url = rtrim($root_url, '/');
if (empty(parse_url($root_url, PHP_URL_PATH))) {
    // Root domain, keep clean
} else {
    // Has path, keep as is
}

// Define steps
$steps = [
    0 => 'Welcome',
    1 => 'System Check',
    2 => 'Website Configuration',
    3 => 'Database Configuration',
    4 => 'Admin Account',
    5 => 'Installation Complete'
];

$title = isset($steps[$step]) ? 'Step ' . $step . ': ' . $steps[$step] : 'BIXA Installer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .installer-container {
            background: white;
            max-width: 800px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .installer-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .installer-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .installer-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .progress-bar {
            background: rgba(255,255,255,0.2);
            height: 6px;
            border-radius: 3px;
            margin-top: 20px;
            overflow: hidden;
        }

        .progress-fill {
            background: white;
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.8rem;
            opacity: 0.6;
        }

        .step-item.active {
            opacity: 1;
        }

        .step-item.completed {
            opacity: 1;
            color: #10b981;
        }

        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .step-item.active .step-circle {
            background: white;
            color: #4f46e5;
        }

        .step-item.completed .step-circle {
            background: #10b981;
            color: white;
        }

        .installer-content {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fed7aa;
        }

        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .system-check {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .check-item:last-child {
            border-bottom: none;
        }

        .check-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pass {
            background: #dcfce7;
            color: #166534;
        }

        .status-fail {
            background: #fef2f2;
            color: #991b1b;
        }

        .database-info {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .database-info h4 {
            color: #1e293b;
            margin-bottom: 10px;
        }

        .database-warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .database-warning h4 {
            color: #92400e;
            margin-bottom: 8px;
        }

        .btn-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            color: #6b7280;
            font-size: 14px;
        }

        .footer a {
            color: #4f46e5;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #4f46e5;
        }

        .command-box {
            background: #1a1a1a;
            color: #00ff00;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 14px;
            border: 2px solid #333;
            cursor: pointer;
            position: relative;
        }

        .command-box:hover {
            background: #222;
        }

        .copy-hint {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .installer-container {
                margin: 10px;
            }
            
            .installer-content {
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .step-indicator {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1><i class="fas fa-cloud"></i> BIXA</h1>
            <p>Professional Free Hosting Panel</p>
            
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($step * 20) ?>%"></div>
            </div>
            
            <div class="step-indicator">
                <?php for($i = 0; $i <= 5; $i++): ?>
                    <div class="step-item <?= $i < $step ? 'completed' : ($i == $step ? 'active' : '') ?>">
                        <div class="step-circle">
                            <?php if($i < $step): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?= $i ?>
                            <?php endif; ?>
                        </div>
                        <span><?= $steps[$i] ?? '' ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="installer-content">
            <?php if ($step === 0): ?>
                <!-- Welcome Step -->
                <div style="text-align: center;">
                    <h2 style="color: #1f2937; margin-bottom: 20px;">Welcome to BIXA Installation</h2>
                    <p style="color: #6b7280; margin-bottom: 30px;">Thank you for choosing BIXA! This installer will guide you through the setup process.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Before we begin, make sure you have your database credentials ready.
                    </div>
                    
                    <a href="?step=1" class="btn btn-primary btn-full">
                        <i class="fas fa-arrow-right"></i>
                        Start Installation
                    </a>
                </div>

            <?php elseif ($step === 1): ?>
                <!-- System Check Step -->
                <h2 style="color: #1f2937; margin-bottom: 20px;">System Requirements Check</h2>
                
                <div class="system-check">
                    <?php
                    $checks = [
                        'PHP Version >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
                        'OpenSSL Extension' => extension_loaded('openssl'),
                        'PDO Extension' => extension_loaded('pdo'),
                        'Mbstring Extension' => extension_loaded('mbstring'),
                        'Tokenizer Extension' => extension_loaded('tokenizer'),
                        'XML Extension' => extension_loaded('xml'),
                        'JSON Extension' => extension_loaded('json'),
                        'cURL Extension' => extension_loaded('curl'),
                        'Zip Extension' => extension_loaded('zip'),
                        'GD Extension' => extension_loaded('gd'),
                    ];
                    
                    $allPassed = true;
                    foreach($checks as $check => $passed): 
                        if(!$passed) $allPassed = false;
                    ?>
                        <div class="check-item">
                            <span><?= htmlspecialchars($check) ?></span>
                            <span class="check-status <?= $passed ? 'status-pass' : 'status-fail' ?>">
                                <?= $passed ? 'PASS' : 'FAIL' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if(!$allPassed): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        Please fix the failed requirements before continuing.
                    </div>
                <?php endif; ?>
                
                <div class="btn-actions">
                    <a href="?step=0" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </a>
                    <?php if($allPassed): ?>
                        <a href="?step=2" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i>
                            Continue
                        </a>
                    <?php endif; ?>
                </div>

            <?php elseif ($step === 2): ?>
                <!-- Website Configuration Step -->
                <h2 style="color: #1f2937; margin-bottom: 20px;">Website Configuration</h2>
                
                <form method="post" action="?step=3">
                    <div class="form-group">
                        <label for="site_url">
                            <i class="fas fa-globe"></i>
                            Website URL
                        </label>
                        <input type="url" 
                               id="site_url" 
                               name="site_url" 
                               class="form-control" 
                               value="<?= htmlspecialchars($_SESSION['site_url'] ?? $root_url) ?>" 
                               placeholder="https://example.com" 
                               required>
                        <small style="color: #6b7280; margin-top: 5px; display: block;">
                            Enter the full URL where your BIXA installation will be accessible.
                        </small>
                    </div>
                    
                    <div class="btn-actions">
                        <a href="?step=1" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i>
                            Continue
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 3):
                $_SESSION['site_url'] = $_POST['site_url'] ?? $_SESSION['site_url'] ?? '';
                
                $error = '';
                $success = false;
                $existingTables = [];
                $tableCount = 0;
                
                // Check if form is submitted
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
                    $_SESSION['db'] = [
                        'host' => $_POST['db_host'],
                        'name' => $_POST['db_name'],
                        'user' => $_POST['db_user'],
                        'pass' => $_POST['db_pass']
                    ];
                    
                    // Test database connection
                    $conn = @mysqli_connect(
                        $_SESSION['db']['host'],
                        $_SESSION['db']['user'],
                        $_SESSION['db']['pass'],
                        $_SESSION['db']['name']
                    );
                    
                    if (!$conn) {
                        $error = 'Database connection failed: ' . mysqli_connect_error();
                    } else {
                        // Check for existing tables
                        $result = mysqli_query($conn, "SHOW TABLES");
                        if ($result) {
                            $tableCount = mysqli_num_rows($result);
                            while ($row = mysqli_fetch_array($result)) {
                                $existingTables[] = $row[0];
                            }
                        }
                        
                        // If database has tables and user wants to clear
                        if ($tableCount > 0 && isset($_POST['clear_database']) && $_POST['clear_database'] == '1') {
                            // Drop all tables
                            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
                            foreach ($existingTables as $table) {
                                mysqli_query($conn, "DROP TABLE IF EXISTS `$table`");
                            }
                            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
                            $tableCount = 0;
                        }
                        
                        // If no tables or cleared, proceed with installation
                        if ($tableCount == 0 && isset($_POST['proceed_install'])) {
                            $success = true;
                        }
                        
                        mysqli_close($conn);
                    }
                }
            ?>
                <!-- Database Configuration Step -->
                <h2 style="color: #1f2937; margin-bottom: 20px;">Database Configuration</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                    <?php if ($tableCount > 0 && !isset($_POST['clear_database'])): ?>
                        <div class="database-warning">
                            <h4><i class="fas fa-exclamation-triangle"></i> Existing Database Detected</h4>
                            <p>The database "<?= htmlspecialchars($_SESSION['db']['name']) ?>" contains <?= $tableCount ?> tables:</p>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <?php foreach (array_slice($existingTables, 0, 10) as $table): ?>
                                    <li><?= htmlspecialchars($table) ?></li>
                                <?php endforeach; ?>
                                <?php if (count($existingTables) > 10): ?>
                                    <li>... and <?= count($existingTables) - 10 ?> more tables</li>
                                <?php endif; ?>
                            </ul>
                            <p><strong>Warning:</strong> Installing BIXA will clear all existing data in this database.</p>
                        </div>
                        
                        <form method="post" action="?step=3">
                            <input type="hidden" name="db_host" value="<?= htmlspecialchars($_SESSION['db']['host']) ?>">
                            <input type="hidden" name="db_name" value="<?= htmlspecialchars($_SESSION['db']['name']) ?>">
                            <input type="hidden" name="db_user" value="<?= htmlspecialchars($_SESSION['db']['user']) ?>">
                            <input type="hidden" name="db_pass" value="<?= htmlspecialchars($_SESSION['db']['pass']) ?>">
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="confirm_clear" name="clear_database" value="1" required>
                                <label for="confirm_clear">I understand that all existing data will be permanently deleted</label>
                            </div>
                            
                            <div class="btn-actions">
                                <a href="?step=2" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Back
                                </a>
                                <button type="submit" name="proceed_install" class="btn btn-danger">
                                    <i class="fas fa-trash"></i>
                                    Clear Database & Continue
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <form method="post" action="?step=3">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="db_host">
                                        <i class="fas fa-server"></i>
                                        Database Host
                                    </label>
                                    <input type="text" 
                                           id="db_host" 
                                           name="db_host" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($_SESSION['db']['host'] ?? 'localhost') ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="db_name">
                                        <i class="fas fa-database"></i>
                                        Database Name
                                    </label>
                                    <input type="text" 
                                           id="db_name" 
                                           name="db_name" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($_SESSION['db']['name'] ?? '') ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="db_user">
                                        <i class="fas fa-user"></i>
                                        Database Username
                                    </label>
                                    <input type="text" 
                                           id="db_user" 
                                           name="db_user" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($_SESSION['db']['user'] ?? '') ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="db_pass">
                                        <i class="fas fa-lock"></i>
                                        Database Password
                                    </label>
                                    <input type="password" 
                                           id="db_pass" 
                                           name="db_pass" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($_SESSION['db']['pass'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <input type="hidden" name="proceed_install" value="1">
                            
                            <div class="btn-actions">
                                <a href="?step=2" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Back
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-database"></i>
                                    Test Connection
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Database connection successful! Ready to proceed with installation.
                    </div>
                    
                    <div class="btn-actions">
                        <a href="?step=2" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </a>
                        <a href="?step=4" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i>
                            Continue
                        </a>
                    </div>
                <?php endif; ?>

            <?php elseif ($step === 4): ?>
                <!-- Admin Account Creation Step -->
                <?php
                $error = '';
                $success = false;
                
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_name'])) {
                    $_SESSION['admin'] = [
                        'name' => $_POST['admin_name'],
                        'email' => $_POST['admin_email'],
                        'password' => $_POST['admin_password']
                    ];
                    
                    // Validate admin data
                    if (empty($_POST['admin_name']) || empty($_POST['admin_email']) || empty($_POST['admin_password'])) {
                        $error = 'All fields are required.';
                    } elseif (strlen($_POST['admin_password']) < 8) {
                        $error = 'Password must be at least 8 characters long.';
                    } elseif ($_POST['admin_password'] !== $_POST['admin_password_confirm']) {
                        $error = 'Passwords do not match.';
                    } elseif (!filter_var($_POST['admin_email'], FILTER_VALIDATE_EMAIL)) {
                        $error = 'Please enter a valid email address.';
                    } else {
                        $success = true;
                    }
                }
                ?>
                
                <h2 style="color: #1f2937; margin-bottom: 20px;">Create Admin Account</h2>
                <p style="color: #6b7280; margin-bottom: 30px;">Create your administrator account to manage your BIXA installation.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Admin account configured successfully!
                    </div>
                    
                    <div class="btn-actions">
                        <a href="?step=3" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </a>
                        <a href="?step=5" class="btn btn-primary">
                            <i class="fas fa-rocket"></i>
                            Install BIXA
                        </a>
                    </div>
                <?php else: ?>
                    <form method="post" action="?step=4">
                        <div class="form-group">
                            <label for="admin_name">
                                <i class="fas fa-user"></i>
                                Full Name
                            </label>
                            <input type="text" 
                                   id="admin_name" 
                                   name="admin_name" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($_SESSION['admin']['name'] ?? '') ?>" 
                                   placeholder="Enter your full name"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">
                                <i class="fas fa-envelope"></i>
                                Email Address
                            </label>
                            <input type="email" 
                                   id="admin_email" 
                                   name="admin_email" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($_SESSION['admin']['email'] ?? '') ?>" 
                                   placeholder="Enter your email address"
                                   required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="admin_password">
                                    <i class="fas fa-lock"></i>
                                    Password
                                </label>
                                <input type="password" 
                                       id="admin_password" 
                                       name="admin_password" 
                                       class="form-control" 
                                       placeholder="Enter password (min. 8 characters)"
                                       minlength="8"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_password_confirm">
                                    <i class="fas fa-lock"></i>
                                    Confirm Password
                                </label>
                                <input type="password" 
                                       id="admin_password_confirm" 
                                       name="admin_password_confirm" 
                                       class="form-control" 
                                       placeholder="Confirm your password"
                                       minlength="8"
                                       required>
                            </div>
                        </div>
                        
                        <div class="btn-actions">
                            <a href="?step=3" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i>
                                Create Admin Account
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            <?php elseif ($step === 5): ?>
                <!-- Installation Step -->
                <?php
                $error = '';
                $success = false;
                
                if (!empty($_SESSION['db']) && !empty($_SESSION['admin'])) {
                    $conn = @mysqli_connect(
                        $_SESSION['db']['host'],
                        $_SESSION['db']['user'],
                        $_SESSION['db']['pass'],
                        $_SESSION['db']['name']
                    );
                    
                    if (!$conn) {
                        $error = 'Database connection failed: ' . mysqli_connect_error();
                    } else {
                        // Import SQL file
                        $sql = @file_get_contents('bixa.sql');
                        if ($sql && mysqli_multi_query($conn, $sql)) {
                            // Wait for all queries to complete
                            do {
                                if ($result = mysqli_store_result($conn)) {
                                    mysqli_free_result($result);
                                }
                            } while (mysqli_next_result($conn));
                            
                            // Update admin user
                            $admin_name = mysqli_real_escape_string($conn, $_SESSION['admin']['name']);
                            $admin_email = mysqli_real_escape_string($conn, $_SESSION['admin']['email']);
                            $admin_password = password_hash($_SESSION['admin']['password'], PASSWORD_DEFAULT);
                            
                            $updateAdminSql = "UPDATE users SET 
                                name = '$admin_name', 
                                email = '$admin_email', 
                                password = '$admin_password',
                                email_verified_at = NOW()
                                WHERE id = 1";
                            
                            if (mysqli_query($conn, $updateAdminSql)) {
                                // Insert default SMTP settings to avoid 500 errors
                                $site_name = mysqli_real_escape_string($conn, parse_url($_SESSION['site_url'], PHP_URL_HOST) ?: 'BIXA');
                                $default_email = mysqli_real_escape_string($conn, $_SESSION['admin']['email']);
                                
                                $smtpSql = "INSERT INTO smtp_settings (
                                    type, hostname, username, password, from_email, from_name, 
                                    port, encryption, status, created_at, updated_at
                                ) VALUES (
                                    'SMTP', 
                                    'smtp.gmail.com', 
                                    '$default_email', 
                                    '', 
                                    '$default_email', 
                                    '$site_name', 
                                    587, 
                                    'tls', 
                                    0, 
                                    NOW(), 
                                    NOW()
                                )";
                                
                                // Execute SMTP settings insertion (non-critical)
                                mysqli_query($conn, $smtpSql);
                                
                                // Insert basic settings to prevent errors
                                $basicSettings = [
                                    'site_name' => $site_name,
                                    'site_description' => 'Free hosting services powered by BIXA',
                                    'site_logo' => '',
                                    'site_favicon' => '',
                                    'registration_enabled' => '1',
                                    'email_verification_required' => '1',
                                    'maintenance_mode' => '0',
                                    'default_language' => 'en',
                                    'timezone' => 'UTC'
                                ];
                                
                                foreach ($basicSettings as $key => $value) {
                                    $escaped_key = mysqli_real_escape_string($conn, $key);
                                    $escaped_value = mysqli_real_escape_string($conn, $value);
                                    $settingSql = "INSERT INTO settings (`key`, `value`, created_at, updated_at) 
                                                  VALUES ('$escaped_key', '$escaped_value', NOW(), NOW()) 
                                                  ON DUPLICATE KEY UPDATE `value` = '$escaped_value', updated_at = NOW()";
                                    mysqli_query($conn, $settingSql);
                                }
                                
                                // Insert default MOFH API settings (disabled)
                                $mofhSql = "INSERT INTO mofh_api_settings (
                                    api_username, api_password, plan, cpanel_url, created_at, updated_at
                                ) VALUES (
                                    '', '', 'free', 'https://cpanel.example.com', NOW(), NOW()
                                )";
                                mysqli_query($conn, $mofhSql);
                                
                                // Create .env file
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
                                
                                $envCreated = file_put_contents(__DIR__ . '/../../.env', $envContent);
                                if ($envCreated !== false) {
                                    $success = true;
                                    
                                    // Auto-delete install directory for security
                                    $installDir = __DIR__;
                                    $deleted = deleteDirectory($installDir);
                                    
                                    // Double check if directory still exists (for shared hosting like cPanel)
                                    if ($deleted && !file_exists($installDir)) {
                                        $_SESSION['install_deleted'] = true;
                                    } else {
                                        $_SESSION['install_deleted'] = false;
                                    }
                                } else {
                                    $error = '.env file could not be created. Please check file permissions.';
                                }
                            } else {
                                $error = 'Failed to update admin user: ' . mysqli_error($conn);
                            }
                        } else {
                            $error = 'SQL import failed. Please check your bixa.sql file.';
                        }
                        
                        mysqli_close($conn);
                    }
                } else {
                    $error = 'Missing configuration data. Please go back and complete all steps.';
                }
                ?>
                
                <h2 style="color: #1f2937; margin-bottom: 20px;">Installation Complete!</h2>
                
                <?php if ($success): ?>
                    <?php
                    // Store values before clearing session (fix for display issue)
                    $installDeleted = $_SESSION['install_deleted'] ?? false;
                    $siteUrl = $_SESSION['site_url'] ?? '';
                    $adminEmail = $_SESSION['admin']['email'] ?? '';
                    $adminPassword = $_SESSION['admin']['password'] ?? '';
                    
                    // Parse site URL to get root domain
                    $parsedUrl = parse_url($siteUrl);
                    $rootDomain = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'localhost');
                    if (isset($parsedUrl['port'])) {
                        $rootDomain .= ':' . $parsedUrl['port'];
                    }
                    
                    // Clear session data
                    session_destroy();
                    ?>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Congratulations!</strong> BIXA has been successfully installed.
                            <?php if ($installDeleted): ?>
                                <br><small>✅ Install directory has been automatically removed for security.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!$installDeleted): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Security Notice:</strong> Unable to automatically delete install directory. Please manually delete the <code>install</code> folder for security.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($installDeleted): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-shield-alt"></i>
                            <strong>Security:</strong> Install directory has been automatically removed. Your installation is secure!
                        </div>
                    <?php endif; ?>
                    
                    <div class="database-info">
                        <h4><i class="fas fa-user-shield"></i> Admin Login Credentials</h4>
                        <p><strong>Email:</strong> <?= htmlspecialchars($adminEmail) ?></p>
                        <p><strong>Password:</strong> <?= htmlspecialchars($adminPassword) ?></p>
                        <p><strong>Login URL:</strong> <a href="<?= htmlspecialchars($siteUrl) ?>/login" target="_blank"><?= htmlspecialchars($siteUrl) ?>/login</a></p>
                        <small style="color: #6b7280;">After login, you can access the admin panel from your dashboard.</small>
                    </div>
                    
                    <div class="database-info">
                        <h4><i class="fas fa-envelope"></i> Email Configuration</h4>
                        <p><strong>Status:</strong> Default SMTP settings created (disabled)</p>
                        <p><strong>From Email:</strong> <?= htmlspecialchars($adminEmail) ?></p>
                        <p><strong>From Name:</strong> <?= htmlspecialchars(parse_url($siteUrl, PHP_URL_HOST) ?: 'BIXA') ?></p>
                        <small style="color: #6b7280;">Please configure your SMTP settings in Admin Panel to enable email functionality.</small>
                    </div>
                    
                    <div class="database-info">
                        <h4><i class="fas fa-cogs"></i> System Configuration</h4>
                        <p><strong>Default Settings:</strong> Created successfully</p>
                        <p><strong>MOFH API:</strong> Default settings created (configure in Admin Panel)</p>
                        <p><strong>Registration:</strong> Enabled</p>
                        <p><strong>Email Verification:</strong> Required</p>
                        <small style="color: #6b7280;">All system settings can be customized in the Admin Panel.</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-terminal"></i>
                        <div>
                            <strong>🚀 Final Step Required:</strong> Run this command from your terminal to complete the installation:
                            <div class="command-box" onclick="copyCommand()" title="Click to copy">
                                $ composer install --no-dev --optimize-autoloader
                            </div>
                            <div class="copy-hint">💡 Click the command above to copy it to clipboard</div>
                            <small><strong>What this does:</strong> Installs all required PHP dependencies for BIXA to function properly.</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Next Steps:</strong>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <li>📖 Read the documentation for advanced configuration</li>
                                <li>💬 Join our community for support and updates</li>
                                <li>⭐ Star our GitHub repository if you like BIXA</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Important Post-Installation Steps:</strong>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <?php if (!$installDeleted): ?>
                                    <li>🗂️ <strong>Manually delete the <code>install</code> folder for security</strong></li>
                                <?php endif; ?>
                                <li>💻 <strong>Run <code>composer install --no-dev --optimize-autoloader</code> from terminal</strong></li>
                                <li>🔐 Change your admin password after first login</li>
                                <li>📧 Configure SMTP email settings in Admin Panel → Settings → Email</li>
                                <li>🌐 Configure MOFH API settings if you plan to offer hosting services</li>
                                <li>⚙️ Review and customize system settings as needed</li>
                                <li>🛡️ Enable SSL certificate for your domain</li>
                                <li>🔒 Set up regular backups for your database</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="btn-actions">
                        <a href="<?= htmlspecialchars($rootDomain) ?>" class="btn btn-primary btn-full" target="_blank">
                            <i class="fas fa-home"></i>
                            Visit Your Website
                        </a>
                    </div>
                    
                    <script>
                        function copyCommand() {
                            const command = 'composer install --no-dev --optimize-autoloader';
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(command).then(() => {
                                    const box = document.querySelector('.command-box');
                                    const original = box.innerHTML;
                                    box.innerHTML = '✅ Copied to clipboard!';
                                    box.style.background = '#064e3b';
                                    setTimeout(() => {
                                        box.innerHTML = original;
                                        box.style.background = '#1a1a1a';
                                    }, 2000);
                                }).catch(() => {
                                    alert('Command copied: ' + command);
                                });
                            } else {
                                alert('Please copy this command: ' + command);
                            }
                        }
                        
                        // Auto redirect after 3 seconds if install was deleted successfully
                        <?php if ($installDeleted): ?>
                        setTimeout(function() {
                            if (confirm('Installation completed successfully! Would you like to visit your website now?')) {
                                window.open('<?= htmlspecialchars($rootDomain) ?>', '_blank');
                            }
                        }, 3000);
                        <?php endif; ?>
                    </script>
                    
                <?php else: ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Installation Failed:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                    
                    <div class="btn-actions">
                        <a href="?step=4" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </a>
                        <a href="?step=5" class="btn btn-primary">
                            <i class="fas fa-redo"></i>
                            Retry Installation
                        </a>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>
                <strong>BIXA</strong> - Professional Free Hosting Panel<br>
                Created by <a href="https://github.com/itsmerosu" target="_blank">ITSMEROSU</a> • 
                <a href="https://github.com/itsmerosu/bixa" target="_blank">GitHub</a> • 
                <a href="https://discord.gg/bixa" target="_blank">Discord</a>
            </p>
        </div>
    </div>

    <script>
        // Add form validation and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bar on load
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                setTimeout(() => {
                    progressFill.style.width = progressFill.style.width;
                }, 100);
            }
            
            // Add form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.style.borderColor = '#ef4444';
                            isValid = false;
                        } else {
                            field.style.borderColor = '#e5e7eb';
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            });
            
            // Password confirmation check
            const passwordField = document.getElementById('admin_password');
            const confirmField = document.getElementById('admin_password_confirm');
            
            if (passwordField && confirmField) {
                function checkPasswordMatch() {
                    if (passwordField.value !== confirmField.value) {
                        confirmField.style.borderColor = '#ef4444';
                        confirmField.setCustomValidity('Passwords do not match');
                    } else {
                        confirmField.style.borderColor = '#10b981';
                        confirmField.setCustomValidity('');
                    }
                }
                
                passwordField.addEventListener('input', checkPasswordMatch);
                confirmField.addEventListener('input', checkPasswordMatch);
            }
        });
    </script>
</body>
</html>