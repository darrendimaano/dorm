<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
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
    color: #e5e5e5 !important;
  }
  .dark .main-content, .dark .content-area {
    background: #1a1a1a !important;
    color: #e5e5e5 !important;
  }
  .dark .user-card {
    background: #1e1e1e !important;
    border-color: #3a3a3a !important;
    color: #e5e5e5 !important;
  }
  .dark .header-section {
    background: #1a1a1a !important;
    color: #e5e5e5 !important;
  }
  .dark [class*="bg-[#FFF5E1]"], .dark [class*="bg-white"] {
    background: #1e1e1e !important;
  }
  .dark [class*="border-[#C19A6B]"], .dark [class*="border-[#E5D3B3]"] {
    border-color: #3a3a3a !important;
  }
  .dark [class*="text-[#5C4033]"],
  .dark [class*="text-gray-600"],
  .dark [class*="text-gray-500"] {
    color: #e5e5e5 !important;
  }
  .dark [class*="text-[#C19A6B]"] {
    color: #f2c17d !important;
  }
  
  /* Sidebar collapsed text hiding */
  #sidebar.collapsed .sidebar-text {
    display: none;
  }
</style>
</head>
<body class="min-h-screen transition-colors<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Sidebar -->
<?php include __DIR__ . '/includes/sidebar.php'; ?>

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
    <div class="flex items-center gap-4 flex-wrap justify-end">
      <div class="flex items-center gap-2 text-xs text-[#5C4033] opacity-75 dark:text-gray-300 dark:opacity-100">
        <i class="fa-solid fa-phone"></i>
        <span>09517394938</span>
      </div>
    </div>
  </div>

  <div class="w-full px-4 py-4">
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
                <span class="text-xs font-semibold uppercase tracking-wide text-[#C19A6B]">Subject</span>
                <div class="flex items-center gap-2 mb-2 mt-1">
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
              <span class="block text-xs font-semibold uppercase tracking-wide text-[#C19A6B] mb-2">Message</span>
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
const mobileOverlay = document.getElementById('mobileOverlay');

if (menuBtn && mobileOverlay) {
  menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    mobileOverlay.classList.toggle('hidden');
  });

  mobileOverlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    mobileOverlay.classList.add('hidden');
  });
}
// Sidebar toggle functionality
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
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const data = await response.json().catch(() => null);

      if (response.ok && data && data.status === 'success') {
        window.location.reload();
        return;
      }

      const message = data && data.message ? data.message : 'Failed to submit comment.';
      throw new Error(message);
    } catch (error) {
      alert(error.message);
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