<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class UserPaymentController extends Controller {

    private $pdo;

    public function __construct() {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            redirect('auth/login');
            exit;
        }

        $config = DatabaseConfig::getInstance();
        $this->pdo = $config->getConnection();

        $this->ensurePaymentHistorySchema();
    }

    public function index() {
        $userId = (int) $_SESSION['user'];
        $data = [
            'payment_history' => [],
            'current_reservation' => null,
            'payment_summary' => [
                'total_payments' => 0,
                'total_paid' => 0.0,
                'last_payment' => null
            ]
        ];

        $historySql = "
            SELECT 
                ph.*,
                r.id AS reservation_id,
                r.room_id,
                r.status AS reservation_status,
                rm.room_name,
                rm.room_number,
                rm.monthly_rate,
                s.fname,
                s.lname,
                s.email
            FROM payment_history ph
            JOIN reservations r ON ph.reservation_id = r.id
            JOIN rooms rm ON r.room_id = rm.id
            JOIN students s ON r.user_id = s.id
            WHERE s.id = ? AND ph.status = 'approved'
            ORDER BY ph.payment_date DESC
        ";

        $stmt = $this->pdo->prepare($historySql);
        $stmt->execute([$userId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($payments)) {
            $data['payment_history'] = $payments;
        }

        $reservationSql = "
            SELECT 
                r.*,
                rm.room_name,
                rm.room_number,
                rm.monthly_rate
            FROM reservations r
            JOIN rooms rm ON r.room_id = rm.id
            WHERE r.user_id = ? AND r.status = 'approved'
            ORDER BY r.id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($reservationSql);
        $stmt->execute([$userId]);
        $currentReservation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($currentReservation) {
            $data['current_reservation'] = $currentReservation;
        }

        $summarySql = "
            SELECT 
                COUNT(*) AS total_payments,
                SUM(amount) AS total_paid,
                MAX(payment_date) AS last_payment
            FROM payment_history ph
            JOIN reservations r ON ph.reservation_id = r.id
            WHERE r.user_id = ? AND ph.status = 'approved'
        ";

        $stmt = $this->pdo->prepare($summarySql);
        $stmt->execute([$userId]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($summary) {
            $data['payment_summary'] = [
                'total_payments' => (int) ($summary['total_payments'] ?? 0),
                'total_paid' => (float) ($summary['total_paid'] ?? 0),
                'last_payment' => $summary['last_payment'] ?? null
            ];
        }

        $this->call->view('user/payments', $data);
    }

    private function findPaymentReceipt(int $paymentId, int $userId): ?array {
        $sql = "
            SELECT 
                ph.*,
                r.id AS reservation_id,
                r.room_id,
                r.stay_start_date,
                r.stay_end_date,
                r.monthly_due_date,
                r.last_payment_date,
                rm.room_name,
                rm.room_number,
                rm.monthly_rate,
                s.fname,
                s.lname,
                s.email,
                s.id AS student_number
            FROM payment_history ph
            JOIN reservations r ON ph.reservation_id = r.id
            JOIN rooms rm ON r.room_id = rm.id
            JOIN students s ON r.user_id = s.id
            WHERE ph.id = ? AND s.id = ? AND ph.status = 'approved'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paymentId, $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $payment ?: null;
    }

    private function prepareReceiptData(array $payment): array {
        if (empty($payment['stay_start_date']) && !empty($payment['payment_date'])) {
            $payment['stay_start_date'] = date('Y-m-01', strtotime($payment['payment_date']));
        }

        if (empty($payment['stay_end_date']) && !empty($payment['stay_start_date'])) {
            $payment['stay_end_date'] = date('Y-m-t', strtotime($payment['stay_start_date']));
        }

        if (!isset($payment['monthly_rate']) || $payment['monthly_rate'] === null) {
            $payment['monthly_rate'] = $payment['amount'];
        }

        $billingMonth = null;
        if (!empty($payment['stay_start_date'])) {
            $billingMonth = date('F Y', strtotime($payment['stay_start_date']));
        } elseif (!empty($payment['monthly_due_date'])) {
            $billingMonth = date('F Y', strtotime($payment['monthly_due_date']));
        } elseif (!empty($payment['payment_date'])) {
            $billingMonth = date('F Y', strtotime($payment['payment_date']));
        }

        $payment['billing_month'] = $billingMonth;

        $purposeLabel = !empty($payment['payment_for'])
            ? ucwords(str_replace('_', ' ', $payment['payment_for']))
            : 'Monthly Rent';
        $methodRaw = $payment['payment_method'] ?? 'Payment';
        $methodLabel = ucwords(str_replace('_', ' ', $methodRaw));
        $periodPhrase = $billingMonth ?? 'this billing period';

        $payment['method_summary'] = sprintf(
            'You paid for %s covering %s via %s.',
            $purposeLabel,
            $periodPhrase,
            $methodLabel
        );

        $methodDetails = 'Payment has been recorded successfully.';
        $methodKey = strtolower($methodRaw);
        switch ($methodKey) {
            case 'gcash':
                $methodDetails = 'GCash payment has been verified by the dormitory staff.';
                if (!empty($payment['transaction_reference'])) {
                    $methodDetails .= ' Reference: ' . $payment['transaction_reference'] . '.';
                }
                break;
            case 'bank_transfer':
                $methodDetails = 'Bank transfer has been credited to the dormitory account.';
                if (!empty($payment['transaction_reference'])) {
                    $methodDetails .= ' Transaction #: ' . $payment['transaction_reference'] . '.';
                }
                break;
            case 'cash':
                $methodDetails = 'Cash payment was received at the dormitory office.';
                break;
        }

        $payment['method_details'] = $methodDetails;

        return $payment;
    }

    public function receipt($paymentId) {
        $userId = (int) $_SESSION['user'];
        $payment = $this->findPaymentReceipt((int) $paymentId, $userId);

        if (!$payment) {
            show_404();
        }

        $payment = $this->prepareReceiptData($payment);

        $this->call->view('user/payment_receipt', ['payment' => $payment]);
    }

    public function receiptPage($paymentId) {
        $userId = (int) $_SESSION['user'];
        $payment = $this->findPaymentReceipt((int) $paymentId, $userId);

        if (!$payment) {
            show_404();
        }

        $payment = $this->prepareReceiptData($payment);

        $this->call->view('user/payment_receipt_page', ['payment' => $payment]);
    }

    public function submit() {
        $userId = (int) $_SESSION['user'];

        try {
            if (!isset($_POST['amount'], $_POST['payment_method'], $_POST['payment_for'])) {
                $_SESSION['error'] = 'Please fill in all required fields.';
                redirect('user/payments');
                return;
            }

            $amount = (float) $_POST['amount'];
            $paymentMethod = (string) $_POST['payment_method'];
            $paymentFor = (string) $_POST['payment_for'];
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : '';

            $referenceNumber = '';
            switch (strtolower($paymentMethod)) {
                case 'gcash':
                    $referenceNumber = $_POST['gcash_reference'] ?? '';
                    break;
                case 'bank_transfer':
                    $referenceNumber = $_POST['bank_reference'] ?? '';
                    break;
                case 'cash':
                    $referenceNumber = 'CASH-' . date('YmdHis') . '-' . $userId;
                    break;
            }

            $reservationSql = "
                SELECT r.id, r.room_id, rm.room_number, rm.room_name
                FROM reservations r
                JOIN rooms rm ON r.room_id = rm.id
                WHERE r.user_id = ? AND r.status = 'approved'
                ORDER BY r.id DESC
                LIMIT 1
            ";

            $stmt = $this->pdo->prepare($reservationSql);
            $stmt->execute([$userId]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                $_SESSION['error'] = 'No active reservation found.';
                redirect('user/payments');
                return;
            }

            $insertSql = "
                INSERT INTO payment_history (
                    reservation_id,
                    amount,
                    payment_method,
                    transaction_reference,
                    payment_for,
                    payment_date,
                    notes,
                    status,
                    approved_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'pending', NULL)
            ";

            $stmt = $this->pdo->prepare($insertSql);
            $stmt->execute([
                $reservation['id'],
                $amount,
                $paymentMethod,
                $referenceNumber,
                $paymentFor,
                $notes
            ]);

            switch (strtolower($paymentMethod)) {
                case 'gcash':
                    $_SESSION['success'] = 'GCash payment submitted successfully! Please wait for admin verification.';
                    break;
                case 'bank_transfer':
                    $_SESSION['success'] = 'Bank transfer payment submitted successfully! Please wait for admin verification.';
                    break;
                case 'cash':
                    $_SESSION['success'] = 'Cash payment request submitted! Please visit the office during business hours.';
                    break;
                default:
                    $_SESSION['success'] = 'Payment request submitted successfully!';
                    break;
            }

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error submitting payment: ' . $e->getMessage();
        }

        redirect('user/payments');
    }

    private function ensurePaymentHistorySchema(): void {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS payment_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reservation_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                transaction_reference VARCHAR(100) DEFAULT NULL,
                payment_for VARCHAR(100) DEFAULT NULL,
                payment_date DATETIME NOT NULL,
                notes TEXT DEFAULT NULL,
                status ENUM('pending','approved','rejected') DEFAULT 'pending',
                approved_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_payment_history_reservation (reservation_id),
                CONSTRAINT fk_payment_history_reservation FOREIGN KEY (reservation_id)
                    REFERENCES reservations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $columnsToEnsure = [
                'transaction_reference' => "ALTER TABLE payment_history ADD COLUMN transaction_reference VARCHAR(100) DEFAULT NULL AFTER payment_method",
                'payment_for' => "ALTER TABLE payment_history ADD COLUMN payment_for VARCHAR(100) DEFAULT NULL AFTER transaction_reference",
                'status' => "ALTER TABLE payment_history ADD COLUMN status ENUM('pending','approved','rejected') DEFAULT 'pending' AFTER notes",
                'approved_at' => "ALTER TABLE payment_history ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER status",
                'created_at' => "ALTER TABLE payment_history ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER approved_at"
            ];

            foreach ($columnsToEnsure as $column => $statement) {
                try {
                    $this->pdo->query("SELECT {$column} FROM payment_history LIMIT 1");
                } catch (\PDOException $e) {
                    try {
                        $this->pdo->exec($statement);
                    } catch (\PDOException $ignore) {
                        // Column may already exist or cannot be added; ignore.
                    }
                }
            }

            try {
                $this->pdo->exec("UPDATE payment_history SET status = 'approved' WHERE status IS NULL OR status = ''");
            } catch (Exception $ignored) {
                // Ignore failures during backfill
            }
        } catch (Exception $e) {
            // Schema ensure best-effort only.
        }
    }
}
