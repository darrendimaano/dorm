<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update User - Dormitory Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen flex items-center justify-center font-sans" style="background: #FFF5E1;">

  <div class="p-8 rounded-2xl shadow-lg w-full max-w-md border" style="background: white; border-color: #C19A6B;">
    <h2 class="text-2xl font-semibold text-center text-[#5C4033] mb-6">
      <i class="fas fa-user-edit text-[#C19A6B] mr-2"></i>Update User
    </h2>

    <form action="<?=site_url('users/update/'.$user['id'])?>" method="POST" class="space-y-4">
      <!-- First Name -->
      <div>
        <label class="block text-[#5C4033] mb-1 font-medium">
          <i class="fas fa-user text-[#C19A6B] mr-1"></i>First Name
        </label>
        <input type="text" name="fname" value="<?= html_escape($user['fname'])?>" required
               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:outline-none" style="border-color: #C19A6B; background: #FFF5E1;" 
               onfocus="this.style.boxShadow='0 0 0 2px #C19A6B'" onblur="this.style.boxShadow='none'">
      </div>

      <!-- Last Name -->
      <div>
        <label class="block text-[#5C4033] mb-1 font-medium">
          <i class="fas fa-user text-[#C19A6B] mr-1"></i>Last Name
        </label>
        <input type="text" name="lname" value="<?= html_escape($user['lname'])?>" required
               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:outline-none" style="border-color: #C19A6B; background: #FFF5E1;"
               onfocus="this.style.boxShadow='0 0 0 2px #C19A6B'" onblur="this.style.boxShadow='none'">
      </div>

      <!-- Email -->
      <div>
        <label class="block text-[#5C4033] mb-1 font-medium">
          <i class="fas fa-envelope text-[#C19A6B] mr-1"></i>Email Address
        </label>
        <input type="email" name="email" value="<?= html_escape($user['email'])?>" required
               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:outline-none" style="border-color: #C19A6B; background: #FFF5E1;"
               onfocus="this.style.boxShadow='0 0 0 2px #C19A6B'" onblur="this.style.boxShadow='none'">
      </div>

      <!-- Password (Optional) -->
      <div>
        <label class="block text-[#5C4033] mb-1 font-medium">
          <i class="fas fa-lock text-[#C19A6B] mr-1"></i>New Password (Optional)
        </label>
        <input type="password" name="password"
               class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:outline-none" style="border-color: #C19A6B; background: #FFF5E1;"
               onfocus="this.style.boxShadow='0 0 0 2px #C19A6B'" onblur="this.style.boxShadow='none'"
               placeholder="Leave blank to keep current password">
      </div>

      <!-- Buttons -->
      <div class="flex gap-3 pt-4">
        <a href="<?= site_url('users') ?>" 
           class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-3 rounded-xl shadow-md transition duration-200 text-center">
          <i class="fas fa-times mr-1"></i>Cancel
        </a>
        <button type="submit"
                class="flex-1 text-white font-medium py-3 rounded-xl shadow-md transition duration-200 hover:bg-[#B07A4B]" style="background: #C19A6B;">
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
