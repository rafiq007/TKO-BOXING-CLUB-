<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Management System - Installation Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .status-ok {
            color: #28a745;
        }
        .status-warning {
            color: #ffc107;
        }
        .status-error {
            color: #dc3545;
        }
        .check-item {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="bi bi-activity"></i> Gym Management System</h1>
        <h3 class="text-center text-white mb-4">Installation Checker</h3>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-server"></i> Server Requirements</h5>
            </div>
            <div class="card-body">
                <?php
                $checks = [];
                
                // PHP Version
                $phpVersion = phpversion();
                $phpOk = version_compare($phpVersion, '8.2.0', '>=');
                $checks[] = [
                    'name' => 'PHP Version',
                    'required' => '8.2.0 or higher',
                    'current' => $phpVersion,
                    'status' => $phpOk ? 'ok' : 'error'
                ];

                // PDO Extension
                $pdoOk = extension_loaded('PDO') && extension_loaded('pdo_mysql');
                $checks[] = [
                    'name' => 'PDO & PDO_MySQL Extension',
                    'required' => 'Enabled',
                    'current' => $pdoOk ? 'Enabled' : 'Disabled',
                    'status' => $pdoOk ? 'ok' : 'error'
                ];

                // mbstring Extension
                $mbstringOk = extension_loaded('mbstring');
                $checks[] = [
                    'name' => 'mbstring Extension',
                    'required' => 'Enabled',
                    'current' => $mbstringOk ? 'Enabled' : 'Disabled',
                    'status' => $mbstringOk ? 'ok' : 'error'
                ];

                // JSON Extension
                $jsonOk = extension_loaded('json');
                $checks[] = [
                    'name' => 'JSON Extension',
                    'required' => 'Enabled',
                    'current' => $jsonOk ? 'Enabled' : 'Disabled',
                    'status' => $jsonOk ? 'ok' : 'error'
                ];

                // OpenSSL Extension
                $opensslOk = extension_loaded('openssl');
                $checks[] = [
                    'name' => 'OpenSSL Extension',
                    'required' => 'Enabled',
                    'current' => $opensslOk ? 'Enabled' : 'Disabled',
                    'status' => $opensslOk ? 'ok' : 'error'
                ];

                // fileinfo Extension
                $fileinfoOk = extension_loaded('fileinfo');
                $checks[] = [
                    'name' => 'fileinfo Extension',
                    'required' => 'Enabled',
                    'current' => $fileinfoOk ? 'Enabled' : 'Disabled',
                    'status' => $fileinfoOk ? 'ok' : 'warning'
                ];

                foreach ($checks as $check) {
                    $icon = $check['status'] === 'ok' ? 'check-circle-fill' : 
                           ($check['status'] === 'warning' ? 'exclamation-triangle-fill' : 'x-circle-fill');
                    $statusClass = 'status-' . $check['status'];
                    
                    echo "<div class='check-item'>
                            <div>
                                <strong>{$check['name']}</strong><br>
                                <small class='text-muted'>Required: {$check['required']}</small>
                            </div>
                            <div class='$statusClass'>
                                <i class='bi bi-$icon'></i> {$check['current']}
                            </div>
                          </div>";
                }
                ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-folder"></i> Directory Permissions</h5>
            </div>
            <div class="card-body">
                <?php
                $directories = [
                    'uploads' => 'uploads/',
                    'uploads/members' => 'uploads/members/',
                    'logs' => 'logs/',
                    'cache' => 'cache/',
                    'backups' => 'backups/'
                ];

                foreach ($directories as $name => $path) {
                    $exists = is_dir($path);
                    $writable = $exists && is_writable($path);
                    
                    if (!$exists) {
                        @mkdir($path, 0755, true);
                        $exists = is_dir($path);
                        $writable = $exists && is_writable($path);
                    }
                    
                    $status = $writable ? 'ok' : ($exists ? 'warning' : 'error');
                    $icon = $status === 'ok' ? 'check-circle-fill' : 
                           ($status === 'warning' ? 'exclamation-triangle-fill' : 'x-circle-fill');
                    $statusClass = 'status-' . $status;
                    $message = $writable ? 'Writable' : ($exists ? 'Not Writable' : 'Does Not Exist');
                    
                    echo "<div class='check-item'>
                            <div>
                                <strong>$name/</strong><br>
                                <small class='text-muted'>$path</small>
                            </div>
                            <div class='$statusClass'>
                                <i class='bi bi-$icon'></i> $message
                            </div>
                          </div>";
                }
                ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-database"></i> Database Configuration</h5>
            </div>
            <div class="card-body">
                <?php
                $dbConfigExists = file_exists('db_connect.php');
                $dbConnected = false;
                $dbError = '';

                if ($dbConfigExists) {
                    try {
                        require_once 'db_connect.php';
                        $pdo = Database::getConnection();
                        $dbConnected = true;
                    } catch (Exception $e) {
                        $dbError = $e->getMessage();
                    }
                }

                echo "<div class='check-item'>
                        <div>
                            <strong>Configuration File</strong><br>
                            <small class='text-muted'>db_connect.php</small>
                        </div>
                        <div class='" . ($dbConfigExists ? 'status-ok' : 'status-error') . "'>
                            <i class='bi bi-" . ($dbConfigExists ? 'check-circle-fill' : 'x-circle-fill') . "'></i> 
                            " . ($dbConfigExists ? 'Found' : 'Not Found') . "
                        </div>
                      </div>";

                if ($dbConfigExists) {
                    echo "<div class='check-item'>
                            <div>
                                <strong>Database Connection</strong><br>
                                <small class='text-muted'>" . ($dbError ? $dbError : 'Connection test') . "</small>
                            </div>
                            <div class='" . ($dbConnected ? 'status-ok' : 'status-error') . "'>
                                <i class='bi bi-" . ($dbConnected ? 'check-circle-fill' : 'x-circle-fill') . "'></i> 
                                " . ($dbConnected ? 'Connected' : 'Failed') . "
                            </div>
                          </div>";

                    if ($dbConnected) {
                        // Check tables
                        try {
                            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                            $requiredTables = ['members', 'staff', 'payments', 'attendance', 'memberships'];
                            $missingTables = array_diff($requiredTables, $tables);
                            $tablesOk = empty($missingTables);

                            echo "<div class='check-item'>
                                    <div>
                                        <strong>Database Tables</strong><br>
                                        <small class='text-muted'>" . count($tables) . " tables found" . 
                                        (!$tablesOk ? " (Missing: " . implode(', ', $missingTables) . ")" : "") . "</small>
                                    </div>
                                    <div class='" . ($tablesOk ? 'status-ok' : 'status-warning') . "'>
                                        <i class='bi bi-" . ($tablesOk ? 'check-circle-fill' : 'exclamation-triangle-fill') . "'></i> 
                                        " . ($tablesOk ? 'Complete' : 'Incomplete') . "
                                    </div>
                                  </div>";
                        } catch (Exception $e) {
                            echo "<div class='check-item'>
                                    <div>
                                        <strong>Database Tables</strong><br>
                                        <small class='text-muted text-danger'>" . $e->getMessage() . "</small>
                                    </div>
                                    <div class='status-error'>
                                        <i class='bi bi-x-circle-fill'></i> Error
                                    </div>
                                  </div>";
                        }
                    }
                }
                ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Security Recommendations</h5>
            </div>
            <div class="card-body">
                <?php
                $securityChecks = [];

                // HTTPS
                $httpsEnabled = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                               $_SERVER['SERVER_PORT'] == 443;
                $securityChecks[] = [
                    'name' => 'HTTPS Enabled',
                    'status' => $httpsEnabled ? 'ok' : 'warning',
                    'message' => $httpsEnabled ? 'Secure connection' : 'Recommended for production'
                ];

                // Display errors
                $displayErrors = ini_get('display_errors');
                $securityChecks[] = [
                    'name' => 'Display Errors',
                    'status' => $displayErrors ? 'warning' : 'ok',
                    'message' => $displayErrors ? 'Should be OFF in production' : 'Disabled (recommended)'
                ];

                // Session cookie
                $sessionSecure = ini_get('session.cookie_secure');
                $securityChecks[] = [
                    'name' => 'Secure Session Cookie',
                    'status' => $sessionSecure ? 'ok' : 'warning',
                    'message' => $sessionSecure ? 'Enabled' : 'Recommended for HTTPS'
                ];

                foreach ($securityChecks as $check) {
                    $icon = $check['status'] === 'ok' ? 'check-circle-fill' : 'exclamation-triangle-fill';
                    $statusClass = 'status-' . $check['status'];
                    
                    echo "<div class='check-item'>
                            <div>
                                <strong>{$check['name']}</strong><br>
                                <small class='text-muted'>{$check['message']}</small>
                            </div>
                            <div class='$statusClass'>
                                <i class='bi bi-$icon'></i>
                            </div>
                          </div>";
                }
                ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Next Steps</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li class="mb-2">
                        <strong>Configure Database:</strong> Edit <code>db_connect.php</code> with your database credentials
                    </li>
                    <li class="mb-2">
                        <strong>Import Schema:</strong> Import <code>database_schema.sql</code> into your database via phpMyAdmin
                    </li>
                    <li class="mb-2">
                        <strong>Create Directories:</strong> Ensure all required directories exist and are writable
                    </li>
                    <li class="mb-2">
                        <strong>Change Default Passwords:</strong> Login and immediately change all default staff passwords
                    </li>
                    <li class="mb-2">
                        <strong>Configure Settings:</strong> Update gym information in system settings
                    </li>
                    <li class="mb-2">
                        <strong>Enable HTTPS:</strong> Install SSL certificate for secure connections
                    </li>
                    <li class="mb-2">
                        <strong>Remove This File:</strong> Delete <code>install_check.php</code> after successful installation
                    </li>
                </ol>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Default Login:</strong><br>
                    Email: <code>john.smith@fitlife.com</code><br>
                    Password: <code>password123</code><br>
                    <small class="text-danger">⚠️ Change this password immediately after first login!</small>
                </div>

                <div class="text-center mt-4">
                    <?php if ($dbConnected && $tablesOk): ?>
                        <a href="login.php" class="btn btn-success btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Go to Login
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="bi bi-exclamation-triangle"></i> Complete Setup First
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 text-white">
            <small>Gym Management System v1.0 &copy; 2024</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
