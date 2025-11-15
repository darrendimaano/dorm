<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class PaymentHistoryController extends Controller
{
    protected $db;

    public function __construct() {
        parent::__construct();
        $this->checkAdminSession();
        $dbConfig = DatabaseConfig::getInstance();
        $this->db = $dbConfig->getConnection();
    }

    private function checkAdminSession() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }
    }

    public function index() {
        $data = [];
        
        try {
            // Get all payment history with tenant and room details
            $query = "
                SELECT 
                    ph.id,
                    ph.amount,
                    ph.payment_date,
                    ph.payment_method,
                    ph.notes,
                    ph.created_at,
                    s.fname,
                    s.lname,
                    s.email,
                    r.id as reservation_id,
                    rm.room_number,
                    rm.payment as room_rate
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                JOIN rooms rm ON r.room_id = rm.id
                ORDER BY ph.payment_date DESC, ph.created_at DESC
            ";
            
            $stmt = $this->db->query($query);
            $data['paymentHistory'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get summary statistics
            $summaryQuery = "
                SELECT 
                    COUNT(*) as total_payments,
                    SUM(amount) as total_collected,
                    AVG(amount) as average_payment,
                    COUNT(DISTINCT reservation_id) as unique_tenants,
                    MIN(payment_date) as first_payment,
                    MAX(payment_date) as latest_payment
                FROM payment_history
            ";
            
            $summaryStmt = $this->db->query($summaryQuery);
            $data['summary'] = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_payments' => 0,
                'total_collected' => 0,
                'average_payment' => 0,
                'unique_tenants' => 0,
                'first_payment' => null,
                'latest_payment' => null
            ];

            // Get monthly breakdown
            $monthlyQuery = "
                SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month_year,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount
                FROM payment_history
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month_year DESC
                LIMIT 12
            ";
            
            $monthlyStmt = $this->db->query($monthlyQuery);
            $data['monthlyBreakdown'] = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $data['error'] = 'Error loading payment history: ' . $e->getMessage();
            $data['paymentHistory'] = [];
            $data['summary'] = [
                'total_payments' => 0,
                'total_collected' => 0,
                'average_payment' => 0,
                'unique_tenants' => 0,
                'first_payment' => null,
                'latest_payment' => null
            ];
            $data['monthlyBreakdown'] = [];
        }

        $this->call->view('admin/payment_history', $data);
    }

    public function downloadCsv() {
        try {
            $query = "
                SELECT 
                    ph.payment_date as 'Payment Date',
                    CONCAT(s.fname, ' ', s.lname) as 'Tenant Name',
                    s.email as 'Email',
                    rm.room_number as 'Room Number',
                    ph.amount as 'Amount Paid',
                    ph.payment_method as 'Payment Method',
                    ph.notes as 'Notes',
                    ph.created_at as 'Recorded Date'
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                JOIN rooms rm ON r.room_id = rm.id
                ORDER BY ph.payment_date DESC
            ";
            
            $stmt = $this->db->query($query);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Set CSV headers
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="payment_history_' . date('Y-m-d') . '.csv"');

            // Open output stream
            $output = fopen('php://output', 'w');

            // Write CSV header
            if (!empty($payments)) {
                fputcsv($output, array_keys($payments[0]));
                
                // Write data rows
                foreach ($payments as $payment) {
                    fputcsv($output, $payment);
                }
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error generating CSV: ' . $e->getMessage();
            header('Location: ' . site_url('admin/reports/payment-history'));
            exit;
        }
    }

    public function downloadPdf() {
        try {
            $query = "
                SELECT 
                    ph.payment_date,
                    CONCAT(s.fname, ' ', s.lname) as tenant_name,
                    s.email,
                    rm.room_number,
                    ph.amount,
                    ph.payment_method,
                    ph.notes
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                JOIN rooms rm ON r.room_id = rm.id
                ORDER BY ph.payment_date DESC
            ";
            
            $stmt = $this->db->query($query);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Simple HTML to PDF conversion (you can use libraries like mPDF for better formatting)
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="payment_history_' . date('Y-m-d') . '.pdf"');

            // For now, output as HTML that can be printed to PDF
            header('Content-Type: text/html');
            
            echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Payment History Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    h1 { color: #5C4033; text-align: center; }
                    .summary { background: #f9f9f9; padding: 10px; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <h1>Dormitory Payment History Report</h1>
                <div class="summary">
                    <p><strong>Generated:</strong> ' . date('F j, Y g:i A') . '</p>
                    <p><strong>Total Records:</strong> ' . count($payments) . '</p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Tenant</th>
                            <th>Room</th>
                            <th>Amount</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($payments as $payment) {
                echo '<tr>
                    <td>' . date('M j, Y', strtotime($payment['payment_date'])) . '</td>
                    <td>' . htmlspecialchars($payment['tenant_name']) . '</td>
                    <td>Room #' . htmlspecialchars($payment['room_number']) . '</td>
                    <td>₱' . number_format($payment['amount'], 2) . '</td>
                    <td>' . ucfirst($payment['payment_method']) . '</td>
                </tr>';
            }
            
            echo '</tbody>
                </table>
            </body>
            </html>';

            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error generating PDF: ' . $e->getMessage();
            header('Location: ' . site_url('admin/reports/payment-history'));
            exit;
        }
    }
}
?>