<?php
/**
 * Member Management System
 * Handle member registration, updates, and queries
 */

require_once 'db_connect.php';

class MemberManager {
    
    /**
     * Register new member
     * 
     * @param array $memberData Member information
     * @return array Result with success status and member ID
     */
    public static function registerMember(array $memberData): array {
        try {
            // Validate required fields
            $required = ['first_name', 'last_name', 'phone'];
            foreach ($required as $field) {
                if (!isset($memberData[$field]) || empty($memberData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ];
                }
            }

            Database::beginTransaction();

            // Generate unique member code
            $memberCode = self::generateMemberCode();

            // Insert member
            $query = "INSERT INTO members 
                (member_code, first_name, last_name, email, phone, date_of_birth, 
                 gender, address, emergency_contact, emergency_phone, blood_group, 
                 medical_conditions, registration_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
            
            $params = [
                $memberCode,
                $memberData['first_name'],
                $memberData['last_name'],
                $memberData['email'] ?? null,
                $memberData['phone'],
                $memberData['date_of_birth'] ?? null,
                $memberData['gender'] ?? 'Male',
                $memberData['address'] ?? null,
                $memberData['emergency_contact'] ?? null,
                $memberData['emergency_phone'] ?? null,
                $memberData['blood_group'] ?? null,
                $memberData['medical_conditions'] ?? null,
                date('Y-m-d')
            ];

            Database::execute($query, $params);
            $memberId = Database::lastInsertId();

            // Log activity
            log_activity(
                $memberData['registered_by'] ?? null,
                'MEMBER_REGISTERED',
                'members',
                (int)$memberId,
                "New member registered: $memberCode"
            );

            Database::commit();

            return [
                'success' => true,
                'message' => 'Member registered successfully',
                'data' => [
                    'member_id' => $memberId,
                    'member_code' => $memberCode
                ]
            ];

        } catch (Exception $e) {
            Database::rollback();
            error_log("Member registration failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }

    /**
     * Update member information
     * 
     * @param int $memberId
     * @param array $memberData
     * @return array
     */
    public static function updateMember(int $memberId, array $memberData): array {
        try {
            $allowedFields = [
                'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
                'gender', 'address', 'emergency_contact', 'emergency_phone',
                'blood_group', 'medical_conditions', 'status'
            ];

            $updateFields = [];
            $params = [];

            foreach ($memberData as $field => $value) {
                if (in_array($field, $allowedFields)) {
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

            $params[] = $memberId;
            $query = "UPDATE members SET " . implode(', ', $updateFields) . " WHERE member_id = ?";
            
            Database::execute($query, $params);

            log_activity(
                $memberData['updated_by'] ?? null,
                'MEMBER_UPDATED',
                'members',
                $memberId,
                "Member information updated"
            );

            return [
                'success' => true,
                'message' => 'Member updated successfully'
            ];

        } catch (Exception $e) {
            error_log("Member update failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Update failed. Please try again.'
            ];
        }
    }

    /**
     * Get member by ID or code
     * 
     * @param string|int $identifier
     * @return array|false
     */
    public static function getMember(string|int $identifier): array|false {
        $query = is_numeric($identifier)
            ? "SELECT m.*, 
                      ms.membership_id,
                      ms.start_date as membership_start,
                      ms.end_date as membership_end,
                      mt.type_name as membership_type,
                      DATEDIFF(ms.end_date, CURDATE()) as days_remaining
               FROM members m
               LEFT JOIN memberships ms ON m.member_id = ms.member_id AND ms.status = 'Active'
               LEFT JOIN membership_types mt ON ms.type_id = mt.type_id
               WHERE m.member_id = ?"
            : "SELECT m.*, 
                      ms.membership_id,
                      ms.start_date as membership_start,
                      ms.end_date as membership_end,
                      mt.type_name as membership_type,
                      DATEDIFF(ms.end_date, CURDATE()) as days_remaining
               FROM members m
               LEFT JOIN memberships ms ON m.member_id = ms.member_id AND ms.status = 'Active'
               LEFT JOIN membership_types mt ON ms.type_id = mt.type_id
               WHERE m.member_code = ?";

        return Database::querySingle($query, [$identifier]);
    }

    /**
     * Search members
     * 
     * @param string $searchTerm
     * @param array $filters
     * @return array
     */
    public static function searchMembers(string $searchTerm = '', array $filters = []): array {
        $query = "SELECT m.member_id, m.member_code, 
                         CONCAT(m.first_name, ' ', m.last_name) as member_name,
                         m.phone, m.email, m.status, m.registration_date,
                         ms.end_date as membership_end,
                         mt.type_name as membership_type
                  FROM members m
                  LEFT JOIN memberships ms ON m.member_id = ms.member_id AND ms.status = 'Active'
                  LEFT JOIN membership_types mt ON ms.type_id = mt.type_id
                  WHERE 1=1";
        
        $params = [];

        if (!empty($searchTerm)) {
            $query .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ? OR m.member_code LIKE ?)";
            $searchPattern = "%$searchTerm%";
            $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        }

        if (isset($filters['status'])) {
            $query .= " AND m.status = ?";
            $params[] = $filters['status'];
        }

        $query .= " ORDER BY m.registration_date DESC LIMIT 100";

        return Database::query($query, $params) ?: [];
    }

    /**
     * Get members expiring soon
     * 
     * @param int $days Number of days ahead to check
     * @return array
     */
    public static function getExpiringMembers(int $days = 5): array {
        $query = "CALL sp_get_expiring_members(?)";
        return Database::query($query, [$days]) ?: [];
    }

    /**
     * Generate unique member code
     * 
     * @return string
     */
    private static function generateMemberCode(): string {
        $result = Database::querySingle(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(member_code, 4) AS UNSIGNED)), 0) + 1 as next_num 
             FROM members"
        );
        
        return 'MEM' . str_pad($result['next_num'], 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get member statistics
     * 
     * @return array
     */
    public static function getMemberStats(): array {
        $stats = [];

        // Total members
        $result = Database::querySingle("SELECT COUNT(*) as total FROM members");
        $stats['total_members'] = $result['total'];

        // Active members
        $result = Database::querySingle("SELECT COUNT(*) as total FROM members WHERE status = 'Active'");
        $stats['active_members'] = $result['total'];

        // Expired members
        $result = Database::querySingle("SELECT COUNT(*) as total FROM members WHERE status = 'Expired'");
        $stats['expired_members'] = $result['total'];

        // New members this month
        $result = Database::querySingle(
            "SELECT COUNT(*) as total FROM members 
             WHERE MONTH(registration_date) = MONTH(CURDATE()) 
             AND YEAR(registration_date) = YEAR(CURDATE())"
        );
        $stats['new_this_month'] = $result['total'];

        // Expiring soon
        $expiringDays = get_setting('expiry_alert_days', 5);
        $result = Database::querySingle(
            "SELECT COUNT(*) as total FROM memberships 
             WHERE status = 'Active' 
             AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)",
            [$expiringDays]
        );
        $stats['expiring_soon'] = $result['total'];

        return $stats;
    }

    /**
     * Upload member photo
     * 
     * @param int $memberId
     * @param array $file $_FILES array element
     * @return array
     */
    public static function uploadMemberPhoto(int $memberId, array $file): array {
        try {
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowedTypes)) {
                return [
                    'success' => false,
                    'message' => 'Only JPG and PNG files are allowed'
                ];
            }

            if ($file['size'] > $maxSize) {
                return [
                    'success' => false,
                    'message' => 'File size must be less than 5MB'
                ];
            }

            // Create upload directory if not exists
            $uploadDir = 'uploads/members/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'member_' . $memberId . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database
                Database::execute(
                    "UPDATE members SET profile_photo = ? WHERE member_id = ?",
                    [$filepath, $memberId]
                );

                return [
                    'success' => true,
                    'message' => 'Photo uploaded successfully',
                    'filepath' => $filepath
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to upload file'
                ];
            }

        } catch (Exception $e) {
            error_log("Photo upload failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Upload failed. Please try again.'
            ];
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = sanitize_input($_POST['action']);
    
    // Verify CSRF token for state-changing operations
    if (!in_array($action, ['get_member', 'search_members', 'get_stats'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
    }
    
    switch ($action) {
        case 'register':
            $memberData = [
                'first_name' => sanitize_input($_POST['first_name']),
                'last_name' => sanitize_input($_POST['last_name']),
                'email' => sanitize_input($_POST['email'] ?? null),
                'phone' => sanitize_input($_POST['phone']),
                'date_of_birth' => sanitize_input($_POST['date_of_birth'] ?? null),
                'gender' => sanitize_input($_POST['gender'] ?? 'Male'),
                'address' => sanitize_input($_POST['address'] ?? null),
                'emergency_contact' => sanitize_input($_POST['emergency_contact'] ?? null),
                'emergency_phone' => sanitize_input($_POST['emergency_phone'] ?? null),
                'blood_group' => sanitize_input($_POST['blood_group'] ?? null),
                'medical_conditions' => sanitize_input($_POST['medical_conditions'] ?? null),
                'registered_by' => (int)($_SESSION['staff_id'] ?? 0)
            ];
            
            $result = MemberManager::registerMember($memberData);
            echo json_encode($result);
            break;

        case 'update':
            $memberId = (int)$_POST['member_id'];
            $memberData = [
                'first_name' => sanitize_input($_POST['first_name'] ?? null),
                'last_name' => sanitize_input($_POST['last_name'] ?? null),
                'email' => sanitize_input($_POST['email'] ?? null),
                'phone' => sanitize_input($_POST['phone'] ?? null),
                'date_of_birth' => sanitize_input($_POST['date_of_birth'] ?? null),
                'gender' => sanitize_input($_POST['gender'] ?? null),
                'address' => sanitize_input($_POST['address'] ?? null),
                'emergency_contact' => sanitize_input($_POST['emergency_contact'] ?? null),
                'emergency_phone' => sanitize_input($_POST['emergency_phone'] ?? null),
                'blood_group' => sanitize_input($_POST['blood_group'] ?? null),
                'medical_conditions' => sanitize_input($_POST['medical_conditions'] ?? null),
                'status' => sanitize_input($_POST['status'] ?? null),
                'updated_by' => (int)($_SESSION['staff_id'] ?? 0)
            ];
            
            // Remove null values
            $memberData = array_filter($memberData, fn($value) => $value !== null);
            
            $result = MemberManager::updateMember($memberId, $memberData);
            echo json_encode($result);
            break;

        case 'get_member':
            $identifier = sanitize_input($_POST['identifier']);
            $member = MemberManager::getMember($identifier);
            
            if ($member) {
                echo json_encode(['success' => true, 'data' => $member]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Member not found']);
            }
            break;

        case 'search_members':
            // Simple direct query without complex joins
            try {
                $pdo = Database::getConnection();
                $searchTerm = sanitize_input($_POST['search'] ?? '');
                $statusFilter = sanitize_input($_POST['status'] ?? '');
                
                $query = "SELECT 
                    m.member_id,
                    m.member_code,
                    m.first_name,
                    m.last_name,
                    m.phone,
                    m.email,
                    m.status,
                    m.registration_date,
                    m.created_at
                FROM members m
                WHERE 1=1";
                
                $params = [];
                
                if (!empty($searchTerm)) {
                    $query .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ? OR m.member_code LIKE ? OR m.email LIKE ?)";
                    $searchPattern = "%$searchTerm%";
                    $params = [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern];
                }
                
                if (!empty($statusFilter)) {
                    $query .= " AND m.status = ?";
                    $params[] = $statusFilter;
                }
                
                $query .= " ORDER BY m.created_at DESC LIMIT 100";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $members, 'count' => count($members)]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'update_member':
            // Update member information
            try {
                if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                    exit;
                }
                
                $pdo = Database::getConnection();
                $memberId = intval($_POST['member_id']);
                
                $query = "UPDATE members SET 
                    first_name = ?,
                    last_name = ?,
                    phone = ?,
                    email = ?,
                    gender = ?,
                    date_of_birth = ?,
                    address = ?,
                    emergency_contact = ?,
                    emergency_phone = ?,
                    blood_group = ?,
                    medical_conditions = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE member_id = ?";
                
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([
                    sanitize_input($_POST['first_name']),
                    sanitize_input($_POST['last_name']),
                    sanitize_input($_POST['phone']),
                    sanitize_input($_POST['email']),
                    sanitize_input($_POST['gender']),
                    sanitize_input($_POST['date_of_birth']),
                    sanitize_input($_POST['address']),
                    sanitize_input($_POST['emergency_contact']),
                    sanitize_input($_POST['emergency_phone']),
                    sanitize_input($_POST['blood_group']),
                    sanitize_input($_POST['medical_conditions']),
                    sanitize_input($_POST['status']),
                    $memberId
                ]);
                
                if ($result) {
                    // Log activity
                    log_activity(
                        $_SESSION['staff_id'],
                        'MEMBER_UPDATED',
                        'members',
                        $memberId,
                        "Updated member information"
                    );
                    
                    echo json_encode(['success' => true, 'message' => 'Member updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update member']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete_member':
            // Delete (soft delete) member
            try {
                if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                    exit;
                }
                
                // Check permission
                if (!Auth::hasRole('Owner') && !Auth::hasRole('Manager')) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied']);
                    exit;
                }
                
                $pdo = Database::getConnection();
                $memberId = intval($_POST['member_id']);
                
                // Soft delete - set status to Cancelled
                $stmt = $pdo->prepare("UPDATE members SET status = 'Cancelled', updated_at = NOW() WHERE member_id = ?");
                $result = $stmt->execute([$memberId]);
                
                if ($result) {
                    // Log activity
                    log_activity(
                        $_SESSION['staff_id'],
                        'MEMBER_DELETED',
                        'members',
                        $memberId,
                        "Deleted member (soft delete)"
                    );
                    
                    echo json_encode(['success' => true, 'message' => 'Member deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete member']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_expiring':
            $days = (int)($_POST['days'] ?? 5);
            $members = MemberManager::getExpiringMembers($days);
            echo json_encode(['success' => true, 'data' => $members]);
            break;

        case 'get_stats':
            $stats = MemberManager::getMemberStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    header('Content-Type: application/json');
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
    
    $memberId = (int)$_POST['member_id'];
    $result = MemberManager::uploadMemberPhoto($memberId, $_FILES['photo']);
    echo json_encode($result);
    exit;
}
?>