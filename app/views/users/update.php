<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
$darkModeEnabled = false;

$bodyBaseClass = 'min-h-screen flex items-center justify-center font-sans';
$bodyInlineStyle = 'background: #FFF5E1;';
$containerClass = 'p-8 rounded-2xl shadow-lg w-full max-w-md border bg-white';
$titleClass = 'text-2xl font-semibold text-center text-[#5C4033] mb-6';
$labelClass = 'block text-[#5C4033] mb-1 font-medium';
$inputClass = 'w-full px-4 py-3 border rounded-xl focus:ring-2 focus:outline-none';
$inputStyle = 'border-color: #C19A6B; background: #FFF5E1;';
$buttonPrimaryClass = 'flex-1 text-white font-medium py-3 rounded-xl shadow-md transition duration-200 hover:bg-[#B07A4B]';
$fullNameValue = isset($user) ? trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')) : '';
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update User - Dormitory Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="<?= $bodyBaseClass ?>" style="<?= $bodyInlineStyle ?>">

  <div class="<?= $containerClass ?>">
    <h2 class="<?= $titleClass ?>">
      <i class="fas fa-user-edit text-[#C19A6B] mr-2"></i>Update User
    </h2>

    <?php if (!empty($error)): ?>
      <div class="mb-4 rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-sm text-red-700">
        <?= html_escape($error) ?>
      </div>
    <?php endif; ?>

    <form action="<?=site_url('users/update/'.$user['id'])?>" method="POST" class="space-y-4">
      <input type="hidden" name="from_modal" value="0">

      <!-- Full Name -->
      <div>
        <label class="<?= $labelClass ?>">
          <i class="fas fa-user text-[#C19A6B] mr-1"></i>Full Name
        </label>
        <input type="text" name="full_name" value="<?= html_escape($fullNameValue) ?>" required
               class="<?= $inputClass ?>" style="<?= $inputStyle ?>"
               onfocus="this.style.boxShadow='0 0 0 2px #C19A6B'" onblur="this.style.boxShadow='none'">
      </div>

      <!-- Email -->
      <div>
        <label class="<?= $labelClass ?>">
          <i class="fas fa-envelope text-[#C19A6B] mr-1"></i>Email Address
        </label>
        <input type="email" name="email" value="<?= html_escape($user['email'])?>" required
               class="<?= $inputClass ?>" style="<?= $inputStyle ?>"
               onfocus="this.style.boxShadow='0 0 0 2px #C19A6B'" onblur="this.style.boxShadow='none'">
      </div>

      <!-- Buttons -->
      <div class="flex gap-3 pt-4">
        <a href="<?= site_url('users') ?>" 
           class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-3 rounded-xl shadow-md transition duration-200 text-center">
          <i class="fas fa-times mr-1"></i>Cancel
        </a>
        <button type="submit"
          class="<?= $buttonPrimaryClass ?>" style="background: #C19A6B;">
          <i class="fas fa-save mr-1"></i>Update
        </button>
      </div>
    </form>
  </div>

  <!-- Small fade-in animation -->
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
      animation: fadeIn 0.8s ease;
    }
  </style>
</body>
</html>
