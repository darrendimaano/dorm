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
<title>Manage Announcements - Admin</title>
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
  .dark .admin-card {
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
<body class="min-h-screen transition-colors<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

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
        <h1 class="font-bold text-xl text-[#5C4033]">Announcements Management</h1>
        <p class="text-[#5C4033] opacity-75 text-sm">Create and manage announcements for tenants</p>
      </div>
    </div>
    <div class="flex items-center gap-4">
      <button onclick="openCreateModal()" class="bg-[#C19A6B] text-white px-4 py-2 rounded-lg hover:bg-[#5C4033] transition-all">
        <i class="fa-solid fa-plus mr-2"></i>New Announcement
      </button>
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

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border admin-card" style="border-color: #C19A6B;">
            <div class="flex items-center gap-4">
                <div class="bg-[#C19A6B] p-3 rounded-lg">
                    <i class="fa-solid fa-bullhorn text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[#5C4033]">Total</h3>
                    <p class="text-2xl font-bold text-[#C19A6B]"><?= count($announcements ?? []) ?></p>
                </div>
            </div>
        </div>
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border admin-card" style="border-color: #C19A6B;">
            <div class="flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fa-solid fa-eye text-green-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[#5C4033]">Active</h3>
                    <p class="text-2xl font-bold text-green-600"><?= $activeCount ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border admin-card" style="border-color: #C19A6B;">
            <div class="flex items-center gap-4">
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="fa-solid fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-[#5C4033]">Urgent</h3>
                    <p class="text-2xl font-bold text-red-600"><?= $urgentCount ?? 0 ?></p>
                </div>
            </div>
        </div>
        <button type="button" id="openCommentsSummary" class="text-left" style="background: #FFF5E1; border: none; cursor: pointer;">
          <div class="p-6 rounded-xl shadow-sm border admin-card transition-transform duration-200 hover:-translate-y-1" style="border-color: #C19A6B;">
            <div class="flex items-center gap-4">
              <div class="bg-blue-100 p-3 rounded-lg">
                <i class="fa-solid fa-comments text-blue-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-[#5C4033]">Comments</h3>
                <p class="text-2xl font-bold text-blue-600"><?= $totalComments ?? 0 ?></p>
                <p class="text-xs text-[#5C4033] opacity-75">Click to review tenant replies</p>
              </div>
            </div>
          </div>
        </button>
    </div>

    <!-- Filter and Search -->
    <div class="mb-6">
      <div style="background: #FFF5E1;" class="p-4 rounded-lg border admin-card" style="border-color: #C19A6B;">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-filter text-[#C19A6B]"></i>
            <span class="text-[#5C4033] font-semibold">Filter:</span>
          </div>
          <select id="statusFilter" class="px-3 py-2 border border-[#C19A6B] rounded-lg bg-[#FFF5E1] text-[#5C4033]">
            <option value="all">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
          <select id="priorityFilter" class="px-3 py-2 border border-[#C19A6B] rounded-lg bg-[#FFF5E1] text-[#5C4033]">
            <option value="all">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
          <div class="flex-1">
            <input type="text" id="searchInput" placeholder="Search announcements..." 
                   class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]">
          </div>
        </div>
      </div>
    </div>

    <!-- Announcements Table -->
    <div style="background: #FFF5E1;" class="rounded-xl shadow-sm border admin-card" style="border-color: #C19A6B;">
      <div class="p-6 border-b" style="border-color: #C19A6B;">
        <h2 class="text-xl font-bold text-[#5C4033]">
          <i class="fa-solid fa-list text-[#C19A6B] mr-2"></i>
          All Announcements
        </h2>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead style="background: #e6ddd4;">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Subject</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Priority</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Comments</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Expires</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y" style="divide-color: #C19A6B;" id="announcementsTableBody">
            <?php if(!empty($announcements)): ?>
              <?php foreach($announcements as $announcement): ?>
                <tr class="announcement-row" 
                    data-status="<?= $announcement['is_active'] ?>" 
                    data-priority="<?= $announcement['priority'] ?>"
                    data-title="<?= strtolower(htmlspecialchars($announcement['title'])) ?>">
                  <td class="px-6 py-4">
                    <div>
                      <div class="text-sm font-medium text-[#5C4033]"><?= htmlspecialchars($announcement['title']) ?></div>
                      <div class="text-xs text-[#5C4033] opacity-75"><?= htmlspecialchars(substr($announcement['content'], 0, 80)) ?>...</div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
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
                  </td>
                  <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $announcement['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                      <?= $announcement['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-[#5C4033]">
                    <i class="fa-solid fa-comments text-[#C19A6B] mr-1"></i>
                    <?= $announcement['comment_count'] ?? 0 ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-[#5C4033]">
                    <?= $announcement['expires_at'] ? date('M j, Y', strtotime($announcement['expires_at'])) : 'Never' ?>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                      <button onclick="editAnnouncement(<?= $announcement['id'] ?>)" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fa-solid fa-edit"></i>
                      </button>
                      <button onclick="toggleStatus(<?= $announcement['id'] ?>, <?= $announcement['is_active'] ? 'false' : 'true' ?>)" 
                              class="text-yellow-600 hover:text-yellow-800 text-sm" title="<?= $announcement['is_active'] ? 'Deactivate' : 'Activate' ?>">
                        <i class="fa-solid <?= $announcement['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                      </button>
                      <button onclick="deleteAnnouncement(<?= $announcement['id'] ?>)" class="text-red-600 hover:text-red-800 text-sm">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-6 py-8 text-center text-[#5C4033] opacity-75">
                  <i class="fa-solid fa-bullhorn text-4xl mb-4 block text-[#C19A6B] opacity-50"></i>
                  No announcements found. Create your first announcement!
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Create/Edit Modal -->
<div id="announcementModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-[#FFF5E1] p-6 rounded-lg max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" style="border: 2px solid #C19A6B;">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-bold text-[#5C4033]" id="modalTitle">Create New Announcement</h3>
      <button onclick="closeModal()" class="text-[#5C4033] hover:text-red-600">
        <i class="fa-solid fa-times text-xl"></i>
      </button>
    </div>
    
    <form id="announcementForm" onsubmit="submitAnnouncement(event)">
      <input type="hidden" id="announcementId" name="id" value="">
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-[#5C4033] font-semibold mb-2">Priority</label>
          <select id="priority" name="priority" required class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        
        <div>
          <label class="block text-[#5C4033] font-semibold mb-2">Expires At (Optional)</label>
          <input type="date" id="expiresAt" name="expires_at" class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]">
        </div>
      </div>
      
      <div class="mb-4">
        <label class="block text-[#5C4033] font-semibold mb-2">Subject</label>
        <input type="text" id="title" name="title" required placeholder="Announcement subject" 
               class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]">
      </div>
      
      <div class="mb-6">
        <label class="block text-[#5C4033] font-semibold mb-2">Content</label>
        <textarea id="content" name="content" required rows="6" placeholder="Announcement content..."
                  class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1] resize-none"></textarea>
      </div>
      
      <div class="flex gap-3 justify-end">
        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
          Cancel
        </button>
        <button type="submit" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition">
          <i class="fa-solid fa-save mr-2"></i>Save
        </button>
      </div>
    </form>
  </div>
</div>

  <!-- Comments Review Modal -->
  <div id="commentsReviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-[#FFF5E1] rounded-xl shadow-2xl w-full max-w-5xl mx-4 max-h-[90vh] overflow-hidden border-2 border-[#C19A6B] flex flex-col md:flex-row">
      <div class="md:w-72 bg-[#F8EAD6] border-r border-[#E5D3B3] overflow-y-auto" id="commentsAnnouncementList">
        <div class="p-4 border-b border-[#E5D3B3] flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-[#5C4033]">Announcements</h3>
            <p class="text-xs text-[#5C4033] opacity-75">Select an announcement to review tenant replies.</p>
          </div>
          <button type="button" onclick="closeCommentsReviewModal()" class="text-[#5C4033] hover:text-red-600">
            <i class="fa-solid fa-times"></i>
          </button>
        </div>
        <ul id="commentsAnnouncementItems" class="divide-y divide-[#E5D3B3]"></ul>
      </div>

      <div class="flex-1 flex flex-col">
        <div class="p-5 border-b border-[#E5D3B3]">
          <h3 class="text-xl font-bold text-[#5C4033]" id="commentsReviewTitle">Comments</h3>
          <p class="text-sm text-[#5C4033] opacity-75" id="commentsReviewMeta"></p>
        </div>
        <div class="flex-1 overflow-y-auto p-5" id="commentsReviewBody">
          <div id="commentsReviewLoading" class="hidden items-center gap-2 text-[#5C4033] text-sm">
            <i class="fa-solid fa-spinner fa-spin"></i>
            Loading comments...
          </div>
          <div id="commentsReviewError" class="hidden bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-3 text-sm"></div>
          <div id="commentsReviewEmpty" class="hidden text-center text-[#5C4033] opacity-75 py-10">
            <i class="fa-solid fa-comments-slash text-3xl mb-3"></i>
            <p>No tenant comments for this announcement yet.</p>
          </div>
          <ul id="commentsReviewList" class="space-y-4"></ul>
        </div>
        <div class="p-5 border-t border-[#E5D3B3] flex justify-end">
          <button type="button" onclick="closeCommentsReviewModal()" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition">
            Close
          </button>
        </div>
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
const commentsReviewModal = document.getElementById('commentsReviewModal');
const commentsAnnouncementItems = document.getElementById('commentsAnnouncementItems');
const commentsReviewTitle = document.getElementById('commentsReviewTitle');
const commentsReviewMeta = document.getElementById('commentsReviewMeta');
const commentsReviewList = document.getElementById('commentsReviewList');
const commentsReviewLoading = document.getElementById('commentsReviewLoading');
const commentsReviewEmpty = document.getElementById('commentsReviewEmpty');
const commentsReviewError = document.getElementById('commentsReviewError');

if (menuBtn && sidebar && mobileOverlay) {
  menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    mobileOverlay.classList.toggle('hidden');
  });

  mobileOverlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    mobileOverlay.classList.add('hidden');
  });
}

const announcementSummaries = <?= json_encode(array_map(static function ($item) {
  return [
    'id' => (int) ($item['id'] ?? 0),
    'title' => $item['title'] ?? 'Untitled announcement',
    'priority' => $item['priority'] ?? 'medium',
    'comment_count' => (int) ($item['comment_count'] ?? 0),
    'is_active' => (int) ($item['is_active'] ?? 0)
  ];
}, $announcements ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

const announcementDetails = <?= json_encode((function(array $items) {
  $formatted = [];
  foreach ($items as $item) {
    $id = isset($item['id']) ? (int) $item['id'] : 0;
    if ($id <= 0) {
      continue;
    }

    $comments = [];
    foreach ($item['comments'] ?? [] as $comment) {
      $createdAt = $comment['created_at'] ?? '';
      $comments[] = [
        'id' => isset($comment['id']) ? (int) $comment['id'] : 0,
        'user_name' => $comment['user_name'] ?? 'Tenant',
        'comment' => $comment['comment'] ?? '',
        'created_at' => $createdAt,
        'created_at_human' => $createdAt ? date('M j, Y g:i A', strtotime($createdAt)) : ''
      ];
    }

    $formatted[$id] = [
      'title' => $item['title'] ?? 'Untitled announcement',
      'priority' => $item['priority'] ?? 'medium',
      'is_active' => isset($item['is_active']) ? (int) $item['is_active'] : 0,
      'content' => $item['content'] ?? '',
      'expires_at' => $item['expires_at'] ?? null,
      'comments' => $comments
    ];
  }

  return $formatted;
})($announcements ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

let activeAnnouncementId = null;

function openCommentsReviewModal() {
  if (!commentsReviewModal) {
    return;
  }

  commentsReviewError.classList.add('hidden');
  commentsReviewEmpty.classList.add('hidden');
  commentsReviewList.innerHTML = '';
  renderAnnouncementList();
  commentsReviewModal.classList.remove('hidden');

  const defaultAnnouncement = announcementSummaries.find(item => item.comment_count > 0)
    || announcementSummaries[0]
    || null;

  if (defaultAnnouncement) {
    loadAnnouncementComments(defaultAnnouncement.id, defaultAnnouncement);
  } else {
    commentsReviewTitle.textContent = 'Comments';
    commentsReviewMeta.textContent = 'No announcements available yet.';
    commentsReviewEmpty.classList.remove('hidden');
  }
}

function closeCommentsReviewModal() {
  if (!commentsReviewModal) {
    return;
  }

  commentsReviewModal.classList.add('hidden');
  activeAnnouncementId = null;
}

function renderAnnouncementList() {
  if (!commentsAnnouncementItems) {
    return;
  }

  commentsAnnouncementItems.innerHTML = '';

  if (!Array.isArray(announcementSummaries) || announcementSummaries.length === 0) {
    const placeholder = document.createElement('li');
    placeholder.className = 'p-4 text-sm text-[#5C4033] opacity-75';
    placeholder.textContent = 'No announcements available.';
    commentsAnnouncementItems.appendChild(placeholder);
    return;
  }

  announcementSummaries.forEach(item => {
    const listItem = document.createElement('li');
    listItem.className = 'p-4 cursor-pointer hover:bg-[#F0E0C8] transition flex flex-col gap-1';
    listItem.dataset.announcementId = String(item.id);

    const titleEl = document.createElement('p');
    titleEl.className = 'text-sm font-semibold text-[#5C4033]';
    titleEl.textContent = item.title;

    const metaEl = document.createElement('div');
    metaEl.className = 'flex items-center justify-between text-xs text-[#5C4033] opacity-75 gap-2';
    const commentLabel = item.comment_count === 1 ? 'comment' : 'comments';
    metaEl.innerHTML = `<span>${item.comment_count} ${commentLabel}</span><span class="uppercase font-semibold">${item.priority}</span>`;

    if (!item.is_active) {
      const inactiveBadge = document.createElement('span');
      inactiveBadge.className = 'text-[10px] uppercase tracking-wide text-red-600 font-bold';
      inactiveBadge.textContent = 'Inactive';
      metaEl.appendChild(inactiveBadge);
    }

    listItem.appendChild(titleEl);
    listItem.appendChild(metaEl);

    listItem.addEventListener('click', () => {
      loadAnnouncementComments(item.id, item);
    });

    commentsAnnouncementItems.appendChild(listItem);
  });
}

function loadAnnouncementComments(announcementId, announcementMeta) {
  if (!commentsReviewList) {
    return;
  }

  if (activeAnnouncementId === announcementId) {
    return;
  }

  activeAnnouncementId = announcementId;
  commentsReviewError.classList.add('hidden');
  commentsReviewEmpty.classList.add('hidden');
  commentsReviewList.innerHTML = '';
  commentsReviewLoading.classList.remove('hidden');

  if (commentsAnnouncementItems) {
    Array.from(commentsAnnouncementItems.children).forEach(node => {
      node.classList.remove('bg-[#E3D4BC]');
      if (parseInt(node.dataset.announcementId || '0', 10) === announcementId) {
        node.classList.add('bg-[#E3D4BC]');
      }
    });
  }

  const detailKey = String(announcementId);
  const detail = (announcementDetails && (announcementDetails[detailKey] ?? announcementDetails[announcementId])) || null;

  if (!detail) {
    commentsReviewLoading.classList.add('hidden');
    commentsReviewError.textContent = 'Comments data is not available for this announcement.';
    commentsReviewError.classList.remove('hidden');
    return;
  }

  const statusLabel = (detail.is_active ?? announcementMeta?.is_active) ? 'Active' : 'Inactive';
  const priorityLabel = detail.priority || announcementMeta?.priority || 'medium';
  const titleLabel = detail.title || announcementMeta?.title || 'Comments';
  const comments = Array.isArray(detail.comments) ? detail.comments : [];
  const commentLabel = comments.length === 1 ? 'comment' : 'comments';

  commentsReviewTitle.textContent = titleLabel;
  commentsReviewMeta.textContent = `${comments.length} ${commentLabel} • ${priorityLabel} • ${statusLabel}`;

  commentsReviewLoading.classList.add('hidden');

  if (comments.length === 0) {
    commentsReviewEmpty.classList.remove('hidden');
    return;
  }

  comments.forEach(comment => {
    const item = document.createElement('li');
    item.className = 'bg-[#F8EAD6] border border-[#E5D3B3] rounded-lg p-4 shadow-sm';

    const header = document.createElement('div');
    header.className = 'flex items-center justify-between mb-2';

    const author = document.createElement('span');
    author.className = 'text-sm font-semibold text-[#5C4033]';
    author.textContent = comment.user_name || 'Tenant';

    const timestamp = document.createElement('span');
    timestamp.className = 'text-xs text-[#5C4033] opacity-75';
    timestamp.textContent = comment.created_at_human || '';

    header.appendChild(author);
    header.appendChild(timestamp);

    const body = document.createElement('p');
    body.className = 'text-sm text-[#5C4033] whitespace-pre-line';
    body.textContent = comment.comment || '';

    item.appendChild(header);
    item.appendChild(body);

    commentsReviewList.appendChild(item);
  });
}

const openCommentsSummaryButton = document.getElementById('openCommentsSummary');
if (openCommentsSummaryButton) {
  openCommentsSummaryButton.addEventListener('click', openCommentsReviewModal);
}

document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && commentsReviewModal && !commentsReviewModal.classList.contains('hidden')) {
    closeCommentsReviewModal();
  }
});

document.addEventListener('click', event => {
  if (event.target === commentsReviewModal) {
    closeCommentsReviewModal();
  }
});

// Filter and search functionality
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('statusFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('.announcement-row');
    
    function filterRows() {
        const statusValue = statusFilter.value;
        const priorityValue = priorityFilter.value;
        const searchValue = searchInput.value.toLowerCase();
        
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const priority = row.getAttribute('data-priority');
            const title = row.getAttribute('data-title');
            
            const statusMatch = statusValue === 'all' || status === statusValue;
            const priorityMatch = priorityValue === 'all' || priority === priorityValue;
            const searchMatch = title.includes(searchValue);
            
            if (statusMatch && priorityMatch && searchMatch) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    statusFilter.addEventListener('change', filterRows);
    priorityFilter.addEventListener('change', filterRows);
    searchInput.addEventListener('input', filterRows);
});

function normalizeDateInput(value) {
  if (!value) {
    return '';
  }

  const trimmed = String(value).trim();
  const isoMatch = trimmed.match(/^(\d{4}-\d{2}-\d{2})/);
  if (isoMatch) {
    return isoMatch[1];
  }

  const parsed = new Date(trimmed);
  if (!Number.isNaN(parsed.getTime())) {
    return parsed.toISOString().slice(0, 10);
  }

  return '';
}

// Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create New Announcement';
    document.getElementById('announcementForm').reset();
    document.getElementById('announcementId').value = '';
    const priorityField = document.getElementById('priority');
    if (priorityField) {
      priorityField.value = 'medium';
    }
    document.getElementById('announcementModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('announcementModal').classList.add('hidden');
}

function editAnnouncement(id) {
    const detailKey = String(id);
    const detail = (announcementDetails && (announcementDetails[detailKey] ?? announcementDetails[id])) || null;

    if (!detail) {
      alert('Announcement data is not available. Please refresh the page and try again.');
      return;
    }

    document.getElementById('modalTitle').textContent = 'Edit Announcement';
    document.getElementById('announcementId').value = id;
    document.getElementById('title').value = detail.title || '';
    document.getElementById('content').value = detail.content || '';
    const priorityField = document.getElementById('priority');
    if (priorityField) {
      const desiredPriority = (detail.priority || 'medium').toLowerCase();
      const allowed = ['low', 'medium', 'high', 'urgent'];
      priorityField.value = allowed.includes(desiredPriority) ? desiredPriority : 'medium';
    }

    const expiresField = document.getElementById('expiresAt');
    if (expiresField) {
      expiresField.value = normalizeDateInput(detail.expires_at);
    }

    document.getElementById('announcementModal').classList.remove('hidden');
}

// Submit announcement
async function submitAnnouncement(event) {
    event.preventDefault();
    
    const submitButton = event.target.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Set loading state
    submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    submitButton.disabled = true;
    
    try {
        const formData = new FormData(event.target);
        const response = await fetch('<?= site_url('announcements/save') ?>', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
          window.location.reload();
        } else {
          throw new Error(data.message || 'Failed to save announcement');
        }
    } catch (error) {
        console.error('Error saving announcement:', error);
        alert('Error saving announcement: ' + error.message);
        
        // Restore button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

// Toggle status
async function toggleStatus(id, newStatus) {
    const confirmed = await confirmToggle(newStatus);
    if (!confirmed) return;
    
    try {
        const response = await fetch('<?= site_url('announcements/toggle') ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin',
          body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(newStatus)}`
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
          window.location.reload();
        } else {
          throw new Error(data.message || 'Failed to update status');
        }
    } catch (error) {
        alert('Error updating status: ' + error.message);
    }
}

// Delete announcement
async function deleteAnnouncement(id) {
    const confirmed = await confirmDelete();
    if (!confirmed) return;
    
    try {
        const response = await fetch('<?= site_url('announcements/delete') ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin',
          body: `id=${encodeURIComponent(id)}`
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
          window.location.reload();
        } else {
          throw new Error(data.message || 'Failed to delete announcement');
        }
    } catch (error) {
        alert('Error deleting announcement: ' + error.message);
    }
}

// Confirmation dialogs
function confirmToggle(newStatus) {
    const action = newStatus === 'true' ? 'activate' : 'deactivate';
    return confirmAction(`Are you sure you want to ${action} this announcement?`);
}

function confirmDelete() {
    return confirmAction('Are you sure you want to delete this announcement? This action cannot be undone.');
}

function confirmAction(message) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-[#FFF5E1] p-6 rounded-lg max-w-md mx-4" style="border: 2px solid #C19A6B;">
                <h3 class="text-lg font-bold mb-4 text-[#5C4033]">Confirm Action</h3>
                <p class="mb-6 text-[#5C4033] opacity-75">${message}</p>
                <div class="flex gap-3 justify-end">
                    <button id="cancelBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                    <button id="confirmBtn" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition">Confirm</button>
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