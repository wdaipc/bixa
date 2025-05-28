<?php
// Simple Error Handling and Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session safely
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple logging function
function debug_log($message) {
    $log_file = __DIR__ . '/installer_debug.log';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($log_file, $timestamp . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

debug_log("Installer accessed - Step: " . ($_GET['step'] ?? 0));

// Basic variables
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
$error = '';
$success = false;

// Step titles
$step_titles = [
    0 => 'Welcome to BIXA',
    1 => 'Website URL',
    2 => 'Database Setup',
    3 => 'Admin Account',
    4 => 'Complete'
];

$current_title = $step_titles[$step] ?? 'Installation';

debug_log("Current step: $step, Title: $current_title");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIXA Installer - <?= htmlspecialchars($current_title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
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
        }
        
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid var(--border);
            padding: 1.5rem;
            text-align: center;
        }
        
        .card-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .progress-bar {
            background-color: var(--gray-light);
            height: 8px;
            border-radius: 4px;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .progress-fill {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary, #a855f7) 100%);
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 4px;
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
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
            border: none;
        }
        
        .btn-primary {
            color: white;
            background-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        
        .btn-success {
            color: white;
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            border: 1px solid;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: var(--success);
            border-color: var(--success);
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: var(--danger);
            border-color: var(--danger);
        }
        
        .alert-warning {
            background-color: #fef3c7;
            color: var(--warning);
            border-color: var(--warning);
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
            font-family: monospace;
            font-size: 0.875rem;
            color: var(--primary);
        }
        
        .debug-info {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        
        .debug-info pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>BIXA Installer</h1>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= ($step * 20) ?>%"></div>
                </div>
                <p><?= htmlspecialchars($current_title) ?></p>
            </div>

            <div class="card-body">
                <?php
                debug_log("Rendering step: $step");
                
                try {
                    if ($step == 0) {
                        // Welcome Screen
                        ?>
                        <p style="text-align: center; margin-bottom: 2rem;">
                            Welcome to the BIXA installation wizard. This will guide you through setting up your hosting control panel.
                        </p>
                        <a href="?step=1" class="btn btn-primary">Start Installation</a>
                        <?php
                        
                    } elseif ($step == 1) {
                        // Website URL Setup
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_url'])) {
                            $_SESSION['site_url'] = trim($_POST['site_url']);
                            debug_log("Site URL saved: " . $_SESSION['site_url']);
                            echo '<script>window.location.href = "?step=2";</script>';
                            exit;
                        }
                        ?>
                        <form method="post">
                            <div class="form-group">
                                <label class="form-label">Website URL</label>
                                <input type="url" class="form-control" name="site_url" 
                                       placeholder="https://yourdomain.com" 
                                       value="<?= htmlspecialchars($_SESSION['site_url'] ?? '') ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Continue</button>
                        </form>
                        <?php
                        
                    } elseif ($step == 2) {
                        // Database Setup
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
                            $db_data = [
                                'host' => trim($_POST['db_host']),
                                'name' => trim($_POST['db_name']),
                                'user' => trim($_POST['db_user']),
                                'pass' => $_POST['db_pass']
                            ];
                            
                            debug_log("Testing database connection...");
                            $conn = @mysqli_connect($db_data['host'], $db_data['user'], $db_data['pass'], $db_data['name']);
                            
                            if (!$conn) {
                                $error = 'Database connection failed: ' . mysqli_connect_error();
                                debug_log("DB Connection Error: $error");
                            } else {
                                $_SESSION['db'] = $db_data;
                                mysqli_close($conn);
                                debug_log("Database connection successful");
                                echo '<script>window.location.href = "?step=3";</script>';
                                exit;
                            }
                        }
                        
                        if ($error) {
                            echo '<div class="alert alert-error">' . htmlspecialchars($error) . '</div>';
                        }
                        ?>
                        <form method="post">
                            <div class="form-group">
                                <label class="form-label">Database Host</label>
                                <input type="text" class="form-control" name="db_host" value="localhost" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Database Name</label>
                                <input type="text" class="form-control" name="db_name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Database Username</label>
                                <input type="text" class="form-control" name="db_user" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Database Password</label>
                                <input type="password" class="form-control" name="db_pass">
                            </div>
                            <button type="submit" class="btn btn-primary">Test Connection</button>
                        </form>
                        <?php
                        
                    } elseif ($step == 3) {
                        // Admin Account Setup & Installation
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_name'])) {
                            debug_log("Starting installation process...");
                            
                            try {
                                // Validate inputs
                                $admin_name = trim($_POST['admin_name']);
                                $admin_email = trim($_POST['admin_email']);
                                $admin_password = $_POST['admin_password'];
                                
                                if (empty($admin_name) || empty($admin_email) || empty($admin_password)) {
                                    throw new Exception('All fields are required.');
                                }
                                
                                if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                                    throw new Exception('Invalid email address.');
                                }
                                
                                if (strlen($admin_password) < 8) {
                                    throw new Exception('Password must be at least 8 characters.');
                                }
                                
                                // Connect to database
                                $conn = mysqli_connect(
                                    $_SESSION['db']['host'],
                                    $_SESSION['db']['user'],
                                    $_SESSION['db']['pass'],
                                    $_SESSION['db']['name']
                                );
                                
                                if (!$conn) {
                                    throw new Exception('Database connection failed: ' . mysqli_connect_error());
                                }
                                
                                mysqli_set_charset($conn, 'utf8mb4');
                                debug_log("Connected to database successfully");
                                
                                // Check if database has existing tables
                                $existing_tables = [];
                                $tables_result = @mysqli_query($conn, "SHOW TABLES");
                                if ($tables_result) {
                                    while ($row = mysqli_fetch_array($tables_result)) {
                                        $existing_tables[] = $row[0];
                                    }
                                }
                                
                                $has_existing_data = count($existing_tables) > 0;
                                debug_log("Database analysis: " . count($existing_tables) . " existing tables found");
                                
                                // Clean installation if requested AND there's existing data
                                if (isset($_POST['clean_install']) && $_POST['clean_install'] == '1' && $has_existing_data) {
                                    debug_log("Performing clean installation - removing " . count($existing_tables) . " existing tables");
                                    
                                    // Safely drop existing tables
                                    @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
                                    foreach ($existing_tables as $table) {
                                        $escaped_table = mysqli_real_escape_string($conn, $table);
                                        $drop_result = @mysqli_query($conn, "DROP TABLE IF EXISTS `$escaped_table`");
                                        if ($drop_result) {
                                            debug_log("Dropped table: $table");
                                        } else {
                                            debug_log("Warning: Could not drop table $table: " . mysqli_error($conn));
                                        }
                                    }
                                    @mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
                                    debug_log("Clean installation completed");
                                } elseif (!$has_existing_data) {
                                    debug_log("Fresh database detected - skipping clean installation");
                                }
                                
                                // Read SQL file
                                $sql_file = __DIR__ . '/bixa.sql';
                                if (!file_exists($sql_file)) {
                                    throw new Exception('bixa.sql file not found in install directory.');
                                }
                                
                                $sql_content = file_get_contents($sql_file);
                                if (empty($sql_content)) {
                                    throw new Exception('SQL file is empty or could not be read.');
                                }
                                
                                debug_log("SQL file loaded, size: " . strlen($sql_content) . " bytes");
                                
                                // Fix collation compatibility issues
                                debug_log("Fixing collation compatibility...");
                                
                                // Replace MySQL 8.0+ collations with compatible ones
                                $collation_replacements = [
                                    'utf8mb4_0900_ai_ci' => 'utf8mb4_unicode_ci',
                                    'utf8_0900_ai_ci' => 'utf8_unicode_ci',
                                    'utf8mb4_0900_as_ci' => 'utf8mb4_unicode_ci',
                                    'utf8_0900_as_ci' => 'utf8_unicode_ci',
                                    'utf8mb4_0900_as_cs' => 'utf8mb4_bin',
                                    'utf8_0900_as_cs' => 'utf8_bin',
                                ];
                                
                                foreach ($collation_replacements as $old => $new) {
                                    $count = 0;
                                    $sql_content = str_replace($old, $new, $sql_content, $count);
                                    if ($count > 0) {
                                        debug_log("Replaced $count instances of '$old' with '$new'");
                                    }
                                }
                                
                                // Fix charset declarations that might be incompatible
                                $sql_content = preg_replace('/DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_[a-z_]+/i', 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', $sql_content);
                                $sql_content = preg_replace('/DEFAULT CHARSET=utf8 COLLATE=utf8_0900_[a-z_]+/i', 'DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci', $sql_content);
                                
                                // Remove version-specific MySQL comments that might cause issues
                                $sql_content = preg_replace('/\/\*![0-9]+ .*? \*\/;?/s', '', $sql_content);
                                
                                // Remove problematic SQL directives
                                $sql_content = preg_replace('/^(SET SQL_MODE|SET time_zone|START TRANSACTION|COMMIT).*$/m', '', $sql_content);
                                
                                debug_log("Collation fixes applied successfully");
                                
                                // Execute SQL statements more safely
                                // Use multi_query for better compatibility
                                mysqli_autocommit($conn, FALSE); // Start transaction
                                
                                // Set MySQL session to be more permissive for compatibility
                                @mysqli_query($conn, "SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
                                @mysqli_query($conn, "SET SESSION FOREIGN_KEY_CHECKS = 0");
                                
                                // Execute using multi_query for better results
                                if (mysqli_multi_query($conn, $sql_content)) {
                                    $executed = 0;
                                    do {
                                        if ($result = mysqli_store_result($conn)) {
                                            mysqli_free_result($result);
                                            $executed++;
                                        }
                                        $error = mysqli_error($conn);
                                        if ($error) {
                                            debug_log("SQL Warning during multi_query: " . $error);
                                            // Don't fail for collation warnings if we can continue
                                            if (stripos($error, 'collation') !== false || 
                                                stripos($error, 'charset') !== false) {
                                                debug_log("Ignoring collation/charset warning, continuing...");
                                            }
                                        }
                                    } while (mysqli_next_result($conn));
                                    
                                    mysqli_commit($conn); // Commit transaction
                                    debug_log("SQL multi_query executed successfully, processed $executed result sets");
                                } else {
                                    $sql_error = mysqli_error($conn);
                                    debug_log("SQL multi_query failed: $sql_error");
                                    
                                    // Check if it's a collation error we can handle
                                    if (stripos($sql_error, 'collation') !== false || stripos($sql_error, 'charset') !== false) {
                                        debug_log("Detected collation/charset error, attempting additional fixes...");
                                        
                                        // Additional collation fixes
                                        $sql_content = preg_replace('/COLLATE\s+utf8mb4_0900_[a-z_]+/i', 'COLLATE utf8mb4_unicode_ci', $sql_content);
                                        $sql_content = preg_replace('/COLLATE\s+utf8_0900_[a-z_]+/i', 'COLLATE utf8_unicode_ci', $sql_content);
                                        
                                        // Try again with fixed content
                                        if (mysqli_multi_query($conn, $sql_content)) {
                                            do {
                                                if ($result = mysqli_store_result($conn)) {
                                                    mysqli_free_result($result);
                                                }
                                            } while (mysqli_next_result($conn));
                                            mysqli_commit($conn);
                                            debug_log("SQL executed successfully after collation fixes");
                                        } else {
                                            mysqli_rollback($conn);
                                            debug_log("Still failing after collation fixes, trying fallback method");
                                            
                                            // Inline fallback execution
                                            $sql_statements = array_filter(
                                                array_map('trim', preg_split('/;\s*(?:\r\n|\n|\r)/', $sql_content))
                                            );
                                            
                                            $executed = 0;
                                            $failed = 0;
                                            foreach ($sql_statements as $statement) {
                                                if (empty($statement) || 
                                                    preg_match('/^(--|#|\/\*)/i', $statement) ||
                                                    preg_match('/^(SET\s+(SQL_MODE|FOREIGN_KEY_CHECKS|AUTOCOMMIT|time_zone)|START\s+TRANSACTION|COMMIT)/i', $statement)) {
                                                    continue;
                                                }
                                                
                                                if (mysqli_query($conn, $statement)) {
                                                    $executed++;
                                                } else {
                                                    $failed++;
                                                    debug_log("Fallback SQL Error: " . mysqli_error($conn));
                                                }
                                            }
                                            
                                            debug_log("Fallback after collation fixes: $executed successful, $failed failed");
                                        }
                                    } else {
                                        mysqli_rollback($conn); // Rollback on failure
                                        
                                        // Fallback: try statement by statement
                                        debug_log("Attempting fallback: statement by statement execution");
                                        
                                        $sql_statements = array_filter(
                                            array_map('trim', preg_split('/;\s*(?:\r\n|\n|\r)/', $sql_content))
                                        );
                                        
                                        $executed = 0;
                                        $failed = 0;
                                        foreach ($sql_statements as $statement) {
                                            if (empty($statement) || 
                                                preg_match('/^(--|#|\/\*)/i', $statement) ||
                                                preg_match('/^(SET\s+(SQL_MODE|FOREIGN_KEY_CHECKS|AUTOCOMMIT|time_zone)|START\s+TRANSACTION|COMMIT)/i', $statement)) {
                                                continue;
                                            }
                                            
                                            if (mysqli_query($conn, $statement)) {
                                                $executed++;
                                            } else {
                                                $failed++;
                                                $error = mysqli_error($conn);
                                                debug_log("SQL Error: $error - Statement: " . substr($statement, 0, 100) . "...");
                                            }
                                        }
                                        
                                        debug_log("Fallback execution: $executed successful, $failed failed");
                                        
                                        if ($executed == 0) {
                                            throw new Exception("Failed to execute SQL statements. Please check the SQL file format or database compatibility.");
                                        }
                                    }
                                }
                                
                                // Restore MySQL session settings
                                @mysqli_query($conn, "SET SESSION FOREIGN_KEY_CHECKS = 1");
                                mysqli_autocommit($conn, TRUE); // Restore autocommit
                                
                                // Verify critical tables exist
                                $critical_tables = ['users'];
                                $tables_created = [];
                                $tables_missing = [];
                                
                                foreach ($critical_tables as $table) {
                                    $check = @mysqli_query($conn, "SHOW TABLES LIKE '$table'");
                                    if ($check && mysqli_num_rows($check) > 0) {
                                        $tables_created[] = $table;
                                    } else {
                                        $tables_missing[] = $table;
                                    }
                                }
                                
                                debug_log("Tables created: " . implode(', ', $tables_created));
                                if (!empty($tables_missing)) {
                                    debug_log("Tables missing: " . implode(', ', $tables_missing));
                                    throw new Exception("Critical tables missing: " . implode(', ', $tables_missing) . ". Please check your SQL file.");
                                }
                                
                                debug_log("Critical tables verification passed");
                                
                                // Create admin user
                                $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 10]);
                                $current_time = date('Y-m-d H:i:s');
                                
                                // First check if admin user exists
                                $check_admin = @mysqli_query($conn, "SELECT id FROM users WHERE id = 1");
                                if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
                                    // Insert new admin user
                                    $admin_sql = "INSERT INTO users (id, name, email, password, role, email_verified_at, created_at, updated_at) VALUES 
                                        (1, '" . mysqli_real_escape_string($conn, $admin_name) . "',
                                         '" . mysqli_real_escape_string($conn, $admin_email) . "',
                                         '" . mysqli_real_escape_string($conn, $hashed_password) . "',
                                         'admin', '$current_time', '$current_time', '$current_time')";
                                } else {
                                    // Update existing admin user
                                    $admin_sql = "UPDATE users SET 
                                        name = '" . mysqli_real_escape_string($conn, $admin_name) . "',
                                        email = '" . mysqli_real_escape_string($conn, $admin_email) . "',
                                        password = '" . mysqli_real_escape_string($conn, $hashed_password) . "',
                                        email_verified_at = '$current_time',
                                        updated_at = '$current_time'
                                        WHERE id = 1";
                                }
                                
                                if (!mysqli_query($conn, $admin_sql)) {
                                    throw new Exception('Failed to create admin user: ' . mysqli_error($conn));
                                }
                                
                                debug_log("Admin user created/updated successfully");
                                
                                // Create .env file - FIXED PATH: Go up 2 levels instead of 1
                                $env_content = "APP_NAME=BIXA\n";
                                $env_content .= "APP_ENV=production\n";
                                $env_content .= "APP_KEY=base64:X6uVZYIzgW/iAIqgkg3/AKxZDxvfZuhVm+SiM3UweVg=\n";
                                $env_content .= "APP_DEBUG=false\n";
                                $env_content .= "APP_URL=" . $_SESSION['site_url'] . "\n\n";
                                $env_content .= "LOG_CHANNEL=stack\n";
                                $env_content .= "LOG_DEPRECATIONS_CHANNEL=null\n";
                                $env_content .= "LOG_LEVEL=debug\n\n";
                                $env_content .= "DB_CONNECTION=mysql\n";
                                $env_content .= "DB_HOST=" . $_SESSION['db']['host'] . "\n";
                                $env_content .= "DB_PORT=3306\n";
                                $env_content .= "DB_DATABASE=" . $_SESSION['db']['name'] . "\n";
                                $env_content .= "DB_USERNAME=" . $_SESSION['db']['user'] . "\n";
                                $env_content .= "DB_PASSWORD=" . $_SESSION['db']['pass'] . "\n\n";
                                $env_content .= "BROADCAST_DRIVER=log\n";
                                $env_content .= "CACHE_DRIVER=file\n";
                                $env_content .= "FILESYSTEM_DISK=local\n";
                                $env_content .= "QUEUE_CONNECTION=sync\n";
                                $env_content .= "SESSION_DRIVER=file\n";
                                $env_content .= "SESSION_LIFETIME=120\n";
                                
                                // FIXED: Use correct path - go up 2 levels to reach project root
                                $env_path = __DIR__ . '/../../.env';
                                debug_log("Creating .env file at: $env_path");
                                
                                if (file_put_contents($env_path, $env_content) === false) {
                                    throw new Exception('Could not create .env file. Check permissions.');
                                }
                                
                                // Secure the .env file by setting restrictive permissions (owner read/write only)
                                if (chmod($env_path, 0600)) {
                                    debug_log("Environment file permissions set to 600 (secure)");
                                } else {
                                    debug_log("Warning: Could not set secure permissions on .env file");
                                }
                                
                                debug_log("Environment file created successfully at: $env_path");
                                
                                mysqli_close($conn);
                                
                                // Store admin info for display
                                $_SESSION['admin_created'] = [
                                    'name' => $admin_name,
                                    'email' => $admin_email
                                ];
                                
                                echo '<script>window.location.href = "?step=4";</script>';
                                exit;
                                
                            } catch (Exception $e) {
                                $error = $e->getMessage();
                                debug_log("Installation error: $error");
                                if (isset($conn)) mysqli_close($conn);
                            }
                        }
                        
                        // Before showing the form, check database status
                        $db_status = 'unknown';
                        $existing_table_count = 0;
                        $mysql_version = 'Unknown';
                        
                        if (isset($_SESSION['db'])) {
                            $conn = @mysqli_connect(
                                $_SESSION['db']['host'],
                                $_SESSION['db']['user'],
                                $_SESSION['db']['pass'],
                                $_SESSION['db']['name']
                            );
                            
                            if ($conn) {
                                // Get MySQL version
                                $version_result = @mysqli_query($conn, "SELECT VERSION() as version");
                                if ($version_result) {
                                    $version_row = mysqli_fetch_assoc($version_result);
                                    $mysql_version = $version_row['version'];
                                }
                                
                                $tables_result = @mysqli_query($conn, "SHOW TABLES");
                                if ($tables_result) {
                                    $existing_table_count = mysqli_num_rows($tables_result);
                                    $db_status = $existing_table_count > 0 ? 'has_data' : 'empty';
                                }
                                mysqli_close($conn);
                            }
                        }
                        
                        if ($error) {
                            echo '<div class="alert alert-error">' . htmlspecialchars($error) . '</div>';
                        }
                        ?>
                        <div class="alert alert-warning">
                            <strong>Admin Account Setup</strong><br>
                            Create your administrator account to manage BIXA.
                        </div>
                        
                        <?php if ($db_status == 'has_data'): ?>
                        <div class="alert alert-warning">
                            <strong>⚠️ Existing Data Detected</strong><br>
                            Found <?= $existing_table_count ?> existing tables in the database. 
                            You can choose to keep or remove existing data during installation.
                        </div>
                        <?php elseif ($db_status == 'empty'): ?>
                        <div class="alert alert-success">
                            <strong>✅ Fresh Database</strong><br>
                            Database is empty and ready for fresh installation.<br>
                            <small>MySQL Version: <?= htmlspecialchars($mysql_version) ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" id="adminForm">
                            <div class="form-group">
                                <label class="form-label">Administrator Name</label>
                                <input type="text" class="form-control" name="admin_name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Administrator Email</label>
                                <input type="email" class="form-control" name="admin_email" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Administrator Password</label>
                                <input type="password" class="form-control" name="admin_password" minlength="8" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="admin_password_confirm" minlength="8" required>
                            </div>
                            
                            <?php if ($db_status == 'has_data'): ?>
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 1rem; background-color: #fef3c7; border-radius: 0.375rem; border: 1px solid #f59e0b;">
                                    <input type="checkbox" name="clean_install" value="1" checked>
                                    <span>Remove existing data (Clean Installation)</span>
                                </label>
                                <small style="color: var(--gray); margin-top: 0.5rem; display: block;">
                                    Recommended: This ensures a fresh installation. Uncheck only if you want to preserve existing data.
                                </small>
                            </div>
                            <?php else: ?>
                            <!-- Hidden field for fresh database -->
                            <input type="hidden" name="clean_install" value="0">
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">Complete Installation</button>
                        </form>
                        
                        <script>
                        document.getElementById('adminForm').addEventListener('submit', function(e) {
                            const pass = document.querySelector('input[name="admin_password"]').value;
                            const confirm = document.querySelector('input[name="admin_password_confirm"]').value;
                            if (pass !== confirm) {
                                e.preventDefault();
                                alert('Passwords do not match!');
                                return false;
                            }
                        });
                        </script>
                        <?php
                        
                    } elseif ($step == 4) {
                        // Installation Complete
                        $admin_info = $_SESSION['admin_created'] ?? ['name' => 'Administrator', 'email' => 'admin@domain.com'];
                        
                        // Try to delete install directory
                        $delete_success = false;
                        try {
                            $files = glob(__DIR__ . '/*');
                            foreach ($files as $file) {
                                if (is_file($file)) unlink($file);
                            }
                            if (rmdir(__DIR__)) {
                                $delete_success = true;
                            }
                        } catch (Exception $e) {
                            debug_log("Could not auto-delete install directory: " . $e->getMessage());
                        }
                        ?>
                        <div class="alert alert-success">
                            <strong>🎉 Installation Complete!</strong><br>
                            BIXA has been successfully installed with your custom admin account.
                        </div>
                        
                        <div class="credentials-box">
                            <h4>Your Admin Login Credentials</h4>
                            <ul>
                                <li>
                                    <span>Name:</span>
                                    <code><?= htmlspecialchars($admin_info['name']) ?></code>
                                </li>
                                <li>
                                    <span>Email:</span>
                                    <code><?= htmlspecialchars($admin_info['email']) ?></code>
                                </li>
                                <li>
                                    <span>Password:</span>
                                    <code>The password you set during installation</code>
                                </li>
                            </ul>
                        </div>
                        
                        <?php if ($delete_success): ?>
                        <div class="alert alert-success">
                            <strong>🔒 Security:</strong> Installation files have been automatically removed.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>⚠️ Security:</strong> Please manually delete the <code>install</code> directory.
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <strong>⚠️ Important Next Steps:</strong><br>
                            <strong>If you see database connection errors:</strong><br>
                            1. <strong>Clear OPcache:</strong> Restart your web server or clear OPcache to load the new .env configuration<br>
                            2. <strong>Check file permissions:</strong> Ensure <code>storage/</code> and <code>bootstrap/cache/</code> are writable (755/775)<br>
                            3. <strong>Verify .env location:</strong> Make sure the .env file is in your Laravel root directory<br>
                            4. <strong>Wait a moment:</strong> Sometimes it takes a few seconds for configuration changes to take effect<br><br>
                            <strong>If problems persist:</strong> Check your server error logs for detailed information.
                        </div>
                        
                        <a href="../" class="btn btn-success">Launch BIXA</a>
                        
                        <div style="margin-top: 1rem; text-align: center;">
                            <button onclick="testDatabaseConnection()" class="btn" style="background-color: var(--gray-light); color: var(--dark); width: auto; display: inline-flex;">
                                Test Database Connection
                            </button>
                        </div>
                        
                        <script>
                        function testDatabaseConnection() {
                            fetch('?step=4&test_db=1')
                                .then(response => response.text())
                                .then(data => {
                                    if (data.includes('Connection successful')) {
                                        alert('✅ Database connection successful!');
                                    } else {
                                        alert('❌ Database connection failed. Please check your configuration.');
                                        console.log('Response:', data);
                                    }
                                })
                                .catch(error => {
                                    alert('❌ Test failed: ' + error.message);
                                });
                        }
                        </script>
                        
                        <?php
                        // Handle database test request
                        if (isset($_GET['test_db']) && $_GET['test_db'] == '1') {
                            if (isset($_SESSION['db'])) {
                                $test_conn = @mysqli_connect(
                                    $_SESSION['db']['host'],
                                    $_SESSION['db']['user'],
                                    $_SESSION['db']['pass'],
                                    $_SESSION['db']['name']
                                );
                                
                                if ($test_conn) {
                                    echo "Connection successful";
                                    mysqli_close($test_conn);
                                } else {
                                    echo "Connection failed: " . mysqli_connect_error();
                                }
                            } else {
                                echo "No database configuration found";
                            }
                            exit;
                        }
                        ?>
                        <?php
                    }
                    
                } catch (Exception $e) {
                    debug_log("Critical error: " . $e->getMessage());
                    echo '<div class="alert alert-error">Critical Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
                
                <?php if (isset($_GET['debug']) || $error): ?>
                <div class="debug-info">
                    <strong>Debug Information:</strong>
                    <pre>PHP Version: <?= phpversion() ?>
Current Step: <?= $step ?>
Session Status: <?= session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?>
Install Directory: <?= __DIR__ ?>
SQL File Exists: <?= file_exists(__DIR__ . '/bixa.sql') ? 'Yes' : 'No' ?>
<?php if (isset($mysql_version)): ?>
MySQL Version: <?= htmlspecialchars($mysql_version) ?>
<?php endif; ?>
<?php 
// Check for .env file - UPDATED to check correct paths
$env_paths = [
    __DIR__ . '/../../.env',     // Correct path (2 levels up)
    dirname(__DIR__) . '/.env',  // Old path (1 level up)
    dirname(dirname(__DIR__)) . '/.env'  // Alternative path (3 levels up)
];

foreach ($env_paths as $index => $env_path) {
    $label = $index == 0 ? 'Correct' : ($index == 1 ? 'Old/Wrong' : 'Alternative');
    if (file_exists($env_path)) {
        echo ".env File ($label): Found at " . $env_path . "\n";
        $env_content = file_get_contents($env_path);
        if (strpos($env_content, 'DB_DATABASE=') !== false) {
            preg_match('/DB_DATABASE=(.*)/', $env_content, $matches);
            echo ".env DB Config: " . trim($matches[1] ?? 'Not found') . "\n";
        }
    } else {
        echo ".env File ($label): NOT FOUND at " . $env_path . "\n";
    }
}
?>
<?php if (isset($_SESSION) && !empty($_SESSION)): ?>
Session Data: <?= print_r($_SESSION, true) ?>
<?php endif; ?>
<?php if ($error): ?>
Last Error: <?= $error ?>
<?php endif; ?></pre>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>