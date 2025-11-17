<?php
session_start();

// Test verification manually
require_once 'app/config/DatabaseConfig.php';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $pin = $_POST['pin'] ?? '';
    
    echo "<h2>Manual Verification Test</h2>";
    echo "Testing email: $email<br>";
    echo "Testing PIN: $pin<br><br>";
    
    try {
        $dbConfig = DatabaseConfig::getInstance();
        $pdo = $dbConfig->getConnection();
        
        // Get student data
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            echo "<strong>Student found:</strong><br>";
            echo "ID: " . $student['id'] . "<br>";
            echo "Name: " . $student['first_name'] . " " . $student['last_name'] . "<br>";
            echo "Email: " . $student['email'] . "<br>";
            echo "Stored PIN: '" . $student['verification_pin'] . "'<br>";
            echo "PIN Expires: " . $student['pin_expires'] . "<br>";
            echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";
            echo "Email Verified: " . ($student['email_verified'] ? 'Yes' : 'No') . "<br><br>";
            
            // Check PIN match
            if ($student['verification_pin'] === $pin) {
                echo "<span style='color: green;'>✅ PIN MATCHES!</span><br>";
            } else {
                echo "<span style='color: red;'>❌ PIN DOES NOT MATCH</span><br>";
                echo "Expected: '" . $student['verification_pin'] . "'<br>";
                echo "Received: '$pin'<br>";
                echo "Lengths - Expected: " . strlen($student['verification_pin']) . ", Received: " . strlen($pin) . "<br>";
            }
            
            // Check expiration
            $now = time();
            $expires = strtotime($student['pin_expires']);
            if ($expires > $now) {
                echo "<span style='color: green;'>✅ PIN NOT EXPIRED</span><br>";
                echo "Expires in: " . round(($expires - $now) / 60) . " minutes<br>";
            } else {
                echo "<span style='color: red;'>❌ PIN IS EXPIRED</span><br>";
                echo "Expired: " . round(($now - $expires) / 60) . " minutes ago<br>";
            }
            
            // Check verification status
            if ($student['email_verified'] == 0) {
                echo "<span style='color: green;'>✅ EMAIL NOT YET VERIFIED (ready for verification)</span><br>";
            } else {
                echo "<span style='color: orange;'>⚠️ EMAIL ALREADY VERIFIED</span><br>";
            }
            
        } else {
            echo "<span style='color: red;'>❌ NO STUDENT FOUND with email: $email</span><br>";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    
} else {
    echo '<h2>Manual Verification Test</h2>';
    echo '<form method="POST">';
    echo 'Email: <input type="email" name="email" required><br><br>';
    echo 'PIN: <input type="text" name="pin" required><br><br>';
    echo '<input type="submit" value="Test Verification">';
    echo '</form>';
}
?>