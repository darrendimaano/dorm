<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Dormitory Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
  
</style>
</head>
<body class="bg-[#FFF5E1] font-sans flex min-h-screen" id="mainBody">

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-64 p-8 main-content" id="mainContent">

  <div class="flex items-center justify-between mb-8">
    <h1 class="text-3xl font-bold text-[#5C4033]">Settings</h1>
    <div class="flex items-center gap-4">
      <button id="menuBtn" class="md:hidden text-[#5C4033] text-2xl">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>

  <!-- Settings Form -->
  <div class="w-full bg-[#FFF5E1] shadow-lg rounded-xl p-6 border border-[#C19A6B] settings-card mx-4">
    <h2 class="text-xl font-bold mb-4 text-[#5C4033]">Update Settings</h2>

    <form method="POST" class="flex flex-col gap-5">
      <div>
        <label class="text-[#5C4033] font-semibold mb-1 block">Site Name</label>
        <input type="text" name="site_name" value="<?= isset($settings['site_name']) ? $settings['site_name'] : '' ?>"
               class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
      </div>

      <div>
        <label class="text-[#5C4033] font-semibold mb-1 block">Admin Email</label>
        <input type="email" name="admin_email" value="<?= isset($settings['admin_email']) ? $settings['admin_email'] : '' ?>"
               class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
      </div>

      <div>
        <label class="text-[#5C4033] font-semibold mb-1 block">Maintenance Mode</label>
        <select name="maintenance_mode" class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
          <option value="0" <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == 0) ? 'selected' : '' ?>>Off</option>
          <option value="1" <?= (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] == 1) ? 'selected' : '' ?>>On</option>
        </select>
      </div>

      <button type="submit" class="bg-[#C19A6B] hover:bg-[#B07A4B] text-white py-3 rounded-full shadow-md transition-all duration-300 mt-2">
        Save Settings
      </button>
    </form>
  </div>

</div>

<script>
// Sidebar toggle for mobile
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menuBtn');
if (menuBtn && sidebar) {
  menuBtn.addEventListener('click', () => sidebar.classList.toggle('-translate-x-full'));
}

// Handle form submission with loading state
document.addEventListener('DOMContentLoaded', function() {
  const settingsForm = document.querySelector('form');
  if (settingsForm) {
    settingsForm.addEventListener('submit', function(e) {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving Settings...';
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
