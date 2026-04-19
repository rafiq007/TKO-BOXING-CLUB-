<?php
/**
 * Authentication Troubleshooting Script
 * This will help diagnose login issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Gym Management System - Login Troubleshooting</h2>";
echo "<hr>";

// Step 1: Check if db_connect.php exists
echo "<h3>1. Checking Database Connection File</h3>";
if (file_exists('db_connect.php')) {
    echo "✅ db_connect.php exists<br>";
    require_once 'db_connect.php';
} else {
    echo "❌ db_connect.php NOT FOUND!<br>";
    echo "<strong>Solution:</strong> Make sure db_connect.php is uploaded and configured.<br>";
    exit;
}

// Step 2: Test database connection
echo "<h3>2. Testing Database Connection</h3>";
try {
    $pdo = Database::getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection FAILED<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Solution:</strong> Check your database credentials in db_connect.php<br>";
    exit;
}

// Step 3: Check if staff table exists
echo "<h3>3. Checking Staff Table</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff");
    $result = $stmt->fetch();
    echo "✅ Staff table exists with {$result['count']} records<br>";
} catch (Exception $e) {
    echo "❌ Staff table NOT FOUND or error<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Solution:</strong> Import database_schema.sql via phpMyAdmin<br>";
    exit;
}

// Step 4: Check for default user
echo "<h3>4. Checking Default User Account</h3>";
try {
    $stmt = $pdo->prepare("SELECT staff_id, first_name, last_name, email, status FROM staff WHERE email = ?");
    $stmt->execute(['john.smith@fitlife.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Default user found<br>";
        echo "User ID: {$user['staff_id']}<br>";
        echo "Name: {$user['first_name']} {$user['last_name']}<br>";
        echo "Email: {$user['email']}<br>";
        echo "Status: {$user['status']}<br>";
        
        if ($user['status'] !== 'Active') {
            echo "<br>⚠️ WARNING: User status is not 'Active'<br>";
            echo "<strong>Solution:</strong> Run this SQL:<br>";
            echo "<code>UPDATE staff SET status = 'Active' WHERE email = 'john.smith@fitlife.com';</code><br>";
        }
    } else {
        echo "❌ Default user NOT FOUND<br>";
        echo "<strong>Solution:</strong> The database may not have been imported properly.<br>";
        echo "Re-import database_schema.sql via phpMyAdmin<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking user<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
}

// Step 5: Test password verification
echo "<h3>5. Testing Password Hash</h3>";
$testPassword = 'password123';
echo "Test password: <code>$testPassword</code><br>";

try {
    $stmt = $pdo->prepare("SELECT password_hash FROM staff WHERE email = ?");
    $stmt->execute(['john.smith@fitlife.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Password hash exists: " . substr($user['password_hash'], 0, 20) . "...<br>";
        
        // Test if password verifies
        if (password_verify($testPassword, $user['password_hash'])) {
            echo "✅ Password verification SUCCESSFUL<br>";
        } else {
            echo "❌ Password verification FAILED<br>";
            echo "<strong>Solution:</strong> Reset the password by running this SQL:<br>";
            $newHash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            echo "<textarea style='width:100%;height:80px;'>UPDATE staff SET password_hash = '$newHash' WHERE email = 'john.smith@fitlife.com';</textarea><br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error testing password<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
}

// Step 6: Check session
echo "<h3>6. Checking Session Configuration</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✅ Session started<br>";
} else {
    echo "✅ Session already active<br>";
}
echo "Session ID: " . session_id() . "<br>";

// Step 7: Test login function
echo "<h3>7. Testing Login Function</h3>";
if (file_exists('auth.php')) {
    echo "✅ auth.php exists<br>";
    
    // Manually test login
    $email = 'john.smith@fitlife.com';
    $password = 'password123';
    
    try {
        $stmt = $pdo->prepare("SELECT s.*, r.role_name 
                               FROM staff s
                               JOIN roles r ON s.role_id = r.role_id
                               WHERE s.email = ? AND s.status = 'Active'");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();
        
        if ($staff) {
            echo "User found in database<br>";
            if (password_verify($password, $staff['password_hash'])) {
                echo "✅ Login credentials are CORRECT<br>";
                echo "<strong>The login should work!</strong><br>";
            } else {
                echo "❌ Password does NOT match<br>";
            }
        } else {
            echo "❌ User not found or not active<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ auth.php NOT FOUND<br>";
}

// Step 8: Quick fix option
echo "<hr>";
echo "<h3>Quick Fix: Reset Default User Password</h3>";
echo "<form method='post'>";
echo "<input type='hidden' name='reset_password' value='1'>";
echo "<button type='submit' style='padding:10px 20px; background:#e74c3c; color:white; border:none; cursor:pointer;'>";
echo "Reset Password to 'password123'</button>";
echo "</form>";

if (isset($_POST['reset_password'])) {
    try {
        $newHash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("UPDATE staff SET password_hash = ?, status = 'Active' WHERE email = ?");
        $stmt->execute([$newHash, 'john.smith@fitlife.com']);
        
        echo "<div style='background:#27ae60; color:white; padding:15px; margin:10px 0;'>";
        echo "✅ Password has been reset successfully!<br>";
        echo "You can now login with:<br>";
        echo "Email: john.smith@fitlife.com<br>";
        echo "Password: password123<br>";
        echo "<a href='login.php' style='color:white; font-weight:bold;'>Go to Login Page</a>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background:#e74c3c; color:white; padding:15px; margin:10px 0;'>";
        echo "❌ Error resetting password: " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If all checks above are ✅ green, try logging in again</li>";
echo "<li>If password verification failed, click 'Reset Password' button above</li>";
echo "<li>Clear your browser cache and cookies</li>";
echo "<li>Make sure JavaScript is enabled in your browser</li>";
echo "<li>Check browser console for any JavaScript errors (F12)</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login Page</a></p>";
?>
