<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservations - Dormitory Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- SheetJS for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
<body class="bg-white font-sans flex">

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-50 shadow-lg">
  <h2 class="text-2xl font-bold mb-8">🏨</h2>
  <nav class="flex flex-col gap-4">
    <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
    </a>
    <a href="<?=site_url('users')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-user"></i> <span>Users</span>
    </a>
    <a href="<?=site_url('rooms')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-bed"></i> <span>Rooms</span>
    </a>
    <a href="<?=site_url('admin/reservations')?>" class="flex items-center gap-2 px-4 py-2 rounded bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
    </a>
    <a href="<?=site_url('admin/reports')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-file-chart-line"></i> <span>Tenant Reports</span>
    </a>
    <a href="<?=site_url('admin/messages')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-envelope"></i> <span>Messages</span>
    </a>
    <a href="<?=site_url('settings')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-cog"></i> <span>Settings</span>
    </a>
    <a href="<?=site_url('auth/logout')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-red-300 transition mt-6">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

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
    <div class="flex items-center gap-2 text-sm text-[#5C4033] opacity-75">
      <i class="fa-solid fa-clock"></i>
      <span id="currentTime"></span>
    </div>
  </div>

  <div class="w-full px-4 py-6">
    
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

    <!-- Pending Reservations Section -->
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
    <div class="relative mb-6 no-print">
      <div class="relative max-w-md">
        <input type="text" id="searchInput" placeholder="Search reservations by tenant, room, or status..." 
               class="w-full px-4 py-3 pl-10 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1] text-[#5C4033]">
        <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-[#C19A6B]"></i>
      </div>
    </div>

    <div id="printArea">
      <div class="print-header print-only text-center p-6 border-b">
        <h1 class="text-2xl font-bold text-[#5C4033]">Dormitory Management System</h1>
        <h2 class="text-lg text-[#C19A6B] mt-2">Reservations Report</h2>
        <p class="text-sm text-[#5C4033] mt-1">Generated on: <?= date('F j, Y g:i A') ?></p>
      </div>
      
    <?php if(!empty($pendingReservations)): ?>
    <div class="w-full overflow-x-auto rounded-lg border border-[#C19A6B] shadow-lg bg-[#FFF5E1] mb-8">
    <table class="w-full border-collapse min-w-full">
        <thead>
            <tr class="bg-[#C19A6B] text-white text-xs md:text-sm uppercase tracking-wide">
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
                <th class="py-4 px-2 md:px-4 text-left hidden lg:table-cell">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Email</span>
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
                    <i class="fa-solid fa-cogs"></i>
                    <span>Actions</span>
                  </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($pendingReservations as $res): ?>
            <tr class="border-b border-[#E5D3B3] hover:bg-[#F5F0E8] transition-all duration-200">
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
                  <div class="flex flex-col sm:flex-row gap-1 sm:gap-2 justify-center">
                    <form method="POST" action="<?= site_url('admin/reservations/approveAction') ?>" class="flex-1">
                        <input type="hidden" name="id" value="<?= $res['id'] ?>">
                        <button type="submit" class="approve-btn w-full bg-green-500 hover:bg-green-600 text-white px-2 md:px-3 py-2 rounded-lg text-xs md:text-sm font-semibold shadow-md transition-all duration-200 transform hover:scale-105"
                                onclick="return confirmAction(this, 'approve', '<?= htmlspecialchars($res['fname']) ?> <?= htmlspecialchars($res['lname']) ?>')">
                            <i class="fa-solid fa-check"></i>
                            <span class="hidden sm:inline">Approve</span>
                        </button>
                    </form>
                    <form method="POST" action="<?= site_url('admin/reservations/rejectAction') ?>" class="flex-1">
                        <input type="hidden" name="id" value="<?= $res['id'] ?>">
                        <button type="submit" class="reject-btn w-full bg-red-500 hover:bg-red-600 text-white px-2 md:px-3 py-2 rounded-lg text-xs md:text-sm font-semibold shadow-md transition-all duration-200 transform hover:scale-105"
                                onclick="return confirmAction(this, 'reject', '<?= htmlspecialchars($res['fname']) ?> <?= htmlspecialchars($res['lname']) ?>')">
                            <i class="fa-solid fa-times"></i>
                            <span class="hidden sm:inline">Reject</span>
                        </button>
                    </form>
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
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl md:text-2xl font-bold text-[#5C4033] flex items-center gap-2">
          <i class="fa-solid fa-history text-[#C19A6B]"></i> 
          <span>Reservations History</span>
      </h2>
      <div class="text-sm text-[#5C4033] opacity-75 bg-[#FFF5E1] px-3 py-1 rounded-full border border-[#C19A6B]">
        <i class="fa-solid fa-database"></i>
        <?= count($allReservations) ?> records
      </div>
    </div>
    <div class="w-full overflow-x-auto rounded-lg border border-[#C19A6B] shadow-lg bg-[#FFF5E1]">
    <table class="w-full border-collapse min-w-full">
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
        <tbody>
            <?php foreach($allReservations as $res): ?>
            <tr class="border-b border-[#E5D3B3] hover:bg-[#F5F0E8] transition-all duration-200">
                <td class="py-4 px-2 md:px-4 font-bold text-[#5C4033] text-sm">
                  <span class="bg-[#C19A6B] text-white px-2 py-1 rounded-full text-xs"><?= $res['id'] ?></span>
                </td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033] font-semibold"><?= htmlspecialchars($res['fname'] . ' ' . $res['lname']) ?></td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033]">
                  <div class="flex items-center gap-2">
                    <i class="fa-solid fa-door-open text-[#C19A6B]"></i>
                    <span class="font-semibold">Room #<?= htmlspecialchars($res['room_number']) ?></span>
                  </div>
                </td>
                <td class="py-4 px-2 md:px-4 text-center">
                    <span class="inline-flex items-center gap-1 px-3 py-2 rounded-full text-xs md:text-sm font-semibold shadow-sm
                        <?= $res['status'] == 'approved' ? 'bg-green-100 text-green-800 border border-green-200' : 
                            ($res['status'] == 'rejected' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-yellow-100 text-yellow-800 border border-yellow-200') ?>">
                        <i class="fa-solid fa-<?= $res['status'] == 'approved' ? 'check-circle' : ($res['status'] == 'rejected' ? 'times-circle' : 'clock') ?>"></i>
                        <span><?= ucfirst($res['status']) ?></span>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    
    </div> <!-- End printArea -->
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

// Enhanced confirmation with loading state
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
