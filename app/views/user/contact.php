<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Admin - Tenant Portal</title>
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
</style>
</head>
<body class="font-sans flex min-h-screen">

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-40 shadow-lg">
  <div class="flex items-center gap-3 mb-8">
    <div class="bg-[#C19A6B] p-2 rounded-lg">
      <i class="fa-solid fa-graduation-cap text-2xl text-white"></i>
    </div>
    <div>
      <h2 class="text-lg font-bold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tenant') ?></h2>
      <p class="text-sm text-[#5C4033] opacity-75">Tenant Portal</p>
    </div>
  </div>
  
  <nav class="flex flex-col gap-2">
    <a href="<?= site_url('user_landing') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-home"></i> <span>Dashboard</span>
    </a>
    <a href="<?= site_url('user/reservations') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-list-check"></i> <span>My Reservations</span>
    </a>
    <a href="<?= site_url('user/payments') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-credit-card"></i> <span>Payment History</span>
    </a>
    <a href="<?= site_url('user/maintenance') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-wrench"></i> <span>Maintenance</span>
    </a>
    <a href="<?= site_url('user/announcements') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-bullhorn"></i> <span>Announcements</span>
    </a>
    <a href="<?= site_url('user/profile') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-user"></i> <span>Profile</span>
    </a>
    <a href="<?= site_url('user/contact') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-envelope"></i> <span>Contact Admin</span>
    </a>
    <hr class="border-[#5C4033] border-opacity-20 my-4">
    <div class="px-4 py-2 text-xs text-[#5C4033] opacity-75">
      <i class="fa-solid fa-phone mr-2"></i>
      <span>Contact: 09517394938</span>
    </div>
    <a href="#" onclick="confirmLogout()" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-400 hover:text-white transition">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

<!-- Main Content -->
<div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
  <!-- Header -->
  <div style="background: #FFF5E1;" class="shadow-md flex items-center justify-between px-6 py-4">
    <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div>
      <h1 class="font-bold text-xl text-[#5C4033]">Contact Admin</h1>
      <p class="text-[#5C4033] opacity-75 text-sm">Get help and support from the dormitory administration</p>
    </div>
    <div class="text-xs text-[#5C4033] opacity-75">
      <i class="fa-solid fa-phone mr-1"></i>
      09517394938
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
      
      <!-- Contact Information -->
      <div class="space-y-6">
        <!-- Quick Contact -->
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border" style="border-color: #C19A6B;">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-4">
            <i class="fa-solid fa-phone text-[#C19A6B]"></i> Quick Contact
          </h3>
          <div class="space-y-3">
            <div class="flex items-center gap-3 text-sm text-[#5C4033] opacity-75">
              <i class="fa-solid fa-envelope text-[#C19A6B]"></i>
              <span>dorm@gmail.com</span>
            </div>
            <div class="flex items-center gap-3 text-sm text-[#5C4033] opacity-75">
              <i class="fa-solid fa-phone text-[#C19A6B]"></i>
              <span>09517394938</span>
            </div>
            <div class="flex items-center gap-3 text-sm text-[#5C4033] opacity-75">
              <i class="fa-solid fa-clock text-[#C19A6B]"></i>
              <span>24/7 Support Available</span>
            </div>
          </div>
        </div>

        <!-- FAQs -->
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border" style="border-color: #C19A6B;">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-4">
            <i class="fa-solid fa-question-circle text-[#C19A6B]"></i> Common Questions
          </h3>
          <div class="space-y-3">
            <div class="text-sm">
              <p class="font-medium text-[#5C4033]">How long does approval take?</p>
              <p class="text-[#5C4033] opacity-75">Usually 24-48 hours</p>
            </div>
            <div class="text-sm">
              <p class="font-medium text-[#5C4033]">Payment methods?</p>
              <p class="text-[#5C4033] opacity-75">Cash, Bank transfer, GCash</p>
            </div>
            <div class="text-sm">
              <p class="font-medium text-[#5C4033]">Can I change rooms?</p>
              <p class="text-[#5C4033] opacity-75">Contact admin for availability</p>
            </div>
          </div>
        </div>

        <!-- Emergency -->
        <div class="bg-[#E5D3B3] border border-[#D2B48C] p-6 rounded-xl">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-2">
            <i class="fa-solid fa-exclamation-triangle text-[#C19A6B]"></i> Emergency
          </h3>
          <p class="text-[#5C4033] text-sm mb-3">For urgent issues, call immediately:</p>
          <p class="text-[#5C4033] font-bold">09517394938</p>
        </div>
      </div>

      <!-- Contact Form -->
      <div class="lg:col-span-2">
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border" style="border-color: #C19A6B;">
          <h3 class="text-lg font-semibold text-[#5C4033] mb-6">
            <i class="fa-solid fa-paper-plane text-[#C19A6B]"></i> Send a Message
          </h3>
          
          <form method="POST" action="<?= site_url('user/contact/send') ?>" class="space-y-4">
            
            <div>
              <label class="block text-sm font-medium text-[#5C4033] mb-2">Subject</label>
              <select name="subject" class="w-full px-4 py-2 border rounded-lg focus:ring-2" style="border-color: #C19A6B; --tw-ring-color: #C19A6B;" required>
                <option value="">Select a subject...</option>
                <option value="Reservation Inquiry">Reservation Inquiry</option>
                <option value="Payment Question">Payment Question</option>
                <option value="Room Issue">Room Issue</option>
                <option value="Maintenance Request">Maintenance Request</option>
                <option value="Policy Question">Policy Question</option>
                <option value="Complaint">Complaint</option>
                <option value="General Support">General Support</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-[#5C4033] mb-2">Message</label>
              <textarea name="message" rows="6" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2" style="border-color: #C19A6B; --tw-ring-color: #C19A6B;" 
                        placeholder="Please describe your issue or question in detail..." required></textarea>
            </div>

            <div class="bg-[#FFF5E1] border border-[#E5D3B3] p-4 rounded-lg">
              <p class="text-[#5C4033] text-sm">
                <i class="fa-solid fa-info-circle"></i>
                <strong>Please include:</strong> Your name, room preference (if applicable), and any relevant details to help us assist you better. For immediate assistance, call 09517394938.
              </p>
            </div>

            <div class="flex gap-3 pt-4">
              <button type="submit" class="text-white px-6 py-3 rounded-lg font-semibold transition-all hover:bg-[#B07A4B]" style="background: #C19A6B;">
                <i class="fa-solid fa-paper-plane"></i> Send Message
              </button>
              <button type="reset" class="bg-[#e6ddd4] hover:bg-[#d1c5b3] text-[#5C4033] px-6 py-3 rounded-lg font-semibold transition-all">
                <i class="fa-solid fa-eraser"></i> Clear
              </button>
            </div>
          </form>
        </div>

        <!-- Response Time Info -->
        <div class="bg-[#E5D3B3] border border-[#D2B48C] p-4 rounded-lg mt-6">
          <h4 class="font-semibold text-[#5C4033] mb-2">
            <i class="fa-solid fa-clock"></i> Response Time
          </h4>
          <p class="text-[#5C4033] opacity-75 text-sm">
            We typically respond to messages within 2-4 hours during business hours (8 AM - 8 PM). 
            For urgent matters, please call 09517394938.
          </p>
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

// Form enhancements
document.querySelector('select[name="subject"]').addEventListener('change', function() {
  const messageTextarea = document.querySelector('textarea[name="message"]');
  const subject = this.value;
  
  const templates = {
    'Reservation Inquiry': 'Hi, I would like to inquire about room availability and the reservation process...',
    'Payment Question': 'Hello, I have a question regarding payment methods and billing...',
    'Room Issue': 'Hi, I am experiencing an issue with my room. The problem is...',
    'Maintenance Request': 'Hello, I would like to request maintenance for...',
    'Policy Question': 'Hi, I have a question about dormitory policies regarding...',
    'Complaint': 'I would like to file a complaint about...',
    'General Support': 'Hi, I need assistance with...'
  };
  
  if (templates[subject] && messageTextarea.value === '') {
    messageTextarea.placeholder = templates[subject];
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