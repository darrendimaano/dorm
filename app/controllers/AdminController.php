<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__.'/../models/ReservationsModel.php';

class AdminController extends Controller {

    public function index() {
        $this->landing();
    }

    public function landing() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            header('Location: '.BASE_URL.'/auth/login'); exit;
        }

        $reservationsModel = new ReservationsModel();
        $data['requests'] = $reservationsModel->getPendingReservations();
        $this->view('admin_landing', $data);
    }

    public function approve($id) {
        $model = new ReservationsModel();
        $model->updateStatus($id, 'approved');
        header('Location: '.BASE_URL.'/admin/landing'); exit;
    }

    public function reject($id) {
        $model = new ReservationsModel();
        $model->updateStatus($id, 'rejected');
        header('Location: '.BASE_URL.'/admin/landing'); exit;
    }
}
