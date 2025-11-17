<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/* -------------------- Landing Page -------------------- */
$router->get('/', 'LandingController::landingpage'); 

/* -------------------- Auth Routes -------------------- */
$router->match('/auth/login', 'AuthController::login', ['GET', 'POST']);
$router->match('/auth/register', 'AuthController::register', ['GET', 'POST']);
$router->match('/auth/verify', 'AuthController::verify', ['GET', 'POST']);
$router->get('/auth/resend_verification', 'AuthController::resend_verification');
$router->get('/auth/logout', 'AuthController::logout');

/* -------------------- User Routes -------------------- */
$router->get('/user_landing', 'UserLandingController::index');
$router->post('/user/reserve/1', 'UserLandingController::reserveRoom');
$router->post('/user/reserve/2', 'UserLandingController::reserveRoom');
$router->post('/user/reserve/3', 'UserLandingController::reserveRoom');
$router->post('/user/reserve/4', 'UserLandingController::reserveRoom');
$router->post('/user/reserve/5', 'UserLandingController::reserveRoom');
$router->post('/user/reserve/(:num)', 'UserLandingController::reserveRoom');
$router->get('/user/reserve/(:num)', 'UserLandingController::redirectToLanding');
$router->get('/user/profile', 'UserLandingController::profile');
$router->post('/user/profile/update', 'UserLandingController::updateProfile');
$router->get('/user/reservations', 'UserLandingController::myReservations');
$router->get('/user/contact', 'UserLandingController::contact');
$router->post('/user/contact/send', 'UserLandingController::sendMessage');

/* -------------------- User Payment Routes -------------------- */
$router->get('/user/payments', 'UserPaymentController::index');
$router->post('/user/payments/submit', 'UserPaymentController::submit');
$router->get('/user/payments/receipt/(:num)', 'UserPaymentController::receipt');
$router->get('/user/payments/download_receipt/(:num)', 'UserPaymentController::download_receipt');

/* -------------------- Admin Dashboard Routes -------------------- */
$router->get('/dashboard', 'DashboardController::index');
$router->get('/admin/dashboard', 'DashboardController::index');

/* -------------------- Admin Landing/Reservations Management -------------------- */
$router->get('/admin/landing', 'AdminLandingController::index');
$router->get('/admin/reservations', 'AdminReservationsController::index');

// Action routes for approve/reject - these handle all IDs
$router->post('/admin/reservations/approveAction', 'AdminReservationsController::approveAction');
$router->post('/admin/reservations/rejectAction', 'AdminReservationsController::rejectAction');

// Quick AJAX routes for approve/reject
$router->post('/admin/reservations/quickApprove', 'AdminReservationsController::quickApprove');
$router->post('/admin/reservations/quickReject', 'AdminReservationsController::quickReject');

// Bulk action routes
$router->post('/admin/reservations/bulkApprove', 'AdminReservationsController::bulkApprove');
$router->post('/admin/reservations/bulkReject', 'AdminReservationsController::bulkReject');

/* -------------------- Admin Reports -------------------- */
$router->get('/admin/reports', 'ReportsController::index');
$router->post('/admin/reports/updatePayment', 'ReportsController::updatePayment');
$router->post('/admin/reports/updateStayDates', 'ReportsController::updateStayDates');

/* -------------------- Payment History -------------------- */
$router->get('/admin/reports/payment-history', 'PaymentHistoryController::index');
$router->get('/admin/reports/payment-history/download-csv', 'PaymentHistoryController::downloadCsv');
$router->get('/admin/reports/payment-history/download-pdf', 'PaymentHistoryController::downloadPdf');

/* -------------------- Console/API Routes -------------------- */
$router->post('/console/payment_check', 'ConsoleController::payment_check');

$router->get('/admin/messages', 'AdminLandingController::messages');
$router->post('/admin/messages/reply/(:num)', 'AdminLandingController::replyMessage');

$router->get('/users', 'UsersController::index');
$router->match('/users/create', 'UsersController::create', ['GET', 'POST']);
$router->match('/users/update/(:num)', 'UsersController::update', ['GET', 'POST']);
$router->get('/users/delete/(:num)', 'UsersController::delete');

// Tenant Management Routes
$router->get('/users/tenants', 'UsersController::tenants');
$router->post('/users/assignTenant', 'UsersController::assignTenant');
$router->get('/users/removeTenant/(:num)', 'UsersController::removeTenant');

/* -------------------- Room Management Routes -------------------- */
$router->get('/rooms', 'RoomsController::index');
$router->match('/rooms/create', 'RoomsController::create', ['GET', 'POST']);
$router->match('/rooms/update/(:num)', 'RoomsController::update', ['GET', 'POST']);
$router->match('/rooms/delete/(:num)', 'RoomsController::delete', ['GET', 'POST']);

// Admin Room Management (updated to use RoomsController consistently)
$router->get('/admin/rooms', 'RoomsController::index');
$router->match('/admin/rooms/create', 'RoomsController::create', ['GET', 'POST']);
$router->match('/admin/rooms/update/(:num)', 'RoomsController::update', ['GET', 'POST']);
$router->match('/admin/rooms/delete/(:num)', 'RoomsController::delete', ['GET', 'POST']);

$router->get('/settings', 'SettingsController::index');
$router->post('/settings/update', 'SettingsController::update');

/* -------------------- Maintenance Routes -------------------- */
$router->get('/user/maintenance', 'MaintenanceController::index');
$router->post('/maintenance/submit', 'MaintenanceController::submit');
$router->get('/admin/maintenance', 'MaintenanceController::admin');
$router->post('/maintenance/update', 'MaintenanceController::updateStatus');

/* -------------------- Announcements Routes -------------------- */
$router->get('/user/announcements', 'AnnouncementsController::index');
$router->get('/admin/announcements', 'AnnouncementsController::admin');
$router->get('/announcements/get/(:num)', 'AnnouncementsController::get');
$router->post('/announcements/save', 'AnnouncementsController::create');
$router->post('/announcements/comment', 'AnnouncementsController::addComment');
$router->post('/announcements/toggle', 'AnnouncementsController::toggleStatus');
$router->post('/announcements/delete', 'AnnouncementsController::delete');

/* -------------------- Welcome/Default Routes -------------------- */
$router->get('/welcome', 'Welcome::index');

/* -------------------- Additional Admin Routes -------------------- */
$router->get('/admin', 'AdminLandingController::index');
$router->get('/admin/dashboard', 'DashboardController::index');
