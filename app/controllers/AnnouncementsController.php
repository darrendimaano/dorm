<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class AnnouncementsController extends Controller {
    
    public function __construct() {
        parent::__construct();
        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            redirect(site_url('auth/login'));
        }
    }

    // User announcements page
    public function index() {
        $data['announcements'] = $this->getActiveAnnouncements();
        $this->call->view('user/announcements', $data);
    }

    // Get announcement details with comments
    public function view($id) {
        $data['announcement'] = $this->getAnnouncement($id);
        $data['comments'] = $this->getAnnouncementComments($id);
        
        if (!$data['announcement']) {
            redirect(site_url('user/announcements'));
        }
        
        $this->call->view('user/announcement_view', $data);
    }

    // Add comment to announcement
    public function addComment() {
        if ($this->io->method() == 'post') {
            $announcement_id = $this->io->post('announcement_id');
            $comment = trim($this->io->post('comment'));
            
            if (!empty($comment) && !empty($announcement_id)) {
                if ($this->insertComment($announcement_id, $_SESSION['user']['id'], $comment)) {
                    $this->session->set_flashdata('success', 'Comment added successfully!');
                } else {
                    $this->session->set_flashdata('error', 'Failed to add comment.');
                }
            } else {
                $this->session->set_flashdata('error', 'Comment cannot be empty.');
            }
        }
        
        $announcement_id = $this->io->post('announcement_id');
        redirect(site_url('announcements/view/' . $announcement_id));
    }

    // Admin announcements page
    public function admin() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            redirect(site_url('auth/login'));
        }
        
        $data['announcements'] = $this->getAllAnnouncements();
        $this->call->view('admin/announcements', $data);
    }

    // Create new announcement (admin only)
    public function create() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            redirect(site_url('auth/login'));
        }
        
        if ($this->io->method() == 'post') {
            $id = $this->io->post('id');
            $title = $this->io->post('title');
            $content = $this->io->post('content');
            $priority = $this->io->post('priority');
            $expires_at = $this->io->post('expires_at') ?: null;
            
            if (!empty($title) && !empty($content)) {
                $data = [
                    'title' => $title,
                    'content' => $content,
                    'priority' => $priority,
                    'expires_at' => $expires_at,
                    'created_by' => $_SESSION['user']['id'],
                    'is_active' => 1
                ];
                
                $success = false;
                if (!empty($id)) {
                    // Update existing announcement
                    $success = $this->updateAnnouncement($id, $data);
                    $message = $success ? 'Announcement updated successfully!' : 'Failed to update announcement.';
                } else {
                    // Create new announcement
                    $success = $this->insertAnnouncement($data);
                    $message = $success ? 'Announcement created successfully!' : 'Failed to create announcement.';
                }
                
                if ($success) {
                    $this->session->set_flashdata('success', $message);
                } else {
                    $this->session->set_flashdata('error', $message);
                }
            } else {
                $this->session->set_flashdata('error', 'Please fill in all required fields.');
            }
        }
        
        redirect(site_url('admin/announcements'));
    }

    // Get single announcement for editing (admin only)
    public function get($id) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        
        $announcement = $this->getAnnouncementById($id);
        if ($announcement) {
            header('Content-Type: application/json');
            echo json_encode($announcement);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Announcement not found']);
        }
    }

    // Toggle announcement status (admin only)
    public function toggleStatus() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            redirect(site_url('auth/login'));
        }
        
        if ($this->io->method() == 'post') {
            $id = $this->io->post('id');
            $status = $this->io->post('status');
            
            if ($this->updateAnnouncementStatus($id, $status)) {
                $this->session->set_flashdata('success', 'Announcement status updated successfully!');
            } else {
                $this->session->set_flashdata('error', 'Failed to update announcement status.');
            }
        }
        
        redirect(site_url('announcements/admin'));
    }

    // Helper methods
    private function getActiveAnnouncements() {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("SELECT a.*, COUNT(ac.id) as comment_count 
                                  FROM announcements a 
                                  LEFT JOIN announcement_comments ac ON a.id = ac.announcement_id 
                                  WHERE a.is_active = 1 
                                  AND (a.expires_at IS NULL OR a.expires_at > NOW()) 
                                  GROUP BY a.id 
                                  ORDER BY a.priority DESC, a.created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function getAllAnnouncements() {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("SELECT a.*, COUNT(ac.id) as comment_count 
                                  FROM announcements a 
                                  LEFT JOIN announcement_comments ac ON a.id = ac.announcement_id 
                                  GROUP BY a.id 
                                  ORDER BY a.created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function getAnnouncement($id) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ? AND is_active = 1");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    private function getAnnouncementById($id) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    private function getAnnouncementComments($announcement_id) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("SELECT ac.*, s.fname, s.lname 
                                  FROM announcement_comments ac 
                                  JOIN students s ON ac.user_id = s.id 
                                  WHERE ac.announcement_id = ? 
                                  ORDER BY ac.created_at ASC");
            $stmt->execute([$announcement_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function insertAnnouncement($data) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, priority, expires_at, created_by, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['title'],
                $data['content'],
                $data['priority'],
                $data['expires_at'],
                $data['created_by'],
                $data['is_active']
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    private function insertComment($announcement_id, $user_id, $comment) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("INSERT INTO announcement_comments (announcement_id, user_id, comment) VALUES (?, ?, ?)");
            return $stmt->execute([$announcement_id, $user_id, $comment]);
        } catch (Exception $e) {
            return false;
        }
    }

    private function updateAnnouncementStatus($id, $status) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("UPDATE announcements SET is_active = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    private function updateAnnouncement($id, $data) {
        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, priority = ?, expires_at = ? WHERE id = ?");
            return $stmt->execute([
                $data['title'],
                $data['content'],
                $data['priority'],
                $data['expires_at'],
                $id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
}