<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;
$paymentAlert = $paymentAlert ?? ['show' => false];

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

if (!function_exists('normalize_room_price')) {
    /**
     * Convert various price formats (e.g., "₱1,500.00") into a float.
     */
    function normalize_room_price($value): float {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = preg_replace('/[^0-9.]/', '', $value);
            if ($normalized === '' || $normalized === '.') {
                return 0.0;
            }
            // Handle multiple decimal points by keeping the first and removing the rest.
            $parts = explode('.', $normalized);
            if (count($parts) > 2) {
                $normalized = array_shift($parts) . '.' . implode('', $parts);
            }
            return (float) $normalized;
        }

        return 0.0;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tenant Dashboard - Dormitory</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php 
// Dark mode CSS embedded directly
?>
<style>
  /* Sidebar styles */
  #sidebar {
    transition: width 0.3s ease, transform 0.3s ease;
    background: #D2B48C; /* warm tan */
  }
  #sidebar.collapsed {
    width: 4rem;
  }
  #sidebar.collapsed nav a span {
    display: none;
  }
  #sidebar.collapsed nav a {
    justify-content: center;
  }
  @media (max-width: 768px) {
    #sidebar {
      transform: translateX(-100%);
      position: fixed;
      z-index: 50;
    }
    #sidebar.open {
      transform: translateX(0);
    }
  }
  
  /* Dark mode styles */
  .dark #sidebar {
    background: #1a1a1a !important;
  }
  .dark body {
    background: #111111 !important;
  }
  .dark .main-content, .dark .content-area {
    background: #1a1a1a !important;
    color: #e5e5e5 !important;
  }
  .dark .user-card {
    background: #2a2a2a !important;
    border-color: #404040 !important;
    color: #e5e5e5 !important;
  }
  .dark input, .dark select, .dark textarea {
    background: #333333 !important;
    border-color: #555555 !important;
    color: #e5e5e5 !important;
  }
  .dark h1, .dark h2, .dark h3, .dark h4, .dark h5, .dark h6, .dark label, .dark p {
    color: #e5e5e5 !important;
  }
  .dark #sidebar a {
    color: #e5e5e5 !important;
  }
  .dark .header-section {
    background: #2a2a2a !important;
  }
    .dark [class*="bg-[#FFF5E1]"], .dark [class*="bg-white"] {
        background: #1f1f1f !important;
    }
    .dark [class*="border-[#C19A6B]"], .dark [class*="border-[#E5D3B3]"] {
        border-color: #3a3a3a !important;
    }
    .dark [class*="text-[#5C4033]"],
    .dark [class*="text-gray-600"],
    .dark [class*="text-gray-500"],
    .dark [class*="text-[#5c4033]"] {
        color: #e5e5e5 !important;
    }
    .dark [class*="text-[#C19A6B]"] {
        color: #f2c17d !important;
    }
  
  /* Dynamic message animations */
  .dynamic-message {
    animation: slideInFromTop 0.3s ease-out;
    z-index: 1000;
  }
  
  @keyframes slideInFromTop {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  .dark .header-section {
    background: #1a1a1a !important;
    color: #e5e5e5 !important;
  }
  
  /* Sidebar collapsed text hiding */
  #sidebar.collapsed .sidebar-text {
    display: none;
  }
  
  /* Modern Professional Enhancements */
  .user-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(193, 154, 107, 0.2);
    backdrop-filter: blur(5px);
  }
  .user-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(193, 154, 107, 0.15);
    border-color: #C19A6B;
  }
  
  /* Enhanced grid spacing for full-width */
  .rooms-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    width: 100%;
  }
  
  @media (min-width: 1024px) {
    .rooms-grid-modern {
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
  }
  
  @media (min-width: 1280px) {
    .rooms-grid-modern {
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
  }
  
  @media (min-width: 1536px) {
    .rooms-grid-modern {
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
  }
  
  /* Professional button hover effects */
  .btn-modern {
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .btn-modern:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-1px);
  }
  
  /* Modern card styling */
  .card-modern {
    background: rgba(255, 245, 225, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(193, 154, 107, 0.2);
  }
  
  /* Smooth scrollbars */
  ::-webkit-scrollbar {
    width: 6px;
  }
  ::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
  }
  ::-webkit-scrollbar-thumb {
    background: #C19A6B;
    border-radius: 3px;
  }
  ::-webkit-scrollbar-thumb:hover {
    background: #B07A4B;
  }
</style>
</head>
<body class="min-h-screen transition-colors<?= $darkModeEnabled ? ' dark' : '' ?>">

<?php if (!empty($paymentAlert['show'])): ?>
<div id="paymentAlertModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center px-4">
    <div class="bg-[#FFF5E1] w-full max-w-md rounded-xl shadow-2xl border-2 border-[#C19A6B] p-6 relative">
        <button id="paymentAlertDismiss" class="absolute top-3 right-3 text-[#5C4033] hover:text-red-600" aria-label="Close alert">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
        <div class="flex items-center gap-3 mb-4">
            <div class="bg-red-100 text-red-600 w-12 h-12 flex items-center justify-center rounded-full">
                <i class="fa-solid fa-circle-exclamation text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-[#5C4033]">Incomplete Payment Alert</h2>
                <p class="text-sm text-[#5C4033] opacity-80">We noticed your payment for <?= htmlspecialchars($paymentAlert['month_label'] ?? date('F Y')) ?> is not fully settled.</p>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-[#E5D3B3] p-4 mb-4">
            <ul class="space-y-2 text-sm text-[#5C4033]">
                <li class="flex justify-between"><span>Amount due:</span><span class="font-semibold text-red-600">₱<?= number_format((float)($paymentAlert['due_amount'] ?? 0), 2) ?></span></li>
                <li class="flex justify-between"><span>Paid this month:</span><span class="font-semibold text-green-600">₱<?= number_format((float)($paymentAlert['paid_amount'] ?? 0), 2) ?></span></li>
                <li class="flex justify-between"><span>Pending for approval:</span><span class="font-semibold text-yellow-600">₱<?= number_format((float)($paymentAlert['pending_amount'] ?? 0), 2) ?></span></li>
                <li class="flex justify-between border-t border-[#E5D3B3] pt-2"><span>Still outstanding:</span><span class="font-semibold text-red-700">₱<?= number_format((float)($paymentAlert['remaining_amount'] ?? 0), 2) ?></span></li>
            </ul>
        </div>
        <p class="text-sm text-[#5C4033] opacity-80 mb-5">Please complete your payment today to avoid penalties or access restrictions.</p>
        <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
            <button id="paymentAlertLater" class="w-full sm:w-auto px-4 py-2 rounded-lg border border-[#C19A6B] text-[#5C4033] font-semibold hover:bg-[#F6EDE0] transition">
                Remind Me Later
            </button>
            <a href="<?= site_url('user/payments') ?>" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-[#C19A6B] text-white font-semibold hover:bg-[#5C4033] transition text-center">
                Go to Payment
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-40 shadow-lg">
  <div class="flex items-center gap-3 mb-8">
    <div class="bg-[#C19A6B] p-2 rounded-lg">
      <i class="fa-solid fa-graduation-cap text-2xl text-white"></i>
    </div>
    <div class="sidebar-text">
      <h2 class="text-lg font-bold"><?= htmlspecialchars($userName ?? 'Tenant') ?></h2>
      <p class="text-sm text-[#5C4033] opacity-75">Tenant Portal</p>
    </div>
  </div>
  
  <nav class="flex flex-col gap-2">
    <a href="<?= site_url('user_landing') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-home"></i> <span>Dashboard</span>
    </a>
    <a href="<?= site_url('user/reservations') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-list-check"></i> <span>My Reservations</span>
    </a>
    <a href="<?= site_url('user/payments') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-credit-card"></i> <span>Payment History</span>
    </a>
    <a href="<?= site_url('user/maintenance') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-wrench"></i> <span>Maintenance</span>
    </a>
    <a href="<?= site_url('user/announcements') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-bullhorn"></i> <span>Announcements</span>
    </a>
    <a href="<?= site_url('user/profile') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-user"></i> <span>Profile</span>
    </a>
    <a href="<?= site_url('user/contact') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-envelope"></i> <span>Contact Admin</span>
    </a>
    <hr class="border-[#5C4033] border-opacity-20 my-4">
    <div class="px-4 py-2 text-xs text-[#5C4033] opacity-75">
      <i class="fa-solid fa-phone mr-2"></i>
      <span class="sidebar-text">Contact: 09517394938</span>
    </div>
    <a href="#" onclick="confirmLogout()" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-400 hover:text-white transition">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

<!-- Main Content -->
<div class="flex-1 transition-all duration-300" id="mainContent" style="margin-left: 16rem;">
  <!-- Header -->
  <div style="background: #FFF5E1;" class="shadow-md flex items-center justify-between px-6 py-4 header-section">
    <div class="flex items-center gap-4">
      <button id="sidebarToggle" class="text-[#5C4033] text-xl hover:bg-[#C19A6B] hover:text-white p-2 rounded-lg transition-all">
        <i class="fa-solid fa-bars" id="toggleIcon"></i>
      </button>
      <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div>
        <h1 class="font-bold text-xl text-[#5C4033]">Available Rooms</h1>
        <p class="text-[#5C4033] opacity-75 text-sm">Find and request your perfect room</p>
      </div>
    </div>
        <div class="flex items-center gap-4 flex-wrap justify-end">
            <div class="flex items-center gap-2 text-xs text-[#5C4033] opacity-75 dark:text-gray-300 dark:opacity-100">
                <i class="fa-solid fa-phone"></i>
                <span>09517394938</span>
            </div>
            <?php if(isset($pendingCount) && $pendingCount > 0): ?>
                <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold dark:bg-yellow-900 dark:text-yellow-200">
                    <i class="fa-solid fa-clock"></i> <?= $pendingCount ?> Pending
                </div>
            <?php endif; ?>
        </div>
  </div>

  <div class="w-full px-6 py-6">

    <!-- Success / Error Messages -->
    <?php if(!empty($success)): ?>
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-lg mb-6 text-center shadow-sm">
            <i class="fa-solid fa-check-circle text-lg"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-lg mb-6 text-center shadow-sm">
            <i class="fa-solid fa-exclamation-circle text-lg"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <?php
        $openRooms = $availableRoomsCount ?? 0;
        $openBeds = $availableSpacesCount ?? 0;
        $totalRoomsDisplay = $totalRoomsCount ?? count($rooms);
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="card-modern p-6 rounded-xl shadow-sm border user-card btn-modern" style="border-color: #C19A6B;">
            <div class="flex items-center gap-4">
                <div class="bg-[#C19A6B] p-3 rounded-lg">
                    <i class="fa-solid fa-bed text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[#5C4033]">Open Rooms</h3>
                    <p class="text-2xl font-bold text-[#C19A6B]"><?= $openRooms ?></p>
                    <p class="text-xs text-[#5C4033] opacity-75"><?= $openBeds ?> open bed<?= $openBeds === 1 ? '' : 's' ?> • <?= $totalRoomsDisplay ?> total rooms</p>
                </div>
            </div>
        </div>
        <div class="card-modern p-6 rounded-xl shadow-sm border user-card btn-modern" style="border-color: #C19A6B;">
            <div class="flex items-center gap-4">
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <i class="fa-solid fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[#5C4033]">Pending Requests</h3>
                    <p class="text-2xl font-bold text-yellow-600"><?= $pendingCount ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div class="card-modern p-6 rounded-xl shadow-sm border user-card btn-modern" style="border-color: #C19A6B;">
            <div class="flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fa-solid fa-shield-alt text-green-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[#5C4033]">Security</h3>
                    <p class="text-sm font-semibold text-green-600">24/7 Protected</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Overview (only show if user has current reservation) -->
    <?php if (!empty($currentReservation)): ?>
    <div class="bg-white p-6 rounded-xl shadow-lg border border-[#E5D3B3] mb-8">
        <h2 class="text-xl font-bold text-[#5C4033] mb-6 flex items-center gap-2">
            <i class="fa-solid fa-gauge"></i>
            My Dashboard Overview
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Current Room Status -->
            <div class="bg-[#FFF5E1] p-4 rounded-lg border border-[#E5D3B3]">
                <h3 class="font-semibold text-[#5C4033] mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-home"></i>
                    Current Room
                </h3>
                <div class="space-y-2">
                    <p class="text-sm text-gray-600">Room: <span class="font-semibold text-[#5C4033]"><?= htmlspecialchars($currentReservation['room_number']) ?></span></p>
                    <p class="text-sm text-gray-600">Monthly Rent: <span class="font-semibold text-[#C19A6B]">₱<?= number_format($currentReservation['monthly_rate'], 2) ?></span></p>
                    <div class="mt-2">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                          <?php
                          switch($currentReservation['payment_status']) {
                            case 'Overdue': echo 'bg-red-100 text-red-800'; break;
                            case 'Due Soon': echo 'bg-yellow-100 text-yellow-800'; break;
                            case 'Up to Date': echo 'bg-green-100 text-green-800'; break;
                            default: echo 'bg-gray-100 text-gray-800'; break;
                          }
                          ?>">
                            <?= htmlspecialchars($currentReservation['payment_status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-[#FFF5E1] p-4 rounded-lg border border-[#E5D3B3]">
                <h3 class="font-semibold text-[#5C4033] mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-bolt"></i>
                    Quick Actions
                </h3>
                <div class="space-y-2">
                    <a href="<?= site_url('user/payments') ?>" 
                       class="block bg-[#C19A6B] hover:bg-[#5C4033] text-white px-3 py-2 rounded text-sm font-semibold transition text-center">
                        <i class="fa-solid fa-credit-card"></i> View Payments
                    </a>
                    <a href="<?= site_url('user/maintenance') ?>" 
                       class="block bg-[#C19A6B] hover:bg-[#5C4033] text-white px-3 py-2 rounded text-sm font-semibold transition text-center">
                        <i class="fa-solid fa-wrench"></i> Report Issue
                    </a>
                    <a href="<?= site_url('user/contact') ?>" 
                       class="block bg-[#C19A6B] hover:bg-[#5C4033] text-white px-3 py-2 rounded text-sm font-semibold transition text-center">
                        <i class="fa-solid fa-envelope"></i> Contact Admin
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-[#FFF5E1] p-4 rounded-lg border border-[#E5D3B3]">
                <h3 class="font-semibold text-[#5C4033] mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-history"></i>
                    Recent Activity
                </h3>
                <div class="space-y-3">
                    <?php if (!empty($recentPayments)): ?>
                        <?php foreach (array_slice($recentPayments, 0, 2) as $payment): ?>
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <p class="font-semibold text-[#5C4033]">Payment</p>
                                <p class="text-xs text-gray-600"><?= date('M j', strtotime($payment['payment_date'])) ?></p>
                            </div>
                            <span class="text-[#C19A6B] font-semibold">₱<?= number_format($payment['amount'], 0) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <a href="<?= site_url('user/payments') ?>" class="text-xs text-[#C19A6B] hover:text-[#5C4033] font-semibold">
                            View all payments →
                        </a>
                    <?php else: ?>
                        <p class="text-sm text-gray-600">No recent payments</p>
                        <a href="<?= site_url('user/payments') ?>" class="text-xs text-[#C19A6B] hover:text-[#5C4033] font-semibold">
                            View payment history →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="mb-6">
      <div class="relative">
        <input type="text" id="searchInput" placeholder="Search rooms by number, type, or price..." class="w-full px-4 py-3 pl-10 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1] text-[#5C4033]">
        <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-[#C19A6B]"></i>
      </div>
    </div>

    <!-- Rooms Grid -->
    <div class="rooms-grid-modern" id="roomsGrid">
        <?php if(!empty($rooms)): ?>
            <?php foreach($rooms as $room): ?>
                <?php
                    $pictureMeta = resolve_room_picture_paths($room['picture'] ?? '', $room['picture_hash'] ?? '');
                    $hasPicture = $pictureMeta['has_picture'];
                    $pictureUrl = $pictureMeta['web_path'];
                    $availableSlots = isset($room['available']) ? max(0, (int) $room['available']) : 0;
                    $roomIsAvailable = $availableSlots > 0;
                    $roomDisplayName = trim((string)($room['display_name'] ?? ($room['room_name'] ?? 'Room')));
                    $roomNumberDisplay = trim((string)($room['display_number'] ?? ($room['room_number'] ?? '')));
                    if ($roomNumberDisplay === '' && $roomDisplayName !== '') {
                        if (preg_match('/(\d+)/', $roomDisplayName, $numberMatch)) {
                            $roomNumberDisplay = ltrim($numberMatch[1], '0');
                            if ($roomNumberDisplay === '') {
                                $roomNumberDisplay = '0';
                            }
                        }
                    }
                    if ($roomNumberDisplay === '' && isset($room['id'])) {
                        $roomNumberDisplay = (string) $room['id'];
                    }
                    $roomNumberLabel = $roomNumberDisplay !== '' ? 'Room #' . $roomNumberDisplay : '';
                    $priceCandidates = [
                        $room['display_price'] ?? null,
                        $room['monthly_rate'] ?? null,
                        $room['payment'] ?? null,
                    ];
                    $rawPrice = 0.0;
                    foreach ($priceCandidates as $candidate) {
                        $candidateValue = normalize_room_price($candidate);
                        if ($candidateValue > 0) {
                            $rawPrice = $candidateValue;
                            break;
                        }
                    }
                ?>
                <div class="card-modern p-4 rounded-lg shadow-sm border hover:shadow-md transition-all relative user-card room-card btn-modern" style="border-color: #C19A6B;">
                    <div class="absolute top-3 right-3">
                        <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold <?= $roomIsAvailable ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?= $roomIsAvailable ? 'Available' : 'Not Available'; ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <?php if ($hasPicture): ?>
                            <img src="<?= htmlspecialchars($pictureUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Room <?= htmlspecialchars($room['room_number']); ?> picture" class="w-full h-40 object-cover rounded-lg border border-[#C19A6B] shadow-sm">
                        <?php else: ?>
                            <div class="w-full h-40 flex items-center justify-center rounded-lg border border-dashed border-[#C19A6B] text-sm text-[#5C4033] opacity-70 bg-[#fdf9f3]">
                                <i class="fa-solid fa-bed mr-2"></i> No image available
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <h2 class="text-lg font-bold text-[#5C4033] mb-1 room-number"><?= htmlspecialchars($roomDisplayName) ?></h2>
                        <?php if ($roomNumberLabel !== '' && stripos($roomDisplayName, $roomNumberDisplay) === false): ?>
                            <p class="text-xs text-[#5C4033] opacity-70 mb-1"><?= htmlspecialchars($roomNumberLabel) ?></p>
                        <?php endif; ?>
                        <p class="text-[#5C4033] opacity-75 text-xs mb-1">
                            <i class="fa-solid fa-bed text-[#C19A6B]"></i> 
                            <?= $room['beds'] ?? 'N/A' ?> Bed<?= ($room['beds'] ?? 0) > 1 ? 's' : '' ?>
                        </p>
                        <p class="text-[#5C4033] opacity-75 text-xs mb-1">
                            <i class="fa-solid fa-users text-[#C19A6B]"></i> 
                            <?= $availableSlots ?> Space<?= $availableSlots === 1 ? '' : 's' ?> Available
                        </p>
                        <p class="text-[#5C4033] opacity-75 text-xs mb-1">
                            <i class="fa-solid fa-door-open text-[#C19A6B]"></i> 
                            <?php if ($roomIsAvailable): ?>
                                <strong><?= $availableSlots ?> Room<?= $availableSlots === 1 ? '' : 's' ?> can be reserved</strong>
                            <?php else: ?>
                                <span class="font-semibold text-red-600">Fully booked</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-[#5C4033] opacity-75 text-xs mb-3 room-type">
                            <i class="fa-solid fa-tag text-[#C19A6B]"></i> 
                            Dormitory Room
                        </p>
                        <div class="bg-[#e6ddd4] p-2 rounded-lg mb-3">
                            <p class="text-lg font-bold text-[#5C4033] room-payment">₱<?= number_format($rawPrice, 2) ?></p>
                            <p class="text-[#5C4033] opacity-75 text-xs">per month</p>
                        </div>
                    </div>

                    <?php if ($roomIsAvailable): ?>
                        <!-- Confirmation Message Area -->
                        <div id="confirm-msg-<?= $room['id'] ?>" class="hidden bg-yellow-50 border border-yellow-200 p-2 rounded-lg mb-2">
                            <p class="text-yellow-800 text-xs mb-2">
                                <i class="fa-solid fa-question-circle"></i> 
                                How many rooms in Room #<?= htmlspecialchars($roomNumberDisplay !== '' ? $roomNumberDisplay : ($room['room_number'] ?? '')) ?>?
                            </p>
                            <div class="mb-2">
                                <label class="block text-yellow-700 text-xs font-semibold mb-1">Quantity:</label>
                                <div class="flex items-center gap-1 justify-center">
                                    <button type="button" onclick="decreaseQuantity(<?= $room['id'] ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white w-6 h-6 rounded flex items-center justify-center text-xs">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="number" id="quantity-<?= $room['id'] ?>" value="1" min="1" max="<?= $availableSlots ?>" class="w-12 text-center border border-yellow-300 rounded px-1 py-1 text-xs" onchange="validateQuantity(<?= $room['id'] ?>, <?= $availableSlots ?>)">
                                    <button type="button" onclick="increaseQuantity(<?= $room['id'] ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white w-6 h-6 rounded flex items-center justify-center text-xs">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                                <span class="text-yellow-700 text-xs block text-center mt-1">of <?= $availableSlots ?> available</span>
                            </div>
                            <div class="flex gap-1">
                                <button onclick="submitReservationRequest(<?= $room['id'] ?>)" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs">
                                    <i class="fa-solid fa-check"></i> Reserve
                                </button>
                                <button onclick="cancelReservation(<?= $room['id'] ?>)" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs">
                                    <i class="fa-solid fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['user'])): ?>
                        <?php if($roomIsAvailable): ?>
                            <?php
                                $reservationEndpoint = site_url('user/reserve');
                                $endpointParts = parse_url($reservationEndpoint);
                                $endpointPath = $endpointParts['path'] ?? '/user/reserve';
                                $endpointQuery = !empty($endpointParts['query']) ? '?' . $endpointParts['query'] : '';
                                $reservationRelativeEndpoint = $endpointPath . $endpointQuery;
                            ?>
                            <form
                                method="POST"
                                action="<?= htmlspecialchars($reservationRelativeEndpoint, ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full"
                                id="reservation-form-<?= $room['id'] ?>"
                                data-reservation-endpoint="<?= htmlspecialchars($reservationRelativeEndpoint, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <input type="hidden" name="room_id" value="<?= (int) $room['id']; ?>">
                                <input type="hidden" name="quantity" value="1" id="quantity-hidden-<?= $room['id'] ?>">
                                <button type="button" onclick="showConfirmation(<?= $room['id'] ?>)" class="w-full text-white py-2 px-3 rounded-lg font-semibold transition-all hover:bg-[#B07A4B] text-sm" style="background: #C19A6B;" id="reserve-btn-<?= $room['id'] ?>">
                                    <i class="fa-solid fa-paper-plane"></i> Request Reservation
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="w-full bg-gray-300 text-gray-500 py-2 px-3 rounded-lg font-semibold cursor-not-allowed text-sm" disabled>
                                <i class="fa-solid fa-ban"></i> Not Available
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= site_url('auth/login') ?>" class="block w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-3 rounded-lg font-semibold text-center transition-all text-sm">
                            <i class="fa-solid fa-sign-in-alt"></i> Login to Reserve
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full bg-white p-12 rounded-xl shadow-sm border text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fa-solid fa-bed text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Rooms Available</h3>
                <p class="text-gray-500">All rooms are currently occupied. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
  </div>
</div>

<!-- Mobile sidebar overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

<script>
// Sidebar toggle functionality
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const toggleIcon = document.getElementById('toggleIcon');
const roomsGrid = document.getElementById('roomsGrid');

function normalizeActionUrl(rawUrl) {
    if (!rawUrl) {
        return '';
    }
    try {
        const parsed = new URL(rawUrl, window.location.href);
        parsed.protocol = window.location.protocol;
        parsed.host = window.location.host;
        return parsed.href;
    } catch (error) {
        console.warn('Unable to normalize reservation action URL', rawUrl, error);
        return rawUrl;
    }
}

// Check for saved sidebar state
const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
if (isSidebarCollapsed) {
    sidebar.classList.add('collapsed');
    mainContent.style.marginLeft = '4rem';
    toggleIcon.className = 'fa-solid fa-times';
    updateGridColumns(true);
}

if (sidebarToggle) {
  sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      const isCollapsed = sidebar.classList.contains('collapsed');
      
      // Update main content margin
      mainContent.style.marginLeft = isCollapsed ? '4rem' : '16rem';
      
      // Update toggle icon
      toggleIcon.className = isCollapsed ? 'fa-solid fa-times' : 'fa-solid fa-bars';
      
      // Save state
      localStorage.setItem('sidebarCollapsed', isCollapsed);
      
      // Update grid columns
      updateGridColumns(isCollapsed);
  });
}

// Mobile menu functionality
const menuBtn = document.getElementById('menuBtn');
const mobileOverlay = document.getElementById('mobileOverlay');

if (menuBtn) {
  menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (mobileOverlay) mobileOverlay.classList.toggle('hidden');
  });
}

if (mobileOverlay) {
  mobileOverlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      mobileOverlay.classList.add('hidden');
  });
}

// Update grid columns based on sidebar state
function updateGridColumns(isCollapsed) {
    if (roomsGrid) {
        if (isCollapsed) {
            roomsGrid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6';
        } else {
            roomsGrid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6';
        }
    }
}
    </script>

    <script>
    // Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const roomCards = document.querySelectorAll('.room-card');
            
            roomCards.forEach(card => {
                const roomNumber = card.querySelector('.room-number')?.textContent.toLowerCase() || '';
                const roomType = card.querySelector('.room-type')?.textContent.toLowerCase() || '';
                const payment = card.querySelector('.room-payment')?.textContent.toLowerCase() || '';
                
                if (roomNumber.includes(searchTerm) || roomType.includes(searchTerm) || payment.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

});

// Confirmation dialog for reservations
function confirmReservation(roomId, roomNumber) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-[#FFF5E1] p-6 rounded-lg max-w-md mx-4" style="border: 2px solid #C19A6B;">
                <h3 class="text-lg font-bold mb-4 text-[#5C4033]">Confirm Reservation</h3>
                <p class="mb-6 text-[#5C4033] opacity-75">Are you sure you want to submit a reservation request for Room #${roomNumber}?</p>
                <div class="flex gap-3 justify-end">
                    <button id="cancelBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                    <button id="confirmBtn" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.querySelector('#confirmBtn').onclick = () => {
            document.body.removeChild(modal);
            resolve(true);
        };
        
        modal.querySelector('#cancelBtn').onclick = () => {
            document.body.removeChild(modal);
            resolve(false);
        };
        
        modal.onclick = (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
                resolve(false);
            }
        };
    });
}

// Function to show messages on the same page
function showMessage(message, type) {
    // Remove any existing messages
    const existingMessages = document.querySelectorAll('.dynamic-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `dynamic-message border rounded-lg p-4 mb-6 text-center shadow-sm ${type === 'success' ? 'bg-green-100 border-green-200 text-green-700' : 'bg-red-100 border-red-200 text-red-700'}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    messageDiv.innerHTML = `<i class="fa-solid ${icon} text-lg"></i> ${message}`;
    
    // Insert message at the top of the main content
    const mainContent = document.querySelector('.w-full.px-6.py-6');
    if (mainContent) {
        mainContent.insertBefore(messageDiv, mainContent.firstChild);
        
        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.style.transition = 'all 0.3s ease';
                messageDiv.style.opacity = '0';
                messageDiv.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
}

// Function to show inline confirmation
function showConfirmation(roomId) {
    // Hide the reserve button and show confirmation message
    const reserveBtn = document.getElementById(`reserve-btn-${roomId}`);
    const confirmMsg = document.getElementById(`confirm-msg-${roomId}`);
    
    if (reserveBtn && confirmMsg) {
        reserveBtn.style.display = 'none';
        confirmMsg.classList.remove('hidden');
    }
}

// Function to validate quantity input
function validateQuantity(roomId, maxAvailable) {
    const quantityInput = document.getElementById(`quantity-${roomId}`);
    let value = parseInt(quantityInput.value);
    
    if (isNaN(value) || value < 1) {
        quantityInput.value = 1;
    } else if (value > maxAvailable) {
        quantityInput.value = maxAvailable;
    }
}

// Function to increase quantity
function increaseQuantity(roomId) {
    const quantityInput = document.getElementById(`quantity-${roomId}`);
    const max = parseInt(quantityInput.getAttribute('max'));
    const current = parseInt(quantityInput.value);
    
    if (current < max) {
        quantityInput.value = current + 1;
    }
}

// Function to decrease quantity
function decreaseQuantity(roomId) {
    const quantityInput = document.getElementById(`quantity-${roomId}`);
    const current = parseInt(quantityInput.value);
    
    if (current > 1) {
        quantityInput.value = current - 1;
    }
}

// Function to cancel reservation
function cancelReservation(roomId) {
    // Show the reserve button and hide confirmation message
    const reserveBtn = document.getElementById(`reserve-btn-${roomId}`);
    const confirmMsg = document.getElementById(`confirm-msg-${roomId}`);
    
    if (reserveBtn && confirmMsg) {
        reserveBtn.style.display = 'block';
        confirmMsg.classList.add('hidden');
    }
}

// Function to submit reservation after inline confirmation
async function submitReservationRequest(roomId) {
    const form = document.getElementById(`reservation-form-${roomId}`);
    const confirmMsg = document.getElementById(`confirm-msg-${roomId}`);
    const quantityInput = document.getElementById(`quantity-${roomId}`);
    const quantityHidden = document.getElementById(`quantity-hidden-${roomId}`);
    const quantity = quantityInput ? Math.max(1, parseInt(quantityInput.value, 10) || 1) : 1;
    const reserveBtn = document.getElementById(`reserve-btn-${roomId}`);

    if (!form) {
        showMessage('Unable to prepare the reservation request. Please refresh and try again.', 'error');
        return;
    }

    if (quantityHidden) {
        quantityHidden.value = quantity;
    }

    if (confirmMsg) {
        if (!confirmMsg.dataset.originalContent) {
            confirmMsg.dataset.originalContent = confirmMsg.innerHTML;
        }
        confirmMsg.classList.remove('hidden');
        confirmMsg.classList.remove('bg-yellow-50', 'border-yellow-200');
        confirmMsg.classList.add('bg-blue-50', 'border-blue-200');
        confirmMsg.innerHTML = `
            <p class="text-blue-800 text-sm flex items-center gap-2 justify-center">
                <i class="fa-solid fa-spinner fa-spin"></i>
                Processing your reservation for <strong>${quantity}</strong> room${quantity > 1 ? 's' : ''}...
            </p>
        `;
    }

    const endpoint = form.getAttribute('data-reservation-endpoint') || form.getAttribute('action') || '';

    if (!endpoint) {
        if (confirmMsg) {
            confirmMsg.classList.remove('bg-blue-50', 'border-blue-200');
            confirmMsg.classList.add('bg-red-50', 'border-red-200');
            confirmMsg.innerHTML = '<p class="text-red-700 text-sm">Unable to determine the reservation endpoint. Please refresh and try again.</p>';
        }
        return;
    }

    const payload = new URLSearchParams();
    payload.set('room_id', roomId);
    payload.set('quantity', quantity);

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: payload.toString()
        });

        const rawText = await response.text();
        if (!response.ok) {
            console.error('Reservation confirm failed', response.status, rawText);
            showMessage(`Request failed (HTTP ${response.status}). Please try again or contact support.`, 'error');
            if (confirmMsg) {
                confirmMsg.classList.remove('bg-blue-50', 'border-blue-200');
                confirmMsg.classList.add('bg-red-50', 'border-red-200');
                confirmMsg.innerHTML = '<p class="text-red-700 text-sm">Reservation request could not be processed. Please try again.</p>';
            }
            if (reserveBtn) {
                reserveBtn.style.display = 'block';
            }
            return;
        }

        let result;
        try {
            result = JSON.parse(rawText);
        } catch (parseError) {
            console.error('Reservation confirm returned non-JSON response:', rawText);
            showMessage('Unexpected response from the server. Please try again or contact support.', 'error');
            if (confirmMsg && confirmMsg.dataset.originalContent) {
                confirmMsg.classList.remove('bg-blue-50', 'border-blue-200');
                confirmMsg.classList.add('bg-yellow-50', 'border-yellow-200');
                confirmMsg.innerHTML = confirmMsg.dataset.originalContent;
            }
            return;
        }

        showMessage(result.message, result.success ? 'success' : 'error');

        if (result.success) {
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            if (confirmMsg && confirmMsg.dataset.originalContent) {
                confirmMsg.classList.remove('bg-blue-50', 'border-blue-200');
                confirmMsg.classList.add('bg-yellow-50', 'border-yellow-200');
                confirmMsg.innerHTML = confirmMsg.dataset.originalContent;
            }
            if (reserveBtn) {
                reserveBtn.style.display = 'block';
            }
        }

    } catch (fetchError) {
        console.error('AJAX Error:', fetchError);
        showMessage('An error occurred while processing your request. Please try again.', 'error');
        if (confirmMsg && confirmMsg.dataset.originalContent) {
            confirmMsg.classList.remove('bg-blue-50', 'border-blue-200');
            confirmMsg.classList.add('bg-yellow-50', 'border-yellow-200');
            confirmMsg.innerHTML = confirmMsg.dataset.originalContent;
        }
        if (reserveBtn) {
            reserveBtn.style.display = 'block';
        }
    }
}

// Custom logout confirmation modal
function confirmLogout() {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.id = 'logoutModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl max-w-md mx-4 p-6 border-2 border-[#C19A6B]">
            <div class="text-center">
                <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-sign-out-alt text-red-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#5C4033] mb-2">Confirm Logout</h3>
                <p class="text-[#5C4033] opacity-75 mb-6">Are you sure you want to logout?</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="closeLogoutModal()" 
                            class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-semibold">
                        Cancel
                    </button>
                    <button onclick="proceedLogout()" 
                            class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-semibold">
                        <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Focus trap and ESC key handler
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeLogoutModal();
    });
    
    document.addEventListener('keydown', handleEscKey);
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.remove();
        document.removeEventListener('keydown', handleEscKey);
    }
}

function proceedLogout() {
    window.location.href = '<?= site_url('auth/logout') ?>';
}

function handleEscKey(e) {
    if (e.key === 'Escape') {
        closeLogoutModal();
    }
}
</script>

<?php if (!empty($paymentAlert['show'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('paymentAlertModal');
    const dismissBtn = document.getElementById('paymentAlertDismiss');
    const laterBtn = document.getElementById('paymentAlertLater');

    function closePaymentAlert() {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    if (dismissBtn) {
        dismissBtn.addEventListener('click', function (event) {
            event.preventDefault();
            closePaymentAlert();
        });
    }

    if (laterBtn) {
        laterBtn.addEventListener('click', function (event) {
            event.preventDefault();
            closePaymentAlert();
        });
    }

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closePaymentAlert();
            }
        });
    }
});
</script>
<?php endif; ?>

</body>
</html>
