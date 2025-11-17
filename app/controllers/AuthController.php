<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

// PHPMailer imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class AuthController extends Controller {

    // Admin & Student login
    public function login() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Check if already logged in and redirect appropriately
        if (isset($_SESSION['admin'])) {
            header('Location: ' . site_url('admin/landing'));
            exit;
        }
        
        if (isset($_SESSION['user'])) {
            header('Location: ' . site_url('user_landing'));
            exit;
        }

        $data = ['error' => '', 'success' => ''];
        
        // Check for success message from registration
        if (isset($_SESSION['success'])) {
            $data['success'] = $_SESSION['success'];
            unset($_SESSION['success']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $data['error'] = "Email and password are required.";
            } else {
                // Admin credentials - check admin first
                $adminEmail = 'dorm@gmail.com';
                $adminPass = 'dorm';

                if ($email === $adminEmail && $password === $adminPass) {
                    $_SESSION['admin'] = $email;
                    $_SESSION['admin_name'] = 'Administrator';
                    // Clear any existing user sessions
                    unset($_SESSION['user']);
                    unset($_SESSION['user_name']);
                    header('Location: ' . site_url('admin/landing'));
                    exit;
                } else {
                    // Student login from `students` table
                    try {
                        $dbConfig = DatabaseConfig::getInstance();
                        $pdo = $dbConfig->getConnection();

                        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
                        $stmt->execute([$email]);
                        $student = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($student && password_verify($password, $student['password'])) {
                            // Check if email is verified
                            if ($student['email_verified'] == 0) {
                                $data['error'] = "Please verify your email before logging in. Check your inbox for verification PIN.";
                            } else {
                                $_SESSION['user'] = $student['id'];
                                $_SESSION['user_name'] = $student['fname'] . ' ' . $student['lname'];
                                // Clear any existing admin sessions
                                unset($_SESSION['admin']);
                                unset($_SESSION['admin_name']);
                                header('Location: ' . site_url('user_landing'));
                                exit;
                            }
                        } else {
                            $data['error'] = "Invalid email or password.";
                        }

                    } catch (PDOException $e) {
                        $data['error'] = "Database error: " . $e->getMessage();
                    }
                }
            }
        }

        $this->call->view('login', $data);
    }

    // Student registration
    public function register() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = ['error' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fname = trim($_POST['fname'] ?? '');
            $lname = trim($_POST['lname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validation
            if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
                $data['error'] = "All fields are required.";
            } elseif (strlen($password) < 6) {
                $data['error'] = "Password must be at least 6 characters long.";
            } elseif ($password !== $confirm_password) {
                $data['error'] = "Passwords do not match.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $data['error'] = "Invalid email format.";
            } else {
                // Connect to DB
                $host = 'localhost';
                $dbname = 'mockdata';
                $username = 'jeany';
                $dbpass = 'jeany';
                $charset = 'utf8mb4';

                $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

                try {
                    $pdo = new PDO($dsn, $username, $dbpass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);

                    // Check if email exists
                    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->rowCount() > 0) {
                        $data['error'] = "Email already registered.";
                    } else {
                        // Hash password
                        $hashed = password_hash($password, PASSWORD_DEFAULT);

                        // Insert student without email verification (will use captcha instead)
                        $insert = $pdo->prepare("INSERT INTO students (fname, lname, email, password, email_verified) VALUES (?, ?, ?, ?, 0)");
                        $insert->execute([$fname, $lname, $email, $hashed]);

                        $_SESSION['success'] = "Registration successful! Please complete the captcha to verify your account.";
                        $_SESSION['verification_email'] = $email;
                        header("Location: " . site_url('auth/verify'));
                        exit;
                    }

                } catch (PDOException $e) {
                    $data['error'] = "Database error: " . $e->getMessage();
                }
            }
        }

        $this->call->view('register', $data);
    }

    // Email verification page
    public function verify() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        error_log("DEBUG: Verify method called");
        
        if (!isset($_SESSION['verification_email'])) {
            error_log("DEBUG: No verification_email in session, redirecting to register");
            header('Location: ' . site_url('auth/register'));
            exit;
        }
        
        error_log("DEBUG: Verification email in session: " . $_SESSION['verification_email']);

        // Generate captcha if not exists
        if (!isset($_SESSION['captcha'])) {
            $_SESSION['captcha'] = $this->generateCaptcha();
        }
        
        $data = [
            'error' => '', 
            'success' => '', 
            'email' => $_SESSION['verification_email'],
            'captcha_question' => $_SESSION['captcha']['question']
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $captcha_answer = trim($_POST['captcha'] ?? '');
            
            if (empty($captcha_answer)) {
                $data['error'] = "Please solve the captcha.";
                $_SESSION['captcha'] = $this->generateCaptcha(); // Generate new captcha
                $data['captcha_question'] = $_SESSION['captcha']['question'];
            } elseif ($captcha_answer != $_SESSION['captcha']['answer']) {
                $data['error'] = "Incorrect captcha answer. Please try again.";
                $_SESSION['captcha'] = $this->generateCaptcha(); // Generate new captcha
                $data['captcha_question'] = $_SESSION['captcha']['question'];
            } else {
                try {
                    $dbConfig = DatabaseConfig::getInstance();
                    $pdo = $dbConfig->getConnection();

                    // Verify and activate account
                    $stmt = $pdo->prepare("UPDATE students SET email_verified = 1 WHERE email = ? AND email_verified = 0");
                    if ($stmt->execute([$_SESSION['verification_email']]) && $stmt->rowCount() > 0) {
                        unset($_SESSION['verification_email']);
                        unset($_SESSION['captcha']);
                        $_SESSION['success'] = "Account verified successfully! You can now log in.";
                        header("Location: " . site_url('auth/login'));
                        exit;
                    } else {
                        $data['error'] = "Account not found or already verified.";
                        $_SESSION['captcha'] = $this->generateCaptcha();
                        $data['captcha_question'] = $_SESSION['captcha']['question'];
                    }

                } catch (PDOException $e) {
                    $data['error'] = "Database error: " . $e->getMessage();
                }
            }
        }

        $this->call->view('verify', $data);
    }
    
    // Generate simple math captcha
    private function generateCaptcha() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operations = ['+', '-', '*'];
        $operation = $operations[array_rand($operations)];
        
        switch ($operation) {
            case '+':
                $answer = $num1 + $num2;
                $question = "$num1 + $num2 = ?";
                break;
            case '-':
                // Ensure positive result
                if ($num1 < $num2) {
                    $temp = $num1;
                    $num1 = $num2;
                    $num2 = $temp;
                }
                $answer = $num1 - $num2;
                $question = "$num1 - $num2 = ?";
                break;
            case '*':
                // Use smaller numbers for multiplication
                $num1 = rand(2, 5);
                $num2 = rand(2, 5);
                $answer = $num1 * $num2;
                $question = "$num1 × $num2 = ?";
                break;
        }
        
        return ['question' => $question, 'answer' => $answer];
    }

    // Resend verification email
    public function resend_verification() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['verification_email'])) {
            header('Location: ' . site_url('auth/register'));
            exit;
        }

        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();

            // Get student details
            $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND email_verified = 0");
            $stmt->execute([$_SESSION['verification_email']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                // Generate new verification PIN
                $verification_pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $pin_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Update PIN
                $updateStmt = $pdo->prepare("UPDATE students SET verification_pin = ?, pin_expires = ? WHERE id = ?");
                $updateStmt->execute([$verification_pin, $pin_expires, $student['id']]);

                // Send verification email
                if ($this->sendVerificationEmail($student['email'], $student['fname'] . ' ' . $student['lname'], $verification_pin)) {
                    $_SESSION['success'] = "New verification PIN sent to your email.";
                } else {
                    $_SESSION['error'] = "Failed to send verification email. Please try again later.";
                }
            } else {
                $_SESSION['error'] = "Student not found or already verified.";
            }

        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }

        header("Location: " . site_url('auth/verify'));
        exit;
    }

    // Send verification email function
    private function sendVerificationEmail($email, $name, $pin) {
        $mail = new PHPMailer(true);

        try {
            // SMTP SETTINGS
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'jeanyespares404@gmail.com';
            $mail->Password   = 'zjyi atup vuvr lrkl'; // APP PASSWORD
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Recipients
            $mail->setFrom('jeanyespares404@gmail.com', 'Dormitory Management System');
            $mail->addAddress($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification - Dormitory Management System';
            
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #FFF5E1; border: 2px solid #D2B48C; border-radius: 10px; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #5C4033; margin-bottom: 10px;'>🏠 Dormitory Management System</h1>
                    <h2 style='color: #C19A6B; font-size: 24px; margin-bottom: 20px;'>Email Verification Required</h2>
                </div>
                
                <div style='background-color: white; padding: 25px; border-radius: 8px; border: 1px solid #E5D3B3; margin-bottom: 20px;'>
                    <p style='color: #5C4033; font-size: 16px; margin-bottom: 15px;'>Hello <strong>{$name}</strong>,</p>
                    <p style='color: #5C4033; line-height: 1.6; margin-bottom: 20px;'>
                        Welcome to our Dormitory Management System! To complete your registration and secure your account, 
                        please verify your email address using the PIN below:
                    </p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <div style='background-color: #5C4033; color: white; padding: 20px; border-radius: 10px; display: inline-block; min-width: 200px;'>
                            <p style='margin: 0; font-size: 14px; color: #C19A6B; margin-bottom: 5px;'>VERIFICATION PIN</p>
                            <p style='margin: 0; font-size: 36px; font-weight: bold; letter-spacing: 8px; font-family: monospace;'>{$pin}</p>
                        </div>
                    </div>
                    
                    <div style='background-color: #FFF5E1; padding: 15px; border-radius: 6px; border-left: 4px solid #C19A6B; margin: 20px 0;'>
                        <p style='margin: 0; color: #5C4033; font-size: 14px;'>
                            <strong>⏰ Important:</strong> This PIN is valid for <strong>1 hour only</strong>. 
                            If it expires, you can request a new verification PIN.
                        </p>
                    </div>
                    
                    <p style='color: #5C4033; line-height: 1.6; margin-bottom: 15px;'>
                        Enter this PIN on the verification page to activate your account and start using our dormitory services.
                    </p>
                    
                    <p style='color: #5C4033; line-height: 1.6;'>
                        If you didn't register for this account, please ignore this email or contact our support team.
                    </p>
                </div>
                
                <div style='background-color: #E5D3B3; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #5C4033; margin-top: 0; margin-bottom: 15px;'>🏠 What's Next?</h3>
                    <ul style='color: #5C4033; line-height: 1.8; margin: 0; padding-left: 20px;'>
                        <li>Verify your email with the PIN above</li>
                        <li>Log in to your tenant dashboard</li>
                        <li>Browse available rooms</li>
                        <li>Submit reservation requests</li>
                        <li>Track payments and maintenance requests</li>
                    </ul>
                </div>
                
                <div style='text-align: center; color: #5C4033; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #D2B48C;'>
                    <p style='margin: 0;'>
                        📧 <strong>Contact Us:</strong> jeanyespares404@gmail.com | 
                        📞 <strong>Phone:</strong> 09517394938
                    </p>
                    <p style='margin: 10px 0 0 0; color: #C19A6B;'>
                        © 2025 Dormitory Management System. All rights reserved.
                    </p>
                </div>
            </div>";

            $mail->AltBody = "Hello {$name},\n\nWelcome to Dormitory Management System!\n\nYour verification PIN is: {$pin}\n\nThis PIN is valid for 1 hour. Please enter it on the verification page to complete your registration.\n\nIf you didn't register for this account, please ignore this email.\n\nContact: jeanyespares404@gmail.com | 09517394938";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email verification failed: " . $mail->ErrorInfo);
            return false;
        }
    }

    public function logout() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        
        // Clear all session variables
        $_SESSION = array();
        
        // If it's desired to kill the session, also delete the session cookie.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finally, destroy the session
        session_destroy();
        
        // Redirect to public landing page
        header('Location: ' . site_url('/')); 
        exit;
    }

    // Helper function to check if user is admin
    public static function isAdmin() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['admin']) && !empty($_SESSION['admin']);
    }

    // Helper function to check if user is logged in
    public static function isUser() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    // Helper function to require admin access
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }
    }

    // Helper function to require user access
    public static function requireUser() {
        if (!self::isUser()) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }
    }

}
