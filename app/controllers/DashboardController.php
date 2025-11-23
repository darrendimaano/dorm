<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Include models
require_once __DIR__ . '/../models/UsersModel.php';
require_once __DIR__ . '/../models/RoomsModel.php';
require_once __DIR__ . '/../models/ReservationsModel.php';

class DashboardController extends Controller {

    public function index() {
        if(session_status() === PHP_SESSION_NONE) session_start();

        // Check if admin
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }

        // Load models
        $usersModel = new UsersModel();
        $roomsModel = new RoomsModel();
        $reservationsModel = new ReservationsModel();

        // Get all data
        $allUsers = $usersModel->all();
        $allRooms = $roomsModel->getAllRooms();
        
        // Dashboard stats
        $data['totalUsers'] = count($allUsers);
        $data['totalRooms'] = count($allRooms);
        $data['availableRooms'] = array_sum(array_column($allRooms, 'available'));
        $data['occupiedRooms'] = $data['totalRooms'] - $data['availableRooms'];
        
        // Get reservation stats (just counts, not full data)
        try {
            $pendingCount = count($reservationsModel->getPendingReservations());
            $totalReservations = count($reservationsModel->getAllReservations());
            $data['pendingCount'] = $pendingCount;
            $data['totalReservations'] = $totalReservations;
        } catch (Exception $e) {
            $data['pendingCount'] = 0;
            $data['totalReservations'] = 0;
        }

        // Prepare users per month for chart
        $usersPerMonth = [];
        foreach ($allUsers as $user) {
            $month = date('F', strtotime($user['created_at'] ?? 'now'));
            if (!isset($usersPerMonth[$month])) $usersPerMonth[$month] = 0;
            $usersPerMonth[$month]++;
        }
        $data['usersPerMonth'] = $usersPerMonth;

        // Prepare rooms availability for chart
        $roomsAvailability = [];
        foreach ($allRooms as $room) {
            $roomsAvailability[$room['room_number']] = $room['available'];
        }
        $data['roomsAvailability'] = $roomsAvailability;

        // Get payment notifications for admin
        require_once __DIR__ . '/NotificationController.php';
        $notificationController = new NotificationController();
        $data['adminNotifications'] = $notificationController->getAdminNotifications(5);

        // ✅ Load the view manually
        $dashboardView = __DIR__ . '/../views/dashboard.php';
        if (file_exists($dashboardView)) {
            // make $data available to view
            $roomsAvailability = $data['roomsAvailability'];
            $usersPerMonth = $data['usersPerMonth'];
            $totalUsers = $data['totalUsers'];
            $totalRooms = $data['totalRooms'];
            $availableRooms = $data['availableRooms'];
            $adminNotifications = $data['adminNotifications'];
            $roomsAvailability = $data['roomsAvailability'];
            $usersPerMonth = $data['usersPerMonth'];
            $totalUsers = $data['totalUsers'];
            $totalRooms = $data['totalRooms'];
            $availableRooms = $data['availableRooms'];
            $adminNotifications = $data['adminNotifications'];
            require $dashboardView;
        } else {
            echo "Dashboard view not found!";
        }
    }
}
