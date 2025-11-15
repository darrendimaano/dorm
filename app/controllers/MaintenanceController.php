<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class MaintenanceController extends Controller {
    
    public function __construct() {
        parent::__construct();
        if(session_status() === PHP_SESSION_NONE) session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            redirect(site_url('auth/login'));
            exit;
        }
    }

    // User maintenance requests page
    public function index() {
        // Use $_SESSION['user'] directly as user ID (consistent with other controllers)
        $user_id = $_SESSION['user'];
        
        $data['requests'] = $this->getUserMaintenanceRequests($user_id);
        $data['rooms'] = $this->getAvailableRooms();
        $this->call->view('user/maintenance', $data);
    }

    // Submit new maintenance request
    public function submit() {
        if ($this->io->method() == 'post') {
            $title = $this->io->post('title');
            $description = $this->io->post('description');
            $priority = $this->io->post('priority');
            $room_id = $this->io->post('room_id') ?: null;
            
            // Use $_SESSION['user'] directly as user ID (consistent with other controllers)
            $user_id = $_SESSION['user'];
            
            if (!empty($title) && !empty($description)) {
                $data = [
                    'user_id' => $user_id,
                    'room_id' => $room_id,
                    'title' => $title,
                    'description' => $description,
                    'priority' => $priority,
                    'status' => 'pending'
                ];
                
                if ($this->insertMaintenanceRequest($data)) {
                    $this->session->set_flashdata('success', 'Maintenance request submitted successfully!');
                } else {
                    $this->session->set_flashdata('error', 'Failed to submit maintenance request.');
                }
            } else {
                $this->session->set_flashdata('error', 'Please fill in all required fields.');
            }
        }
        
        redirect(site_url('user/maintenance'));
    }

    // Admin maintenance requests page
    public function admin() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            redirect(site_url('auth/login'));
        }
        
        $data['requests'] = $this->getAllMaintenanceRequests();
        $this->call->view('admin/maintenance', $data);
    }

    // Update maintenance request status (admin only)
    public function updateStatus() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            redirect(site_url('auth/login'));
        }
        
        if ($this->io->method() == 'post') {
            $id = $this->io->post('id');
            $status = $this->io->post('status');
            $admin_notes = $this->io->post('admin_notes');
            
            $data = [
                'status' => $status,
                'admin_notes' => $admin_notes
            ];
            
            if ($status === 'completed') {
                $data['completed_at'] = date('Y-m-d H:i:s');
            }
            
            if ($this->updateMaintenanceRequest($id, $data)) {
                $this->session->set_flashdata('success', 'Maintenance request updated successfully!');
            } else {
                $this->session->set_flashdata('error', 'Failed to update maintenance request.');
            }
        }
        
        redirect(site_url('maintenance/admin'));
    }

    // Helper methods
    private function getUserMaintenanceRequests($user_id) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("SELECT mr.*, r.room_number 
                                  FROM maintenance_requests mr 
                                  LEFT JOIN rooms r ON mr.room_id = r.id 
                                  WHERE mr.user_id = ? 
                                  ORDER BY mr.created_at DESC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function getAllMaintenanceRequests() {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("SELECT mr.*, s.fname, s.lname, s.email, r.room_number 
                                  FROM maintenance_requests mr 
                                  LEFT JOIN students s ON mr.user_id = s.id 
                                  LEFT JOIN rooms r ON mr.room_id = r.id 
                                  ORDER BY mr.created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function insertMaintenanceRequest($data) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, room_id, title, description, priority, status) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['user_id'],
                $data['room_id'],
                $data['title'],
                $data['description'],
                $data['priority'],
                $data['status']
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    private function updateMaintenanceRequest($id, $data) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            
            $sql = "UPDATE maintenance_requests SET status = ?, admin_notes = ?";
            $params = [$data['status'], $data['admin_notes']];
            
            if (isset($data['completed_at'])) {
                $sql .= ", completed_at = ?";
                $params[] = $data['completed_at'];
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            return false;
        }
    }

    private function getAvailableRooms() {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->query("SELECT id, room_number FROM rooms ORDER BY room_number ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}