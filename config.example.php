<?php
/**
 * Gym Management System - Configuration File
 * Copy this file and rename to config.php
 * Update the values according to your environment
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================

// Database Host (usually 'localhost' for cPanel)
define('DB_HOST', 'localhost');

// Database Name (created in cPanel)
define('DB_NAME', 'your_database_name');

// Database Username
define('DB_USER', 'your_database_user');

// Database Password
define('DB_PASS', 'your_database_password');

// Database Charset
define('DB_CHARSET', 'utf8mb4');

// ============================================
// APPLICATION CONFIGURATION
// ============================================

// Application Environment ('development' or 'production')
define('APP_ENV', 'development');

// Base URL of your application (with trailing slash)
define('BASE_URL', 'https://yourdomain.com/gym/');

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur'); // Change to your timezone

// ============================================
// SECURITY SETTINGS
// ============================================

// Session lifetime in seconds (default: 1800 = 30 minutes)
define('SESSION_LIFETIME', 1800);

// CSRF token expiry in seconds (default: 3600 = 1 hour)
define('CSRF_TOKEN_LIFETIME', 3600);

// Password minimum length
define('PASSWORD_MIN_LENGTH', 8);

// Maximum login attempts before lockout
define('MAX_LOGIN_ATTEMPTS', 5);

// Login lockout duration in seconds (default: 900 = 15 minutes)
define('LOGIN_LOCKOUT_DURATION', 900);

// ============================================
// FILE UPLOAD SETTINGS
// ============================================

// Upload directory (relative to application root)
define('UPLOAD_DIR', 'uploads/');

// Maximum file size for uploads (in bytes, 5MB default)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Allowed file types for member photos
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);

// ============================================
// EMAIL CONFIGURATION (Optional)
// ============================================

// SMTP Settings (for sending emails)
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@yourgym.com');
define('SMTP_FROM_NAME', 'Gym Management System');

// ============================================
// GYM INFORMATION
// ============================================

// These can also be managed via database system_settings table
define('GYM_NAME', 'FitLife Gym');
define('GYM_ADDRESS', '123 Fitness Street, Wellness City');
define('GYM_PHONE', '+1-234-567-8900');
define('GYM_EMAIL', 'info@fitlifegym.com');
define('GYM_WEBSITE', 'https://www.fitlifegym.com');

// ============================================
// BUSINESS SETTINGS
// ============================================

// Default membership expiry alert (days before expiry)
define('EXPIRY_ALERT_DAYS', 5);

// Default trainer commission percentage
define('DEFAULT_COMMISSION_RATE', 20.00);

// Receipt number prefix
define('RECEIPT_PREFIX', 'GYM');

// Currency symbol
define('CURRENCY_SYMBOL', '$');

// Currency code
define('CURRENCY_CODE', 'USD');

// Tax rate percentage (0 for no tax)
define('TAX_RATE', 0);

// ============================================
// PAGINATION SETTINGS
// ============================================

// Number of records per page
define('RECORDS_PER_PAGE', 20);

// ============================================
// ERROR REPORTING
// ============================================

if (APP_ENV === 'development') {
    // Show all errors in development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Hide errors in production, log them instead
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// ============================================
// PATH DEFINITIONS
// ============================================

// Application root directory
define('ROOT_PATH', __DIR__ . '/');

// Include path
define('INCLUDE_PATH', ROOT_PATH . 'includes/');

// Template path
define('TEMPLATE_PATH', ROOT_PATH . 'templates/');

// Log path
define('LOG_PATH', ROOT_PATH . 'logs/');

// ============================================
// FEATURE FLAGS
// ============================================

// Enable/disable features
define('FEATURE_QR_CODE', true);
define('FEATURE_EMAIL_NOTIFICATIONS', false);
define('FEATURE_SMS_NOTIFICATIONS', false);
define('FEATURE_ONLINE_PAYMENTS', false);
define('FEATURE_MEMBER_PORTAL', false);
define('FEATURE_BIOMETRIC_ATTENDANCE', false);

// ============================================
// API KEYS (if using third-party services)
// ============================================

// SMS API (Twilio, etc.)
define('SMS_API_KEY', '');
define('SMS_API_SECRET', '');

// Payment Gateway (Stripe, PayPal, etc.)
define('PAYMENT_API_KEY', '');
define('PAYMENT_SECRET_KEY', '');

// QR Code Generator API
define('QR_CODE_API_KEY', '');

// ============================================
// MAINTENANCE MODE
// ============================================

// Set to true to enable maintenance mode
define('MAINTENANCE_MODE', false);

// Maintenance message
define('MAINTENANCE_MESSAGE', 'System is currently under maintenance. Please check back soon.');

// IPs allowed during maintenance (comma-separated)
define('MAINTENANCE_ALLOWED_IPS', '127.0.0.1');

// ============================================
// BACKUP SETTINGS
// ============================================

// Automatic backup enabled
define('AUTO_BACKUP_ENABLED', true);

// Backup frequency (daily, weekly, monthly)
define('BACKUP_FREQUENCY', 'daily');

// Backup retention days
define('BACKUP_RETENTION_DAYS', 30);

// Backup directory
define('BACKUP_DIR', ROOT_PATH . 'backups/');

// ============================================
// LOGGING SETTINGS
// ============================================

// Log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
define('LOG_LEVEL', 'INFO');

// Log to file
define('LOG_TO_FILE', true);

// Log to database
define('LOG_TO_DATABASE', true);

// Log file rotation (daily, weekly, monthly)
define('LOG_ROTATION', 'daily');

// ============================================
// CACHE SETTINGS
// ============================================

// Enable caching
define('CACHE_ENABLED', true);

// Cache driver (file, redis, memcached)
define('CACHE_DRIVER', 'file');

// Cache lifetime in seconds
define('CACHE_LIFETIME', 3600);

// Cache directory (for file-based cache)
define('CACHE_DIR', ROOT_PATH . 'cache/');

// ============================================
// PERFORMANCE SETTINGS
// ============================================

// Enable query caching
define('QUERY_CACHE_ENABLED', true);

// Enable output compression
define('OUTPUT_COMPRESSION', true);

// Memory limit
ini_set('memory_limit', '256M');

// Maximum execution time
ini_set('max_execution_time', '300');

// ============================================
// DO NOT EDIT BELOW THIS LINE
// ============================================

// Load database connection
require_once 'db_connect.php';

// Initialize application
// Add any initialization code here

?>
