<?php
/**
 * Daily Payment Reminder Cron Job
 * Run this script daily to send payment reminders
 * 
 * You can set this up in Windows Task Scheduler or run manually
 * Command: php C:\wamp\www\lasttry\payment_reminders.php
 */

// Set up the environment
define('BASEPATH', __DIR__ . '/');
require_once __DIR__ . '/app/config/DatabaseConfig.php';
require_once __DIR__ . '/app/controllers/NotificationController.php';

try {
    echo "=== Daily Payment Reminder Check ===\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n";
    
    $notificationController = new NotificationController();
    $result = $notificationController->checkPaymentReminders();
    
    if (isset($result['error'])) {
        echo "ERROR: " . $result['error'] . "\n";
    } else {
        echo "✓ Payment reminders sent: " . $result['reminders_sent'] . "\n";
        echo "✓ Overdue notices sent: " . $result['overdue_notices'] . "\n";
    }
    
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "=====================================\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
?>