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
        try {
            // Record payment
            $stmt = $this->db->prepare("
                INSERT INTO payment_history (reservation_id, amount, payment_date, payment_method, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$reservation_id, $amount, $payment_date, $payment_method, $notes]);
            
            // Update next due date (add 30 days from current due date)
            $stmt = $this->db->prepare("
                UPDATE reservations 
                SET last_payment_date = ?,
                    monthly_due_date = DATE_ADD(monthly_due_date, INTERVAL 30 DAY)
                WHERE id = ?
            ");
            
            $stmt->execute([$payment_date, $reservation_id]);
            
            // Create payment confirmation notification with actual next due date
            $stmt = $this->db->prepare("SELECT monthly_due_date FROM reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $updated_reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $next_due_date = $updated_reservation ? date('M j, Y', strtotime($updated_reservation['monthly_due_date'])) : 'updated due date';
            
            $this->createNotification($reservation_id, 'payment_reminder', 
                "Payment of ₱" . number_format($amount, 2) . " received on {$payment_date}. Thank you! Your next payment is due on {$next_due_date}.");
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>