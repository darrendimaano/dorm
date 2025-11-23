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

        $data = [
            'messages' => [],
            'success' => '',
            'error' => '',
            'maintenanceRequests' => []
        ];

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
            $this->ensureMessagesTable($pdo);
            
            $stmt = $pdo->query("SELECT m.*, s.fname, s.lname, s.email 
                               FROM messages m 
                               JOIN students s ON m.user_id = s.id 
                               ORDER BY m.id DESC");
            $data['messages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $maintenanceStmt = $pdo->query("SELECT mr.*, s.fname, s.lname, s.email, r.room_number 
                                             FROM maintenance_requests mr 
                                             LEFT JOIN students s ON mr.user_id = s.id 
                                             LEFT JOIN rooms r ON mr.room_id = r.id 
                                             ORDER BY mr.created_at DESC");
            $data['maintenanceRequests'] = $maintenanceStmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $data['error'] = "Error loading messages: " . $e->getMessage();
            $data['maintenanceRequests'] = [];
        }

        $this->call->view('admin/messages', $data);
    }

    public function replyMessage($id = null) {
        if(session_status() === PHP_SESSION_NONE) session_start();

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $this->logMessageDebug('replyMessage start id=' . ($id ?? 'NULL') . ' ajax=' . ($isAjax ? 'yes' : 'no') . ' method=' . ($_SERVER['REQUEST_METHOD'] ?? ''));

        if(!isset($_SESSION['admin'])) {
            if ($isAjax) {
                $this->jsonResponse(['error' => 'Unauthorized access.'], 403);
            }
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                $this->jsonResponse(['error' => 'Invalid request method.'], 405);
            }
            header('Location: ' . site_url('admin/messages'));
            exit;
        }

        $messageId = $id;
        if ($messageId === null) {
            $messageId = $_POST['message_id'] ?? $_POST['id'] ?? null;
        }

        if ($messageId === null || !ctype_digit((string) $messageId)) {
            $this->logMessageDebug('replyMessage missing identifier routeId=' . ($id ?? 'NULL') . ' postId=' . ($_POST['message_id'] ?? $_POST['id'] ?? 'NULL'));
            if ($isAjax) {
                $this->jsonResponse(['error' => 'Message identifier is required.'], 422);
            }
            $_SESSION['error'] = "Message identifier is required.";
            header('Location: ' . site_url('admin/messages'));
            exit;
        }

        $messageId = (int) $messageId;

        $reply = trim($_POST['reply'] ?? '');

        if ($reply === '') {
            $this->logMessageDebug('replyMessage empty reply id=' . $messageId);
        }

        if (empty($reply)) {
            if ($isAjax) {
                $this->jsonResponse(['error' => 'Reply message cannot be empty.'], 422);
            }
            $_SESSION['error'] = "Reply message cannot be empty.";
            header('Location: ' . site_url('admin/messages'));
            exit;
        }

        try {
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $this->ensureMessagesTable($pdo);
            
            $messageStmt = $pdo->prepare("SELECT id, status FROM messages WHERE id = ? LIMIT 1");
            if (!$messageStmt->execute([$messageId])) {
                $this->logMessageDebug('replyMessage fetch failed: ' . implode(' | ', $messageStmt->errorInfo()));
            }
            $existingMessage = $messageStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingMessage) {
                $this->logMessageDebug('replyMessage missing message id=' . $messageId);
                if ($isAjax) {
                    $this->jsonResponse(['error' => 'Message not found.'], 404);
                }
                $_SESSION['error'] = "Message not found.";
            } else {
                $this->logMessageDebug('replyMessage updating id=' . $messageId . ' currentStatus=' . ($existingMessage['status'] ?? 'unknown'));
                $stmt = $pdo->prepare("UPDATE messages SET admin_reply = ?, status = 'replied' WHERE id = ?");
                if (!$stmt->execute([$reply, $messageId])) {
                    $errorInfo = $stmt->errorInfo();
                    $this->logMessageDebug('replyMessage update failed: ' . implode(' | ', $errorInfo));
                    if ($isAjax) {
                        $this->jsonResponse([
                            'error' => 'Unable to send reply right now.',
                            'errorDetails' => $errorInfo[2] ?? null
                        ], 500);
                    }
                    $_SESSION['error'] = "Unable to send reply right now.";
                } else {
                    $this->logMessageDebug('replyMessage update success id=' . $messageId);
                    if ($isAjax) {
                        $unreadCountStmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'");
                        $unreadCount = (int) $unreadCountStmt->fetchColumn();

                        $this->jsonResponse([
                            'success' => true,
                            'message' => 'Message sent!',
                            'status' => 'replied',
                            'admin_reply' => $reply,
                            'unreadCount' => $unreadCount
                        ]);
                    }

                    $_SESSION['success'] = "Message sent!";
                }
            }
            
        } catch (Exception $e) {
            $this->logMessageDebug('replyMessage error: ' . $e->getMessage());
            if ($isAjax) {
                            $this->jsonResponse([
                                'error' => 'Error sending reply.',
                                'errorDetails' => $e->getMessage()
                            ], 500);
            }
            $_SESSION['error'] = "Error sending reply.";
        }

        header('Location: ' . site_url('admin/messages'));
        exit;
    }

    public function markMessageAsRead($id = null) {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['admin'])) {
            $this->jsonResponse(['error' => 'Unauthorized access.'], 403);
        }

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method.'], 405);
        }

        $messageId = $id;
        if ($messageId === null) {
            $messageId = $_POST['message_id'] ?? $_POST['id'] ?? null;
        }

        if ($messageId === null || !is_numeric($messageId)) {
            $this->jsonResponse(['error' => 'Message identifier missing.'], 400);
        }

        $messageId = (int) $messageId;

        try {
            $this->logMessageDebug('markMessageAsRead start id=' . $messageId . ' method=' . ($_SERVER['REQUEST_METHOD'] ?? '')); 
            $dbConfig = DatabaseConfig::getInstance();
            $pdo = $dbConfig->getConnection();
            $this->ensureMessagesTable($pdo);

            $stmt = $pdo->prepare("SELECT status FROM messages WHERE id = ?");
            if (!$stmt->execute([$messageId])) {
                $this->logMessageDebug('markMessageAsRead fetch failed: ' . implode(' | ', $stmt->errorInfo()));
            }
            $message = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$message) {
                $this->logMessageDebug('markMessageAsRead missing message id=' . $messageId);
                $this->jsonResponse(['error' => 'Message not found.'], 404);
            }

            $storedStatus = is_string($message['status']) ? $message['status'] : '';
            $normalizedStatus = strtolower($storedStatus);
            $newStatus = $storedStatus;

            $unreadAliases = ['unread', 'pending', 'new'];

            if (in_array($normalizedStatus, $unreadAliases, true)) {
                $update = $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
                if (!$update->execute([$messageId])) {
                    $errorInfo = $update->errorInfo();
                    $this->logMessageDebug('markMessageAsRead update failed: ' . implode(' | ', $errorInfo));
                    $this->jsonResponse([
                        'error' => 'Unable to update message status.',
                        'errorDetails' => $errorInfo[2] ?? 'Unknown database error'
                    ], 500);
                }
                $newStatus = 'read';
            }

            $unreadCountStmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'");
            $unreadCount = (int) $unreadCountStmt->fetchColumn();

            $this->logMessageDebug('markMessageAsRead success id=' . $messageId . ' status=' . $newStatus . ' unread=' . $unreadCount);
            $this->jsonResponse([
                'success' => true,
                'status' => $newStatus,
                'unreadCount' => $unreadCount
            ]);

        } catch (Exception $e) {
            $this->logMessageDebug('markMessageAsRead error: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Unable to update message status.'], 500);
        }
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

    private function jsonResponse(array $payload, int $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    private function ensureMessagesTable(
        \PDO $pdo
    ) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            admin_reply TEXT DEFAULT NULL,
            status ENUM('unread','read','replied') DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_messages_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $columnsToEnsure = [
            'admin_reply' => "ALTER TABLE messages ADD COLUMN admin_reply TEXT DEFAULT NULL",
            'status' => "ALTER TABLE messages ADD COLUMN status ENUM('unread','read','replied') DEFAULT 'unread'",
            'created_at' => "ALTER TABLE messages ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE messages ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];

        foreach ($columnsToEnsure as $column => $statement) {
            try {
                $pdo->query("SELECT {$column} FROM messages LIMIT 1");
            } catch (\PDOException $e) {
                try {
                    $pdo->exec($statement);
                } catch (\PDOException $ignore) {
                    // Column may already exist or cannot be added; continue silently.
                }
            }
        }

        try {
            $statusDefinition = $pdo->query("SHOW COLUMNS FROM messages LIKE 'status'")->fetch(\PDO::FETCH_ASSOC);
            if ($statusDefinition) {
                $currentType = strtolower($statusDefinition['Type'] ?? '');

                $desiredStatuses = ['unread', 'read', 'replied'];
                $existingStatuses = [];
                try {
                    $existingStatuses = $pdo->query("SELECT DISTINCT status FROM messages WHERE status IS NOT NULL")
                        ->fetchAll(\PDO::FETCH_COLUMN) ?: [];
                } catch (\PDOException $ignore) {
                    $existingStatuses = [];
                }

                $allStatuses = $desiredStatuses;
                foreach ($existingStatuses as $value) {
                    if (!in_array($value, $allStatuses, true)) {
                        $allStatuses[] = $value;
                    }
                }

                $targetEnum = 'enum(' . implode(',', array_map(function ($value) {
                    return "'" . str_replace("'", "''", $value) . "'";
                }, $allStatuses)) . ')';

                if ($currentType !== $targetEnum) {
                    $pdo->exec("ALTER TABLE messages MODIFY COLUMN status {$targetEnum} DEFAULT 'unread'");
                }
            }
        } catch (\PDOException $e) {
            $this->logMessageDebug('ensureMessagesTable status alter failed: ' . $e->getMessage());
        }
    }

    private function logMessageDebug(string $message): void {
        try {
            $rootPath = dirname(dirname(__DIR__));
            $logDir = $rootPath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($logDir)) {
                return;
            }

            $logFile = $logDir . DIRECTORY_SEPARATOR . 'messages-debug.log';
            $timestamp = date('Y-m-d H:i:s');
            $formatted = "[{$timestamp}] {$message}\n";
            file_put_contents($logFile, $formatted, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Best-effort logging; swallow any IO issues.
        }
    }
}
