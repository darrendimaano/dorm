<!DOCTYPE html>
<html lang="en">
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
<body class="bg-white font-sans flex">

<!-- Sidebar -->
<div id="sidebar" class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-50 shadow-lg">
  <h2 class="text-2xl font-bold mb-8">🏨</h2>
  <nav class="flex flex-col gap-4">
    <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
    </a>
    <a href="<?=site_url('users')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-user"></i> <span>Users</span>
    </a>
 <a href="<?=site_url('admin/landing')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
    <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
</a>

    <a href="<?=site_url('rooms')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-bed"></i> <span>Rooms</span>
    </a>
    <a href="<?=site_url('settings')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-cog"></i> <span>Settings</span>
    </a>
    <a href="<?=site_url('auth/logout')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-red-300 transition mt-6">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

<!-- Main content -->
<div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
  <div class="bg-[#FFF5E1] shadow-md flex items-center justify-between px-4 py-3 md:ml-0">
    <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
      <i class="fa-solid fa-bars"></i>
    </button>
    <h1 class="font-bold text-lg text-[#5C4033]">Rooms</h1>
  </div>

  <div class="max-w-6xl mx-auto mt-6 px-4">

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
          <div class="bg-[#FFF5E1] shadow-lg rounded-2xl overflow-hidden border border-[#C19A6B] flex flex-col" id="room-<?= $room['id'] ?>">
            
            <!-- Room Picture -->
            <?php if (!empty($room['picture']) && file_exists($room['picture'])): ?>
              <div class="relative h-48 overflow-hidden">
                <img src="<?= site_url($room['picture']) ?>" alt="Room #<?= $room['room_number'] ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
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
            <!-- Room Display -->
            <div class="room-view">
              <div class="space-y-3 mb-4">
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

            <!-- Update Form -->
            <div class="room-edit hidden">
              <form method="POST" action="<?=site_url('rooms/update/'.$room['id'])?>" enctype="multipart/form-data">
                <input type="hidden" name="existing_picture" value="<?= htmlspecialchars($room['picture'] ?? '') ?>">
                <div class="grid grid-cols-1 gap-3">
                  <input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" placeholder="Room Number" class="border border-[#C19A6B] p-3 rounded-lg w-full" required>
                  <input type="number" name="beds" value="<?= htmlspecialchars($room['beds']) ?>" placeholder="Beds" class="border border-[#C19A6B] p-3 rounded-lg w-full" min="1" required>
                  <input type="number" name="available" value="<?= htmlspecialchars($room['available']) ?>" placeholder="Available" class="border border-[#C19A6B] p-3 rounded-lg w-full" min="0" required>
                  <input type="number" name="payment" value="<?= htmlspecialchars($room['payment']) ?>" placeholder="Payment" step="0.01" class="border border-[#C19A6B] p-3 rounded-lg w-full" min="0" required>
                  
                  <div>
                    <label class="block text-[#5C4033] font-semibold mb-1">
                      <i class="fas fa-camera mr-2"></i>Update Picture (optional)
                    </label>
                    <input type="file" name="picture" accept="image/*" class="border border-[#C19A6B] p-3 rounded-lg w-full">
                    <?php if (!empty($room['picture'])): ?>
                      <p class="text-xs text-[#5C4033] opacity-70 mt-1">Current: <?= basename($room['picture']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="flex justify-between mt-4">
                  <button type="submit" class="bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition duration-200">
                    <i class="fa-solid fa-save"></i> Save Changes
                  </button>
                  <button type="button" class="cancelEdit bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg shadow">Cancel</button>
                </div>
              </form>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between mt-4 room-actions">
              <button class="editRoom bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition duration-200">
                <i class="fa-solid fa-pen-to-square"></i> Update
              </button>
              <form method="POST" action="<?=site_url('rooms/delete/'.$room['id'])?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete Room #<?= htmlspecialchars($room['room_number']) ?>?')">
                <button type="submit" class="bg-red-400 hover:bg-red-500 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition duration-200">
                  <i class="fa-solid fa-trash"></i> Delete
                </button>
              </form>
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

// Edit room functionality
document.querySelectorAll('.editRoom').forEach(button => {
  button.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    
    // Find the closest room container
    const roomContainer = e.target.closest('.bg-white.rounded-xl.shadow-lg');
    if (!roomContainer) return;
    
    const roomView = roomContainer.querySelector('.room-view');
    const roomEdit = roomContainer.querySelector('.room-edit');
    const roomActions = roomContainer.querySelector('.room-actions');
    
    if (roomView) roomView.classList.add('hidden');
    if (roomEdit) roomEdit.classList.remove('hidden');
    if (roomActions) roomActions.classList.add('hidden');
  });
});

// Cancel edit
document.querySelectorAll('.cancelEdit').forEach(button => {
  button.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    
    // Find the closest room container
    const roomContainer = e.target.closest('.bg-white.rounded-xl.shadow-lg');
    if (!roomContainer) return;
    
    const roomView = roomContainer.querySelector('.room-view');
    const roomEdit = roomContainer.querySelector('.room-edit');
    const roomActions = roomContainer.querySelector('.room-actions');
    
    if (roomView) roomView.classList.remove('hidden');
    if (roomEdit) roomEdit.classList.add('hidden');
    if (roomActions) roomActions.classList.remove('hidden');
  });
});

// Handle form submissions for room updates
document.querySelectorAll('.room-edit form').forEach(form => {
  form.addEventListener('submit', function(e) {
    const submitButton = this.querySelector('button[type="submit"]');
    if (submitButton) {
      const originalText = submitButton.innerHTML;
      submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
      submitButton.disabled = true;
      
      // Re-enable button after timeout as fallback
      setTimeout(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
      }, 10000);
    }
  });
});
</script>

</body>
</html>