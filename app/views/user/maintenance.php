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
<title>Maintenance Requests - Dormitory</title>
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
    background: #1f1f1f !important;
    border-color: #3a3a3a !important;
    color: #e5e5e5 !important;
  }
  .dark .header-section {
    background: #1a1a1a !important;
    color: #e5e5e5 !important;
  }
  .dark [class*="bg-[#FFF5E1]"], .dark [class*="bg-white"] {
    background: #1f1f1f !important;
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
        <h1 class="font-bold text-xl text-[#5C4033]">Maintenance Requests</h1>
        <p class="text-[#5C4033] opacity-75 text-sm">Submit and track maintenance requests</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Submit New Request -->
      <div class="lg:col-span-2">
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border user-card" style="border-color: #C19A6B;">
          <h2 class="text-xl font-bold text-[#5C4033] mb-6">
            <i class="fa-solid fa-plus-circle text-[#C19A6B] mr-2"></i>
            Submit Maintenance Request
          </h2>
          
          <form method="POST" action="<?= site_url('maintenance/submit') ?>" onsubmit="return handleMaintenanceSubmit(event)">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-[#5C4033] font-semibold mb-2">Room Number</label>
                <select name="room_id" required class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]">
                  <option value="">Select Room</option>
                  <?php if(!empty($userRooms)): ?>
                    <?php foreach($userRooms as $room): ?>
                      <option value="<?= $room['id'] ?>">Room #<?= htmlspecialchars($room['room_number']) ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              
              <div>
                <label class="block text-[#5C4033] font-semibold mb-2">Priority</label>
                <select name="priority" required class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]">
                  <option value="low">Low</option>
                  <option value="medium" selected>Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
            
            <div class="mb-4">
              <label class="block text-[#5C4033] font-semibold mb-2">Issue Title</label>
              <input type="text" name="title" required placeholder="Brief description of the issue" 
                     class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]">
            </div>
            
            <div class="mb-6">
              <label class="block text-[#5C4033] font-semibold mb-2">Detailed Description</label>
              <textarea name="description" required rows="4" placeholder="Please provide detailed information about the maintenance issue..."
                        class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1] resize-none"></textarea>
            </div>
            
            <button type="submit" class="w-full bg-[#C19A6B] text-white py-3 px-6 rounded-lg font-semibold hover:bg-[#5C4033] transition-all">
              <i class="fa-solid fa-paper-plane mr-2"></i>
              Submit Request
            </button>
          </form>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="space-y-6">
        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border user-card" style="border-color: #C19A6B;">
          <h3 class="text-lg font-bold text-[#5C4033] mb-4">
            <i class="fa-solid fa-chart-bar text-[#C19A6B] mr-2"></i>
            Request Statistics
          </h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-[#5C4033] opacity-75">Pending</span>
              <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-sm font-semibold">
                <?= $pendingCount ?? 0 ?>
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-[#5C4033] opacity-75">In Progress</span>
              <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-semibold">
                <?= $inProgressCount ?? 0 ?>
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-[#5C4033] opacity-75">Completed</span>
              <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm font-semibold">
                <?= $completedCount ?? 0 ?>
              </span>
            </div>
          </div>
        </div>

        <div style="background: #FFF5E1;" class="p-6 rounded-xl shadow-sm border user-card" style="border-color: #C19A6B;">
          <h3 class="text-lg font-bold text-[#5C4033] mb-4">
            <i class="fa-solid fa-clock text-[#C19A6B] mr-2"></i>
            Response Times
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-[#5C4033] opacity-75">Low Priority:</span>
              <span class="text-[#5C4033]">48-72 hours</span>
            </div>
            <div class="flex justify-between">
              <span class="text-[#5C4033] opacity-75">Medium Priority:</span>
              <span class="text-[#5C4033]">24-48 hours</span>
            </div>
            <div class="flex justify-between">
              <span class="text-[#5C4033] opacity-75">High Priority:</span>
              <span class="text-[#5C4033]">12-24 hours</span>
            </div>
            <div class="flex justify-between">
              <span class="text-[#5C4033] opacity-75">Urgent:</span>
              <span class="text-red-600 font-semibold">2-4 hours</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Requests -->
    <div class="mt-8">
      <div style="background: #FFF5E1;" class="rounded-xl shadow-sm border user-card" style="border-color: #C19A6B;">
        <div class="p-6 border-b" style="border-color: #C19A6B;">
          <h2 class="text-xl font-bold text-[#5C4033]">
            <i class="fa-solid fa-list text-[#C19A6B] mr-2"></i>
            Your Maintenance Requests
          </h2>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead style="background: #e6ddd4;">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Request</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Room</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Priority</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y" style="divide-color: #C19A6B;">
              <?php if(!empty($maintenanceRequests)): ?>
                <?php foreach($maintenanceRequests as $request): ?>
                  <tr>
                    <td class="px-6 py-4">
                      <div>
                        <div class="text-sm font-medium text-[#5C4033]"><?= htmlspecialchars($request['title']) ?></div>
                        <div class="text-xs text-[#5C4033] opacity-75"><?= htmlspecialchars(substr($request['description'], 0, 50)) ?>...</div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-[#5C4033]">#<?= htmlspecialchars($request['room_number']) ?></td>
                    <td class="px-6 py-4">
                      <?php 
                      $priorityColors = [
                        'low' => 'bg-gray-100 text-gray-800',
                        'medium' => 'bg-blue-100 text-blue-800',
                        'high' => 'bg-orange-100 text-orange-800',
                        'urgent' => 'bg-red-100 text-red-800'
                      ];
                      $color = $priorityColors[$request['priority']] ?? 'bg-gray-100 text-gray-800';
                      ?>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $color ?>"><?= ucfirst($request['priority']) ?></span>
                    </td>
                    <td class="px-6 py-4">
                      <?php 
                      $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'in_progress' => 'bg-blue-100 text-blue-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                      ];
                      $statusColor = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                      $statusText = str_replace('_', ' ', ucfirst($request['status']));
                      ?>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColor ?>"><?= $statusText ?></span>
                    </td>
                    <td class="px-6 py-4 text-sm text-[#5C4033]"><?= date('M j, Y', strtotime($request['created_at'])) ?></td>
                    <td class="px-6 py-4">
                      <button onclick="viewRequest(<?= $request['id'] ?>)" class="text-[#C19A6B] hover:text-[#5C4033] font-medium text-sm">
                        <i class="fa-solid fa-eye mr-1"></i> View
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="px-6 py-8 text-center text-[#5C4033] opacity-75">
                    <i class="fa-solid fa-clipboard-list text-4xl mb-4 block text-[#C19A6B] opacity-50"></i>
                    No maintenance requests yet. Submit your first request above.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
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

// Handle maintenance form submission
async function handleMaintenanceSubmit(event) {
    event.preventDefault();
    
    const confirmed = await confirmMaintenanceSubmit();
    if (confirmed) {
        event.target.submit();
    }
    
    return false;
}

// Confirmation dialog for maintenance requests
function confirmMaintenanceSubmit() {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-[#FFF5E1] p-6 rounded-lg max-w-md mx-4" style="border: 2px solid #C19A6B;">
                <h3 class="text-lg font-bold mb-4 text-[#5C4033]">Confirm Maintenance Request</h3>
                <p class="mb-6 text-[#5C4033] opacity-75">Are you sure you want to submit this maintenance request?</p>
                <div class="flex gap-3 justify-end">
                    <button id="cancelBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                    <button id="confirmBtn" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition">Submit</button>
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

// View request details
function viewRequest(requestId) {
    // You can implement a modal or redirect to a detail page
    alert('Request details modal can be implemented here for request ID: ' + requestId);
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