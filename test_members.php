<?php
/**
 * Test Members Loading
 * Diagnostic page to check if members can be retrieved
 */

require_once 'db_connect.php';

try {
    $pdo = Database::getConnection();
    
    echo "<h2>Testing Members Database</h2>";
    
    // Check if members table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'members'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Members table exists</p>";
        
        // Count total members
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM members");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total members in database: <strong>{$count['count']}</strong></p>";
        
        // Get all members
        $stmt = $pdo->query("
            SELECT m.*, mt.type_name as membership_type
            FROM members m
            LEFT JOIN membership_types mt ON m.membership_type_id = mt.type_id
            ORDER BY m.created_at DESC
            LIMIT 10
        ");
        
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($members) > 0) {
            echo "<h3>Members List:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Membership</th>
                    <th>Created</th>
                  </tr>";
            
            foreach ($members as $member) {
                echo "<tr>";
                echo "<td>{$member['member_id']}</td>";
                echo "<td>{$member['member_code']}</td>";
                echo "<td>{$member['first_name']} {$member['last_name']}</td>";
                echo "<td>{$member['phone']}</td>";
                echo "<td>{$member['email']}</td>";
                echo "<td>{$member['status']}</td>";
                echo "<td>" . ($member['membership_type'] ?? 'None') . "</td>";
                echo "<td>{$member['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Test AJAX response format
            echo "<h3>AJAX Response Format Test:</h3>";
            echo "<pre>";
            echo json_encode([
                'success' => true,
                'data' => $members
            ], JSON_PRETTY_PRINT);
            echo "</pre>";
            
        } else {
            echo "<p>❌ No members found in database</p>";
            echo "<p>Try registering a member first!</p>";
        }
        
    } else {
        echo "<p>❌ Members table does not exist!</p>";
        echo "<p>Run the database installation script first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Dashboard</a></p>";
?>