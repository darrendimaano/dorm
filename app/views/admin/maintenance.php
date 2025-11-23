<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if (session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;

$maintenanceRequests = $maintenanceRequests ?? [];
$success = $success ?? '';
$error = $error ?? '';

$pendingCount = 0;
$inProgressCount = 0;
$completedCount = 0;
$cancelledCount = 0;

foreach ($maintenanceRequests as $request) {
    switch ($request['status'] ?? '') {
        case 'pending':
            $pendingCount++;
            break;
        case 'in_progress':
            $inProgressCount++;
            break;
        case 'completed':
            $completedCount++;
            break;
        case 'cancelled':
            $cancelledCount++;
            break;
    }
}

$totalRequests = count($maintenanceRequests);
?>
<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Maintenance Requests - Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  #sidebar {
    transition: width 0.3s ease, transform 0.3s ease;
    background: #D2B48C;
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
  .dark body {
    background: #111111 !important;
    color: #e5e5e5 !important;
  }
  .dark .content-card {
    background: #1f1f1f !important;
    border-color: #3a3a3a !important;
  }
  .dark .text-primary {
    color: #e5e5e5 !important;
  }
</style>
</head>
<body class="min-h-screen transition-colors<?= $darkModeEnabled ? ' dark' : '' ?>">

<?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>

<div class="flex-1 transition-all duration-300" id="mainContent" style="margin-left: 16rem;">
  <div style="background: #FFF5E1;" class="shadow-md flex items-center justify-between px-6 py-4 header-section">
    <div class="flex items-center gap-4">
      <button id="sidebarToggle" class="text-[#5C4033] text-xl hover:bg-[#C19A6B] hover:text-white p-2 rounded-lg transition-all">
        <i class="fa-solid fa-bars" id="toggleIcon"></i>
      </button>
      <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div>
        <h1 class="font-bold text-xl text-[#5C4033]">Maintenance Requests</h1>
        <p class="text-[#5C4033] opacity-75 text-sm">Monitor and resolve tenant maintenance concerns</p>
      </div>
    </div>
    <div class="flex items-center gap-4 text-sm text-[#5C4033] opacity-75">
      <i class="fa-solid fa-clock"></i>
      <span><?= date('M j, Y h:i A') ?></span>
    </div>
  </div>

  <div class="w-full px-4 py-4">
    <?php if (!empty($success)): ?>
      <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-lg mb-6 text-center shadow-sm">
        <i class="fa-solid fa-check-circle text-lg"></i> <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-lg mb-6 text-center shadow-sm">
        <i class="fa-solid fa-exclamation-circle text-lg"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="content-card p-5 rounded-xl border shadow-sm" style="background: #FFF5E1; border-color: #C19A6B;">
        <div class="text-sm text-[#5C4033] opacity-70">Total Requests</div>
        <div class="text-2xl font-bold text-[#5C4033] mt-2"><?= $totalRequests ?></div>
      </div>
      <div class="content-card p-5 rounded-xl border shadow-sm" style="background: #FFF5E1; border-color: #C19A6B;">
        <div class="text-sm text-[#5C4033] opacity-70">Pending</div>
        <div class="text-2xl font-bold text-[#5C4033] mt-2"><?= $pendingCount ?></div>
      </div>
      <div class="content-card p-5 rounded-xl border shadow-sm" style="background: #FFF5E1; border-color: #C19A6B;">
        <div class="text-sm text-[#5C4033] opacity-70">In Progress</div>
        <div class="text-2xl font-bold text-[#5C4033] mt-2"><?= $inProgressCount ?></div>
      </div>
      <div class="content-card p-5 rounded-xl border shadow-sm" style="background: #FFF5E1; border-color: #C19A6B;">
        <div class="text-sm text-[#5C4033] opacity-70">Completed</div>
        <div class="text-2xl font-bold text-[#5C4033] mt-2"><?= $completedCount ?></div>
      </div>
    </div>

    <div style="background: #FFF5E1; border-color: #C19A6B;" class="content-card rounded-xl border shadow-sm">
      <div class="p-6 border-b" style="border-color: #C19A6B;">
        <h2 class="text-xl font-bold text-[#5C4033] flex items-center gap-2">
          <i class="fa-solid fa-tools text-[#C19A6B]"></i>
          Active Requests
        </h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead style="background: #e6ddd4;">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Tenant</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Room</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Issue</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Priority</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Submitted</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y" style="divide-color: #C19A6B;">
            <?php if (!empty($maintenanceRequests)): ?>
              <?php foreach ($maintenanceRequests as $request): ?>
                <?php
                  $priorityColors = [
                    'low' => 'bg-gray-100 text-gray-800',
                    'medium' => 'bg-blue-100 text-blue-800',
                    'high' => 'bg-orange-100 text-orange-800',
                    'urgent' => 'bg-red-100 text-red-800'
                  ];
                  $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'in_progress' => 'bg-blue-100 text-blue-800',
                    'completed' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-gray-200 text-gray-700'
                  ];
                  $priorityClass = $priorityColors[$request['priority'] ?? 'medium'] ?? 'bg-gray-100 text-gray-800';
                  $statusClass = $statusColors[$request['status'] ?? 'pending'] ?? 'bg-yellow-100 text-yellow-800';
                  $tenantName = trim(($request['fname'] ?? '') . ' ' . ($request['lname'] ?? ''));
                ?>
                <?php
                  $modalData = [
                    'id' => $request['id'] ?? null,
                    'tenant' => $tenantName ?: 'Unknown Tenant',
                    'room' => $request['room_number'] ?? 'N/A',
                    'priority' => $request['priority'] ?? 'medium',
                    'status' => $request['status'] ?? 'pending',
                    'title' => $request['title'] ?? '',
                    'description' => $request['description'] ?? '',
                    'notes' => $request['admin_notes'] ?? ''
                  ];
                  $modalJson = htmlspecialchars(json_encode($modalData), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                  <td class="px-4 py-4 text-sm text-[#5C4033]">
                    <div class="font-semibold"><?= htmlspecialchars($tenantName ?: 'Unknown Tenant') ?></div>
                    <div class="text-xs opacity-75">ID: <?= (int) ($request['user_id'] ?? 0) ?></div>
                  </td>
                  <td class="px-4 py-4 text-sm text-[#5C4033]">#<?= htmlspecialchars($request['room_number'] ?? 'N/A') ?></td>
                  <td class="px-4 py-4 text-sm text-[#5C4033]">
                    <div class="font-medium"><?= htmlspecialchars($request['title'] ?? 'Maintenance Issue') ?></div>
                    <div class="text-xs opacity-75"><?= htmlspecialchars(substr($request['description'] ?? '', 0, 50)) ?><?= strlen($request['description'] ?? '') > 50 ? '…' : '' ?></div>
                  </td>
                  <td class="px-4 py-4 text-sm text-[#5C4033]">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $priorityClass ?>"><?= ucfirst($request['priority'] ?? 'medium') ?></span>
                  </td>
                  <td class="px-4 py-4 text-sm text-[#5C4033]">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $request['status'] ?? 'pending')) ?></span>
                  </td>
                  <td class="px-4 py-4 text-sm text-[#5C4033]">
                    <?= isset($request['created_at']) ? date('M j, Y h:i A', strtotime($request['created_at'])) : '—' ?>
                  </td>
                  <td class="px-4 py-4 text-sm text-[#5C4033]">
                    <div class="flex flex-col gap-2">
                      <button class="px-3 py-2 bg-[#C19A6B] text-white rounded-lg text-xs font-semibold hover:bg-[#A67C52] transition" onclick="openUpdateModal(<?= $modalJson ?>)">
                        <i class="fa-solid fa-pen mr-1"></i> Update Status
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="px-6 py-10 text-center text-[#5C4033] opacity-75">
                  <i class="fa-solid fa-wrench text-4xl mb-4 block text-[#C19A6B] opacity-50"></i>
                  No maintenance requests found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
  <div class="bg-[#FFF5E1] rounded-lg shadow-xl border-2 border-[#C19A6B] p-6 w-full max-w-lg mx-4">
    <div class="mb-4 pb-3 border-b border-[#E5D3B3]">
      <h3 class="text-lg font-bold text-[#5C4033] flex items-center gap-2">
        <i class="fa-solid fa-screwdriver-wrench text-[#C19A6B]"></i>
        Update Maintenance Request
      </h3>
    </div>
    <form id="updateForm" method="POST" action="<?= site_url('maintenance/update') ?>">
      <input type="hidden" name="id" id="update_request_id">
      <div class="mb-4">
        <p class="text-sm text-[#5C4033]"><span class="font-semibold">Tenant:</span> <span id="update_tenant" class="opacity-80"></span></p>
        <p class="text-sm text-[#5C4033]"><span class="font-semibold">Room:</span> <span id="update_room" class="opacity-80"></span></p>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2 text-[#5C4033]">Issue</label>
        <p id="update_issue" class="text-sm text-[#5C4033] opacity-80"></p>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2 text-[#5C4033]">Status</label>
        <select name="status" id="update_status" class="w-full p-3 border border-[#E5D3B3] rounded-lg focus:border-[#C19A6B] focus:ring focus:ring-[#C19A6B] focus:ring-opacity-30" required>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="block text-sm font-medium mb-2 text-[#5C4033]">Admin Notes</label>
        <textarea name="admin_notes" id="update_notes" rows="3" class="w-full p-3 border border-[#E5D3B3] rounded-lg focus:border-[#C19A6B] focus:ring focus:ring-[#C19A6B] focus:ring-opacity-30" placeholder="Add internal notes or instructions..."></textarea>
      </div>
      <div class="flex gap-3">
        <button type="submit" style="background: #C19A6B;" class="hover:bg-[#A67C52] text-white px-6 py-3 rounded-lg flex-1 font-semibold transition">
          <i class="fa-solid fa-save mr-2"></i>Save Changes
        </button>
        <button type="button" onclick="closeUpdateModal()" class="bg-[#8B7355] hover:bg-[#6B5B48] text-white px-6 py-3 rounded-lg font-semibold transition">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

<script>
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const mainContent = document.getElementById('mainContent');
const toggleIcon = document.getElementById('toggleIcon');
const menuBtn = document.getElementById('menuBtn');
const mobileOverlay = document.getElementById('mobileOverlay');

function updateMainContentMargin() {
  if (!mainContent) return;
  if (window.innerWidth < 768) {
    mainContent.style.marginLeft = '0';
    return;
  }
  const isCollapsed = sidebar && sidebar.classList.contains('collapsed');
  mainContent.style.marginLeft = isCollapsed ? '4rem' : '16rem';
}

if (sidebar) {
  const savedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  if (savedCollapsed) {
    sidebar.classList.add('collapsed');
    if (toggleIcon) {
      toggleIcon.className = 'fa-solid fa-times';
    }
  }
}

updateMainContentMargin();

if (sidebarToggle) {
  sidebarToggle.addEventListener('click', () => {
    if (!sidebar) return;
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    if (toggleIcon) {
      toggleIcon.className = isCollapsed ? 'fa-solid fa-times' : 'fa-solid fa-bars';
    }
    localStorage.setItem('sidebarCollapsed', isCollapsed);
    updateMainContentMargin();
  });
}

if (menuBtn) {
  menuBtn.addEventListener('click', () => {
    if (!sidebar || !mobileOverlay) return;
    const isHidden = sidebar.classList.contains('-translate-x-full');
    if (isHidden) {
      sidebar.classList.remove('-translate-x-full');
      mobileOverlay.classList.remove('hidden');
    } else {
      sidebar.classList.add('-translate-x-full');
      mobileOverlay.classList.add('hidden');
    }
  });
}

if (mobileOverlay) {
  mobileOverlay.addEventListener('click', () => {
    mobileOverlay.classList.add('hidden');
    if (sidebar) {
      sidebar.classList.add('-translate-x-full');
    }
  });
}

window.addEventListener('resize', updateMainContentMargin);

window.closeLogoutModal = window.closeLogoutModal || function() {
  const modal = document.getElementById('logoutModal');
  if (modal) {
    modal.remove();
  }
};

window.proceedLogout = window.proceedLogout || function() {
  window.location.href = '<?= site_url('auth/logout') ?>';
};

window.confirmLogout = window.confirmLogout || function() {
  if (document.getElementById('logoutModal')) {
    return;
  }

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

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeLogoutModal();
    }
  });

  const escHandler = (event) => {
    if (event.key === 'Escape') {
      closeLogoutModal();
      document.removeEventListener('keydown', escHandler);
    }
  };

  document.addEventListener('keydown', escHandler);
  document.body.appendChild(modal);
};

function openUpdateModal(data) {
  const modal = document.getElementById('updateModal');
  if (!modal) return;

  document.getElementById('update_request_id').value = data.id || '';
  document.getElementById('update_tenant').textContent = data.tenant || 'Unknown Tenant';
  document.getElementById('update_room').textContent = `Room #${data.room || 'N/A'}`;
  document.getElementById('update_issue').textContent = `${data.title || ''} — ${data.description || ''}`.trim();
  document.getElementById('update_status').value = data.status || 'pending';
  document.getElementById('update_notes').value = data.notes || '';

  modal.classList.remove('hidden');
}

function closeUpdateModal() {
  const modal = document.getElementById('updateModal');
  if (modal) {
    modal.classList.add('hidden');
  }
}

window.closeUpdateModal = closeUpdateModal;

window.addEventListener('click', (event) => {
  const modal = document.getElementById('updateModal');
  if (event.target === modal) {
    closeUpdateModal();
  }
});
</script>
</body>
</html>
