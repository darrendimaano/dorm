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
$darkModeEnabled = false;
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
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
  
  /* Modern card styling */
  .card-modern {
    backdrop-filter: blur(20px);
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(193, 154, 107, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
  }
  
  .card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    background: rgba(255, 255, 255, 1);
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
<body class="bg-[#FFF5E1] font-sans flex<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Sidebar -->
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-64 px-2 py-2 main-content">

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-[#5C4033]">Admin Dashboard</h1>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card card-modern">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h2 class="text-sm font-medium text-[#5C4033] opacity-75 mb-1">Total Users</h2>
                    <p class="text-3xl font-bold text-[#5C4033]"><?= $totalUsers ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-xl ml-4">
                    <i class="fa-solid fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-5 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card card-modern">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h2 class="text-sm font-medium text-[#5C4033] opacity-75 mb-1">Total Rooms</h2>
                    <p class="text-3xl font-bold text-[#5C4033]"><?= $totalRooms ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-xl ml-4">
                    <i class="fa-solid fa-bed text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-5 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card card-modern">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h2 class="text-sm font-medium text-[#5C4033] opacity-75 mb-1">Available Beds</h2>
                    <p class="text-3xl font-bold text-[#5C4033]"><?= $availableRooms ?></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-xl ml-4">
                    <i class="fa-solid fa-door-open text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-5 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card card-modern">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h2 class="text-sm font-medium text-[#5C4033] opacity-75 mb-1">Pending Requests</h2>
                    <p class="text-3xl font-bold text-orange-600"><?= $data['pendingCount'] ?? 0 ?></p>
                </div>
                <div class="bg-orange-100 p-3 rounded-xl ml-4">
                    <i class="fa-solid fa-clock text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a href="<?= site_url('admin/reservations') ?>" class="bg-white p-6 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card card-modern group">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#5C4033] group-hover:text-[#C19A6B] transition mb-2">Manage Reservations</h3>
                    <p class="text-sm text-[#5C4033] opacity-75 mb-3">View and approve pending requests</p>
                    <p class="text-2xl font-bold text-orange-600"><?= $data['pendingCount'] ?? 0 ?> Pending</p>
                </div>
                <div class="bg-orange-100 p-4 rounded-xl group-hover:bg-orange-200 transition ml-4">
                    <i class="fa-solid fa-clipboard-check text-orange-600 text-2xl"></i>
                </div>
            </div>
        </a>
        
        <a href="<?= site_url('users') ?>" class="bg-white p-6 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card card-modern group">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#5C4033] group-hover:text-[#C19A6B] transition mb-2">Manage Users</h3>
                    <p class="text-sm text-[#5C4033] opacity-75 mb-3">View and manage user accounts</p>
                    <p class="text-2xl font-bold text-blue-600"><?= $totalUsers ?> Users</p>
                </div>
                <div class="bg-blue-100 p-4 rounded-xl group-hover:bg-blue-200 transition ml-4">
                    <i class="fa-solid fa-users text-blue-600 text-2xl"></i>
                </div>
            </div>
        </a>
        
        <a href="<?= site_url('rooms') ?>" class="bg-white p-6 rounded-xl shadow border border-[#C19A6B] hover:shadow-lg transition card card-modern group">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#5C4033] group-hover:text-[#C19A6B] transition mb-2">Manage Rooms</h3>
                    <p class="text-sm text-[#5C4033] opacity-75 mb-3">View and manage room availability</p>
                    <p class="text-2xl font-bold text-green-600"><?= $availableRooms ?> Available</p>
                </div>
                <div class="bg-green-100 p-4 rounded-xl group-hover:bg-green-200 transition ml-4">
                    <i class="fa-solid fa-bed text-green-600 text-2xl"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="bg-white p-6 rounded-xl shadow border border-[#C19A6B] card card-modern">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-[#5C4033] flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-[#C19A6B]"></i>
                    Users Registered Per Month
                </h2>
            </div>
            <canvas id="usersChart" class="w-full h-64"></canvas>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border border-[#C19A6B] card card-modern">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-[#5C4033] flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-[#C19A6B]"></i>
                    Rooms Availability
                </h2>
            </div>
            <canvas id="roomsChart" class="w-full h-64"></canvas>
        </div>

    </div>

    <!-- Payment Notifications -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Payment Reminders -->
        <div class="bg-white p-4 rounded-xl shadow border border-[#C19A6B] card lg:col-span-2 xl:col-span-2 2xl:col-span-3 card-modern">
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
        
        <!-- Quick Payment Actions removed (metrics available in Tenant Reports) -->
    </div>

    <!-- Rooms Table -->
    <div class="bg-white p-6 rounded-xl shadow border border-[#C19A6B] card-modern">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-[#5C4033] flex items-center gap-2">
                <i class="fa-solid fa-door-open text-[#C19A6B]"></i>
                Rooms Overview
            </h2>
            <a href="<?= site_url('admin/rooms') ?>" class="text-sm text-[#C19A6B] hover:text-[#A67C52] font-medium">
                Manage Rooms →
            </a>
        </div>
        <div class="overflow-x-auto rounded-lg border border-[#E5D3B3]">
            <table class="w-full text-center border-collapse">
                <thead>
                    <tr class="bg-gradient-to-r from-[#C19A6B] to-[#A67C52] text-white text-sm uppercase tracking-wide">
                        <th class="py-3 px-4 font-medium">Room Number</th>
                        <th class="py-3 px-4 font-medium">Available Beds</th>
                        <th class="py-3 px-4 font-medium">Occupancy Status</th>
                    </tr>
                </thead>
                <tbody class="text-[#5C4033] text-sm">
                    <?php foreach($rooms as $r): ?>
                    <tr class="hover:bg-[#FFF5E1] transition-all duration-200 border-b border-[#F5E6D3]">
                        <td class="py-3 px-4 font-medium">#<?= $r['room_number'] ?></td>
                        <td class="py-3 px-4"><?= $r['available'] ?></td>
                        <td class="py-3 px-4">
                            <?php if($r['available'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <div class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1"></div>
                                    Available
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <div class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1"></div>
                                    Full
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($rooms)): ?>
                    <tr>
                        <td colspan="3" class="py-8 px-4 text-center text-[#5C4033] opacity-60">
                            <i class="fa-solid fa-inbox text-2xl mb-2 block"></i>
                            <div>No rooms available</div>
                        </td>
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
</script>

</body>
</html>
