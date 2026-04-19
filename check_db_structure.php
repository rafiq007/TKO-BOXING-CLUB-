<?php
/**
 * Database Structure Checker
 * Shows the actual column names in the members table
 */

require_once 'db_connect.php';

try {
    $pdo = Database::getConnection();
    
    echo "<h2>Members Table Structure</h2>";
    
    // Get column information
    $stmt = $pdo->query("DESCRIBE members");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Sample Member Data:</h3>";
    
    $stmt = $pdo->query("SELECT * FROM members LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "<pre>";
        print_r($sample);
        echo "</pre>";
    } else {
        echo "<p>No members in database yet.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test_members.php'>← Back to Members Test</a> | <a href='index.php'>Dashboard</a></p>";
?>