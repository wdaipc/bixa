<?php
/**
 * Laravel Directory Permissions Fix Tool
 * 
 * This tool recursively sets permissions on directories (755) and files (664)
 * Auto-detects Laravel root directory when placed in public folder
 * Version: 2.0
 * Author: Bixa
 */

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Security: Basic authentication check
$enable_auth = false; // Set to true to enable authentication
$auth_username = 'admin';
$auth_password = 'admin123';

if ($enable_auth) {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
        $_SERVER['PHP_AUTH_USER'] !== $auth_username || $_SERVER['PHP_AUTH_PW'] !== $auth_password) {
        header('WWW-Authenticate: Basic realm="Laravel Permissions Fix Tool"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Access Denied';
        exit;
    }
}

// Function to detect Laravel root directory
function detect_laravel_root() {
    $current_dir = dirname(__FILE__);
    
    // Check if we're in public directory of Laravel
    if (basename($current_dir) === 'public') {
        $potential_root = dirname($current_dir);
        
        // Verify Laravel structure by checking for key files/directories
        $laravel_indicators = [
            'artisan',
            'composer.json',
            'app/Http/Kernel.php',
            'bootstrap/app.php',
            'config/app.php'
        ];
        
        $found_indicators = 0;
        foreach ($laravel_indicators as $indicator) {
            if (file_exists($potential_root . '/' . $indicator)) {
                $found_indicators++;
            }
        }
        
        // If we found at least 3 Laravel indicators, this is likely Laravel root
        if ($found_indicators >= 3) {
            return $potential_root;
        }
    }
    
    // Fallback: search upward for Laravel root
    $search_dir = $current_dir;
    $max_levels = 5; // Don't search too far up
    
    for ($i = 0; $i < $max_levels; $i++) {
        if (file_exists($search_dir . '/artisan') && 
            file_exists($search_dir . '/composer.json') && 
            is_dir($search_dir . '/app')) {
            return $search_dir;
        }
        
        $parent = dirname($search_dir);
        if ($parent === $search_dir) {
            break; // Reached filesystem root
        }
        $search_dir = $parent;
    }
    
    // If no Laravel root found, return current directory
    return $current_dir;
}

// Function to get Laravel project info
function get_laravel_info($root_dir) {
    $info = [
        'is_laravel' => false,
        'version' => 'Unknown',
        'app_name' => 'Unknown',
        'environment' => 'Unknown'
    ];
    
    // Check composer.json for Laravel
    $composer_file = $root_dir . '/composer.json';
    if (file_exists($composer_file)) {
        $composer_data = json_decode(file_get_contents($composer_file), true);
        if (isset($composer_data['require']['laravel/framework'])) {
            $info['is_laravel'] = true;
            $info['version'] = $composer_data['require']['laravel/framework'];
        }
    }
    
    // Try to read .env file for app info
    $env_file = $root_dir . '/.env';
    if (file_exists($env_file)) {
        $env_content = file_get_contents($env_file);
        
        // Extract APP_NAME
        if (preg_match('/^APP_NAME=(.*)$/m', $env_content, $matches)) {
            $info['app_name'] = trim($matches[1], '"\'');
        }
        
        // Extract APP_ENV
        if (preg_match('/^APP_ENV=(.*)$/m', $env_content, $matches)) {
            $info['environment'] = trim($matches[1], '"\'');
        }
    }
    
    return $info;
}

// Function to recursively chmod directories and files
function fix_permissions($dir, $dir_perms = 0755, $file_perms = 0664, &$log = []) {
    if (!file_exists($dir)) {
        $log[] = [
            'type' => 'error',
            'path' => $dir,
            'message' => 'Directory does not exist'
        ];
        return false;
    }
    
    if (!is_dir($dir)) {
        $log[] = [
            'type' => 'error',
            'path' => $dir,
            'message' => 'Not a directory'
        ];
        return false;
    }
    
    // Set permission on the starting directory
    if (!@chmod($dir, $dir_perms)) {
        $log[] = [
            'type' => 'error',
            'path' => $dir,
            'message' => 'Failed to chmod directory'
        ];
    } else {
        $log[] = [
            'type' => 'success',
            'path' => $dir,
            'message' => 'Changed directory permissions to ' . decoct($dir_perms)
        ];
    }
    
    $items = new DirectoryIterator($dir);
    foreach ($items as $item) {
        if ($item->isDot()) {
            continue;
        }
        
        $path = $item->getPathname();
        
        if ($item->isDir()) {
            // Recursively process subdirectories
            fix_permissions($path, $dir_perms, $file_perms, $log);
        } else {
            // Set file permissions
            if (!@chmod($path, $file_perms)) {
                $log[] = [
                    'type' => 'error',
                    'path' => $path,
                    'message' => 'Failed to chmod file'
                ];
            } else {
                $log[] = [
                    'type' => 'success',
                    'path' => $path,
                    'message' => 'Changed file permissions to ' . decoct($file_perms)
                ];
            }
        }
    }
    
    return true;
}

// Function to get directory size and count
function get_directory_stats($dir) {
    $total_size = 0;
    $file_count = 0;
    $dir_count = 0;
    
    if (!file_exists($dir) || !is_dir($dir)) {
        return [
            'size' => 0,
            'files' => 0,
            'dirs' => 0
        ];
    }
    
    try {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($items as $item) {
            if ($item->isDir()) {
                $dir_count++;
            } else {
                $file_count++;
                $total_size += $item->getSize();
            }
        }
    } catch (Exception $e) {
        // Handle permission errors gracefully
    }
    
    return [
        'size' => $total_size,
        'files' => $file_count,
        'dirs' => $dir_count
    ];
}

// Function to format file size
function format_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

// Detect Laravel root directory
$laravel_root = detect_laravel_root();
$laravel_info = get_laravel_info($laravel_root);

// Process form submission
$log = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_dir'])) {
    $target_dir = $_POST['target_dir'];
    
    // Security: Basic path validation
    if (!realpath($target_dir) || !is_dir($target_dir)) {
        $log[] = [
            'type' => 'error',
            'path' => $target_dir,
            'message' => 'Invalid directory path'
        ];
    } else {
        $normalized_path = realpath($target_dir);
        
        // Get stats before fix
        $before_stats = get_directory_stats($normalized_path);
        
        // Perform the fix
        fix_permissions($normalized_path, 0755, 0664, $log);
        $done = true;
        
        // Get stats after fix
        $after_stats = get_directory_stats($normalized_path);
    }
}

// Get current directory stats (use Laravel root as default)
$current_dir = isset($_POST['target_dir']) ? $_POST['target_dir'] : $laravel_root;
$current_stats = get_directory_stats($current_dir);

// Get web server info
$server_info = [
    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_version' => phpversion(),
    'os' => PHP_OS,
    'user' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Permissions Fix Tool</title>
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
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem;
        }
        
        .navbar-container {
            max-width: 1200px;
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
        
        .navbar-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
            margin-left: 0.5rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.25rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
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
            box-shadow: 0 0 0 0.2rem rgba(255, 45, 32, 0.25);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
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
        }
        
        .btn-primary {
            color: white;
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            background-color: transparent;
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            color: white;
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            border-left: 4px solid;
        }
        
        .stat-card.primary {
            border-left-color: var(--primary);
        }
        
        .stat-card.secondary {
            border-left-color: var(--secondary);
        }
        
        .stat-card.success {
            border-left-color: var(--success);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
        }
        
        .stat-icon.primary {
            background-color: var(--primary);
        }
        
        .stat-icon.secondary {
            background-color: var(--secondary);
        }
        
        .stat-icon.success {
            background-color: var(--success);
        }
        
        .stat-content h3 {
            margin: 0;
            font-size: 0.875rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        .stat-content p {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .laravel-info {
            background: linear-gradient(135deg, var(--primary-light) 0%, #f0f4ff 100%);
            border: 1px solid #c7d2fe;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .laravel-info h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .laravel-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .laravel-info-item {
            display: flex;
            justify-content: space-between;
        }
        
        .laravel-info-item span:first-child {
            color: var(--gray);
        }
        
        .laravel-info-item span:last-child {
            font-weight: 500;
            color: var(--dark);
        }
        
        .log-container {
            max-height: 400px;
            overflow-y: auto;
            background-color: var(--gray-light);
            border-radius: 0.375rem;
            padding: 0.5rem;
        }
        
        .log-entry {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .log-entry:last-child {
            margin-bottom: 0;
        }
        
        .log-entry.success {
            background-color: var(--success-light);
            color: var(--success);
        }
        
        .log-entry.error {
            background-color: var(--danger-light);
            color: var(--danger);
        }
        
        .log-entry.warning {
            background-color: var(--warning-light);
            color: var(--warning);
        }
        
        .log-entry-content {
            flex: 1;
        }
        
        .log-entry-content .path {
            font-size: 0.875rem;
            color: var(--dark);
            margin-bottom: 0.25rem;
            word-break: break-all;
        }
        
        .log-entry-content .message {
            font-size: 0.875rem;
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
        }
        
        .alert-content p {
            margin: 0;
            font-size: 0.875rem;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .badge-success {
            background-color: var(--success-light);
            color: var(--success);
        }
        
        .server-info {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .server-info span {
            color: var(--dark);
            font-weight: 500;
        }
        
        .feather {
            width: 1em;
            height: 1em;
            vertical-align: -0.125em;
        }
        
        .form-text {
            font-size: 0.875rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div>
                <div class="navbar-title">
                    <i data-feather="shield"></i>
                    Laravel Permissions Fix Tool
                    <?php if ($laravel_info['is_laravel']): ?>
                    <span class="navbar-subtitle">| Detected Laravel Project</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="server-info">
                PHP: <span><?= htmlspecialchars($server_info['php_version']) ?></span> | 
                Server: <span><?= htmlspecialchars($server_info['software']) ?></span>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($done): ?>
        <div class="alert alert-success">
            <i data-feather="check-circle"></i>
            <div class="alert-content">
                <h4>Operation Completed</h4>
                <p>Permission fix has been applied to the selected directory.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($laravel_info['is_laravel']): ?>
        <div class="laravel-info">
            <h4>
                <i data-feather="zap"></i>
                Laravel Project Detected
            </h4>
            <div class="laravel-info-grid">
                <div class="laravel-info-item">
                    <span>App Name:</span>
                    <span><?= htmlspecialchars($laravel_info['app_name']) ?></span>
                </div>
                <div class="laravel-info-item">
                    <span>Environment:</span>
                    <span><?= htmlspecialchars($laravel_info['environment']) ?></span>
                </div>
                <div class="laravel-info-item">
                    <span>Laravel Version:</span>
                    <span><?= htmlspecialchars($laravel_info['version']) ?></span>
                </div>
                <div class="laravel-info-item">
                    <span>Root Directory:</span>
                    <span><?= htmlspecialchars(basename($laravel_root)) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <span>Directory Information</span>
                <div class="badge <?= $laravel_info['is_laravel'] ? 'badge-success' : 'badge-primary' ?>">
                    <i data-feather="<?= $laravel_info['is_laravel'] ? 'zap' : 'info' ?>" class="me-1"></i>
                    <?= $laravel_info['is_laravel'] ? 'Laravel Project' : 'Directory Info' ?>
                </div>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon primary">
                            <i data-feather="folder"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Directories</h3>
                            <p><?= number_format($current_stats['dirs']) ?></p>
                        </div>
                    </div>
                    <div class="stat-card secondary">
                        <div class="stat-icon secondary">
                            <i data-feather="file"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Files</h3>
                            <p><?= number_format($current_stats['files']) ?></p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i data-feather="hard-drive"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Total Size</h3>
                            <p><?= format_size($current_stats['size']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span>Fix Directory Permissions</span>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i data-feather="alert-triangle"></i>
                    <div class="alert-content">
                        <h4>Important Information</h4>
                        <p>This tool will recursively set directories to 755 (rwxr-xr-x) and files to 664 (rw-rw-r--) permissions. 
                        <?php if ($laravel_info['is_laravel']): ?>
                        For Laravel projects, this is typically safe for most directories, but be careful with storage and bootstrap/cache folders.
                        <?php endif ?>
                        Use with caution as changing permissions incorrectly may affect the functionality of your application.</p>
                    </div>
                </div>
                
                <form method="post">
                    <div class="form-group">
                        <label for="target_dir" class="form-label">Target Directory</label>
                        <input type="text" class="form-control" id="target_dir" name="target_dir" value="<?= htmlspecialchars($current_dir) ?>" required>
                        <div class="form-text">
                            <?php if ($laravel_info['is_laravel']): ?>
                            Auto-detected Laravel root directory. You can change this path if needed.
                            <?php else: ?>
                            Enter the absolute path to the directory you want to fix permissions for.
                            <?php endif ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="check-square"></i>
                        Fix Permissions
                    </button>
                    <?php if ($laravel_info['is_laravel']): ?>
                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('target_dir').value='<?= htmlspecialchars($laravel_root . '/storage') ?>'">
                        <i data-feather="folder"></i>
                        Fix Storage Only
                    </button>
                    <?php endif ?>
                </form>
            </div>
        </div>
        
        <?php if (!empty($log)): ?>
        <div class="card">
            <div class="card-header">
                <span>Operation Log</span>
                <span>Total Changes: <?= count($log) ?></span>
            </div>
            <div class="card-body">
                <div class="log-container">
                    <?php foreach ($log as $entry): ?>
                    <div class="log-entry <?= $entry['type'] ?>">
                        <?php if ($entry['type'] === 'success'): ?>
                            <i data-feather="check-circle"></i>
                        <?php elseif ($entry['type'] === 'error'): ?>
                            <i data-feather="alert-circle"></i>
                        <?php elseif ($entry['type'] === 'warning'): ?>
                            <i data-feather="alert-triangle"></i>
                        <?php endif; ?>
                        <div class="log-entry-content">
                            <div class="path"><?= htmlspecialchars($entry['path']) ?></div>
                            <div class="message"><?= htmlspecialchars($entry['message']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Initialize Feather Icons
        feather.replace();
        
        // Add some helpful shortcuts for Laravel
        <?php if ($laravel_info['is_laravel']): ?>
        function setStoragePermissions() {
            document.getElementById('target_dir').value = '<?= htmlspecialchars($laravel_root . '/storage') ?>';
        }
        
        function setBootstrapCachePermissions() {
            document.getElementById('target_dir').value = '<?= htmlspecialchars($laravel_root . '/bootstrap/cache') ?>';
        }
        <?php endif; ?>
    </script>
</body>
</html>
