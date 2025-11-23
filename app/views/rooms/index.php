<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$darkModeEnabled = false;

if (!function_exists('resolve_room_picture_paths')) {
  function resolve_room_picture_paths($picturePath, $pictureHash = '') {
    static $cachedRoot = null;
    $result = [
      'has_picture'   => false,
      'absolute_path' => '',
      'web_path'      => '',
      'file_name'     => '',
      'stored_path'   => $picturePath ?? ''
    ];

    if (empty($picturePath)) {
      return $result;
    }

    if ($cachedRoot === null) {
      $candidates = [
        dirname(__DIR__, 2),
        dirname(__DIR__, 3),
        dirname(__DIR__, 4)
      ];

      foreach ($candidates as $candidate) {
        if (is_string($candidate) && is_dir($candidate . DIRECTORY_SEPARATOR . 'public')) {
          $cachedRoot = $candidate;
          break;
        }
      }

      if ($cachedRoot === null) {
        $cachedRoot = dirname(__DIR__, 2);
      }
    }

    $normalized = str_replace('\\', '/', $picturePath);
    $result['stored_path'] = $normalized;

    if (preg_match('#^https?://#i', $normalized)) {
      $result['has_picture'] = true;
      $result['web_path'] = $normalized;
      $parsedPath = parse_url($normalized, PHP_URL_PATH);
      $result['file_name'] = $parsedPath ? basename($parsedPath) : '';
      if ($pictureHash !== '') {
        $separator = strpos($normalized, '?') === false ? '?' : '&';
        $result['web_path'] .= $separator . 'v=' . rawurlencode($pictureHash);
      }
      return $result;
    }

    $isAbsoluteFs = preg_match('#^(?:[a-zA-Z]:/|/)#', $normalized) === 1;
    if ($isAbsoluteFs) {
      $absolutePath = str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    } else {
      $relative = ltrim($normalized, '/');
      $absolutePath = $cachedRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    if (!file_exists($absolutePath)) {
      return $result;
    }

    $result['has_picture'] = true;
    $result['absolute_path'] = $absolutePath;
    $result['file_name'] = basename($absolutePath);

    if ($isAbsoluteFs) {
      $basePath = $cachedRoot;
      $normalizedAbsolute = str_replace('\\', '/', $absolutePath);
      $normalizedBase = rtrim(str_replace('\\', '/', $basePath), '/');
      if (strpos($normalizedAbsolute, $normalizedBase . '/') === 0) {
        $relativeFromBase = substr($normalizedAbsolute, strlen($normalizedBase . '/'));
      } else {
        $relativeFromBase = $result['file_name'];
      }
    } else {
      $relativeFromBase = ltrim($normalized, '/');
    }

    $relativeFromBase = ltrim(str_replace('\\', '/', $relativeFromBase), '/');
    $baseUrl = rtrim(base_url(), '/');
    $webPath = $baseUrl . '/' . $relativeFromBase;
    if ($pictureHash !== '') {
      $webPath .= (strpos($webPath, '?') === false ? '?' : '&') . 'v=' . rawurlencode($pictureHash);
    }

    $result['web_path'] = $webPath;

    return $result;
  }
}
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rooms - Dormitory Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  /* Sidebar collapsed style */
  #sidebar {
    transition: width 0.3s ease, transform 0.3s ease;
    background: #D2B48C; /* warm tan */
  }
  #sidebar.collapsed {
    width: 4rem; /* icons only */
  }
  #sidebar.collapsed nav a span {
    display: none;
  }
  #sidebar.collapsed nav a {
    justify-content: center;
  }
  #sidebar:hover.collapsed {
    width: 16rem;
  }
</style>
</head>
<body class="bg-[#FFF5E1] font-sans flex<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Main content -->
<div class="flex-1 ml-64 transition-all duration-300 main-content" id="mainContent">
  <div class="bg-[#FFF5E1] shadow-md flex items-center justify-between px-4 py-3 md:ml-0">
    <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
      <i class="fa-solid fa-bars"></i>
    </button>
    <h1 class="font-bold text-lg text-[#5C4033]">Rooms</h1>
  </div>

  <div class="w-full mt-4 px-3">

    <!-- Clean Header Section -->
    <div class="mb-6 rounded-lg p-6 shadow-lg border border-[#C19A6B]" style="background: #FFF5E1;">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <i class="fas fa-bed text-2xl mr-3 text-[#C19A6B]"></i>
          <div>
            <h3 class="text-xl font-bold text-[#5C4033]">Dormitory Room Management</h3>
            <p class="text-[#5C4033] opacity-75">Manage all dormitory rooms and their availability</p>
          </div>
        </div>
        <div class="flex gap-3">
          <a href="<?= site_url('admin/rooms'); ?>" class="text-white px-4 py-2 rounded-lg font-semibold transition duration-300 flex items-center hover:bg-[#B07A4B]" style="background: #C19A6B;">
            <i class="fas fa-cogs mr-2"></i>Advanced Management
          </a>
        </div>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (!empty($success)): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        <div class="flex items-center">
          <i class="fas fa-check-circle mr-2"></i>
          <?= htmlspecialchars($success); ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <div class="flex items-center">
          <i class="fas fa-exclamation-circle mr-2"></i>
          <?= htmlspecialchars($error); ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Add Room Form -->
    <div id="addRoomForm" class="mb-6 bg-[#FFF5E1] shadow-lg rounded-2xl p-6 border border-[#C19A6B] hidden">
      <h2 class="text-xl font-bold mb-4 text-[#5C4033]">Add New Room</h2>
      <form method="POST" action="<?=site_url('rooms/create')?>" enctype="multipart/form-data">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <input type="text" name="room_number" placeholder="Room Number" class="border border-[#C19A6B] p-3 rounded-lg w-full" required>
          <input type="number" name="beds" placeholder="Number of Beds" class="border border-[#C19A6B] p-3 rounded-lg w-full" min="1" required>
          <input type="number" name="available" placeholder="Available Slots" class="border border-[#C19A6B] p-3 rounded-lg w-full" min="0" required>
          <input type="number" name="payment" placeholder="Monthly Payment (₱)" step="0.01" class="border border-[#C19A6B] p-3 rounded-lg w-full" min="0" required>
        </div>
        <div class="mt-4">
          <label class="block text-[#5C4033] font-semibold mb-2">
            <i class="fas fa-camera mr-2"></i>Room Picture (optional)
          </label>
          <input type="file" name="picture" accept="image/*" class="border border-[#C19A6B] p-3 rounded-lg w-full">
          <p class="text-sm text-[#5C4033] opacity-70 mt-1">Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</p>
        </div>
        <button type="submit" class="mt-4 bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 flex items-center gap-2">
          <i class="fas fa-save"></i>Add Room
        </button>
      </form>
    </div>

    <div class="flex justify-end mb-6">
      <button id="showAddFormBtn" class="inline-flex items-center gap-2 bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-5 py-2 rounded-full shadow-md transition-all duration-300">
        <i class="fa-solid fa-plus"></i> Add Room
      </button>
    </div>

    <!-- Rooms as Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (empty($rooms)): ?>
        <div class="col-span-full text-center py-12">
          <i class="fas fa-bed text-6xl text-[#C19A6B] mb-4 opacity-50"></i>
          <p class="text-[#5C4033] text-lg">No rooms available yet.</p>
          <p class="text-[#5C4033] text-sm opacity-70 mt-2">Add your first dormitory room to get started.</p>
        </div>
      <?php else: ?>
        <?php foreach($rooms as $room): ?>
          <?php
            $pictureMeta = resolve_room_picture_paths($room['picture'] ?? '', $room['picture_hash'] ?? '');
            $hasPicture = $pictureMeta['has_picture'];
            $pictureUrl = $pictureMeta['web_path'];
          ?>
          <div class="bg-[#FFF5E1] shadow-lg rounded-2xl overflow-hidden border border-[#C19A6B] flex flex-col" id="room-<?= $room['id'] ?>">
            
            <!-- Room Picture -->
            <?php if ($hasPicture): ?>
              <div class="relative h-48 overflow-hidden">
                <img src="<?= htmlspecialchars($pictureUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Room #<?= $room['room_number'] ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                <div class="absolute top-2 right-2 bg-[#C19A6B] text-white px-2 py-1 rounded-full text-xs font-semibold">
                  Room #<?= $room['room_number'] ?>
                </div>
              </div>
            <?php else: ?>
              <div class="h-48 bg-gradient-to-br from-[#E5D3B3] to-[#C19A6B] flex items-center justify-center relative">
                <div class="text-center text-white">
                  <i class="fas fa-bed text-4xl mb-2 opacity-75"></i>
                  <p class="font-semibold">Room #<?= $room['room_number'] ?></p>
                  <p class="text-xs opacity-90">No image available</p>
                </div>
              </div>
            <?php endif; ?>
            
            <div class="p-6 flex-1 flex flex-col justify-between">
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <h2 class="text-xl font-bold text-[#5C4033]">Room #<?= $room['room_number'] ?></h2>
                  <span class="text-xs px-2 py-1 rounded-full text-white" style="background: #C19A6B;">
                    <i class="fas fa-home mr-1"></i>Dormitory
                  </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                  <div class="flex items-center text-[#5C4033]">
                    <i class="fas fa-bed mr-2 text-[#C19A6B]"></i>
                    <span><strong><?= $room['beds'] ?></strong> beds</span>
                  </div>
                  <div class="flex items-center text-[#5C4033]">
                    <i class="fas fa-users mr-2 text-green-500"></i>
                    <span class="<?= $room['available'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                      <strong><?= $room['available'] ?></strong> <?= $room['available'] > 0 ? 'available' : 'full' ?>
                    </span>
                  </div>
                </div>

                <div class="flex items-center justify-center bg-green-50 border border-green-200 rounded-lg p-3">
                  <i class="fas fa-peso-sign mr-2 text-green-600"></i>
                  <span class="text-green-600 font-bold text-lg">₱<?= number_format($room['payment'], 2) ?>/month</span>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Toggle sidebar for mobile
document.getElementById('menuBtn')?.addEventListener('click', () => {
  const sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('collapsed');
});

// Show add room form
document.getElementById('showAddFormBtn')?.addEventListener('click', () => {
  const form = document.getElementById('addRoomForm');
  form.classList.toggle('hidden');
});

// Handle add room form submission
document.addEventListener('DOMContentLoaded', function() {
  const addRoomForm = document.querySelector('#addRoomForm form');
  if (addRoomForm) {
    addRoomForm.addEventListener('submit', function(e) {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding Room...';
        submitButton.disabled = true;
        
        // Re-enable button after timeout as fallback
        setTimeout(() => {
          submitButton.innerHTML = originalText;
          submitButton.disabled = false;
        }, 10000);
      }
    });
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