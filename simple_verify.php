<!DOCTYPE html>
<html>
<head>
    <title>Simple PIN Verification</title>
</head>
<body>
    <h2>Simple PIN Verification Test</h2>
    <p>Use this if the fancy form isn't working</p>
    
    <?php 
    session_start();
    if (!isset($_SESSION['verification_email'])) {
        echo "<p style='color: red;'>No verification email in session. Please register first.</p>";
        echo "<a href='/auth/register'>Go to Register</a>";
    } else {
        echo "<p>Verifying for email: <strong>" . $_SESSION['verification_email'] . "</strong></p>";
    }
    
    if (isset($_SESSION['error'])) {
        echo "<p style='color: red;'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['success'])) {
        echo "<p style='color: green;'>" . $_SESSION['success'] . "</p>";
        unset($_SESSION['success']);
    }
    ?>
    
    <form method="POST" action="/auth/verify">
        <p>Enter PIN: <input type="text" name="pin" maxlength="6" required></p>
        <p><button type="submit">Verify PIN</button></p>
    </form>
    
    <p><a href="/auth/resend_verification">Resend PIN</a></p>
</body>
</html>