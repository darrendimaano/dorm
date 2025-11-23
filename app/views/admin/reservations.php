<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservations - Dormitory Admin</title>
<script>
// Offline-ready Tailwind CSS fallback
if (!window.navigator.onLine) {
    document.addEventListener('DOMContentLoaded', function() {
        // Add basic offline styling if Tailwind fails to load
        const style = document.createElement('style');
        style.textContent = `
            .bg-white { background-color: #ffffff; }
            .text-white { color: #ffffff; }
            .border { border: 1px solid #d1d5db; }
            .rounded-lg { border-radius: 8px; }
            .p-4 { padding: 16px; }
            .mb-4 { margin-bottom: 16px; }
            .shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .bg-green-500 { background-color: #10b981; }
            .bg-red-500 { background-color: #ef4444; }
            .hover\\:bg-green-600:hover { background-color: #059669; }
            .hover\\:bg-red-600:hover { background-color: #dc2626; }
            .flex { display: flex; }
            .items-center { align-items: center; }
            .justify-between { justify-content: space-between; }
            .gap-2 { gap: 8px; }
            .font-semibold { font-weight: 600; }
            .text-sm { font-size: 14px; }
            .text-xs { font-size: 12px; }
        `;
        document.head.appendChild(style);
    });
}
</script>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Offline FontAwesome fallbacks using unicode symbols */
.offline-icon { font-family: sans-serif; font-weight: bold; }
body.offline-mode .fa-check::before { content: '✓'; }
body.offline-mode .fa-times::before { content: '✗'; }
body.offline-mode .fa-spinner::before { content: '⟳'; animation: spin 1s linear infinite; }
body.offline-mode .fa-user::before { content: '👤'; }
body.offline-mode .fa-bed::before { content: '🛏️'; }
body.offline-mode .fa-envelope::before { content: '✉️'; }
body.offline-mode .fa-hashtag::before { content: '#'; }
body.offline-mode .fa-clock::before { content: '🕐'; }
body.offline-mode .fa-check-circle::before { content: '✅'; }
body.offline-mode .fa-times-circle::before { content: '❌'; }
body.offline-mode .fa-history::before { content: '📋'; }
body.offline-mode .fa-hourglass-empty::before { content: '⏳'; }
body.offline-mode .fa-search::before { content: '🔍'; }
body.offline-mode .fa-print::before { content: '🖨️'; }
body.offline-mode .fa-file-excel::before { content: '📊'; }
body.offline-mode .fa-file-csv::before { content: '📄'; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Ensure offline functionality */
.offline-ready {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 8px 12px;
    border-radius: 4px;
    margin: 4px;
}
</style>
<!-- Excel export made optional for offline use -->
<script>
// Load Excel library only if online
if (window.navigator.onLine) {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
    script.onerror = function() {
        console.log('Excel export unavailable offline');
        // Disable Excel export button if library fails to load
        const excelBtn = document.querySelector('[onclick="exportToExcel()"]');
        if (excelBtn) {
            excelBtn.style.display = 'none';
        }
    };
    document.head.appendChild(script);
} else {
    // Hide Excel export when offline
    document.addEventListener('DOMContentLoaded', function() {
        const excelBtn = document.querySelector('[onclick="exportToExcel()"]');
        if (excelBtn) {
            excelBtn.style.display = 'none';
        }
    });
}
</script>
<style>
  /* Print styles */
  @media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    #printArea, #printArea * { visibility: visible; }
    #printArea { position: absolute; left: 0; top: 0; width: 100% !important; }
    body * { visibility: hidden; }
    #sidebar, .header-area { display: none !important; }
    .main-content { margin-left: 0 !important; }
  }
  .print-only { display: none; }
  
  /* Flexible sidebar styles */
  #sidebar {
    transition: all 0.3s ease;
    background: #D2B48C;
    transform: translateX(0);
  }
  #sidebar.collapsed {
    width: 4rem !important;
    min-width: 4rem;
  }
  #sidebar.collapsed nav a span {
    opacity: 0;
    width: 0;
    overflow: hidden;
  }
  #sidebar.collapsed nav a {
    justify-content: center;
    padding: 0.5rem;
  }
  #sidebar.collapsed h2 {
    text-align: center;
    font-size: 1.2rem;
  }
  .content-area {
    transition: all 0.3s ease;
    width: 100%;
    max-width: none;
  }
  @media (max-width: 768px) {
    #sidebar {
      position: fixed;
      z-index: 1000;
      transform: translateX(-100%);
    }
    #sidebar.show {
      transform: translateX(0);
    }
    .content-area {
      margin-left: 0 !important;
    }
  }
</style>
</head>
<body class="bg-white font-sans flex<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Offline Status Notification -->
<div id="offlineNotice" class="fixed top-4 right-4 z-50 hidden">
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded-lg shadow-lg max-w-sm">
        <div class="flex items-center">
            <span class="text-yellow-400 mr-2">📶</span>
            <div>
                <p class="text-sm font-medium text-yellow-800">No Internet Detected</p>
                <p class="text-xs text-yellow-700">Don't worry! All approve/reject functions work offline.</p>
            </div>
            <button onclick="this.parentElement.parentElement.parentElement.style.display='none'" 
                    class="ml-2 text-yellow-600 hover:text-yellow-800 text-lg">×</button>
        </div>
    </div>
</div>

<!-- Quick Action Notifications -->
<div id="quickNotificationContainer" class="fixed top-4 right-4 z-[60] space-y-2"></div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70] flex items-center justify-center">
    <div class="bg-[#FFF5E1] rounded-lg shadow-xl border-2 border-[#C19A6B] p-6 w-full max-w-md mx-4">
        <div class="mb-4 pb-3 border-b border-[#E5D3B3]">
            <h3 class="text-lg font-bold text-[#5C4033] flex items-center gap-2">
                <i id="confirmIcon" class="fa-solid fa-question-circle text-[#C19A6B]"></i>
                <span id="confirmTitle">Confirm Action</span>
            </h3>
        </div>
        <div class="mb-6">
            <p id="confirmMessage" class="text-[#5C4033]"></p>
        </div>
        <div class="flex gap-3 justify-end">
            <button id="confirmCancel" class="px-4 py-2 bg-[#8B7355] text-white rounded-lg hover:bg-[#6B5B48] transition-all duration-200">
                Cancel
            </button>
            <button id="confirmOk" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200">
                Confirm
            </button>
        </div>
    </div>
</div>

<script>
// Simple offline detection and notification
function toggleOfflineMode(isOffline) {
  if (!document.body) {
    return;
  }

  const notice = document.getElementById('offlineNotice');

  if (isOffline) {
    document.body.classList.add('offline-mode');
    if (notice) {
      notice.classList.remove('hidden');
      setTimeout(() => notice.style.display = 'none', 10000);
    }
  } else {
    document.body.classList.remove('offline-mode');
    if (notice) {
      notice.style.display = 'none';
    }
  }
}

document.addEventListener('DOMContentLoaded', function() {
  if (!navigator.onLine) {
    toggleOfflineMode(true);
  }
});

window.addEventListener('offline', function() {
  toggleOfflineMode(true);
});

window.addEventListener('online', function() {
  toggleOfflineMode(false);
});
</script>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-64 transition-all duration-300 content-area" id="mainContent">
  <div class="bg-[#FFF5E1] shadow-md flex items-center justify-between px-4 py-3">
    <button id="menuBtn" class="text-[#5C4033] text-xl hover:bg-[#C19A6B] p-2 rounded transition">
      <i class="fa-solid fa-bars"></i>
    </button>
    <h1 class="font-bold text-lg text-[#5C4033] flex items-center gap-2">
      <i class="fa-solid fa-list-check text-[#C19A6B]"></i>
      Reservations Management
    </h1>
    <div class="flex items-center gap-3 text-sm text-[#5C4033] opacity-75">
      <i class="fa-solid fa-clock"></i>
      <span id="currentTime"></span>
    </div>
  </div>

  <div class="w-full px-3 py-4">
    
    <!-- Success / Error Messages -->
    <?php if(!empty($success)): ?>
        <div id="successMessage" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 shadow-lg animate-pulse">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fa-solid fa-check-circle text-xl mr-3"></i>
                    <div>
                        <p class="font-semibold">Success!</p>
                        <p><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
                <button onclick="closeMessage('successMessage')" class="text-green-500 hover:text-green-700">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div id="errorMessage" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 shadow-lg animate-pulse">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fa-solid fa-exclamation-circle text-xl mr-3"></i>
                    <div>
                        <p class="font-semibold">Error!</p>
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
                <button onclick="closeMessage('errorMessage')" class="text-red-500 hover:text-red-700">
                    <i class="fa-solid fa-times"></i>
                </button>
        </div>
      </div>
    <?php endif; ?>

    <!-- Quick Action Success Message -->
    <div id="quickActionMessage" class="hidden fixed top-4 right-4 z-50 max-w-sm">
        <div class="bg-white border-l-4 border-green-500 p-4 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fa-solid fa-check-circle text-green-500 text-xl mr-3"></i>
                    <p id="quickActionText" class="text-sm font-medium text-gray-800"></p>
                </div>
                <button onclick="hideQuickMessage()" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        </div>
    </div>    <!-- Pending Reservations Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <h2 class="text-2xl font-bold text-[#5C4033]">
          <i class="fa-solid fa-clock text-orange-600"></i> Pending Reservations 
          <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-semibold ml-2">
            <?= count($pendingReservations) ?>
          </span>
      </h2>
      <div class="no-print flex flex-wrap gap-2">
        <button onclick="printReservations()" class="group inline-flex items-center px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hover:scale-102" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
          <i class="fas fa-print text-sm group-hover:animate-pulse mr-2"></i>
          <span class="font-medium hidden sm:inline">Print</span>
          <span class="font-medium sm:hidden">Print</span>
        </button>
        <button onclick="exportToExcel()" class="group inline-flex items-center px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hover:scale-102" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
          <i class="fas fa-file-excel text-sm group-hover:animate-bounce mr-2"></i>
          <span class="font-medium hidden sm:inline">Excel</span>
          <span class="font-medium sm:hidden">Excel</span>
        </button>
        <button onclick="exportToCSV()" class="group inline-flex items-center px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hover:scale-102" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
          <i class="fas fa-file-csv text-sm group-hover:animate-pulse mr-2"></i>
          <span class="font-medium hidden sm:inline">CSV</span>
          <span class="font-medium sm:hidden">CSV</span>
        </button>
      </div>
    </div>
    
    <!-- Confirmation Message Container -->
    <div id="confirmationBox" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 no-print">
      <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl">
        <div class="flex items-center mb-4">
          <i class="fas fa-question-circle text-[#C19A6B] text-2xl mr-3"></i>
          <h3 class="text-lg font-semibold text-[#5C4033]">Confirm Action</h3>
        </div>
        <p id="confirmationMessage" class="text-[#5C4033] mb-6"></p>
        <div class="flex justify-end gap-3">
          <button onclick="cancelAction()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition duration-300">
            <i class="fas fa-times mr-2"></i>Cancel
          </button>
          <button id="confirmButton" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition duration-300">
            <i class="fas fa-check mr-2"></i>Continue
          </button>
        </div>
      </div>
    </div>
    
    <!-- Search Bar -->
    <div class="relative mb-4 no-print">
      <div class="relative max-w-sm">
        <input type="text" id="searchInput" placeholder="Search reservations by tenant, room, or status..." 
               class="w-full px-3 py-2 pl-8 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1] text-[#5C4033] text-sm">
        <i class="fa-solid fa-search absolute left-2 top-1/2 transform -translate-y-1/2 text-[#C19A6B]"></i>
      </div>
    </div>

    <div id="printArea">
      <div class="print-header print-only text-center p-6 border-b">
        <h1 class="text-2xl font-bold text-[#5C4033]">Dormitory Management System</h1>
        <h2 class="text-lg text-[#C19A6B] mt-2">Reservations Report</h2>
        <p class="text-sm text-[#5C4033] mt-1">Generated on: <?= date('F j, Y g:i A') ?></p>
      </div>
      
    <?php if(!empty($pendingReservations)): ?>
    <div class="w-full overflow-x-auto rounded-lg border border-[#C19A6B] shadow-lg bg-[#FFF5E1] mb-6">
    <table class="w-full border-collapse min-w-full">
        <thead>
            <tr class="bg-[#C19A6B] text-white text-xs uppercase tracking-wide">
                <th class="py-2 px-2 text-left">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-hashtag"></i>
                    <span class="hidden sm:inline">ID</span>
                  </div>
                </th>
                <th class="py-2 px-2 text-left">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-user"></i>
                    <span>Tenant</span>
                  </div>
                </th>
                <th class="py-2 px-2 text-left hidden lg:table-cell">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Email</span>
                  </div>
                </th>
                <th class="py-2 px-2 text-left">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-bed"></i>
                    <span>Room</span>
                  </div>
                </th>
                <th class="py-2 px-2 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <i class="fa-solid fa-cogs"></i>
                    <span>Actions</span>
                  </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($pendingReservations as $res): ?>
            <tr class="border-b border-[#E5D3B3] hover:bg-[#F5F0E8] transition-all duration-200" id="row-<?= $res['id'] ?>">
                <td class="py-4 px-2 md:px-4 font-bold text-[#5C4033] text-sm">
                  <span class="bg-[#C19A6B] text-white px-2 py-1 rounded-full text-xs"><?= $res['id'] ?></span>
                </td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033]">
                  <div class="font-semibold"><?= htmlspecialchars($res['fname'] . ' ' . $res['lname']) ?></div>
                  <div class="text-xs text-[#5C4033] opacity-75 lg:hidden"><?= htmlspecialchars($res['email']) ?></div>
                </td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033] text-sm hidden lg:table-cell"><?= htmlspecialchars($res['email']) ?></td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033]">
                  <div class="flex items-center gap-2">
                    <i class="fa-solid fa-door-open text-[#C19A6B]"></i>
                    <span class="font-semibold">Room #<?= htmlspecialchars($res['room_number']) ?></span>
                  </div>
                </td>
                <td class="py-4 px-2 md:px-4">
                  <div class="flex flex-col sm:flex-row gap-2 justify-center">
                    <button onclick="quickApprove(<?= $res['id'] ?>, '<?= htmlspecialchars($res['fname']) ?> <?= htmlspecialchars($res['lname']) ?>', this)" 
                            class="approve-btn w-full bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg text-sm font-semibold shadow-md transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check"></i>
                        <span>Approve</span>
                    </button>
                    <button onclick="quickReject(<?= $res['id'] ?>, '<?= htmlspecialchars($res['fname']) ?> <?= htmlspecialchars($res['lname']) ?>', this)" 
                            class="reject-btn w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-semibold shadow-md transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-times"></i>
                        <span>Reject</span>
                    </button>
                  </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
        <div class="border rounded-lg mb-8 text-center shadow p-6" style="background: #FFF5E1; border-color: #C19A6B; color: #5C4033;">
            <i class="fa-solid fa-info-circle text-2xl mb-2" style="color: #C19A6B;"></i>
            <h3 class="font-semibold text-lg mb-1 text-[#5C4033]">No Pending Reservations</h3>
            <p class="text-sm text-[#5C4033] opacity-80">All reservation requests have been processed.</p>
        </div>
    <?php endif; ?>

    <!-- All Reservations History -->
    <?php if(!empty($allReservations)): ?>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
      <h2 class="text-xl md:text-2xl font-bold text-[#5C4033] flex items-center gap-2">
          <i class="fa-solid fa-history text-[#C19A6B]"></i> 
          <span>Reservations History</span>
      </h2>
      <div class="flex items-center gap-3">
        <div class="relative">
          <input type="text" 
                 id="historySearchInput" 
                 placeholder="Search reservations..." 
                 class="px-4 py-2 pl-10 pr-4 rounded-lg border border-[#C19A6B] focus:border-[#A67C52] focus:ring-2 focus:ring-[#C19A6B] focus:ring-opacity-30 text-[#5C4033] w-64 bg-[#FFF5E1]"
                 onkeyup="filterReservations()">
          <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-[#5C4033] opacity-50"></i>
        </div>
        <button onclick="clearHistorySearch()" 
                class="px-3 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200 text-sm">
          <i class="fa-solid fa-times"></i>
        </button>
        <div class="text-sm text-[#5C4033] opacity-75 bg-[#FFF5E1] px-3 py-1 rounded-full border border-[#C19A6B]">
          <i class="fa-solid fa-database"></i>
          <span id="recordsCount"><?= count($allReservations) ?></span> records
        </div>
      </div>
    </div>
    <div class="w-full overflow-x-auto rounded-lg border border-[#C19A6B] shadow-lg bg-[#FFF5E1]">
    <table class="w-full border-collapse min-w-full" id="reservationsTable">
        <thead>
            <tr class="bg-[#5C4033] text-white text-xs md:text-sm uppercase tracking-wide">
                <th class="py-4 px-2 md:px-4 text-left">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-hashtag"></i>
                    <span class="hidden sm:inline">ID</span>
                  </div>
                </th>
                <th class="py-4 px-2 md:px-4 text-left">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-user"></i>
                    <span>Tenant</span>
                  </div>
                </th>
                <th class="py-4 px-2 md:px-4 text-left">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-bed"></i>
                    <span>Room</span>
                  </div>
                </th>
                <th class="py-4 px-2 md:px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <i class="fa-solid fa-info-circle"></i>
                    <span>Status</span>
                  </div>
                </th>
            </tr>
        </thead>
        <tbody id="reservationsTableBody">
            <?php foreach($allReservations as $res): ?>
            <tr class="reservation-row border-b border-[#E5D3B3] hover:bg-[#F5F0E8] transition-all duration-200">
                <td class="py-4 px-2 md:px-4 font-bold text-[#5C4033] text-sm">
                  <span class="bg-[#C19A6B] text-white px-2 py-1 rounded-full text-xs reservation-id"><?= $res['id'] ?></span>
                </td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033] font-semibold reservation-tenant"><?= htmlspecialchars($res['fname'] . ' ' . $res['lname']) ?></td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033]">
                  <div class="flex items-center gap-2">
                    <i class="fa-solid fa-door-open text-[#C19A6B]"></i>
                    <span class="font-semibold reservation-room">Room #<?= htmlspecialchars($res['room_number']) ?></span>
                  </div>
                </td>
                <td class="py-4 px-2 md:px-4 text-center">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold reservation-status
                        <?= $res['status'] == 'approved' ? 'bg-green-100 text-green-800 border border-green-200' : 
                            ($res['status'] == 'rejected' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-yellow-100 text-yellow-800 border border-yellow-200') ?>">
                        <i class="fa-solid fa-<?= $res['status'] == 'approved' ? 'check-circle' : ($res['status'] == 'rejected' ? 'times-circle' : 'clock') ?>"></i>
                        <?= ucfirst($res['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- No Results Found Message -->
    <div id="noHistoryResults" class="hidden p-8 text-center text-[#5C4033] opacity-60">
        <i class="fa-solid fa-search text-4xl mb-3"></i>
        <h3 class="text-lg font-semibold mb-2">No matching reservations found</h3>
        <p class="text-sm">Try adjusting your search terms</p>
    </div>
    </div>
    <?php endif; ?>
    
    </div> <!-- End printArea -->
  </div>
</div>

<script>
// On-page notification system
function showNotification(message, type = 'success') {
    const container = document.getElementById('quickNotificationContainer');
    if (!container) return;
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-item transform transition-all duration-300 ease-in-out translate-x-full opacity-0`;
    
    const bgColor = type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
    const textColor = type === 'success' ? 'text-green-800' : 'text-red-800';
    const iconColor = type === 'success' ? 'text-green-500' : 'text-red-500';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    notification.innerHTML = `
        <div class="${bgColor} border-l-4 p-4 rounded-lg shadow-lg max-w-sm">
            <div class="flex items-start">
                <i class="fa-solid ${icon} ${iconColor} text-lg mr-3 mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium ${textColor}">${message}</p>
                </div>
                <button onclick="removeNotification(this.closest('.notification-item'))" 
                        class="ml-2 ${textColor} hover:opacity-75 text-lg leading-none">×</button>
            </div>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.classList.remove('translate-x-full', 'opacity-0');
    }, 10);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        removeNotification(notification);
    }, 5000);
}

function removeNotification(notification) {
    if (!notification) return;
    
    notification.classList.add('translate-x-full', 'opacity-0');
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Custom confirmation modal
function showConfirmation(message, title = 'Confirm Action', iconClass = 'fa-question-circle') {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmationModal');
        const titleEl = document.getElementById('confirmTitle');
        const messageEl = document.getElementById('confirmMessage');
        const iconEl = document.getElementById('confirmIcon');
        const okBtn = document.getElementById('confirmOk');
        const cancelBtn = document.getElementById('confirmCancel');
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        iconEl.className = `fa-solid ${iconClass} text-[#C19A6B]`;
        
        modal.classList.remove('hidden');
        
        function handleResponse(result) {
            modal.classList.add('hidden');
            okBtn.removeEventListener('click', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
            document.removeEventListener('keydown', handleEscape);
            resolve(result);
        }
        
        function handleOk() {
            handleResponse(true);
        }
        
        function handleCancel() {
            handleResponse(false);
        }
        
        function handleEscape(e) {
            if (e.key === 'Escape') {
                handleResponse(false);
            }
        }
        
        okBtn.addEventListener('click', handleOk);
        cancelBtn.addEventListener('click', handleCancel);
        document.addEventListener('keydown', handleEscape);
    });
}

// Custom confirmation modal
function showConfirmation(message, title = 'Confirm Action', iconClass = 'fa-question-circle') {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmationModal');
        const titleEl = document.getElementById('confirmTitle');
        const messageEl = document.getElementById('confirmMessage');
        const iconEl = document.getElementById('confirmIcon');
        const okBtn = document.getElementById('confirmOk');
        const cancelBtn = document.getElementById('confirmCancel');
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        iconEl.className = `fa-solid ${iconClass} text-[#C19A6B]`;
        
        modal.classList.remove('hidden');
        
        function handleResponse(result) {
            modal.classList.add('hidden');
            okBtn.removeEventListener('click', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
            document.removeEventListener('keydown', handleEscape);
            resolve(result);
        }
        
        function handleOk() {
            handleResponse(true);
        }
        
        function handleCancel() {
            handleResponse(false);
        }
        
        function handleEscape(e) {
            if (e.key === 'Escape') {
                handleResponse(false);
            }
        }
        
        okBtn.addEventListener('click', handleOk);
        cancelBtn.addEventListener('click', handleCancel);
        document.addEventListener('keydown', handleEscape);
    });
}

// Alias for backward compatibility
function showQuickMessage(message, type) {
    showNotification(message, type);
}

// Override browser confirm with custom modal for better UX
window.customConfirm = async function(message, title = 'Confirm Action', iconClass = 'fa-question-circle') {
    return await showConfirmation(message, title, iconClass);
};

// Search functionality for reservations history
function filterReservations() {
    const searchInput = document.getElementById('historySearchInput');
    const filter = searchInput.value.toLowerCase();
    const tableBody = document.getElementById('reservationsTableBody');
    const rows = tableBody.getElementsByClassName('reservation-row');
    const noResults = document.getElementById('noHistoryResults');
    const recordsCount = document.getElementById('recordsCount');
    let visibleRows = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const reservationId = row.querySelector('.reservation-id').textContent.toLowerCase();
        const tenantName = row.querySelector('.reservation-tenant').textContent.toLowerCase();
        const roomNumber = row.querySelector('.reservation-room').textContent.toLowerCase();
        const status = row.querySelector('.reservation-status').textContent.toLowerCase();
        
        // Check if any of the searchable fields contain the filter text
        if (reservationId.includes(filter) || 
            tenantName.includes(filter) || 
            roomNumber.includes(filter) || 
            status.includes(filter)) {
            row.style.display = '';
            visibleRows++;
        } else {
            row.style.display = 'none';
        }
    }
    
    // Show/hide no results message
    if (visibleRows === 0 && filter !== '') {
        noResults.classList.remove('hidden');
    } else {
        noResults.classList.add('hidden');
    }
    
    // Update records count
    if (recordsCount) {
        recordsCount.textContent = visibleRows;
    }
}

function clearHistorySearch() {
    const searchInput = document.getElementById('historySearchInput');
    searchInput.value = '';
    filterReservations(); // This will show all rows again
    searchInput.focus();
}

// Add event listeners for the search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('historySearchInput');
    if (searchInput) {
        // Add event listener for Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterReservations();
            }
        });
        
        // Add event listener for Escape key to clear search
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearHistorySearch();
            }
        });
    }
});

// Fast AJAX approval/rejection functions
async function quickApprove(id, studentName, button) {
    const confirmed = await showConfirmation(
        `Quick approve reservation for ${studentName}?`,
        'Approve Reservation',
        'fa-check-circle'
    );
    
    if (!confirmed) return;
    
    // Set loading state
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    
    fetch('<?= site_url("admin/reservations/quickApprove") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row with animation
            const row = document.getElementById(`row-${id}`);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(100%)';
                setTimeout(() => row.remove(), 300);
            }
            showQuickMessage(data.message, 'success');
            updatePendingCount();
        } else {
            button.disabled = false;
            button.innerHTML = originalContent;
            showQuickMessage(data.message || 'Failed to approve reservation', 'error');
        }
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = originalContent;
        showQuickMessage('Network error occurred', 'error');
    });
}

function quickReject(id, studentName, button) {
    if (!confirm(`Quick reject reservation for ${studentName}?`)) return;
    
    // Set loading state
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    
    fetch('<?= site_url("admin/reservations/quickReject") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row with animation
            const row = document.getElementById(`row-${id}`);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-100%)';
                setTimeout(() => row.remove(), 300);
            }
            showQuickMessage(data.message, 'success');
            updatePendingCount();
        } else {
            button.disabled = false;
            button.innerHTML = originalContent;
            showQuickMessage(data.message || 'Failed to reject reservation', 'error');
        }
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = originalContent;
        showQuickMessage('Network error occurred', 'error');
    });
}

// Bulk actions
function bulkApprove() {
    const selected = getSelectedReservations();
    if (selected.length === 0) {
        showQuickMessage('No reservations selected', 'error');
        return;
    }
    
    if (!confirm(`Approve ${selected.length} selected reservations?`)) return;
    
    processBulkAction(selected, 'approve');
}

function bulkReject() {
    const selected = getSelectedReservations();
    if (selected.length === 0) {
        showQuickMessage('No reservations selected', 'error');
        return;
    }
    
    if (!confirm(`Reject ${selected.length} selected reservations?`)) return;
    
    processBulkAction(selected, 'reject');
}

function processBulkAction(ids, action) {
    const btn = document.getElementById(`bulk${action.charAt(0).toUpperCase() + action.slice(1)}Btn`);
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Processing ${action}...`;
    
    fetch(`<?= site_url("admin/reservations/bulk") ?>${action.charAt(0).toUpperCase() + action.slice(1)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove processed rows
            ids.forEach(id => {
                const row = document.getElementById(`row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.8)';
                    setTimeout(() => row.remove(), 300);
                }
            });
            showQuickMessage(data.message, 'success');
            updatePendingCount();
            updateBulkActions();
        } else {
            showQuickMessage(data.message || `Failed to ${action} reservations`, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = originalContent;
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
        showQuickMessage('Network error occurred', 'error');
    });
}

// Utility functions
function getSelectedReservations() {
    const checkboxes = document.querySelectorAll('.reservation-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function toggleAllCheckboxes(source) {
    const checkboxes = document.querySelectorAll('.reservation-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    updateBulkActions();
}

function updateBulkActions() {
    const selected = getSelectedReservations();
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    const bulkRejectBtn = document.getElementById('bulkRejectBtn');
    const selectedCount = document.getElementById('selectedCount');
    
    if (bulkApproveBtn && bulkRejectBtn && selectedCount) {
        bulkApproveBtn.disabled = selected.length === 0;
        bulkRejectBtn.disabled = selected.length === 0;
        selectedCount.textContent = `${selected.length} selected`;
    }
}

function updatePendingCount() {
    const pendingRows = document.querySelectorAll('tbody tr');
    const count = pendingRows.length;
    // Update any pending count displays
    document.querySelectorAll('.pending-count').forEach(el => {
        el.textContent = count;
    });
}

function showQuickMessage(message, type = 'success') {
    const messageDiv = document.getElementById('quickActionMessage');
    const messageText = document.getElementById('quickActionText');
    
    if (messageDiv && messageText) {
        messageText.textContent = message;
        
        // Update styling based on type
        const borderClass = type === 'success' ? 'border-green-500' : 'border-red-500';
        const iconClass = type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500';
        
        messageDiv.querySelector('.border-l-4').className = `bg-white border-l-4 ${borderClass} p-4 rounded-lg shadow-lg`;
        messageDiv.querySelector('i').className = `fa-solid ${iconClass} text-xl mr-3`;
        
        messageDiv.classList.remove('hidden');
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            hideQuickMessage();
        }, 3000);
    }
}

function hideQuickMessage() {
    const messageDiv = document.getElementById('quickActionMessage');
    if (messageDiv) {
        messageDiv.classList.add('hidden');
    }
}

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

// Search functionality
const searchInput = document.getElementById('searchInput');
if (searchInput) {
  searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
      const tenantName = row.cells[1].textContent.toLowerCase();
      const roomNumber = row.cells[2].textContent.toLowerCase();
      const status = row.cells[3] ? row.cells[3].textContent.toLowerCase() : '';
      
      if (tenantName.includes(searchTerm) || roomNumber.includes(searchTerm) || status.includes(searchTerm)) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  });
}

// Print functionality
function printReservations() {
  showConfirmation('Are you sure you want to print the reservations report?', function() {
    const printHeader = document.querySelector('.print-header');
    if (printHeader) {
      printHeader.style.display = 'block';
    }
    window.print();
    if (printHeader) {
      printHeader.style.display = 'none';
    }
  });
}

// Export to Excel
function exportToExcel() {
  showConfirmation('Are you sure you want to download the reservations data as Excel file?', function() {
    const table = document.querySelector('#printArea table');
    if (!table) return;
    
    const wb = XLSX.utils.table_to_book(table, {sheet: 'Reservations'});
    const fileName = 'Reservations_Report_' + new Date().toISOString().slice(0,10) + '.xlsx';
    XLSX.writeFile(wb, fileName);
  });
}

// Export to CSV
function exportToCSV() {
  showConfirmation('Are you sure you want to download the reservations data as CSV file?', function() {
    const tables = document.querySelectorAll('#printArea table');
    let csv = 'Dormitory Reservations Report\n';
    csv += 'Generated on: ' + new Date().toLocaleDateString() + '\n\n';
    
    tables.forEach((table, index) => {
      if (index === 0) {
        csv += 'Pending Reservations\n';
      } else {
        csv += '\nAll Reservations\n';
      }
      
      const rows = table.querySelectorAll('tr');
      rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
          return '"' + cell.textContent.trim().replace(/"/g, '""') + '"';
        }).join(',');
        csv += rowData + '\n';
      });
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'Reservations_Report_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
  });
}

// Enhanced sidebar toggle functionality
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

// Current time display
function updateTime() {
  const now = new Date();
  const timeString = now.toLocaleTimeString('en-US', { 
    hour12: true, 
    hour: '2-digit', 
    minute: '2-digit' 
  });
  const timeElement = document.getElementById('currentTime');
  if (timeElement) {
    timeElement.textContent = timeString;
  }
}

// Update time every second
setInterval(updateTime, 1000);
updateTime(); // Initial call

// Sidebar toggle
if (menuBtn && sidebar && mainContent) {
  menuBtn.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    
    if (window.innerWidth <= 768) {
      // Mobile behavior
      sidebar.classList.toggle('show');
    } else {
      // Desktop behavior
      if (sidebar.classList.contains('collapsed')) {
        mainContent.style.marginLeft = '4rem';
      } else {
        mainContent.style.marginLeft = '16rem';
      }
    }
  });
}

// Handle window resize
window.addEventListener('resize', function() {
  if (window.innerWidth <= 768) {
    mainContent.style.marginLeft = '0';
  } else if (sidebar.classList.contains('collapsed')) {
    mainContent.style.marginLeft = '4rem';
  } else {
    mainContent.style.marginLeft = '16rem';
  }
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
  if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
    if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
      sidebar.classList.remove('show');
    }
  }
});

// Enhanced button hover effects
document.querySelectorAll('button[type="submit"]').forEach(button => {
  button.addEventListener('mouseenter', function() {
    this.style.transform = 'scale(1.05)';
  });
  
  button.addEventListener('mouseleave', function() {
    this.style.transform = 'scale(1)';
  });
});

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
  const messages = document.querySelectorAll('#successMessage, #errorMessage');
  messages.forEach(message => {
    setTimeout(() => {
      hideMessage(message);
    }, 5000);
  });
});

// Function to close message manually
function closeMessage(messageId) {
  const message = document.getElementById(messageId);
  if (message) {
    hideMessage(message);
  }
}

// Function to hide message with animation
function hideMessage(messageElement) {
  messageElement.style.transition = 'all 0.3s ease';
  messageElement.style.opacity = '0';
  messageElement.style.transform = 'translateY(-20px)';
  setTimeout(() => {
    messageElement.remove();
  }, 300);
}

// Fast AJAX approval/rejection functions
function quickApprove(id, studentName, button) {
    if (!confirm(`Quick approve reservation for ${studentName}?`)) return;
    
    // Set loading state
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    
    fetch('<?= site_url("admin/reservations/quickApprove") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row with animation
            const row = document.getElementById(`row-${id}`);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(100%)';
                setTimeout(() => row.remove(), 300);
            }
            showQuickMessage(data.message, 'success');
            updatePendingCount();
        } else {
            button.disabled = false;
            button.innerHTML = originalContent;
            showQuickMessage(data.message || 'Failed to approve reservation', 'error');
        }
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = originalContent;
        showQuickMessage('Network error occurred', 'error');
    });
}

function quickReject(id, studentName, button) {
    if (!confirm(`Quick reject reservation for ${studentName}?`)) return;
    
    // Set loading state
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    
    fetch('<?= site_url("admin/reservations/quickReject") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove row with animation
            const row = document.getElementById(`row-${id}`);
            if (row) {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-100%)';
                setTimeout(() => row.remove(), 300);
            }
            showQuickMessage(data.message, 'success');
            updatePendingCount();
        } else {
            button.disabled = false;
            button.innerHTML = originalContent;
            showQuickMessage(data.message || 'Failed to reject reservation', 'error');
        }
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = originalContent;
        showQuickMessage('Network error occurred', 'error');
    });
}

// Bulk actions
function bulkApprove() {
    const selected = getSelectedReservations();
    if (selected.length === 0) {
        showQuickMessage('No reservations selected', 'error');
        return;
    }
    
    if (!confirm(`Approve ${selected.length} selected reservations?`)) return;
    
    processBulkAction(selected, 'approve');
}

function bulkReject() {
    const selected = getSelectedReservations();
    if (selected.length === 0) {
        showQuickMessage('No reservations selected', 'error');
        return;
    }
    
    if (!confirm(`Reject ${selected.length} selected reservations?`)) return;
    
    processBulkAction(selected, 'reject');
}

function processBulkAction(ids, action) {
    const btn = document.getElementById(`bulk${action.charAt(0).toUpperCase() + action.slice(1)}Btn`);
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Processing ${action}...`;
    
    fetch(`<?= site_url("admin/reservations/bulk") ?>${action.charAt(0).toUpperCase() + action.slice(1)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove processed rows
            ids.forEach(id => {
                const row = document.getElementById(`row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.8)';
                    setTimeout(() => row.remove(), 300);
                }
            });
            showQuickMessage(data.message, 'success');
            updatePendingCount();
            updateBulkActions();
        } else {
            showQuickMessage(data.message || `Failed to ${action} reservations`, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = originalContent;
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
        showQuickMessage('Network error occurred', 'error');
    });
}

// Utility functions
function getSelectedReservations() {
    const checkboxes = document.querySelectorAll('.reservation-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function toggleAllCheckboxes(source) {
    const checkboxes = document.querySelectorAll('.reservation-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    updateBulkActions();
}

function updateBulkActions() {
    const selected = getSelectedReservations();
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    const bulkRejectBtn = document.getElementById('bulkRejectBtn');
    const selectedCount = document.getElementById('selectedCount');
    
    if (bulkApproveBtn && bulkRejectBtn && selectedCount) {
        bulkApproveBtn.disabled = selected.length === 0;
        bulkRejectBtn.disabled = selected.length === 0;
        selectedCount.textContent = `${selected.length} selected`;
    }
}

function updatePendingCount() {
    const pendingRows = document.querySelectorAll('tbody tr');
    const count = pendingRows.length;
    // Update any pending count displays
    document.querySelectorAll('.pending-count').forEach(el => {
        el.textContent = count;
    });
}

function showQuickMessage(message, type = 'success') {
    const messageDiv = document.getElementById('quickActionMessage');
    const messageText = document.getElementById('quickActionText');
    
    if (messageDiv && messageText) {
        messageText.textContent = message;
        
        // Update styling based on type
        const borderClass = type === 'success' ? 'border-green-500' : 'border-red-500';
        const iconClass = type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500';
        
        messageDiv.querySelector('.border-l-4').className = `bg-white border-l-4 ${borderClass} p-4 rounded-lg shadow-lg`;
        messageDiv.querySelector('i').className = `fa-solid ${iconClass} text-xl mr-3`;
        
        messageDiv.classList.remove('hidden');
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            hideQuickMessage();
        }, 3000);
    }
}

function hideQuickMessage() {
    const messageDiv = document.getElementById('quickActionMessage');
    if (messageDiv) {
        messageDiv.classList.add('hidden');
    }
}

// Enhanced confirmation with loading state (fallback for old forms)
function confirmAction(button, action, studentName) {
  const message = action === 'approve' ? 
    `Are you sure you want to APPROVE the reservation for ${studentName}?` :
    `Are you sure you want to REJECT the reservation for ${studentName}?`;
    
  if (confirm(message)) {
    // Change button to loading state
    button.disabled = true;
    button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Processing...`;
    button.style.opacity = '0.7';
    return true;
  }
  return false;
}
</script>

</body>
</html>
