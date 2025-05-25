<?php
ob_start();
session_start();

$step = isset($_GET['step']) ? intval($_GET['step']) : 0;
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/';
$title = 'Installer';
switch ($step) {
    case 1: $title = 'Step 1: Website URL'; break;
    case 2: $title = 'Step 2: Database Configuration'; break;
    case 3: $title = 'Installation Complete'; break;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .installer-wrapper {
            background: white;
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
        }
        form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        form input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .message {
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .success {
            background: #e0f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .error {
            background: #fbe9e7;
            color: #d32f2f;
            border: 1px solid #ffcdd2;
        }
        a.button-link {
            display: inline-block;
            padding: 10px 15px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
<div class="installer-wrapper">
    <h2><?= $title ?></h2>

    <?php if ($step === 1): ?>
        <form method="post" action="?step=2">
            <label>Website URL</label>
            <input type="url" name="site_url" placeholder="https://example.com" required>
            <button type="submit">Next</button>
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
                        $error = '.env file could not be created.';
                    }
                } else {
                    $error = 'Please check your sql file.';
                }
            }
        }
        ?>

        <?php if (!$success): ?>
            <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>
            <form method="post" action="?step=2">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required>

                <label>Database Name</label>
                <input type="text" name="db_name" required>

                <label>Database Username</label>
                <input type="text" name="db_user" required>

                <label>Database Password</label>
                <input type="password" name="db_pass">

                <button type="submit">Next</button>
            </form>
        <?php else: ?>
            <div class="message success">
                Installation complete!
            </div>
            <a class="button-link" href="?step=3">Finish Installation</a>
        <?php endif; ?>

    <?php elseif ($step === 3): ?>
        <div class="message success">
            <strong>Installation complete!</strong><br><br>
            <strong>Login Credentials:</strong>
            <ul>
                <li>Email: <code>demo@bixa.app</code></li>
                <li>Password: <code>bixadotapp</code></li>
            </ul>
            <p style="color: red; font-weight: bold;">⚠️ Please delete <code>install.php</code> now to secure your site.</p>
        </div>
        <a class="button-link" href="../">Go to Site</a>

    <?php else: ?>
        <p>Welcome to the BIXA Installer</p>
        <a class="button-link" href="?step=1">Start Installation</a>
    <?php endif; ?>
</div>
<!-- Credit line -->
<p style="text-align: center; margin-top: 40px; font-size: 14px; color: #999;">
    Created by <a href="https://github.com/itsmerosu" target="_blank" style="color: #007BFF;">ITSMEROSU</a>
</p>
</body>
</html>