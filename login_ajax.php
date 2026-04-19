<?php
/**
 * Login Page - Fixed Version with Better Error Handling
 */

// Start session
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
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
            background: linear-gradient(135deg, #1a1a1a 0%, #2c3e50 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: rgba(44, 62, 80, 0.9);
            border-radius: 20px;
            border: 2px solid #e74c3c;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 80px;
            color: #e74c3c;
        }

        .logo h2 {
            color: #fff;
            margin-top: 20px;
            font-weight: bold;
        }

        .form-control {
            background-color: rgba(26, 26, 26, 0.6);
            border: 1px solid #e74c3c;
            color: #fff;
            padding: 12px 15px;
            border-radius: 10px;
        }

        .form-control:focus {
            background-color: rgba(26, 26, 26, 0.8);
            border-color: #e74c3c;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(231, 76, 60, 0.25);
        }

        .btn-login {
            background-color: #e74c3c;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 10px;
            transition: all 0.3s;
            color: white;
        }

        .btn-login:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
            color: white;
        }

        .input-group-text {
            background-color: rgba(26, 26, 26, 0.6);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }

        .alert {
            border-radius: 10px;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="bi bi-activity"></i>
            <h2>GYM MANAGER</h2>
            <p class="text-muted">Professional Edition</p>
        </div>

        <div id="alertContainer"></div>

        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope-fill"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your email" value="john.smith@fitlife.com" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter your password" value="password123" required>
                </div>
                <small class="text-muted">Default: password123</small>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe">
                <label class="form-check-label" for="rememberMe">
                    Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-login w-100" id="loginBtn">
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

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('#email').val();
            const password = $('#password').val();
            const loginBtn = $('#loginBtn');
            
            // Disable button and show loading
            loginBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Logging in...');
            
            $.ajax({
                url: 'auth.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    auth_action: 'login',
                    email: email,
                    password: password
                },
                success: function(response) {
                    console.log('Login response:', response);
                    
                    if (response.success) {
                        $('#alertContainer').html(`
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> ${response.message}
                            </div>
                        `);
                        
                        // Redirect to dashboard
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 1000);
                    } else {
                        $('#alertContainer').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle"></i> ${response.message}
                                <hr>
                                <small>If this persists, <a href="test_login.php" class="alert-link">run diagnostics</a></small>
                            </div>
                        `);
                        loginBtn.prop('disabled', false).html('<i class="bi bi-box-arrow-in-right"></i> Login');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    let errorMsg = 'Connection error. ';
                    
                    if (xhr.status === 404) {
                        errorMsg += 'auth.php not found. Make sure all files are uploaded.';
                    } else if (xhr.status === 500) {
                        errorMsg += 'Server error. Check error logs or run <a href="test_login.php" class="alert-link">diagnostics</a>.';
                    } else {
                        errorMsg += 'Please check your setup and try again.';
                    }
                    
                    $('#alertContainer').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle"></i> ${errorMsg}
                            <hr>
                            <small><strong>Error details:</strong> ${status} - ${error}</small><br>
                            <small><a href="test_login.php" class="alert-link">Click here to troubleshoot</a></small>
                        </div>
                    `);
                    loginBtn.prop('disabled', false).html('<i class="bi bi-box-arrow-in-right"></i> Login');
                }
            });
        });

        // Debug: Log to console
        console.log('Login page loaded');
        console.log('jQuery version:', $.fn.jquery);
    </script>
</body>
</html>