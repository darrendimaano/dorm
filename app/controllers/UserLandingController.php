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

            // Get user's current reservation and payment status
            $currentReservationQuery = "
                SELECT 
                    r.*,
                    rm.room_name,
                    rm.room_number,
                    rm.monthly_rate,
                    DATEDIFF(r.monthly_due_date, CURDATE()) as days_until_due,
                    CASE 
                        WHEN r.last_payment_date IS NULL THEN 'No Payment Yet'
                        WHEN DATEDIFF(CURDATE(), r.last_payment_date) > 30 THEN 'Overdue'
                        WHEN DATEDIFF(r.monthly_due_date, CURDATE()) <= 3 THEN 'Due Soon'
                        ELSE 'Up to Date'
                    END as payment_status
                FROM reservations r
                JOIN rooms rm ON r.room_id = rm.id
                WHERE r.user_id = ? AND r.status = 'approved'
                ORDER BY r.id DESC
                LIMIT 1
            ";
            
            $currentStmt = $pdo->prepare($currentReservationQuery);
            $currentStmt->execute([$_SESSION['user']]);
            $data['currentReservation'] = $currentStmt->fetch(PDO::FETCH_ASSOC);

            // Get recent activity (last 3 payments)
            $recentPaymentsQuery = "
                SELECT 
                    ph.payment_date,
                    ph.amount,
                    ph.payment_method,
                    rm.room_number
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE r.user_id = ?
                ORDER BY ph.payment_date DESC
                LIMIT 3
            ";
            
            $paymentsStmt = $pdo->prepare($recentPaymentsQuery);
            $paymentsStmt->execute([$_SESSION['user']]);
            $data['recentPayments'] = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get maintenance request count
            $maintenanceStmt = $pdo->prepare("SELECT COUNT(*) as count FROM maintenance_requests WHERE user_id = ? AND status = 'pending'");
            $maintenanceStmt->execute([$_SESSION['user']]);
            $data['maintenanceCount'] = $maintenanceStmt->fetch(PDO::FETCH_ASSOC)['count'];

        } catch(PDOException $e) {
            $data['error'] = "Database error: " . $e->getMessage();
        }

        $this->call->view('user_landing', $data);
    }

    public function reserveRoom($id = null) {
        if(session_status() === PHP_SESSION_NONE) session_start();

        // Check if it's an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        // If no ID passed, try to get from URL
        if ($id === null) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('/\/user\/reserve\/(\d+)/', $uri, $matches)) {
                $id = $matches[1];
            } else {
                $message = "Invalid reservation request.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $message]);
                    exit;
                } else {
                    $_SESSION['error'] = $message;
                    header('Location: ' . site_url('user_landing'));
                    exit;
                }
            }
        }

        if(!isset($_SESSION['user'])) {
            $message = "You must be logged in to make a reservation.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            } else {
                header('Location: ' . site_url('auth/login'));
                exit;
            }
        }

        if($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            $message = "Invalid request method.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            } else {
                $_SESSION['error'] = $message;
                header('Location: ' . site_url('user_landing'));
                exit;
            }
        }

        // For GET requests (direct URL access), redirect with message
        if($_SERVER['REQUEST_METHOD'] === 'GET') {
            $message = "Please use the reservation button on a room to make a reservation.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            } else {
                $_SESSION['error'] = $message;
                header('Location: ' . site_url('user_landing'));
                exit;
            }
        }

        try {
            $pdo = $this->getDbConnection();
            
            // Get quantity from POST data (default to 1 if not provided)
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            if ($quantity < 1) $quantity = 1;

            // Get the room
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$room) {
                $message = "Room not found.";
                $success = false;
            } elseif($room['available'] <= 0) {
                $message = "Room is not available.";
                $success = false;
            } elseif($quantity > $room['available']) {
                $message = "Not enough rooms available. Only {$room['available']} room(s) available.";
                $success = false;
            } else {
                // Check if user already has a pending reservation for this room
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE user_id = ? AND room_id = ? AND status = 'pending'");
                $checkStmt->execute([$_SESSION['user'], $id]);
                $existingReservations = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if($existingReservations > 0) {
                    $message = "You already have a pending reservation for this room.";
                    $success = false;
                } else {
                    // Begin transaction for multiple reservations
                    $pdo->beginTransaction();
                    
                    try {
                        $successCount = 0;
                        
                        // Create multiple pending reservations
                        $insert = $pdo->prepare("INSERT INTO reservations (user_id, room_id, status, reserved_at) VALUES (?, ?, 'pending', NOW())");
                        
                        for($i = 0; $i < $quantity; $i++) {
                            $result = $insert->execute([$_SESSION['user'], $id]);
                            if($result) {
                                $successCount++;
                            }
                        }
                        
                        if($successCount == $quantity) {
                            $pdo->commit();
                            $message = "Reservation request submitted successfully! {$quantity} room(s) requested for approval.";
                            $success = true;
                        } else {
                            $pdo->rollback();
                            $message = "Failed to submit all reservation requests.";
                            $success = false;
                        }
                        
                    } catch(PDOException $e) {
                        $pdo->rollback();
                        throw $e;
                    }
                }
            }

        } catch(PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $success = false;
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $message
            ]);
            exit;
        } else {
            // For non-AJAX requests, use session and redirect (fallback)
            if ($success) {
                $_SESSION['success'] = $message;
            } else {
                $_SESSION['error'] = $message;
            }
            header('Location: ' . site_url('user_landing'));
            exit;
        }
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

    public function redirectToLanding($id = null) {
        $_SESSION['error'] = "Invalid access method. Please use the reservation button on the room.";
        header('Location: ' . site_url('user_landing'));
        exit;
    }
}
