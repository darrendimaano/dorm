<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../models/RoomsModel.php';
require_once __DIR__ . '/../models/ReservationsModel.php';

class AdminReservationsController extends Controller {

    private function checkAdminSession() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }
    }

    public function index() {
        $this->checkAdminSession();
        
        $data = ['pendingReservations' => [], 'allReservations' => [], 'success' => '', 'error' => ''];
        
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
            $reservationsModel = new ReservationsModel();
            $data['pendingReservations'] = $reservationsModel->getPendingReservations();
            $data['allReservations'] = $reservationsModel->getAllWithDetails();
        } catch (Exception $e) {
            $data['error'] = 'Error loading reservations: ' . $e->getMessage();
        }

        $this->call->view('admin/reservations', $data);
    }

    public function approveAction() {
        $this->checkAdminSession();
        
        $id = $_POST['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $_SESSION['error'] = 'Invalid reservation ID.';
            header('Location: ' . site_url('admin/reservations'));
            exit;
        }
        
        try {
            $reservationsModel = new ReservationsModel();
            
            // Get reservation details first
            $reservation = $reservationsModel->getReservationById($id);
            if (!$reservation) {
                $_SESSION['error'] = 'Reservation not found.';
                header('Location: ' . site_url('admin/reservations'));
                exit;
            }
            
            // Check if already approved
            if ($reservation['status'] === 'approved') {
                $_SESSION['error'] = 'Reservation is already approved.';
                header('Location: ' . site_url('admin/reservations'));
                exit;
            }
            
            // Update reservation status
            if ($reservationsModel->updateStatus($id, 'approved')) {
                // Update room availability (reduce by 1 when approved)
                $roomsModel = new RoomsModel();
                $room = $roomsModel->find($reservation['room_id']);
                if ($room && $room['available'] > 0) {
                    $newAvailability = $room['available'] - 1;
                    $roomsModel->update($reservation['room_id'], ['available' => $newAvailability]);
                }
                
                // Auto-set stay dates and create payment reminders
                require_once __DIR__ . '/NotificationController.php';
                $notificationController = new NotificationController();
                $notificationController->autoSetStayDates($id);
                
                $_SESSION['success'] = "Reservation for {$reservation['fname']} {$reservation['lname']} has been approved! Automatic payment reminders have been set up.";
            } else {
                $_SESSION['error'] = 'Failed to approve reservation.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error approving reservation: ' . $e->getMessage();
        }

        header('Location: ' . site_url('admin/reservations'));
        exit;
    }

    public function rejectAction() {
        $this->checkAdminSession();
        
        $id = $_POST['id'] ?? null;
        
        if (!$id || !is_numeric($id)) {
            $_SESSION['error'] = 'Invalid reservation ID.';
            header('Location: ' . site_url('admin/reservations'));
            exit;
        }
        
        try {
            $reservationsModel = new ReservationsModel();
            
            // Get reservation details first
            $reservation = $reservationsModel->getReservationById($id);
            if (!$reservation) {
                $_SESSION['error'] = 'Reservation not found.';
                header('Location: ' . site_url('admin/reservations'));
                exit;
            }
            
            // Check if already rejected
            if ($reservation['status'] === 'rejected') {
                $_SESSION['error'] = 'Reservation is already rejected.';
                header('Location: ' . site_url('admin/reservations'));
                exit;
            }
            
            // Update reservation status to rejected
            if ($reservationsModel->updateStatus($id, 'rejected')) {
                $_SESSION['success'] = "Reservation for {$reservation['fname']} {$reservation['lname']} has been rejected.";
            } else {
                $_SESSION['error'] = 'Failed to reject reservation.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error rejecting reservation: ' . $e->getMessage();
        }

        header('Location: ' . site_url('admin/reservations'));
        exit;
    }

    public function quickApprove() {
        $this->checkAdminSession();
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            
            if (!$id || !is_numeric($id)) {
                error_log('Invalid reservation ID provided for quickApprove: ' . $id);
                echo json_encode(['success' => false, 'message' => 'Invalid reservation ID.']);
                exit;
            }
            
            $reservationsModel = new ReservationsModel();
            $reservation = $reservationsModel->getReservationById($id);
            
            if (!$reservation) {
                error_log('Reservation not found for ID: ' . $id);
                echo json_encode(['success' => false, 'message' => 'Reservation not found.']);
                exit;
            }
            
            if ($reservation['status'] === 'approved') {
                echo json_encode(['success' => false, 'message' => 'Reservation is already approved.']);
                exit;
            }
            
            // Update reservation status
            if ($reservationsModel->updateStatus($id, 'approved')) {
                // Update room availability
                $roomsModel = new RoomsModel();
                $room = $roomsModel->find($reservation['room_id']);
                if ($room && $room['available'] > 0) {
                    $newAvailability = $room['available'] - 1;
                    $roomsModel->update($reservation['room_id'], ['available' => $newAvailability]);
                }
                
                // Auto-set stay dates and create payment reminders
                try {
                    require_once __DIR__ . '/NotificationController.php';
                    $notificationController = new NotificationController();
                    $notificationController->autoSetStayDates($id);
                } catch (Exception $e) {
                    error_log('Warning: Failed to set stay dates for reservation ' . $id . ': ' . $e->getMessage());
                    // Don't fail the approval if this fails
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => "✅ {$reservation['fname']} {$reservation['lname']}'s reservation approved successfully!"
                ]);
            } else {
                error_log('Failed to update reservation status to approved for ID: ' . $id);
                echo json_encode(['success' => false, 'message' => 'Failed to approve reservation.']);
            }
        } catch (Exception $e) {
            error_log('Exception in quickApprove: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function quickReject() {
        $this->checkAdminSession();
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            
            if (!$id || !is_numeric($id)) {
                error_log('Invalid reservation ID provided for quickReject: ' . $id);
                echo json_encode(['success' => false, 'message' => 'Invalid reservation ID.']);
                exit;
            }
            
            $reservationsModel = new ReservationsModel();
            $reservation = $reservationsModel->getReservationById($id);
            
            if (!$reservation) {
                error_log('Reservation not found for ID: ' . $id);
                echo json_encode(['success' => false, 'message' => 'Reservation not found.']);
                exit;
            }
            
            if ($reservation['status'] === 'rejected') {
                echo json_encode(['success' => false, 'message' => 'Reservation is already rejected.']);
                exit;
            }
            
            if ($reservationsModel->updateStatus($id, 'rejected')) {
                echo json_encode([
                    'success' => true, 
                    'message' => "❌ {$reservation['fname']} {$reservation['lname']}'s reservation rejected successfully!"
                ]);
            } else {
                error_log('Failed to update reservation status to rejected for ID: ' . $id);
                echo json_encode(['success' => false, 'message' => 'Failed to reject reservation.']);
            }
        } catch (Exception $e) {
            error_log('Exception in quickReject: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function bulkApprove() {
        $this->checkAdminSession();
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'No reservations selected.']);
            exit;
        }
        
        try {
            $reservationsModel = new ReservationsModel();
            $roomsModel = new RoomsModel();
            $successCount = 0;
            
            foreach ($ids as $id) {
                if (!is_numeric($id)) continue;
                
                $reservation = $reservationsModel->getReservationById($id);
                if (!$reservation || $reservation['status'] === 'approved') continue;
                
                if ($reservationsModel->updateStatus($id, 'approved')) {
                    // Update room availability
                    $room = $roomsModel->find($reservation['room_id']);
                    if ($room && $room['available'] > 0) {
                        $newAvailability = $room['available'] - 1;
                        $roomsModel->update($reservation['room_id'], ['available' => $newAvailability]);
                    }
                    
                    // Auto-set stay dates and create payment reminders
                    require_once __DIR__ . '/NotificationController.php';
                    $notificationController = new NotificationController();
                    $notificationController->autoSetStayDates($id);
                    
                    $successCount++;
                }
            }
            
            if ($successCount > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => "✅ Successfully approved {$successCount} reservation" . ($successCount > 1 ? 's' : '') . "!"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No reservations were approved.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function bulkReject() {
        $this->checkAdminSession();
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['success' => false, 'message' => 'No reservations selected.']);
            exit;
        }
        
        try {
            $reservationsModel = new ReservationsModel();
            $successCount = 0;
            
            foreach ($ids as $id) {
                if (!is_numeric($id)) continue;
                
                $reservation = $reservationsModel->getReservationById($id);
                if (!$reservation || $reservation['status'] === 'rejected') continue;
                
                if ($reservationsModel->updateStatus($id, 'rejected')) {
                    $successCount++;
                }
            }
            
            if ($successCount > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => "❌ Successfully rejected {$successCount} reservation" . ($successCount > 1 ? 's' : '') . "!"
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No reservations were rejected.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function reserve($room_id) {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['admin'])) {
            $_SESSION['error'] = "You must be logged in as admin.";
            header('Location: ' . site_url('rooms'));
            exit;
        }

        $roomsModel = new RoomsModel();
        $reservationsModel = new ReservationsModel();

        $room = $roomsModel->find($room_id);
        if(!$room) {
            $_SESSION['error'] = "Room not found.";
            header('Location: ' . site_url('rooms'));
            exit;
        }

        if($room['available'] <= 0) {
            $_SESSION['error'] = "Room is full.";
            header('Location: ' . site_url('rooms'));
            exit;
        }

        // Reduce availability
        $roomsModel->update($room_id, ['available' => $room['available'] - 1]);

        // Insert reservation for admin (or assign to a specific user)
        $reservationsModel->insert([
            'user_id' => 0,  // 0 = admin
            'room_id' => $room_id,
            'status'  => 'approved'
        ]);

        $_SESSION['success'] = "Room reserved successfully!";
        header('Location: ' . site_url('rooms'));
        exit;
    }

}
