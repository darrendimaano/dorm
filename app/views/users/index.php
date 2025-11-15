<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users - Dormitory Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  /* Sidebar collapsed style */
  #sidebar {
    transition: width 0.3s ease, transform 0.3s ease;
    background: #D2B48C; /* warm tan */
  }
  #sidebar.collapsed {
    width: 4rem; /* icons only */
  }
  #sidebar.collapsed nav a span {
    display: none;
  }
  #sidebar.collapsed nav a {
    justify-content: center;
  }
  #sidebar:hover.collapsed {
    width: 16rem;
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
 <a href="<?= site_url('admin/reservations') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
    <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
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
<div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
  <div class="bg-[#FFF5E1] shadow-md flex items-center justify-between px-4 py-3 md:ml-0">
    <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
      <i class="fa-solid fa-bars"></i>
    </button>
    <h1 class="font-bold text-lg text-[#5C4033]">Users</h1>
  </div>

  <div class="max-w-6xl mx-auto mt-6 px-4">

    <!-- Management Links Section -->
    <div class="mb-6 rounded-lg p-6 shadow-lg border border-[#C19A6B]" style="background: #FFF5E1;">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <i class="fas fa-users text-2xl mr-3 text-[#C19A6B]"></i>
          <div>
            <h3 class="text-xl font-bold text-[#5C4033]">User Management</h3>
            <p class="text-[#5C4033] opacity-75">Manage registered users and current tenants</p>
          </div>
        </div>
        <div class="flex gap-3">
          <a href="<?= site_url('users/tenants'); ?>" class="text-white px-4 py-2 rounded-lg font-semibold transition duration-300 flex items-center hover:bg-[#B07A4B]" style="background: #C19A6B;">
            <i class="fas fa-bed mr-2"></i>View Tenants/Occupants
          </a>
        </div>
      </div>
    </div>

    <!-- Add User Form -->
    <div id="addUserForm" class="mb-6 bg-[#FFF5E1] shadow-lg rounded-2xl p-6 border border-[#C19A6B] hidden">
      <h2 class="text-xl font-bold mb-4 text-[#5C4033]">Add New User</h2>
      <form method="POST" action="<?=site_url('users/create')?>">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <input type="text" name="lname" placeholder="Lastname" class="border p-2 rounded w-full" required>
          <input type="text" name="fname" placeholder="Firstname" class="border p-2 rounded w-full" required>
          <input type="email" name="email" placeholder="Email" class="border p-2 rounded w-full" required>
        </div>
        <button type="submit" class="mt-4 bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-5 py-2 rounded-full shadow-md transition-all duration-300">Add User</button>
      </form>
    </div>

    <div class="flex justify-end mb-6">
      <button id="showAddFormBtn" class="inline-flex items-center gap-2 bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-5 py-2 rounded-full shadow-md transition-all duration-300">
        <i class="fa-solid fa-user-plus"></i> Add User
      </button>
    </div>

    <!-- Users Table -->
    <div class="overflow-x-auto rounded-2xl border border-[#C19A6B] shadow bg-[#FFF5E1]">
      <table class="w-full text-center border-collapse">
        <thead>
          <tr class="bg-[#C19A6B] text-white text-sm uppercase tracking-wide">
            <th class="py-3 px-4">ID</th>
            <th class="py-3 px-4">Lastname</th>
            <th class="py-3 px-4">Firstname</th>
            <th class="py-3 px-4">Email</th>
            <th class="py-3 px-4">Action</th>
          </tr>
        </thead>
        <tbody class="text-[#5C4033] text-sm">
          <?php foreach(html_escape($users) as $user): ?>
          <tr class="hover:bg-[#FFEFD5] transition duration-200">
            <td class="py-3 px-4 font-medium"><?=($user['id']);?></td>
            <td class="py-3 px-4"><?=($user['lname']);?></td>
            <td class="py-3 px-4"><?=($user['fname']);?></td>
            <td class="py-3 px-4"><?=($user['email']);?></td>
            <td class="py-3 px-4 flex justify-center gap-3">
              <a href="<?=site_url('users/update/'.$user['id']);?>"
                 class="bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-3 py-1 rounded-lg shadow flex items-center gap-1 transition duration-200">
                <i class="fa-solid fa-pen-to-square"></i> Update
              </a>
              <a href="<?=site_url('users/delete/'.$user['id']);?>"
                 class="bg-red-400 hover:bg-red-500 text-white px-3 py-1 rounded-lg shadow flex items-center gap-1 transition duration-200">
                <i class="fa-solid fa-trash"></i> Delete
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<script>
// Sidebar toggle for mobile
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menuBtn');
menuBtn.addEventListener('click', () => sidebar.classList.toggle('-translate-x-full'));

// Show Add User form
const showAddBtn = document.getElementById('showAddFormBtn');
const addForm = document.getElementById('addUserForm');
showAddBtn.addEventListener('click', () => addForm.classList.toggle('hidden'));

// Handle Add User form submission
document.addEventListener('DOMContentLoaded', function() {
  const userForm = document.querySelector('#addUserForm form');
  if (userForm) {
    userForm.addEventListener('submit', function(e) {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding User...';
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
