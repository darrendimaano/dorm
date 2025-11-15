<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class LandingController extends Controller {

    public function landingpage() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Check if admin is logged in
        $isAdmin = isset($_SESSION['admin']);

        // Load Rooms model
        $this->call->model('RoomsModel');
        $rooms = $this->RoomsModel->All();

        // Pass data to view
        $data = [
            'rooms' => $rooms,
            'isAdmin' => $isAdmin
        ];

        $this->call->view('landingpage', $data);
    }
}
