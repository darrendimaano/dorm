<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class RoomsController extends Controller {
    private function logDebug($message) {
        $logDir = APP_DIR . '../runtime/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/rooms_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }

    private function logUploadDetails(string $context, $details): void {
        $payload = is_string($details) ? $details : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->logDebug("[UPLOAD][$context] $payload");
    }

    private function resolveStoragePath(?string $path): string {
        if (empty($path)) {
            return '';
        }

        $normalized = str_replace('\\', '/', $path);
        $relative = ltrim($normalized, '/');

        if ($relative === '') {
            return '';
        }

        $absoluteBase = dirname(__DIR__, 2);
        return $absoluteBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function deletePictureIfExists(?string $path): void {
        $absolute = $this->resolveStoragePath($path);

        if ($absolute !== '' && file_exists($absolute)) {
            @unlink($absolute);
        }
    }
    
    private function checkAdminSession() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        if(!isset($_SESSION['admin'])) {
            header('Location: ' . site_url('auth/login'));
            exit;
        }
    }

    private function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function sendJsonResponse(bool $success, string $message, array $extra = []): void {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $extra));
        exit;
    }

    private function resolvePictureUrl(?string $picturePath, ?string $pictureHash = null): ?string {
        if (empty($picturePath)) {
            return null;
        }

        $normalized = str_replace('\\', '/', $picturePath);
        if (preg_match('#^https?://#i', $normalized)) {
            $url = $normalized;
        } else {
            $url = rtrim(base_url(), '/') . '/' . ltrim($normalized, '/');
        }

        if (!empty($pictureHash)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'v=' . rawurlencode($pictureHash);
        }

        return $url;
    }

    private function formatRoomPayload(?array $room): array {
        if (empty($room)) {
            return [];
        }

        $room = $this->normalizeRoomRecord($room);

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $scriptBase = rtrim($scriptName, '/');
        $updatePath = $scriptBase . '/admin/rooms/update';

        return [
            'id' => isset($room['id']) ? (int) $room['id'] : 0,
            'room_number' => (string) ($room['room_number'] ?? ''),
            'room_name' => (string) ($room['room_name'] ?? ''),
            'beds' => isset($room['beds']) ? (int) $room['beds'] : 0,
            'available' => isset($room['available']) ? (int) $room['available'] : 0,
            'payment' => isset($room['payment']) ? (float) $room['payment'] : 0.0,
            'monthly_rate' => isset($room['monthly_rate']) ? (float) $room['monthly_rate'] : 0.0,
            'picture' => $room['picture'] ?? null,
            'picture_hash' => $room['picture_hash'] ?? null,
            'picture_url' => $this->resolvePictureUrl($room['picture'] ?? null, $room['picture_hash'] ?? null),
            'picture_name' => !empty($room['picture']) ? basename(str_replace('\\', '/', $room['picture'])) : '',
            'update_path' => $updatePath,
            'absolute_update_url' => site_url('admin/rooms/update'),
            'alternate_update_url' => site_url('rooms/update')
        ];
    }

    private function normalizeRoomRecord(array $room): array {
        $room['beds'] = isset($room['beds']) ? (int) $room['beds'] : 0;
        $room['available'] = isset($room['available']) ? (int) $room['available'] : 0;
        $room['payment'] = $this->normalizeCurrencyInput($room['payment'] ?? 0);
        $room['monthly_rate'] = $this->normalizeCurrencyInput($room['monthly_rate'] ?? $room['payment'] ?? 0);

        if ($room['payment'] <= 0 && $room['monthly_rate'] > 0) {
            $room['payment'] = $room['monthly_rate'];
        }

        if ($room['monthly_rate'] <= 0 && $room['payment'] > 0) {
            $room['monthly_rate'] = $room['payment'];
        }

        return $room;
    }

    private function normalizeCurrencyInput($value): float {
        if (is_null($value)) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return 0.0;
            }

            $normalized = preg_replace('/[^0-9.\-]+/', '', $trimmed);
            if ($normalized === '' || $normalized === '-' || $normalized === '.') {
                return 0.0;
            }

            return (float) $normalized;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return $this->normalizeCurrencyInput((string) $value);
        }

        return (float) $value;
    }

    public function __construct() {
        parent::__construct();
        $this->call->model('RoomsModel');
    }

    // Display all rooms
    public function index() {
        $this->checkAdminSession();
        
        $data = ['rooms' => [], 'success' => '', 'error' => ''];
        
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
            $data['rooms'] = array_map(function ($room) {
                return $this->normalizeRoomRecord($room);
            }, $this->RoomsModel->getAllRooms());
        } catch (Exception $e) {
            $data['error'] = 'Error loading rooms: ' . $e->getMessage();
        }
        
        // Check if this is admin route and use appropriate view
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/admin/rooms') !== false || strpos($uri, 'index.php/admin/rooms') !== false) {
            $this->call->view('admin/rooms', $data);
        } else {
            $this->call->view('rooms/index', $data);
        }
    }

    // Create room
    public function create() {
        $this->checkAdminSession();
        
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $isAdminContext = false;
        foreach ([$uri, $referrer] as $source) {
            if (strpos($source, '/admin/rooms') !== false || strpos($source, 'index.php/admin/rooms') !== false) {
                $isAdminContext = true;
                break;
            }
        }
            if (!$isAdminContext && isset($_POST['return_to']) && $_POST['return_to'] === 'admin') {
                $isAdminContext = true;
            }
        $baseRoute = $isAdminContext ? 'admin/rooms' : 'rooms';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $room_number = trim($_POST['room_number'] ?? '');
            $beds = (int)($_POST['beds'] ?? 1);
            $available = (int)($_POST['available'] ?? 1);
            $paymentInput = $_POST['payment'] ?? null;
            $payment = $this->normalizeCurrencyInput($paymentInput);
            $room_name_input = isset($_POST['room_name']) ? trim($_POST['room_name']) : '';
            $room_name = $room_name_input !== '' ? $room_name_input : 'Room ' . $room_number;
            $monthly_rate_input = $_POST['monthly_rate'] ?? null;
            $monthly_rate = ($monthly_rate_input !== null && $monthly_rate_input !== '')
                ? $this->normalizeCurrencyInput($monthly_rate_input)
                : $payment;
            
            if (empty($room_number) || $beds <= 0 || $available < 0 || $payment < 0) {
                $_SESSION['error'] = 'All fields are required and must have valid values.';
            } else {
                // Handle picture upload
                $picture_path = null;
                if (isset($_FILES['picture'])) {
                    $this->logUploadDetails('create_request', [
                        'name' => $_FILES['picture']['name'] ?? null,
                        'type' => $_FILES['picture']['type'] ?? null,
                        'size' => $_FILES['picture']['size'] ?? null,
                        'error' => $_FILES['picture']['error'] ?? null
                    ]);

                    if ($_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                        $picture_path = $this->uploadPicture($_FILES['picture']);
                        if (!$picture_path) {
                            $_SESSION['error'] = 'Failed to upload picture. Please try again.';
                            $this->logUploadDetails('create_failure', 'uploadPicture returned false');
                            header('Location: ' . site_url($baseRoute));
                            exit;
                        }
                    } elseif ($_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $this->logUploadDetails('create_failure', 'Unexpected upload error code: ' . ($_FILES['picture']['error'] ?? 'unknown'));
                        $_SESSION['error'] = 'Unable to process the uploaded image.';
                        header('Location: ' . site_url($baseRoute));
                        exit;
                    }
                }

                $data = [
                    'room_number' => $room_number,
                    'beds' => $beds,
                    'available' => $available,
                    'payment' => $payment,
                    'picture' => $picture_path
                ];
                
                try {
                    if ($this->RoomsModel->insertRoom($data)) {
                        $_SESSION['success'] = 'Dormitory room created successfully!';
                    } else {
                        $_SESSION['error'] = 'Failed to create room.';
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                }
            }
            
            // Determine redirect URL based on current route
            header('Location: ' . site_url($baseRoute));
            exit;
        } else {
            $this->call->view('rooms/create');
        }
    }

    // Update room
    public function update($id = null) {
        $this->checkAdminSession();
        $room_name = '';
        $monthly_rate = 0;
        $data = ['room' => [], 'success' => '', 'error' => ''];
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $isAdminContext = false;
        foreach ([$uri, $referrer] as $source) {
            if (strpos($source, '/admin/rooms') !== false || strpos($source, 'index.php/admin/rooms') !== false) {
                $isAdminContext = true;
                break;
            }
        }
            if (isset($_POST['return_to']) && $_POST['return_to'] === 'admin') {
                $isAdminContext = true;
            }
        if ($id === null && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }
        $this->logDebug("Update route hit | id=" . ($id ?? 'NULL') . " | method=" . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | uri={$uri} | referrer={$referrer}");
        if (isset($_POST['return_to']) && $_POST['return_to'] === 'admin') {
            $isAdminContext = true;
        }

        if (empty($id)) {
            $_SESSION['error'] = 'Invalid room identifier.';
            $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
            header('Location: ' . site_url($redirectUrl));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = $this->isAjaxRequest();
            $room_number = trim($_POST['room_number'] ?? '');
            $beds = (int)($_POST['beds'] ?? 1);
            $available = (int)($_POST['available'] ?? 1);
            $paymentInput = $_POST['payment'] ?? null;
            $payment = $this->normalizeCurrencyInput($paymentInput);
            $room_name_input = isset($_POST['room_name']) ? trim($_POST['room_name']) : '';
            $room_name = $room_name_input !== '' ? $room_name_input : 'Room ' . $room_number;
            $monthly_rate_raw = $_POST['monthly_rate'] ?? null;
            $monthly_rate = ($monthly_rate_raw !== null && $monthly_rate_raw !== '')
                ? $this->normalizeCurrencyInput($monthly_rate_raw)
                : $payment;

            $errors = [];
            if (empty($room_number)) {
                $errors[] = 'Room number is required';
            }
            if ($beds <= 0) {
                $errors[] = 'Number of beds must be greater than 0';
            }
            if ($available < 0) {
                $errors[] = 'Available slots cannot be negative';
            }
            if ($payment < 0) {
                $errors[] = 'Payment amount cannot be negative';
            }
            if ($monthly_rate < 0) {
                $errors[] = 'Monthly rate cannot be negative';
            }

            if (empty($errors)) {
                $existingPicturePath = isset($_POST['existing_picture']) ? trim($_POST['existing_picture']) : null;
                if ($existingPicturePath === '') {
                    $existingPicturePath = null;
                }

                $picture_path = $existingPicturePath;

                if (isset($_FILES['picture'])) {
                    if ($_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                        $this->logUploadDetails('update_request', [
                            'room_id' => $id,
                            'name' => $_FILES['picture']['name'] ?? null,
                            'type' => $_FILES['picture']['type'] ?? null,
                            'size' => $_FILES['picture']['size'] ?? null
                        ]);

                        $new_picture = $this->uploadPicture($_FILES['picture']);
                        if ($new_picture) {
                            $this->deletePictureIfExists($existingPicturePath);
                            $picture_path = $new_picture;
                        } else {
                            $errors[] = 'Failed to upload the new picture. Please check the file and try again.';
                            $this->logUploadDetails('update_failure', [
                                'room_id' => $id,
                                'reason' => 'uploadPicture returned false'
                            ]);
                        }
                    } elseif ($_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = 'Unable to process the uploaded image.';
                        $this->logUploadDetails('update_failure', [
                            'room_id' => $id,
                            'reason' => 'Unexpected upload error code',
                            'error_code' => $_FILES['picture']['error'] ?? null
                        ]);
                    }
                }

                if (empty($errors)) {
                    $updateData = [
                        'room_number' => $room_number,
                        'room_name' => $room_name,
                        'beds' => $beds,
                        'available' => $available,
                        'payment' => $payment,
                        'monthly_rate' => $monthly_rate,
                        'picture' => $picture_path
                    ];

                    try {
                        $result = $this->RoomsModel->update($id, $updateData);

                        if ($result) {
                            $successMessage = 'Dormitory room updated successfully!';

                            if ($isAjax) {
                                $updatedRoom = $this->RoomsModel->find($id);
                                $this->sendJsonResponse(true, $successMessage, [
                                    'room' => $this->formatRoomPayload($updatedRoom ?: array_merge($updateData, ['id' => $id]))
                                ]);
                            }

                            $_SESSION['success'] = $successMessage;
                        } else {
                            $errors[] = 'Failed to update room.';
                        }
                    } catch (Exception $e) {
                        $errors[] = 'Database error: ' . $e->getMessage();
                    }
                }
            }

            if (!empty($errors)) {
                $errorMessage = implode('. ', $errors);
                if ($isAjax) {
                    $this->sendJsonResponse(false, $errorMessage);
                }
                $_SESSION['error'] = $errorMessage;
            }

            if ($isAjax) {
                $this->sendJsonResponse(false, $_SESSION['error'] ?? 'Unable to update room.');
            }

            // If there was a validation error, show the form again with the error
            if (!empty($errors)) {
                try {
                    $data['room'] = $this->RoomsModel->find($id);
                    if ($data['room']) {
                        $data['room'] = $this->normalizeRoomRecord($data['room']);
                    }
                    $data['error'] = $_SESSION['error'];
                    unset($_SESSION['error']);
                    
                    if ($data['room']) {
                        // Check current route to determine which context we're in
                        $data['isAdminRoute'] = $isAdminContext;
                        $this->call->view('rooms/update', $data);
                        return;
                    }
                } catch (Exception $e) {
                    // Error loading room data
                }
            }
            
            // Determine redirect URL based on current route or referrer
            $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
            header('Location: ' . site_url($redirectUrl));
            exit;
        } else {
            // For GET requests, try to find the room and show the update form
            try {
                $data['room'] = $this->RoomsModel->find($id);
                if ($data['room']) {
                    $data['room'] = $this->normalizeRoomRecord($data['room']);
                }
                if (!$data['room']) {
                    $_SESSION['error'] = 'Room not found.';
                    $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
                    header('Location: ' . site_url($redirectUrl));
                    exit;
                }
                
                // Always show the update view
                // Check current route to determine which context we're in
                $data['isAdminRoute'] = $isAdminContext;
                
                // Pass any session messages to the view
                if (isset($_SESSION['error'])) {
                    $data['error'] = $_SESSION['error'];
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    $data['success'] = $_SESSION['success'];
                    unset($_SESSION['success']);
                }
                
                $this->call->view('rooms/update', $data);
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error loading room: ' . $e->getMessage();
                $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
                header('Location: ' . site_url($redirectUrl));
                exit;
            }
        }
    }

    // Delete room
    public function delete($id = null) {
        $this->checkAdminSession();
        $isAjax = $this->isAjaxRequest();
        if ($id === null && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }
        $this->logDebug("Delete route hit | id=" . ($id ?? 'NULL') . " | method=" . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | uri=" . ($_SERVER['REQUEST_URI'] ?? '') . " | referrer=" . ($_SERVER['HTTP_REFERER'] ?? ''));
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $isAdminContext = false;
        foreach ([$uri, $referrer] as $source) {
            if (strpos($source, '/admin/rooms') !== false || strpos($source, 'index.php/admin/rooms') !== false) {
                $isAdminContext = true;
                break;
            }
        }
        if (isset($_POST['return_to']) && $_POST['return_to'] === 'admin') {
            $isAdminContext = true;
        }
        if (empty($id)) {
            $errorMessage = 'Invalid room identifier.';
            if ($isAjax) {
                $this->sendJsonResponse(false, $errorMessage);
            }
            $_SESSION['error'] = $errorMessage;
            $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
            header('Location: ' . site_url($redirectUrl));
            exit;
        }
        
        try {
            // Get room info to delete picture
            $room = $this->RoomsModel->find($id);
            if ($room && !empty($room['picture'])) {
                $this->deletePictureIfExists($room['picture']);
            }
            
            if ($this->RoomsModel->delete($id)) {
                $successMessage = 'Room deleted successfully!';
                if ($isAjax) {
                    $this->sendJsonResponse(true, $successMessage, ['id' => (int) $id]);
                }
                $_SESSION['success'] = $successMessage;
            } else {
                $errorMessage = 'Room not found or already deleted.';
                if ($isAjax) {
                    $this->sendJsonResponse(false, $errorMessage);
                }
                $_SESSION['error'] = $errorMessage;
            }
        } catch (Exception $e) {
            $errorMessage = 'Error deleting room: ' . $e->getMessage();
            if ($isAjax) {
                $this->sendJsonResponse(false, $errorMessage);
            }
            $_SESSION['error'] = $errorMessage;
        }
        
        if ($isAjax) {
            $this->sendJsonResponse(false, $_SESSION['error'] ?? 'Unable to delete room.');
        }
        // Determine redirect URL based on current route
        $redirectUrl = $isAdminContext ? 'admin/rooms' : 'rooms';
        header('Location: ' . site_url($redirectUrl));
        exit;
    }
    
    // Helper method to upload pictures
    private function uploadPicture($file) {
        $relativeDir = 'public/uploads/rooms/';
        $absoluteDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg', 'image/pjpeg', 'image/x-png'];
        $reportedType = $file['type'] ?? '';
        $detectedType = $reportedType;

        if (!in_array($detectedType, $allowed_types) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if ($detected && in_array($detected, $allowed_types)) {
                    $detectedType = $detected;
                }
            }
        }

        if (!in_array($detectedType, $allowed_types)) {
            $this->logUploadDetails('upload_rejected', [
                'reason' => 'unsupported_mime',
                'reported_type' => $reportedType,
                'detected_type' => $detectedType
            ]);
            $detectedType = '';
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            $this->logUploadDetails('upload_rejected', [
                'reason' => 'file_too_large',
                'size' => $file['size'] ?? 0
            ]);
            return false;
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension);
        if ($safeExtension === '' || !in_array($safeExtension, $allowedExtensions)) {
            $this->logUploadDetails('upload_rejected', [
                'reason' => 'invalid_extension',
                'original_name' => $file['name'] ?? null
            ]);
            return false;
        }

        if ($detectedType === '' && !in_array($reportedType, $allowed_types)) {
            $this->logUploadDetails('upload_warning', [
                'note' => 'Proceeding based on extension despite unknown MIME type',
                'extension' => $safeExtension,
                'reported_type' => $reportedType
            ]);
        }

        $filename = 'room_' . time() . '_' . uniqid('', true) . '.' . $safeExtension;
        $relativePath = $relativeDir . $filename;
        $absolutePath = $absoluteDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $absolutePath)) {
            $this->logUploadDetails('upload_success', [
                'relative' => $relativePath,
                'absolute' => $absolutePath
            ]);
            return str_replace('\\', '/', $relativePath);
        }

        $this->logUploadDetails('upload_failure', [
            'reason' => 'move_uploaded_file_failed',
            'tmp_name' => $file['tmp_name'] ?? null,
            'target' => $absolutePath
        ]);

        return false;
    }
}
