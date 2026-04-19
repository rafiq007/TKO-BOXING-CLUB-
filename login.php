<?php
/**
 * Simple PHP Login (No AJAX)
 * Direct form submission alternative
 */

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);

// Initialize variables
$error = '';
$success = '';

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    require_once 'db_connect.php';
    
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        try {
            // Get database connection
            $pdo = Database::getConnection();
            
            // Get staff member by email
            $stmt = $pdo->prepare(
                "SELECT s.*, r.role_name 
                 FROM staff s
                 JOIN roles r ON s.role_id = r.role_id
                 WHERE s.email = ? AND s.status = 'Active'"
            );
            $stmt->execute([$email]);
            $staff = $stmt->fetch();

            if (!$staff) {
                $error = 'Invalid email or password';
            } else {
                // Verify password
                if (password_verify($password, $staff['password_hash'])) {
                    // Set session data
                    $_SESSION['staff_id'] = $staff['staff_id'];
                    $_SESSION['role_id'] = $staff['role_id'];
                    $_SESSION['role_name'] = $staff['role_name'];
                    $_SESSION['staff_name'] = $staff['first_name'] . ' ' . $staff['last_name'];
                    $_SESSION['email'] = $staff['email'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['last_activity'] = time();

                    // Log successful login
                    log_activity(
                        $staff['staff_id'],
                        'LOGIN_SUCCESS',
                        'staff',
                        $staff['staff_id'],
                        'User logged in successfully'
                    );

                    // Redirect to dashboard
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'Invalid email or password';
                    
                    // Log failed attempt
                    log_activity(
                        $staff['staff_id'],
                        'LOGIN_FAILED',
                        'staff',
                        $staff['staff_id'],
                        'Invalid password attempt'
                    );
                }
            }
        } catch (Exception $e) {
            $error = 'Database connection failed. Please check your setup.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gym Management System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: rgba(58, 58, 58, 0.9);
            border-radius: 20px;
            border: 2px solid #D4AF37;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.2);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 80px;
            color: #D4AF37;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .logo h2 {
            color: #D4AF37;
            margin-top: 20px;
            font-weight: bold;
            font-size: 32px;
        }

        .logo p {
            color: #b0b0b0;
        }

        .form-control {
            background-color: rgba(42, 42, 42, 0.8);
            border: 1px solid #D4AF37;
            color: #ffffff;
            padding: 12px 15px;
            border-radius: 10px;
        }

        .form-control:focus {
            background-color: rgba(58, 58, 58, 0.9);
            border-color: #D4AF37;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.25);
        }

        .btn-login {
            background-color: rgba(58, 58, 58, 0.9);
            border: 2px solid #D4AF37;
            color: #D4AF37;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background-color: #D4AF37;
            border-color: #D4AF37;
            color: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
        }

        .input-group-text {
            background-color: rgba(42, 42, 42, 0.8);
            border: 1px solid #D4AF37;
            color: #D4AF37;
        }

        .troubleshoot-link {
            text-align: center;
            margin-top: 20px;
        }

        .troubleshoot-link a {
            color: #3498db;
            text-decoration: none;
        }

        .troubleshoot-link a:hover {
            text-decoration: underline;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.15);
            border: 1px solid #27ae60;
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="bi bi-activity"></i>
            <h2>TKO BOXING CLUB</h2>
            <p class="text-muted">Professional Edition</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope-fill"></i>
                    </span>
                    <input type="email" class="form-control" name="email" 
                           value="john.smith@fitlife.com" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" class="form-control" name="password" 
                           value="password123" required>
                </div>
                <small class="text-muted">Default: password123</small>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="remember_me" id="rememberMe">
                <label class="form-check-label" for="rememberMe">
                    Remember me
                </label>
            </div>

            <button type="submit" name="login" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
        </form>

        <div class="troubleshoot-link">
            <a href="test_login.php">
                <i class="bi bi-tools"></i> Having trouble logging in?
            </a>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> Gym Management System. All rights reserved.
            </small>
        </div>
    </div>
</body>
</html>