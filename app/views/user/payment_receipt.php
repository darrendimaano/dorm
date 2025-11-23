<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;

$stayStartDisplay = !empty($payment['stay_start_date']) ? date('M j, Y', strtotime($payment['stay_start_date'])) : null;
$stayEndDisplay = !empty($payment['stay_end_date']) ? date('M j, Y', strtotime($payment['stay_end_date'])) : null;
$monthlyDueDisplay = !empty($payment['monthly_due_date']) ? date('M j, Y', strtotime($payment['monthly_due_date'])) : null;
$lastPaymentDisplay = !empty($payment['last_payment_date']) ? date('M j, Y', strtotime($payment['last_payment_date'])) : null;
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Receipt #<?= $payment['id'] ?> - Tenant Portal</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  body {
    background: linear-gradient(135deg, #FFF5E1 0%, #F5E6D3 100%);
  }
  .dark body {
    background: #111111 !important;
    color: #e5e5e5 !important;
  }
  .dark [class*="bg-white"], .dark [class*="bg-[#FFF5E1]"] {
    background: #1f1f1f !important;
  }
  .dark [class*="border-[#E5D3B3]"] {
    border-color: #3a3a3a !important;
  }
  .dark [class*="text-[#5C4033]"],
  .dark [class*="text-gray-600"],
  .dark [class*="text-gray-500"] {
    color: #e5e5e5 !important;
  }
  .dark [class*="text-yellow-700"], .dark [class*="text-yellow-800"] {
    color: #fde68a !important;
  }
  .dark .bg-yellow-50 { background: #3a2f1a !important; }
  @media print {
    body { background: white !important; }
    .no-print { display: none !important; }
    .print-only { display: block !important; }
  }
</style>
</head>
<body class="font-sans<?= $darkModeEnabled ? ' dark' : '' ?>">

<div class="min-h-screen p-6">
  <div class="w-full px-4">
    
    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-6 no-print flex-wrap gap-4">
      <a href="<?= site_url('user/payments') ?>" 
         class="flex items-center gap-2 text-[#C19A6B] hover:text-[#5C4033] font-semibold">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Payment History
      </a>
      <div class="flex items-center gap-4">
        <div class="flex gap-3">
        <button onclick="window.print()" 
                class="bg-[#C19A6B] hover:bg-[#5C4033] text-white px-4 py-2 rounded-lg font-semibold transition">
          <i class="fa-solid fa-print"></i> Print Receipt
        </button>
        </div>
      </div>
    </div>

    <!-- Receipt -->
    <div class="bg-white shadow-lg rounded-lg border border-[#E5D3B3] overflow-hidden">
      
      <!-- Header -->
      <div class="bg-[#5C4033] text-white p-6">
        <div class="text-center">
          <div class="mb-4">
            <i class="fa-solid fa-building text-4xl mb-2"></i>
            <h1 class="text-2xl font-bold">DORMITORY MANAGEMENT SYSTEM</h1>
            <p class="text-sm opacity-90">Official Payment Receipt</p>
          </div>
          <div class="bg-white bg-opacity-20 rounded-lg p-3 inline-block">
            <p class="text-lg font-semibold">Receipt #<?= str_pad($payment['id'], 6, '0', STR_PAD_LEFT) ?></p>
          </div>
        </div>
      </div>

      <!-- Receipt Details -->
      <div class="p-6">
        
        <!-- Date and Status -->
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-[#E5D3B3]">
          <div>
            <p class="text-sm text-gray-600">Payment Date</p>
            <p class="text-lg font-semibold text-[#5C4033]">
              <?= date('F j, Y', strtotime($payment['payment_date'])) ?>
            </p>
            <p class="text-sm text-gray-600"><?= date('g:i A', strtotime($payment['payment_date'])) ?></p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Status</p>
            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
              <i class="fa-solid fa-check-circle"></i> Completed
            </span>
          </div>
        </div>

        <!-- Tenant Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
            <h3 class="text-lg font-semibold text-[#5C4033] mb-3 flex items-center gap-2">
              <i class="fa-solid fa-user"></i>
              Tenant Information
            </h3>
            <div class="space-y-2">
              <div>
                <p class="text-sm text-gray-600">Full Name</p>
                <p class="font-semibold text-[#5C4033]"><?= htmlspecialchars($payment['fname']) ?> <?= htmlspecialchars($payment['lname']) ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Student ID</p>
                <p class="font-semibold text-[#5C4033]"><?= htmlspecialchars($payment['student_number']) ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="font-semibold text-[#5C4033]"><?= htmlspecialchars($payment['email']) ?></p>
              </div>
            </div>
          </div>
          
          <!-- Room Information -->
          <div>
            <h3 class="text-lg font-semibold text-[#5C4033] mb-3 flex items-center gap-2">
              <i class="fa-solid fa-home"></i>
              Room Information
            </h3>
            <div class="space-y-2">
              <div>
                <p class="text-sm text-gray-600">Room</p>
                <p class="font-semibold text-[#5C4033]"><?= htmlspecialchars($payment['room_name']) ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Room Number</p>
                <p class="font-semibold text-[#5C4033]"><?= htmlspecialchars($payment['room_number']) ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Stay Period</p>
                <p class="font-semibold text-[#5C4033]">
                  <?php if ($stayStartDisplay && $stayEndDisplay): ?>
                    <?= htmlspecialchars($stayStartDisplay) ?> - <?= htmlspecialchars($stayEndDisplay) ?>
                  <?php elseif ($stayStartDisplay): ?>
                    <?= htmlspecialchars($stayStartDisplay) ?>
                  <?php else: ?>
                    <span class="text-gray-500">Not recorded</span>
                  <?php endif; ?>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Details -->
        <div class="bg-[#FFF5E1] border border-[#E5D3B3] rounded-lg p-6 mb-6">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-4 flex items-center gap-2">
            <i class="fa-solid fa-credit-card"></i>
            Payment Details
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600">Amount Paid:</span>
                <span class="font-bold text-xl text-[#C19A6B]">₱<?= number_format($payment['amount'], 2) ?></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Payment Method:</span>
                <span class="font-semibold text-[#5C4033]"><?= htmlspecialchars($payment['payment_method']) ?></span>
              </div>
              <?php if ($monthlyDueDisplay): ?>
              <div class="flex justify-between">
                <span class="text-gray-600">Next Due Date:</span>
                <span class="font-semibold text-[#5C4033]"><?= htmlspecialchars($monthlyDueDisplay) ?></span>
              </div>
              <?php endif; ?>
            </div>
            
            <div class="space-y-3">
              <?php if (!empty($payment['transaction_reference'])): ?>
              <div class="flex justify-between">
                <span class="text-gray-600">Transaction ID:</span>
                <span class="font-semibold text-[#5C4033] font-mono text-sm"><?= htmlspecialchars($payment['transaction_reference']) ?></span>
              </div>
              <?php endif; ?>
              <?php if ($lastPaymentDisplay): ?>
              <div class="flex justify-between">
                <span class="text-gray-600">Last Payment Date:</span>
                <span class="font-semibold text-[#5C4033]"><?= htmlspecialchars($lastPaymentDisplay) ?></span>
              </div>
              <?php endif; ?>
              <div class="flex justify-between">
                <span class="text-gray-600">Processing Fee:</span>
                <span class="font-semibold text-[#5C4033]">₱0.00</span>
              </div>
            </div>
          </div>
          
          <div class="border-t border-[#E5D3B3] pt-4 mt-4">
            <div class="flex justify-between items-center">
              <span class="text-lg font-semibold text-[#5C4033]">Total Amount:</span>
              <span class="text-2xl font-bold text-[#C19A6B]">₱<?= number_format($payment['amount'], 2) ?></span>
            </div>
          </div>
        </div>

        <?php if (!empty($payment['method_summary'])): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-5 mb-6">
          <h4 class="text-lg font-semibold text-green-800 mb-2 flex items-center gap-2">
            <i class="fa-solid fa-calendar-check"></i>
            Payment Confirmation
          </h4>
          <p class="text-green-700 font-medium"><?= htmlspecialchars($payment['method_summary']) ?></p>
          <?php if (!empty($payment['billing_month'])): ?>
            <div class="inline-flex items-center gap-2 mt-3 px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-semibold">
              <i class="fa-solid fa-calendar"></i>
              Billing Month: <?= htmlspecialchars($payment['billing_month']) ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($payment['method_details'])): ?>
            <p class="text-sm text-green-700 mt-3"><?= htmlspecialchars($payment['method_details']) ?></p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
          <h4 class="font-semibold text-yellow-800 mb-2">
            <i class="fa-solid fa-info-circle"></i> Important Notes
          </h4>
          <ul class="text-sm text-yellow-700 space-y-1">
            <li>• This receipt serves as proof of payment for dormitory accommodation.</li>
            <li>• Please keep this receipt for your records.</li>
            <li>• For any payment inquiries, contact the admin office.</li>
            <li>• Payment is non-refundable once processed.</li>
          </ul>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500 pt-4 border-t border-[#E5D3B3]">
          <p>This is an official receipt generated by the Dormitory Management System.</p>
          <p>Generated on <?= date('F j, Y g:i A') ?></p>
          <p class="mt-2">
            <i class="fa-solid fa-phone"></i> Contact: 09517394938 | 
            <i class="fa-solid fa-envelope"></i> Email: admin@dormitory.com
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

</body>
</html>