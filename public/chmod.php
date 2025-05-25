<?php
/**
 * Directory Permissions Fix Tool
 * 
 * This tool recursively sets permissions on directories (755) and files (664)
 * Version: 1.0
 * Author: Claude
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
        header('WWW-Authenticate: Basic realm="Permissions Fix Tool"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Access Denied';
        exit;
    }
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

// Get current directory stats
$current_dir = isset($_POST['target_dir']) ? $_POST['target_dir'] : dirname(__FILE__);
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
    <title>Directory Permissions Fix Tool</title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
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
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
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
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.25rem;
            display: flex;
            align-items: center;
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
        }
        
        .alert-warning {
            background-color: var(--warning-light);
            color: var(--warning);
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
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-title">
                <i data-feather="shield"></i>
                Directory Permissions Fix Tool
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
        
        <div class="card">
            <div class="card-header">
                <span>Current Directory Information</span>
                <div class="badge badge-primary">
                    <i data-feather="info" class="me-1"></i>
                    Information
                </div>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i data-feather="folder"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Directories</h3>
                            <p><?= number_format($current_stats['dirs']) ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon secondary">
                            <i data-feather="file"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Files</h3>
                            <p><?= number_format($current_stats['files']) ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
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
                        <p>This tool will recursively set directories to 755 (rwxr-xr-x) and files to 664 (rw-rw-r--) permissions. Use with caution as changing permissions incorrectly may affect the functionality of your application.</p>
                    </div>
                </div>
                
                <form method="post">
                    <div class="form-group">
                        <label for="target_dir" class="form-label">Target Directory</label>
                        <input type="text" class="form-control" id="target_dir" name="target_dir" value="<?= htmlspecialchars($current_dir) ?>" required>
                        <small class="form-text">Enter the absolute path to the directory you want to fix permissions for.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="check-square"></i>
                        Fix Permissions
                    </button>
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
    </script>
</body>
</html>
