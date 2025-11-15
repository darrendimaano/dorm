<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class AdminLandingController extends Controller {

    public function index() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        // Check if admin
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        // Redirect to main dashboard instead of having duplicate
        header('Location: ' . site_url('dashboard'));
        exit;
    }

    public function approve($id) {
        $this->updateReservationStatus($id, 'approved');
    }

    public function reject($id) {
        $this->updateReservationStatus($id, 'rejected');
    }

    private function updateReservationStatus($id, $status) {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        try {
            $reservationsModel = new ReservationsModel();
            
            if ($status === 'approved') {
                // Get reservation details to update room availability
                $reservation = $reservationsModel->getReservationById($id);
                if ($reservation) {
                    // Update reservation status
                    $reservationsModel->updateStatus($id, $status);
                    
                    // Reduce room availability
                    require_once __DIR__ . '/../config/DatabaseConfig.php';
                    $dbConfig = DatabaseConfig::getInstance();
                    $pdo = $dbConfig->getConnection();
                    $updateRoom = $pdo->prepare("UPDATE rooms SET available = available - 1 WHERE id = ? AND available > 0");
                    $updateRoom->execute([$reservation['room_id']]);
                    
                    $_SESSION['success'] = "Reservation approved successfully!";
                } else {
                    $_SESSION['error'] = "Reservation not found.";
                }
            } else {
                // Just update status for rejection
                $reservationsModel->updateStatus($id, $status);
                $_SESSION['success'] = "Reservation rejected successfully!";
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating reservation: " . $e->getMessage();
        }

        header('Location: ' . site_url('admin/reservations'));
        exit;
    }

    public function messages() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        $data = ['messages' => [], 'success' => '', 'error' => ''];

        if (isset($_SESSION['success'])) {
            $data['success'] = $_SESSION['success'];
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            
            $stmt = $pdo->query("SELECT m.*, s.fname, s.lname, s.email 
                               FROM messages m 
                               JOIN students s ON m.user_id = s.id 
                               ORDER BY m.id DESC");
            $data['messages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $data['error'] = "Error loading messages: " . $e->getMessage();
        }

        $this->call->view('admin/messages', $data);
    }

    public function replyMessage($id) {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . site_url('admin/messages'));
            exit;
        }

        $reply = trim($_POST['reply'] ?? '');

        if (empty($reply)) {
            $_SESSION['error'] = "Reply message cannot be empty.";
            header('Location: ' . site_url('admin/messages'));
            exit;
        }

        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            
            $stmt = $pdo->prepare("UPDATE messages SET admin_reply = ?, status = 'replied' WHERE id = ?");
            $stmt->execute([$reply, $id]);

            $_SESSION['success'] = "Reply sent successfully!";
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error sending reply: " . $e->getMessage();
        }

        header('Location: ' . site_url('admin/messages'));
        exit;
    }

    public function rooms() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        $data = ['rooms' => [], 'success' => '', 'error' => ''];
        
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
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            
            $stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_number ASC");
            $data['rooms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $data['error'] = "Error loading rooms: " . $e->getMessage();
        }

        $this->call->view('admin/rooms', $data);
    }
}
