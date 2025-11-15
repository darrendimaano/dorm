<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
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
</style>
</head>
<body class="min-h-screen transition-colors">

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
    <a href="<?= site_url('auth/logout') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-400 hover:text-white transition">
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
    <div class="flex items-center gap-4">
      <button id="darkModeToggle" class="p-2 rounded-lg border border-[#C19A6B] hover:bg-[#C19A6B] hover:text-white transition">
        <i class="fa-solid fa-moon" id="darkModeIcon"></i>
      </button>
      <div class="text-xs text-[#5C4033] opacity-75">
        <i class="fa-solid fa-phone mr-1"></i>
        09517394938
      </div>
      <?php if(isset($pendingCount) && $pendingCount > 0): ?>
        <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">
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
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border user-card" style="border-color: #C19A6B;">
            <div class="flex items-center gap-4">
                <div class="bg-[#C19A6B] p-3 rounded-lg">
                    <i class="fa-solid fa-bed text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[#5C4033]">Available Rooms</h3>
                    <p class="text-2xl font-bold text-[#C19A6B]"><?= count($rooms) ?></p>
                </div>
            </div>
        </div>
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border user-card" style="border-color: #C19A6B;">
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
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border user-card" style="border-color: #C19A6B;">
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
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 3xl:grid-cols-7 gap-4" id="roomsGrid">
        <?php if(!empty($rooms)): ?>
            <?php foreach($rooms as $room): ?>
                <div style="background: #FFF5E1;" class="p-4 rounded-lg shadow-sm border hover:shadow-md transition-all relative user-card room-card" style="border-color: #C19A6B;">
                    <div class="absolute top-3 right-3">
                        <span class="bg-green-100 text-green-800 px-1.5 py-0.5 rounded-full text-xs font-semibold">
                            Available
                        </span>
                    </div>
                    <div class="mb-3">
                        <h2 class="text-lg font-bold text-[#5C4033] mb-1 room-number">Room #<?= htmlspecialchars($room['room_number']) ?></h2>
                        <p class="text-[#5C4033] opacity-75 text-xs mb-1">
                            <i class="fa-solid fa-bed text-[#C19A6B]"></i> 
                            <?= $room['beds'] ?? 'N/A' ?> Bed<?= ($room['beds'] ?? 0) > 1 ? 's' : '' ?>
                        </p>
                        <p class="text-[#5C4033] opacity-75 text-xs mb-1">
                            <i class="fa-solid fa-users text-[#C19A6B]"></i> 
                            <?= $room['available'] ?> Space<?= $room['available'] > 1 ? 's' : '' ?> Available
                        </p>
                        <p class="text-[#5C4033] opacity-75 text-xs mb-1">
                            <i class="fa-solid fa-door-open text-[#C19A6B]"></i> 
                            <strong><?= $room['available'] ?> Room<?= $room['available'] > 1 ? 's' : '' ?> can be reserved</strong>
                        </p>
                        <p class="text-[#5C4033] opacity-75 text-xs mb-3 room-type">
                            <i class="fa-solid fa-tag text-[#C19A6B]"></i> 
                            Dormitory Room
                        </p>
                        <div class="bg-[#e6ddd4] p-2 rounded-lg mb-3">
                            <p class="text-lg font-bold text-[#5C4033] room-payment">₱<?= number_format($room['payment'] ?? 0, 2) ?></p>
                            <p class="text-[#5C4033] opacity-75 text-xs">per month</p>
                        </div>
                    </div>

                    <!-- Confirmation Message Area -->
                    <div id="confirm-msg-<?= $room['id'] ?>" class="hidden bg-yellow-50 border border-yellow-200 p-2 rounded-lg mb-2">
                        <p class="text-yellow-800 text-xs mb-2">
                            <i class="fa-solid fa-question-circle"></i> 
                            How many rooms in Room #<?= htmlspecialchars($room['room_number']) ?>?
                        </p>
                        <div class="mb-2">
                            <label class="block text-yellow-700 text-xs font-semibold mb-1">Quantity:</label>
                            <div class="flex items-center gap-1 justify-center">
                                <button type="button" onclick="decreaseQuantity(<?= $room['id'] ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white w-6 h-6 rounded flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                <input type="number" id="quantity-<?= $room['id'] ?>" value="1" min="1" max="<?= $room['available'] ?>" class="w-12 text-center border border-yellow-300 rounded px-1 py-1 text-xs" onchange="validateQuantity(<?= $room['id'] ?>, <?= $room['available'] ?>)">
                                <button type="button" onclick="increaseQuantity(<?= $room['id'] ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white w-6 h-6 rounded flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                            <span class="text-yellow-700 text-xs block text-center mt-1">of <?= $room['available'] ?> available</span>
                        </div>
                        <div class="flex gap-1">
                            <button onclick="confirmReservation(<?= $room['id'] ?>)" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded text-xs">
                                <i class="fa-solid fa-check"></i> Reserve
                            </button>
                            <button onclick="cancelReservation(<?= $room['id'] ?>)" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded text-xs">
                                <i class="fa-solid fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>

                    <?php if(isset($_SESSION['user'])): ?>
                        <?php if($room['available'] > 0): ?>
                            <form method="POST" action="http://localhost/lasttry/index.php/user/reserve/<?= $room['id'] ?>" class="w-full" id="reservation-form-<?= $room['id'] ?>">
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
// Dark mode functionality
function initDarkMode() {
    const darkModeToggle = document.getElementById("darkModeToggle");
    const darkModeIcon = document.getElementById("darkModeIcon");
    const mainBody = document.body;
    
    if (!darkModeToggle) return;
    
    // Check for saved dark mode preference
    const isDarkMode = localStorage.getItem("userDarkMode") === "true";
    if (isDarkMode) {
        mainBody.classList.add("dark");
        if(darkModeIcon) darkModeIcon.className = "fa-solid fa-sun";
    }
    
    darkModeToggle.addEventListener("click", () => {
        mainBody.classList.toggle("dark");
        const isDark = mainBody.classList.contains("dark");
        
        // Save preference
        localStorage.setItem("userDarkMode", isDark);
        
        // Update icon
        if(darkModeIcon) {
            darkModeIcon.className = isDark ? "fa-solid fa-sun" : "fa-solid fa-moon";
        }
        
        // Update database setting via AJAX
        fetch("<?= site_url('settings/update') ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "dark_mode_user=" + (isDark ? "1" : "0") + "&ajax=1"
        });
    });
}

// Initialize when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDarkMode);
} else {
    initDarkMode();
}

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

// Handle reservation form submission
async function handleReservationSubmit(event, roomId, roomNumber) {
    event.preventDefault();
    
    console.log('Reservation form submitted for room:', roomId, roomNumber);
    
    try {
        const confirmed = await confirmReservation(roomId, roomNumber);
        if (confirmed) {
            console.log('Reservation confirmed, submitting via AJAX...');
            
            // Add loading state to button
            const button = event.target.querySelector('button[type="submit"]');
            let originalText = '';
            if (button) {
                originalText = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
                button.disabled = true;
            }
            
            try {
                // Submit reservation via AJAX
                const response = await fetch(`<?= site_url('user/reserve/') ?>${roomId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `room_id=${roomId}`
                });
                
                const result = await response.json();
                
                // Show message on the same page
                showMessage(result.message, result.success ? 'success' : 'error');
                
                // If successful, refresh the page stats
                if (result.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
                
            } catch (fetchError) {
                console.error('AJAX Error:', fetchError);
                showMessage('An error occurred while processing your request. Please try again.', 'error');
            }
            
            // Re-enable button
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        } else {
            console.log('Reservation cancelled by user');
        }
    } catch (error) {
        console.error('Error in handleReservationSubmit:', error);
        showMessage('An error occurred while processing your request. Please try again.', 'error');
        
        // Re-enable button on error
        const button = event.target.querySelector('button[type="submit"]');
        if (button) {
            button.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Request Reservation';
            button.disabled = false;
        }
    }
    
    return false;
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

// Function to confirm reservation
async function confirmReservation(roomId) {
    const form = document.getElementById(`reservation-form-${roomId}`);
    const confirmMsg = document.getElementById(`confirm-msg-${roomId}`);
    const quantityInput = document.getElementById(`quantity-${roomId}`);
    const quantity = quantityInput ? quantityInput.value : 1;
    
    // Hide confirmation message
    if (confirmMsg) {
        confirmMsg.classList.add('hidden');
    }
    
    // Show loading state
    if (confirmMsg) {
        confirmMsg.innerHTML = `
            <p class="text-blue-800 text-sm">
                <i class="fa-solid fa-spinner fa-spin"></i> 
                Processing your reservation request for ${quantity} room${quantity > 1 ? 's' : ''}...
            </p>
        `;
        confirmMsg.classList.remove('hidden');
        confirmMsg.className = confirmMsg.className.replace('bg-yellow-50 border-yellow-200', 'bg-blue-50 border-blue-200');
    }
    
    try {
        // Submit reservation via AJAX with quantity
        const response = await fetch(`<?= site_url('user/reserve/') ?>${roomId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `room_id=${roomId}&quantity=${quantity}`
        });
        
        const result = await response.json();
        
        // Show message on the same page
        showMessage(result.message, result.success ? 'success' : 'error');
        
        // If successful, refresh the page stats
        if (result.success) {
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Reset the button state if there was an error
            const reserveBtn = document.getElementById(`reserve-btn-${roomId}`);
            if (reserveBtn && confirmMsg) {
                reserveBtn.style.display = 'block';
                confirmMsg.classList.add('hidden');
            }
        }
        
    } catch (fetchError) {
        console.error('AJAX Error:', fetchError);
        showMessage('An error occurred while processing your request. Please try again.', 'error');
        
        // Reset the button state on error
        const reserveBtn = document.getElementById(`reserve-btn-${roomId}`);
        if (reserveBtn && confirmMsg) {
            reserveBtn.style.display = 'block';
            confirmMsg.classList.add('hidden');
        }
    }
}
</script>

</body>
</html>
