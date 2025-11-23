<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;
$userDisplayName = trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? ''));
if ($userDisplayName !== '') {
  $_SESSION['user_name'] = $userDisplayName;
}
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - Tenant Portal</title>
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
  .dark [class*="border-[#C19A6B]"] {
    border-color: #3a3a3a !important;
  }
  .dark [class*="text-[#5C4033]"],
  .dark [class*="text-gray-600"],
  .dark [class*="text-gray-500"] {
    color: #e5e5e5 !important;
  }
  .dark [class*="opacity-75"], .dark [class*="opacity-60"] {
    color: rgba(229, 229, 229, 0.75) !important;
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
      <h1 class="font-bold text-xl text-[#5C4033]">My Profile</h1>
      <p class="text-[#5C4033] opacity-75 text-sm">Manage your personal information</p>
    </div>
    <div class="flex items-center gap-4 flex-wrap justify-end">
      <div class="flex items-center gap-2 text-xs text-[#5C4033] opacity-75 dark:text-gray-300 dark:opacity-100">
        <i class="fa-solid fa-phone"></i>
        <span>09517394938</span>
      </div>
    </div>
  </div>

  <div class="w-full px-4 py-4">
    
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Profile Card -->
      <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border" style="border-color: #C19A6B;">
        <div class="text-center">
          <div class="bg-gradient-to-br" style="background: linear-gradient(to bottom right, #C19A6B, #D2B48C);" class="w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-user text-white text-3xl"></i>
          </div>
          <h2 class="text-xl font-bold text-[#5C4033]"><?= htmlspecialchars($user['fname'] ?? '') ?> <?= htmlspecialchars($user['lname'] ?? '') ?></h2>
          <p class="text-[#5C4033] opacity-75"><?= htmlspecialchars($user['email'] ?? '') ?></p>
          <div class="mt-4 pt-4 border-t" style="border-color: #C19A6B;">
            <p class="text-sm text-[#5C4033] opacity-60">Member since</p>
            <p class="font-semibold text-[#5C4033]">Tenant</p>
          </div>
        </div>
      </div>

      <!-- Edit Profile Form -->
      <div class="lg:col-span-2">
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border" style="border-color: #C19A6B;">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-6">
            <i class="fa-solid fa-edit text-[#C19A6B]"></i> Edit Profile Information
          </h3>
          
          <form method="POST" action="<?= site_url('user/profile/update') ?>" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-[#5C4033] mb-2">First Name</label>
                <input type="text" name="fname" value="<?= htmlspecialchars($user['fname'] ?? '') ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2" style="border-color: #C19A6B; --tw-ring-color: #C19A6B;" required>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-[#5C4033] mb-2">Last Name</label>
                <input type="text" name="lname" value="<?= htmlspecialchars($user['lname'] ?? '') ?>" 
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2" style="border-color: #C19A6B; --tw-ring-color: #C19A6B;" required>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-[#5C4033] mb-2">Email Address</label>
              <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                     class="w-full px-4 py-2 border rounded-lg focus:ring-2" style="border-color: #C19A6B; --tw-ring-color: #C19A6B;" required>
            </div>

            <div>
              <label class="block text-sm font-medium text-[#5C4033] mb-2">New Password (leave blank to keep current)</label>
              <input type="password" name="password" 
                     class="w-full px-4 py-2 border rounded-lg focus:ring-2" style="border-color: #C19A6B; --tw-ring-color: #C19A6B;"
                     placeholder="Enter new password">
              <p class="text-sm text-[#5C4033] opacity-60 mt-1">Leave empty if you don't want to change your password</p>
            </div>

            <div class="flex gap-3 pt-4">
              <button type="submit" class="text-white px-6 py-2 rounded-lg font-semibold transition-all hover:bg-[#B07A4B]" style="background: #C19A6B;">
                <i class="fa-solid fa-save"></i> Update Profile
              </button>
              <button type="reset" class="bg-[#e6ddd4] hover:bg-[#d1c5b3] text-[#5C4033] px-6 py-2 rounded-lg font-semibold transition-all">
                <i class="fa-solid fa-undo"></i> Reset
              </button>
            </div>
          </form>
        </div>
      </div>
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

// Handle form submission with loading state
document.addEventListener('DOMContentLoaded', function() {
  const profileForm = document.querySelector('form[action*="profile/update"]');
  if (profileForm) {
    profileForm.addEventListener('submit', function(e) {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
        submitButton.disabled = true;
        
        // Re-enable button after timeout as fallback
        setTimeout(() => {
          submitButton.innerHTML = originalText;
          submitButton.disabled = false;
        }, 10000);
      }
    });
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