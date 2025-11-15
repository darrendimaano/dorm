<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment History - Tenant Portal</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  #sidebar {
    transition: width 0.3s ease, transform 0.3s ease;
    background: #D2B48C; /* warm tan */
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
  body {
    background: linear-gradient(135deg, #FFF5E1 0%, #F5E6D3 100%);
  }
</style>
</head>
<body class="font-sans flex min-h-screen">

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-40 shadow-lg">
  <div class="flex items-center gap-3 mb-8">
    <div class="bg-[#C19A6B] p-2 rounded-lg">
      <i class="fa-solid fa-graduation-cap text-2xl text-white"></i>
    </div>
    <div>
      <h2 class="text-lg font-bold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tenant') ?></h2>
      <p class="text-sm text-[#5C4033] opacity-75">Tenant Portal</p>
    </div>
  </div>
  
  <nav class="flex flex-col gap-2">
    <a href="<?= site_url('user_landing') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-home"></i> <span>Dashboard</span>
    </a>
    <a href="<?= site_url('user/reservations') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-list-check"></i> <span>My Reservations</span>
    </a>
    <a href="<?= site_url('user/payments') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-credit-card"></i> <span>Payment History</span>
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
      <span>Contact: 09517394938</span>
    </div>
    <a href="<?= site_url('auth/logout') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-400 hover:text-white transition">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

<!-- Main Content -->
<div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
  <!-- Header -->
  <div style="background: #FFF5E1;" class="shadow-md flex items-center justify-between px-6 py-4">
    <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div>
      <h1 class="font-bold text-xl text-[#5C4033]">Payment History</h1>
      <p class="text-[#5C4033] opacity-75 text-sm">Track your rent payments and receipts</p>
    </div>
    <div class="text-xs text-[#5C4033] opacity-75">
      <i class="fa-solid fa-phone mr-1"></i>
      09517394938
    </div>
  </div>

  <div class="max-w-6xl mx-auto p-6">
    
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
      <div class="flex items-center gap-2 text-green-800">
        <i class="fa-solid fa-check-circle"></i>
        <span class="font-semibold"><?= htmlspecialchars($_SESSION['success']) ?></span>
      </div>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <div class="flex items-center gap-2 text-red-800">
        <i class="fa-solid fa-exclamation-circle"></i>
        <span class="font-semibold"><?= htmlspecialchars($_SESSION['error']) ?></span>
      </div>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <!-- Payment Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <!-- Current Payment Status -->
      <?php if ($current_reservation): ?>
      <div class="bg-white p-6 rounded-lg shadow-lg border border-[#E5D3B3]">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-[#5C4033]">Current Status</h3>
          <i class="fa-solid fa-home text-[#C19A6B] text-xl"></i>
        </div>
        <div class="mb-2">
          <p class="text-sm text-gray-600">Room: <span class="font-semibold"><?= htmlspecialchars($current_reservation['room_name']) ?></span></p>
          <p class="text-sm text-gray-600">Monthly Rent: <span class="font-semibold">₱<?= number_format($current_reservation['monthly_rate'], 2) ?></span></p>
        </div>
        <div class="mt-4">
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
              Active Reservation
            </span>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Total Payments -->
      <div class="bg-white p-6 rounded-lg shadow-lg border border-[#E5D3B3]">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-[#5C4033]">Total Payments</h3>
          <i class="fa-solid fa-money-bills text-[#C19A6B] text-xl"></i>
        </div>
        <div>
          <p class="text-2xl font-bold text-[#5C4033]"><?= $payment_summary['total_payments'] ?></p>
          <p class="text-sm text-gray-600">Payment transactions</p>
        </div>
        <div class="mt-2">
          <p class="text-lg font-semibold text-[#C19A6B]">₱<?= number_format($payment_summary['total_paid'], 2) ?></p>
          <p class="text-sm text-gray-600">Total amount paid</p>
        </div>
      </div>
      
      <!-- Last Payment -->
      <div class="bg-white p-6 rounded-lg shadow-lg border border-[#E5D3B3]">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-[#5C4033]">Last Payment</h3>
          <i class="fa-solid fa-clock text-[#C19A6B] text-xl"></i>
        </div>
        <div>
          <?php if ($payment_summary['last_payment']): ?>
          <p class="text-lg font-semibold text-[#5C4033]"><?= date('M j, Y', strtotime($payment_summary['last_payment'])) ?></p>
          <p class="text-sm text-gray-600"><?= date('g:i A', strtotime($payment_summary['last_payment'])) ?></p>
          <?php else: ?>
          <p class="text-lg font-semibold text-gray-500">No payments yet</p>
          <p class="text-sm text-gray-600">Make your first payment</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Make Payment Section -->
    <?php if ($current_reservation): ?>
    <div class="bg-white rounded-lg shadow-lg border border-[#E5D3B3] overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-[#E5D3B3] bg-[#FFF5E1]">
        <h3 class="text-lg font-semibold text-[#5C4033] flex items-center gap-2">
          <i class="fa-solid fa-credit-card text-[#C19A6B]"></i>
          Make Payment
        </h3>
        <p class="text-sm text-gray-600 mt-1">Choose your preferred payment method</p>
      </div>
      
      <div class="p-6">
        <form method="POST" action="<?= site_url('user/payments/submit') ?>" class="space-y-6">
          
          <!-- Amount -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-[#5C4033] mb-2">Payment Amount</label>
              <div class="relative">
                <span class="absolute left-3 top-3 text-[#5C4033] font-semibold">₱</span>
                <input type="number" name="amount" step="0.01" min="1" 
                       value="<?= number_format($current_reservation['monthly_rate'], 2) ?>"
                       class="w-full pl-8 pr-4 py-3 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]" 
                       required>
              </div>
              <p class="text-xs text-gray-600 mt-1">Suggested: Monthly rent amount</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-[#5C4033] mb-2">Payment For</label>
              <select name="payment_for" class="w-full px-4 py-3 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]" required>
                <option value="monthly_rent">Monthly Rent</option>
                <option value="advance_payment">Advance Payment</option>
                <option value="utilities">Utilities</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <!-- Payment Method -->
          <div>
            <label class="block text-sm font-medium text-[#5C4033] mb-3">Payment Method</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              
              <!-- GCash -->
              <label class="payment-method-card cursor-pointer">
                <input type="radio" name="payment_method" value="gcash" class="hidden payment-method-radio" required>
                <div class="border-2 border-[#E5D3B3] rounded-lg p-4 text-center transition-all hover:border-[#C19A6B] hover:bg-[#FFF5E1]">
                  <div class="text-blue-600 text-3xl mb-2">
                    <i class="fa-solid fa-mobile-alt"></i>
                  </div>
                  <h4 class="font-semibold text-[#5C4033]">GCash</h4>
                  <p class="text-xs text-gray-600 mt-1">Mobile Payment</p>
                  <div class="mt-2 text-xs text-blue-600 font-medium">Instant Transfer</div>
                </div>
              </label>
              
              <!-- Bank Transfer -->
              <label class="payment-method-card cursor-pointer">
                <input type="radio" name="payment_method" value="bank_transfer" class="hidden payment-method-radio" required>
                <div class="border-2 border-[#E5D3B3] rounded-lg p-4 text-center transition-all hover:border-[#C19A6B] hover:bg-[#FFF5E1]">
                  <div class="text-green-600 text-3xl mb-2">
                    <i class="fa-solid fa-university"></i>
                  </div>
                  <h4 class="font-semibold text-[#5C4033]">Bank Transfer</h4>
                  <p class="text-xs text-gray-600 mt-1">Online Banking</p>
                  <div class="mt-2 text-xs text-green-600 font-medium">Secure & Fast</div>
                </div>
              </label>
              
              <!-- Cash -->
              <label class="payment-method-card cursor-pointer">
                <input type="radio" name="payment_method" value="cash" class="hidden payment-method-radio" required>
                <div class="border-2 border-[#E5D3B3] rounded-lg p-4 text-center transition-all hover:border-[#C19A6B] hover:bg-[#FFF5E1]">
                  <div class="text-[#C19A6B] text-3xl mb-2">
                    <i class="fa-solid fa-money-bills"></i>
                  </div>
                  <h4 class="font-semibold text-[#5C4033]">Cash</h4>
                  <p class="text-xs text-gray-600 mt-1">Pay at Office</p>
                  <div class="mt-2 text-xs text-[#C19A6B] font-medium">Walk-in Payment</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Payment Details Section (Hidden by default) -->
          <div id="payment-details" class="hidden">
            
            <!-- GCash Details -->
            <div id="gcash-details" class="payment-details hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h5 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-mobile-alt"></i>
                GCash Payment Instructions
              </h5>
              <div class="space-y-2 text-sm text-blue-700">
                <p><strong>Step 1:</strong> Send payment to GCash number: <span class="font-mono font-bold">09517394938</span></p>
                <p><strong>Step 2:</strong> Enter reference number below</p>
                <p><strong>Step 3:</strong> Upload screenshot (optional)</p>
              </div>
              <div class="mt-4">
                <label class="block text-sm font-medium text-blue-800 mb-2">GCash Reference Number</label>
                <input type="text" name="gcash_reference" placeholder="Enter 13-digit reference number" 
                       class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
            </div>

            <!-- Bank Transfer Details -->
            <div id="bank-details" class="payment-details hidden bg-green-50 border border-green-200 rounded-lg p-4">
              <h5 class="font-semibold text-green-800 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-university"></i>
                Bank Transfer Instructions
              </h5>
              <div class="space-y-2 text-sm text-green-700">
                <p><strong>Bank:</strong> BPI / BDO / Metrobank</p>
                <p><strong>Account Name:</strong> Dormitory Management System</p>
                <p><strong>Account Number:</strong> <span class="font-mono font-bold">012345678901</span></p>
              </div>
              <div class="mt-4">
                <label class="block text-sm font-medium text-green-800 mb-2">Bank Reference Number</label>
                <input type="text" name="bank_reference" placeholder="Enter bank transaction reference" 
                       class="w-full px-4 py-2 border border-green-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
              </div>
            </div>

            <!-- Cash Details -->
            <div id="cash-details" class="payment-details hidden bg-[#FFF5E1] border border-[#E5D3B3] rounded-lg p-4">
              <h5 class="font-semibold text-[#5C4033] mb-3 flex items-center gap-2">
                <i class="fa-solid fa-money-bills"></i>
                Cash Payment Instructions
              </h5>
              <div class="space-y-2 text-sm text-[#5C4033]">
                <p><strong>Office Hours:</strong> Monday to Friday, 8:00 AM - 5:00 PM</p>
                <p><strong>Location:</strong> Dormitory Admin Office, Ground Floor</p>
                <p><strong>Contact:</strong> 09517394938</p>
                <p class="text-orange-600"><strong>Note:</strong> Please bring this form and a valid ID</p>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-[#5C4033] mb-2">Notes (Optional)</label>
            <textarea name="notes" rows="3" placeholder="Additional notes or comments..." 
                     class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]"></textarea>
          </div>

          <!-- Submit Button -->
          <div class="text-right">
            <button type="submit" class="bg-[#C19A6B] hover:bg-[#A67C52] text-white px-8 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center gap-2 ml-auto">
              <i class="fa-solid fa-paper-plane"></i>
              Submit Payment Request
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Payment History Table -->
    <div class="bg-white rounded-lg shadow-lg border border-[#E5D3B3] overflow-hidden">
      <div class="px-6 py-4 border-b border-[#E5D3B3] flex items-center justify-between">
        <h3 class="text-lg font-semibold text-[#5C4033]">
          <i class="fa-solid fa-receipt mr-2"></i>
          Payment History
        </h3>
        <p class="text-sm text-gray-600"><?= count($payment_history) ?> total payments</p>
      </div>
      
      <?php if (!empty($payment_history)): ?>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-[#F5E6D3]">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Room</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Method</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#E5D3B3]">
            <?php foreach ($payment_history as $payment): ?>
            <tr class="hover:bg-[#FFF5E1] transition">
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-[#5C4033]">
                    <?= date('M j, Y', strtotime($payment['payment_date'])) ?>
                  </div>
                  <div class="text-sm text-gray-500">
                    <?= date('g:i A', strtotime($payment['payment_date'])) ?>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-[#5C4033]"><?= htmlspecialchars($payment['room_name']) ?></div>
                  <div class="text-sm text-gray-500">Room <?= htmlspecialchars($payment['room_number']) ?></div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-[#C19A6B]">₱<?= number_format($payment['amount'], 2) ?></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-[#5C4033]"><?= htmlspecialchars($payment['payment_method']) ?></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                  <i class="fa-solid fa-check-circle"></i> Completed
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex items-center gap-2">
                  <a href="<?= site_url('user/payments/receipt/'.$payment['id']) ?>" 
                     class="text-[#C19A6B] hover:text-[#5C4033] font-medium">
                    <i class="fa-solid fa-eye"></i> View
                  </a>
                  <a href="<?= site_url('user/payments/download_receipt/'.$payment['id']) ?>" 
                     class="text-[#C19A6B] hover:text-[#5C4033] font-medium">
                    <i class="fa-solid fa-download"></i> Download
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="p-12 text-center">
        <i class="fa-solid fa-receipt text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Payment History</h3>
        <p class="text-gray-500">Your payment history will appear here once you make payments.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="bg-[#E5D3B3] border border-[#D2B48C] p-6 rounded-lg mt-6">
      <h4 class="font-semibold text-[#5C4033] mb-4">
        <i class="fa-solid fa-lightbulb"></i> Quick Actions
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="<?= site_url('user_landing') ?>" 
           class="bg-[#C19A6B] hover:bg-[#5C4033] text-white px-4 py-3 rounded-lg text-center font-semibold transition">
          <i class="fa-solid fa-home"></i> Back to Dashboard
        </a>
        <a href="<?= site_url('user/reservations') ?>" 
           class="bg-[#C19A6B] hover:bg-[#5C4033] text-white px-4 py-3 rounded-lg text-center font-semibold transition">
          <i class="fa-solid fa-list-check"></i> My Reservations
        </a>
        <a href="<?= site_url('user/contact') ?>" 
           class="bg-[#C19A6B] hover:bg-[#5C4033] text-white px-4 py-3 rounded-lg text-center font-semibold transition">
          <i class="fa-solid fa-envelope"></i> Contact Admin
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Mobile Menu Overlay -->
<div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

<script>
// Payment method selection functionality
document.addEventListener('DOMContentLoaded', function() {
    const paymentRadios = document.querySelectorAll('.payment-method-radio');
    const paymentCards = document.querySelectorAll('.payment-method-card');
    const paymentDetailsSection = document.getElementById('payment-details');
    const allPaymentDetails = document.querySelectorAll('.payment-details');
    
    // Handle payment method selection
    paymentRadios.forEach((radio, index) => {
        radio.addEventListener('change', function() {
            // Reset all cards
            paymentCards.forEach(card => {
                card.querySelector('div').classList.remove('border-[#C19A6B]', 'bg-[#FFF5E1]', 'ring-2', 'ring-[#C19A6B]');
                card.querySelector('div').classList.add('border-[#E5D3B3]');
            });
            
            // Highlight selected card
            if (this.checked) {
                const selectedCard = this.closest('.payment-method-card').querySelector('div');
                selectedCard.classList.remove('border-[#E5D3B3]');
                selectedCard.classList.add('border-[#C19A6B]', 'bg-[#FFF5E1]', 'ring-2', 'ring-[#C19A6B]');
                
                // Show payment details section
                paymentDetailsSection.classList.remove('hidden');
                
                // Hide all payment details
                allPaymentDetails.forEach(detail => {
                    detail.classList.add('hidden');
                });
                
                // Show relevant payment details
                const selectedMethod = this.value;
                const detailsElement = document.getElementById(selectedMethod + '-details');
                if (detailsElement) {
                    detailsElement.classList.remove('hidden');
                }
            }
        });
    });
    
    // Add click event to cards
    paymentCards.forEach((card, index) => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('.payment-method-radio');
            radio.checked = true;
            radio.dispatchEvent(new Event('change'));
        });
    });
});

// Mobile menu toggle
document.getElementById('mobileMenuToggle').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileMenuOverlay');
    
    sidebar.classList.toggle('open');
    overlay.classList.toggle('hidden');
});

document.getElementById('mobileMenuOverlay').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileMenuOverlay');
    
    sidebar.classList.remove('open');
    overlay.classList.add('hidden');
});

// Close mobile menu when clicking on a link
document.querySelectorAll('#sidebar a').forEach(link => {
    link.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        
        if (window.innerWidth < 768) {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        }
    });
});
</script>

</body>
</html>