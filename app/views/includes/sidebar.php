<!-- Sidebar -->
<div id="sidebar" class="bg-brown-700 text-white w-64 min-h-screen p-6 fixed left-0 top-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50">
  <h2 class="text-2xl font-bold mb-8">Dormitory Admin</h2>
  <nav class="flex flex-col gap-4">
    <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-brown-600 transition">
      <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>
    <a href="<?= site_url('users') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-brown-600 transition">
      <i class="fa-solid fa-user"></i> Users
    </a>
    <a href="<?= site_url('rooms') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-brown-600 transition">
      <i class="fa-solid fa-bed"></i> Rooms
    </a>
    <a href="<?= site_url('settings') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-brown-600 transition">
      <i class="fa-solid fa-cog"></i> Settings
    </a>
    <a href="<?= site_url('auth/logout') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-red-500 transition mt-6">
      <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
  </nav>
</div>

<!-- Sidebar Toggle Script -->
<script>
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menuBtn');
menuBtn.addEventListener('click', () => sidebar.classList.toggle('-translate-x-full'));
</script>
