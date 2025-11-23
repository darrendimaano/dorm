<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class NotificationController extends Controller
{
    protected $db;

    public function __construct() {
        parent::__construct();
        $dbConfig = DatabaseConfig::getInstance();
        $this->db = $dbConfig->getConnection();

        $this->ensurePaymentHistorySchema();
        $this->ensureReservationBillingColumns();
    }

    // Auto-update stay dates when reservation is approved
    public function autoSetStayDates($reservation_id) {
        try {
            // Set start date to today and calculate monthly due date
            $start_date = date('Y-m-d');
            $monthly_due_date = date('Y-m-d', strtotime('+30 days'));
            
            $stmt = $this->db->prepare("
                UPDATE reservations 
                SET stay_start_date = ?, 
                    monthly_due_date = ?,
                    last_payment_date = NULL
                WHERE id = ? AND status = 'approved'
            ");
            
            $stmt->execute([$start_date, $monthly_due_date, $reservation_id]);
            
            // Create welcome notification
            $this->createNotification($reservation_id, 'payment_reminder', 
                "Welcome! Your monthly rent of is due on {$monthly_due_date}. Please prepare your payment 3 days before the due date.");
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // Create notification
    public function createNotification($reservation_id, $type, $message) {
        try {
            // Get user_id from reservation
            $stmt = $this->db->prepare("SELECT user_id FROM reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) return false;
            
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, reservation_id, type, message) 
                VALUES (?, ?, ?, ?)
            ");
            
            return $stmt->execute([$reservation['user_id'], $reservation_id, $type, $message]);
        } catch (Exception $e) {
            return false;
        }
    }

    // Check for upcoming payments and send reminders
    public function checkPaymentReminders() {
        try {
            // Find reservations with due dates in 3 days
            $reminder_date = date('Y-m-d', strtotime('+3 days'));
            
            $stmt = $this->db->prepare("
                SELECT r.*, s.fname, s.lname, s.email, rm.payment, rm.room_number
                FROM reservations r
                JOIN students s ON r.user_id = s.id  
                JOIN rooms rm ON r.room_id = rm.id
                WHERE r.monthly_due_date = ? 
                AND r.status IN ('approved', 'confirmed')
                AND NOT EXISTS (
                    SELECT 1 FROM notifications n 
                    WHERE n.reservation_id = r.id 
                    AND n.type = 'payment_reminder' 
                    AND DATE(n.created_at) = CURDATE()
                )
            ");
            
            $stmt->execute([$reminder_date]);
            $upcoming_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($upcoming_payments as $payment) {
                $message = "Hi {$payment['fname']}! Your monthly rent of ₱" . number_format($payment['payment'], 2) . 
                          " for Room #{$payment['room_number']} is due on {$payment['monthly_due_date']}. Please prepare your payment.";
                
                $this->createNotification($payment['id'], 'payment_reminder', $message);
            }
            
            // Find overdue payments
            $stmt = $this->db->prepare("
                SELECT r.*, s.fname, s.lname, rm.payment, rm.room_number
                FROM reservations r
                JOIN students s ON r.user_id = s.id
                JOIN rooms rm ON r.room_id = rm.id  
                WHERE r.monthly_due_date < CURDATE() 
                AND r.status IN ('approved', 'confirmed')
                AND NOT EXISTS (
                    SELECT 1 FROM notifications n 
                    WHERE n.reservation_id = r.id 
                    AND n.type = 'payment_overdue' 
                    AND DATE(n.created_at) = CURDATE()
                )
            ");
            
            $stmt->execute();
            $overdue_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($overdue_payments as $payment) {
                $days_overdue = (strtotime('now') - strtotime($payment['monthly_due_date'])) / (60 * 60 * 24);
                $message = "OVERDUE: Your rent of ₱" . number_format($payment['payment'], 2) . 
                          " for Room #{$payment['room_number']} was due on {$payment['monthly_due_date']} " .
                          "({$days_overdue} days ago). Please pay immediately to avoid penalties.";
                
                $this->createNotification($payment['id'], 'payment_overdue', $message);
            }
            
            return [
                'reminders_sent' => count($upcoming_payments),
                'overdue_notices' => count($overdue_payments)
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get notifications for a user
    public function getUserNotifications($user_id, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT n.*, rm.room_number 
                FROM notifications n
                JOIN reservations r ON n.reservation_id = r.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE n.user_id = ? 
                ORDER BY n.created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Get admin notifications
    public function getAdminNotifications($limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT n.*, s.fname, s.lname, rm.room_number, rm.payment
                FROM notifications n
                JOIN students s ON n.user_id = s.id
                JOIN reservations r ON n.reservation_id = r.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE n.type IN ('payment_overdue', 'payment_due')
                ORDER BY n.created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Mark notification as read
    public function markAsRead($notification_id) {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            return $stmt->execute([$notification_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    // Process payment and update due date
    public function processPayment($reservation_id, $amount, $payment_date, $payment_method, $notes = '') {
        // Ensure reservation exists and is already approved
        $reservationStmt = $this->db->prepare("SELECT status FROM reservations WHERE id = ?");
        $reservationStmt->execute([$reservation_id]);
        $reservation = $reservationStmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            throw new Exception('Reservation not found.');
        }

        $status = strtolower((string) ($reservation['status'] ?? ''));
        if (!in_array($status, ['approved', 'confirmed'], true)) {
            throw new Exception('Reservation must be approved before recording a payment.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO payment_history (
                reservation_id,
                amount,
                payment_method,
                transaction_reference,
                payment_for,
                payment_date,
                notes,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $reservation_id,
            $amount,
            $payment_method,
            null,
            'monthly_rent',
            $payment_date,
            $notes,
            'pending'
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function approvePayment(int $paymentId): void {
        $this->db->beginTransaction();

        try {
            $paymentStmt = $this->db->prepare(
                "SELECT * FROM payment_history WHERE id = ? FOR UPDATE"
            );
            $paymentStmt->execute([$paymentId]);
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception('Payment record not found.');
            }

            if (strtolower((string) $payment['status']) !== 'pending') {
                throw new Exception('Only pending payments can be approved.');
            }

            $reservationStmt = $this->db->prepare("SELECT * FROM reservations WHERE id = ?");
            $reservationStmt->execute([$payment['reservation_id']]);
            $reservation = $reservationStmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                throw new Exception('Reservation not found for payment record.');
            }

            $paymentDate = $payment['payment_date'];

            $updateReservation = $this->db->prepare("
                UPDATE reservations 
                SET last_payment_date = ?,
                    monthly_due_date = CASE 
                        WHEN monthly_due_date IS NULL OR monthly_due_date < ? THEN DATE_ADD(?, INTERVAL 30 DAY)
                        ELSE DATE_ADD(monthly_due_date, INTERVAL 30 DAY)
                    END
                WHERE id = ?
            ");
            $updateReservation->execute([$paymentDate, $paymentDate, $paymentDate, $payment['reservation_id']]);

            $updatePayment = $this->db->prepare("UPDATE payment_history SET status = 'approved', approved_at = NOW() WHERE id = ?");
            $updatePayment->execute([$paymentId]);

            $nextDueStmt = $this->db->prepare("SELECT monthly_due_date FROM reservations WHERE id = ?");
            $nextDueStmt->execute([$payment['reservation_id']]);
            $updatedReservation = $nextDueStmt->fetch(PDO::FETCH_ASSOC);

            $nextDueDate = $updatedReservation ? date('M j, Y', strtotime($updatedReservation['monthly_due_date'])) : 'updated due date';

            $this->createNotification(
                $payment['reservation_id'],
                'payment_reminder',
                "Payment of ₱" . number_format($payment['amount'], 2) . " received on {$paymentDate}. Thank you! Your next payment is due on {$nextDueDate}."
            );

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function rejectPayment(int $paymentId, string $reason = ''): void {
        $stmt = $this->db->prepare("SELECT reservation_id, amount, payment_date, status FROM payment_history WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            throw new Exception('Payment record not found.');
        }

        if (strtolower((string) $payment['status']) !== 'pending') {
            throw new Exception('Only pending payments can be rejected.');
        }

        $update = $this->db->prepare("UPDATE payment_history SET status = 'rejected', notes = CONCAT_WS(' | ', notes, ?) WHERE id = ?");
        $update->execute([$reason !== '' ? 'Rejected: ' . $reason : 'Rejected', $paymentId]);

        $message = 'Payment request submitted on ' . date('M j, Y', strtotime($payment['payment_date'])) . ' for ₱' . number_format($payment['amount'], 2) . ' was rejected.';
        if ($reason !== '') {
            $message .= ' Reason: ' . $reason;
        }

        $this->createNotification($payment['reservation_id'], 'payment_rejected', $message);
    }

    private function ensurePaymentHistorySchema(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS payment_history (
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
                    $this->db->query("SELECT {$column} FROM payment_history LIMIT 1");
                } catch (\PDOException $e) {
                    try {
                        $this->db->exec($statement);
                    } catch (\PDOException $ignore) {
                        // Ignore if column still cannot be added.
                    }
                }
            }

            try {
                $this->db->exec("UPDATE payment_history SET status = 'approved' WHERE status IS NULL OR status = ''");
            } catch (Exception $ignored) {
                // Ignore failures during backfill
            }
        } catch (Exception $e) {
            // Swallow schema ensure issues silently.
        }
    }

    private function ensureReservationBillingColumns(): void {
        $columnsToEnsure = [
            'stay_start_date' => "ALTER TABLE reservations ADD COLUMN stay_start_date DATE NULL AFTER updated_at",
            'stay_end_date' => "ALTER TABLE reservations ADD COLUMN stay_end_date DATE NULL AFTER stay_start_date",
            'monthly_due_date' => "ALTER TABLE reservations ADD COLUMN monthly_due_date DATE NULL AFTER stay_end_date",
            'last_payment_date' => "ALTER TABLE reservations ADD COLUMN last_payment_date DATE NULL AFTER monthly_due_date"
        ];

        foreach ($columnsToEnsure as $column => $statement) {
            try {
                $this->db->query("SELECT {$column} FROM reservations LIMIT 1");
            } catch (\PDOException $e) {
                try {
                    $this->db->exec($statement);
                } catch (\PDOException $ignore) {
                    // Ignore if column still cannot be added.
                }
            }
        }
    }
}
?>