<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ConsoleController extends Controller
{
    public function payment_check() {
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/NotificationController.php';
            $notificationController = new NotificationController();
            $result = $notificationController->checkPaymentReminders();
            
            if (isset($result['error'])) {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            } else {
                echo json_encode([
                    'success' => true,
                    'reminders_sent' => $result['reminders_sent'],
                    'overdue_notices' => $result['overdue_notices']
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
?>