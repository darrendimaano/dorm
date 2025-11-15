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
  
  /* Dark mode styles */
  .dark #sidebar {
    background: #1a1a1a;
  }
  .dark body {
    background: #111111 !important;
  }
  .dark .main-content {
    background: #1a1a1a;
    color: #e5e5e5;
  }
  .dark .settings-card {
    background: #2a2a2a !important;
    border-color: #404040 !important;
  }
  .dark input, .dark select {
    background: #333333 !important;
    border-color: #555555 !important;
    color: #e5e5e5 !important;
  }
  .dark h1, .dark h2, .dark label {
    color: #e5e5e5 !important;
  }
  .dark #sidebar a {
    color: #e5e5e5 !important;
  }
</style>
</head>
<body class="bg-[#FFF5E1] font-sans flex min-h-screen transition-colors" id="mainBody">

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-50 shadow-lg">
  <h2 class="text-2xl font-bold mb-8">🏨</h2>
  <nav class="flex flex-col gap-4">
    <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
    </a>
    <a href="<?= site_url('users') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-user"></i> <span>Users</span>
    </a>
<a href="<?=site_url('admin/landing')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
    <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
</a>


    <a href="<?= site_url('rooms') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-bed"></i> <span>Rooms</span>
    </a>
    <a href="<?= site_url('settings') ?>" class="flex items-center gap-2 px-4 py-2 rounded bg-[#C19A6B] text-white transition">
      <i class="fa-solid fa-cog"></i> <span>Settings</span>
    </a>
    <a href="<?= site_url('auth/logout') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-red-400 transition mt-6">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

<!-- Main Content -->
<div class="flex-1 ml-64 p-8 main-content" id="mainContent">

  <div class="flex items-center justify-between mb-8">
    <h1 class="text-3xl font-bold text-[#5C4033]">Settings</h1>
    <div class="flex items-center gap-4">
      <button id="darkModeToggle" class="p-2 rounded-lg border border-[#C19A6B] hover:bg-[#C19A6B] hover:text-white transition">
        <i class="fa-solid fa-moon" id="darkModeIcon"></i>
      </button>
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
        <label class="text-[#5C4033] font-semibold mb-1 block">Dark Mode (Admin)</label>
        <select name="dark_mode_admin" class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
          <option value="0" <?= (isset($settings['dark_mode_admin']) && $settings['dark_mode_admin'] == 0) ? 'selected' : '' ?>>Light Mode</option>
          <option value="1" <?= (isset($settings['dark_mode_admin']) && $settings['dark_mode_admin'] == 1) ? 'selected' : '' ?>>Dark Mode</option>
        </select>
      </div>

      <div>
        <label class="text-[#5C4033] font-semibold mb-1 block">Dark Mode (User)</label>
        <select name="dark_mode_user" class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
          <option value="0" <?= (isset($settings['dark_mode_user']) && $settings['dark_mode_user'] == 0) ? 'selected' : '' ?>>Light Mode</option>
          <option value="1" <?= (isset($settings['dark_mode_user']) && $settings['dark_mode_user'] == 1) ? 'selected' : '' ?>>Dark Mode</option>
        </select>
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
menuBtn.addEventListener('click', () => sidebar.classList.toggle('-translate-x-full'));

// Dark mode functionality
const darkModeToggle = document.getElementById('darkModeToggle');
const darkModeIcon = document.getElementById('darkModeIcon');
const mainBody = document.getElementById('mainBody');

// Check for saved dark mode preference
const isDarkMode = localStorage.getItem('adminDarkMode') === 'true';
if (isDarkMode) {
    mainBody.classList.add('dark');
    darkModeIcon.className = 'fa-solid fa-sun';
}

darkModeToggle.addEventListener('click', () => {
    mainBody.classList.toggle('dark');
    const isDark = mainBody.classList.contains('dark');
    
    // Save preference
    localStorage.setItem('adminDarkMode', isDark);
    
    // Update icon
    darkModeIcon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    
    // Update database setting via AJAX
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'dark_mode_admin=' + (isDark ? '1' : '0') + '&ajax=1'
    });
});

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
</script>

</body>
</html>
