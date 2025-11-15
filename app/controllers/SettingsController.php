<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class SettingsController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->call->model('SettingsModel');
    }

    public function index() {
        $data['settings'] = $this->SettingsModel->getSettings();
        $this->call->view('settings/index', $data);
    }

    public function getDarkMode($type = 'admin') {
        $settings = $this->SettingsModel->getSettings();
        if($type === 'admin') {
            return isset($settings['dark_mode_admin']) ? $settings['dark_mode_admin'] : '0';
        } else {
            return isset($settings['dark_mode_user']) ? $settings['dark_mode_user'] : '0';
        }
    }

    public function update() {
        if($this->io->method() == 'post') {
            // Check if it's an AJAX request for dark mode toggle
            if($this->io->post('ajax')) {
                $dark_mode_admin = $this->io->post('dark_mode_admin');
                $data = ['dark_mode_admin' => $dark_mode_admin];
                
                if($this->SettingsModel->updateSettings($data)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false]);
                }
                exit;
            }
            
            // Regular form submission
            $site_name = $this->io->post('site_name');
            $admin_email = $this->io->post('admin_email');
            $dark_mode_admin = $this->io->post('dark_mode_admin') ?? '0';
            $dark_mode_user = $this->io->post('dark_mode_user') ?? '0';
            $maintenance_mode = $this->io->post('maintenance_mode') ?? '0';

            $data = [
                'site_name' => $site_name,
                'admin_email' => $admin_email,
                'dark_mode_admin' => $dark_mode_admin,
                'dark_mode_user' => $dark_mode_user,
                'maintenance_mode' => $maintenance_mode
            ];

            if($this->SettingsModel->updateSettings($data)) {
                redirect(site_url('settings'));
            } else {
                echo "Error updating settings.";
            }
        }
    }
}
