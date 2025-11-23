<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class UserLandingController extends Controller {

    private function getDbConnection() {
        $dbConfig = DatabaseConfig::getInstance();
        return $dbConfig->getConnection();
    }

    private function parseAmount($value): float {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^0-9.]/', '', $value);
            if ($normalized === '' || $normalized === '.') {
                return 0.0;
            }

            $parts = explode('.', $normalized);
            if (count($parts) > 2) {
                $normalized = array_shift($parts) . '.' . implode('', $parts);
            }

            return (float) $normalized;
        }

        return 0.0;
    }

    public function index() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        if(!isset($_SESSION['user'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        $data = [
            'rooms' => [],
            'success' => '',
            'error' => '',
            'userName' => $_SESSION['user_name'] ?? 'User',
            'totalRoomsCount' => 0,
            'availableRoomsCount' => 0,
            'availableSpacesCount' => 0,
            'paymentAlert' => [
                'show' => false,
                'due_amount' => 0.0,
                'paid_amount' => 0.0,
                'pending_amount' => 0.0,
                'remaining_amount' => 0.0,
                'month_label' => date('F Y')
            ],
        ];

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
            $stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_number ASC");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $priceByName = [];
            $priceByBeds = [];
            $allPrices = [];

            foreach ($rooms as $roomSample) {
                $baseMonthlyRate = $this->parseAmount($roomSample['monthly_rate'] ?? null);
                $basePayment = $this->parseAmount($roomSample['payment'] ?? null);
                $basePrice = $baseMonthlyRate > 0 ? $baseMonthlyRate : $basePayment;

                if ($basePrice > 0) {
                    if (!empty($roomSample['room_name'])) {
                        $priceByName[$roomSample['room_name']][] = $basePrice;
                    }

                    if (isset($roomSample['beds'])) {
                        $bedsKey = (string) (int) $roomSample['beds'];
                        $priceByBeds[$bedsKey][] = $basePrice;
                    }

                    $allPrices[] = $basePrice;
                }
            }

            // Prepare fallback monthly payments from occupancy data for rooms without a stored price
            $priceFallbacks = [];
            $priceStmt = $pdo->query("SELECT room_id, MAX(monthly_payment) AS monthly_payment FROM room_occupancy WHERE monthly_payment IS NOT NULL AND monthly_payment > 0 GROUP BY room_id");
            if ($priceStmt) {
                foreach ($priceStmt->fetchAll(PDO::FETCH_ASSOC) as $priceRow) {
                    $roomId = isset($priceRow['room_id']) ? (int) $priceRow['room_id'] : 0;
                    if ($roomId > 0) {
                        $priceFallbacks[$roomId] = $this->parseAmount($priceRow['monthly_payment'] ?? 0);
                    }
                }
            }

            foreach ($rooms as $index => &$room) {
                $monthlyRateValue = $this->parseAmount($room['monthly_rate'] ?? null);
                $paymentValue = $this->parseAmount($room['payment'] ?? null);
                $displayPrice = 0.0;

                if ($monthlyRateValue > 0) {
                    $displayPrice = $monthlyRateValue;
                } elseif ($paymentValue > 0) {
                    $displayPrice = $paymentValue;
                } elseif (!empty($room['id'])) {
                    $roomId = (int) $room['id'];
                    if (isset($priceFallbacks[$roomId]) && $priceFallbacks[$roomId] > 0) {
                        $displayPrice = $priceFallbacks[$roomId];
                    }
                }

                if ($displayPrice <= 0 && !empty($room['room_name']) && !empty($priceByName[$room['room_name']] ?? [])) {
                    $series = $priceByName[$room['room_name']];
                    sort($series, SORT_NUMERIC);
                    $displayPrice = $series[0] ?? 0.0;
                }

                if ($displayPrice <= 0 && isset($room['beds'])) {
                    $bedsKey = (string) (int) $room['beds'];
                    if (!empty($priceByBeds[$bedsKey] ?? [])) {
                        $series = $priceByBeds[$bedsKey];
                        sort($series, SORT_NUMERIC);
                        $displayPrice = $series[0] ?? 0.0;
                    }
                }

                if ($displayPrice <= 0 && !empty($allPrices)) {
                    $displayPrice = min($allPrices);
                }

                $room['display_price'] = $displayPrice;

                $roomNumberRaw = trim((string)($room['room_number'] ?? ''));
                $roomNameRaw = trim((string)($room['room_name'] ?? ''));
                $resolvedNumber = '';

                $candidates = [$roomNumberRaw, $roomNameRaw];
                foreach ($candidates as $candidate) {
                    if ($candidate === '') {
                        continue;
                    }
                    if (preg_match('/(\d+)/', $candidate, $matches)) {
                        $resolvedNumber = ltrim($matches[1], "0");
                        if ($resolvedNumber === '') {
                            $resolvedNumber = '0';
                        }
                        break;
                    }
                }

                if ($resolvedNumber === '') {
                    $resolvedNumber = (string) ($index + 1);
                }

                $displayName = $roomNameRaw !== '' ? $roomNameRaw : ($roomNumberRaw !== '' ? $roomNumberRaw : 'Room ' . $resolvedNumber);
                $normalizedName = trim($displayName);
                if ($normalizedName === '' || strcasecmp($normalizedName, 'Room') === 0) {
                    $normalizedName = 'Room ' . $resolvedNumber;
                }

                $room['display_number'] = $resolvedNumber;
                $room['display_name'] = $normalizedName;
            }
            unset($room);

            $data['rooms'] = $rooms;

            $data['totalRoomsCount'] = count($rooms);
            $data['availableRoomsCount'] = 0;
            $data['availableSpacesCount'] = 0;

            foreach ($rooms as $room) {
                $availableSlots = isset($room['available']) ? (int) $room['available'] : 0;
                if ($availableSlots > 0) {
                    $data['availableRoomsCount']++;
                    $data['availableSpacesCount'] += $availableSlots;
                }
            }

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
                    rm.payment AS room_payment,
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

            if (!empty($data['currentReservation'])) {
                $reservationId = isset($data['currentReservation']['id']) ? (int) $data['currentReservation']['id'] : 0;
                $monthlyDue = $this->parseAmount($data['currentReservation']['monthly_rate'] ?? null);
                if ($monthlyDue <= 0) {
                    $monthlyDue = $this->parseAmount($data['currentReservation']['room_payment'] ?? null);
                }

                if ($reservationId > 0 && $monthlyDue > 0) {
                    $monthKey = date('Y-m');

                    $approvedStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payment_history WHERE reservation_id = ? AND status = 'approved' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
                    $approvedStmt->execute([$reservationId, $monthKey]);
                    $approvedTotal = (float) $approvedStmt->fetchColumn();

                    $pendingStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payment_history WHERE reservation_id = ? AND status = 'pending' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
                    $pendingStmt->execute([$reservationId, $monthKey]);
                    $pendingTotal = (float) $pendingStmt->fetchColumn();

                    $remaining = $monthlyDue - $approvedTotal;
                    if ($remaining < 0) {
                        $remaining = 0.0;
                    }

                    $shouldAlert = $remaining > 0.01;

                    $data['paymentAlert'] = [
                        'show' => $shouldAlert,
                        'due_amount' => $monthlyDue,
                        'paid_amount' => $approvedTotal,
                        'pending_amount' => $pendingTotal,
                        'remaining_amount' => $remaining,
                        'month_label' => date('F Y')
                    ];
                }
            }

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
                WHERE r.user_id = ? AND ph.status = 'approved'
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
        $this->logReservationDebug('reserveRoom start method=' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' rawId=' . var_export($id, true) . ' uri=' . ($_SERVER['REQUEST_URI'] ?? '') . ' ajax=' . ($isAjax ? 'yes' : 'no'));

        // If no ID passed, try to get from POST payload first then URL
        if ($id === null && isset($_POST['room_id'])) {
            $id = (int) $_POST['room_id'];
            if ($id > 0) {
                $this->logReservationDebug('reserveRoom derived id from post=' . $id);
            } else {
                $id = null; // reset so URI fallback can run
            }
        }

        // If still no ID, try to get from URL
        if ($id === null) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('/\/user\/reserve\/(\d+)/', $uri, $matches)) {
                $id = $matches[1];
                $this->logReservationDebug('reserveRoom derived id from uri=' . $id);
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
            $this->logReservationDebug('reserveRoom user=' . ($_SESSION['user'] ?? 'guest') . ' roomId=' . $id . ' quantity=' . $quantity);

            // Get the room
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$room) {
                $this->logReservationDebug('reserveRoom room not found id=' . $id);
            }

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
                        $insert = $pdo->prepare("INSERT INTO reservations (user_id, room_id, status) VALUES (?, ?, 'pending')");
                        
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
                            $this->logReservationDebug('reserveRoom success user=' . ($_SESSION['user'] ?? 'guest') . ' room=' . $id . ' qty=' . $quantity);
                        } else {
                            $pdo->rollback();
                            $message = "Failed to submit all reservation requests.";
                            $success = false;
                            $this->logReservationDebug('reserveRoom partial failure user=' . ($_SESSION['user'] ?? 'guest') . ' room=' . $id);
                        }
                        
                    } catch(PDOException $e) {
                        $pdo->rollback();
                        $this->logReservationDebug('reserveRoom insert exception: ' . $e->getMessage());
                        throw $e;
                    }
                }
            }

        } catch(PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $success = false;
            $this->logReservationDebug('reserveRoom exception: ' . $e->getMessage());
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
            $this->logReservationDebug('reserveRoom redirect success=' . ($success ? 'yes' : 'no'));
            header('Location: ' . site_url('user_landing'));
            exit;
        }
    }

    private function logReservationDebug(string $message): void {
        try {
            $rootPath = dirname(__DIR__, 2);
            $logDir = $rootPath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($logDir)) {
                return;
            }

            $logFile = $logDir . DIRECTORY_SEPARATOR . 'reservations-debug.log';
            $timestamp = date('Y-m-d H:i:s');
            $formatted = "[{$timestamp}] {$message}\n";
            file_put_contents($logFile, $formatted, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Suppress logging failures
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

        $data = ['success' => '', 'error' => '', 'messages' => []];

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
            $this->ensureMessagesTable($pdo);

            $stmt = $pdo->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY id DESC");
            $stmt->execute([$_SESSION['user']]);
            $data['messages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            if (empty($data['error'])) {
                $data['error'] = "We couldn't load your messages right now. Please try again later.";
            }
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
            $this->ensureMessagesTable($pdo);

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

    private function ensureMessagesTable(PDO $pdo) {
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
            } catch (PDOException $e) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $ignore) {
                    // Column may already exist or cannot be added; silently continue.
                }
            }
        }

        try {
            $statusDefinition = $pdo->query("SHOW COLUMNS FROM messages LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
            if ($statusDefinition) {
                $currentType = strtolower($statusDefinition['Type'] ?? '');

                $desiredStatuses = ['unread', 'read', 'replied'];
                $existingStatuses = [];
                try {
                    $existingStatuses = $pdo->query("SELECT DISTINCT status FROM messages WHERE status IS NOT NULL")
                        ->fetchAll(PDO::FETCH_COLUMN) ?: [];
                } catch (PDOException $ignore) {
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
        } catch (PDOException $e) {
            // Log for debugging but do not interrupt user flow.
            error_log('ensureMessagesTable (user) status alter failed: ' . $e->getMessage());
        }
    }
}
