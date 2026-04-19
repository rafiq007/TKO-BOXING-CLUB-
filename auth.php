<?php
/**
 * Authentication and Authorization System
 * Role-based access control for staff
 */

require_once 'db_connect.php';

class Auth {
    
    /**
     * Login staff member
     * 
     * @param string $email
     * @param string $password
     * @return array Result with success status and user data
     */
    public static function login(string $email, string $password): array {
        try {
            // Get staff member by email
            $staff = Database::querySingle(
                "SELECT s.*, r.role_name 
                 FROM staff s
                 JOIN roles r ON s.role_id = r.role_id
                 WHERE s.email = ? AND s.status = 'Active'",
                [$email]
            );

            if (!$staff) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            // Verify password
            if (!verify_password($password, $staff['password_hash'])) {
                log_activity(
                    $staff['staff_id'],
                    'LOGIN_FAILED',
                    'staff',
                    $staff['staff_id'],
                    'Invalid password attempt'
                );

                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

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

            return [
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'staff_id' => $staff['staff_id'],
                    'name' => $staff['first_name'] . ' ' . $staff['last_name'],
                    'role' => $staff['role_name'],
                    'email' => $staff['email']
                ]
            ];

        } catch (Exception $e) {
            error_log("Login failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }

    /**
     * Logout current user
     * 
     * @return array
     */
    public static function logout(): array {
        if (isset($_SESSION['staff_id'])) {
            log_activity(
                $_SESSION['staff_id'],
                'LOGOUT',
                'staff',
                $_SESSION['staff_id'],
                'User logged out'
            );
        }

        session_unset();
        session_destroy();

        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool {
        // Check session timeout (30 minutes)
        $timeout = 1800;
        
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
                self::logout();
                return false;
            }
            
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        return false;
    }

    /**
     * Check if user has specific role
     * 
     * @param string|array $roles Role name(s) to check
     * @return bool
     */
    public static function hasRole(string|array $roles): bool {
        if (!self::isLoggedIn()) {
            return false;
        }

        $userRole = $_SESSION['role_name'] ?? '';
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        
        return $userRole === $roles;
    }

    /**
     * Check if user has permission for action
     * 
     * @param string $permission Permission to check
     * @return bool
     */
    public static function hasPermission(string $permission): bool {
        if (!self::isLoggedIn()) {
            return false;
        }

        $role = $_SESSION['role_name'] ?? '';

        // Define role permissions
        $permissions = [
            'Owner' => [
                'view_dashboard',
                'manage_members',
                'manage_payments',
                'manage_attendance',
                'manage_staff',
                'manage_payroll',
                'view_reports',
                'manage_settings',
                'view_financials'
            ],
            'Manager' => [
                'view_dashboard',
                'manage_members',
                'manage_payments',
                'manage_attendance',
                'view_reports'
            ],
            'Trainer' => [
                'view_dashboard',
                'view_members',
                'manage_pt_sessions',
                'view_assigned_clients',
                'manage_workout_plans'
            ],
            'Front Desk' => [
                'view_dashboard',
                'view_members',
                'manage_attendance',
                'process_payments'
            ]
        ];

        return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
    }

    /**
     * Require login (redirect if not logged in)
     * 
     * @param string $redirectUrl
     */
    public static function requireLogin(string $redirectUrl = 'login.php'): void {
        if (!self::isLoggedIn()) {
            header("Location: $redirectUrl");
            exit;
        }
    }

    /**
     * Require specific role (redirect if unauthorized)
     * 
     * @param string|array $roles
     * @param string $redirectUrl
     */
    public static function requireRole(string|array $roles, string $redirectUrl = 'unauthorized.php'): void {
        self::requireLogin();
        
        if (!self::hasRole($roles)) {
            header("Location: $redirectUrl");
            exit;
        }
    }

    /**
     * Require specific permission (redirect if unauthorized)
     * 
     * @param string $permission
     * @param string $redirectUrl
     */
    public static function requirePermission(string $permission, string $redirectUrl = 'unauthorized.php'): void {
        self::requireLogin();
        
        if (!self::hasPermission($permission)) {
            header("Location: $redirectUrl");
            exit;
        }
    }

    /**
     * Change password
     * 
     * @param int $staffId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public static function changePassword(int $staffId, string $currentPassword, string $newPassword): array {
        try {
            // Get current password hash
            $staff = Database::querySingle(
                "SELECT password_hash FROM staff WHERE staff_id = ?",
                [$staffId]
            );

            if (!$staff) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Verify current password
            if (!verify_password($currentPassword, $staff['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }

            // Validate new password
            if (strlen($newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'New password must be at least 8 characters long'
                ];
            }

            // Hash new password
            $newHash = hash_password($newPassword);

            // Update password
            Database::execute(
                "UPDATE staff SET password_hash = ? WHERE staff_id = ?",
                [$newHash, $staffId]
            );

            log_activity(
                $staffId,
                'PASSWORD_CHANGED',
                'staff',
                $staffId,
                'Password changed successfully'
            );

            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];

        } catch (Exception $e) {
            error_log("Password change failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Password change failed. Please try again.'
            ];
        }
    }

    /**
     * Create new staff member (Owner only)
     * 
     * @param array $staffData
     * @return array
     */
    public static function createStaff(array $staffData): array {
        try {
            // Validate required fields
            $required = ['role_id', 'first_name', 'last_name', 'email', 'password'];
            foreach ($required as $field) {
                if (!isset($staffData[$field]) || empty($staffData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ];
                }
            }

            // Check if email already exists
            $existing = Database::querySingle(
                "SELECT staff_id FROM staff WHERE email = ?",
                [$staffData['email']]
            );

            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Email already exists'
                ];
            }

            // Hash password
            $passwordHash = hash_password($staffData['password']);

            // Insert staff member
            $query = "INSERT INTO staff 
                (role_id, first_name, last_name, email, phone, password_hash, 
                 base_salary, commission_rate, hire_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')";
            
            Database::execute($query, [
                $staffData['role_id'],
                $staffData['first_name'],
                $staffData['last_name'],
                $staffData['email'],
                $staffData['phone'] ?? null,
                $passwordHash,
                $staffData['base_salary'] ?? 0,
                $staffData['commission_rate'] ?? 0,
                $staffData['hire_date'] ?? date('Y-m-d')
            ]);

            $staffId = Database::lastInsertId();

            log_activity(
                $_SESSION['staff_id'] ?? null,
                'STAFF_CREATED',
                'staff',
                (int)$staffId,
                "New staff member created: {$staffData['email']}"
            );

            return [
                'success' => true,
                'message' => 'Staff member created successfully',
                'data' => [
                    'staff_id' => $staffId
                ]
            ];

        } catch (Exception $e) {
            error_log("Staff creation failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Staff creation failed. Please try again.'
            ];
        }
    }

    /**
     * Get current user information
     * 
     * @return array|false
     */
    public static function getCurrentUser(): array|false {
        if (!self::isLoggedIn()) {
            return false;
        }

        return Database::querySingle(
            "SELECT s.*, r.role_name 
             FROM staff s
             JOIN roles r ON s.role_id = r.role_id
             WHERE s.staff_id = ?",
            [$_SESSION['staff_id']]
        );
    }
}

// Handle AJAX authentication requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_action'])) {
    header('Content-Type: application/json');
    
    $action = sanitize_input($_POST['auth_action']);
    
    switch ($action) {
        case 'login':
            $email = sanitize_input($_POST['email']);
            $password = $_POST['password']; // Don't sanitize password
            
            $result = Auth::login($email, $password);
            echo json_encode($result);
            break;

        case 'logout':
            $result = Auth::logout();
            echo json_encode($result);
            break;

        case 'change_password':
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }
            
            $staffId = (int)$_SESSION['staff_id'];
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            
            $result = Auth::changePassword($staffId, $currentPassword, $newPassword);
            echo json_encode($result);
            break;

        case 'create_staff':
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                exit;
            }
            
            if (!Auth::hasPermission('manage_staff')) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            $staffData = [
                'role_id' => (int)$_POST['role_id'],
                'first_name' => sanitize_input($_POST['first_name']),
                'last_name' => sanitize_input($_POST['last_name']),
                'email' => sanitize_input($_POST['email']),
                'phone' => sanitize_input($_POST['phone'] ?? null),
                'password' => $_POST['password'],
                'base_salary' => (float)($_POST['base_salary'] ?? 0),
                'commission_rate' => (float)($_POST['commission_rate'] ?? 0),
                'hire_date' => sanitize_input($_POST['hire_date'] ?? date('Y-m-d'))
            ];
            
            $result = Auth::createStaff($staffData);
            echo json_encode($result);
            break;

        case 'check_session':
            echo json_encode([
                'success' => true,
                'logged_in' => Auth::isLoggedIn(),
                'user' => Auth::isLoggedIn() ? [
                    'name' => $_SESSION['staff_name'],
                    'role' => $_SESSION['role_name']
                ] : null
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
?>
