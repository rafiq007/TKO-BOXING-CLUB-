<?php
/**
 * Main Dashboard - index.php
 * Requires authentication and role-based access control
 */

// Start output buffering to prevent header issues
ob_start();

// Suppress errors in production
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db_connect.php';
require_once 'auth.php';

// Require login - redirect to login page if not authenticated
Auth::requireLogin('login.php');

// Get current user information
$currentUser = Auth::getCurrentUser();

if (!$currentUser) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Get user permissions
$hasFullAccess = Auth::hasRole('Owner');
$canManageStaff = Auth::hasPermission('manage_staff');
$canViewReports = Auth::hasPermission('view_reports');
$canManagePayroll = Auth::hasPermission('manage_payroll');

// Clear any output before HTML
ob_clean();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gym Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --gym-primary: #D4AF37;      /* Light Gold border */
            --gym-secondary: #B8960E;    /* Darker Gold */
            --gym-accent: #E5C565;       /* Lighter Gold accent */
            --gym-dark: #1a1a1a;
            --gym-light: #2c3e50;
            --card-grey: #2d2d2d;        /* Grey for cards */
            --card-dark: #1f1f1f;        /* Darker grey */
            --text-grey: #e0e0e0;        /* Lighter grey text for better visibility */
            --text-white: #ffffff;       /* White text */
        }

        body {
            background-color: var(--gym-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(180deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 20px;
            overflow-y: auto;
            border-right: 2px solid var(--gym-primary);
        }

        .logo {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--gym-primary);
        }

        .logo i {
            font-size: 50px;
            color: var(--gym-primary);
        }

        .logo h4 {
            color: var(--gym-primary);
            margin-top: 10px;
            font-weight: bold;
        }

        .logo small {
            color: var(--text-grey);
        }

        .user-info {
            background: rgba(58, 58, 58, 0.5);
            border: 1px solid var(--gym-primary);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        .user-info h6 {
            color: #ffffff;
            margin: 0;
        }

        .user-info .badge {
            background: var(--gym-primary);
            color: var(--gym-dark);
            font-weight: bold;
            margin-top: 5px;
        }

        .nav-link {
            color: var(--text-grey);
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
            border-left: 3px solid transparent;
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
        }

        .nav-link:hover {
            background: rgba(58, 58, 58, 0.5);
            color: var(--gym-primary);
            border-left: 3px solid var(--gym-primary);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(212, 175, 55, 0.15), transparent);
            color: var(--gym-primary);
            border-left: 3px solid var(--gym-primary);
            font-weight: bold;
        }

        #logoutBtn {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }

        #logoutBtn:hover {
            background: #e74c3c;
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        .top-bar {
            background: #2d2d2d;
            border: 2px solid var(--gym-primary);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
        }

        .card {
            background: #2d2d2d;
            border: 2px solid var(--gym-primary);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--gym-primary), var(--gym-secondary));
            color: #1a1a1a;
            font-weight: bold;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            border-bottom: none;
        }

        .card-body {
            color: #ffffff;
        }

        .stats-card {
            background: linear-gradient(135deg, #2d2d2d 0%, #1f1f1f 100%);
            border: 2px solid var(--gym-primary);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
            background: linear-gradient(135deg, #353535 0%, #252525 100%);
        }

        .stats-card .icon {
            font-size: 48px;
            color: var(--gym-primary);
            margin-bottom: 15px;
        }

        .stats-card h3 {
            color: #ffffff;
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stats-card h3::before {
            content: '£';
            margin-right: 2px;
            color: #ffffff;
        }

        .stats-card p {
            color: #c0c0c0;
            margin: 0;
            font-size: 14px;
        }

        .quick-action-btn {
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-weight: bold;
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
            margin: 10px 0;
            display: block;
            width: 100%;
            background: #2d2d2d;
            color: #ffffff;
        }

        .quick-action-btn i {
            display: block;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .btn-checkin {
            background: linear-gradient(135deg, #2d2d2d 0%, #1f1f1f 100%);
            border: 2px solid var(--gym-primary);
            color: #ffffff;
        }

        .btn-checkin i {
            color: var(--gym-primary);
        }

        .btn-checkin:hover {
            background: linear-gradient(135deg, var(--gym-primary), var(--gym-secondary));
            border-color: var(--gym-primary);
            color: #1a1a1a;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(212, 175, 55, 0.4);
        }

        .btn-checkin:hover i {
            color: #1a1a1a;
        }

        .btn-register {
            background: linear-gradient(135deg, #2d2d2d 0%, #1f1f1f 100%);
            border: 2px solid #27ae60;
            color: #ffffff;
        }

        .btn-register i {
            color: #27ae60;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(39, 174, 96, 0.4);
        }

        .btn-register:hover i {
            color: white;
        }

        .btn-payment {
            background: linear-gradient(135deg, #2d2d2d 0%, #1f1f1f 100%);
            border: 2px solid #3498db;
            color: #ffffff;
        }

        .btn-payment i {
            color: #3498db;
        }

        .btn-payment:hover {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.4);
        }

        .btn-payment:hover i {
            color: white;
        }

        .btn-alerts {
            background: linear-gradient(135deg, #2d2d2d 0%, #1f1f1f 100%);
            border: 2px solid #f39c12;
            color: #ffffff;
        }

        .btn-alerts i {
            color: #f39c12;
        }

        .btn-alerts:hover {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: #1a1a1a;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(243, 156, 18, 0.4);
        }

        .btn-alerts:hover i {
            color: #1a1a1a;
        }

        .table {
            color: #ffffff;
            background: #1f1f1f;
        }

        .table thead {
            background: rgba(212, 175, 55, 0.1);
            border-bottom: 2px solid var(--gym-primary);
            color: var(--gym-primary);
        }

        .table tbody tr {
            border-bottom: 1px solid rgba(212, 175, 55, 0.15);
            color: #e0e0e0;
        }

        .table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
            color: #ffffff;
        }

        .badge-owner {
            background: var(--gym-primary);
            color: var(--gym-dark);
        }

        .badge-manager {
            background: #3498db;
        }

        .badge-trainer {
            background: #27ae60;
        }

        .badge-frontdesk {
            background: #9b59b6;
        }

        .btn-primary {
            background: #2d2d2d;
            border: 2px solid var(--gym-primary);
            color: #ffffff;
            font-weight: bold;
        }

        .btn-primary:hover {
            background: var(--gym-primary);
            border-color: var(--gym-primary);
            color: #1a1a1a;
        }

        .btn-success {
            background: #2d2d2d;
            border: 2px solid #27ae60;
            color: #ffffff;
            font-weight: bold;
        }

        .btn-success:hover {
            background: #27ae60;
            border-color: #27ae60;
            color: white;
        }

        .form-control, .form-select {
            background: #1f1f1f;
            border: 1px solid var(--gym-primary);
            color: #ffffff;
        }

        .form-control:focus, .form-select:focus {
            background: #2a2a2a;
            border-color: var(--gym-primary);
            box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.25);
            color: #ffffff;
        }

        .form-control::placeholder {
            color: #999999;
        }

        .form-label {
            color: #e0e0e0;
            font-weight: 500;
        }

        .modal-content {
            background: #2a2a2a;
            border: 2px solid var(--gym-primary);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--gym-primary), var(--gym-secondary));
            color: #1a1a1a;
            border-bottom: 2px solid var(--gym-secondary);
        }

        .modal-body {
            background: #1f1f1f;
        }

        .btn-close {
            filter: brightness(0) invert(1);
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.15);
            border: 1px solid #27ae60;
            color: #27ae60;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }

        .alert-warning {
            background: rgba(212, 175, 55, 0.15);
            border: 1px solid var(--gym-primary);
            color: var(--gym-primary);
        }

        .alert-info {
            background: rgba(52, 152, 219, 0.15);
            border: 1px solid #3498db;
            color: #3498db;
        }

        .input-group-text {
            background: #1f1f1f;
            border: 1px solid var(--gym-primary);
            color: var(--gym-primary);
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gym-dark);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gym-primary);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gym-secondary);
        }

        /* Accordion */
        .accordion-button {
            background: #2a2a2a;
            color: var(--text-grey);
            border: 1px solid var(--gym-primary);
        }

        .accordion-button:not(.collapsed) {
            background: var(--gym-primary);
            color: #1a1a1a;
            font-weight: bold;
        }

        .accordion-item {
            background: #2a2a2a;
            border: 1px solid var(--gym-primary);
            margin-bottom: 10px;
        }

        .accordion-body {
            background: #1f1f1f;
        }

        /* Currency symbol */
        .currency::before {
            content: '£';
        }

        body {
            background-color: var(--gym-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(180deg, var(--gym-secondary) 0%, var(--gym-dark) 100%);
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
            border-right: 2px solid var(--gym-primary);
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gym-primary);
        }

        .sidebar .logo i {
            font-size: 48px;
            color: var(--gym-primary);
        }

        .sidebar .logo h4 {
            color: #fff;
            margin-top: 10px;
            font-weight: bold;
        }

        .sidebar .user-info {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid var(--gym-primary);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar .user-info .user-name {
            color: #fff;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .sidebar .user-info .user-role {
            display: inline-block;
            background: var(--gym-primary);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
        }

        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .sidebar .nav-link:hover {
            background-color: var(--gym-primary);
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background-color: var(--gym-primary);
            color: #fff;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .sidebar .nav-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .top-navbar {
            background: var(--gym-light);
            padding: 15px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--gym-light) 0%, var(--gym-secondary) 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid var(--gym-primary);
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(231, 76, 60, 0.3);
        }

        .stats-card .icon {
            font-size: 48px;
            color: var(--gym-primary);
        }

        .stats-card h3 {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stats-card p {
            color: #bdc3c7;
            margin: 0;
        }

        .card {
            background: var(--gym-light);
            border: 1px solid var(--gym-primary);
            border-radius: 15px;
        }

        .card-header {
            background: var(--gym-secondary);
            border-bottom: 2px solid var(--gym-primary);
            font-weight: bold;
        }

        .table {
            color: #ecf0f1;
        }

        .table thead {
            background-color: var(--gym-secondary);
        }

        .badge-active {
            background-color: #27ae60;
        }

        .badge-expired {
            background-color: #e74c3c;
        }

        .badge-warning {
            background-color: #f39c12;
        }

        .quick-action-btn {
            width: 100%;
            padding: 20px;
            margin: 10px 0;
            font-size: 18px;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <i class="bi bi-activity"></i>
            <h4>TKO BOXING CLUB</h4>
            <small class="text-muted">Pro Edition</small>
        </div>

        <!-- User Info -->
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
            <span class="user-role"><?php echo htmlspecialchars($currentUser['role_name']); ?></span>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link active" href="#" data-page="dashboard">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link" href="#" data-page="members">
                <i class="bi bi-people"></i> Members
            </a>
            <a class="nav-link" href="#" data-page="attendance">
                <i class="bi bi-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="#" data-page="payments">
                <i class="bi bi-credit-card"></i> Payments
            </a>
            
            <?php if ($canManageStaff): ?>
            <a class="nav-link" href="#" data-page="staff">
                <i class="bi bi-person-badge"></i> Staff
            </a>
            <?php endif; ?>
            
            <?php if ($canManagePayroll): ?>
            <a class="nav-link" href="#" data-page="payroll">
                <i class="bi bi-cash-stack"></i> Payroll
            </a>
            <?php endif; ?>
            
            <?php if ($canViewReports): ?>
            <a class="nav-link" href="#" data-page="reports">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <?php endif; ?>
            
            <?php if ($hasFullAccess): ?>
            <a class="nav-link" href="#" data-page="settings">
                <i class="bi bi-gear"></i> Settings
            </a>
            <?php endif; ?>
        </nav>

        <div class="mt-4 pt-4" style="border-top: 1px solid var(--gym-primary);">
            <a class="nav-link" href="logout.php" id="logoutBtn">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <i class="bi bi-clock"></i> 
                        <span id="currentTime"></span>
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <span class="me-3">
                        <i class="bi bi-person-circle"></i> 
                        <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                    </span>
                    <span class="badge bg-danger"><?php echo htmlspecialchars($currentUser['role_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div id="dashboardContent">
            <!-- Stats Row -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 id="totalMembers">0</h3>
                        <p>Total Members</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h3 id="activeMembers">0</h3>
                        <p>Active Members</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="icon">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <h3 id="expiringMembers">0</h3>
                        <p>Expiring Soon</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="icon">
                            <i class="bi bi-calendar2-check-fill"></i>
                        </div>
                        <h3 id="todayAttendance">0</h3>
                        <p>Today's Attendance</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-lightning-fill"></i> Quick Actions
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <button class="quick-action-btn btn-checkin" data-bs-toggle="modal" data-bs-target="#checkInModal">
                                        <i class="bi bi-box-arrow-in-right"></i><br>
                                        Member Check-In
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="quick-action-btn btn-register" data-bs-toggle="modal" data-bs-target="#registerModal">
                                        <i class="bi bi-person-plus-fill"></i><br>
                                        Register Member
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="quick-action-btn btn-payment" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                        <i class="bi bi-credit-card-fill"></i><br>
                                        Process Payment
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="quick-action-btn btn-alerts" onclick="loadExpiringMembers()">
                                        <i class="bi bi-bell-fill"></i><br>
                                        View Alerts
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-clock-history"></i> Today's Check-ins
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Member</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="todayCheckIns">
                                        <tr>
                                            <td colspan="3" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-exclamation-circle"></i> Expiring Memberships
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Member</th>
                                            <th>Expires</th>
                                            <th>Days Left</th>
                                        </tr>
                                    </thead>
                                    <tbody id="expiringList">
                                        <tr>
                                            <td colspan="3" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Management Content (Only for Owner/Manager) -->
        <?php if ($canManageStaff): ?>
        <div id="staffContent" style="display:none;">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-person-badge"></i> Staff Management</span>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                <i class="bi bi-plus-lg"></i> Add Staff
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Salary</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="staffTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Members Management Content -->
        <div id="membersContent" style="display:none;">
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-people-fill"></i> Members Management</span>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#registerModal">
                                <i class="bi bi-person-plus-fill"></i> Add New Member
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Search and Filter -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" class="form-control" id="memberSearchInput" 
                                               placeholder="Search by name, code, phone, or email...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="memberStatusFilter">
                                        <option value="">All Status</option>
                                        <option value="Active">Active</option>
                                        <option value="Expired">Expired</option>
                                        <option value="Pending">Pending</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-primary w-100" onclick="searchMembers()">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                </div>
                            </div>

                            <!-- Members Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Membership</th>
                                            <th>Status</th>
                                            <th>Expiry</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="membersTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading members...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div id="membersPagination" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Management Content -->
        <div id="attendanceContent" style="display:none;">
            <div class="row mb-3">
                <!-- Quick Check-In Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-box-arrow-in-right"></i> Quick Check-In
                        </div>
                        <div class="card-body">
                            <form id="quickCheckInForm">
                                <div class="mb-3">
                                    <label class="form-label">Member Code or Scan QR</label>
                                    <input type="text" class="form-control form-control-lg" id="quickCheckInCode" 
                                           placeholder="MEM00001" autofocus>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-check-lg"></i> Check In
                                </button>
                            </form>
                            <div id="quickCheckInResult" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Today's Stats -->
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="icon"><i class="bi bi-calendar-check"></i></div>
                                <h3 id="todayCheckIns">0</h3>
                                <p>Today's Check-Ins</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="icon"><i class="bi bi-people"></i></div>
                                <h3 id="currentlyInGym">0</h3>
                                <p>Currently in Gym</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="icon"><i class="bi bi-clock-history"></i></div>
                                <h3 id="avgDuration">0 min</h3>
                                <p>Avg Duration</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Records -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-clock-history"></i> Today's Attendance
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Member</th>
                                            <th>Code</th>
                                            <th>Check-In Time</th>
                                            <th>Check-Out Time</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attendanceTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Management Content -->
        <div id="paymentsContent" style="display:none;">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="icon"><i class="bi bi-currency-dollar"></i></div>
                        <h3 id="todayRevenue">$0</h3>
                        <p>Today's Revenue</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="icon"><i class="bi bi-calendar-month"></i></div>
                        <h3 id="monthRevenue">$0</h3>
                        <p>This Month</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="icon"><i class="bi bi-credit-card"></i></div>
                        <h3 id="totalPayments">0</h3>
                        <p>Total Payments</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="icon"><i class="bi bi-hourglass-split"></i></div>
                        <h3 id="pendingPayments">0</h3>
                        <p>Pending</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-receipt"></i> Payment History</span>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="bi bi-plus-lg"></i> New Payment
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Receipt #</th>
                                            <th>Date</th>
                                            <th>Member</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentsTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payroll Management Content -->
        <?php if ($canManagePayroll): ?>
        <div id="payrollContent" style="display:none;">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="icon"><i class="bi bi-cash-stack"></i></div>
                        <h3 id="monthlyPayroll">$0</h3>
                        <p>Monthly Payroll</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="icon"><i class="bi bi-people"></i></div>
                        <h3 id="activeStaff">0</h3>
                        <p>Active Staff</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="icon"><i class="bi bi-percent"></i></div>
                        <h3 id="totalCommissions">$0</h3>
                        <p>Commissions</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-cash"></i> Staff Payroll
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Role</th>
                                            <th>Base Salary</th>
                                            <th>Commission Rate</th>
                                            <th>This Month Commission</th>
                                            <th>Total</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="payrollTableBody">
                                        <tr>
                                            <td colspan="7" class="text-center">Loading...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reports Content -->
        <?php if ($canViewReports): ?>
        <div id="reportsContent" style="display:none;">
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-graph-up"></i> Reports Dashboard
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <button class="btn btn-outline-primary w-100 mb-3" onclick="generateReport('members')">
                                        <i class="bi bi-people"></i><br>Members Report
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-success w-100 mb-3" onclick="generateReport('revenue')">
                                        <i class="bi bi-currency-dollar"></i><br>Revenue Report
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-info w-100 mb-3" onclick="generateReport('attendance')">
                                        <i class="bi bi-calendar-check"></i><br>Attendance Report
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-outline-warning w-100 mb-3" onclick="generateReport('expiring')">
                                        <i class="bi bi-exclamation-triangle"></i><br>Expiring Members
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-file-earmark-text"></i> Report Results
                        </div>
                        <div class="card-body" id="reportResults">
                            <div class="text-center text-muted">
                                <i class="bi bi-file-earmark-bar-graph" style="font-size: 64px;"></i>
                                <p class="mt-3">Select a report type above to generate</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Settings Content -->
        <?php if ($hasFullAccess): ?>
        <div id="settingsContent" style="display:none;">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-gear"></i> System Settings
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="settingsAccordion">
                                <!-- General Settings -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#generalSettings">
                                            <i class="bi bi-building"></i> &nbsp; Gym Information
                                        </button>
                                    </h2>
                                    <div id="generalSettings" class="accordion-collapse collapse show" data-bs-parent="#settingsAccordion">
                                        <div class="accordion-body">
                                            <form id="generalSettingsForm">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Gym Name</label>
                                                        <input type="text" class="form-control" name="gym_name" value="TKO Boxing Club">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Contact Phone</label>
                                                        <input type="text" class="form-control" name="gym_phone" value="+44 20 1234 5678">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" class="form-control" name="gym_email" value="info@tkoboxing.co.uk">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Website</label>
                                                        <input type="url" class="form-control" name="gym_website" value="https://tkoboxing.co.uk">
                                                    </div>
                                                    <div class="col-md-12 mb-3">
                                                        <label class="form-label">Address</label>
                                                        <textarea class="form-control" name="gym_address" rows="2">123 Boxing Street, London, UK</textarea>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save"></i> Save Changes
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Membership Types -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#membershipSettings">
                                            <i class="bi bi-card-list"></i> &nbsp; Membership Types
                                        </button>
                                    </h2>
                                    <div id="membershipSettings" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                                        <div class="accordion-body">
                                            <button class="btn btn-success btn-sm mb-3">
                                                <i class="bi bi-plus-lg"></i> Add Membership Type
                                            </button>
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Type Name</th>
                                                            <th>Duration</th>
                                                            <th>Price</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Monthly</td>
                                                            <td>1 month</td>
                                                            <td>$50.00</td>
                                                            <td><span class="badge bg-success">Active</span></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Quarterly</td>
                                                            <td>3 months</td>
                                                            <td>$135.00</td>
                                                            <td><span class="badge bg-success">Active</span></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Yearly</td>
                                                            <td>12 months</td>
                                                            <td>$480.00</td>
                                                            <td><span class="badge bg-success">Active</span></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- System Preferences -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#systemPreferences">
                                            <i class="bi bi-sliders"></i> &nbsp; System Preferences
                                        </button>
                                    </h2>
                                    <div id="systemPreferences" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                                        <div class="accordion-body">
                                            <form id="preferencesForm">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Currency</label>
                                                        <select class="form-select" name="currency">
                                                            <option value="GBP" selected>GBP - British Pound (£)</option>
                                                            <option value="USD">USD - US Dollar ($)</option>
                                                            <option value="EUR">EUR - Euro (€)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Timezone</label>
                                                        <select class="form-select" name="timezone">
                                                            <option value="Europe/London" selected>London (GMT/BST)</option>
                                                            <option value="UTC">UTC</option>
                                                            <option value="America/New_York">New York (EST)</option>
                                                            <option value="America/Los_Angeles">Los Angeles (PST)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Default Trainer Commission (%)</label>
                                                        <input type="number" class="form-control" name="commission_rate" value="20" step="0.1">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Expiry Alert Days</label>
                                                        <input type="number" class="form-control" name="expiry_alert_days" value="5">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save"></i> Save Preferences
                                                </button>
                                            </form>
                                                    <i class="bi bi-save"></i> Save Preferences
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Check-In Modal -->
    <div class="modal fade" id="checkInModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-in-right"></i> Member Check-In
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="checkInForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-3">
                            <label class="form-label">Member Code or ID</label>
                            <input type="text" class="form-control" id="checkInIdentifier" 
                                   placeholder="Enter member code or scan QR" autofocus required>
                            <small class="text-muted">Scan QR code or enter member code manually</small>
                        </div>
                        <button type="submit" class="btn w-100" style="background: #2d2d2d; border: 2px solid var(--gym-primary); color: #ffffff; font-weight: bold;">
                            <i class="bi bi-check-lg"></i> Check In
                        </button>
                    </form>
                    <div id="checkInResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Member Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i> Register New Member
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerMemberForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" name="emergency_contact">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Phone</label>
                                <input type="tel" class="form-control" name="emergency_phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select class="form-select" name="blood_group">
                                    <option value="">Select</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Medical Conditions</label>
                                <input type="text" class="form-control" name="medical_conditions" placeholder="Any health issues?">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-lg"></i> Register Member
                        </button>
                    </form>
                    <div id="registerResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Member Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i> Register New Member
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerMemberForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" name="emergency_contact">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Phone</label>
                                <input type="tel" class="form-control" name="emergency_phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select class="form-select" name="blood_group">
                                    <option value="">Select</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Medical Conditions</label>
                                <input type="text" class="form-control" name="medical_conditions" placeholder="Any health issues?">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-lg"></i> Register Member
                        </button>
                    </form>
                    <div id="registerResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Member Details Modal -->
    <div class="modal fade" id="viewMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-circle"></i> Member Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="memberDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Edit Member
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editMemberForm">
                        <input type="hidden" name="member_id" id="editMemberId">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="editLastName" name="last_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="editPhone" name="phone" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="editEmail" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" id="editGender" name="gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="editDOB" name="date_of_birth">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" id="editAddress" name="address" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" id="editEmergencyContact" name="emergency_contact">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Phone</label>
                                <input type="tel" class="form-control" id="editEmergencyPhone" name="emergency_phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select class="form-select" id="editBloodGroup" name="blood_group">
                                    <option value="">Select</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="editStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Suspended">Suspended</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Medical Conditions</label>
                                <textarea class="form-control" id="editMedicalConditions" name="medical_conditions" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Process Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-credit-card"></i> Process Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="processPaymentForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <!-- Member Selection -->
                        <div class="mb-3">
                            <label class="form-label">Search Member *</label>
                            <input type="text" class="form-control" id="memberSearch" 
                                   placeholder="Enter member name, code, or phone" autocomplete="off">
                            <input type="hidden" id="selectedMemberId" name="member_id">
                            <div id="memberSearchResults" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                        </div>

                        <div id="selectedMemberInfo" class="alert alert-info" style="display:none;">
                            <strong>Selected Member:</strong> <span id="selectedMemberName"></span><br>
                            <small>Code: <span id="selectedMemberCode"></span> | Phone: <span id="selectedMemberPhone"></span></small>
                        </div>

                        <!-- Payment Type Selection -->
                        <div class="mb-3">
                            <label class="form-label">Payment Type *</label>
                            <select class="form-select" id="paymentType" required>
                                <option value="">Select Payment Type</option>
                                <option value="membership">Membership Fee</option>
                                <option value="pt">Personal Training</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Membership Type (shown when membership selected) -->
                        <div id="membershipTypeSection" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Membership Type *</label>
                                <select class="form-select" name="type_id" id="membershipTypeSelect">
                                    <option value="">Select Membership</option>
                                    <?php
                                    $types = Database::query("SELECT * FROM membership_types WHERE is_active = 1");
                                    foreach ($types as $type) {
                                        echo "<option value='{$type['type_id']}' data-price='{$type['price']}' data-months='{$type['duration_months']}'>";
                                        echo htmlspecialchars($type['type_name']) . " - $" . number_format($type['price'], 2) . " ({$type['duration_months']} months)";
                                        echo "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Trainer Selection (shown when PT selected) -->
                        <div id="trainerSection" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Select Trainer *</label>
                                <select class="form-select" name="trainer_id" id="trainerSelect">
                                    <option value="">Select Trainer</option>
                                    <?php
                                    $trainers = Database::query(
                                        "SELECT staff_id, first_name, last_name, commission_rate 
                                         FROM staff 
                                         WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'Trainer') 
                                         AND status = 'Active'"
                                    );
                                    foreach ($trainers as $trainer) {
                                        echo "<option value='{$trainer['staff_id']}' data-commission='{$trainer['commission_rate']}'>";
                                        echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) . " (Commission: {$trainer['commission_rate']}%)";
                                        echo "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="mb-3">
                            <label class="form-label">Amount *</label>
                            <input type="number" class="form-control" name="amount" id="paymentAmount" 
                                   step="0.01" min="0" required>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="UPI">UPI</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>

                        <!-- Transaction ID (optional) -->
                        <div class="mb-3">
                            <label class="form-label">Transaction ID (Optional)</label>
                            <input type="text" class="form-control" name="transaction_id" 
                                   placeholder="For card/online payments">
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> Process Payment
                        </button>
                    </form>
                    <div id="paymentResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <?php if ($canManageStaff): ?>
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i> Add New Staff
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addStaffForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role *</label>
                                <select class="form-select" name="role_id" required>
                                    <option value="">Select Role</option>
                                    <?php
                                    $roles = Database::query("SELECT * FROM roles ORDER BY role_name");
                                    foreach ($roles as $role) {
                                        echo "<option value='{$role['role_id']}'>{$role['role_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" required minlength="8">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Base Salary</label>
                                <input type="number" class="form-control" name="base_salary" step="0.01" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Commission Rate (%)</label>
                                <input type="number" class="form-control" name="commission_rate" step="0.01" value="0">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Hire Date</label>
                                <input type="date" class="form-control" name="hire_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> Add Staff Member
                        </button>
                    </form>
                    <div id="addStaffResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <script>
        // CSRF Token
        const csrfToken = '<?php echo generate_csrf_token(); ?>';
        
        // User permissions
        const canManageStaff = <?php echo $canManageStaff ? 'true' : 'false'; ?>;
        
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Load dashboard stats
        function loadDashboardStats() {
            $.ajax({
                url: 'member_management.php',
                method: 'POST',
                data: { action: 'get_stats' },
                success: function(response) {
                    if (response.success) {
                        $('#totalMembers').text(response.data.total_members);
                        $('#activeMembers').text(response.data.active_members);
                        $('#expiringMembers').text(response.data.expiring_soon);
                    }
                }
            });
        }

        // Load today's attendance
        function loadTodayAttendance() {
            $.ajax({
                url: 'attendance.php',
                method: 'POST',
                data: { action: 'get_today_attendance' },
                success: function(response) {
                    if (response.success) {
                        const tbody = $('#todayCheckIns');
                        tbody.empty();
                        
                        $('#todayAttendance').text(response.data.length);
                        
                        if (response.data.length === 0) {
                            tbody.append('<tr><td colspan="3" class="text-center">No check-ins yet</td></tr>');
                        } else {
                            response.data.slice(0, 5).forEach(function(item) {
                                const time = new Date(item.check_in_time).toLocaleTimeString();
                                const status = item.check_out_time ? 
                                    '<span class="badge bg-secondary">Checked Out</span>' : 
                                    '<span class="badge bg-success">Active</span>';
                                
                                tbody.append(`
                                    <tr>
                                        <td>${item.member_name}</td>
                                        <td>${time}</td>
                                        <td>${status}</td>
                                    </tr>
                                `);
                            });
                        }
                    }
                }
            });
        }

        // Load expiring memberships
        function loadExpiringMembers() {
            $.ajax({
                url: 'member_management.php',
                method: 'POST',
                data: { action: 'get_expiring', days: 5 },
                success: function(response) {
                    if (response.success) {
                        const tbody = $('#expiringList');
                        tbody.empty();
                        
                        if (response.data.length === 0) {
                            tbody.append('<tr><td colspan="3" class="text-center">No expiring memberships</td></tr>');
                        } else {
                            response.data.forEach(function(item) {
                                const badgeClass = item.days_remaining <= 2 ? 'bg-danger' : 'bg-warning';
                                tbody.append(`
                                    <tr>
                                        <td>${item.member_name}</td>
                                        <td>${item.end_date}</td>
                                        <td><span class="badge ${badgeClass}">${item.days_remaining} days</span></td>
                                    </tr>
                                `);
                            });
                        }
                    }
                }
            });
        }

        // Load staff list (for owners/managers)
        function loadStaffList() {
            if (!canManageStaff) return;
            
            $.ajax({
                url: 'staff_management.php',
                method: 'POST',
                data: { action: 'get_all_staff' },
                success: function(response) {
                    if (response.success) {
                        const tbody = $('#staffTableBody');
                        tbody.empty();
                        
                        if (response.data.length === 0) {
                            tbody.append('<tr><td colspan="7" class="text-center">No staff members found</td></tr>');
                        } else {
                            response.data.forEach(function(staff) {
                                const statusBadge = staff.status === 'Active' ? 
                                    '<span class="badge bg-success">Active</span>' : 
                                    '<span class="badge bg-danger">Inactive</span>';
                                
                                tbody.append(`
                                    <tr>
                                        <td>${staff.staff_id}</td>
                                        <td>${staff.first_name} ${staff.last_name}</td>
                                        <td>${staff.email}</td>
                                        <td><span class="badge bg-info">${staff.role_name}</span></td>
                                        <td>£${parseFloat(staff.base_salary).toFixed(2)}</td>
                                        <td>${statusBadge}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editStaff(${staff.staff_id})">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteStaff(${staff.staff_id})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                    }
                }
            });
        }

        // Handle check-in form
        $('#checkInForm').on('submit', function(e) {
            e.preventDefault();
            const identifier = $('#checkInIdentifier').val();
            
            $.ajax({
                url: 'attendance.php',
                method: 'POST',
                data: { 
                    action: 'check_in',
                    identifier: identifier,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        $('#checkInResult').html(`
                            <div class="alert alert-success">
                                <h6>${response.message}</h6>
                                <p><strong>Member:</strong> ${response.data.member_name}</p>
                                <p><strong>Status:</strong> ${response.data.membership_status}</p>
                            </div>
                        `);
                        $('#checkInIdentifier').val('');
                        loadTodayAttendance();
                        loadDashboardStats();
                    } else {
                        $('#checkInResult').html(`
                            <div class="alert alert-danger">${response.message}</div>
                        `);
                    }
                }
            });
        });

        // Handle register member form
        $('#registerMemberForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize() + '&action=register';
            
            $.ajax({
                url: 'member_management.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#registerResult').html(`
                            <div class="alert alert-success">
                                <h6>${response.message}</h6>
                                <p><strong>Member Code:</strong> ${response.data.member_code}</p>
                                <p><strong>Member ID:</strong> ${response.data.member_id}</p>
                            </div>
                        `);
                        $('#registerMemberForm')[0].reset();
                        loadDashboardStats();
                        setTimeout(() => {
                            $('#registerModal').modal('hide');
                            $('#registerResult').html('');
                        }, 2000);
                    } else {
                        $('#registerResult').html(`
                            <div class="alert alert-danger">${response.message}</div>
                        `);
                    }
                }
            });
        });

        // Member search for payment
        $('#memberSearch').on('input', function() {
            const searchTerm = $(this).val();
            
            if (searchTerm.length < 2) {
                $('#memberSearchResults').empty();
                return;
            }
            
            $.ajax({
                url: 'member_management.php',
                method: 'POST',
                data: {
                    action: 'search_members',
                    search: searchTerm
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(member) {
                            html += `
                                <a href="#" class="list-group-item list-group-item-action member-select-item" 
                                   data-id="${member.member_id}" 
                                   data-name="${member.member_name}"
                                   data-code="${member.member_code}"
                                   data-phone="${member.phone}">
                                    <strong>${member.member_name}</strong><br>
                                    <small>Code: ${member.member_code} | Phone: ${member.phone}</small>
                                </a>
                            `;
                        });
                        $('#memberSearchResults').html(html);
                    } else {
                        $('#memberSearchResults').html('<div class="list-group-item">No members found</div>');
                    }
                }
            });
        });

        // Handle member selection
        $(document).on('click', '.member-select-item', function(e) {
            e.preventDefault();
            const memberId = $(this).data('id');
            const memberName = $(this).data('name');
            const memberCode = $(this).data('code');
            const memberPhone = $(this).data('phone');
            
            $('#selectedMemberId').val(memberId);
            $('#selectedMemberName').text(memberName);
            $('#selectedMemberCode').text(memberCode);
            $('#selectedMemberPhone').text(memberPhone);
            $('#selectedMemberInfo').show();
            $('#memberSearchResults').empty();
            $('#memberSearch').val(memberName);
        });

        // Payment type change
        $('#paymentType').on('change', function() {
            const type = $(this).val();
            
            $('#membershipTypeSection').hide();
            $('#trainerSection').hide();
            
            if (type === 'membership') {
                $('#membershipTypeSection').show();
            } else if (type === 'pt') {
                $('#trainerSection').show();
            }
        });

        // Auto-fill amount when membership type selected
        $('#membershipTypeSelect').on('change', function() {
            const price = $(this).find(':selected').data('price');
            if (price) {
                $('#paymentAmount').val(price);
            }
        });

        // Handle payment form
        $('#processPaymentForm').on('submit', function(e) {
            e.preventDefault();
            
            const paymentType = $('#paymentType').val();
            const memberId = $('#selectedMemberId').val();
            
            if (!memberId) {
                $('#paymentResult').html(`
                    <div class="alert alert-danger">Please select a member first</div>
                `);
                return;
            }
            
            let ajaxData = $(this).serialize();
            
            if (paymentType === 'membership') {
                ajaxData += '&action=process_membership';
            } else if (paymentType === 'pt') {
                ajaxData += '&action=process_pt_payment';
            } else {
                $('#paymentResult').html(`
                    <div class="alert alert-danger">Please select a valid payment type</div>
                `);
                return;
            }
            
            $.ajax({
                url: 'process_payment.php',
                method: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        let resultHtml = `
                            <div class="alert alert-success">
                                <h6>${response.message}</h6>
                                <p><strong>Receipt No:</strong> ${response.data.receipt_number}</p>
                                <p><strong>Amount:</strong> £${parseFloat(response.data.amount).toFixed(2)}</p>
                        `;
                        
                        if (response.data.membership_type) {
                            resultHtml += `
                                <p><strong>Membership:</strong> ${response.data.membership_type}</p>
                                <p><strong>Valid Until:</strong> ${response.data.end_date}</p>
                            `;
                        }
                        
                        if (response.data.commission) {
                            resultHtml += `
                                <p><strong>Trainer Commission:</strong> £${parseFloat(response.data.commission).toFixed(2)}</p>
                            `;
                        }
                        
                        resultHtml += `
                                <hr>
                                <a href="receipt_generator.php?payment_id=${response.data.payment_id}&action=generate_receipt" 
                                   target="_blank" class="btn btn-sm btn-primary">
                                    <i class="bi bi-printer"></i> Print Receipt
                                </a>
                            </div>
                        `;
                        
                        $('#paymentResult').html(resultHtml);
                        $('#processPaymentForm')[0].reset();
                        $('#selectedMemberInfo').hide();
                        loadDashboardStats();
                        
                    } else {
                        $('#paymentResult').html(`
                            <div class="alert alert-danger">${response.message}</div>
                        `);
                    }
                }
            });
        });

        // Handle add staff form
        $('#addStaffForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize() + '&auth_action=create_staff';
            
            $.ajax({
                url: 'auth.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#addStaffResult').html(`
                            <div class="alert alert-success">${response.message}</div>
                        `);
                        $('#addStaffForm')[0].reset();
                        loadStaffList();
                        setTimeout(() => {
                            $('#addStaffModal').modal('hide');
                        }, 1500);
                    } else {
                        $('#addStaffResult').html(`
                            <div class="alert alert-danger">${response.message}</div>
                        `);
                    }
                }
            });
        });

        // Handle logout - Direct logout without confirmation
        $('#logoutBtn').on('click', function(e) {
            e.preventDefault();
            
            // Show loading state
            $(this).html('<i class="bi bi-hourglass-split"></i> Logging out...');
            
            // Method 1: Try AJAX logout
            $.ajax({
                url: 'auth.php',
                method: 'POST',
                data: { auth_action: 'logout' },
                success: function(response) {
                    window.location.href = 'login.php';
                },
                error: function() {
                    // Method 2: Fallback to direct logout.php
                    window.location.href = 'logout.php';
                }
            });
            
            // Method 3: Timeout fallback (if AJAX takes too long)
            setTimeout(function() {
                window.location.href = 'logout.php';
            }, 2000);
        });

        // Page navigation
        $('[data-page]').on('click', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            
            // Update active state
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
            
            // Hide all content sections
            $('#dashboardContent, #staffContent, #membersContent, #attendanceContent, #paymentsContent, #payrollContent, #reportsContent, #settingsContent').hide();
            
            // Show selected section
            if (page === 'dashboard') {
                $('#dashboardContent').show();
                loadDashboardStats();
                loadTodayAttendance();
                loadExpiringMembers();
            } else if (page === 'members') {
                $('#membersContent').show();
                loadAllMembers();
            } else if (page === 'attendance') {
                $('#attendanceContent').show();
                loadAttendanceData();
            } else if (page === 'payments') {
                $('#paymentsContent').show();
                loadPaymentsData();
            } else if (page === 'staff' && canManageStaff) {
                $('#staffContent').show();
                loadStaffList();
            } else if (page === 'payroll') {
                $('#payrollContent').show();
                loadPayrollData();
            } else if (page === 'reports') {
                $('#reportsContent').show();
            } else if (page === 'settings') {
                $('#settingsContent').show();
                loadSettings();
            }
        });

        // Load attendance data
        function loadAttendanceData() {
            $.ajax({
                url: 'attendance.php',
                method: 'POST',
                data: { action: 'get_today_attendance' },
                success: function(response) {
                    if (response.success) {
                        const tbody = $('#attendanceTableBody');
                        tbody.empty();
                        
                        $('#todayCheckIns').text(response.data.length);
                        
                        let currentlyInGym = 0;
                        let totalDuration = 0;
                        let completedSessions = 0;
                        
                        if (response.data.length === 0) {
                            tbody.append('<tr><td colspan="7" class="text-center">No check-ins today</td></tr>');
                        } else {
                            response.data.forEach(function(record) {
                                const checkInTime = new Date(record.check_in_time).toLocaleTimeString();
                                const checkOutTime = record.check_out_time ? new Date(record.check_out_time).toLocaleTimeString() : '-';
                                const duration = record.duration || '-';
                                const status = record.check_out_time ? 'Checked Out' : 'In Gym';
                                const statusClass = record.check_out_time ? 'secondary' : 'success';
                                
                                if (!record.check_out_time) currentlyInGym++;
                                if (record.duration) {
                                    totalDuration += parseInt(record.duration);
                                    completedSessions++;
                                }
                                
                                tbody.append(`
                                    <tr>
                                        <td>${record.member_name}</td>
                                        <td>${record.member_code}</td>
                                        <td>${checkInTime}</td>
                                        <td>${checkOutTime}</td>
                                        <td>${duration} min</td>
                                        <td><span class="badge bg-${statusClass}">${status}</span></td>
                                        <td>
                                            ${!record.check_out_time ? `<button class="btn btn-sm btn-warning" onclick="checkOut(${record.attendance_id})"><i class="bi bi-box-arrow-right"></i> Check Out</button>` : ''}
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                        
                        $('#currentlyInGym').text(currentlyInGym);
                        $('#avgDuration').text(completedSessions > 0 ? Math.round(totalDuration / completedSessions) + ' min' : '0 min');
                    }
                }
            });
        }

        // Quick check-in form
        $('#quickCheckInForm').on('submit', function(e) {
            e.preventDefault();
            const code = $('#quickCheckInCode').val();
            
            $.ajax({
                url: 'attendance.php',
                method: 'POST',
                data: {
                    action: 'check_in',
                    identifier: code,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        $('#quickCheckInResult').html(`
                            <div class="alert alert-success">
                                <strong>${response.data.member_name}</strong> checked in successfully!
                            </div>
                        `);
                        $('#quickCheckInCode').val('').focus();
                        loadAttendanceData();
                    } else {
                        $('#quickCheckInResult').html(`
                            <div class="alert alert-danger">${response.message}</div>
                        `);
                    }
                }
            });
        });

        // Check out member
        function checkOut(attendanceId) {
            $.ajax({
                url: 'attendance.php',
                method: 'POST',
                data: {
                    action: 'check_out',
                    attendance_id: attendanceId,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        loadAttendanceData();
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        // Load payments data
        function loadPaymentsData() {
            $.ajax({
                url: 'process_payment.php',
                method: 'POST',
                data: { action: 'get_all_payments' },
                success: function(response) {
                    if (response.success) {
                        const tbody = $('#paymentsTableBody');
                        tbody.empty();
                        
                        // Calculate stats
                        let todayTotal = 0;
                        let monthTotal = 0;
                        const today = new Date().toISOString().split('T')[0];
                        const thisMonth = new Date().toISOString().slice(0, 7);
                        
                        response.data.forEach(function(payment) {
                            const paymentDate = payment.payment_date.split(' ')[0];
                            const amount = parseFloat(payment.amount);
                            
                            if (paymentDate === today) todayTotal += amount;
                            if (paymentDate.startsWith(thisMonth)) monthTotal += amount;
                            
                            tbody.append(`
                                <tr>
                                    <td><strong>${payment.receipt_number}</strong></td>
                                    <td>${payment.payment_date}</td>
                                    <td>${payment.member_name}</td>
                                    <td>${payment.payment_type}</td>
                                    <td>£${parseFloat(payment.amount).toFixed(2)}</td>
                                    <td>${payment.payment_method}</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewReceipt(${payment.payment_id})">
                                            <i class="bi bi-receipt"></i>
                                        </button>
                                    </td>
                                </tr>
                            `);
                        });
                        
                        $('#todayRevenue').text('£' + todayTotal.toFixed(2));
                        $('#monthRevenue').text('£' + monthTotal.toFixed(2));
                        $('#totalPayments').text(response.data.length);
                    }
                }
            });
        }

        // View receipt
        function viewReceipt(paymentId) {
            window.open('receipt_generator.php?payment_id=' + paymentId + '&action=generate_receipt', '_blank');
        }

        // Load payroll data
        function loadPayrollData() {
            $.ajax({
                url: 'staff_management.php',
                method: 'POST',
                data: { action: 'get_all_staff' },
                success: function(response) {
                    if (response.success) {
                        const tbody = $('#payrollTableBody');
                        tbody.empty();
                        
                        let totalPayroll = 0;
                        let activeCount = 0;
                        
                        response.data.forEach(function(staff) {
                            if (staff.status === 'Active') {
                                activeCount++;
                                const baseSalary = parseFloat(staff.base_salary || 0);
                                const commission = 0; // Would calculate from PT sessions
                                const total = baseSalary + commission;
                                totalPayroll += total;
                                
                                tbody.append(`
                                    <tr>
                                        <td>${staff.first_name} ${staff.last_name}</td>
                                        <td><span class="badge bg-info">${staff.role_name}</span></td>
                                        <td>£${baseSalary.toFixed(2)}</td>
                                        <td>${staff.commission_rate}%</td>
                                        <td>£${commission.toFixed(2)}</td>
                                        <td><strong>£${total.toFixed(2)}</strong></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">
                                                <i class="bi bi-cash"></i> Process
                                            </button>
                                        </td>
                                    </tr>
                                `);
                            }
                        });
                        
                        $('#monthlyPayroll').text('£' + totalPayroll.toFixed(2));
                        $('#activeStaff').text(activeCount);
                    }
                }
            });
        }

        // Generate reports
        function generateReport(type) {
            $('#reportResults').html('<div class="text-center"><div class="spinner-border"></div><p>Generating report...</p></div>');
            
            setTimeout(function() {
                let html = '<div class="alert alert-info">Report generation feature - Coming soon!</div>';
                html += '<p>Report type: <strong>' + type + '</strong></p>';
                $('#reportResults').html(html);
            }, 1000);
        }

        // Load settings when settings page is opened
        function loadSettings() {
            $.ajax({
                url: 'settings_management.php',
                method: 'POST',
                data: { action: 'get_settings' },
                success: function(response) {
                    if (response.success) {
                        const settings = response.data;
                        
                        // Populate gym information
                        $('[name="gym_name"]').val(settings.gym_name || 'TKO Boxing Club');
                        $('[name="gym_phone"]').val(settings.gym_phone || '+44 20 1234 5678');
                        $('[name="gym_email"]').val(settings.gym_email || 'info@tkoboxing.co.uk');
                        $('[name="gym_website"]').val(settings.gym_website || 'https://tkoboxing.co.uk');
                        $('[name="gym_address"]').val(settings.gym_address || '123 Boxing Street, London, UK');
                        
                        // Populate preferences
                        $('[name="currency"]').val(settings.currency || 'GBP');
                        $('[name="timezone"]').val(settings.timezone || 'Europe/London');
                        $('[name="commission_rate"]').val(settings.commission_rate || '20');
                        $('[name="expiry_alert_days"]').val(settings.expiry_alert_days || '5');
                    }
                }
            });
        }

        // Save gym information
        $('#generalSettingsForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize() + '&action=save_gym_info';
            
            $.ajax({
                url: 'settings_management.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        alert('✅ ' + response.message);
                    } else {
                        alert('❌ ' + response.message);
                    }
                },
                error: function() {
                    alert('❌ Error saving settings. Please try again.');
                }
            });
        });

        // Save system preferences
        $('#preferencesForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize() + '&action=save_preferences';
            
            $.ajax({
                url: 'settings_management.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        alert('✅ ' + response.message);
                    } else {
                        alert('❌ ' + response.message);
                    }
                },
                error: function() {
                    alert('❌ Error saving preferences. Please try again.');
                }
            });
        });

        // Load all members
        function loadAllMembers() {
            const searchTerm = $('#memberSearchInput').val();
            const status = $('#memberStatusFilter').val();
            
            console.log('Loading members with search:', searchTerm, 'status:', status);
            
            $.ajax({
                url: 'member_management.php',
                method: 'POST',
                data: {
                    action: 'search_members',
                    search: searchTerm || '',
                    status: status || ''
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Members response:', response);
                    const tbody = $('#membersTableBody');
                    tbody.empty();
                    
                    if (response.success && response.data && response.data.length > 0) {
                        console.log('Found', response.data.length, 'members');
                        
                        response.data.forEach(function(member) {
                            const statusClass = member.status === 'Active' ? 'success' : 
                                              member.status === 'Expired' ? 'danger' : 
                                              member.status === 'Pending' ? 'warning' : 'secondary';
                            
                            // Simple member display with basic info
                            const firstName = member.first_name || '';
                            const lastName = member.last_name || '';
                            const fullName = firstName + ' ' + lastName;
                            const email = member.email || 'N/A';
                            const phone = member.phone || 'N/A';
                            const regDate = member.registration_date || member.created_at || 'N/A';
                            
                            tbody.append(`
                                <tr>
                                    <td><strong>${member.member_code}</strong></td>
                                    <td>${fullName}</td>
                                    <td>${phone}</td>
                                    <td>${email}</td>
                                    <td>-</td>
                                    <td><span class="badge bg-${statusClass}">${member.status}</span></td>
                                    <td>${regDate}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewMember(${member.member_id})" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="editMember(${member.member_id})" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteMember(${member.member_id}, '${fullName}')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        console.log('No members found. Response:', response);
                        tbody.append(`
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 64px; color: #7f8c8d;"></i>
                                    <h5 class="mt-3">No members found</h5>
                                    <p class="text-muted">${response.message || 'Get started by adding your first member'}</p>
                                    <button class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#registerModal">
                                        <i class="bi bi-person-plus-fill"></i> Add First Member
                                    </button>
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    $('#membersTableBody').html(`
                        <tr>
                            <td colspan="8" class="text-center text-danger py-4">
                                <i class="bi bi-exclamation-triangle" style="font-size: 48px;"></i>
                                <h5 class="mt-3">Error loading members</h5>
                                <p class="small">${error}</p>
                                <p class="small text-muted">Check browser console for details</p>
                            </td>
                        </tr>
                    `);
                }
            });
        }

        // Search members function
        function searchMembers() {
            loadAllMembers();
        }

        // View member details
        function viewMember(memberId) {
            $('#memberDetailsContent').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `);
            
            $('#viewMemberModal').modal('show');
            
            $.ajax({
                url: 'member_management.php',
                method: 'POST',
                data: {
                    action: 'get_member',
                    identifier: memberId
                },
                success: function(response) {
                    if (response.success) {
                        const m = response.data;
                        let html = `
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <h4>${m.first_name} ${m.last_name}</h4>
                                    <p class="text-muted">Member Code: <strong>${m.member_code}</strong></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Phone:</strong> ${m.phone}</p>
                                    <p><strong>Email:</strong> ${m.email || 'N/A'}</p>
                                    <p><strong>Gender:</strong> ${m.gender || 'N/A'}</p>
                                    <p><strong>Date of Birth:</strong> ${m.date_of_birth || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> <span class="badge bg-${m.status === 'Active' ? 'success' : 'danger'}">${m.status}</span></p>
                                    <p><strong>Join Date:</strong> ${m.join_date}</p>
                                    <p><strong>Blood Group:</strong> ${m.blood_group || 'N/A'}</p>
                                </div>
                                <div class="col-md-12">
                                    <p><strong>Address:</strong> ${m.address || 'N/A'}</p>
                                    <p><strong>Emergency Contact:</strong> ${m.emergency_contact || 'N/A'} (${m.emergency_phone || 'N/A'})</p>
                                    <p><strong>Medical Conditions:</strong> ${m.medical_conditions || 'None'}</p>
                                </div>
                            </div>
                        `;
                        $('#memberDetailsContent').html(html);
                    }
                }
            });
        }

        // Edit member function
        function editMember(memberId) {
            // Load member data
            $.ajax({
                url: 'member_management.php',
                method: 'POST',
                data: {
                    action: 'get_member',
                    identifier: memberId
                },
                success: function(response) {
                    if (response.success) {
                        const m = response.data;
                        
                        // Populate edit form
                        $('#editMemberId').val(m.member_id);
                        $('#editFirstName').val(m.first_name);
                        $('#editLastName').val(m.last_name);
                        $('#editPhone').val(m.phone);
                        $('#editEmail').val(m.email);
                        $('#editGender').val(m.gender);
                        $('#editDOB').val(m.date_of_birth);
                        $('#editAddress').val(m.address);
                        $('#editEmergencyContact').val(m.emergency_contact);
                        $('#editEmergencyPhone').val(m.emergency_phone);
                        $('#editBloodGroup').val(m.blood_group);
                        $('#editMedicalConditions').val(m.medical_conditions);
                        $('#editStatus').val(m.status);
                        
                        // Show modal
                        $('#editMemberModal').modal('show');
                    } else {
                        alert('Error loading member data: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading member data');
                }
            });
        }

        // Save edited member
        $('#editMemberForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize() + '&action=update_member';
            
            $.ajax({
                url: 'member_management.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#editMemberModal').modal('hide');
                        alert('✅ Member updated successfully!');
                        loadAllMembers(); // Reload the list
                    } else {
                        alert('❌ Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('❌ Error updating member');
                }
            });
        });

        // Delete member function
        function deleteMember(memberId, memberName) {
            if (confirm(`Are you sure you want to delete ${memberName}?\n\nThis action cannot be undone!`)) {
                $.ajax({
                    url: 'member_management.php',
                    method: 'POST',
                    data: {
                        action: 'delete_member',
                        member_id: memberId,
                        csrf_token: csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Member deleted successfully');
                            loadAllMembers(); // Reload the list
                        } else {
                            alert('❌ Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('❌ Error deleting member');
                    }
                });
            }
        }

        // Renew membership function
        function renewMembership(memberId) {
            // Open payment modal with this member pre-selected
            $('#paymentModal').modal('show');
            $('#selectedMemberId').val(memberId);
            $('#paymentType').val('membership').change();
        }

        // Trigger search on input
        $('#memberSearchInput').on('keyup', function(e) {
            if (e.key === 'Enter') {
                searchMembers();
            }
        });

        // Load data on page load
        $(document).ready(function() {
            loadDashboardStats();
            loadTodayAttendance();
            loadExpiringMembers();
            
            // Refresh every 30 seconds
            setInterval(function() {
                loadTodayAttendance();
                loadDashboardStats();
            }, 30000);
        });

        // Delete staff function
        function deleteStaff(staffId) {
            if (confirm('Are you sure you want to delete this staff member?')) {
                $.ajax({
                    url: 'staff_management.php',
                    method: 'POST',
                    data: {
                        action: 'delete_staff',
                        staff_id: staffId,
                        csrf_token: csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            loadStaffList();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>