<?php
// Debug verification - check database state
require_once 'app/config/DatabaseConfig.php';

try {
    $dbConfig = DatabaseConfig::getInstance();
    $pdo = $dbConfig->getConnection();
    
    echo "<h2>Database Connection Test</h2>";
    echo "Connected successfully to database<br><br>";
    
    // Check if students table exists and has data
    echo "<h3>Students table structure:</h3>";
    $stmt = $pdo->query("DESCRIBE students");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
    
    echo "<br><h3>All students with verification data:</h3>";
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, verification_pin, pin_expires, email_verified FROM students ORDER BY id DESC LIMIT 10");
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>PIN</th><th>Expires</th><th>Verified</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['verification_pin'] . "</td>";
        echo "<td>" . $row['pin_expires'] . "</td>";
        echo "<td>" . ($row['email_verified'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>Current time:</h3>";
    echo date('Y-m-d H:i:s');
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>