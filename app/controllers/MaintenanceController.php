<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class MaintenanceController extends Controller {
    
    public function __construct() {
        parent::__construct();
        if(session_status() === PHP_SESSION_NONE) session_start();
    }

    // User maintenance requests page
    public function index() {
        $this->requireUser();
        // Use $_SESSION['user'] directly as user ID (consistent with other controllers)
        $user_id = $_SESSION['user'];
        $requests = $this->getUserMaintenanceRequests($user_id);

        $data = [
            'maintenanceRequests' => $requests,
            'userRooms' => $this->getUserRooms($user_id),
            'pendingCount' => $this->countRequestsByStatus($requests, 'pending'),
            'inProgressCount' => $this->countRequestsByStatus($requests, 'in_progress'),
            'completedCount' => $this->countRequestsByStatus($requests, 'completed'),
            'success' => $this->session->flashdata('success') ?? '',
            'error' => $this->session->flashdata('error') ?? ''
        ];

        $this->call->view('user/maintenance', $data);
    }

    // Submit new maintenance request
    public function submit() {
        $this->requireUser();
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
        $this->requireAdmin();

        $data = [
            'maintenanceRequests' => $this->getAllMaintenanceRequests(),
            'success' => $this->session->flashdata('success') ?? '',
            'error' => $this->session->flashdata('error') ?? ''
        ];

        $this->call->view('admin/maintenance', $data);
    }

    // Update maintenance request status (admin only)
    public function updateStatus() {
        $this->requireAdmin();
        
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
        
        redirect(site_url('admin/maintenance'));
    }

    private function requireUser(): void {
        if (!isset($_SESSION['user'])) {
            redirect(site_url('auth/login'));
            exit;
        }
    }

    private function requireAdmin(): void {
        if (!isset($_SESSION['admin'])) {
            redirect(site_url('auth/login'));
            exit;
        }
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

    private function getUserRooms($user_id) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("SELECT DISTINCT rm.id, rm.room_number
                                   FROM reservations r
                                   JOIN rooms rm ON r.room_id = rm.id
                                   WHERE r.user_id = ?
                                     AND r.status IN ('approved', 'confirmed')
                                   ORDER BY rm.room_number ASC");
            $stmt->execute([$user_id]);

            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rooms)) {
                return $rooms;
            }

            // Fallback to any rooms tied to maintenance requests if reservation data is missing
            $fallback = $pdo->prepare("SELECT DISTINCT rm.id, rm.room_number
                                        FROM maintenance_requests mr
                                        JOIN rooms rm ON mr.room_id = rm.id
                                        WHERE mr.user_id = ?
                                        ORDER BY rm.room_number ASC");
            $fallback->execute([$user_id]);
            return $fallback->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function countRequestsByStatus(array $requests, string $status): int {
        $count = 0;
        foreach ($requests as $request) {
            if (($request['status'] ?? '') === $status) {
                $count++;
            }
        }
        return $count;
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