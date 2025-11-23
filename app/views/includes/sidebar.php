<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/DatabaseConfig.php';

$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
if (strpos($currentPath, 'index.php/') === 0) {
    $currentPath = substr($currentPath, strlen('index.php/'));
}

$adminName = $_SESSION['admin_name'] ?? 'Dormitory Admin';

$unreadMessagesCount = 0;
try {
  $dbConfig = DatabaseConfig::getInstance();
  $pdo = $dbConfig->getConnection();
  $stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'");
  $unreadMessagesCount = (int) $stmt->fetchColumn();
} catch (Exception $e) {
  $unreadMessagesCount = 0;
}

$navItems = [
    [
        'label' => 'Dashboard',
        'icon'  => 'fa-solid fa-home',
        'url'   => site_url('dashboard'),
        'match' => ['dashboard', 'admin/landing']
    ],
    [
        'label' => 'Users',
        'icon'  => 'fa-solid fa-user',
        'url'   => site_url('users'),
        'match' => ['users']
    ],
    [
        'label' => 'Rooms',
        'icon'  => 'fa-solid fa-bed',
        'url'   => site_url('rooms'),
        'match' => ['rooms', 'admin/rooms']
    ],
    [
      'label' => 'Reservations',
      'icon'  => 'fa-solid fa-list-check',
      'url'   => site_url('admin/reservations'),
      'match' => ['admin/reservations']
    ],
    [
      'label' => 'Announcements',
      'icon'  => 'fa-solid fa-bullhorn',
      'url'   => site_url('admin/announcements'),
      'match' => ['admin/announcements']
    ],
    [
      'label' => 'Tenant Reports',
      'icon'  => 'fa-solid fa-chart-line',
      'url'   => site_url('admin/reports'),
      'match' => ['admin/reports', 'admin/reports/payment-history']
    ],
    [
      'label' => 'Messages',
      'icon'  => 'fa-solid fa-envelope',
      'url'   => site_url('admin/messages'),
      'match' => ['admin/messages']
    ],
    [
      'label' => 'Settings',
      'icon'  => 'fa-solid fa-cog',
      'url'   => site_url('settings'),
      'match' => ['settings']
    ],
];

foreach ($navItems as &$item) {
  if ($item['label'] === 'Messages') {
    $item['badge'] = $unreadMessagesCount;
    $item['isMessagesNav'] = true;
    break;
  }
}
unset($item);

$isActive = function(array $patterns, string $currentPath): bool {
    foreach ($patterns as $pattern) {
        $pattern = trim($pattern, '/');
        if ($pattern === '') {
            if ($currentPath === '') {
                return true;
            }
            continue;
        }

        if ($currentPath === $pattern || strpos($currentPath, $pattern . '/') === 0) {
            return true;
        }
    }

    return false;
};
?>

<style>
  #sidebar nav a i {
    font-family: "Font Awesome 6 Free";
    font-weight: 900 !important;
    font-style: normal;
    color: inherit;
  }
</style>

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50 shadow-lg" style="background: #D2B48C;">
  <div class="flex items-center gap-3 mb-8">
    <div class="bg-[#C19A6B] p-2 rounded-lg">
      <i class="fa-solid fa-graduation-cap text-2xl text-white"></i>
    </div>
    <div class="sidebar-text">
      <h2 class="text-lg font-bold leading-snug"><?= htmlspecialchars($adminName) ?></h2>
      <p class="text-sm text-[#5C4033] opacity-75">Admin Portal</p>
    </div>
  </div>

  <nav class="flex flex-col gap-2">
    <?php foreach ($navItems as $item): ?>
      <?php
        $active = $isActive($item['match'], $currentPath);
        $baseClasses = 'flex items-center gap-3 px-4 py-3 rounded-lg transition hover:bg-[#C19A6B] hover:text-white text-[#5C4033]';
        $activeClasses = $active ? ' bg-[#C19A6B] text-white shadow-md' : '';
        $labelClasses = 'font-medium transition-colors';
        if ($active) {
            $labelClasses .= ' font-semibold';
        }
      ?>
      <?php
        $isMessagesNav = $item['isMessagesNav'] ?? false;
        $badgeCount = isset($item['badge']) ? (int) $item['badge'] : 0;
      ?>
      <a href="<?= $item['url'] ?>" class="<?= $baseClasses . $activeClasses ?>"<?= $isMessagesNav ? ' id="sidebar-messages-link"' : '' ?> data-label="<?= htmlspecialchars($item['label']) ?>">
        <i class="<?= $item['icon'] ?> text-lg"></i>
        <span class="<?= $labelClasses ?>"><?= htmlspecialchars($item['label']) ?></span>
        <?php if ($isMessagesNav): ?>
          <span id="sidebar-messages-badge" class="ml-auto inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 rounded-full bg-red-500 text-white text-xs font-semibold<?= $badgeCount === 0 ? ' hidden' : '' ?>" data-count="<?= $badgeCount ?>">
            <?= $badgeCount ?>
          </span>
        <?php elseif (!empty($item['badge'])): ?>
          <span class="ml-auto inline-flex items-center justify-center min-w-[1.75rem] h-7 px-2 rounded-full bg-red-500 text-white text-xs font-semibold">
            <?= (int) $item['badge'] ?>
          </span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>

    <hr class="border-[#5C4033] border-opacity-20 my-4">

    <div class="px-4 py-2 text-xs text-[#5C4033] opacity-75 flex items-center gap-2">
      <i class="fa-solid fa-phone"></i>
      <span>Contact: 09517394938</span>
    </div>

    <a href="#" onclick="confirmLogout()" class="flex items-center gap-3 px-4 py-3 rounded-lg transition hover:bg-red-400 hover:text-white">
      <i class="fa-solid fa-right-from-bracket text-lg"></i>
      <span>Logout</span>
    </a>
  </nav>
</div>

<!-- Sidebar Toggle Script -->
<script>
(function() {
  const sidebar = document.getElementById('sidebar');
  const menuBtn = document.getElementById('menuBtn');
  const sidebarToggle = document.getElementById('sidebarToggle');

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function() {
      sidebar.classList.toggle('-translate-x-full');
    });
  }

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('-translate-x-full');
    });
  }

  if (sidebar) {
    const links = sidebar.querySelectorAll('nav a');
    links.forEach(function(link) {
      link.addEventListener('click', function() {
        if (window.innerWidth < 768) {
          sidebar.classList.add('-translate-x-full');
        }
      });
    });
  }

  let logoutEscHandler = null;

  window.closeLogoutModal = function() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
      modal.remove();
    }

    if (logoutEscHandler) {
      document.removeEventListener('keydown', logoutEscHandler);
      logoutEscHandler = null;
    }
  };

  window.proceedLogout = function() {
    window.location.href = '<?= site_url('auth/logout') ?>';
  };

  window.confirmLogout = function() {
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

    modal.addEventListener('click', function(event) {
      if (event.target === modal) {
        window.closeLogoutModal();
      }
    });

    logoutEscHandler = function(event) {
        if (event.key === 'Escape') {
          window.closeLogoutModal();
        }
      };

    document.addEventListener('keydown', logoutEscHandler);
    document.body.appendChild(modal);
  };
})();
</script>
