<?php
/**
 * Member Check-in / Attendance System
 * For front desk staff to log member attendance
 */

require_once 'db_connect.php';

class AttendanceManager {
    
    /**
     * Check-in member by member code or ID
     * 
     * @param string|int $identifier Member code or member ID
     * @param int $staffId Staff processing check-in
     * @return array Result with success status and member info
     */
    public static function checkInMember(string|int $identifier, int $staffId): array {
        try {
            // Determine if identifier is member_code or member_id
            $query = is_numeric($identifier) 
                ? "SELECT * FROM members WHERE member_id = ?"
                : "SELECT * FROM members WHERE member_code = ?";
            
            $member = Database::querySingle($query, [$identifier]);
            
            if (!$member) {
                return [
                    'success' => false,
                    'message' => 'Member not found. Please check the member code/ID.'
                ];
            }

            // Check if member already checked in today
            $alreadyCheckedIn = Database::querySingle(
                "SELECT attendance_id, check_in_time, check_out_time 
                 FROM attendance 
                 WHERE member_id = ? AND DATE(check_in_time) = CURDATE() 
                 ORDER BY check_in_time DESC LIMIT 1",
                [$member['member_id']]
            );

            if ($alreadyCheckedIn && !$alreadyCheckedIn['check_out_time']) {
                return [
                    'success' => false,
                    'message' => 'Member already checked in today at ' . 
                               date('h:i A', strtotime($alreadyCheckedIn['check_in_time'])),
                    'already_checked_in' => true,
                    'attendance_id' => $alreadyCheckedIn['attendance_id']
                ];
            }

            // Get active membership
            $membership = Database::querySingle(
                "SELECT ms.*, mt.type_name,
                        DATEDIFF(ms.end_date, CURDATE()) as days_remaining
                 FROM memberships ms
                 JOIN membership_types mt ON ms.type_id = mt.type_id
                 WHERE ms.member_id = ? AND ms.status = 'Active'
                 ORDER BY ms.end_date DESC LIMIT 1",
                [$member['member_id']]
            );

            $membershipStatus = 'No Active Membership';
            $expiryWarning = false;
            
            if ($membership) {
                if ($membership['end_date'] < date('Y-m-d')) {
                    $membershipStatus = 'Expired on ' . date('M d, Y', strtotime($membership['end_date']));
                } elseif ($membership['days_remaining'] <= 5) {
                    $membershipStatus = 'Expires in ' . $membership['days_remaining'] . ' days';
                    $expiryWarning = true;
                } else {
                    $membershipStatus = 'Active until ' . date('M d, Y', strtotime($membership['end_date']));
                }
            }

            // Record check-in
            $checkInQuery = "INSERT INTO attendance (member_id, check_in_time, checked_in_by) 
                            VALUES (?, NOW(), ?)";
            
            Database::execute($checkInQuery, [$member['member_id'], $staffId]);
            $attendanceId = Database::lastInsertId();

            // Log activity
            log_activity(
                $staffId,
                'MEMBER_CHECKIN',
                'attendance',
                (int)$attendanceId,
                "Member {$member['member_code']} checked in"
            );

            return [
                'success' => true,
                'message' => 'Check-in successful!',
                'data' => [
                    'attendance_id' => $attendanceId,
                    'member_id' => $member['member_id'],
                    'member_code' => $member['member_code'],
                    'member_name' => $member['first_name'] . ' ' . $member['last_name'],
                    'phone' => $member['phone'],
                    'check_in_time' => date('h:i A'),
                    'membership_status' => $membershipStatus,
                    'expiry_warning' => $expiryWarning,
                    'membership_type' => $membership['type_name'] ?? null,
                    'member_photo' => $member['profile_photo']
                ]
            ];

        } catch (Exception $e) {
            error_log("Check-in failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Check-in failed. Please try again.'
            ];
        }
    }

    /**
     * Check-out member
     * 
     * @param int $attendanceId Attendance record ID
     * @return array Result with success status
     */
    public static function checkOutMember(int $attendanceId): array {
        try {
            $attendance = Database::querySingle(
                "SELECT * FROM attendance WHERE attendance_id = ?",
                [$attendanceId]
            );

            if (!$attendance) {
                return [
                    'success' => false,
                    'message' => 'Attendance record not found'
                ];
            }

            if ($attendance['check_out_time']) {
                return [
                    'success' => false,
                    'message' => 'Member already checked out'
                ];
            }

            Database::execute(
                "UPDATE attendance SET check_out_time = NOW() WHERE attendance_id = ?",
                [$attendanceId]
            );

            // Calculate duration
            $checkIn = new DateTime($attendance['check_in_time']);
            $checkOut = new DateTime();
            $duration = $checkIn->diff($checkOut);

            return [
                'success' => true,
                'message' => 'Check-out successful!',
                'data' => [
                    'duration' => $duration->format('%h hours %i minutes')
                ]
            ];

        } catch (Exception $e) {
            error_log("Check-out failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Check-out failed. Please try again.'
            ];
        }
    }

    /**
     * Get today's attendance summary
     * 
     * @return array
     */
    public static function getTodayAttendance(): array {
        $query = "SELECT 
            a.attendance_id,
            a.check_in_time,
            a.check_out_time,
            m.member_id,
            m.member_code,
            CONCAT(m.first_name, ' ', m.last_name) as member_name,
            m.phone,
            TIMESTAMPDIFF(MINUTE, a.check_in_time, COALESCE(a.check_out_time, NOW())) as duration_minutes
        FROM attendance a
        JOIN members m ON a.member_id = m.member_id
        WHERE DATE(a.check_in_time) = CURDATE()
        ORDER BY a.check_in_time DESC";

        return Database::query($query) ?: [];
    }

    /**
     * Get member attendance history
     * 
     * @param int $memberId
     * @param int $limit
     * @return array
     */
    public static function getMemberAttendance(int $memberId, int $limit = 30): array {
        $query = "SELECT 
            DATE(check_in_time) as date,
            check_in_time,
            check_out_time,
            TIMESTAMPDIFF(MINUTE, check_in_time, COALESCE(check_out_time, NOW())) as duration_minutes
        FROM attendance
        WHERE member_id = ?
        ORDER BY check_in_time DESC
        LIMIT ?";

        return Database::query($query, [$memberId, $limit]) ?: [];
    }

    /**
     * Get attendance statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getAttendanceStats(string $startDate, string $endDate): array {
        $query = "SELECT 
            DATE(check_in_time) as date,
            COUNT(DISTINCT member_id) as unique_members,
            COUNT(*) as total_visits,
            AVG(TIMESTAMPDIFF(MINUTE, check_in_time, COALESCE(check_out_time, NOW()))) as avg_duration_minutes
        FROM attendance
        WHERE DATE(check_in_time) BETWEEN ? AND ?
        GROUP BY DATE(check_in_time)
        ORDER BY date DESC";

        return Database::query($query, [$startDate, $endDate]) ?: [];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Verify CSRF token for state-changing operations
    if (!in_array($_POST['action'], ['get_today_attendance', 'get_member_attendance'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit;
        }
    }

    $action = sanitize_input($_POST['action']);
    
    switch ($action) {
        case 'check_in':
            $identifier = sanitize_input($_POST['identifier']);
            $staffId = (int)($_SESSION['staff_id'] ?? 0);
            
            if (!$staffId) {
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                break;
            }
            
            $result = AttendanceManager::checkInMember($identifier, $staffId);
            echo json_encode($result);
            break;

        case 'check_out':
            $attendanceId = (int)$_POST['attendance_id'];
            $result = AttendanceManager::checkOutMember($attendanceId);
            echo json_encode($result);
            break;

        case 'get_today_attendance':
            $attendance = AttendanceManager::getTodayAttendance();
            echo json_encode(['success' => true, 'data' => $attendance]);
            break;

        case 'get_member_attendance':
            $memberId = (int)$_POST['member_id'];
            $limit = (int)($_POST['limit'] ?? 30);
            $attendance = AttendanceManager::getMemberAttendance($memberId, $limit);
            echo json_encode(['success' => true, 'data' => $attendance]);
            break;

        case 'get_stats':
            $startDate = sanitize_input($_POST['start_date']);
            $endDate = sanitize_input($_POST['end_date']);
            $stats = AttendanceManager::getAttendanceStats($startDate, $endDate);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
?>
