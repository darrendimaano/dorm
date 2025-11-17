<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tenant Reports - Dormitory Admin</title>
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
  
  /* Professional Modern Enhancements */
  .card-modern {
    background: rgba(255, 245, 225, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(193, 154, 107, 0.2);
    transition: all 0.3s ease;
  }
  .card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(193, 154, 107, 0.15);
    border-color: #C19A6B;
  }
  
  /* Enhanced grid for full-width utilization */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }
  
  @media (min-width: 1024px) {
    .stats-grid {
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
  }
  
  @media (min-width: 1536px) {
    .stats-grid {
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
  }
  
  /* Smooth scrollbars */
  ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
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
    <a href="<?=site_url('admin/reservations')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
    </a>
    <a href="<?=site_url('admin/reports')?>" class="flex items-center gap-2 px-4 py-2 rounded bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-file-chart-line"></i> <span>Tenant Reports</span>
    </a>
    <a href="<?=site_url('admin/messages')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-envelope"></i> <span>Messages</span>
    </a>
    <a href="<?=site_url('settings')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-cog"></i> <span>Settings</span>
    </a>
    <a href="#" onclick="confirmLogout()" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-red-300 transition mt-6">
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
      <i class="fa-solid fa-file-chart-line text-[#C19A6B]"></i>
      Tenant Reports & Monitoring
    </h1>
    <div class="flex items-center gap-2 text-sm text-[#5C4033] opacity-75">
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

    <!-- Summary Statistics -->
    <div class="stats-grid mb-6">
        <div class="card-modern rounded-lg p-4 shadow-lg border border-[#E5D3B3] hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[#5C4033] text-xs opacity-75">Active Tenants</p>
                    <p class="text-xl font-bold text-[#C19A6B]"><?= $summary['total_active_tenants'] ?? 0 ?></p>
                </div>
                <i class="fa-solid fa-users text-[#C19A6B] text-lg"></i>
            </div>
        </div>
        
        <div class="bg-[#FFF5E1] rounded-lg p-4 shadow-lg border border-[#E5D3B3] hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[#5C4033] text-xs opacity-75">Overdue Payments</p>
                    <p class="text-xl font-bold text-red-600"><?= $summary['overdue_tenants'] ?? 0 ?></p>
                </div>
                <i class="fa-solid fa-exclamation-triangle text-red-500 text-lg"></i>
            </div>
        </div>
        
        <div class="bg-[#FFF5E1] rounded-lg p-4 shadow-lg border border-[#E5D3B3] hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[#5C4033] text-xs opacity-75">Due Soon</p>
                    <p class="text-xl font-bold text-orange-600"><?= $summary['due_soon_tenants'] ?? 0 ?></p>
                </div>
                <i class="fa-solid fa-clock text-orange-500 text-lg"></i>
            </div>
        </div>
        
        <div class="bg-[#FFF5E1] rounded-lg p-4 shadow-lg border border-[#E5D3B3] hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[#5C4033] text-xs opacity-75">Monthly Revenue</p>
                    <p class="text-lg font-bold text-[#C19A6B]">₱<?= number_format($summary['total_monthly_revenue'] ?? 0, 2) ?></p>
                </div>
                <i class="fa-solid fa-peso-sign text-[#C19A6B] text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Quick Actions Bar -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="<?= site_url('admin/reports/payment-history') ?>" 
           class="flex items-center gap-2 px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200 shadow-lg font-semibold text-sm">
            <i class="fa-solid fa-history"></i>
            <span>Payment History</span>
        </a>
        
        <a href="<?= site_url('admin/reports/payment-history/download-csv') ?>" 
           class="flex items-center gap-2 px-4 py-2 border-2 border-[#C19A6B] text-[#5C4033] rounded-lg hover:bg-[#C19A6B] hover:text-white transition-all duration-200 font-semibold text-sm">
            <i class="fa-solid fa-download"></i>
            <span>Download CSV</span>
        </a>
        
        <button onclick="window.print()" 
                class="flex items-center gap-2 px-4 py-2 border-2 border-[#C19A6B] text-[#5C4033] rounded-lg hover:bg-[#C19A6B] hover:text-white transition-all duration-200 font-semibold text-sm">
            <i class="fa-solid fa-print"></i>
            <span>Print Current View</span>
        </button>
        
        <button onclick="runPaymentReminders()" 
                class="flex items-center gap-2 px-4 py-2 bg-[#8B7355] text-white rounded-lg hover:bg-[#6B5B48] transition-all duration-200 font-semibold text-sm">
            <i class="fa-solid fa-bell"></i>
            <span>Send Reminders</span>
        </button>
    </div>

    <!-- Tenant Reports Table -->
    <div class="bg-[#FFF5E1] rounded-lg shadow-lg border border-[#E5D3B3] overflow-hidden mb-6">
        <div class="px-4 py-3" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-table"></i>
                Tenant Reports & Payment Tracking
            </h2>
        </div>
        
        <?php if(!empty($tenantReports)): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead style="background: #FFF5E1; color: #5C4033;" class="border-b border-[#E5D3B3]">
                    <tr>
                        <th class="py-2 px-3 text-left font-semibold text-xs">
                            <div class="flex items-center gap-1">
                                <i class="fa-solid fa-user"></i>
                                <span>Tenant</span>
                            </div>
                        </th>
                        <th class="py-2 px-3 text-left font-semibold text-xs">
                            <div class="flex items-center gap-1">
                                <i class="fa-solid fa-bed"></i>
                                <span>Room</span>
                            </div>
                        </th>
                        <th class="py-4 px-4 text-left font-semibold">
                            <div class="flex items-center gap-1">
                                <i class="fa-solid fa-calendar"></i>
                                <span>Stay Period</span>
                            </div>
                        </th>
                        <th class="py-4 px-4 text-left font-semibold">
                            <div class="flex items-center gap-1">
                                <i class="fa-solid fa-info-circle"></i>
                                <span>Status</span>
                            </div>
                        </th>
                        <th class="py-4 px-4 text-left font-semibold">
                            <div class="flex items-center gap-1">
                                <i class="fa-solid fa-peso-sign"></i>
                                <span>Amount Due</span>
                            </div>
                        </th>
                        <th class="py-4 px-4 text-center font-semibold">
                            <div class="flex items-center justify-center gap-1">
                                <i class="fa-solid fa-cogs"></i>
                                <span>Actions</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-[#FFF5E1]">
                    <?php foreach($tenantReports as $report): ?>
                    <tr class="border-b border-[#E5D3B3] hover:bg-[#F5F0E8] transition-all duration-200">
                        <td class="py-4 px-4">
                            <div>
                                <p class="font-semibold text-[#5C4033]"><?= htmlspecialchars($report['fname'] . ' ' . $report['lname']) ?></p>
                                <p class="text-sm text-[#5C4033] opacity-75"><?= htmlspecialchars($report['email']) ?></p>
                                <p class="text-sm text-[#5C4033] opacity-75">Student ID: <?= htmlspecialchars($report['student_id']) ?></p>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-door-open text-[#C19A6B]"></i>
                                <div>
                                    <p class="font-semibold text-[#5C4033]">Room #<?= htmlspecialchars($report['room_number']) ?></p>
                                    <p class="text-sm text-[#5C4033] opacity-75">₱<?= number_format($report['room_price'], 2) ?>/month</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div>
                                <?php if($report['start_date']): ?>
                                    <div class="flex items-center gap-2 mb-1">
                                        <i class="fa-solid fa-calendar-check text-[#C19A6B]"></i>
                                        <span class="text-sm font-medium text-[#5C4033]">Start:</span>
                                        <span class="text-sm text-[#5C4033]"><?= date('M j, Y', strtotime($report['start_date'])) ?></span>
                                    </div>
                                    <?php if($report['monthly_due_date']): ?>
                                        <div class="flex items-center gap-2 mb-1">
                                            <i class="fa-solid fa-calendar-clock text-[#C19A6B]"></i>
                                            <span class="text-sm font-medium text-[#5C4033]">Due:</span>
                                            <span class="text-sm font-bold text-[#5C4033]"><?= date('M j, Y', strtotime($report['monthly_due_date'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($report['last_payment_date']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-peso-sign text-[#C19A6B]"></i>
                                            <span class="text-sm font-medium text-[#5C4033]">Last Paid:</span>
                                            <span class="text-sm text-[#5C4033]"><?= date('M j, Y', strtotime($report['last_payment_date'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button onclick="openDateModal(<?= $report['reservation_id'] ?>)" 
                                            class="px-3 py-1 rounded-lg border border-[#C19A6B] text-[#5C4033] hover:bg-[#C19A6B] hover:text-white transition-all duration-200 text-sm">
                                        <i class="fa-solid fa-calendar-plus"></i> Set Dates
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <?php 
                                $statusColors = [
                                    'Overdue' => 'bg-[#FFE4E1] text-[#8B0000] border-[#CD5C5C]',
                                    'Due Soon' => 'bg-[#FFF8DC] text-[#8B4513] border-[#DAA520]',
                                    'Active' => 'bg-[#F0FFF0] text-[#006400] border-[#90EE90]'
                                ];
                                $statusColor = $statusColors[$report['stay_status']] ?? 'bg-[#F5F5F5] text-[#5C4033] border-[#E5D3B3]';
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold border <?= $statusColor ?>">
                                <?= $report['stay_status'] ?>
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <div>
                                <p class="font-semibold text-lg text-[#5C4033]">₱<?= number_format($report['total_amount_due'], 2) ?></p>
                                <p class="text-sm text-[#5C4033] opacity-75">Monthly Rent</p>
                                <?php if($report['days_stayed'] > 0): ?>
                                    <p class="text-xs text-[#C19A6B] mt-1"><?= $report['days_stayed'] ?> days since start</p>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex flex-col gap-2">
                                <button onclick="openPaymentModal(<?= $report['student_id'] ?>, '<?= htmlspecialchars($report['fname'] . ' ' . $report['lname']) ?>', <?= $report['total_amount_due'] ?>)" 
                                        style="background: #C19A6B; color: white;" 
                                        class="hover:bg-[#A67C52] px-3 py-1 rounded text-sm transition-all duration-200 font-semibold">
                                    <i class="fa-solid fa-peso-sign"></i> Record Payment
                                </button>
                                <?php if(!$report['start_date']): ?>
                                <button onclick="openDateModal(<?= $report['reservation_id'] ?>)" 
                                        class="border border-[#C19A6B] text-[#5C4033] hover:bg-[#C19A6B] hover:text-white px-3 py-1 rounded text-sm transition-all duration-200 font-semibold">
                                    <i class="fa-solid fa-calendar"></i> Set Dates
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="p-8 text-center text-[#5C4033]">
                <i class="fa-solid fa-chart-line text-6xl mb-4 opacity-50 text-[#C19A6B]"></i>
                <h3 class="text-xl font-semibold mb-2">No Active Tenants</h3>
                <p class="opacity-75">No tenant reports available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-[#FFF5E1] rounded-lg shadow-xl border-2 border-[#C19A6B] p-6 w-full max-w-md mx-4">
        <div class="mb-4 pb-3 border-b border-[#E5D3B3]">
            <h3 class="text-lg font-bold text-[#5C4033] flex items-center gap-2">
                <i class="fa-solid fa-peso-sign text-[#C19A6B]"></i>
                Record Payment
            </h3>
        </div>
        <form id="paymentForm" method="POST" action="<?= site_url('admin/reports/updatePayment') ?>">
            <input type="hidden" id="payment_student_id" name="student_id">
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2 text-[#5C4033]">Tenant Name</label>
                <input type="text" id="payment_student_name" class="w-full p-3 border border-[#E5D3B3] rounded-lg bg-[#F5F0E8] text-[#5C4033]" readonly>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2 text-[#5C4033]">Amount Paid *</label>
                <input type="number" id="payment_amount" name="amount_paid" step="0.01" class="w-full p-3 border border-[#E5D3B3] rounded-lg focus:border-[#C19A6B] focus:ring focus:ring-[#C19A6B] focus:ring-opacity-30" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2 text-[#5C4033]">Payment Date *</label>
                <input type="date" name="payment_date" class="w-full p-3 border border-[#E5D3B3] rounded-lg focus:border-[#C19A6B] focus:ring focus:ring-[#C19A6B] focus:ring-opacity-30" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2 text-[#5C4033]">Payment Method</label>
                <select name="payment_method" class="w-full p-3 border border-[#E5D3B3] rounded-lg focus:border-[#C19A6B] focus:ring focus:ring-[#C19A6B] focus:ring-opacity-30">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="gcash">GCash</option>
                    <option value="check">Check</option>
                </select>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" style="background: #C19A6B;" class="hover:bg-[#A67C52] text-white px-6 py-3 rounded-lg flex-1 font-semibold transition-all duration-200">
                    <i class="fa-solid fa-save mr-2"></i>Record Payment
                </button>
                <button type="button" onclick="closePaymentModal()" class="bg-[#8B7355] hover:bg-[#6B5B48] text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Date Modal -->
<div id="dateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-[#FFF5E1] rounded-lg shadow-xl border-2 border-[#C19A6B] p-6 w-full max-w-md mx-4">
        <div class="mb-4 pb-3 border-b border-[#E5D3B3]">
            <h3 class="text-lg font-bold text-[#5C4033] flex items-center gap-2">
                <i class="fa-solid fa-calendar-alt text-[#C19A6B]"></i>
                Set Stay Dates
            </h3>
        </div>
        <form id="dateForm" method="POST" action="<?= site_url('admin/reports/updateStayDates') ?>">
            <input type="hidden" id="date_reservation_id" name="reservation_id">
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2 text-[#5C4033]">Start Date *</label>
                <input type="date" name="start_date" class="w-full p-3 border border-[#E5D3B3] rounded-lg focus:border-[#C19A6B] focus:ring focus:ring-[#C19A6B] focus:ring-opacity-30" required>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2 text-[#5C4033]">End Date</label>
                <input type="date" name="end_date" class="w-full p-3 border border-[#E5D3B3] rounded-lg focus:border-[#C19A6B] focus:ring focus:ring-[#C19A6B] focus:ring-opacity-30">
                <p class="text-xs text-[#5C4033] opacity-75 mt-1">Leave empty for indefinite stay</p>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" style="background: #C19A6B;" class="hover:bg-[#A67C52] text-white px-6 py-3 rounded-lg flex-1 font-semibold transition-all duration-200">
                    <i class="fa-solid fa-calendar-check mr-2"></i>Update Dates
                </button>
                <button type="button" onclick="closeDateModal()" class="bg-[#8B7355] hover:bg-[#6B5B48] text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Current time display
function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
updateTime();
setInterval(updateTime, 60000);

// Modal functions
function openPaymentModal(studentId, studentName, suggestedAmount) {
    document.getElementById('payment_student_id').value = studentId;
    document.getElementById('payment_student_name').value = studentName;
    document.getElementById('payment_amount').value = suggestedAmount.toFixed(2);
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function openDateModal(reservationId) {
    document.getElementById('date_reservation_id').value = reservationId;
    document.getElementById('dateModal').classList.remove('hidden');
}

function closeDateModal() {
    document.getElementById('dateModal').classList.add('hidden');
}

// Message functions
function closeMessage(messageId) {
    const message = document.getElementById(messageId);
    if (message) {
        hideMessage(message);
    }
}

function hideMessage(messageElement) {
    messageElement.style.transition = 'all 0.3s ease';
    messageElement.style.opacity = '0';
    messageElement.style.transform = 'translateY(-20px)';
    setTimeout(() => {
        messageElement.remove();
    }, 300);
}

// Auto-hide messages
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('#successMessage, #errorMessage');
    messages.forEach(message => {
        setTimeout(() => {
            hideMessage(message);
        }, 5000);
    });
});

// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menuBtn');

menuBtn.addEventListener('click', function() {
    sidebar.classList.toggle('show');
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'paymentModal') {
        closePaymentModal();
    }
    if (e.target.id === 'dateModal') {
        closeDateModal();
    }
});

// Payment reminders function
function runPaymentReminders() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Sending...';
    button.disabled = true;
    
    fetch('<?= site_url("console/payment_check") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Payment reminders sent successfully!\n${data.reminders_sent} reminders sent\n${data.overdue_notices} overdue notices sent`);
            window.location.reload();
        } else {
            alert('Error sending reminders: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error sending reminders: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Custom logout confirmation modal
function confirmLogout() {
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

</body>
</html>