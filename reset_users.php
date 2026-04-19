<?php
/**
 * User Password Reset & Management Tool
 * Reset passwords for all default users
 */

session_start();
require_once 'db_connect.php';

// Initialize
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getConnection();
        
        // Reset all default users
        if (isset($_POST['reset_all'])) {
            $defaultHash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
            
            $users = [
                'john.smith@fitlife.com',
                'sarah.j@fitlife.com',
                'mike.w@fitlife.com',
                'emily.d@fitlife.com'
            ];
            
            foreach ($users as $email) {
                $stmt = $pdo->prepare("UPDATE staff SET password_hash = ?, status = 'Active' WHERE email = ?");
                $stmt->execute([$defaultHash, $email]);
            }
            
            $message = "All default user passwords have been reset to 'password123' and activated!";
            $messageType = 'success';
        }
        
        // Reset single user
        elseif (isset($_POST['reset_single'])) {
            $email = $_POST['email'];
            $newPassword = $_POST['new_password'] ?: 'password123';
            
            $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $pdo->prepare("UPDATE staff SET password_hash = ?, status = 'Active' WHERE email = ?");
            $result = $stmt->execute([$hash, $email]);
            
            if ($result) {
                $message = "Password for {$email} has been reset to '{$newPassword}' and account activated!";
                $messageType = 'success';
            } else {
                $message = "Failed to reset password for {$email}";
                $messageType = 'danger';
            }
        }
        
        // Create new user
        elseif (isset($_POST['create_user'])) {
            $firstName = $_POST['first_name'];
            $lastName = $_POST['last_name'];
            $email = $_POST['email'];
            $roleId = $_POST['role_id'];
            $password = $_POST['password'] ?: 'password123';
            
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $pdo->prepare(
                "INSERT INTO staff (role_id, first_name, last_name, email, password_hash, status, hire_date) 
                 VALUES (?, ?, ?, ?, ?, 'Active', CURDATE())"
            );
            
            if ($stmt->execute([$roleId, $firstName, $lastName, $email, $hash])) {
                $message = "User {$firstName} {$lastName} created successfully! Email: {$email}, Password: {$password}";
                $messageType = 'success';
            } else {
                $message = "Failed to create user. Email might already exist.";
                $messageType = 'danger';
            }
        }
        
        // Delete user
        elseif (isset($_POST['delete_user'])) {
            $staffId = $_POST['staff_id'];
            
            $stmt = $pdo->prepare("UPDATE staff SET status = 'Inactive' WHERE staff_id = ?");
            if ($stmt->execute([$staffId])) {
                $message = "User deactivated successfully!";
                $messageType = 'warning';
            }
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get all users
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query(
        "SELECT s.*, r.role_name 
         FROM staff s 
         JOIN roles r ON s.role_id = r.role_id 
         ORDER BY s.staff_id"
    );
    $users = $stmt->fetchAll();
    
    // Get roles for dropdown
    $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
    
} catch (Exception $e) {
    $users = [];
    $roles = [];
    $message = "Database error: " . $e->getMessage();
    $messageType = 'danger';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Reset Passwords</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2c3e50 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            background: rgba(44, 62, 80, 0.9);
            border: 2px solid #e74c3c;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .card-header {
            background: #e74c3c;
            color: white;
            font-weight: bold;
            border-radius: 13px 13px 0 0 !important;
        }
        .btn-reset {
            background: #e74c3c;
            color: white;
            border: none;
        }
        .btn-reset:hover {
            background: #c0392b;
            color: white;
        }
        .user-card {
            background: rgba(52, 73, 94, 0.5);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid #34495e;
        }
        .badge-owner { background: #e74c3c; }
        .badge-manager { background: #3498db; }
        .badge-trainer { background: #2ecc71; }
        .badge-frontdesk { background: #f39c12; }
        .badge-active { background: #27ae60; }
        .badge-inactive { background: #95a5a6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-center mb-4">
            <h1 class="text-white">
                <i class="bi bi-people-fill"></i> User Management & Password Reset
            </h1>
            <p class="text-muted">Manage all user accounts and reset passwords</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>-fill"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Reset All Default Users -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning-fill"></i> Quick Reset - All Default Users
            </div>
            <div class="card-body">
                <p>Reset passwords for all 4 default users to <code>password123</code> and set status to Active:</p>
                <ul class="mb-3">
                    <li><strong>john.smith@fitlife.com</strong> - Owner</li>
                    <li><strong>sarah.j@fitlife.com</strong> - Manager</li>
                    <li><strong>mike.w@fitlife.com</strong> - Trainer</li>
                    <li><strong>emily.d@fitlife.com</strong> - Front Desk</li>
                </ul>
                <form method="POST" onsubmit="return confirm('Reset all default user passwords to password123?')">
                    <button type="submit" name="reset_all" class="btn btn-reset btn-lg w-100">
                        <i class="bi bi-arrow-clockwise"></i> Reset All Default Users Now
                    </button>
                </form>
            </div>
        </div>

        <!-- Current Users List -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul"></i> All User Accounts (<?php echo count($users); ?>)
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="alert alert-warning">
                        No users found. Database may not be imported properly.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($users as $user): 
                            $roleBadgeClass = 'badge-owner';
                            switch($user['role_name']) {
                                case 'Manager': $roleBadgeClass = 'badge-manager'; break;
                                case 'Trainer': $roleBadgeClass = 'badge-trainer'; break;
                                case 'Front Desk': $roleBadgeClass = 'badge-frontdesk'; break;
                            }
                            $statusBadgeClass = $user['status'] === 'Active' ? 'badge-active' : 'badge-inactive';
                        ?>
                            <div class="col-md-6">
                                <div class="user-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                <small class="text-muted">#<?php echo $user['staff_id']; ?></small>
                                            </h5>
                                            <p class="mb-1">
                                                <i class="bi bi-envelope"></i> 
                                                <code><?php echo htmlspecialchars($user['email']); ?></code>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="badge <?php echo $roleBadgeClass; ?> mb-1">
                                                <?php echo htmlspecialchars($user['role_name']); ?>
                                            </span><br>
                                            <span class="badge <?php echo $statusBadgeClass; ?>">
                                                <?php echo htmlspecialchars($user['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#resetModal<?php echo $user['staff_id']; ?>">
                                            <i class="bi bi-key"></i> Reset Password
                                        </button>
                                        <?php if ($user['status'] !== 'Active'): ?>
                                        <form method="POST" class="d-inline" style="flex: 1;">
                                            <input type="hidden" name="email" value="<?php echo $user['email']; ?>">
                                            <input type="hidden" name="new_password" value="password123">
                                            <button type="submit" name="reset_single" class="btn btn-sm btn-success w-100">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal<?php echo $user['staff_id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Reset Password Modal -->
                                <div class="modal fade" id="resetModal<?php echo $user['staff_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reset Password - <?php echo htmlspecialchars($user['first_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">New Password</label>
                                                        <input type="text" class="form-control" name="new_password" 
                                                               value="password123" required>
                                                        <small class="text-muted">Leave as 'password123' or enter custom password</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="reset_single" class="btn btn-warning">
                                                        <i class="bi bi-key"></i> Reset Password
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $user['staff_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger">
                                                <h5 class="modal-title text-white">Deactivate User</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="staff_id" value="<?php echo $user['staff_id']; ?>">
                                                    <p>Are you sure you want to deactivate:</p>
                                                    <p><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong><br>
                                                    <?php echo htmlspecialchars($user['email']); ?></p>
                                                    <div class="alert alert-warning">
                                                        <i class="bi bi-info-circle"></i> User will be set to Inactive status but data will be preserved.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_user" class="btn btn-danger">
                                                        <i class="bi bi-trash"></i> Deactivate
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Create New User -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus-fill"></i> Create New User
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Password</label>
                            <input type="text" class="form-control" name="password" value="password123">
                            <small class="text-muted">Default is 'password123' - user can change later</small>
                        </div>
                    </div>
                    <button type="submit" name="create_user" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg"></i> Create User Account
                    </button>
                </form>
            </div>
        </div>

        <!-- Navigation -->
        <div class="card">
            <div class="card-body text-center">
                <h5>Navigation</h5>
                <a href="login_simple.php" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right"></i> Go to Login
                </a>
                <a href="index.php" class="btn btn-info">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="check_system.php" class="btn btn-warning">
                    <i class="bi bi-gear"></i> System Check
                </a>
            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> Gym Management System - User Management Tool
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>