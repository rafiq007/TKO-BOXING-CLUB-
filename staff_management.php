<?php
/**
 * Staff Management System
 * Handle staff CRUD operations
 */

require_once 'db_connect.php';
require_once 'auth.php';

// Require owner or manager role
Auth::requirePermission('manage_staff');

class StaffManager {
    
    /**
     * Get all staff members
     * 
     * @return array
     */
    public static function getAllStaff(): array {
        $query = "SELECT s.*, r.role_name 
                  FROM staff s
                  JOIN roles r ON s.role_id = r.role_id
                  ORDER BY s.staff_id DESC";
        
        return Database::query($query) ?: [];
    }

    /**
     * Get staff by ID
     * 
     * @param int $staffId
     * @return array|false
     */
    public static function getStaffById(int $staffId): array|false {
        $query = "SELECT s.*, r.role_name 
                  FROM staff s
                  JOIN roles r ON s.role_id = r.role_id
                  WHERE s.staff_id = ?";
        
        return Database::querySingle($query, [$staffId]);
    }

    /**
     * Update staff information
     * 
     * @param int $staffId
     * @param array $staffData
     * @return array
     */
    public static function updateStaff(int $staffId, array $staffData): array {
        try {
            $allowedFields = [
                'first_name', 'last_name', 'email', 'phone', 'role_id',
                'base_salary', 'commission_rate', 'status'
            ];

            $updateFields = [];
            $params = [];

            foreach ($staffData as $field => $value) {
                if (in_array($field, $allowedFields) && $value !== null && $value !== '') {
                    $updateFields[] = "$field = ?";
                    $params[] = $value;
                }
            }

            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'No valid fields to update'
                ];
            }

            $params[] = $staffId;
            $query = "UPDATE staff SET " . implode(', ', $updateFields) . " WHERE staff_id = ?";
            
            Database::execute($query, $params);

            log_activity(
                $_SESSION['staff_id'] ?? null,
                'STAFF_UPDATED',
                'staff',
                $staffId,
                "Staff information updated"
            );

            return [
                'success' => true,
                'message' => 'Staff updated successfully'
            ];

        } catch (Exception $e) {
            error_log("Staff update failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Update failed. Please try again.'
            ];
        }
    }

    /**
     * Delete staff member (soft delete - set status to Inactive)
     * 
     * @param int $staffId
     * @return array
     */
    public static function deleteStaff(int $staffId): array {
        try {
            // Don't allow deleting yourself
            if ($staffId == $_SESSION['staff_id']) {
                return [
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ];
            }

            // Soft delete - set status to Inactive
            Database::execute(
                "UPDATE staff SET status = 'Inactive' WHERE staff_id = ?",
                [$staffId]
            );

            log_activity(
                $_SESSION['staff_id'] ?? null,
                'STAFF_DELETED',
                'staff',
                $staffId,
                "Staff member deactivated"
            );

            return [
                'success' => true,
                'message' => 'Staff member deleted successfully'
            ];

        } catch (Exception $e) {
            error_log("Staff deletion failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Deletion failed. Please try again.'
            ];
        }
    }

    /**
     * Get staff statistics
     * 
     * @return array
     */
    public static function getStaffStats(): array {
        $stats = [];

        // Total staff
        $result = Database::querySingle("SELECT COUNT(*) as total FROM staff WHERE status = 'Active'");
        $stats['total_staff'] = $result['total'];

        // By role
        $roles = Database::query(
            "SELECT r.role_name, COUNT(s.staff_id) as count 
             FROM roles r 
             LEFT JOIN staff s ON r.role_id = s.role_id AND s.status = 'Active'
             GROUP BY r.role_id"
        );
        $stats['by_role'] = $roles;

        // Total payroll
        $result = Database::querySingle(
            "SELECT SUM(base_salary) as total FROM staff WHERE status = 'Active'"
        );
        $stats['total_payroll'] = $result['total'] ?? 0;

        return $stats;
    }

    /**
     * Change staff password (admin function)
     * 
     * @param int $staffId
     * @param string $newPassword
     * @return array
     */
    public static function resetStaffPassword(int $staffId, string $newPassword): array {
        try {
            if (strlen($newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least 8 characters long'
                ];
            }

            $passwordHash = hash_password($newPassword);

            Database::execute(
                "UPDATE staff SET password_hash = ? WHERE staff_id = ?",
                [$passwordHash, $staffId]
            );

            log_activity(
                $_SESSION['staff_id'] ?? null,
                'PASSWORD_RESET',
                'staff',
                $staffId,
                "Password reset by administrator"
            );

            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];

        } catch (Exception $e) {
            error_log("Password reset failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Password reset failed. Please try again.'
            ];
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = sanitize_input($_POST['action']);
    
    // Verify CSRF token for state-changing operations
    if (!in_array($action, ['get_all_staff', 'get_staff'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
    }
    
    switch ($action) {
        case 'get_all_staff':
            $staff = StaffManager::getAllStaff();
            echo json_encode(['success' => true, 'data' => $staff]);
            break;

        case 'get_staff':
            $staffId = (int)$_POST['staff_id'];
            $staff = StaffManager::getStaffById($staffId);
            
            if ($staff) {
                echo json_encode(['success' => true, 'data' => $staff]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Staff not found']);
            }
            break;

        case 'update_staff':
            $staffId = (int)$_POST['staff_id'];
            $staffData = [
                'first_name' => sanitize_input($_POST['first_name'] ?? null),
                'last_name' => sanitize_input($_POST['last_name'] ?? null),
                'email' => sanitize_input($_POST['email'] ?? null),
                'phone' => sanitize_input($_POST['phone'] ?? null),
                'role_id' => isset($_POST['role_id']) ? (int)$_POST['role_id'] : null,
                'base_salary' => isset($_POST['base_salary']) ? (float)$_POST['base_salary'] : null,
                'commission_rate' => isset($_POST['commission_rate']) ? (float)$_POST['commission_rate'] : null,
                'status' => sanitize_input($_POST['status'] ?? null)
            ];
            
            // Remove null values
            $staffData = array_filter($staffData, fn($value) => $value !== null && $value !== '');
            
            $result = StaffManager::updateStaff($staffId, $staffData);
            echo json_encode($result);
            break;

        case 'delete_staff':
            $staffId = (int)$_POST['staff_id'];
            $result = StaffManager::deleteStaff($staffId);
            echo json_encode($result);
            break;

        case 'reset_password':
            // Only for admins
            if (!Auth::hasRole('Owner')) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            $staffId = (int)$_POST['staff_id'];
            $newPassword = $_POST['new_password'];
            
            $result = StaffManager::resetStaffPassword($staffId, $newPassword);
            echo json_encode($result);
            break;

        case 'get_stats':
            $stats = StaffManager::getStaffStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
?>
