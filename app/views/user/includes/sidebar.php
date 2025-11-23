<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
if (strpos($currentPath, 'index.php/') === 0) {
    $currentPath = substr($currentPath, strlen('index.php/'));
}

$userName = $_SESSION['user_name'] ?? 'Tenant';

$navItems = [
    [
        'label' => 'Dashboard',
        'icon'  => 'fa-solid fa-home',
        'url'   => site_url('user_landing'),
        'match' => ['user_landing']
    ],
    [
        'label' => 'My Reservations',
        'icon'  => 'fa-solid fa-list-check',
        'url'   => site_url('user/reservations'),
        'match' => ['user/reservations']
    ],
    [
        'label' => 'Payment History',
        'icon'  => 'fa-solid fa-credit-card',
        'url'   => site_url('user/payments'),
        'match' => ['user/payments', 'user/payments/receipt']
    ],
    [
        'label' => 'Maintenance',
        'icon'  => 'fa-solid fa-wrench',
        'url'   => site_url('user/maintenance'),
        'match' => ['user/maintenance']
    ],
    [
        'label' => 'Announcements',
        'icon'  => 'fa-solid fa-bullhorn',
        'url'   => site_url('user/announcements'),
        'match' => ['user/announcements']
    ],
    [
        'label' => 'Profile',
        'icon'  => 'fa-solid fa-user',
        'url'   => site_url('user/profile'),
        'match' => ['user/profile']
    ],
    [
        'label' => 'Contact Admin',
        'icon'  => 'fa-solid fa-envelope',
        'url'   => site_url('user/contact'),
        'match' => ['user/contact']
    ],
];

$contactNumber = '09517394938';

$matchesRoute = function(array $patterns, string $currentPath): bool {
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

<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-40 shadow-lg transition-transform duration-300" style="background: #D2B48C;">
  <div class="flex items-center gap-3 mb-8">
    <div class="bg-[#C19A6B] p-2 rounded-lg">
      <i class="fa-solid fa-graduation-cap text-2xl text-white"></i>
    </div>
    <div class="sidebar-text">
      <h2 class="text-lg font-bold"><?= htmlspecialchars($userName) ?></h2>
      <p class="text-sm text-[#5C4033] opacity-75">Tenant Portal</p>
    </div>
  </div>

  <nav class="flex flex-col gap-2">
    <?php foreach ($navItems as $item): ?>
      <?php
        $active = $matchesRoute($item['match'], $currentPath);
        $baseClasses = 'flex items-center gap-3 px-4 py-3 rounded-lg transition hover:bg-[#C19A6B] hover:text-white text-[#5C4033]';
        $activeClasses = $active ? ' bg-[#C19A6B] text-white font-semibold shadow-md' : '';
      ?>
      <a href="<?= $item['url'] ?>" class="<?= $baseClasses . $activeClasses ?>">
        <i class="<?= $item['icon'] ?>"></i>
        <span><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>

    <hr class="border-[#5C4033] border-opacity-20 my-4">

    <div class="px-4 py-2 text-xs text-[#5C4033] opacity-75">
      <i class="fa-solid fa-phone mr-2"></i>
      <span class="sidebar-text">Contact: <?= htmlspecialchars($contactNumber) ?></span>
    </div>

    <a href="#" onclick="confirmLogout()" class="flex items-center gap-3 px-4 py-3 rounded-lg transition hover:bg-red-400 hover:text-white">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Logout</span>
    </a>
  </nav>
</div>
