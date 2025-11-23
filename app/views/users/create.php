<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
$darkModeEnabled = false;

$bodyClasses = $darkModeEnabled
    ? 'bg-[#0f172a] text-gray-100 min-h-screen flex items-center justify-center font-sans'
    : 'bg-gradient-to-br from-indigo-600 via-blue-500 to-cyan-400 min-h-screen flex items-center justify-center font-sans';

$cardClasses = $darkModeEnabled
    ? 'bg-[#1f2937] p-8 rounded-3xl shadow-2xl w-full max-w-md border border-gray-700'
    : 'bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md animate-fadeIn border border-gray-200';

$headingTextClass = $darkModeEnabled ? 'text-2xl font-bold text-gray-100 mt-3' : 'text-2xl font-bold text-gray-800 mt-3';
$paragraphClass = $darkModeEnabled ? 'text-gray-400 text-sm' : 'text-gray-500 text-sm';
$labelClass = $darkModeEnabled ? 'block text-gray-200 mb-1 font-medium' : 'block text-gray-700 mb-1 font-medium';
$inputClass = $darkModeEnabled
    ? 'w-full px-4 py-3 border border-gray-600 rounded-xl bg-[#111827] text-gray-100 focus:ring-2 focus:ring-indigo-400 focus:outline-none shadow-sm transition duration-200'
    : 'w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm transition duration-200';
$buttonClass = $darkModeEnabled
    ? 'w-full bg-gradient-to-r from-indigo-500 to-blue-500 hover:from-indigo-600 hover:to-blue-600 text-white font-semibold py-3 rounded-xl shadow-lg transition duration-300 transform hover:scale-105'
    : 'w-full bg-gradient-to-r from-indigo-600 to-blue-500 hover:from-indigo-700 hover:to-blue-600 text-white font-semibold py-3 rounded-xl shadow-lg transition duration-300 transform hover:scale-105';
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tenant Sign Up</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="<?= $bodyClasses ?><?= $darkModeEnabled ? ' dark' : '' ?>">

  <div class="<?= $cardClasses ?>">
    
    <!-- Header -->
    <div class="flex flex-col items-center mb-6">
      <div class="bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full p-3 shadow-md">
        <i class="fa-solid fa-user-graduate text-white text-3xl"></i>
      </div>
      <h2 class="<?= $headingTextClass ?>">Create Your Tenant Account</h2>
      <p class="<?= $paragraphClass ?>">Join our tenant community today!</p>
    </div>

    <!-- Form -->
    <form action="<?=site_url('users/create')?>" method="POST" class="space-y-5">
      
      <!-- First Name -->
      <div>
         <label class="<?= $labelClass ?>">First Name</label>
         <input type="text" name="fname" placeholder="Enter your first name" required
           class="<?= $inputClass ?>">
      </div>

      <!-- Last Name -->
      <div>
         <label class="<?= $labelClass ?>">Last Name</label>
         <input type="text" name="lname" placeholder="Enter your last name" required
           class="<?= $inputClass ?>">
      </div>

      <!-- Email -->
      <div>
         <label class="<?= $labelClass ?>">Email Address</label>
         <input type="email" name="email" placeholder="Enter your email" required
           class="<?= $inputClass ?>">
      </div>

      <!-- Sign Up Button -->
            <button type="submit"
              class="<?= $buttonClass ?>">
        <i class="fa-solid fa-user-plus mr-2"></i> Sign In
      </button>

      
    </form>
  </div>

  <!-- Animation -->
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
