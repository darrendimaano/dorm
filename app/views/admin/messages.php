<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages - Dormitory Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-[#FFF5E1] font-sans flex">

<!-- Sidebar -->
<div class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-50 shadow-lg" style="background: #D2B48C;">
  <h2 class="text-2xl font-bold mb-8">🏨 Dormitory Admin</h2>
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
    <a href="<?=site_url('admin/reservations')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
    </a>
    <a href="<?=site_url('admin/reports')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-file-chart-line"></i> <span>Tenant Reports</span>
    </a>
    <a href="<?=site_url('admin/messages')?>" class="flex items-center gap-2 px-4 py-2 rounded bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-envelope"></i> <span>Messages</span>
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
<div class="flex-1 ml-64 transition-all duration-300">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-[#5C4033] mb-2">Messages</h1>
                    <p class="text-[#5C4033] opacity-75">Manage tenant messages and inquiries</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-[#5C4033] opacity-75">Last updated</p>
                        <p class="text-[#5C4033] font-semibold"><?= date('M j, Y g:i A') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto">
    
    <!-- Success / Error Messages -->
    <?php if(!empty($success)): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 text-center shadow border border-green-200">
            <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 text-center shadow border border-red-200">
            <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($messages)): ?>
      <!-- Messages List -->
      <div class="space-y-6">
        <?php foreach($messages as $message): ?>
          <div class="bg-[#FFF5E1] border border-[#C19A6B] rounded-2xl p-6 shadow-lg">
            
            <!-- Message Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 pb-4 border-b border-[#E5D3B3]">
              <div class="flex items-center gap-4 mb-2 md:mb-0">
                <div class="bg-[#C19A6B] text-white w-12 h-12 rounded-full flex items-center justify-center">
                  <i class="fa-solid fa-user text-xl"></i>
                </div>
                <div>
                  <h3 class="font-bold text-[#5C4033]"><?= htmlspecialchars($message['fname'] . ' ' . $message['lname']) ?></h3>
                  <p class="text-gray-600 text-sm"><?= htmlspecialchars($message['email']) ?></p>
                </div>
              </div>
              
              <div class="flex items-center gap-4">
                <span class="px-3 py-1 rounded-full text-sm font-semibold 
                  <?= $message['status'] == 'replied' ? 'bg-green-100 text-green-800' : 
                      ($message['status'] == 'read' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') ?>">
                  <i class="fa-solid fa-<?= $message['status'] == 'replied' ? 'reply' : 
                                          ($message['status'] == 'read' ? 'eye' : 'envelope') ?>"></i>
                  <?= ucfirst($message['status']) ?>
                </span>
                <span class="text-sm text-gray-500">#<?= $message['id'] ?></span>
              </div>
            </div>

            <!-- Message Content -->
            <div class="mb-4">
              <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-tag text-[#C19A6B]"></i>
                <span class="font-semibold text-[#5C4033]">Subject:</span>
                <span class="text-gray-700"><?= htmlspecialchars($message['subject']) ?></span>
              </div>
              
              <div class="bg-white p-4 rounded-lg border border-[#E5D3B3]">
                <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
              </div>
            </div>

            <!-- Admin Reply Section -->
            <?php if (!empty($message['admin_reply'])): ?>
              <div class="bg-green-50 border border-green-200 p-4 rounded-lg mb-4">
                <div class="flex items-center gap-2 mb-2">
                  <i class="fa-solid fa-reply text-green-600"></i>
                  <span class="font-semibold text-green-800">Admin Reply:</span>
                </div>
                <p class="text-green-700"><?= nl2br(htmlspecialchars($message['admin_reply'])) ?></p>
              </div>
            <?php endif; ?>

            <!-- Reply Form -->
            <?php if ($message['status'] !== 'replied'): ?>
              <div class="border-t border-[#E5D3B3] pt-4">
                <form method="POST" action="<?= site_url('admin/messages/reply/'.$message['id']) ?>">
                  <div class="mb-3">
                    <label class="block text-sm font-medium text-[#5C4033] mb-2">Reply to Tenant:</label>
                    <textarea name="reply" rows="3" 
                              class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:ring-2 focus:ring-[#C19A6B] focus:border-[#C19A6B]" 
                              placeholder="Type your reply here..." required></textarea>
                  </div>
                  <div class="flex gap-2">
                    <button type="submit" class="bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-4 py-2 rounded-full font-semibold transition text-sm">
                      <i class="fa-solid fa-paper-plane"></i> Send Reply
                    </button>
                    <button type="button" onclick="markAsRead(<?= $message['id'] ?>)" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-full font-semibold transition text-sm">
                      <i class="fa-solid fa-eye"></i> Mark as Read
                    </button>
                  </div>
                </form>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- No Messages -->
      <div class="bg-[#FFF5E1] border border-[#C19A6B] p-12 rounded-2xl text-center shadow-lg">
        <div class="text-[#C19A6B] mb-6">
          <i class="fa-solid fa-envelope-open text-6xl"></i>
        </div>
        <h3 class="text-2xl font-semibold text-[#5C4033] mb-4">No Messages Yet</h3>
        <p class="text-gray-600">No tenant messages have been received.</p>
      </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="bg-[#E5D3B3] border border-[#D2B48C] p-6 rounded-lg mt-6">
      <h4 class="font-semibold text-[#5C4033] mb-4">
        <i class="fa-solid fa-lightbulb"></i> Quick Actions
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="<?= site_url('admin/landing') ?>" class="bg-[#C19A6B] hover:bg-[#5C4033] text-white px-4 py-3 rounded-lg text-center font-semibold transition">
          <i class="fa-solid fa-list-check"></i> Check Reservations
        </a>
        <a href="<?= site_url('rooms') ?>" class="bg-[#C19A6B] hover:bg-[#5C4033] text-white px-4 py-3 rounded-lg text-center font-semibold transition">
          <i class="fa-solid fa-bed"></i> Manage Rooms
        </a>
      </div>
    </div>
  </div>
</div>

<script>
// Sidebar toggle functionality
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

if (menuBtn) {
  menuBtn.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    if (sidebar.classList.contains('collapsed')) {
      mainContent.classList.remove('ml-64');
      mainContent.classList.add('ml-16');
    } else {
      mainContent.classList.remove('ml-16');
      mainContent.classList.add('ml-64');
    }
  });
}

// Mark as read functionality (placeholder)
function markAsRead(messageId) {
  // This would typically make an AJAX call to mark the message as read
  alert('Feature coming soon: Mark as read');
}

// Auto-resize textareas
document.querySelectorAll('textarea').forEach(textarea => {
  textarea.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
  });
});
</script>

</body>
</html>