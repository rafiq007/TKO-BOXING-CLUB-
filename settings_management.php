<?php
/**
 * Settings Management
 * Handle system settings CRUD operations
 */

session_start();
require_once 'db_connect.php';
require_once 'auth.php';

// Set JSON header
header('Content-Type: application/json');

// Require authentication
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get database connection
try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get the action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Get all settings
 */
if ($action === 'get_settings') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Set defaults if not exists
        $defaults = [
            'gym_name' => 'TKO Boxing Club',
            'gym_phone' => '+44 20 1234 5678',
            'gym_email' => 'info@tkoboxing.co.uk',
            'gym_website' => 'https://tkoboxing.co.uk',
            'gym_address' => '123 Boxing Street, London, UK',
            'currency' => 'GBP',
            'currency_symbol' => '£',
            'timezone' => 'Europe/London',
            'commission_rate' => '20',
            'expiry_alert_days' => '5'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading settings: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Save gym information
 */
if ($action === 'save_gym_info') {
    // Check permission
    if (!Auth::hasRole('Owner')) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    try {
        $gymName = sanitize_input($_POST['gym_name']);
        $gymPhone = sanitize_input($_POST['gym_phone']);
        $gymEmail = sanitize_input($_POST['gym_email']);
        $gymWebsite = sanitize_input($_POST['gym_website']);
        $gymAddress = sanitize_input($_POST['gym_address']);
        
        // Prepare update/insert statement
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        
        // Save each setting
        $stmt->execute(['gym_name', $gymName]);
        $stmt->execute(['gym_phone', $gymPhone]);
        $stmt->execute(['gym_email', $gymEmail]);
        $stmt->execute(['gym_website', $gymWebsite]);
        $stmt->execute(['gym_address', $gymAddress]);
        
        // Log activity
        log_activity(
            $_SESSION['staff_id'],
            'SETTINGS_UPDATE',
            'settings',
            0,
            'Updated gym information'
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Gym information updated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error saving gym information: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Save system preferences
 */
if ($action === 'save_preferences') {
    // Check permission
    if (!Auth::hasRole('Owner')) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    try {
        $currency = sanitize_input($_POST['currency']);
        $timezone = sanitize_input($_POST['timezone']);
        $commissionRate = floatval($_POST['commission_rate']);
        $expiryAlertDays = intval($_POST['expiry_alert_days']);
        
        // Determine currency symbol
        $currencySymbol = match($currency) {
            'GBP' => '£',
            'USD' => '$',
            'EUR' => '€',
            default => '£'
        };
        
        // Prepare update/insert statement
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        
        // Save each preference
        $stmt->execute(['currency', $currency]);
        $stmt->execute(['currency_symbol', $currencySymbol]);
        $stmt->execute(['timezone', $timezone]);
        $stmt->execute(['commission_rate', $commissionRate]);
        $stmt->execute(['expiry_alert_days', $expiryAlertDays]);
        
        // Log activity
        log_activity(
            $_SESSION['staff_id'],
            'SETTINGS_UPDATE',
            'settings',
            0,
            'Updated system preferences'
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'System preferences updated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error saving preferences: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Get membership types
 */
if ($action === 'get_membership_types') {
    try {
        $stmt = $pdo->query("
            SELECT * FROM membership_types 
            WHERE status = 'Active' 
            ORDER BY duration_months ASC
        ");
        
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $types
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading membership types: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Add membership type
 */
if ($action === 'add_membership_type') {
    // Check permission
    if (!Auth::hasRole('Owner')) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    try {
        $typeName = sanitize_input($_POST['type_name']);
        $durationMonths = intval($_POST['duration_months']);
        $price = floatval($_POST['price']);
        $description = sanitize_input($_POST['description'] ?? '');
        
        $stmt = $pdo->prepare("
            INSERT INTO membership_types 
            (type_name, duration_months, price, description, status, created_at) 
            VALUES (?, ?, ?, ?, 'Active', NOW())
        ");
        
        $stmt->execute([$typeName, $durationMonths, $price, $description]);
        
        // Log activity
        log_activity(
            $_SESSION['staff_id'],
            'MEMBERSHIP_TYPE_CREATED',
            'membership_types',
            $pdo->lastInsertId(),
            "Created membership type: $typeName"
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Membership type added successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error adding membership type: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Update membership type
 */
if ($action === 'update_membership_type') {
    // Check permission
    if (!Auth::hasRole('Owner')) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    try {
        $typeId = intval($_POST['type_id']);
        $typeName = sanitize_input($_POST['type_name']);
        $durationMonths = intval($_POST['duration_months']);
        $price = floatval($_POST['price']);
        $description = sanitize_input($_POST['description'] ?? '');
        
        $stmt = $pdo->prepare("
            UPDATE membership_types 
            SET type_name = ?, duration_months = ?, price = ?, description = ? 
            WHERE type_id = ?
        ");
        
        $stmt->execute([$typeName, $durationMonths, $price, $description, $typeId]);
        
        // Log activity
        log_activity(
            $_SESSION['staff_id'],
            'MEMBERSHIP_TYPE_UPDATED',
            'membership_types',
            $typeId,
            "Updated membership type: $typeName"
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Membership type updated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating membership type: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * Delete membership type
 */
if ($action === 'delete_membership_type') {
    // Check permission
    if (!Auth::hasRole('Owner')) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }
    
    try {
        $typeId = intval($_POST['type_id']);
        
        // Soft delete - set to inactive
        $stmt = $pdo->prepare("UPDATE membership_types SET status = 'Inactive' WHERE type_id = ?");
        $stmt->execute([$typeId]);
        
        // Log activity
        log_activity(
            $_SESSION['staff_id'],
            'MEMBERSHIP_TYPE_DELETED',
            'membership_types',
            $typeId,
            "Deleted membership type"
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Membership type deleted successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting membership type: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Invalid action
echo json_encode([
    'success' => false,
    'message' => 'Invalid action'
]);
?>