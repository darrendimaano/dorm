<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements - Dormitory</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  /* Sidebar styles */
  #sidebar {
    transition: width 0.3s ease, transform 0.3s ease;
    background: #D2B48C; /* warm tan */
  }
  #sidebar.collapsed {
    width: 4rem;
  }
  #sidebar.collapsed nav a span {
    display: none;
  }
  #sidebar.collapsed nav a {
    justify-content: center;
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
  
  /* Dark mode styles */
  .dark #sidebar {
    background: #1a1a1a !important;
  }
  .dark body {
    background: #111111 !important;
  }
  .dark .main-content, .dark .content-area {
    background: #1a1a1a !important;
    color: #ffffff !important;
  }
  .dark .user-card {
    background: #2d2d2d !important;
    border-color: #444444 !important;
    color: #ffffff !important;
  }
  .dark .header-section {
    background: #1a1a1a !important;
    color: #ffffff !important;
  }
  
  /* Sidebar collapsed text hiding */
  #sidebar.collapsed .sidebar-text {
    display: none;
  }
</style>
</head>
<body class="min-h-screen transition-colors">

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-40 shadow-lg">
  <div class="flex items-center gap-3 mb-8">
    <div class="bg-[#C19A6B] p-2 rounded-lg">
      <i class="fa-solid fa-graduation-cap text-2xl text-white"></i>
    </div>
    <div class="sidebar-text">
      <h2 class="text-lg font-bold"><?= htmlspecialchars($userName ?? 'Tenant') ?></h2>
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
    <a href="<?= site_url('user/announcements') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-bullhorn"></i> <span>Announcements</span>
    </a>
    <a href="<?= site_url('user/profile') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-user"></i> <span>Profile</span>
    </a>
    <a href="<?= site_url('user/contact') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-[#C19A6B] hover:text-white transition">
      <i class="fa-solid fa-envelope"></i> <span>Contact Admin</span>
    </a>
    <hr class="border-[#5C4033] border-opacity-20 my-4">
    <div class="px-4 py-2 text-xs text-[#5C4033] opacity-75">
      <i class="fa-solid fa-phone mr-2"></i>
      <span class="sidebar-text">Contact: 09517394938</span>
    </div>
    <a href="<?= site_url('auth/logout') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-400 hover:text-white transition">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

<!-- Main Content -->
<div class="flex-1 transition-all duration-300" id="mainContent" style="margin-left: 16rem;">
  <!-- Header -->
  <div style="background: #FFF5E1;" class="shadow-md flex items-center justify-between px-6 py-4 md:ml-0 header-section">
    <div class="flex items-center gap-4">
      <button id="sidebarToggle" class="text-[#5C4033] text-xl hover:bg-[#C19A6B] hover:text-white p-2 rounded-lg transition-all">
        <i class="fa-solid fa-bars" id="toggleIcon"></i>
      </button>
      <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div>
        <h1 class="font-bold text-xl text-[#5C4033]">Announcements</h1>
        <p class="text-[#5C4033] opacity-75 text-sm">Stay updated with the latest news and updates</p>
      </div>
    </div>
    <div class="flex items-center gap-4">
      <button id="darkModeToggle" class="p-2 rounded-lg border border-[#C19A6B] hover:bg-[#C19A6B] hover:text-white transition">
        <i class="fa-solid fa-moon" id="darkModeIcon"></i>
      </button>
      <div class="text-xs text-[#5C4033] opacity-75">
        <i class="fa-solid fa-phone mr-1"></i>
        09517394938
      </div>
    </div>
  </div>

  <div class="max-w-4xl mx-auto p-6">
    <!-- Success / Error Messages -->
    <?php if(!empty($success)): ?>
        <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-lg mb-6 text-center shadow-sm">
            <i class="fa-solid fa-check-circle text-lg"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-lg mb-6 text-center shadow-sm">
            <i class="fa-solid fa-exclamation-circle text-lg"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Filter Options -->
    <div class="mb-6">
      <div style="background: #FFF5E1;" class="p-4 rounded-lg border user-card" style="border-color: #C19A6B;">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-filter text-[#C19A6B]"></i>
            <span class="text-[#5C4033] font-semibold">Filter:</span>
          </div>
          <select id="priorityFilter" class="px-3 py-2 border border-[#C19A6B] rounded-lg bg-[#FFF5E1] text-[#5C4033] focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
            <option value="all">All Priorities</option>
            <option value="low">Low Priority</option>
            <option value="medium">Medium Priority</option>
            <option value="high">High Priority</option>
            <option value="urgent">Urgent</option>
          </select>
          <div class="ml-auto text-sm text-[#5C4033] opacity-75">
            <i class="fa-solid fa-bullhorn mr-1"></i>
            <?= count($announcements ?? []) ?> announcement<?= count($announcements ?? []) !== 1 ? 's' : '' ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Announcements List -->
    <div class="space-y-6">
      <?php if(!empty($announcements)): ?>
        <?php foreach($announcements as $announcement): ?>
          <div class="announcement-card" data-priority="<?= $announcement['priority'] ?>" style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border user-card" style="border-color: #C19A6B;">
            <!-- Announcement Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <h2 class="text-xl font-bold text-[#5C4033]"><?= htmlspecialchars($announcement['title']) ?></h2>
                  <?php 
                  $priorityColors = [
                    'low' => 'bg-gray-100 text-gray-800',
                    'medium' => 'bg-blue-100 text-blue-800',
                    'high' => 'bg-orange-100 text-orange-800',
                    'urgent' => 'bg-red-100 text-red-800'
                  ];
                  $priorityColor = $priorityColors[$announcement['priority']] ?? 'bg-gray-100 text-gray-800';
                  ?>
                  <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $priorityColor ?>">
                    <?= ucfirst($announcement['priority']) ?>
                  </span>
                </div>
                <p class="text-sm text-[#5C4033] opacity-75">
                  <i class="fa-solid fa-user mr-1"></i>
                  By <?= htmlspecialchars($announcement['created_by_name'] ?? 'Admin') ?> •
                  <i class="fa-solid fa-calendar mr-1"></i>
                  <?= date('M j, Y g:i A', strtotime($announcement['created_at'])) ?>
                  <?php if(!empty($announcement['expires_at']) && strtotime($announcement['expires_at']) > time()): ?>
                    • <i class="fa-solid fa-clock mr-1"></i>
                    Expires <?= date('M j, Y', strtotime($announcement['expires_at'])) ?>
                  <?php endif; ?>
                </p>
              </div>
            </div>

            <!-- Announcement Content -->
            <div class="mb-6">
              <div class="text-[#5C4033] leading-relaxed whitespace-pre-line"><?= nl2br(htmlspecialchars($announcement['content'])) ?></div>
            </div>

            <!-- Comments Section -->
            <div class="border-t pt-4" style="border-color: #C19A6B;">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-[#5C4033]">
                  <i class="fa-solid fa-comments text-[#C19A6B] mr-2"></i>
                  Comments (<?= count($announcement['comments'] ?? []) ?>)
                </h4>
                <button onclick="toggleComments(<?= $announcement['id'] ?>)" class="text-[#C19A6B] hover:text-[#5C4033] font-medium text-sm">
                  <i class="fa-solid fa-chevron-down mr-1" id="toggle-<?= $announcement['id'] ?>"></i>
                  Show/Hide
                </button>
              </div>

              <div id="comments-<?= $announcement['id'] ?>" class="hidden">
                <!-- Add Comment Form -->
                <form onsubmit="submitComment(event, <?= $announcement['id'] ?>)" class="mb-4">
                  <div class="flex gap-3">
                    <textarea name="comment" required placeholder="Write a comment..." rows="2"
                              class="flex-1 px-3 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1] text-[#5C4033] resize-none"></textarea>
                    <button type="submit" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition-all self-start">
                      <i class="fa-solid fa-paper-plane"></i>
                    </button>
                  </div>
                </form>

                <!-- Comments List -->
                <div class="space-y-3 max-h-96 overflow-y-auto" id="comments-list-<?= $announcement['id'] ?>">
                  <?php if(!empty($announcement['comments'])): ?>
                    <?php foreach($announcement['comments'] as $comment): ?>
                      <div class="bg-[#e6ddd4] p-3 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                          <div class="flex items-center gap-2">
                            <span class="font-semibold text-[#5C4033] text-sm"><?= htmlspecialchars($comment['user_name']) ?></span>
                            <span class="text-xs text-[#5C4033] opacity-75"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></span>
                          </div>
                        </div>
                        <p class="text-[#5C4033] text-sm"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p class="text-[#5C4033] opacity-75 text-center py-4">No comments yet. Be the first to comment!</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="background: #FFF5E1;" class="p-12 rounded-xl shadow-sm border user-card text-center" style="border-color: #C19A6B;">
          <i class="fa-solid fa-bullhorn text-6xl text-[#C19A6B] opacity-50 mb-4"></i>
          <h3 class="text-xl font-bold text-[#5C4033] mb-2">No Announcements</h3>
          <p class="text-[#5C4033] opacity-75">There are currently no announcements to display.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Mobile Menu Overlay -->
<div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

<script>
// Sidebar toggle functionality
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const toggleIcon = document.getElementById('toggleIcon');

// Check for saved sidebar state
const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
if (isSidebarCollapsed) {
    sidebar.classList.add('collapsed');
    mainContent.style.marginLeft = '4rem';
    toggleIcon.className = 'fa-solid fa-times';
}

if (sidebarToggle) {
  sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      const isCollapsed = sidebar.classList.contains('collapsed');
      
      // Update main content margin
      mainContent.style.marginLeft = isCollapsed ? '4rem' : '16rem';
      
      // Update toggle icon
      toggleIcon.className = isCollapsed ? 'fa-solid fa-times' : 'fa-solid fa-bars';
      
      // Save state
      localStorage.setItem('sidebarCollapsed', isCollapsed);
  });
}

// Mobile menu functionality
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mobileOverlay = document.getElementById('mobileOverlay');

menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    mobileOverlay.classList.toggle('hidden');
});

mobileOverlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    mobileOverlay.classList.add('hidden');
});

// Dark mode functionality
function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');
    const mainBody = document.body;
    
    if (!darkModeToggle) return;
    
    // Check for saved dark mode preference
    const isDarkMode = localStorage.getItem("userDarkMode") === "true";
    if (isDarkMode) {
        mainBody.classList.add("dark");
        if(darkModeIcon) darkModeIcon.className = "fa-solid fa-sun";
    }
    
    darkModeToggle.addEventListener("click", () => {
        mainBody.classList.toggle("dark");
        const isDark = mainBody.classList.contains("dark");
        
        // Save preference
        localStorage.setItem("userDarkMode", isDark);
        
        // Update icon
        if(darkModeIcon) {
            darkModeIcon.className = isDark ? "fa-solid fa-sun" : "fa-solid fa-moon";
        }
        
        // Update database setting via AJAX
        fetch("<?= site_url('settings/update') ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "dark_mode_user=" + (isDark ? "1" : "0") + "&ajax=1"
        });
    });
}

// Initialize when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDarkMode);
} else {
    initDarkMode();
}

// Filter announcements by priority
document.addEventListener('DOMContentLoaded', function() {
    const priorityFilter = document.getElementById('priorityFilter');
    if (priorityFilter) {
        priorityFilter.addEventListener('change', function() {
            const selectedPriority = this.value;
            const announcementCards = document.querySelectorAll('.announcement-card');
            
            announcementCards.forEach(card => {
                const cardPriority = card.getAttribute('data-priority');
                if (selectedPriority === 'all' || cardPriority === selectedPriority) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});

// Toggle comments visibility
function toggleComments(announcementId) {
    const commentsDiv = document.getElementById(`comments-${announcementId}`);
    const toggleIcon = document.getElementById(`toggle-${announcementId}`);
    
    if (commentsDiv.classList.contains('hidden')) {
        commentsDiv.classList.remove('hidden');
        toggleIcon.classList.remove('fa-chevron-down');
        toggleIcon.classList.add('fa-chevron-up');
    } else {
        commentsDiv.classList.add('hidden');
        toggleIcon.classList.remove('fa-chevron-up');
        toggleIcon.classList.add('fa-chevron-down');
    }
}

// Submit comment
async function submitComment(event, announcementId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('announcement_id', announcementId);
    
    const confirmed = await confirmCommentSubmit();
    if (!confirmed) return;
    
    try {
        const response = await fetch('<?= site_url('announcements/comment') ?>', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            // Reload page to show new comment
            window.location.reload();
        } else {
            throw new Error('Failed to submit comment');
        }
    } catch (error) {
        alert('Error submitting comment. Please try again.');
    }
}

// Confirmation dialog for comment submission
function confirmCommentSubmit() {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-[#FFF5E1] p-6 rounded-lg max-w-md mx-4" style="border: 2px solid #C19A6B;">
                <h3 class="text-lg font-bold mb-4 text-[#5C4033]">Post Comment</h3>
                <p class="mb-6 text-[#5C4033] opacity-75">Are you sure you want to post this comment?</p>
                <div class="flex gap-3 justify-end">
                    <button id="cancelBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                    <button id="confirmBtn" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition">Post</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.querySelector('#confirmBtn').onclick = () => {
            document.body.removeChild(modal);
            resolve(true);
        };
        
        modal.querySelector('#cancelBtn').onclick = () => {
            document.body.removeChild(modal);
            resolve(false);
        };
        
        modal.onclick = (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
                resolve(false);
            }
        };
    });
}
</script>

</body>
</html>