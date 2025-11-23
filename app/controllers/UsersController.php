<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';
require_once __DIR__ . '/../models/RoomsModel.php';

class UsersController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('UsersModel');
    }

    public function index() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $data = [
            'users' => $this->UsersModel->all(),
            'success' => $_SESSION['users_success'] ?? '',
            'error' => $_SESSION['users_error'] ?? ''
        ];

        unset($_SESSION['users_success'], $_SESSION['users_error']);

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
                if(session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['users_success'] = "User created successfully.";
                redirect(site_url('users'));
            }
        } else {
            $this->call->view('users/create');
        }
    }

    public function update($id = null) {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $routeId = (int) $id;
        $postId = (int) ($this->io->post('id') ?? 0);
        $targetId = $routeId > 0 ? $routeId : $postId;

        if($targetId <= 0) {
            $message = 'Missing user identifier.';

            if($this->isAjaxRequest()) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => $message
                ], 422);
            }

            $_SESSION['users_error'] = $message;
            redirect(site_url('users'));
            return;
        }

        $user = $this->UsersModel->find($targetId);
        if(!$user) {
            $_SESSION['users_error'] = "User not found.";
            if($this->isAjaxRequest()) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => 'User not found.'
                ], 404);
            }
            redirect(site_url('users'));
            return;
        }

        if($this->io->method() == 'post') {
            $postData = $this->io->post();

            $fromModal = isset($postData['from_modal']) && $postData['from_modal'] === '1';
            $fullNameInput = isset($postData['full_name']) ? trim((string) $postData['full_name']) : '';
            $email = isset($postData['email']) ? trim((string) $postData['email']) : '';
            $fname = isset($postData['fname']) ? trim((string) $postData['fname']) : '';
            $lname = isset($postData['lname']) ? trim((string) $postData['lname']) : '';
            $errors = [];

            if($fullNameInput !== '') {
                $nameParts = preg_split('/\s+/', $fullNameInput);
                $nameParts = array_filter($nameParts, static function($part) {
                    return $part !== '';
                });

                if(count($nameParts) < 2) {
                    $errors[] = "Please provide full name with first and last name.";
                } else {
                    $fname = array_shift($nameParts);
                    $lname = implode(' ', $nameParts);
                }
            }

            if($fname === '' || $lname === '') {
                $errors[] = "First name and last name are required.";
            }

            if($email === '') {
                $errors[] = "Email address is required.";
            } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please provide a valid email address.";
            }

            if(empty($errors) && $this->UsersModel->emailExistsForOther($email, $targetId)) {
                $errors[] = "Email address is already in use.";
            }

            if(!empty($errors)) {
                $message = implode(' ', $errors);

                if($fromModal || $this->isAjaxRequest()) {
                    $_SESSION['users_error'] = $message;
                    if($this->isAjaxRequest()) {
                        $this->respondJson([
                            'status' => 'error',
                            'message' => $message
                        ], 422);
                    }
                    redirect(site_url('users'));
                    return;
                }

                $data['error'] = $message;
                $data['user'] = $this->UsersModel->find($targetId);
                $this->call->view('users/update', $data);
                return;
            }

            $updateData = [
                'fname' => $fname,
                'lname' => $lname,
                'email' => $email
            ];

            $result = $this->UsersModel->update($targetId, $updateData);

            if($result === false) {
                $_SESSION['users_error'] = "Unable to update user at this time.";
                if($this->isAjaxRequest()) {
                    $this->respondJson([
                        'status' => 'error',
                        'message' => 'Unable to update user at this time.'
                    ], 500);
                }
            } else {
                $_SESSION['users_success'] = "User updated successfully.";
                if($this->isAjaxRequest()) {
                    $updated = $this->UsersModel->find($targetId);
                    $fullName = trim(($updated['fname'] ?? '') . ' ' . ($updated['lname'] ?? ''));

                    $this->respondJson([
                        'status' => 'success',
                        'message' => 'User updated successfully.',
                        'data' => [
                            'id' => (int) ($updated['id'] ?? $targetId),
                            'fname' => (string) ($updated['fname'] ?? $fname),
                            'lname' => (string) ($updated['lname'] ?? $lname),
                            'email' => (string) ($updated['email'] ?? $email),
                            'full_name' => $fullName
                        ]
                    ]);
                }
            }

            redirect(site_url('users'));
            return;
        } else {
            $data['user'] = $user;
            $data['error'] = $_SESSION['users_error'] ?? '';
            unset($_SESSION['users_error']);
            $this->call->view('users/update', $data);
        }
    }

    public function updateAjax() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if($this->io->method() !== 'post') {
            $this->respondJson([
                'status' => 'error',
                'message' => 'Invalid request method.'
            ], 405);
        }

        $id = (int) $this->io->post('id');
        if($id <= 0) {
            $this->respondJson([
                'status' => 'error',
                'message' => 'Missing user identifier.'
            ], 422);
        }

        $user = $this->UsersModel->find($id);
        if(!$user) {
            $this->respondJson([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        $postData = $this->io->post();

        $fullNameInput = isset($postData['full_name']) ? trim((string) $postData['full_name']) : '';
        $email = isset($postData['email']) ? trim((string) $postData['email']) : '';
        $fname = isset($postData['fname']) ? trim((string) $postData['fname']) : '';
        $lname = isset($postData['lname']) ? trim((string) $postData['lname']) : '';

        $errors = [];

        if($fullNameInput !== '') {
            $nameParts = preg_split('/\s+/', $fullNameInput);
            $nameParts = array_filter($nameParts, static function($part) {
                return $part !== '';
            });

            if(count($nameParts) < 2) {
                $errors[] = "Please provide full name with first and last name.";
            } else {
                $fname = array_shift($nameParts);
                $lname = implode(' ', $nameParts);
            }
        }

        if($fname === '' || $lname === '') {
            $errors[] = "First name and last name are required.";
        }

        if($email === '') {
            $errors[] = "Email address is required.";
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please provide a valid email address.";
        }

        if(empty($errors) && $this->UsersModel->emailExistsForOther($email, $id)) {
            $errors[] = "Email address is already in use.";
        }

        if(!empty($errors)) {
            $this->respondJson([
                'status' => 'error',
                'message' => implode(' ', $errors)
            ], 422);
        }

        $updateData = [
            'fname' => $fname,
            'lname' => $lname,
            'email' => $email
        ];

        $result = $this->UsersModel->update($id, $updateData);

        if($result === false) {
            $this->respondJson([
                'status' => 'error',
                'message' => 'Unable to update user at this time.'
            ], 500);
        }

        $updated = $this->UsersModel->find($id);
        $fullName = trim(($updated['fname'] ?? '') . ' ' . ($updated['lname'] ?? ''));

        $_SESSION['users_success'] = "User updated successfully.";

        $this->respondJson([
            'status' => 'success',
            'message' => 'User updated successfully.',
            'data' => [
                'id' => (int) ($updated['id'] ?? $id),
                'fname' => (string) ($updated['fname'] ?? $fname),
                'lname' => (string) ($updated['lname'] ?? $lname),
                'email' => (string) ($updated['email'] ?? $email),
                'full_name' => $fullName
            ]
        ]);
    }

    public function delete($id = null) {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $routeId = (int) $id;
        $postId = (int) ($this->io->post('id') ?? 0);
        $targetId = $routeId > 0 ? $routeId : $postId;

        if($targetId <= 0) {
            $message = 'Missing user identifier.';

            if($this->isAjaxRequest()) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => $message
                ], 422);
            }

            $_SESSION['users_error'] = $message;
            redirect(site_url('users'));
            return;
        }

        $existingUser = $this->UsersModel->find($targetId);
        if(!$existingUser) {
            $message = 'User not found.';

            if($this->isAjaxRequest()) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => $message
                ], 404);
            }

            $_SESSION['users_error'] = $message;
            redirect(site_url('users'));
            return;
        }

        $result = $this->UsersModel->delete($targetId);

        if($this->isAjaxRequest()) {
            if($result === false) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => 'Unable to delete user.'
                ], 500);
            }

            $this->respondJson([
                'status' => 'success',
                'message' => 'User deleted successfully.',
                'data' => ['id' => (int) $targetId]
            ]);
        }

        if($result === false) {
            $_SESSION['users_error'] = "Unable to delete user.";
        } else {
            $_SESSION['users_success'] = "User deleted successfully.";
        }

        redirect(site_url('users'));
    }

    private function isAjaxRequest(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function respondJson(array $payload, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
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

            // Availability already adjusted with direct SQL update above.
            
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

                // Availability increment handled via direct UPDATE above.
                
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
