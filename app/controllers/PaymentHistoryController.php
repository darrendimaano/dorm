<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';
require_once __DIR__ . '/NotificationController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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
        $selectedMonth = isset($_GET['month']) ? trim($_GET['month']) : '';
        if ($selectedMonth !== '' && !preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = '';
        }

        $selectedUser = 0;
        if (isset($_GET['user']) && $_GET['user'] !== '') {
            $selectedUser = (int) $_GET['user'];
            if ($selectedUser < 0) {
                $selectedUser = 0;
            }
        }
        
        // Ensure payment history schema is up to date (status/approved columns)
        $notifier = new NotificationController();

        try {
            $filterClauses = ["ph.status = 'approved'"];
            $filterParams = [];

            if ($selectedMonth !== '') {
                $filterClauses[] = "DATE_FORMAT(ph.payment_date, '%Y-%m') = ?";
                $filterParams[] = $selectedMonth;
            }

            if ($selectedUser > 0) {
                $filterClauses[] = "s.id = ?";
                $filterParams[] = $selectedUser;
            }

            $filterWhere = implode(' AND ', $filterClauses);

            $approvedQuery = "
                SELECT 
                    ph.id,
                    ph.amount,
                    ph.payment_date,
                    ph.payment_method,
                    ph.transaction_reference,
                    ph.payment_for,
                    ph.notes,
                    ph.created_at,
                    ph.approved_at,
                    s.fname,
                    s.lname,
                    s.email,
                    r.id as reservation_id,
                    r.status as reservation_status,
                    rm.room_number,
                    rm.payment as room_rate
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE {$filterWhere}
                ORDER BY ph.payment_date DESC, ph.created_at DESC
            ";
            $approvedStmt = $this->db->prepare($approvedQuery);
            $approvedStmt->execute($filterParams);
            $data['approvedPayments'] = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);

            $pendingQuery = "
                SELECT 
                    ph.id,
                    ph.amount,
                    ph.payment_date,
                    ph.payment_method,
                    ph.transaction_reference,
                    ph.payment_for,
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
                WHERE ph.status = 'pending'
                ORDER BY ph.created_at ASC
            ";
            $pendingStmt = $this->db->query($pendingQuery);
            $data['pendingPayments'] = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

            $summaryQuery = "
                SELECT 
                    COUNT(*) as total_payments,
                    SUM(ph.amount) as total_collected,
                    COUNT(DISTINCT r.user_id) as unique_tenants,
                    MIN(ph.payment_date) as first_payment,
                    MAX(ph.payment_date) as latest_payment
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                WHERE {$filterWhere}
            ";
            $summaryStmt = $this->db->prepare($summaryQuery);
            $summaryStmt->execute($filterParams);
            $data['summary'] = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_payments' => 0,
                'total_collected' => 0,
                'unique_tenants' => 0,
                'first_payment' => null,
                'latest_payment' => null
            ];

            $monthlyQuery = "
                SELECT 
                    DATE_FORMAT(ph.payment_date, '%Y-%m') as month_year,
                    COUNT(*) as payment_count,
                    SUM(ph.amount) as total_amount
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                WHERE {$filterWhere}
                GROUP BY DATE_FORMAT(ph.payment_date, '%Y-%m')
                ORDER BY month_year DESC
                LIMIT 12
            ";
            $monthlyStmt = $this->db->prepare($monthlyQuery);
            $monthlyStmt->execute($filterParams);
            $data['monthlyBreakdown'] = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

            $monthsStmt = $this->db->query("
                SELECT DISTINCT DATE_FORMAT(payment_date, '%Y-%m') AS month_year
                FROM payment_history
                WHERE status = 'approved'
                ORDER BY month_year DESC
            ");
            $data['monthOptions'] = $monthsStmt->fetchAll(PDO::FETCH_ASSOC);

            $usersStmt = $this->db->query("
                SELECT DISTINCT s.id, s.fname, s.lname
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                WHERE ph.status = 'approved'
                ORDER BY s.lname, s.fname
            ");
            $data['userOptions'] = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $data['error'] = 'Error loading payment history: ' . $e->getMessage();
            $data['approvedPayments'] = [];
            $data['pendingPayments'] = [];
            $data['summary'] = [
                'total_payments' => 0,
                'total_collected' => 0,
                'unique_tenants' => 0,
                'first_payment' => null,
                'latest_payment' => null
            ];
            $data['monthlyBreakdown'] = [];
            $data['monthOptions'] = [];
            $data['userOptions'] = [];
        }

        $data['selectedMonth'] = $selectedMonth;
        $data['selectedUser'] = $selectedUser;

        $this->call->view('admin/payment_history', $data);
    }

    public function approve() {
        $this->checkAdminSession();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $paymentId = isset($payload['id']) ? (int) $payload['id'] : 0;

        if ($paymentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing payment identifier.']);
            return;
        }

        try {
            require_once __DIR__ . '/NotificationController.php';
            $notificationController = new NotificationController();
            $notificationController->approvePayment($paymentId);
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function reject() {
        $this->checkAdminSession();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $paymentId = isset($payload['id']) ? (int) $payload['id'] : 0;
        $reason = isset($payload['reason']) ? trim((string) $payload['reason']) : '';

        if ($paymentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing payment identifier.']);
            return;
        }

        try {
            require_once __DIR__ . '/NotificationController.php';
            $notificationController = new NotificationController();
            $notificationController->rejectPayment($paymentId, $reason);
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function downloadCsv() {
        try {
            $selectedMonth = isset($_GET['month']) ? trim($_GET['month']) : '';
            if ($selectedMonth !== '' && !preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
                $selectedMonth = '';
            }

            $selectedUser = 0;
            if (isset($_GET['user']) && $_GET['user'] !== '') {
                $selectedUser = (int) $_GET['user'];
                if ($selectedUser < 0) {
                    $selectedUser = 0;
                }
            }

            $filterClauses = ["ph.status = 'approved'"];
            $filterParams = [];

            if ($selectedMonth !== '') {
                $filterClauses[] = "DATE_FORMAT(ph.payment_date, '%Y-%m') = ?";
                $filterParams[] = $selectedMonth;
            }

            if ($selectedUser > 0) {
                $filterClauses[] = "s.id = ?";
                $filterParams[] = $selectedUser;
            }

            $filterWhere = implode(' AND ', $filterClauses);

            $query = "
                SELECT 
                    ph.payment_date as 'Payment Date',
                    CONCAT(s.fname, ' ', s.lname) as 'Tenant Name',
                    s.email as 'Email',
                    rm.room_number as 'Room Number',
                    ph.amount as 'Amount Paid',
                    ph.payment_for as 'Payment For',
                    ph.payment_method as 'Payment Method',
                    ph.transaction_reference as 'Transaction Reference',
                    ph.notes as 'Notes',
                    ph.created_at as 'Recorded Date'
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE {$filterWhere}
                ORDER BY ph.payment_date DESC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($filterParams);
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
            $selectedMonth = isset($_GET['month']) ? trim($_GET['month']) : '';
            if ($selectedMonth !== '' && !preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
                $selectedMonth = '';
            }

            $selectedUser = 0;
            if (isset($_GET['user']) && $_GET['user'] !== '') {
                $selectedUser = (int) $_GET['user'];
                if ($selectedUser < 0) {
                    $selectedUser = 0;
                }
            }

            $filterClauses = ["ph.status = 'approved'"];
            $filterParams = [];

            if ($selectedMonth !== '') {
                $filterClauses[] = "DATE_FORMAT(ph.payment_date, '%Y-%m') = ?";
                $filterParams[] = $selectedMonth;
            }

            if ($selectedUser > 0) {
                $filterClauses[] = "s.id = ?";
                $filterParams[] = $selectedUser;
            }

            $filterWhere = implode(' AND ', $filterClauses);

            $query = "
                SELECT 
                    ph.payment_date,
                    CONCAT(s.fname, ' ', s.lname) as tenant_name,
                    s.email,
                    rm.room_number,
                    ph.amount,
                    ph.payment_method,
                    ph.transaction_reference,
                    ph.payment_for,
                    ph.notes
                FROM payment_history ph
                JOIN reservations r ON ph.reservation_id = r.id
                JOIN students s ON r.user_id = s.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE {$filterWhere}
                ORDER BY ph.payment_date DESC
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($filterParams);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $tenantLabel = 'All Tenants';
            if ($selectedUser > 0) {
                $tenantStmt = $this->db->prepare("SELECT CONCAT(fname, ' ', lname) FROM students WHERE id = ? LIMIT 1");
                $tenantStmt->execute([$selectedUser]);
                $fetchedLabel = $tenantStmt->fetchColumn();
                if (!empty($fetchedLabel)) {
                    $tenantLabel = $fetchedLabel;
                }
            }

            $monthLabel = 'All Months';
            if ($selectedMonth !== '') {
                $dateObj = DateTime::createFromFormat('Y-m', $selectedMonth);
                $monthLabel = $dateObj ? $dateObj->format('F Y') : $selectedMonth;
            }

            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);

            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Payment History Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                    h1 { text-align: center; color: #5C4033; margin-bottom: 10px; }
                    .summary { background: #f8f4ef; padding: 12px; border: 1px solid #e0d2c0; border-radius: 6px; margin-bottom: 20px; }
                    .summary p { margin: 4px 0; font-size: 12px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; font-size: 11px; }
                    th { background-color: #f2f2f2; text-align: left; }
                    .muted { color: #777; }
                </style>
            </head>
            <body>
                <h1>Dormitory Payment History Report</h1>
                <div class="summary">
                    <p><strong>Generated:</strong> ' . date('F j, Y g:i A') . '</p>
                    <p><strong>Total Records:</strong> ' . count($payments) . '</p>
                    <p><strong>Filters:</strong> ' . htmlspecialchars($monthLabel) . ' | ' . htmlspecialchars($tenantLabel) . '</p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Tenant</th>
                            <th>Email</th>
                            <th>Room</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Payment For</th>
                            <th>Reference</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>';

            if (!empty($payments)) {
                foreach ($payments as $payment) {
                    $html .= '<tr>
                        <td>' . date('M j, Y', strtotime($payment['payment_date'])) . '</td>
                        <td>' . htmlspecialchars($payment['tenant_name']) . '</td>
                        <td>' . htmlspecialchars($payment['email']) . '</td>
                        <td>Room #' . htmlspecialchars($payment['room_number']) . '</td>
                        <td>₱' . number_format($payment['amount'], 2) . '</td>
                        <td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_method']))) . '</td>
                        <td>' . ($payment['payment_for'] ? htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_for']))) : '<span class="muted">-</span>') . '</td>
                        <td>' . ($payment['transaction_reference'] ? htmlspecialchars($payment['transaction_reference']) : '<span class="muted">-</span>') . '</td>
                        <td>' . ($payment['notes'] ? htmlspecialchars($payment['notes']) : '<span class="muted">-</span>') . '</td>
                    </tr>';
                }
            } else {
                $html .= '<tr><td colspan="9" style="text-align:center; padding: 20px;">No payment records found for the selected filters.</td></tr>';
            }

            $html .= '</tbody>
                </table>
            </body>
            </html>';

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $dompdf->stream('payment_history_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error generating PDF: ' . $e->getMessage();
            header('Location: ' . site_url('admin/reports/payment-history'));
            exit;
        }
    }
}
?>