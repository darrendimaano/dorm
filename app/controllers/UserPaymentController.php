<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class UserPaymentController extends Controller {

    private $pdo;

    public function __construct() {
        parent::__construct();
        if(session_status() === PHP_SESSION_NONE) session_start();
        
        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            redirect('auth/login');
            exit;
        }
        
        // Initialize database connection
        $config = DatabaseConfig::getInstance();
        $this->pdo = $config->getConnection();
    }

    public function index() {
        $user_id = $_SESSION['user'];
        
        // Get user's payment history with reservation details
        $query = "
            SELECT 
                ph.*,
                r.id as reservation_id,
                r.room_id,
                r.status as reservation_status,
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
            WHERE s.id = ?
            ORDER BY ph.payment_date DESC
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id]);
        $data['payment_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get current reservation with payment status
        $current_query = "
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
        
        $stmt2 = $this->pdo->prepare($current_query);
        $stmt2->execute([$user_id]);
        $current_reservation = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $data['current_reservation'] = !empty($current_reservation) ? $current_reservation[0] : null;
        
        // Calculate payment summary
        $summary_query = "
            SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_paid,
                MAX(payment_date) as last_payment
            FROM payment_history ph
            JOIN reservations r ON ph.reservation_id = r.id
            WHERE r.user_id = ?
        ";
        
        $stmt3 = $this->pdo->prepare($summary_query);
        $stmt3->execute([$user_id]);
        $summary = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        $data['payment_summary'] = !empty($summary) ? $summary[0] : [
            'total_payments' => 0,
            'total_paid' => 0,
            'last_payment' => null
        ];
        
        $this->call->view('user/payments', $data);
    }
    
    public function receipt($payment_id) {
        $user_id = $_SESSION['user'];
        
        // Get payment details with verification that it belongs to the user
        $query = "
            SELECT 
                ph.*,
                r.id as reservation_id,
                r.room_id,
                rm.room_name,
                rm.room_number,
                s.fname,
                s.lname,
                s.email,
                s.student_id as student_number
            FROM payment_history ph
            JOIN reservations r ON ph.reservation_id = r.id
            JOIN rooms rm ON r.room_id = rm.id
            JOIN students s ON r.user_id = s.id
            WHERE ph.id = ? AND s.id = ?
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$payment_id, $user_id]);
        $payment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($payment)) {
            show_404();
        }
        
        $data['payment'] = $payment[0];
        $this->call->view('user/payment_receipt', $data);
    }
    
    public function download_receipt($payment_id) {
        $user_id = $_SESSION['user'];
        
        // Get payment details
        $query = "
            SELECT 
                ph.*,
                r.id as reservation_id,
                r.room_id,
                rm.room_name,
                rm.room_number,
                s.fname,
                s.lname,
                s.email,
                s.student_id as student_number
            FROM payment_history ph
            JOIN reservations r ON ph.reservation_id = r.id
            JOIN rooms rm ON r.room_id = rm.id
            JOIN students s ON r.user_id = s.id
            WHERE ph.id = ? AND s.id = ?
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$payment_id, $user_id]);
        $payment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($payment)) {
            show_404();
        }
        
        $payment = $payment[0];
        
        // Generate CSV receipt
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="payment_receipt_' . $payment['id'] . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, ['PAYMENT RECEIPT - DORMITORY MANAGEMENT SYSTEM']);
        fputcsv($output, ['']);
        fputcsv($output, ['Receipt Details']);
        fputcsv($output, ['Receipt ID', $payment['id']]);
        fputcsv($output, ['Payment Date', $payment['payment_date']]);
        fputcsv($output, ['']);
        fputcsv($output, ['Tenant Information']);
        fputcsv($output, ['Name', $payment['fname'] . ' ' . $payment['lname']]);
        fputcsv($output, ['Student ID', $payment['student_number']]);
        fputcsv($output, ['Email', $payment['email']]);
        fputcsv($output, ['']);
        fputcsv($output, ['Room Information']);
        fputcsv($output, ['Room', $payment['room_name'] . ' - ' . $payment['room_number']]);
        fputcsv($output, ['']);
        fputcsv($output, ['Payment Information']);
        fputcsv($output, ['Amount Paid', 'PHP ' . number_format($payment['amount'], 2)]);
        fputcsv($output, ['Payment Method', $payment['payment_method']]);
        fputcsv($output, ['Transaction ID', $payment['transaction_reference']]);
        fputcsv($output, ['Status', 'Completed']);
        
        fclose($output);
        exit;
    }
    
    public function submit() {
        $user_id = $_SESSION['user'];
        
        try {
            // Validate required fields
            if (!isset($_POST['amount']) || !isset($_POST['payment_method']) || !isset($_POST['payment_for'])) {
                $_SESSION['error'] = 'Please fill in all required fields.';
                redirect('user/payments');
                return;
            }
            
            $amount = floatval($_POST['amount']);
            $payment_method = $_POST['payment_method'];
            $payment_for = $_POST['payment_for'];
            $notes = $_POST['notes'] ?? '';
            
            // Get reference number based on payment method
            $reference_number = '';
            switch ($payment_method) {
                case 'gcash':
                    $reference_number = $_POST['gcash_reference'] ?? '';
                    break;
                case 'bank_transfer':
                    $reference_number = $_POST['bank_reference'] ?? '';
                    break;
                case 'cash':
                    $reference_number = 'CASH-' . date('YmdHis') . '-' . $user_id;
                    break;
            }
            
            // Get user's active reservation
            $stmt = $this->pdo->prepare("
                SELECT r.id, r.room_id, rm.room_number, rm.room_name 
                FROM reservations r 
                JOIN rooms rm ON r.room_id = rm.id 
                WHERE r.user_id = ? AND r.status = 'approved' 
                ORDER BY r.id DESC 
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reservation) {
                $_SESSION['error'] = 'No active reservation found.';
                redirect('user/payments');
                return;
            }
            
            // Insert payment record
            $insertStmt = $this->pdo->prepare("
                INSERT INTO payment_history (
                    reservation_id, 
                    amount, 
                    payment_method, 
                    transaction_reference, 
                    payment_date, 
                    notes
                ) VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            
            $insertStmt->execute([
                $reservation['id'],
                $amount,
                $payment_method,
                $reference_number,
                $notes
            ]);
            
            // Set success message based on payment method
            switch ($payment_method) {
                case 'gcash':
                    $_SESSION['success'] = 'GCash payment submitted successfully! Please wait for admin verification.';
                    break;
                case 'bank_transfer':
                    $_SESSION['success'] = 'Bank transfer payment submitted successfully! Please wait for admin verification.';
                    break;
                case 'cash':
                    $_SESSION['success'] = 'Cash payment request submitted! Please visit the office during business hours.';
                    break;
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error submitting payment: ' . $e->getMessage();
        }
        
        redirect('user/payments');
    }
}