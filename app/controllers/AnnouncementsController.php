<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class AnnouncementsController extends Controller {

    private string $lastErrorMessage = '';
    private bool $tablesVerified = false;

    public function __construct() {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->ensureAnnouncementTables();
    }

    // User announcements page
    public function index() {
        $this->requireStudent();

        $success = $this->session->flashdata('success');
        $error = $this->session->flashdata('error');

        $data = [
            'announcements' => $this->getActiveAnnouncements(),
            'success' => $success ?? '',
            'error' => $error ?? '',
            'userName' => $_SESSION['user_name'] ?? 'Tenant'
        ];

        $this->call->view('user/announcements', $data);
    }

    // Get announcement details with comments
    public function view($id) {
        $this->requireStudent();

        $announcementId = (int) $id;
        if ($announcementId <= 0) {
            redirect(site_url('user/announcements'));
            return;
        }

        $announcement = $this->getAnnouncement($announcementId);
        if (!$announcement) {
            redirect(site_url('user/announcements'));
            return;
        }

        $success = $this->session->flashdata('success');
        $error = $this->session->flashdata('error');

        $data = [
            'announcement' => $announcement,
            'comments' => $this->getAnnouncementComments($announcementId),
            'success' => $success ?? '',
            'error' => $error ?? ''
        ];

        $this->call->view('user/announcement_view', $data);
    }

    // Add comment to announcement
    public function addComment() {
        $this->requireStudent();

        $isAjax = $this->isAjaxRequest();

        if ($this->io->method() !== 'post') {
            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => 'Invalid request method.'
                ], 405);
            }

            redirect(site_url('user/announcements'));
            return;
        }

        $announcementId = (int) $this->io->post('announcement_id');
        $comment = trim((string) $this->io->post('comment'));

        if ($announcementId <= 0 || $comment === '') {
            $message = 'Comment cannot be empty.';
            $this->session->set_flashdata('error', $message);

            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => $message
                ], 422);
            }

            redirect(site_url('user/announcements'));
            return;
        }

        $userId = (int) $_SESSION['user'];
        $success = $this->insertComment($announcementId, $userId, $comment);

        if ($success) {
            $message = 'Comment added successfully!';
            $this->session->set_flashdata('success', $message);

            if ($isAjax) {
                $this->respondJson([
                    'status' => 'success',
                    'message' => $message
                ]);
            }
        } else {
            $message = 'Failed to add comment.';
            if ($this->lastErrorMessage !== '') {
                $message .= ' Details: ' . $this->lastErrorMessage;
            }

            $this->session->set_flashdata('error', $message);

            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => $message
                ], 500);
            }
        }

        redirect(site_url('announcements/view/' . $announcementId));
    }

    // Admin announcements page
    public function admin() {
        $this->requireAdmin();

        $announcements = $this->getAllAnnouncements();

        $success = $this->session->flashdata('success');
        $error = $this->session->flashdata('error');

        $data = [
            'announcements' => $announcements,
            'success' => $success ?? '',
            'error' => $error ?? '',
            'activeCount' => count(array_filter($announcements, static function ($item) {
                return (int) ($item['is_active'] ?? 0) === 1;
            })),
            'urgentCount' => count(array_filter($announcements, static function ($item) {
                return ($item['priority'] ?? '') === 'urgent';
            })),
            'totalComments' => array_sum(array_map(static function ($item) {
                return (int) ($item['comment_count'] ?? 0);
            }, $announcements))
        ];

        $this->call->view('admin/announcements', $data);
    }

    // Create new announcement (admin only)
    public function create() {
        $this->requireAdmin();

        $isAjax = $this->isAjaxRequest();
        $this->lastErrorMessage = '';

        if ($this->io->method() !== 'post') {
            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => 'Invalid request method.'
                ], 405);
            }

            redirect(site_url('admin/announcements'));
            return;
        }

        $id = (int) $this->io->post('id');
        $title = trim((string) $this->io->post('title'));
        $content = trim((string) $this->io->post('content'));
        $priority = $this->normalizePriority((string) $this->io->post('priority'));
        $expiresRaw = trim((string) $this->io->post('expires_at'));
        $expiresAt = null;
        if ($expiresRaw !== '') {
            $timestamp = strtotime($expiresRaw);
            if ($timestamp !== false) {
                $expiresAt = date('Y-m-d 23:59:59', $timestamp);
            }
        }

        if ($title === '' || $content === '') {
            $message = 'Please fill in all required fields.';

            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => $message
                ], 422);
            }

            $this->session->set_flashdata('error', $message);
            redirect(site_url('admin/announcements'));
            return;
        }

        $success = false;
        $message = 'Unable to save announcement.';

        if ($id > 0) {
            $updateData = [
                'title' => $title,
                'content' => $content,
                'priority' => $priority,
                'expires_at' => $expiresAt
            ];

            $success = $this->updateAnnouncement($id, $updateData);
            $message = $success ? 'Announcement updated successfully!' : 'Failed to update announcement.';
        } else {
            $creatorId = $this->resolveCreatorId();

            if ($creatorId === null) {
                $detail = $this->lastErrorMessage !== '' ? ' Details: ' . $this->lastErrorMessage : '';
                $message = 'Unable to determine announcement creator. Please ensure an admin account exists in the students list.' . $detail;

                if ($isAjax) {
                    $this->respondJson([
                        'status' => 'error',
                        'message' => $message
                    ], 500);
                }

                $this->session->set_flashdata('error', $message);
                redirect(site_url('admin/announcements'));
                return;
            }

            $insertData = [
                'title' => $title,
                'content' => $content,
                'priority' => $priority,
                'expires_at' => $expiresAt,
                'created_by' => $creatorId,
                'is_active' => 1
            ];

            $success = $this->insertAnnouncement($insertData);
            $message = $success ? 'Announcement created successfully!' : 'Failed to create announcement.';
        }

        if (!$success && $this->lastErrorMessage !== '') {
            $message .= ' Details: ' . $this->lastErrorMessage;
        }

        if ($success) {
            $this->session->set_flashdata('success', $message);

            if ($isAjax) {
                $this->respondJson([
                    'status' => 'success',
                    'message' => $message
                ]);
            }
        } else {
            $this->session->set_flashdata('error', $message);

            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => $message
                ], 500);
            }
        }

        redirect(site_url('admin/announcements'));
    }

    // Get single announcement for editing (admin only)
    public function get($id) {
        $this->requireAdmin();

        $announcementId = (int) $id;
        if ($announcementId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid announcement identifier']);
            return;
        }

        $announcement = $this->getAnnouncementById($announcementId);
        if ($announcement) {
            header('Content-Type: application/json');
            echo json_encode($announcement);
            return;
        }

        http_response_code(404);
        echo json_encode(['error' => 'Announcement not found']);
    }

    public function comments($id) {
        $this->requireAdmin();

        $announcementId = (int) $id;
        if ($announcementId <= 0) {
            $this->respondJson([
                'status' => 'error',
                'message' => 'Invalid announcement identifier.'
            ], 400);
        }

        $announcement = $this->getAnnouncementById($announcementId);
        if (!$announcement) {
            $this->respondJson([
                'status' => 'error',
                'message' => 'Announcement not found.',
                'id' => $announcementId
            ], 404);
        }

        $comments = $this->getAnnouncementComments($announcementId);

        $this->respondJson([
            'status' => 'success',
            'announcement' => [
                'id' => (int) $announcement['id'],
                'title' => $announcement['title'] ?? 'Announcement',
                'priority' => $announcement['priority'] ?? 'medium',
                'comment_count' => count($comments)
            ],
            'comments' => array_map(static function (array $comment) {
                $createdAt = $comment['created_at'] ?? '';
                return [
                    'id' => (int) ($comment['id'] ?? 0),
                    'user_name' => $comment['user_name'] ?? 'Tenant',
                    'comment' => $comment['comment'] ?? '',
                    'created_at' => $createdAt,
                    'created_at_human' => $createdAt ? date('M j, Y g:i A', strtotime($createdAt)) : ''
                ];
            }, $comments)
        ]);
    }

    // Toggle announcement status (admin only)
    public function toggleStatus() {
        $this->requireAdmin();

        $isAjax = $this->isAjaxRequest();

        if ($this->io->method() === 'post') {
            $id = (int) $this->io->post('id');
            $statusRaw = (string) $this->io->post('status');
            $status = in_array(strtolower($statusRaw), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;

            if ($id > 0 && $this->updateAnnouncementStatus($id, $status)) {
                $message = 'Announcement status updated successfully!';
                $this->session->set_flashdata('success', $message);

                if ($isAjax) {
                    $this->respondJson([
                        'status' => 'success',
                        'message' => $message
                    ]);
                }
            } else {
                $message = 'Failed to update announcement status.';
                $this->session->set_flashdata('error', $message);

                if ($isAjax) {
                    $this->respondJson([
                        'status' => 'error',
                        'message' => $message
                    ], 500);
                }
            }
        } else if ($isAjax) {
            $this->respondJson([
                'status' => 'error',
                'message' => 'Invalid request method.'
            ], 405);
        }

        redirect(site_url('admin/announcements'));
    }

    public function delete() {
        $this->requireAdmin();

        $isAjax = $this->isAjaxRequest();

        if ($this->io->method() !== 'post') {
            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => 'Invalid request method.'
                ], 405);
            }

            redirect(site_url('admin/announcements'));
            return;
        }

        $id = (int) $this->io->post('id');
        if ($id <= 0) {
            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => 'Invalid announcement identifier.'
                ], 422);
            } else {
                $this->session->set_flashdata('error', 'Invalid announcement identifier.');
            }
            redirect(site_url('admin/announcements'));
            return;
        }

        if ($this->deleteAnnouncementById($id)) {
            $message = 'Announcement deleted successfully.';
            $this->session->set_flashdata('success', $message);

            if ($isAjax) {
                $this->respondJson([
                    'status' => 'success',
                    'message' => $message
                ]);
            }
        } else {
            $message = 'Failed to delete announcement.';
            $this->session->set_flashdata('error', $message);

            if ($isAjax) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => $message
                ], 500);
            }
        }

        redirect(site_url('admin/announcements'));
    }

    // Helper methods
    private function getActiveAnnouncements(): array {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "SELECT a.*, CONCAT(s.fname, ' ', s.lname) AS creator_name,
                        (SELECT COUNT(*) FROM announcement_comments ac WHERE ac.announcement_id = a.id) AS comment_count
                 FROM announcements a
                 LEFT JOIN students s ON a.created_by = s.id
                 WHERE a.is_active = 1
                   AND (a.expires_at IS NULL OR a.expires_at > NOW())
                 ORDER BY a.priority DESC, a.created_at DESC"
            );
            $stmt->execute();
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($announcements as &$announcement) {
                $announcement['created_by_name'] = $announcement['creator_name'] ?? 'Admin';
                $announcement['comments'] = $this->getAnnouncementComments((int) ($announcement['id'] ?? 0));
                $announcement['comment_count'] = isset($announcement['comment_count'])
                    ? (int) $announcement['comment_count']
                    : count($announcement['comments']);
            }

            return $announcements;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getAllAnnouncements(): array {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "SELECT a.*, CONCAT(s.fname, ' ', s.lname) AS creator_name,
                        (SELECT COUNT(*) FROM announcement_comments ac WHERE ac.announcement_id = a.id) AS comment_count
                 FROM announcements a
                 LEFT JOIN students s ON a.created_by = s.id
                 ORDER BY a.created_at DESC"
            );
            $stmt->execute();
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($announcements as &$announcement) {
                $announcement['created_by_name'] = $announcement['creator_name'] ?? 'Admin';
                $announcement['comment_count'] = (int) ($announcement['comment_count'] ?? 0);
                $announcement['comments'] = $this->getAnnouncementComments((int) ($announcement['id'] ?? 0));
            }

            return $announcements;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getAnnouncement(int $id) {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "SELECT a.*, CONCAT(s.fname, ' ', s.lname) AS creator_name,
                        (SELECT COUNT(*) FROM announcement_comments ac WHERE ac.announcement_id = a.id) AS comment_count
                 FROM announcements a
                 LEFT JOIN students s ON a.created_by = s.id
                 WHERE a.id = ?
                   AND a.is_active = 1
                   AND (a.expires_at IS NULL OR a.expires_at > NOW())"
            );
            $stmt->execute([$id]);
            $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$announcement) {
                return false;
            }

            $announcement['created_by_name'] = $announcement['creator_name'] ?? 'Admin';
            $announcement['comments'] = $this->getAnnouncementComments($id);
            $announcement['comment_count'] = (int) ($announcement['comment_count'] ?? count($announcement['comments']));

            return $announcement;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getAnnouncementById(int $id) {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "SELECT a.*, CONCAT(s.fname, ' ', s.lname) AS creator_name
                 FROM announcements a
                 LEFT JOIN students s ON a.created_by = s.id
                 WHERE a.id = ?"
            );
            $stmt->execute([$id]);
            $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($announcement) {
                $announcement['created_by_name'] = $announcement['creator_name'] ?? 'Admin';
            }

            return $announcement ?: false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getAnnouncementComments(int $announcementId): array {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "SELECT ac.*, CONCAT(s.fname, ' ', s.lname) AS user_name
                 FROM announcement_comments ac
                 JOIN students s ON ac.user_id = s.id
                 WHERE ac.announcement_id = ?
                 ORDER BY ac.created_at ASC"
            );
            $stmt->execute([$announcementId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($comments as &$comment) {
                $comment['user_name'] = $comment['user_name'] ?? 'Tenant';
            }

            return $comments;
        } catch (Exception $e) {
            return [];
        }
    }

    private function insertAnnouncement(array $data): bool {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "INSERT INTO announcements (title, content, priority, expires_at, created_by, is_active)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            return $stmt->execute([
                $data['title'],
                $data['content'],
                $data['priority'],
                $data['expires_at'],
                $data['created_by'],
                $data['is_active']
            ]);
        } catch (Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
            return false;
        }
    }

    private function insertComment(int $announcementId, int $userId, string $comment): bool {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "INSERT INTO announcement_comments (announcement_id, user_id, comment)
                 VALUES (?, ?, ?)"
            );

            return $stmt->execute([$announcementId, $userId, $comment]);
        } catch (Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
            return false;
        }
    }

    private function updateAnnouncementStatus(int $id, int $status): bool {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare("UPDATE announcements SET is_active = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
            return false;
        }
    }

    private function updateAnnouncement(int $id, array $data): bool {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "UPDATE announcements SET title = ?, content = ?, priority = ?, expires_at = ? WHERE id = ?"
            );

            return $stmt->execute([
                $data['title'],
                $data['content'],
                $data['priority'],
                $data['expires_at'],
                $id
            ]);
        } catch (Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
            return false;
        }
    }

    private function deleteAnnouncementById(int $id): bool {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
            return false;
        }
    }

    private function ensureAnnouncementTables(): void {
        if ($this->tablesVerified) {
            return;
        }

        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS announcements (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
                    is_active TINYINT(1) DEFAULT 1,
                    created_by INT NOT NULL,
                    expires_at DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_is_active (is_active),
                    INDEX idx_priority (priority),
                    INDEX idx_created_by (created_by),
                    INDEX idx_expires_at (expires_at),
                    CONSTRAINT fk_announcements_created_by FOREIGN KEY (created_by) REFERENCES students(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $this->alignAnnouncementPriorityColumn($pdo);

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS announcement_comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    announcement_id INT NOT NULL,
                    user_id INT NOT NULL,
                    comment TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_announcement_id (announcement_id),
                    INDEX idx_user_id (user_id),
                    CONSTRAINT fk_comments_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
                    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $this->tablesVerified = true;
        } catch (Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
        }
    }

    private function alignAnnouncementPriorityColumn(PDO $pdo): void {
        try {
            $columnStmt = $pdo->query("SHOW COLUMNS FROM announcements LIKE 'priority'");
            $column = $columnStmt ? $columnStmt->fetch(PDO::FETCH_ASSOC) : null;

            if (!$column) {
                return;
            }

            $type = strtolower((string) ($column['Type'] ?? ''));
            $expectedEnum = "enum('low','medium','high','urgent')";

            if (strpos($type, "enum(") === false) {
                $pdo->exec("ALTER TABLE announcements MODIFY priority VARCHAR(10) NOT NULL DEFAULT 'medium'");
                $type = 'varchar(10)';
            }

            $pdo->exec(
                "UPDATE announcements SET priority = CASE LOWER(priority)
                    WHEN 'low' THEN 'low'
                    WHEN 'l' THEN 'low'
                    WHEN '1' THEN 'low'
                    WHEN 'medium' THEN 'medium'
                    WHEN 'med' THEN 'medium'
                    WHEN 'm' THEN 'medium'
                    WHEN 'mid' THEN 'medium'
                    WHEN '2' THEN 'medium'
                    WHEN 'high' THEN 'high'
                    WHEN 'h' THEN 'high'
                    WHEN '3' THEN 'high'
                    WHEN 'urgent' THEN 'urgent'
                    WHEN 'u' THEN 'urgent'
                    WHEN '4' THEN 'urgent'
                    ELSE 'medium'
                END"
            );

            if ($type !== $expectedEnum) {
                $pdo->exec("ALTER TABLE announcements MODIFY priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium'");
            }
        } catch (Exception $e) {
            if ($this->lastErrorMessage === '') {
                $this->lastErrorMessage = $e->getMessage();
            }
        }
    }

    private function isAjaxRequest(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function respondJson(array $payload, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    private function requireStudent(): void {
        if (!isset($_SESSION['user'])) {
            if ($this->isAjaxRequest()) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => 'Authentication required. Please log in as a tenant.'
                ], 401);
            }

            redirect(site_url('auth/login'));
            exit;
        }
    }

    private function requireAdmin(): void {
        if (!isset($_SESSION['admin'])) {
            if ($this->isAjaxRequest()) {
                $this->respondJson([
                    'status' => 'error',
                    'message' => 'Authentication required. Please log in as an administrator.'
                ], 401);
            }

            redirect(site_url('auth/login'));
            exit;
        }
    }

    private function resolveCreatorId(): ?int {
        if (isset($_SESSION['user']) && is_numeric($_SESSION['user'])) {
            return (int) $_SESSION['user'];
        }

        if (!isset($_SESSION['admin']) || $_SESSION['admin'] === '') {
            return null;
        }

        $adminEmail = (string) $_SESSION['admin'];
        $existingId = $this->getStudentIdByEmail($adminEmail);
        if ($existingId !== null) {
            return $existingId;
        }

        $adminName = (string) ($_SESSION['admin_name'] ?? 'Administrator');
        return $this->createShadowStudentForAdmin($adminEmail, $adminName);
    }

    private function getStudentIdByEmail(string $email): ?int {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['id'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function createShadowStudentForAdmin(string $email, string $displayName): ?int {
        try {
            $pdo = DatabaseConfig::getInstance()->getConnection();

            $nameParts = preg_split('/\s+/', trim($displayName)) ?: [];
            $fname = $nameParts[0] ?? 'Admin';
            $lname = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : 'Account';

            $passwordSeed = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($passwordSeed, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO students (fname, lname, email, password, verification_pin, pin_expires, email_verified)
                 VALUES (?, ?, ?, ?, NULL, NULL, 1)"
            );
            $stmt->execute([$fname, $lname, $email, $hashedPassword]);

            return (int) $pdo->lastInsertId();
        } catch (Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
            return null;
        }
    }

    private function normalizePriority(string $priority): string {
        $allowed = ['low', 'medium', 'high', 'urgent'];
        $normalized = strtolower(trim($priority));
        return in_array($normalized, $allowed, true) ? $normalized : 'medium';
    }
}