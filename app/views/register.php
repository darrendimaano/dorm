<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Dormitory</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="flex items-center justify-center min-h-screen font-sans" style="background: #FFF5E1;">

<div class="p-8 rounded-2xl shadow-lg w-full max-w-md border" style="background: white; border-color: #C19A6B;">
    <h1 class="text-2xl font-bold mb-6 text-center text-[#5C4033]">Register</h1>

    <?php if(!empty($error)): ?>
        <div class="p-3 rounded mb-4 border" style="background: #FFF0F0; border-color: #DC6B6B; color: #B91C1C;">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="flex flex-col gap-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <input type="text" name="fname" placeholder="First Name" required class="p-2 rounded w-full border border-[#C19A6B] focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
            <input type="text" name="lname" placeholder="Last Name" required class="p-2 rounded w-full border border-[#C19A6B] focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
        </div>
        <input type="email" name="email" placeholder="Email" required class="p-2 rounded w-full border border-[#C19A6B] focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
        <input type="password" name="password" placeholder="Password" required minlength="6" class="p-2 rounded w-full border border-[#C19A6B] focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required class="p-2 rounded w-full border border-[#C19A6B] focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">

        <button type="submit" class="text-white py-2 rounded-lg shadow transition-all duration-300 hover:bg-[#B07A4B]" style="background: #C19A6B;">
            <i class="fa-solid fa-user-plus"></i> Register
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-[#5C4033]">
        Already have an account? <a href="<?= site_url('auth/login') ?>" class="text-[#C19A6B] hover:underline font-semibold">Login here</a>
    </p>
</div>

</body>
</html>
