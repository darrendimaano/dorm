<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tenants/Occupants - Dormitory Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- SheetJS for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
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
  
  /* Print styles */
  @media print {
    body * { visibility: hidden; }
    #printArea, #printArea * { visibility: visible; }
    #printArea { position: absolute; left: 0; top: 0; width: 100% !important; }
    .no-print { display: none !important; }
    .page-break { page-break-before: always; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    thead { background-color: #f0f0f0; }
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
    <a href="<?=site_url('users')?>" class="flex items-center gap-2 px-4 py-2 rounded bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-user"></i> <span>Users</span>
    </a>
    <a href="<?=site_url('rooms')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-bed"></i> <span>Rooms</span>
    </a>
    <a href="<?=site_url('admin/reservations')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
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
      <i class="fa-solid fa-bed text-[#C19A6B]"></i>
      Current Tenants/Occupants
    </h1>
    <div class="flex items-center gap-2">
      <a href="<?= site_url('users'); ?>" class="text-[#5C4033] hover:bg-[#C19A6B] hover:text-white px-3 py-2 rounded transition">
        <i class="fas fa-arrow-left mr-1"></i>Back to Users
      </a>
    </div>
  </div>

  <div class="w-full px-3 py-4">
    
    <!-- Success / Error Messages -->
    <?php if(!empty($success)): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 text-center shadow border border-green-200">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 text-center shadow border border-red-200">
            <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Assign New Tenant Section -->
    <div class="mb-8 rounded-lg p-6 shadow-lg border border-[#C19A6B]" style="background: #FFF5E1;">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-[#5C4033] flex items-center gap-2">
          <i class="fas fa-user-plus text-[#C19A6B]"></i>
          Assign New Tenant
        </h2>
        <button id="toggleAssignForm" class="text-white px-4 py-2 rounded-lg font-semibold transition duration-300 flex items-center hover:bg-[#B07A4B]" style="background: #C19A6B;">
          <i class="fas fa-plus mr-2"></i>Show Form
        </button>
      </div>
      
      <div id="assignForm" class="hidden">
        <form method="POST" action="<?= site_url('users/assignTenant') ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <div>
            <label class="block text-[#5C4033] font-semibold mb-1">Student</label>
            <select name="student_id" class="w-full border border-[#C19A6B] p-3 rounded-lg" required>
              <option value="">Select Student</option>
              <?php
              try {
                $pdo = new PDO("mysql:host=localhost;dbname=mockdata;charset=utf8mb4", "jeany", "jeany");
                $stmt = $pdo->query("SELECT id, fname, lname, email FROM students WHERE id NOT IN (SELECT student_id FROM room_occupancy WHERE status = 'active') ORDER BY lname ASC");
                while($student = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo "<option value='{$student['id']}'>{$student['lname']}, {$student['fname']} ({$student['email']})</option>";
                }
              } catch(Exception $e) {}
              ?>
            </select>
          </div>
          
          <div>
            <label class="block text-[#5C4033] font-semibold mb-1">Room</label>
            <select name="room_id" id="roomSelect" class="w-full border border-[#C19A6B] p-3 rounded-lg" required>
              <option value="">Select Room</option>
              <?php
              try {
                $stmt = $pdo->query("SELECT id, room_number, beds, available FROM rooms WHERE available > 0 ORDER BY room_number ASC");
                while($room = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo "<option value='{$room['id']}' data-beds='{$room['beds']}'>Room #{$room['room_number']} ({$room['available']} available)</option>";
                }
              } catch(Exception $e) {}
              ?>
            </select>
          </div>
          
          <div>
            <label class="block text-[#5C4033] font-semibold mb-1">Bed Number</label>
            <select name="bed_number" id="bedSelect" class="w-full border border-[#C19A6B] p-3 rounded-lg" required>
              <option value="">Select Bed</option>
            </select>
          </div>
          
          <div>
            <label class="block text-[#5C4033] font-semibold mb-1">Monthly Payment</label>
            <input type="number" name="monthly_payment" step="0.01" class="w-full border border-[#C19A6B] p-3 rounded-lg" placeholder="₱0.00" required>
          </div>
          
          <div class="flex items-end">
            <button type="submit" class="w-full text-white py-3 rounded-lg font-semibold transition duration-300 hover:bg-green-600" style="background: #10b981;">
              <i class="fas fa-save mr-2"></i>Assign
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Current Tenants Table -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl md:text-2xl font-bold text-[#5C4033] flex items-center gap-2">
          <i class="fa-solid fa-users text-[#C19A6B]"></i> 
          <span>Current Tenants</span>
      </h2>
      <div class="flex items-center gap-3">
        <div class="no-print flex gap-2">
          <button onclick="printTenants()" class="group inline-flex items-center px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hover:scale-102" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
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
        
        <!-- Confirmation Message Container -->
        <div id="confirmationBox" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 no-print">
          <div class="rounded-lg p-6 max-w-md mx-4 shadow-xl" style="background: #FFF5E1; border: 2px solid #C19A6B;">
            <div class="flex items-center mb-4">
              <i class="fas fa-question-circle text-2xl mr-3" style="color: #C19A6B;"></i>
              <h3 class="text-lg font-semibold text-[#5C4033]">Confirm Action</h3>
            </div>
            <p id="confirmationMessage" class="text-[#5C4033] mb-6"></p>
            <div class="flex justify-end gap-3">
              <button onclick="cancelAction()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition duration-300">
                <i class="fas fa-times mr-2"></i>Cancel
              </button>
              <button id="confirmButton" class="px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition duration-300" style="background: #C19A6B;">
                <i class="fas fa-check mr-2"></i>Continue
              </button>
            </div>
          </div>
        </div>
        <div class="text-sm text-[#5C4033] opacity-75 bg-[#FFF5E1] px-3 py-1 rounded-full border border-[#C19A6B]">
          <i class="fa-solid fa-bed"></i>
          <?= count($tenants) ?> occupied beds
        </div>
      </div>
    </div>

    <?php if(!empty($tenants)): ?>
    <div id="printArea">
      <div class="print-header" style="display: none;">
        <h1 style="text-align: center; margin-bottom: 20px; font-size: 24px; font-weight: bold;">Current Tenants/Occupants Report</h1>
        <p style="text-align: center; margin-bottom: 20px;">Generated on <?= date('F d, Y h:i A') ?></p>
        <p style="text-align: center; margin-bottom: 30px;">Total Occupied Beds: <?= count($tenants) ?></p>
      </div>
      
      <div class="w-full overflow-x-auto rounded-lg border border-[#C19A6B] shadow-lg bg-[#FFF5E1]">
      <table class="w-full border-collapse min-w-full" id="tenantsTable">
        <thead>
            <tr class="bg-[#C19A6B] text-white text-xs md:text-sm uppercase tracking-wide">
                <th class="py-4 px-2 md:px-4 text-left">
                  <div class="flex items-center gap-1">
                    <i class="fa-solid fa-user"></i>
                    <span>Tenant Name</span>
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
                    <i class="fa-solid fa-door-open"></i>
                    <span>Room</span>
                  </div>
                </th>
                <th class="py-4 px-2 md:px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <i class="fa-solid fa-bed"></i>
                    <span>Bed</span>
                  </div>
                </th>
                <th class="py-4 px-2 md:px-4 text-center hidden sm:table-cell">
                  <div class="flex items-center justify-center gap-1">
                    <i class="fa-solid fa-calendar"></i>
                    <span>Check-in</span>
                  </div>
                </th>
                <th class="py-4 px-2 md:px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <i class="fa-solid fa-peso-sign"></i>
                    <span>Payment</span>
                  </div>
                </th>
                <th class="py-4 px-2 md:px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <i class="fa-solid fa-cogs"></i>
                    <span>Action</span>
                  </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($tenants as $tenant): ?>
            <tr class="border-b border-[#E5D3B3] hover:bg-[#F5F0E8] transition-all duration-200">
                <td class="py-4 px-2 md:px-4 text-[#5C4033]">
                  <div class="font-semibold"><?= htmlspecialchars($tenant['fname'] . ' ' . $tenant['lname']) ?></div>
                  <div class="text-xs text-[#5C4033] opacity-75 lg:hidden"><?= htmlspecialchars($tenant['email']) ?></div>
                </td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033] text-sm hidden lg:table-cell"><?= htmlspecialchars($tenant['email']) ?></td>
                <td class="py-4 px-2 md:px-4 text-[#5C4033]">
                  <div class="flex items-center gap-2">
                    <i class="fa-solid fa-door-open text-[#C19A6B]"></i>
                    <span class="font-semibold">Room #<?= htmlspecialchars($tenant['room_number']) ?></span>
                  </div>
                </td>
                <td class="py-4 px-2 md:px-4 text-center">
                  <span class="bg-[#C19A6B] text-white px-2 py-1 rounded-full text-xs font-semibold">
                    Bed #<?= htmlspecialchars($tenant['bed_number']) ?>
                  </span>
                </td>
                <td class="py-4 px-2 md:px-4 text-center text-sm text-[#5C4033] hidden sm:table-cell">
                  <?= date('M d, Y', strtotime($tenant['check_in_date'])) ?>
                </td>
                <td class="py-4 px-2 md:px-4 text-center">
                  <span class="text-green-600 font-semibold">₱<?= number_format($tenant['monthly_payment'], 2) ?></span>
                </td>
                <td class="py-4 px-2 md:px-4 text-center">
                  <a href="<?= site_url('users/removeTenant/' . $tenant['occupancy_id']) ?>" 
                     onclick="return confirm('Remove <?= htmlspecialchars($tenant['fname']) ?> from Room #<?= htmlspecialchars($tenant['room_number']) ?>?')"
                     class="bg-red-500 hover:bg-red-600 text-white px-2 md:px-3 py-2 rounded-lg text-xs md:text-sm font-semibold shadow-md transition-all duration-200 transform hover:scale-105">
                      <i class="fa-solid fa-times"></i>
                      <span class="hidden sm:inline">Remove</span>
                  </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div> <!-- End printArea -->
    <?php else: ?>
        <div class="text-center py-12 rounded-lg border border-[#C19A6B]" style="background: #FFF5E1;">
            <i class="fas fa-bed text-6xl text-[#C19A6B] mb-4 opacity-50"></i>
            <p class="text-[#5C4033] text-lg">No tenants currently assigned</p>
            <p class="text-[#5C4033] text-sm opacity-70 mt-2">Assign tenants to track occupancy</p>
        </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Enhanced sidebar toggle functionality
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

// Sidebar toggle
if (menuBtn && sidebar && mainContent) {
  menuBtn.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    
    if (window.innerWidth <= 768) {
      sidebar.classList.toggle('show');
    } else {
      if (sidebar.classList.contains('collapsed')) {
        mainContent.style.marginLeft = '4rem';
      } else {
        mainContent.style.marginLeft = '16rem';
      }
    }
  });
}

// Toggle assign form
document.getElementById('toggleAssignForm')?.addEventListener('click', function() {
  const form = document.getElementById('assignForm');
  const btn = this;
  
  form.classList.toggle('hidden');
  
  if (form.classList.contains('hidden')) {
    btn.innerHTML = '<i class="fas fa-plus mr-2"></i>Show Form';
  } else {
    btn.innerHTML = '<i class="fas fa-minus mr-2"></i>Hide Form';
  }
});

// Room and bed selection
document.getElementById('roomSelect')?.addEventListener('change', function() {
  const bedSelect = document.getElementById('bedSelect');
  const selectedOption = this.options[this.selectedIndex];
  const maxBeds = selectedOption.getAttribute('data-beds');
  
  // Clear bed options
  bedSelect.innerHTML = '<option value="">Select Bed</option>';
  
  if (maxBeds) {
    for (let i = 1; i <= parseInt(maxBeds); i++) {
      const option = document.createElement('option');
      option.value = i;
      option.textContent = `Bed #${i}`;
      bedSelect.appendChild(option);
    }
  }
});

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
  const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
  messages.forEach(message => {
    setTimeout(() => {
      message.style.opacity = '0';
      message.style.transform = 'translateY(-20px)';
      setTimeout(() => {
        message.remove();
      }, 300);
    }, 5000);
  });
});

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

// Print functionality
function printTenants() {
  showConfirmation('Are you sure you want to print the tenants report?', function() {
    // Show print header
    document.querySelector('.print-header').style.display = 'block';
    
    // Print the page
    window.print();
    
    // Hide print header after printing
    setTimeout(() => {
      document.querySelector('.print-header').style.display = 'none';
    }, 1000);
  });
}

// Export to Excel
function exportToExcel() {
  showConfirmation('Are you sure you want to download the tenants data as Excel file?', function() {
    const table = document.getElementById('tenantsTable');
    if (!table) {
      alert('Table not found!');
      return;
    }

    // Create worksheet data by reading from table
    const wsData = [];
    
    // Add header row
    const headerRow = table.querySelector('thead tr');
    const headers = [];
    headerRow.querySelectorAll('th').forEach(th => {
      const text = th.textContent.trim();
      if (text && text !== 'Actions') {
        headers.push(text);
      }
    });
    wsData.push(headers);
    
    // Add data rows
    const dataRows = table.querySelectorAll('tbody tr');
    dataRows.forEach(row => {
      const rowData = [];
      const cells = row.querySelectorAll('td');
      cells.forEach((cell, index) => {
        // Skip actions column (usually last)
        if (index < cells.length - 1 || !cell.querySelector('button')) {
          let text = cell.textContent.trim();
          // Clean up text (remove extra spaces and icons)
          text = text.replace(/\s+/g, ' ').trim();
          rowData.push(text);
        }
      });
      if (rowData.length > 0) {
        wsData.push(rowData);
      }
    });
    
    if (wsData.length <= 1) {
      alert('No data to export!');
      return;
    }
    
    const ws = XLSX.utils.aoa_to_sheet(wsData);
    const wb = XLSX.utils.book_new();
    
    // Auto-fit columns
    const colWidths = wsData[0].map((_, i) => ({
      wch: Math.max(...wsData.map(row => String(row[i] || '').length)) + 2
    }));
    ws['!cols'] = colWidths;
    
    XLSX.utils.book_append_sheet(wb, ws, 'Current Tenants');
    
    // Generate filename with current date
    const filename = `Current_Tenants_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(wb, filename);
  });
}

// Export to CSV
function exportToCSV() {
  showConfirmation('Are you sure you want to download the tenants data as CSV file?', function() {
    const table = document.getElementById('tenantsTable');
    if (!table) {
      alert('Table not found!');
      return;
    }

    const data = [];
    
    // Add header row
    const headerRow = table.querySelector('thead tr');
    const headers = [];
    headerRow.querySelectorAll('th').forEach(th => {
      const text = th.textContent.trim();
      if (text && text !== 'Actions') {
        headers.push(text);
      }
    });
    data.push(headers);
    
    // Add data rows
    const dataRows = table.querySelectorAll('tbody tr');
    dataRows.forEach(row => {
      const rowData = [];
      const cells = row.querySelectorAll('td');
      cells.forEach((cell, index) => {
        // Skip actions column (usually last)
        if (index < cells.length - 1 || !cell.querySelector('button')) {
          let text = cell.textContent.trim();
          // Clean up text (remove extra spaces and icons)
          text = text.replace(/\s+/g, ' ').trim();
          rowData.push(text);
        }
      });
      if (rowData.length > 0) {
        data.push(rowData);
      }
    });
    
    if (data.length <= 1) {
      alert('No data to export!');
      return;
    }
    
    // Convert to CSV
    const csvContent = data.map(row => 
      row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')
    ).join('\n');
    
    // Create download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    
    // Generate filename with current date
    const filename = `Current_Tenants_${new Date().toISOString().split('T')[0]}.csv`;
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
}
</script>

</body>
</html>