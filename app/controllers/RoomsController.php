<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class RoomsController extends Controller {
    
    private function checkAdminSession() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }
    }

    public function __construct() {
        parent::__construct();
        $this->call->model('RoomsModel');
    }

    // Display all rooms
    public function index() {
        $this->checkAdminSession();
        
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
            $data['rooms'] = $this->RoomsModel->getAllRooms();
        } catch (Exception $e) {
            $data['error'] = 'Error loading rooms: ' . $e->getMessage();
        }
        
        // Check if this is admin route and use appropriate view
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/admin/rooms') !== false) {
            $this->call->view('admin/rooms', $data);
        } else {
            $this->call->view('rooms/index', $data);
        }
    }

    // Create room
    public function create() {
        $this->checkAdminSession();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $room_number = trim($_POST['room_number'] ?? '');
            $beds = (int)($_POST['beds'] ?? 1);
            $available = (int)($_POST['available'] ?? 1);
            $payment = (float)($_POST['payment'] ?? 0);
            
            if (empty($room_number) || $beds <= 0 || $available < 0 || $payment < 0) {
                $_SESSION['error'] = 'All fields are required and must have valid values.';
            } else {
                // Handle picture upload
                $picture_path = null;
                if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                    $picture_path = $this->uploadPicture($_FILES['picture']);
                    if (!$picture_path) {
                        $_SESSION['error'] = 'Failed to upload picture. Please try again.';
                        header('Location: ' . site_url('rooms'));
                        exit;
                    }
                }
                
                $data = [
                    'room_number' => $room_number,
                    'beds' => $beds,
                    'available' => $available,
                    'payment' => $payment,
                    'picture' => $picture_path
                ];
                
                try {
                    if ($this->RoomsModel->insertRoom($data)) {
                        $_SESSION['success'] = 'Dormitory room created successfully!';
                    } else {
                        $_SESSION['error'] = 'Failed to create room.';
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                }
            }
            
            // Determine redirect URL based on current route
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $redirectUrl = (strpos($uri, '/admin/rooms') !== false) ? 'admin/rooms' : 'rooms';
            header('Location: ' . site_url($redirectUrl));
            exit;
        } else {
            $this->call->view('rooms/create');
        }
    }

    // Update room
    public function update($id) {
        $this->checkAdminSession();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $room_number = trim($_POST['room_number'] ?? '');
            $beds = (int)($_POST['beds'] ?? 1);
            $available = (int)($_POST['available'] ?? 1);
            $payment = (float)($_POST['payment'] ?? 0);
            
            // Improved validation with specific error messages
            $errors = [];
            if (empty($room_number)) {
                $errors[] = "Room number is required";
            }
            if ($beds <= 0) {
                $errors[] = "Number of beds must be greater than 0";
            }
            if ($available < 0) {
                $errors[] = "Available slots cannot be negative";
            }
            if ($payment < 0) {
                $errors[] = "Payment amount cannot be negative";
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode(". ", $errors);
            } else {
                // Handle picture upload
                $picture_path = $_POST['existing_picture'] ?? null; // Keep existing if no new upload
                if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                    $new_picture = $this->uploadPicture($_FILES['picture']);
                    if ($new_picture) {
                        // Delete old picture if exists
                        if ($picture_path && file_exists($picture_path)) {
                            unlink($picture_path);
                        }
                        $picture_path = $new_picture;
                    }
                }
                
                $data = [
                    'room_number' => $room_number,
                    'beds' => $beds,
                    'available' => $available,
                    'payment' => $payment,
                    'picture' => $picture_path
                ];
                
                try {
                    $result = $this->RoomsModel->update($id, $data);
                    
                    if ($result) {
                        $_SESSION['success'] = 'Dormitory room updated successfully!';
                    } else {
                        $_SESSION['error'] = 'Failed to update room.';
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                }
            }
            
            // If there was a validation error, show the form again with the error
            if (!empty($errors)) {
                try {
                    $data['room'] = $this->RoomsModel->find($id);
                    $data['error'] = $_SESSION['error'];
                    unset($_SESSION['error']);
                    
                    if ($data['room']) {
                        // Check current route to determine which context we're in
                        $uri = $_SERVER['REQUEST_URI'] ?? '';
                        $data['isAdminRoute'] = strpos($uri, '/admin/rooms') !== false;
                        $this->call->view('rooms/update', $data);
                        return;
                    }
                } catch (Exception $e) {
                    // Error loading room data
                }
            }
            
            // Determine redirect URL based on current route or referrer
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            $isAdminContext = (strpos($uri, '/admin/rooms') !== false) || (strpos($referrer, '/admin/rooms') !== false);
            $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
            header('Location: ' . site_url($redirectUrl));
            exit;
        } else {
            // For GET requests, try to find the room and show the update form
            try {
                $data['room'] = $this->RoomsModel->find($id);
                if (!$data['room']) {
                    $_SESSION['error'] = 'Room not found.';
                    $uri = $_SERVER['REQUEST_URI'] ?? '';
                    $isAdminContext = strpos($uri, '/admin/rooms') !== false;
                    $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
                    header('Location: ' . site_url($redirectUrl));
                    exit;
                }
                
                // Always show the update view
                // Check current route to determine which context we're in
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                $data['isAdminRoute'] = strpos($uri, '/admin/rooms') !== false;
                
                // Pass any session messages to the view
                if (isset($_SESSION['error'])) {
                    $data['error'] = $_SESSION['error'];
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    $data['success'] = $_SESSION['success'];
                    unset($_SESSION['success']);
                }
                
                $this->call->view('rooms/update', $data);
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error loading room: ' . $e->getMessage();
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                $isAdminContext = strpos($uri, '/admin/rooms') !== false;
                $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
                header('Location: ' . site_url($redirectUrl));
                exit;
            }
        }
    }

    // Delete room
    public function delete($id) {
        $this->checkAdminSession();
        
        try {
            // Get room info to delete picture
            $room = $this->RoomsModel->find($id);
            if ($room && !empty($room['picture']) && file_exists($room['picture'])) {
                unlink($room['picture']);
            }
            
            if ($this->RoomsModel->delete($id)) {
                $_SESSION['success'] = 'Room deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete room.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error deleting room: ' . $e->getMessage();
        }
        
        // Determine redirect URL based on current route
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $redirectUrl = (strpos($uri, '/admin/rooms') !== false) ? 'admin/rooms' : 'rooms';
        header('Location: ' . site_url($redirectUrl));
        exit;
    }
    
    // Helper method to upload pictures
    private function uploadPicture($file) {
        // Create upload directory if it doesn't exist
        $upload_dir = 'public/uploads/rooms/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Validate file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        // Check file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'room_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filepath;
        }
        
        return false;
    }
}
