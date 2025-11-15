<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class UsersController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('UsersModel');
    }

    public function index() {
        $data['users'] = $this->UsersModel->All();
        $this->call->view('users/index', $data);
    }

    public function create() {
        if($this->io->method() == 'post') {
            $password = $this->io->post('password');
            $confirm  = $this->io->post('confirm_password');

            if($password !== $confirm) {
                $data['error'] = "Passwords do not match.";
                $this->call->view('users/create', $data);
                return;
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $data = [
                'fname' => $this->io->post('fname'),
                'lname' => $this->io->post('lname'),
                'email' => $this->io->post('email'),
                'password' => $hashed
            ];

            if($this->UsersModel->insert($data)) {
                redirect(site_url('users'));
            }
        } else {
            $this->call->view('users/create');
        }
    }

    public function update($id) {
        $user = $this->UsersModel->find($id);
        if(!$user) {
            echo "User not found.";
            return;
        }

        if($this->io->method() == 'post') {
            $password = $this->io->post('password');
            $data = [
                'fname' => $this->io->post('fname'),
                'lname' => $this->io->post('lname'),
                'email' => $this->io->post('email')
            ];

            if(!empty($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if($this->UsersModel->update($id, $data)) redirect(site_url('users'));
        } else {
            $data['user'] = $user;
            $this->call->view('users/update', $data);
        }
    }

    public function delete($id) {
        if($this->UsersModel->delete($id)) redirect(site_url('users'));
    }

    public function tenants() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        
        // Check if admin
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        $data = ['tenants' => [], 'success' => '', 'error' => ''];
        
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
            
            // Get all current tenants with their room and bed information
            $stmt = $pdo->query("
                SELECT 
                    ro.id as occupancy_id,
                    s.id as student_id,
                    s.fname, 
                    s.lname, 
                    s.email,
                    r.room_number,
                    ro.bed_number,
                    ro.check_in_date,
                    ro.monthly_payment,
                    ro.status
                FROM room_occupancy ro
                JOIN students s ON ro.student_id = s.id 
                JOIN rooms r ON ro.room_id = r.id
                WHERE ro.status = 'active'
                ORDER BY r.room_number ASC, ro.bed_number ASC
            ");
            $data['tenants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $data['error'] = "Error loading tenants: " . $e->getMessage();
        }

        $this->call->view('users/tenants', $data);
    }

    public function assignTenant() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        
        if(!isset($_SESSION['admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . site_url('users/tenants'));
            exit;
        }

        try {
            $student_id = $_POST['student_id'] ?? null;
            $room_id = $_POST['room_id'] ?? null;
            $bed_number = $_POST['bed_number'] ?? null;
            $monthly_payment = $_POST['monthly_payment'] ?? null;

            if (!$student_id || !$room_id || !$bed_number || !$monthly_payment) {
                $_SESSION['error'] = "All fields are required.";
                header('Location: ' . site_url('users/tenants'));
                exit;
            }

            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            
            // Check if bed is available
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) FROM room_occupancy 
                WHERE room_id = ? AND bed_number = ? AND status = 'active'
            ");
            $checkStmt->execute([$room_id, $bed_number]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $_SESSION['error'] = "This bed is already occupied.";
                header('Location: ' . site_url('users/tenants'));
                exit;
            }
            
            // Insert new occupancy
            $insertStmt = $pdo->prepare("
                INSERT INTO room_occupancy (student_id, room_id, bed_number, check_in_date, monthly_payment, status) 
                VALUES (?, ?, ?, CURDATE(), ?, 'active')
            ");
            $insertStmt->execute([$student_id, $room_id, $bed_number, $monthly_payment]);
            
            // Update room availability
            $updateRoom = $pdo->prepare("UPDATE rooms SET available = available - 1 WHERE id = ? AND available > 0");
            $updateRoom->execute([$room_id]);
            
            $_SESSION['success'] = "Tenant assigned successfully!";
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error assigning tenant: " . $e->getMessage();
        }

        header('Location: ' . site_url('users/tenants'));
        exit;
    }

    public function removeTenant($occupancy_id) {
        if(session_status() === PHP_SESSION_NONE) session_start();
        
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('users/tenants'));
            exit;
        }

        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            
            // Get room info before removing
            $getRoomStmt = $pdo->prepare("SELECT room_id FROM room_occupancy WHERE id = ?");
            $getRoomStmt->execute([$occupancy_id]);
            $occupancy = $getRoomStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($occupancy) {
                // Remove occupancy
                $deleteStmt = $pdo->prepare("UPDATE room_occupancy SET status = 'inactive' WHERE id = ?");
                $deleteStmt->execute([$occupancy_id]);
                
                // Update room availability
                $updateRoom = $pdo->prepare("UPDATE rooms SET available = available + 1 WHERE id = ?");
                $updateRoom->execute([$occupancy['room_id']]);
                
                $_SESSION['success'] = "Tenant removed successfully!";
            } else {
                $_SESSION['error'] = "Tenant not found.";
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error removing tenant: " . $e->getMessage();
        }

        header('Location: ' . site_url('users/tenants'));
        exit;
    }
}
