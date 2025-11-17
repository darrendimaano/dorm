<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();

$usersPerMonth = $data['usersPerMonth'] ?? [];
$roomsAvailability = $data['roomsAvailability'] ?? [];
$totalUsers = $data['totalUsers'] ?? 0;
$totalRooms = $data['totalRooms'] ?? 0;
$availableRooms = $data['availableRooms'] ?? 0;

$rooms = [];
foreach ($roomsAvailability as $roomNumber => $available) {
    $rooms[] = ['room_number' => $roomNumber, 'available' => $available];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Dormitory</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php 
// Dark mode CSS embedded directly
?>
<style>
  /* Sidebar collapsed style */
  #sidebar {
    transition: width 0.3s ease, transform 0.3s ease;
    background: #D2B48C;
  }
  #sidebar.collapsed { width: 4rem; }
  #sidebar.collapsed nav a span { display: none; }
  #sidebar.collapsed nav a { justify-content: center; }
  #sidebar:hover.collapsed { width: 16rem; }
  
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
  .dark .card, .dark .admin-card {
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
  .dark table {
    background: #2a2a2a !important;
    color: #e5e5e5 !important;
  }
  .dark th {
    background: #333333 !important;
    color: #e5e5e5 !important;
    border-color: #555555 !important;
  }
  .dark td {
    border-color: #555555 !important;
  }
  .dark .header-section {
    background: #1a1a1a !important;
    color: #e5e5e5 !important;
  }
</style>
</head>
<body class="bg-[#FFF5E1] font-sans flex">

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-50 shadow-lg">
  <h2 class="text-2xl font-bold mb-8">🏨</h2>
  <nav class="flex flex-col gap-4">
    <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
    </a>
    <a href="<?= site_url('users') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-user"></i> <span>Users</span>
    </a>
    <a href="<?= site_url('rooms') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-bed"></i> <span>Rooms</span>
    </a>
    <a href="<?= site_url('admin/reservations') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
    </a>
    <a href="<?= site_url('admin/reports') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-file-chart-line"></i> <span>Tenant Reports</span>
    </a>
    <a href="<?= site_url('settings') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-cog"></i> <span>Settings</span>
    </a>
    <a href="<?= site_url('auth/logout') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-red-300 transition mt-6">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

<!-- Main Content -->
<div class="flex-1 ml-64 px-4 py-4 main-content">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#5C4033]">Admin Dashboard</h1>
        <button id="darkModeToggle" class="p-2 rounded-lg border border-[#C19A6B] hover:bg-[#C19A6B] hover:text-white transition">
            <i class="fa-solid fa-moon" id="darkModeIcon"></i>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-8 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-medium text-[#5C4033] opacity-75">Total Users</h2>
                    <p class="text-xl font-bold text-[#5C4033]"><?= $totalUsers ?></p>
                </div>
                <div class="bg-blue-100 p-2 rounded-lg">
                    <i class="fa-solid fa-users text-blue-600 text-sm"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-medium text-[#5C4033] opacity-75">Total Rooms</h2>
                    <p class="text-xl font-bold text-[#5C4033]"><?= $totalRooms ?></p>
                </div>
                <div class="bg-green-100 p-2 rounded-lg">
                    <i class="fa-solid fa-bed text-green-600 text-sm"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-medium text-[#5C4033] opacity-75">Available Beds</h2>
                    <p class="text-xl font-bold text-[#5C4033]"><?= $availableRooms ?></p>
                </div>
                <div class="bg-yellow-100 p-2 rounded-lg">
                    <i class="fa-solid fa-door-open text-yellow-600 text-sm"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-medium text-[#5C4033] opacity-75">Pending Requests</h2>
                    <p class="text-xl font-bold text-orange-600"><?= $data['pendingCount'] ?? 0 ?></p>
                </div>
                <div class="bg-orange-100 p-2 rounded-lg">
                    <i class="fa-solid fa-clock text-orange-600 text-sm"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 mb-6">
        <a href="<?= site_url('admin/reservations') ?>" class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-[#5C4033] group-hover:text-[#C19A6B] transition">Manage Reservations</h3>
                    <p class="text-xs text-[#5C4033] opacity-75">View and approve pending requests</p>
                    <p class="text-lg font-bold text-orange-600 mt-1"><?= $data['pendingCount'] ?? 0 ?> Pending</p>
                </div>
                <div class="bg-orange-100 p-2 rounded-lg group-hover:bg-orange-200 transition">
                    <i class="fa-solid fa-clipboard-check text-orange-600 text-lg"></i>
                </div>
            </div>
        </a>
        
        <a href="<?= site_url('users') ?>" class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-[#5C4033] group-hover:text-[#C19A6B] transition">Manage Users</h3>
                    <p class="text-xs text-[#5C4033] opacity-75">View and manage user accounts</p>
                    <p class="text-lg font-bold text-blue-600 mt-1"><?= $totalUsers ?> Users</p>
                </div>
                <div class="bg-blue-100 p-2 rounded-lg group-hover:bg-blue-200 transition">
                    <i class="fa-solid fa-users text-blue-600 text-lg"></i>
                </div>
            </div>
        </a>
        
        <a href="<?= site_url('rooms') ?>" class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-[#5C4033] group-hover:text-[#C19A6B] transition">Manage Rooms</h3>
                    <p class="text-xs text-[#5C4033] opacity-75">View and manage room availability</p>
                    <p class="text-lg font-bold text-green-600 mt-1"><?= $availableRooms ?> Available</p>
                </div>
                <div class="bg-green-100 p-2 rounded-lg group-hover:bg-green-200 transition">
                    <i class="fa-solid fa-bed text-green-600 text-lg"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 mb-6">

        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] card">
            <h2 class="text-sm font-semibold mb-3 text-[#5C4033]">Users Registered Per Month</h2>
            <canvas id="usersChart" class="w-full h-48"></canvas>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B]">
            <h2 class="text-sm font-semibold mb-3 text-[#5C4033]">Rooms Availability</h2>
            <canvas id="roomsChart" class="w-full h-48"></canvas>
        </div>

    </div>

    <!-- Payment Notifications -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 mb-6">
        <!-- Payment Reminders -->
        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] card">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-[#5C4033] flex items-center gap-2">
                    <i class="fa-solid fa-bell text-[#C19A6B]"></i>
                    Payment Alerts
                </h2>
                <a href="<?= site_url('admin/reports') ?>" class="text-xs text-[#C19A6B] hover:text-[#A67C52] font-medium">
                    View All Reports →
                </a>
            </div>
            
            <?php if (!empty($adminNotifications)): ?>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <?php foreach($adminNotifications as $notification): ?>
                    <div class="p-2 rounded-lg border-l-4 <?= $notification['type'] === 'payment_overdue' ? 'border-red-500 bg-red-50' : 'border-yellow-500 bg-yellow-50' ?>">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-semibold text-sm text-[#5C4033]">
                                    <?= htmlspecialchars($notification['fname'] . ' ' . $notification['lname']) ?>
                                    <span class="text-[#C19A6B]">Room #<?= $notification['room_number'] ?></span>
                                </p>
                                <p class="text-xs text-[#5C4033] opacity-75 mt-1">
                                    <?= $notification['type'] === 'payment_overdue' ? 'OVERDUE:' : 'DUE SOON:' ?>
                                    ₱<?= number_format($notification['payment'], 2) ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-[#5C4033] opacity-60">
                                    <?= date('M j, g:i A', strtotime($notification['created_at'])) ?>
                                </div>
                                <div class="text-xs mt-1">
                                    <span class="px-2 py-1 rounded-full text-white <?= $notification['type'] === 'payment_overdue' ? 'bg-red-500' : 'bg-yellow-500' ?>">
                                        <?= $notification['type'] === 'payment_overdue' ? 'Overdue' : 'Due Soon' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-[#5C4033] opacity-60">
                    <i class="fa-solid fa-check-circle text-4xl text-green-500 mb-3"></i>
                    <p class="text-sm">No payment alerts at this time</p>
                    <p class="text-xs mt-1">All tenants are up to date!</p>
                </div>
            <?php endif; ?>
            
            <div class="mt-4 pt-3 border-t border-[#E5D3B3] text-center">
                <button onclick="runPaymentCheck()" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200 text-sm font-medium">
                    <i class="fa-solid fa-refresh mr-1"></i>
                    Check for New Alerts
                </button>
            </div>
        </div>
        
        <!-- Quick Payment Actions -->
        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] card">
            <h2 class="text-sm font-semibold text-[#5C4033] mb-3 flex items-center gap-2">
                <i class="fa-solid fa-peso-sign text-[#C19A6B]"></i>
                Payment Summary
            </h2>
            
            <div class="space-y-2">
                <div class="flex items-center justify-between p-2 bg-[#FFF5E1] rounded-lg border border-[#E5D3B3]">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-xs font-medium text-[#5C4033]">Paid This Month</span>
                    </div>
                    <span class="text-sm font-bold text-green-600">₱0.00</span>
                </div>
                
                <div class="flex items-center justify-between p-2 bg-[#FFF5E1] rounded-lg border border-[#E5D3B3]">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                        <span class="text-xs font-medium text-[#5C4033]">Due Soon</span>
                    </div>
                    <span class="text-sm font-bold text-yellow-600">₱0.00</span>
                </div>
                
                <div class="flex items-center justify-between p-2 bg-[#FFF5E1] rounded-lg border border-[#E5D3B3]">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span class="text-xs font-medium text-[#5C4033]">Overdue</span>
                    </div>
                    <span class="text-sm font-bold text-red-600">₱0.00</span>
                </div>
            </div>
            
            <div class="mt-3 flex gap-1">
                <a href="<?= site_url('admin/reports') ?>" class="flex-1 px-2 py-1 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200 text-xs font-medium text-center">
                    <i class="fa-solid fa-chart-bar mr-1"></i>
                    Reports
                </a>
                <button onclick="window.location.reload()" class="px-2 py-1 border border-[#C19A6B] text-[#5C4033] rounded-lg hover:bg-[#C19A6B] hover:text-white transition-all duration-200 text-xs font-medium">
                    <i class="fa-solid fa-refresh"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Rooms Table -->
    <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B]">
        <h2 class="text-sm font-semibold mb-3 text-[#5C4033]">Rooms Details</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-center border-collapse">
                <thead>
                    <tr class="bg-[#C19A6B] text-white text-xs uppercase tracking-wide">
                        <th class="py-2 px-3">Room #</th>
                        <th class="py-2 px-3">Available Beds</th>
                    </tr>
                </thead>
                <tbody class="text-[#5C4033] text-xs">
                    <?php foreach($rooms as $r): ?>
                    <tr class="hover:bg-[#FFEFD5] transition">
                        <td class="py-2 px-3"><?= $r['room_number'] ?></td>
                        <td class="py-2 px-3"><?= $r['available'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($rooms)): ?>
                    <tr>
                        <td colspan="2" class="py-2 px-3 text-center text-[#5C4033]">No rooms available</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// Users Per Month Chart
const usersChart = new Chart(document.getElementById('usersChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($usersPerMonth)) ?>,
        datasets: [{
            label: 'New Users',
            data: <?= json_encode(array_values($usersPerMonth)) ?>,
            backgroundColor: 'rgba(79, 70, 229, 0.2)',
            borderColor: '#4f46e5',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Rooms Availability Chart
const roomsChart = new Chart(document.getElementById('roomsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($r)=>'Room '.$r['room_number'],$rooms)) ?>,
        datasets: [{
            label: 'Available Beds',
            data: <?= json_encode(array_column($rooms,'available')) ?>,
            backgroundColor: '#f59e0b'
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>

<script>
// Dark mode functionality
function initDarkMode() {
    const darkModeToggle = document.getElementById("darkModeToggle");
    const darkModeIcon = document.getElementById("darkModeIcon");
    const mainBody = document.body;
    
    if (!darkModeToggle) return;
    
    // Check for saved dark mode preference
    const isDarkMode = localStorage.getItem("adminDarkMode") === "true";
    if (isDarkMode) {
        mainBody.classList.add("dark");
        if(darkModeIcon) darkModeIcon.className = "fa-solid fa-sun";
    }
    
    darkModeToggle.addEventListener("click", () => {
        mainBody.classList.toggle("dark");
        const isDark = mainBody.classList.contains("dark");
        
        // Save preference
        localStorage.setItem("adminDarkMode", isDark);
        
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
            body: "dark_mode_admin=" + (isDark ? "1" : "0") + "&ajax=1"
        });
    });
}

// Payment reminder functionality
function runPaymentCheck() {
    const button = document.querySelector('button[onclick="runPaymentCheck()"]');
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Checking...';
    button.disabled = true;
    
    // Run payment check via fetch
    fetch('<?= site_url("console/payment_check") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert(`Payment check completed!\n${data.reminders_sent} reminders sent\n${data.overdue_notices} overdue notices sent`);
            // Reload page to show new notifications
            window.location.reload();
        } else {
            alert('Error checking payments: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error checking payments: ' + error.message);
    })
    .finally(() => {
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Initialize when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDarkMode);
} else {
    initDarkMode();
}
</script>

</body>
</html>
