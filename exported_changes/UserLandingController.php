<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class UserLandingController extends Controller {

    private function getDbConnection() {
        $dbConfig = DatabaseConfig::getInstance();
        return $dbConfig->getConnection();
    }

    public function index() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        $data = ['rooms' => [], 'success' => '', 'error' => '', 'userName' => $_SESSION['user_name'] ?? 'User'];

        // Get success/error messages
        if (isset($_SESSION['success'])) {
            $data['success'] = $_SESSION['success'];
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        try {
            $pdo = $this->getDbConnection();
            $stmt = $pdo->query("SELECT * FROM rooms WHERE available > 0 ORDER BY room_number ASC");
            $data['rooms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get user's pending reservations count
            $pendingStmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE user_id = ? AND status = 'pending'");
            $pendingStmt->execute([$_SESSION['user']]);
            $data['pendingCount'] = $pendingStmt->fetch(PDO::FETCH_ASSOC)['count'];

        } catch(PDOException $e) {
            $data['error'] = "Database error: " . $e->getMessage();
        }

        $this->call->view('user_landing', $data);
    }

    public function reserveRoom($id) {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        // Debug: Check if we're receiving the POST request
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Invalid request method.";
            header('Location: ' . site_url('user_landing'));
            exit;
        }

        try {
            $pdo = $this->getDbConnection();

            // Get the room
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$room) {
                $_SESSION['error'] = "Room not found.";
            } elseif($room['available'] <= 0) {
                $_SESSION['error'] = "Room is not available.";
            } else {
                // Check if user already has a pending reservation for this room
                $checkStmt = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND room_id = ? AND status = 'pending'");
                $checkStmt->execute([$_SESSION['user'], $id]);
                
                if($checkStmt->rowCount() > 0) {
                    $_SESSION['error'] = "You already have a pending reservation for this room.";
                } else {
                    // Create a pending reservation
                    $insert = $pdo->prepare("INSERT INTO reservations (user_id, room_id, status, reserved_at) VALUES (?, ?, 'pending', NOW())");
                    $result = $insert->execute([$_SESSION['user'], $id]);

                    if($result) {
                        $_SESSION['success'] = "Reservation request submitted successfully! Please wait for admin approval.";
                    } else {
                        $_SESSION['error'] = "Failed to submit reservation request.";
                    }
                }
            }

        } catch(PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }

        header('Location: ' . site_url('user_landing'));
        exit;
    }

    public function profile() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        $data = ['user' => [], 'success' => '', 'error' => ''];

        // Get messages
        if (isset($_SESSION['success'])) {
            $data['success'] = $_SESSION['success'];
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        try {
            $pdo = $this->getDbConnection();
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$_SESSION['user']]);
            $data['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            $data['error'] = "Database error: " . $e->getMessage();
        }

        $this->call->view('user/profile', $data);
    }

    public function updateProfile() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . site_url('user/profile'));
            exit;
        }

        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($fname) || empty($lname) || empty($email)) {
            $_SESSION['error'] = "All fields except password are required.";
            header('Location: ' . site_url('user/profile'));
            exit;
        }

        try {
            $pdo = $this->getDbConnection();

            if (!empty($password)) {
                // Update with password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE students SET fname = ?, lname = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$fname, $lname, $email, $hashed, $_SESSION['user']]);
            } else {
                // Update without password
                $stmt = $pdo->prepare("UPDATE students SET fname = ?, lname = ?, email = ? WHERE id = ?");
                $stmt->execute([$fname, $lname, $email, $_SESSION['user']]);
            }

            $_SESSION['user_name'] = $fname . ' ' . $lname;
            $_SESSION['success'] = "Profile updated successfully!";

        } catch(PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }

        header('Location: ' . site_url('user/profile'));
        exit;
    }

    public function myReservations() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        $data = ['reservations' => [], 'success' => '', 'error' => ''];

        try {
            $pdo = $this->getDbConnection();
            $stmt = $pdo->prepare("SELECT r.*, ro.room_number, ro.beds, ro.available, ro.payment 
                                 FROM reservations r 
                                 JOIN rooms ro ON r.room_id = ro.id 
                                 WHERE r.user_id = ? 
                                 ORDER BY r.id DESC");
            $stmt->execute([$_SESSION['user']]);
            $data['reservations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            $data['error'] = "Database error: " . $e->getMessage();
        }

        $this->call->view('user/reservations', $data);
    }

    public function contact() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        $data = ['success' => '', 'error' => ''];

        if (isset($_SESSION['success'])) {
            $data['success'] = $_SESSION['success'];
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        $this->call->view('user/contact', $data);
    }

    public function sendMessage() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . site_url('user/contact'));
            exit;
        }

        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($subject) || empty($message)) {
            $_SESSION['error'] = "Subject and message are required.";
            header('Location: ' . site_url('user/contact'));
            exit;
        }

        try {
            $pdo = $this->getDbConnection();
            
            // Create messages table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                admin_reply TEXT DEFAULT NULL,
                status ENUM('unread', 'read', 'replied') DEFAULT 'unread'
            )");

            $stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message, status) VALUES (?, ?, ?, 'unread')");
            $stmt->execute([$_SESSION['user'], $subject, $message]);

            $_SESSION['success'] = "Message sent successfully! We'll get back to you soon.";

        } catch(PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }

        header('Location: ' . site_url('user/contact'));
        exit;
    }
}
