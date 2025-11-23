<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dormitory Landing Page</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-[#FFF5E1] font-sans">

<!-- Navbar -->
<nav class="bg-[#C19A6B] text-white shadow-md">
  <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
    <div class="text-2xl font-bold flex items-center">
      <i class="fas fa-home mr-2"></i>Dormitory Rooms
    </div>
    <div class="flex items-center space-x-3">
      <div class="hidden md:block text-sm text-[#FFF5E1] mr-4">
        <i class="fas fa-info-circle mr-1"></i>New here? Create an account or sign in
      </div>
      <a href="<?= site_url('auth/login') ?>" class="flex items-center hover:bg-[#B07A4B] px-4 py-2 rounded transition duration-300">
        <i class="fas fa-sign-in-alt mr-2"></i>Login
      </a>
      <a href="<?= site_url('auth/register') ?>" class="flex items-center bg-[#D2B48C] hover:bg-[#B07A4B] text-[#5C4033] px-4 py-2 rounded transition duration-300 font-semibold">
        <i class="fas fa-user-plus mr-2"></i>Register
      </a>
    </div>
  </div>
</nav>

<!-- Hero / Intro -->
<header class="text-center py-10">
  <h1 class="text-4xl font-bold text-[#5C4033] mb-2">Find Your Room</h1>
  <p class="text-[#5C4033]">Browse available dormitory rooms and pick the one that suits you.</p>
</header>

<!-- Rooms Grid -->
<main class="max-w-6xl mx-auto px-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pb-10">
  <?php if(!empty($rooms)): ?>
    <?php foreach($rooms as $room): ?>
      <div class="bg-white p-6 rounded-2xl shadow-lg border border-[#C19A6B] relative">
        <h2 class="text-xl font-bold mb-2 text-[#5C4033]">Room <?= $room['room_number']; ?></h2>
        <p class="text-[#5C4033]">Beds: <?= $room['beds']; ?></p>
        <p class="text-[#5C4033]">Available: <?= $room['available']; ?></p>
        <p class="text-[#5C4033]">Payment: ₱<?= $room['payment']; ?></p>

        <span class="availabilityBadge mt-2 inline-block px-2 py-1 rounded-full font-semibold cursor-pointer <?= $room['available'] > 0 ? 'bg-green-500 text-white' : 'bg-red-500 text-white' ?>">
          <?= $room['available'] > 0 ? 'Available' : 'Full' ?>
        </span>

      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="text-center col-span-full text-[#5C4033]">No rooms available at the moment.</p>
  <?php endif; ?>
</main>

<script>
document.querySelectorAll('.availabilityBadge').forEach(badge => {
  badge.addEventListener('click', () => {
    const card = badge.parentElement;

    // Remove existing popup if any
    const existing = card.querySelector('.popupMsg');
    if(existing) existing.remove();

    // Create popup message
    const msg = document.createElement('div');
    msg.className = 'popupMsg absolute -top-10 left-1/2 transform -translate-x-1/2 bg-[#FFEFD5] border-l-4 border-[#D2B48C] text-[#5C4033] px-4 py-2 rounded shadow-md text-center';
    msg.textContent = 'You must login or register to access this feature.';

    card.appendChild(msg);

    // Remove after 3 seconds
    setTimeout(() => {
      msg.remove();
    }, 3000);
  });
});
</script>

</body>
</html>
