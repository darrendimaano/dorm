<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if (session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;

if (!function_exists('resolve_room_picture_paths')) {
    function resolve_room_picture_paths($picturePath, $pictureHash = '') {
        static $cachedRoot = null;
        $result = [
            'has_picture'   => false,
            'absolute_path' => '',
            'web_path'      => '',
            'file_name'     => '',
            'stored_path'   => $picturePath ?? ''
        ];

        if (empty($picturePath)) {
            return $result;
        }

        $normalized = str_replace('\\', '/', $picturePath);
        $result['stored_path'] = $normalized;

        if ($cachedRoot === null) {
            $candidates = [
                dirname(__DIR__, 2),
                dirname(__DIR__, 3),
                dirname(__DIR__, 4)
            ];

            foreach ($candidates as $candidate) {
                if (is_string($candidate) && is_dir($candidate . DIRECTORY_SEPARATOR . 'public')) {
                    $cachedRoot = $candidate;
                    break;
                }
            }

            if ($cachedRoot === null) {
                $cachedRoot = dirname(__DIR__, 2);
            }
        }

        if (preg_match('#^https?://#i', $normalized)) {
            $result['has_picture'] = true;
            $result['web_path'] = $normalized;
            $parsedPath = parse_url($normalized, PHP_URL_PATH);
            $result['file_name'] = $parsedPath ? basename($parsedPath) : '';
            if ($pictureHash !== '') {
                $separator = strpos($normalized, '?') === false ? '?' : '&';
                $result['web_path'] .= $separator . 'v=' . rawurlencode($pictureHash);
            }
            return $result;
        }

        $isAbsoluteFs = preg_match('#^(?:[a-zA-Z]:/|/)#', $normalized) === 1;
        if ($isAbsoluteFs) {
            $absolutePath = str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        } else {
            $relative = ltrim($normalized, '/');
            $absolutePath = $cachedRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        }

        if (!file_exists($absolutePath)) {
            return $result;
        }

        $result['has_picture'] = true;
        $result['absolute_path'] = $absolutePath;
        $result['file_name'] = basename($absolutePath);

        if ($isAbsoluteFs) {
            $basePath = $cachedRoot;
            $normalizedAbsolute = str_replace('\\', '/', $absolutePath);
            $normalizedBase = rtrim(str_replace('\\', '/', $basePath), '/');
            if (strpos($normalizedAbsolute, $normalizedBase . '/') === 0) {
                $relativeFromBase = substr($normalizedAbsolute, strlen($normalizedBase . '/'));
            } else {
                $relativeFromBase = $result['file_name'];
            }
        } else {
            $relativeFromBase = ltrim($normalized, '/');
        }

        $relativeFromBase = ltrim(str_replace('\\', '/', $relativeFromBase), '/');
        $baseUrl = rtrim(base_url(), '/');
        $webPath = $baseUrl . '/' . $relativeFromBase;
        if ($pictureHash !== '') {
            $webPath .= (strpos($webPath, '?') === false ? '?' : '&') . 'v=' . rawurlencode($pictureHash);
        }

        $result['web_path'] = $webPath;

        return $result;
    }
}

$currentScriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rooms - Dormitory Admin</title>
<script>
if (!window.navigator.onLine) {
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = `
            .bg-white { background-color: #ffffff; }
            .text-white { color: #ffffff; }
            .border { border: 1px solid #d1d5db; }
            .rounded-lg { border-radius: 0.75rem; }
            .shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .flex { display: flex; }
            .items-center { align-items: center; }
            .justify-between { justify-content: space-between; }
            .gap-2 { gap: 0.5rem; }
            .gap-3 { gap: 0.75rem; }
            .px-4 { padding-left: 1rem; padding-right: 1rem; }
            .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
            .text-sm { font-size: 0.875rem; }
            .font-medium { font-weight: 500; }
        `;
        document.head.appendChild(style);
        if (document.body) {
            document.body.classList.add('offline-mode');
        }
    });
}
</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
body.offline-mode .fa-check::before { content: '✓'; }
body.offline-mode .fa-times::before { content: '✗'; }
body.offline-mode .fa-print::before { content: '🖨️'; }
body.offline-mode .fa-file-excel::before { content: '📊'; }
body.offline-mode .fa-file-csv::before { content: '📄'; }
body.offline-mode .fa-exclamation-circle::before { content: '❗'; }
body.offline-mode .fa-check-circle::before { content: '✅'; }
</style>
</head>
<body class="bg-white font-sans flex<?= $darkModeEnabled ? ' dark' : ''; ?>">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="flex-1 ml-64 transition-all duration-300 content-area" id="mainContent">
    <div class="bg-[#FFF5E1] shadow-md flex items-center justify-between px-4 py-3">
                    <button id="menuBtn" class="text-[#5C4033] text-xl hover:bg-[#C19A6B] p-2 rounded transition">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1 class="font-bold text-lg text-[#5C4033] flex items-center gap-2">
                        <i class="fa-solid fa-bed text-[#C19A6B]"></i>
                        Dormitory Rooms
                    </h1>
                    <div class="flex items-center gap-2 text-sm text-[#5C4033] opacity-75">
                        <i class="fa-regular fa-calendar"></i>
                        <span id="currentTime"><?= date('M d, Y'); ?></span>
                    </div>
    </div>

    <div class="min-h-screen bg-gradient-to-br from-[#FDF6EC] to-[#F5E6D3]<?= $darkModeEnabled ? ' dark:from-[#111827] dark:to-[#1f2933]' : ''; ?>">
        <div class="max-w-7xl mx-auto px-6 py-8 space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-extrabold text-[#5C4033]">Dormitory Rooms</h1>
                            <p class="text-sm text-[#5C4033] opacity-80">Manage availability, rates, and assets without leaving the page.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 no-print">
                            <button onclick="printTable()" class="group inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white shadow-md hover:shadow-lg transition duration-300 transform hover:-translate-y-0.5" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
                                <i class="fas fa-print text-sm group-hover:animate-pulse"></i>
                                <span class="font-medium">Print</span>
                            </button>
                            <button onclick="exportToExcel()" class="group inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white shadow-md hover:shadow-lg transition duration-300 transform hover:-translate-y-0.5" style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                                <i class="fas fa-file-excel text-sm group-hover:animate-pulse"></i>
                                <span class="font-medium">Excel</span>
                            </button>
                            <button onclick="exportToCSV()" class="group inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white shadow-md hover:shadow-lg transition duration-300 transform hover:-translate-y-0.5" style="background: linear-gradient(135deg, #2196F3 0%, #1565C0 100%);">
                                <i class="fas fa-file-csv text-sm group-hover:animate-pulse"></i>
                                <span class="font-medium">CSV</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 no-print">
                        <div class="inline-flex items-center px-3 py-2 rounded-lg bg-white border border-[#C19A6B] text-[#5C4033] shadow-sm">
                            <i class="fas fa-database mr-2 text-[#C19A6B]"></i>
                            <span>Total rooms: <span data-total-rooms-count><?= count($rooms); ?></span></span>
                        </div>
                        <div class="inline-flex items-center px-3 py-2 rounded-lg bg-white border border-[#C19A6B] text-[#5C4033] shadow-sm">
                            <i class="fas fa-sitemap mr-2 text-[#C19A6B]"></i>
                            <span>Admin overview</span>
                        </div>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div style="background: #e6f7e6; border-color: #C19A6B;" class="border text-green-700 px-4 py-3 rounded mb-2 shadow-sm">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                <?= htmlspecialchars($success); ?>
                            </div>
                        </div>
                        <div id="roomsSuccessToastData" data-message="<?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>" class="hidden"></div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div style="background: #ffe6e6; border-color: #C19A6B;" class="border text-red-700 px-4 py-3 rounded mb-2 shadow-sm">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <?= htmlspecialchars($error); ?>
                            </div>
                        </div>
                        <div id="roomsErrorToastData" data-message="<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>" class="hidden"></div>
                    <?php endif; ?>

                    <!-- Confirmation Message Container -->
                    <div id="confirmationBox" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 no-print">
                        <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl border" style="border-color: #C19A6B;">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-question-circle text-[#C19A6B] text-2xl mr-3"></i>
                                <h3 class="text-lg font-semibold text-[#5C4033]">Confirm Action</h3>
                            </div>
                            <p id="confirmationMessage" class="text-[#5C4033] mb-6"></p>
                            <div class="flex justify-end gap-3">
                                <button onclick="cancelAction()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </button>
                                <button id="confirmButton" class="px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition duration-200" style="background: #C19A6B;">
                                    <i class="fas fa-check mr-2"></i>Confirm
                                </button>
                            </div>
                        </div>
                    </div>
        <!-- Rooms Management Table -->
        <div style="background: #FFF5E1;" class="rounded-lg shadow-lg overflow-hidden border" style="border-color: #C19A6B;">
            <!-- Print Header (only visible when printing) -->
            <div class="print-only p-6 text-center border-b">
                <h1 class="text-2xl font-bold text-[#5C4033]">Dormitory Room Management Report</h1>
                <p class="text-[#5C4033]">Generated on <?= date('F d, Y \a\t g:i A'); ?></p>
                <p class="text-[#5C4033]">Total Rooms: <?= count($rooms); ?></p>
            </div>
            
            <div class="p-6 border-b" style="border-color: #C19A6B;">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-[#5C4033]">
                        <i class="fas fa-list mr-2 text-[#C19A6B]"></i>All Dormitory Rooms - Professional Management
                    </h2>
                    <div class="text-sm text-[#5C4033] no-print opacity-80">
                        <span style="background: #e6f2ff; color: #5C4033; border-color: #C19A6B;" class="border px-2 py-1 rounded">
                            <i class="fas fa-info-circle mr-1"></i>
                            Use export buttons above to download or print this data
                        </span>
                    </div>
                </div>
            </div>

            <?php if (empty($rooms)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-bed text-6xl text-[#C19A6B] mb-4"></i>
                    <p class="text-[#5C4033] text-lg">No dormitory rooms found in the system.</p>
                    <p class="text-[#5C4033] opacity-70 text-sm mt-2">Rooms can be managed through the main rooms interface.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full" id="roomsTable">
                        <thead style="background: #e6ddd4;">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Room Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Beds</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Available</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Payment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Picture</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y" style="border-color: #C19A6B;">
                            <?php foreach ($rooms as $room): ?>
                                <?php
                                    $pictureMeta = resolve_room_picture_paths($room['picture'] ?? '', $room['picture_hash'] ?? '');
                                    $hasPicture = $pictureMeta['has_picture'];
                                    $pictureUrl = $pictureMeta['web_path'];
                                    $pictureFileName = $pictureMeta['file_name'];
                                    $storedPicturePath = $pictureMeta['stored_path'];
                                ?>
                                <tr class="hover:bg-[#FFF5E1] transition duration-200" data-room-id="<?= (int) $room['id']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-door-open text-[#C19A6B] mr-2 no-print"></i>
                                            <span class="text-sm font-medium text-[#5C4033]" data-field="room_number"><?= htmlspecialchars($room['room_number']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033]">
                                        <i class="fas fa-bed text-[#C19A6B] mr-1 no-print"></i>
                                        <span data-field="beds"><?= htmlspecialchars($room['beds']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033]">
                                        <span data-field="available"><?= htmlspecialchars($room['available']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033]">
                                        <i class="fas fa-peso-sign text-[#C19A6B] mr-1 no-print"></i>
                                        <span data-field="payment" data-raw-payment="<?= htmlspecialchars($room['payment']); ?>">₱<?= number_format($room['payment'], 2); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" data-picture-cell>
                                        <?php if ($hasPicture): ?>
                                            <img src="<?= htmlspecialchars($pictureUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Room <?= htmlspecialchars($room['room_number']); ?> picture" class="w-16 h-16 object-cover rounded-lg border border-[#C19A6B] shadow-sm" data-room-picture>
                                        <?php else: ?>
                                            <div class="w-16 h-16 flex items-center justify-center rounded-lg border border-dashed border-[#C19A6B] text-xs text-[#5C4033] opacity-70" data-room-picture-placeholder>
                                                No Image
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($room['available'] > 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800" data-field="status">
                                                <i class="fas fa-check mr-1 no-print"></i>
                                                Available
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" data-field="status">
                                                <i class="fas fa-times mr-1 no-print"></i>
                                                Full
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium no-print">
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                class="editRoomBtn inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-[#C19A6B] text-white hover:bg-[#B07A4B] transition duration-200 shadow"
                                                onclick="openEditModal(this); return false;"
                                                data-action="admin/rooms/update"
                                                data-action-route="admin/rooms/update"
                                                data-action-path="<?= htmlspecialchars(rtrim($currentScriptName, '/') . '/admin/rooms/update', ENT_QUOTES, 'UTF-8'); ?>"
                                                data-action-full="<?= htmlspecialchars(site_url('admin/rooms/update'), ENT_QUOTES, 'UTF-8'); ?>"
                                                data-room-id="<?= (int) $room['id']; ?>"
                                                data-room-number="<?= htmlspecialchars($room['room_number']); ?>"
                                                data-beds="<?= (int) $room['beds']; ?>"
                                                data-available="<?= (int) $room['available']; ?>"
                                                data-payment="<?= htmlspecialchars($room['payment']); ?>"
                                                data-monthly-rate="<?= htmlspecialchars($room['monthly_rate']); ?>"
                                                data-picture="<?= htmlspecialchars($storedPicturePath, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-picture-url="<?= htmlspecialchars($pictureUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-picture-name="<?= htmlspecialchars($pictureFileName, ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                <i class="fas fa-pen-to-square text-sm"></i>
                                                <span>Edit</span>
                                            </button>
                                            <form
                                                method="POST"
                                                action="<?= site_url('admin/rooms/delete'); ?>"
                                                class="deleteRoomForm inline"
                                                data-room-number="<?= htmlspecialchars($room['room_number']); ?>"
                                            >
                                                <input type="hidden" name="return_to" value="admin">
                                                <input type="hidden" name="id" value="<?= (int) $room['id']; ?>">
                                                <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-red-400 text-white hover:bg-red-500 transition duration-200 shadow">
                                                    <i class="fas fa-trash text-sm"></i>
                                                    <span>Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Room Modal -->
        <div id="editRoomModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 no-print">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 relative border" style="border-color: #C19A6B;">
                <button type="button" id="closeEditModal" class="absolute top-3 right-3 text-[#5C4033] hover:text-red-500 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-[#C19A6B] text-white p-3 rounded-xl">
                            <i class="fas fa-pen-to-square text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-[#5C4033]">Edit Room</h2>
                            <p class="text-sm text-[#5C4033] opacity-80">Update room details and availability information</p>
                        </div>
                    </div>
                    <form id="editRoomForm" method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="return_to" value="admin">
                        <input type="hidden" name="id" id="editRoomId">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="editRoomNumber" class="block text-sm font-semibold text-[#5C4033] mb-1">Room Number</label>
                                <input type="text" id="editRoomNumber" name="room_number" class="w-full border border-[#C19A6B] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#C19A6B]" required>
                            </div>
                            <div>
                                <label for="editBeds" class="block text-sm font-semibold text-[#5C4033] mb-1">Beds</label>
                                <input type="number" id="editBeds" name="beds" min="1" class="w-full border border-[#C19A6B] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#C19A6B]" required>
                            </div>
                            <div>
                                <label for="editAvailable" class="block text-sm font-semibold text-[#5C4033] mb-1">Available Slots</label>
                                <input type="number" id="editAvailable" name="available" min="0" class="w-full border border-[#C19A6B] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#C19A6B]" required>
                            </div>
                            <div>
                                <label for="editPayment" class="block text-sm font-semibold text-[#5C4033] mb-1">Monthly Payment (₱)</label>
                                <input type="number" step="0.01" min="0" id="editPayment" name="payment" class="w-full border border-[#C19A6B] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#C19A6B]" required>
                            </div>
                        </div>
                        <div>
                            <label for="editPicture" class="block text-sm font-semibold text-[#5C4033] mb-1">Update Picture (optional)</label>
                            <input type="file" id="editPicture" name="picture" accept="image/*" class="w-full border border-[#C19A6B] rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
                            <input type="hidden" name="existing_picture" id="editExistingPicture">
                            <p id="currentPictureName" class="text-xs text-[#5C4033] opacity-70 mt-2"></p>
                                <div id="editPicturePreviewWrapper" class="mt-3">
                                    <img id="editPicturePreviewImage" src="" alt="Selected room picture preview" class="hidden w-full max-h-60 object-cover rounded-xl border border-[#C19A6B] shadow-sm">
                                    <div id="editPicturePreviewPlaceholder" class="flex items-center justify-center w-full h-32 border border-dashed border-[#C19A6B] rounded-xl text-sm text-[#5C4033] opacity-70">
                                        No preview available
                                    </div>
                                </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" class="px-4 py-2 rounded-lg bg-gray-200 text-[#5C4033] hover:bg-gray-300 transition" id="cancelEditModal">Cancel</button>
                            <button type="submit" class="px-4 py-2 rounded-lg bg-[#C19A6B] text-white hover:bg-[#B07A4B] transition flex items-center gap-2">
                                <i class="fas fa-save"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
            <div style="background: #FFF5E1;" class="rounded-lg shadow p-6 border" style="border-color: #C19A6B;">
                <div class="flex items-center">
                    <div class="p-3 rounded-full mr-4" style="background: #e6ddd4; color: #C19A6B;">
                        <i class="fas fa-bed text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#5C4033] opacity-80">Total Rooms</p>
                        <p class="text-2xl font-bold text-[#5C4033]" data-total-rooms-card><?= count($rooms); ?></p>
                    </div>
                </div>
            </div>

            <div style="background: #FFF5E1;" class="rounded-lg shadow p-6 border" style="border-color: #C19A6B;">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                        <i class="fas fa-check text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#5C4033] opacity-80">Available</p>
                        <p class="text-2xl font-bold text-[#5C4033]" data-available-rooms-card>
                            <?= array_reduce($rooms, function($carry, $room) { return $carry + ($room['available'] > 0 ? 1 : 0); }, 0); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div style="background: #FFF5E1;" class="rounded-lg shadow p-6 border" style="border-color: #C19A6B;">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                        <i class="fas fa-times text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#5C4033] opacity-80">Full</p>
                        <p class="text-2xl font-bold text-[#5C4033]" data-full-rooms-card>
                            <?= array_reduce($rooms, function($carry, $room) { return $carry + ($room['available'] == 0 ? 1 : 0); }, 0); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div style="background: #FFF5E1;" class="rounded-lg shadow p-6 border" style="border-color: #C19A6B;">
                <div class="flex items-center">
                    <div class="p-3 rounded-full mr-4" style="background: #e6ddd4; color: #C19A6B;">
                        <i class="fas fa-peso-sign text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#5C4033] opacity-80">Avg. Rate</p>
                        <p class="text-2xl font-bold text-[#5C4033]" data-average-rate-display>
                            ₱<?= count($rooms) > 0 ? number_format(array_sum(array_column($rooms, 'payment')) / count($rooms), 2) : '0.00'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Confirmation dialog functions (define first)
function showConfirmation(message, callback) {
    document.getElementById('confirmationMessage').textContent = message;
    document.getElementById('confirmationBox').classList.remove('hidden');
    
    document.getElementById('confirmButton').onclick = function() {
        hideConfirmation();
        callback();
    };
}

function cancelAction() {
    hideConfirmation();
}

function hideConfirmation() {
    document.getElementById('confirmationBox').classList.add('hidden');
}

const APP_SCRIPT_NAME = <?= json_encode($_SERVER['SCRIPT_NAME'] ?? '/index.php'); ?>;

const editModal = document.getElementById('editRoomModal');
const editForm = document.getElementById('editRoomForm');
const editRoomNumberInput = document.getElementById('editRoomNumber');
const editRoomIdInput = document.getElementById('editRoomId');
const editBedsInput = document.getElementById('editBeds');
const editAvailableInput = document.getElementById('editAvailable');
const editPaymentInput = document.getElementById('editPayment');
const editExistingPictureInput = document.getElementById('editExistingPicture');
const currentPictureNameLabel = document.getElementById('currentPictureName');
const editCancelButton = document.getElementById('cancelEditModal');
const editCloseButton = document.getElementById('closeEditModal');
const editPictureInput = document.getElementById('editPicture');
const editPicturePreviewImage = document.getElementById('editPicturePreviewImage');
const editPicturePreviewPlaceholder = document.getElementById('editPicturePreviewPlaceholder');
let previousFocusElement = null;
const currencyFormatter = (typeof Intl !== 'undefined' && typeof Intl.NumberFormat === 'function')
    ? new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
    : null;

function parsePaymentValue(value) {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }

    if (typeof value === 'string') {
        const trimmed = value.trim();
        if (trimmed === '') {
            return 0;
        }

        const normalized = trimmed.replace(/[^0-9.\-]+/g, '');
        if (normalized === '' || normalized === '-' || normalized === '.') {
            return 0;
        }

        const parsed = parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    if (value && typeof value.valueOf === 'function' && value !== value.valueOf()) {
        return parsePaymentValue(value.valueOf());
    }

    return 0;
}

function formatCurrency(amount) {
    const safeValue = parsePaymentValue(amount);
    if (currencyFormatter) {
        return currencyFormatter.format(safeValue);
    }

    return `₱${safeValue.toFixed(2)}`;
}

function updateRoomRow(room) {
    if (!room || typeof room.id === 'undefined') {
        return;
    }

    const row = document.querySelector(`tr[data-room-id="${room.id}"]`);
    if (!row) {
        return;
    }

    const numberEl = row.querySelector('[data-field="room_number"]');
    if (numberEl) {
        numberEl.textContent = room.room_number || '';
    }

    const bedsEl = row.querySelector('[data-field="beds"]');
    if (bedsEl) {
        bedsEl.textContent = room.beds ?? '';
    }

    const availableEl = row.querySelector('[data-field="available"]');
    if (availableEl) {
        availableEl.textContent = room.available ?? '';
    }

    const paymentEl = row.querySelector('[data-field="payment"]');
    if (paymentEl) {
        const resolvedPayment = parsePaymentValue(room.payment);
        paymentEl.textContent = formatCurrency(resolvedPayment);
        paymentEl.dataset.rawPayment = String(resolvedPayment);
    }

    const statusEl = row.querySelector('[data-field="status"]');
    if (statusEl) {
        const isAvailable = Number(room.available) > 0;
        statusEl.classList.remove('bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
        statusEl.classList.add(isAvailable ? 'bg-green-100' : 'bg-red-100', isAvailable ? 'text-green-800' : 'text-red-800');
        statusEl.innerHTML = `<i class="fas fa-${isAvailable ? 'check' : 'times'} mr-1 no-print"></i>${isAvailable ? 'Available' : 'Full'}`;
    }

    const pictureCell = row.querySelector('[data-picture-cell]');
    if (pictureCell) {
        const existingImg = pictureCell.querySelector('[data-room-picture]');
        const placeholder = pictureCell.querySelector('[data-room-picture-placeholder]');

        if (room.picture_url) {
            if (!existingImg) {
                const img = document.createElement('img');
                img.className = 'w-16 h-16 object-cover rounded-lg border border-[#C19A6B] shadow-sm';
                img.alt = `Room ${room.room_number || ''} picture`;
                img.src = room.picture_url;
                img.setAttribute('data-room-picture', '');
                pictureCell.innerHTML = '';
                pictureCell.appendChild(img);
            } else {
                existingImg.src = room.picture_url;
                existingImg.alt = `Room ${room.room_number || ''} picture`;
            }
        } else {
            if (existingImg && existingImg.parentNode === pictureCell) {
                pictureCell.removeChild(existingImg);
            }

            if (!placeholder || placeholder.parentNode !== pictureCell) {
                pictureCell.innerHTML = '';
                const placeholderDiv = document.createElement('div');
                placeholderDiv.className = 'w-16 h-16 flex items-center justify-center rounded-lg border border-dashed border-[#C19A6B] text-xs text-[#5C4033] opacity-70';
                placeholderDiv.textContent = 'No Image';
                placeholderDiv.setAttribute('data-room-picture-placeholder', '');
                pictureCell.appendChild(placeholderDiv);
            }
        }
    }

    const editButton = row.querySelector('.editRoomBtn');
    if (editButton) {
        editButton.dataset.roomId = room.id != null ? String(room.id) : '';
        editButton.dataset.roomNumber = room.room_number || '';
        editButton.dataset.beds = room.beds != null ? String(room.beds) : '';
        editButton.dataset.available = room.available != null ? String(room.available) : '';
        const resolvedPayment = parsePaymentValue(room.payment);
        editButton.dataset.payment = String(resolvedPayment);
        editButton.dataset.picture = room.picture || '';
        editButton.dataset.pictureUrl = room.picture_url || '';
        editButton.dataset.pictureName = room.picture_name || '';
        if (room.monthly_rate != null) {
            const normalizedMonthly = parsePaymentValue(room.monthly_rate);
            editButton.dataset.monthlyRate = String(normalizedMonthly);
        } else {
            delete editButton.dataset.monthlyRate;
        }
        editButton.dataset.actionRoute = 'admin/rooms/update';
        editButton.dataset.action = 'admin/rooms/update';
        if (room.absolute_update_url) {
            editButton.dataset.actionFull = room.absolute_update_url;
        }
        if (room.update_path) {
            editButton.dataset.actionPath = room.update_path;
        }
    }

    refreshRoomsOverview();
}

function refreshRoomsOverview() {
    const rows = Array.from(document.querySelectorAll('#roomsTable tbody tr'));
    const totalRooms = rows.length;
    let availableCount = 0;
    let fullCount = 0;
    let paymentTotal = 0;

    rows.forEach(row => {
        const availableEl = row.querySelector('[data-field="available"]');
        const availableValue = availableEl ? parseInt(availableEl.textContent || '0', 10) : 0;
        if (availableValue > 0) {
            availableCount++;
        } else {
            fullCount++;
        }

        const paymentEl = row.querySelector('[data-field="payment"]');
        if (paymentEl) {
            const rawValue = paymentEl.dataset.rawPayment != null ? paymentEl.dataset.rawPayment : paymentEl.textContent;
            paymentTotal += parsePaymentValue(rawValue);
        }
    });

    const badgeTarget = document.querySelector('[data-total-rooms-count]');
    if (badgeTarget) {
        badgeTarget.textContent = String(totalRooms);
    }

    const totalCard = document.querySelector('[data-total-rooms-card]');
    if (totalCard) {
        totalCard.textContent = String(totalRooms);
    }

    const availableCard = document.querySelector('[data-available-rooms-card]');
    if (availableCard) {
        availableCard.textContent = String(availableCount);
    }

    const fullCard = document.querySelector('[data-full-rooms-card]');
    if (fullCard) {
        fullCard.textContent = String(fullCount);
    }

    const averageDisplay = document.querySelector('[data-average-rate-display]');
    if (averageDisplay) {
        averageDisplay.textContent = totalRooms > 0 ? formatCurrency(paymentTotal / totalRooms) : formatCurrency(0);
    }
}

async function performRoomDeletion(form) {
    if (!form) {
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton && !submitButton.dataset.originalText) {
        submitButton.dataset.originalText = submitButton.innerHTML;
    }

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i><span> Deleting...</span>';
    }

    const formData = new FormData(form);
    if (!formData.get('id')) {
        const row = form.closest('tr');
        if (row && row.dataset.roomId) {
            formData.set('id', row.dataset.roomId);
        }
    }
    formData.set('return_to', 'admin');

    const requestUrl = form.getAttribute('action') || window.location.href;

    try {
        const response = await fetch(requestUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include'
        });

        const contentType = response.headers.get('Content-Type') || '';
        const payload = contentType.includes('application/json') ? await response.json() : null;

        if (response.ok && payload && payload.success) {
            const row = form.closest('tr');
            if (row) {
                row.remove();
            }
            refreshRoomsOverview();
            showExportMessage(payload.message || 'Room deleted successfully!', 'success');

            if (!document.querySelector('#roomsTable tbody tr')) {
                setTimeout(() => window.location.reload(), 1000);
            }
            return;
        }

        const failureMessage = (payload && payload.message) || `Unable to delete room. (${response.status})`;
        throw new Error(failureMessage);
    } catch (error) {
        const fallbackMessage = error instanceof Error ? error.message : 'Unable to delete room.';
        showExportMessage(fallbackMessage, 'error');
    } finally {
        if (submitButton) {
            const restored = submitButton.dataset.originalText || '<i class="fas fa-trash text-sm"></i><span> Delete</span>';
            submitButton.innerHTML = restored;
            submitButton.disabled = false;
        }
    }
}

function getScriptName() {
    if (typeof APP_SCRIPT_NAME === 'string' && APP_SCRIPT_NAME.trim() !== '') {
        return APP_SCRIPT_NAME.trim();
    }
    return '/index.php';
}

function resolveRouteUrl(route, fallbackUrl = '') {
    const scriptName = getScriptName();
    const normalizedScript = scriptName.endsWith('/') ? scriptName.slice(0, -1) : scriptName;
    const normalizedRoute = (route || '').replace(/^\/+/, '');

    if (normalizedRoute !== '') {
        const path = normalizedScript !== ''
            ? `${normalizedScript}/${normalizedRoute}`
            : `/${normalizedRoute}`;
        return `${window.location.origin}${path}`;
    }

    if (fallbackUrl) {
        try {
            return new URL(fallbackUrl, window.location.href).toString();
        } catch (error) {
            // ignore
        }
    }

    return `${window.location.origin}${normalizedScript || '/index.php'}`;
}

function normalizeCandidate(url) {
    if (!url || typeof url !== 'string') {
        return null;
    }

    const trimmed = url.trim();
    if (trimmed === '') {
        return null;
    }

    try {
        const normalized = new URL(trimmed, window.location.href);
        normalized.protocol = window.location.protocol;
        normalized.host = window.location.host;
        return `${normalized.origin}${normalized.pathname}${normalized.search}`;
    } catch (error) {
        const [pathPart, queryPart] = trimmed.split('?');
        const sanitizedPath = `/${pathPart.replace(/^\/+/, '')}`;
        const queryString = queryPart ? `?${queryPart}` : '';
        return `${window.location.origin}${sanitizedPath}${queryString}`;
    }
}

function addCandidate(setRef, candidate) {
    const normalized = normalizeCandidate(candidate);
    if (normalized) {
        setRef.add(normalized);
    }
}

function addPathVariants(setRef, path) {
    if (!path || typeof path !== 'string') {
        return;
    }

    const trimmed = path.trim();
    if (trimmed === '') {
        return;
    }

    const normalizedPath = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
    addCandidate(setRef, normalizedPath);

    const segments = normalizedPath.replace(/^\/+/, '').split('/').filter(Boolean);
    if (segments.length > 1) {
        addCandidate(setRef, `/${segments.join('/')}`);
        addCandidate(setRef, `/${segments.slice(1).join('/')}`);
        if (segments.length > 2) {
            addCandidate(setRef, `/${segments.slice(2).join('/')}`);
        }
    }
}

function buildActionUrlCandidates(form) {
    const candidates = new Set();
    const route = (form.dataset.actionRoute || '').replace(/^\/+/, '');
    const currentAction = form.getAttribute('action') || '';
    const originalAction = form.dataset.originalAction || '';
    const fullAction = form.dataset.actionFull || '';
    const actionPath = form.dataset.actionPath || '';

    if (actionPath) {
        addCandidate(candidates, actionPath);
    }

    if (route) {
        addCandidate(candidates, resolveRouteUrl(route, currentAction || originalAction));
        const scriptName = getScriptName();
        if (scriptName) {
            const scriptBase = scriptName.endsWith('/') ? scriptName.slice(0, -1) : scriptName;
            if (scriptBase !== '') {
                addCandidate(candidates, `${scriptBase}/${route}`);
            }
        }
        addPathVariants(candidates, route);
    }

    [currentAction, originalAction, fullAction].forEach(action => {
        if (!action) {
            return;
        }
        addCandidate(candidates, action);

        try {
            const parsed = new URL(action, window.location.href);
            addPathVariants(candidates, parsed.pathname);
        } catch (error) {
            addPathVariants(candidates, action);
        }
    });

    const currentPath = window.location.pathname || '';
    if (route && currentPath) {
        const basePath = currentPath.replace(/\/?admin\/rooms.*$/i, '');
        if (basePath && basePath !== currentPath) {
            addCandidate(candidates, `${basePath}/${route}`);
        }
    }

    if (!candidates.size) {
        addCandidate(candidates, fullAction || currentAction || originalAction || window.location.href);
    }

    return Array.from(candidates);
}

function buildRequestUrl(form) {
    if (!form) {
        return window.location.href;
    }

    const storedUrl = form.dataset.submitUrl || form.getAttribute('action') || '';
    if (storedUrl && typeof storedUrl === 'string') {
        return storedUrl;
    }

    return window.location.href;
}

async function submitRoomUpdateRequest(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include'
    });

    const contentType = response.headers.get('Content-Type') || '';
    const isJson = contentType.includes('application/json');
    const payload = isJson ? await response.json() : null;

    return { response, payload };
}

function updateEditPicturePreview(previewSrc) {
    if (!editPicturePreviewImage || !editPicturePreviewPlaceholder) {
        return;
    }

    if (previewSrc) {
        editPicturePreviewImage.src = previewSrc;
        editPicturePreviewImage.classList.remove('hidden');
        editPicturePreviewPlaceholder.classList.add('hidden');
    } else {
        editPicturePreviewImage.src = '';
        editPicturePreviewImage.classList.add('hidden');
        editPicturePreviewPlaceholder.classList.remove('hidden');
    }
}

function handleEditModalEsc(event) {
    if (event.key === 'Escape') {
        closeEditModal();
    }
}

function openEditModal(button) {
    if (!editModal || !editForm) return;

    previousFocusElement = document.activeElement;

    const picturePath = button.dataset.picture || '';
    const pictureUrl = button.dataset.pictureUrl || '';
    const pictureName = button.dataset.pictureName || '';

    const actionRoute = button.dataset.actionRoute || '';
    const fallbackAction = button.dataset.action || '';
    const fallbackFull = button.dataset.actionFull || '';
    const actionPath = button.dataset.actionPath || '';
    const resolvedActionCandidate = fallbackFull || actionPath || (actionRoute ? resolveRouteUrl(actionRoute, fallbackAction) : fallbackAction);

    if (resolvedActionCandidate) {
        editForm.action = resolvedActionCandidate;
        editForm.dataset.submitUrl = resolvedActionCandidate;
    }
    editForm.dataset.actionRoute = actionRoute;
    editForm.dataset.originalAction = fallbackAction;
    if (fallbackFull) {
        editForm.dataset.actionFull = fallbackFull;
    } else {
        delete editForm.dataset.actionFull;
    }
    if (actionPath) {
        editForm.dataset.actionPath = actionPath;
    } else {
        delete editForm.dataset.actionPath;
    }
    editForm.dataset.roomId = button.dataset.roomId || '';
    if (editRoomIdInput) {
        editRoomIdInput.value = button.dataset.roomId || '';
    }
    if (editRoomNumberInput) editRoomNumberInput.value = button.dataset.roomNumber || '';
    if (editBedsInput) editBedsInput.value = button.dataset.beds || '';
    if (editAvailableInput) editAvailableInput.value = button.dataset.available || '';
    if (editPaymentInput) {
        let paymentDataset = button.dataset.payment;
        if ((!paymentDataset || paymentDataset === '0') && button.dataset.monthlyRate) {
            paymentDataset = button.dataset.monthlyRate;
        }

        if (typeof paymentDataset === 'string' && paymentDataset !== '') {
            const parsedPayment = parsePaymentValue(paymentDataset);
            editPaymentInput.value = parsedPayment > 0 ? String(parsedPayment) : '';
        } else {
            editPaymentInput.value = '';
        }
    }
    if (editExistingPictureInput) editExistingPictureInput.value = picturePath;

    if (editPictureInput) {
        editPictureInput.value = '';
    }

    if (editModal) {
        if (pictureUrl) {
            editModal.dataset.currentPictureUrl = pictureUrl;
        } else {
            delete editModal.dataset.currentPictureUrl;
        }

        if (pictureName) {
            editModal.dataset.currentPictureName = pictureName;
        } else {
            delete editModal.dataset.currentPictureName;
        }
    }

    if (currentPictureNameLabel) {
        currentPictureNameLabel.textContent = pictureName ? `Current file: ${pictureName}` : 'No picture uploaded for this room.';
    }

    updateEditPicturePreview(pictureUrl);

    editModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    document.addEventListener('keydown', handleEditModalEsc);

    if (editRoomNumberInput) {
        setTimeout(() => editRoomNumberInput.focus(), 100);
    }
}

function closeEditModal() {
    if (!editModal) return;

    editModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.removeEventListener('keydown', handleEditModalEsc);

    if (editPictureInput) {
        editPictureInput.value = '';
    }

    const fallbackUrl = editModal.dataset.currentPictureUrl || '';
    const fallbackName = editModal.dataset.currentPictureName || '';

    updateEditPicturePreview(fallbackUrl);

    if (currentPictureNameLabel) {
        currentPictureNameLabel.textContent = fallbackName ? `Current file: ${fallbackName}` : 'No picture uploaded for this room.';
    }

    if (previousFocusElement && typeof previousFocusElement.focus === 'function') {
        previousFocusElement.focus();
    }
    previousFocusElement = null;
}

// Print functionality
function printTable() {
    showConfirmation('Are you sure you want to print the rooms report?', function() {
        // Hide no-print elements and show print-only elements
        const noPrintElements = document.querySelectorAll('.no-print');
        const printOnlyElements = document.querySelectorAll('.print-only');
        
        noPrintElements.forEach(el => el.style.display = 'none');
        printOnlyElements.forEach(el => el.style.display = 'block');
        
        // Print the page
        window.print();
        
        // Restore original visibility after printing
    setTimeout(() => {
        noPrintElements.forEach(el => el.style.display = '');
        printOnlyElements.forEach(el => el.style.display = 'none');
    }, 1000);
    const candidates = [
        form.dataset.actionFull || '',
        form.getAttribute('action') || '',
        form.dataset.actionPath || '',
        form.dataset.originalAction || '',
        form.dataset.actionRoute ? resolveRouteUrl(form.dataset.actionRoute, '') : ''
    ];

    for (const candidate of candidates) {
        if (!candidate || typeof candidate !== 'string') {
            continue;
        }

        const normalized = normalizeCandidate(candidate);
        if (normalized) {
            return normalized;
        }
    }

    return window.location.href;
    XLSX.utils.book_append_sheet(wb, ws, 'Rooms');
    
    // Auto-size columns
    const colWidths = [];
    for (let i = 0; i < headers.length; i++) {
        let maxLength = headers[i].length;
        for (let j = 1; j < data.length; j++) {
            if (data[j][i] && data[j][i].length > maxLength) {
                maxLength = data[j][i].length;
            }
        }
        colWidths.push({ width: Math.min(maxLength + 2, 50) });
    }
    ws['!cols'] = colWidths;
    
    // Generate filename with current date
    const now = new Date();
    const filename = `Dormitory_Rooms_${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}.xlsx`;
    
    // Save file
    XLSX.writeFile(wb, filename);
    
    // Show success message
    showExportMessage('Excel file downloaded successfully!', 'success');
    });
}

// Export to CSV
function exportToCSV() {
    showConfirmation('Are you sure you want to download the rooms data as CSV file?', function() {
        const table = document.getElementById('roomsTable');
        const rows = [];
    
    // Get headers (excluding Actions column)
    const headerRow = table.querySelector('thead tr');
    const headerCells = headerRow.querySelectorAll('th:not(.no-print)');
    const headers = Array.from(headerCells).map(cell => cell.textContent.trim());
    rows.push(headers.join(','));
    
    // Get data rows
    const bodyRows = table.querySelectorAll('tbody tr');
    bodyRows.forEach(row => {
        const cells = row.querySelectorAll('td:not(.no-print)');
        const rowData = Array.from(cells).map(cell => {
            let text = cell.textContent.trim();
            // Handle payment formatting and escape commas
            if (text.includes('₱')) {
                text = text.replace('₱', '').trim();
            }
            // Escape commas and quotes in CSV
            if (text.includes(',') || text.includes('"')) {
                text = `"${text.replace(/"/g, '""')}"`;
            }
            return text;
        });
        rows.push(rowData.join(','));
    });
    
    // Create and download CSV
    const csvContent = rows.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    
    // Generate filename with current date
    const now = new Date();
    const filename = `Dormitory_Rooms_${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}.csv`;
    
    // Create download link
    const link = document.createElement('a');
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Show success message
    showExportMessage('CSV file downloaded successfully!', 'success');
    });
}

// Show export success message
function showExportMessage(message, type = 'success') {
    // Remove existing messages
    const existingMessage = document.getElementById('exportMessage');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.id = 'exportMessage';
    messageDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 ${
        type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'
    }`;
    messageDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
            ${message}
        </div>
    `;
    
    document.body.appendChild(messageDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        messageDiv.style.opacity = '0';
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 300);
    }, 3000);
}

// Add some animations for better UX
function initializeAdminRoomsPage() {
    const exportButtons = document.querySelectorAll('[onclick^="export"], [onclick="printTable()"]');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.getAttribute('onclick') !== 'printTable()') {
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
                this.disabled = true;

                setTimeout(() => {
                    this.innerHTML = originalContent;
                    this.disabled = false;
                }, 1500);
            }
        });
    });

    const editButtons = document.querySelectorAll('.editRoomBtn');
    editButtons.forEach(button => {
        button.addEventListener('click', () => openEditModal(button));
    });

    document.addEventListener('click', function(event) {
        const targetButton = event.target ? event.target.closest('.editRoomBtn') : null;
        if (!targetButton) {
            return;
        }

        event.preventDefault();
        openEditModal(targetButton);
    });

    if (editCancelButton) {
        editCancelButton.addEventListener('click', closeEditModal);
    }

    if (editCloseButton) {
        editCloseButton.addEventListener('click', closeEditModal);
    }

    if (editModal) {
        editModal.addEventListener('click', function(event) {
            if (event.target === editModal) {
                closeEditModal();
            }
        });
    }

    if (editPictureInput) {
        editPictureInput.addEventListener('change', function() {
            const files = this.files;
            const file = files && files[0] ? files[0] : null;

            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const result = event && event.target ? event.target.result : '';
                    updateEditPicturePreview(result);
                };
                reader.readAsDataURL(file);

                if (currentPictureNameLabel) {
                    currentPictureNameLabel.textContent = `Selected file: ${file.name}`;
                }
            } else {
                const fallbackUrl = editModal && editModal.dataset ? editModal.dataset.currentPictureUrl || '' : '';
                const fallbackName = editModal && editModal.dataset ? editModal.dataset.currentPictureName || '' : '';

                updateEditPicturePreview(fallbackUrl);

                if (currentPictureNameLabel) {
                    currentPictureNameLabel.textContent = fallbackName ? `Current file: ${fallbackName}` : 'No picture uploaded for this room.';
                }
            }
        });
    }

    document.querySelectorAll('.deleteRoomForm').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formRef = this;
            const roomNumber = formRef.dataset.roomNumber || '';
            const message = roomNumber ? `Delete Room #${roomNumber}? This action cannot be undone.` : 'Delete this room? This action cannot be undone.';

            showConfirmation(message, () => {
                performRoomDeletion(formRef);
            });
        });
    });

    if (editForm) {
        editForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton && !submitButton.dataset.originalText) {
                submitButton.dataset.originalText = submitButton.innerHTML;
            }

            if (submitButton) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Saving...</span>';
                submitButton.disabled = true;
            }

            const formData = new FormData(this);

            if (!formData.get('id') && this.dataset.roomId) {
                formData.set('id', this.dataset.roomId);
            }

            if (formData.has('payment')) {
                const normalizedPayment = parsePaymentValue(formData.get('payment'));
                formData.set('payment', String(normalizedPayment));

                const monthlyRateValue = formData.get('monthly_rate');
                if (!monthlyRateValue || String(monthlyRateValue).trim() === '') {
                    formData.set('monthly_rate', String(normalizedPayment));
                }
            }
            const requestUrl = buildRequestUrl(this);
            let payload = null;
            let failureMessage = 'Unable to update room.';

            try {
                try {
                    const { response, payload: attemptPayload } = await submitRoomUpdateRequest(requestUrl, formData);

                    if (response.ok && attemptPayload && attemptPayload.success === true) {
                        payload = attemptPayload;
                    } else if (attemptPayload && typeof attemptPayload.message === 'string') {
                        failureMessage = attemptPayload.message;
                    } else {
                        failureMessage = `Unable to update room. (${response.status})`;
                    }
                } catch (networkError) {
                    const message = networkError instanceof Error ? networkError.message : null;
                    if (message) {
                        failureMessage = message;
                    }
                }

                if (!payload) {
                    throw new Error(failureMessage);
                }

                const roomPayload = payload.room || null;
                if (roomPayload) {
                    updateRoomRow(roomPayload);

                    editForm.dataset.roomId = roomPayload.id != null ? String(roomPayload.id) : '';
                    if (editRoomIdInput) {
                        editRoomIdInput.value = roomPayload.id != null ? String(roomPayload.id) : '';
                    }
                    if (roomPayload.update_path) {
                        editForm.dataset.actionPath = roomPayload.update_path;
                    }

                    const persistedUrl = roomPayload.absolute_update_url || requestUrl;
                    editForm.action = persistedUrl;
                    editForm.dataset.originalAction = persistedUrl;
                    editForm.dataset.actionFull = persistedUrl;
                    editForm.dataset.submitUrl = persistedUrl;

                    if (editExistingPictureInput) {
                        editExistingPictureInput.value = roomPayload.picture || '';
                    }

                    if (editModal) {
                        if (roomPayload.picture_url) {
                            editModal.dataset.currentPictureUrl = roomPayload.picture_url;
                        } else {
                            delete editModal.dataset.currentPictureUrl;
                        }

                        if (roomPayload.picture_name) {
                            editModal.dataset.currentPictureName = roomPayload.picture_name;
                        } else {
                            delete editModal.dataset.currentPictureName;
                        }
                    }

                    if (currentPictureNameLabel) {
                        currentPictureNameLabel.textContent = roomPayload.picture_name ? `Current file: ${roomPayload.picture_name}` : 'No picture uploaded for this room.';
                    }

                    updateEditPicturePreview(roomPayload.picture_url || '');
                }

                showExportMessage(payload.message || 'Dormitory room updated successfully!', 'success');
                closeEditModal();
            } catch (error) {
                const fallbackMessage = error instanceof Error ? error.message : 'Unable to update room.';
                showExportMessage(fallbackMessage, 'error');
            } finally {
                if (submitButton) {
                    const original = submitButton.dataset.originalText || '<i class="fas fa-save"></i><span> Save Changes</span>';
                    submitButton.innerHTML = original;
                    submitButton.disabled = false;
                }
            }
        });
    }

    const successToastData = document.getElementById('roomsSuccessToastData');
    if (successToastData) {
        showExportMessage(successToastData.dataset.message || 'Changes saved successfully!', 'success');
    }

    const errorToastData = document.getElementById('roomsErrorToastData');
    if (errorToastData) {
        showExportMessage(errorToastData.dataset.message || 'Something went wrong.', 'error');
    }
}

function startCurrentTimeClock() {
    const target = document.getElementById('currentTime');
    if (!target) {
        return;
    }

    const formatter = (typeof Intl !== 'undefined' && typeof Intl.DateTimeFormat === 'function')
        ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' })
        : null;

    const updateClock = () => {
        const now = new Date();
        target.textContent = formatter ? formatter.format(now) : now.toLocaleString();
    };

    updateClock();
    setInterval(updateClock, 60000);
}

function bootRoomsPage() {
    initializeAdminRoomsPage();
    startCurrentTimeClock();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootRoomsPage);
} else {
    bootRoomsPage();
}
</script>

</body>
</html>