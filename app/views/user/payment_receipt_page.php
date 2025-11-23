<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;

$stayStartDisplay = !empty($payment['stay_start_date']) ? date('M j, Y', strtotime($payment['stay_start_date'])) : null;
$stayEndDisplay = !empty($payment['stay_end_date']) ? date('M j, Y', strtotime($payment['stay_end_date'])) : null;
$monthlyDueDisplay = !empty($payment['monthly_due_date']) ? date('M j, Y', strtotime($payment['monthly_due_date'])) : null;
$lastPaymentDisplay = !empty($payment['last_payment_date']) ? date('M j, Y', strtotime($payment['last_payment_date'])) : null;
$billingMonth = $payment['billing_month'] ?? null;

$methodKey = strtolower($payment['payment_method'] ?? 'payment');
$methodLabel = ucwords(str_replace('_', ' ', $payment['payment_method'] ?? 'Payment'));
$methodIcon = 'fa-receipt';
$methodAccent = 'text-[#C19A6B]';
$methodBadge = 'bg-[#C19A6B] text-white';

if ($methodKey === 'gcash') {
    $methodIcon = 'fa-mobile-screen-button';
    $methodAccent = 'text-blue-600';
    $methodBadge = 'bg-blue-600 text-white';
} elseif ($methodKey === 'bank_transfer') {
    $methodIcon = 'fa-university';
    $methodAccent = 'text-green-600';
    $methodBadge = 'bg-green-600 text-white';
} elseif ($methodKey === 'cash') {
    $methodIcon = 'fa-money-bill-wave';
    $methodAccent = 'text-[#C19A6B]';
    $methodBadge = 'bg-[#C19A6B] text-white';
}
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt #<?= htmlspecialchars(str_pad($payment['id'], 6, '0', STR_PAD_LEFT)) ?> - Tenant Portal</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  #sidebar {
    transition: width 0.3s ease, transform 0.3s ease;
    background: #D2B48C;
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
  .dark [class*="text-gray-500"],
  .dark [class*="text-gray-400"] {
    color: #e5e5e5 !important;
  }
  .dark .shadow-lg {
    box-shadow: 0 12px 30px rgba(0,0,0,0.45) !important;
  }
</style>
</head>
<body class="font-sans flex min-h-screen<?= $darkModeEnabled ? ' dark' : '' ?>">

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
  <div class="bg-[#FFF5E1] shadow-md px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
        <i class="fa-solid fa-bars"></i>
      </button>
      <a href="<?= site_url('user/payments') ?>" class="text-[#C19A6B] hover:text-[#5C4033] font-semibold text-sm flex items-center gap-2">
        <i class="fa-solid fa-arrow-left"></i> Back to Payment History
      </a>
      <span class="hidden md:inline text-[#5C4033] opacity-60">|</span>
      <span class="text-[#5C4033] font-semibold text-sm">Receipt #<?= htmlspecialchars(str_pad($payment['id'], 6, '0', STR_PAD_LEFT)) ?></span>
    </div>
    <div class="flex items-center gap-3">
      <a href="<?= site_url('user/payments/receipt/'.$payment['id']) ?>" target="_blank"
         class="bg-[#C19A6B] hover:bg-[#A67C52] text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
        <i class="fa-solid fa-print"></i> Printable Receipt
      </a>
    </div>
  </div>

  <div class="px-6 py-6 space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="bg-white border border-[#E5D3B3] rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500">Amount Paid</p>
          <i class="fa-solid fa-peso-sign <?= $methodAccent ?> text-xl"></i>
        </div>
        <p class="text-3xl font-bold text-[#5C4033]">₱<?= number_format($payment['amount'], 2) ?></p>
        <p class="text-xs text-gray-500 mt-2">Paid on <?= date('F j, Y g:i A', strtotime($payment['payment_date'])) ?></p>
      </div>
      <div class="bg-white border border-[#E5D3B3] rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500">Payment Method</p>
          <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $methodBadge ?> flex items-center gap-2">
            <i class="fa-solid <?= $methodIcon ?>"></i>
            <?= htmlspecialchars($methodLabel) ?>
          </span>
        </div>
        <p class="text-lg font-semibold text-[#5C4033]"><?= htmlspecialchars($payment['method_summary']) ?></p>
      </div>
      <div class="bg-white border border-[#E5D3B3] rounded-xl p-6 shadow-lg">
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-500">Billing Information</p>
          <i class="fa-solid fa-calendar-days text-[#C19A6B] text-xl"></i>
        </div>
        <p class="text-lg font-semibold text-[#5C4033]">
          <?= $billingMonth ? htmlspecialchars($billingMonth) : 'Billing period not recorded' ?>
        </p>
        <?php if ($monthlyDueDisplay): ?>
        <p class="text-sm text-gray-600">Next Due Date: <?= htmlspecialchars($monthlyDueDisplay) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="bg-white border border-[#E5D3B3] rounded-xl shadow-lg">
      <div class="px-6 py-4 border-b border-[#E5D3B3] flex items-center justify-between">
        <h2 class="text-xl font-bold text-[#5C4033] flex items-center gap-3">
          <i class="fa-solid fa-file-invoice-dollar"></i> Receipt Details
        </h2>
        <span class="text-sm text-gray-500">Generated <?= date('F j, Y g:i A') ?></span>
      </div>
      <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-[#5C4033] flex items-center gap-2">
            <i class="fa-solid fa-user"></i> Tenant Information
          </h3>
          <div class="space-y-2 text-sm text-gray-600">
            <div>
              <span class="font-medium">Full Name:</span>
              <span class="text-[#5C4033] font-semibold"><?= htmlspecialchars($payment['fname'] . ' ' . $payment['lname']) ?></span>
            </div>
            <div>
              <span class="font-medium">Student ID:</span>
              <span class="text-[#5C4033] font-semibold"><?= htmlspecialchars($payment['student_number']) ?></span>
            </div>
            <div>
              <span class="font-medium">Email:</span>
              <span class="text-[#5C4033] font-semibold"><?= htmlspecialchars($payment['email']) ?></span>
            </div>
          </div>
        </div>
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-[#5C4033] flex items-center gap-2">
            <i class="fa-solid fa-building"></i> Room Information
          </h3>
          <div class="space-y-2 text-sm text-gray-600">
            <div>
              <span class="font-medium">Room:</span>
              <span class="text-[#5C4033] font-semibold"><?= htmlspecialchars($payment['room_name']) ?></span>
            </div>
            <div>
              <span class="font-medium">Room Number:</span>
              <span class="text-[#5C4033] font-semibold"><?= htmlspecialchars($payment['room_number']) ?></span>
            </div>
            <div>
              <span class="font-medium">Stay Period:</span>
              <span class="text-[#5C4033] font-semibold">
                <?php if ($stayStartDisplay && $stayEndDisplay): ?>
                  <?= htmlspecialchars($stayStartDisplay) ?> - <?= htmlspecialchars($stayEndDisplay) ?>
                <?php elseif ($stayStartDisplay): ?>
                  <?= htmlspecialchars($stayStartDisplay) ?>
                <?php else: ?>
                  Not recorded
                <?php endif; ?>
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="px-6 pb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-[#FFF5E1] border border-[#E5D3B3] rounded-lg p-5">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-4 flex items-center gap-2">
            <i class="fa-solid fa-credit-card"></i> Payment Breakdown
          </h3>
          <div class="space-y-3 text-sm text-gray-600">
            <div class="flex justify-between">
              <span>Amount Paid</span>
              <span class="text-[#C19A6B] font-semibold">₱<?= number_format($payment['amount'], 2) ?></span>
            </div>
            <div class="flex justify-between">
              <span>Payment Method</span>
              <span class="text-[#5C4033] font-semibold"><?= htmlspecialchars($methodLabel) ?></span>
            </div>
            <?php if ($billingMonth): ?>
            <div class="flex justify-between">
              <span>Billing Month</span>
              <span class="text-[#5C4033] font-semibold"><?= htmlspecialchars($billingMonth) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($monthlyDueDisplay): ?>
            <div class="flex justify-between">
              <span>Next Due Date</span>
              <span class="text-[#5C4033] font-semibold"><?= htmlspecialchars($monthlyDueDisplay) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="bg-white border border-dashed border-[#E5D3B3] rounded-lg p-5">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-info"></i> Method Notes
          </h3>
          <p class="text-sm text-[#5C4033] leading-relaxed">
            <?= htmlspecialchars($payment['method_details'] ?? 'Payment has been recorded successfully.') ?>
          </p>
          <?php if (!empty($payment['transaction_reference'])): ?>
          <p class="text-xs text-gray-500 mt-3">Reference: <?= htmlspecialchars($payment['transaction_reference']) ?></p>
          <?php endif; ?>
          <?php if ($lastPaymentDisplay): ?>
          <p class="text-xs text-gray-500 mt-1">Last Payment Date: <?= htmlspecialchars($lastPaymentDisplay) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($payment['notes'])): ?>
      <div class="px-6 pb-6">
        <div class="bg-white border border-[#E5D3B3] rounded-lg p-5">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-3 flex items-center gap-2">
            <i class="fa-solid fa-sticky-note"></i> Tenant Notes
          </h3>
          <p class="text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="bg-[#E5D3B3] border border-[#D2B48C] rounded-xl p-5 text-sm text-[#5C4033]">
      <p>This receipt confirms your payment recorded in the Dormitory Management System. For concerns, contact the admin office at 09517394938.</p>
    </div>
  </div>
</div>

<div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

<script>
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

if (menuBtn) {
  menuBtn.addEventListener('click', function() {
    sidebar.classList.toggle('open');
    mobileMenuOverlay.classList.toggle('hidden');
  });
}

if (mobileMenuOverlay) {
  mobileMenuOverlay.addEventListener('click', function() {
    sidebar.classList.remove('open');
    mobileMenuOverlay.classList.add('hidden');
  });
}
</script>

</body>
</html>
