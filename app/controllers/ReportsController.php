<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';
require_once __DIR__ . '/NotificationController.php';

class ReportsController extends Controller
{
    protected $db;
    protected $notifier;

    public function __construct() {
        parent::__construct();
        $this->checkAdminSession();
        // Initialize database connection
        $dbConfig = DatabaseConfig::getInstance();
        $this->db = $dbConfig->getConnection();

        try {
            $this->notifier = new NotificationController();
        } catch (Exception $ignored) {
            $this->notifier = null;
        }
    }

    private function checkAdminSession() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }
    }

    public function index() {
        $data = ['success' => '', 'error' => ''];
        
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
            // Get tenant reports with payment and stay information
            $query = "
                SELECT 
                    s.id as student_id,
                    s.fname,
                    s.lname,
                    s.email,
                    r.id as reservation_id,
                    r.status as reservation_status,
                    r.reserved_at,
                    r.stay_start_date as start_date,
                    r.stay_end_date as end_date,
                    r.monthly_due_date,
                    r.last_payment_date,
                    rm.room_number,
                    rm.payment as room_price,
                    rm.beds as capacity,
                    COALESCE(DATEDIFF(CURDATE(), r.stay_start_date), 0) as days_stayed,
                    CASE 
                        WHEN r.monthly_due_date IS NULL THEN 'Setup Required'
                        WHEN r.monthly_due_date < CURDATE() THEN 'Overdue'
                        WHEN r.monthly_due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'Due Soon'
                        ELSE 'Active'
                    END as stay_status,
                    CASE 
                        WHEN r.stay_start_date IS NULL THEN 'Not Started'
                        WHEN r.monthly_due_date IS NULL THEN 'Monthly'
                        ELSE CONCAT(
                            DATEDIFF(r.monthly_due_date, r.stay_start_date), 
                            '-day billing period'
                        )
                    END as billing_period,
                    rm.payment as total_amount_due
                FROM reservations r
                JOIN students s ON r.user_id = s.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE r.status IN ('approved', 'confirmed')
                ORDER BY 
                    CASE 
                        WHEN r.monthly_due_date IS NULL THEN 1
                        WHEN r.monthly_due_date < CURDATE() THEN 2
                        WHEN r.monthly_due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 3
                        ELSE 4
                    END,
                    r.reserved_at DESC
            ";
            
            $stmt = $this->db->query($query);
            $data['tenantReports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $summary = [
                'total_active_tenants' => 0,
                'overdue_tenants' => 0,
                'due_soon_tenants' => 0,
                'total_monthly_revenue' => 0,
                'average_room_price' => 0
            ];

            if (!empty($data['tenantReports'])) {
                $summary['total_active_tenants'] = count($data['tenantReports']);

                $todayTs = strtotime(date('Y-m-d'));
                $dueSoonThreshold = strtotime('+3 days', $todayTs);

                $totalRoomPrice = 0;
                $roomCount = 0;

                foreach ($data['tenantReports'] as $report) {
                    $roomPrice = isset($report['room_price']) ? (float) $report['room_price'] : 0.0;
                    if ($roomPrice > 0) {
                        $totalRoomPrice += $roomPrice;
                        $roomCount++;
                    }

                    if (!empty($report['monthly_due_date'])) {
                        $dueTimestamp = strtotime($report['monthly_due_date']);
                        if ($dueTimestamp !== false) {
                            if ($dueTimestamp < $todayTs) {
                                $summary['overdue_tenants']++;
                            } elseif ($dueTimestamp <= $dueSoonThreshold) {
                                $summary['due_soon_tenants']++;
                            }
                        }
                    }
                }

                if ($roomCount > 0) {
                    $summary['average_room_price'] = $totalRoomPrice / $roomCount;
                }
            }

            try {
                $revenueStmt = $this->db->query("
                                        SELECT COALESCE(SUM(amount), 0) AS total
                                        FROM payment_history
                                        WHERE payment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                                            AND payment_date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                                            AND status = 'approved'
                ");

                $summary['total_monthly_revenue'] = (float) $revenueStmt->fetchColumn();
            } catch (Exception $revenueException) {
                // Keep revenue at zero if the query fails (e.g., table missing)
            }

            $data['summary'] = $summary;

        } catch (Exception $e) {
            $data['error'] = 'Error loading reports: ' . $e->getMessage();
            $data['tenantReports'] = [];
            $data['summary'] = [
                'total_active_tenants' => 0,
                'overdue_tenants' => 0,
                'due_soon_tenants' => 0,
                'total_monthly_revenue' => 0,
                'average_room_price' => 0
            ];
        }

        $this->call->view('admin/reports', $data);
    }

    public function updatePayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            header('Location: ' . site_url('admin/reports'));
            exit;
        }

        $student_id = $_POST['student_id'] ?? null;
        $amount_paid = $_POST['amount_paid'] ?? null;
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $payment_method = $_POST['payment_method'] ?? 'cash';

        if (!$student_id || !$amount_paid) {
            $_SESSION['error'] = 'Student ID and amount are required.';
            header('Location: ' . site_url('admin/reports'));
            exit;
        }

        try {
            // Get reservation ID from student ID
            $stmt = $this->db->prepare("SELECT id FROM reservations WHERE user_id = ? AND status IN ('approved', 'confirmed') ORDER BY id DESC LIMIT 1");
            $stmt->execute([$student_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) {
                $_SESSION['error'] = 'No active reservation found for this student.';
                header('Location: ' . site_url('admin/reports'));
                exit;
            }
            
            // Get student name for success message
            $studentStmt = $this->db->prepare("SELECT fname, lname FROM students WHERE id = ?");
            $studentStmt->execute([$student_id]);
            $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                $_SESSION['error'] = 'Student not found.';
                header('Location: ' . site_url('admin/reports'));
                exit;
            }
            
            // Use NotificationController to process payment request (pending approval)
            require_once __DIR__ . '/NotificationController.php';
            $notificationController = new NotificationController();
            
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : '';

            try {
                $notificationController->processPayment($reservation['id'], $amount_paid, $payment_date, $payment_method, $notes);
                $_SESSION['success'] = "Payment of ₱" . number_format($amount_paid, 2) . " recorded for {$student['fname']} {$student['lname']}. Awaiting approval.";
            } catch (Exception $processException) {
                $_SESSION['error'] = 'Failed to record payment: ' . $processException->getMessage();
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error processing payment: ' . $e->getMessage();
        }

        header('Location: ' . site_url('admin/reports'));
        exit;
    }

    public function updateStayDates() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            header('Location: ' . site_url('admin/reports'));
            exit;
        }

        $reservation_id = $_POST['reservation_id'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;

        if (!$reservation_id || !$start_date) {
            $_SESSION['error'] = 'Reservation ID and start date are required.';
            header('Location: ' . site_url('admin/reports'));
            exit;
        }

        try {
            // Add columns if they don't exist (using raw SQL for ALTER TABLE)
            try {
                $this->db->exec("ALTER TABLE reservations ADD COLUMN start_date DATE NULL");
            } catch (Exception $e) {
                // Column already exists, ignore error
            }
            try {
                $this->db->exec("ALTER TABLE reservations ADD COLUMN end_date DATE NULL");
            } catch (Exception $e) {
                // Column already exists, ignore error
            }
            try {
                $this->db->exec("ALTER TABLE reservations ADD COLUMN approved_at TIMESTAMP NULL");
            } catch (Exception $e) {
                // Column already exists, ignore error
            }

            // Update reservation dates
            $updateStmt = $this->db->prepare("
                UPDATE reservations 
                SET start_date = ?, end_date = ?, approved_at = COALESCE(approved_at, NOW())
                WHERE id = ?
            ");
            $updateStmt->execute([$start_date, $end_date, $reservation_id]);

            $_SESSION['success'] = "Stay dates updated successfully";

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error updating stay dates: ' . $e->getMessage();
        }

        header('Location: ' . site_url('admin/reports'));
        exit;
    }
}
?>