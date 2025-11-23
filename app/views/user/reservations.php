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
<title>My Reservations - Tenant Portal</title>
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

  .dark body {
    background: #111111 !important;
    color: #e5e5e5 !important;
  }
  .dark #sidebar {
    background: #1a1a1a !important;
  }
  .dark [class*="bg-white"], .dark [class*="bg-[#FFF5E1]"] {
    background: #1f1f1f !important;
  }
  .dark [class*="border-[#C19A6B]"], .dark [class*="border-[#E5D3B3]"], .dark [class*="border-blue-200"] {
    border-color: #3a3a3a !important;
  }
  .dark [class*="text-[#5C4033]"],
  .dark [class*="text-gray-600"],
  .dark [class*="text-gray-500"],
  .dark [class*="text-gray-400"] {
    color: #e5e5e5 !important;
  }
  .dark [class*="text-blue-700"], .dark [class*="text-blue-800"] {
    color: #93c5fd !important;
  }
  .dark .shadow-md, .dark .shadow-lg, .dark .shadow-sm {
    box-shadow: 0 10px 30px rgba(0,0,0,0.4) !important;
  }
</style>
</head>
<body class="font-sans flex min-h-screen<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Sidebar -->
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
  <!-- Header -->
  <div style="background: #FFF5E1;" class="shadow-md flex items-center justify-between px-6 py-4">
    <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div>
      <h1 class="font-bold text-xl text-[#5C4033]">My Reservations</h1>
      <p class="text-[#5C4033] opacity-75 text-sm">Track your room reservation requests</p>
    </div>
    <div class="flex items-center gap-4 flex-wrap justify-end">
      <div class="flex items-center gap-2 text-xs text-[#5C4033] opacity-75 dark:text-gray-300 dark:opacity-100">
        <i class="fa-solid fa-phone"></i>
        <span>09517394938</span>
      </div>
    </div>
  </div>

  <div class="w-full px-4 py-4">

    <?php
      $totalReserved = 0;
      $totalApproved = 0;
      $totalAmount = 0.0;
      if(!empty($reservations)) {
          foreach($reservations as $reservation) {
              $payment = isset($reservation['payment']) ? (float) $reservation['payment'] : 0.0;
              $totalAmount += $payment;
              $totalReserved++;
              if(($reservation['status'] ?? '') === 'approved') {
                  $totalApproved++;
              }
          }
      }
    ?>

    <?php if($totalReserved > 0): ?>
      <div class="mb-6 grid gap-4 grid-cols-1 md:grid-cols-3">
        <div class="rounded-xl border border-[#C19A6B] bg-[#FFF5E1] p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-[#5C4033] opacity-70">Total Reservations</p>
          <p class="mt-2 text-2xl font-bold text-[#5C4033]"><?= $totalReserved; ?></p>
        </div>
        <div class="rounded-xl border border-green-300 bg-green-50 p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-green-700 opacity-70">Approved Slots</p>
          <p class="mt-2 text-2xl font-bold text-green-700"><?= $totalApproved; ?></p>
        </div>
        <div class="rounded-xl border border-[#C19A6B] bg-white p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-[#5C4033] opacity-70">Monthly Total Reserved</p>
          <p class="mt-2 text-2xl font-bold text-[#5C4033]">₱<?= number_format($totalAmount, 2); ?></p>
        </div>
      </div>
    <?php endif; ?>
    
    <!-- Success / Error Messages -->
    <?php if(!empty($success)): ?>
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
            <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($reservations)): ?>
      <!-- Reservations List -->
      <div class="grid grid-cols-1 gap-6">
        <?php foreach($reservations as $reservation): ?>
          <div style="background: #FFF5E1;" class="rounded-xl shadow-sm border p-6" style="border-color: #C19A6B;">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              
              <!-- Reservation Info -->
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-xl font-bold text-gray-800">Room #<?= htmlspecialchars($reservation['room_number']) ?></h3>
                  <span class="px-3 py-1 rounded-full text-sm font-semibold 
                    <?= $reservation['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                        ($reservation['status'] == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                    <i class="fa-solid fa-<?= $reservation['status'] == 'approved' ? 'check' : 
                                          ($reservation['status'] == 'rejected' ? 'times' : 'clock') ?>"></i>
                    <?= ucfirst($reservation['status']) ?>
                  </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                  <div>
                    <i class="fa-solid fa-tag text-gray-400"></i>
                    <span class="font-medium">Capacity:</span> <?= htmlspecialchars($reservation['beds'] ?? '1') ?> bed(s)
                  </div>
                  <div>
                    <i class="fa-solid fa-peso-sign text-gray-400"></i>
                    <span class="font-medium">Monthly:</span> ₱<?= number_format($reservation['payment'] ?? 0, 2) ?>
                  </div>
                  <div>
                    <i class="fa-solid fa-calendar text-gray-400"></i>
                    <span class="font-medium">Request ID:</span> #<?= $reservation['id'] ?>
                  </div>
                </div>

                <?php if(isset($reservation['payment']) && (float)$reservation['payment'] > 0): ?>
                  <div class="mt-2 inline-flex items-center gap-2 rounded-full bg-[#C19A6B] bg-opacity-10 px-3 py-1 text-xs font-semibold text-[#5C4033]">
                    <i class="fa-solid fa-sack-dollar"></i>
                    Monthly Due: ₱<?= number_format($reservation['payment'], 2) ?>
                  </div>
                <?php endif; ?>

                <!-- Status Description -->
                <div class="mt-3 p-3 rounded-lg <?= $reservation['status'] == 'approved' ? 'bg-green-50' : 
                                                    ($reservation['status'] == 'rejected' ? 'bg-red-50' : 'bg-yellow-50') ?>">
                  <p class="text-sm <?= $reservation['status'] == 'approved' ? 'text-green-700' : 
                                      ($reservation['status'] == 'rejected' ? 'text-red-700' : 'text-yellow-700') ?>">
                    <?php if($reservation['status'] == 'pending'): ?>
                      <i class="fa-solid fa-hourglass-half"></i> Your reservation request is being reviewed by the admin. You'll be notified once it's processed.
                    <?php elseif($reservation['status'] == 'approved'): ?>
                      <i class="fa-solid fa-party-horn"></i> Congratulations! Your reservation has been approved. Please proceed with the payment.
                    <?php else: ?>
                      <i class="fa-solid fa-info-circle"></i> Your reservation request was not approved. You may submit a new request.
                    <?php endif; ?>
                  </p>
                </div>
              </div>
              
              <!-- Actions -->
              <div class="flex flex-col gap-2 md:w-40">
                <?php if($reservation['status'] == 'approved'): ?>
                  <a href="<?= site_url('user/payments') ?>" class="bg-[#C19A6B] hover:bg-[#A67C52] text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all text-center">
                    <i class="fa-solid fa-credit-card"></i> Pay Now
                  </a>
                <?php elseif($reservation['status'] == 'rejected'): ?>
                  <a href="<?= site_url('user_landing') ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold text-center transition-all">
                    <i class="fa-solid fa-refresh"></i> New Request
                  </a>
                <?php else: ?>
                  <div class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-semibold text-center">
                    <i class="fa-solid fa-clock"></i> Waiting...
                  </div>
                <?php endif; ?>
              </div>
              
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- No Reservations -->
      <div class="bg-white p-12 rounded-xl shadow-sm border text-center">
        <div class="text-gray-400 mb-6">
          <i class="fa-solid fa-bed text-6xl"></i>
        </div>
        <h3 class="text-2xl font-semibold text-gray-600 mb-4">No Reservations Yet</h3>
        <p class="text-gray-500 mb-6">You haven't made any room reservation requests yet.</p>
        <a href="<?= site_url('user_landing') ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all inline-block">
          <i class="fa-solid fa-plus"></i> Make Your First Reservation
        </a>
      </div>
    <?php endif; ?>

    <!-- Help Section -->
    <div class="bg-blue-50 border border-blue-200 p-6 rounded-xl mt-6">
      <h4 class="font-semibold text-blue-800 mb-2">
        <i class="fa-solid fa-question-circle"></i> Need Help?
      </h4>
      <p class="text-blue-700 text-sm mb-3">
        Have questions about your reservations or the approval process?
      </p>
      <a href="<?= site_url('user/contact') ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
        <i class="fa-solid fa-envelope"></i> Contact Admin
      </a>
    </div>
  </div>
</div>

<!-- Mobile sidebar overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

<script>
// Sidebar functionality
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (menuBtn) {
  menuBtn.addEventListener('click', function() {
    if (window.innerWidth < 768) {
      sidebar.classList.toggle('open');
      sidebarOverlay.classList.toggle('hidden');
    }
  });
}

if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', function() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.add('hidden');
  });
}

window.addEventListener('resize', function() {
  if (window.innerWidth >= 768) {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.add('hidden');
  } else {
    mainContent.classList.remove('ml-64', 'ml-16');
  }
});

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