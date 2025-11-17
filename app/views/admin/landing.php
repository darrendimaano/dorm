<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TEST</title>
    <!-- ALL EXTERNAL SCRIPTS DISABLED FOR TESTING -->
</head>
<body class="bg-white font-sans flex">

<!-- Sidebar -->
<div class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-50 shadow-lg" style="background: #D2B48C;">
  <h2 class="text-2xl font-bold mb-8">🏨 CLEAN ADMIN</h2>
  <div class="flex flex-col gap-4" id="clean-navigation">
    <a href="<?= site_url('dashboard') ?>" class="nav-item dashboard">Dashboard</a>
    <a href="<?=site_url('users')?>" class="nav-item users">Users</a>
    <a href="<?=site_url('rooms')?>" class="nav-item rooms">Rooms</a>
    <a href="<?=site_url('admin/reservations')?>" class="nav-item bookings">Booking System</a>
    <a href="<?=site_url('admin/reports')?>" class="nav-item reports">Tenant Reports</a>
    <a href="<?=site_url('settings')?>" class="nav-item settings">Settings</a>
    <a href="<?=site_url('auth/logout')?>" class="nav-item logout">Logout</a>
  </div>
</div>

<style>
.nav-item {
  display: block;
  padding: 12px 16px;
  color: #5C4033;
  text-decoration: none;
  border-radius: 8px;
  transition: background-color 0.2s;
}
.nav-item:hover {
  background-color: #C19A6B;
  color: white;
}
.nav-item.dashboard {
  background-color: #C19A6B;
  color: white;
  font-weight: bold;
}
</style>

<!-- Main content -->
<div class="flex-1 ml-64 transition-all duration-300">
  <div style="background: #FFF5E1;" class="shadow-md flex items-center justify-between px-4 py-3 md:ml-0">
    <h1 class="font-bold text-lg text-[#5C4033]">Admin Dashboard</h1>
    <div class="flex items-center space-x-4">
      <span class="text-[#5C4033]">Welcome, Administrator</span>
      <span class="text-sm text-[#5C4033] opacity-70"><?= date('F d, Y'); ?></span>
    </div>
  </div>

  <div class="w-full mt-4 px-3">
    <!-- Success/Error Messages -->
    <?php if (!empty($success)): ?>
        <div style="background: #e6f7e6; border-color: #C19A6B;" class="border text-green-700 px-4 py-3 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($success); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: #ffe6e6; border-color: #C19A6B;" class="border text-red-700 px-4 py-3 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($error); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Welcome Section -->
    <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-6 mb-6 border" style="border-color: #C19A6B;">
      <h2 class="text-3xl font-bold text-[#5C4033] mb-4">
        <i class="fas fa-tachometer-alt text-[#C19A6B] mr-3"></i>
        Dashboard Overview
      </h2>
      <p class="text-[#5C4033] opacity-80">
        Welcome to the dormitory management system. Use the sidebar navigation to manage different aspects of the system.
      </p>
      

    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4 mb-6">
      <!-- Pending Reservations -->
      <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-4 border hover:shadow-xl transition" style="border-color: #C19A6B;">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-[#5C4033] uppercase tracking-wider opacity-70">Pending Reservations</p>
            <p class="text-xl font-bold text-[#5C4033]"><?= count($pendingReservations ?? []) ?></p>
          </div>
          <div class="bg-yellow-600 rounded-md p-2">
            <i class="fas fa-clock text-white text-lg"></i>
          </div>
        </div>
      </div>

      <!-- Total Reservations -->
      <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-6 border hover:shadow-xl transition" style="border-color: #C19A6B;">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-[#5C4033] uppercase tracking-wider opacity-70">Total Reservations</p>
            <p class="text-3xl font-bold text-[#5C4033]"><?= count($allReservations ?? []) ?></p>
          </div>
          <div class="rounded-md p-3" style="background: #C19A6B;">
            <i class="fas fa-bed text-white text-xl"></i>
          </div>
        </div>
      </div>

      <!-- Available Rooms -->
      <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-6 border hover:shadow-xl transition" style="border-color: #C19A6B;">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-[#5C4033] uppercase tracking-wider opacity-70">Available Rooms</p>
            <p class="text-3xl font-bold text-[#5C4033]">--</p>
          </div>
          <div class="bg-green-600 rounded-md p-3">
            <i class="fas fa-door-open text-white text-xl"></i>
          </div>
        </div>
      </div>

      <!-- Messages -->
      <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-6 border hover:shadow-xl transition" style="border-color: #C19A6B;">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-[#5C4033] uppercase tracking-wider opacity-70">New Messages</p>
            <p class="text-3xl font-bold text-[#5C4033]">--</p>
          </div>
          <div class="bg-purple-600 rounded-md p-3">
            <i class="fas fa-message text-white text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-6 mb-6 border" style="border-color: #C19A6B;">
      <h3 class="text-2xl font-bold text-[#5C4033] mb-6">
        <i class="fas fa-rocket text-[#C19A6B] mr-2"></i>
        Quick Actions
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Reservation Management -->
        <a href="<?= site_url('admin/reservations') ?>" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition border border-[#C19A6B] block">
          <div class="text-center">
            <div class="bg-yellow-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-calendar-check text-yellow-600 text-2xl"></i>
            </div>
            <h4 class="text-xl font-semibold text-[#5C4033] mb-2">Booking Management</h4>
            <p class="text-[#5C4033] opacity-70 mb-4">Approve or reject booking requests from students.</p>
            <span class="inline-block bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700 transition">
              Manage Bookings
            </span>
          </div>
        </a>

        <!-- Manage Rooms -->
        <a href="<?= site_url('rooms') ?>" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition border border-[#C19A6B] block">
          <div class="text-center">
            <div class="bg-green-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-bed text-green-600 text-2xl"></i>
            </div>
            <h4 class="text-xl font-semibold text-[#5C4033] mb-2">Manage Rooms</h4>
            <p class="text-[#5C4033] opacity-70 mb-4">Add new rooms, update room details, and manage availability.</p>
            <span class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
              Go to Rooms
            </span>
          </div>
        </a>

        <!-- Manage Users -->
        <a href="<?= site_url('users') ?>" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition border border-[#C19A6B] block">
          <div class="text-center">
            <div class="rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4" style="background: #D2B48C;">
              <i class="fas fa-users text-[#5C4033] text-2xl"></i>
            </div>
            <h4 class="text-xl font-semibold text-[#5C4033] mb-2">Manage Users</h4>
            <p class="text-[#5C4033] opacity-70 mb-4">View and manage user accounts in the system.</p>
            <span class="text-white px-6 py-2 rounded-lg hover:bg-[#B07A4B] transition" style="background: #C19A6B;">
              Go to Users
            </span>
          </div>
        </a>

        <!-- System Settings -->
        <a href="<?= site_url('settings') ?>" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition border border-[#C19A6B] block">
          <div class="text-center">
            <div class="bg-purple-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-cog text-purple-600 text-2xl"></i>
            </div>
            <h4 class="text-xl font-semibold text-[#5C4033] mb-2">System Settings</h4>
            <p class="text-[#5C4033] opacity-70 mb-4">Configure system settings and maintenance options.</p>
            <span class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
              Go to Settings
            </span>
          </div>
        </a>

        <!-- Announcements -->
        <a href="<?= site_url('announcements') ?>" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition border border-[#C19A6B] block">
          <div class="text-center">
            <div class="bg-red-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-bullhorn text-red-600 text-2xl"></i>
            </div>
            <h4 class="text-xl font-semibold text-[#5C4033] mb-2">Announcements</h4>
            <p class="text-[#5C4033] opacity-70 mb-4">Create and manage system-wide announcements.</p>
            <span class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
              Go to Announcements
            </span>
          </div>
        </a>

        <!-- Reports -->
        <a href="<?= site_url('reports') ?>" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition border border-[#C19A6B] block">
          <div class="text-center">
            <div class="bg-indigo-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-chart-bar text-indigo-600 text-2xl"></i>
            </div>
            <h4 class="text-xl font-semibold text-[#5C4033] mb-2">Reports</h4>
            <p class="text-[#5C4033] opacity-70 mb-4">Generate and view various system reports.</p>
            <span class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
              View Reports
            </span>
          </div>
        </a>
      </div>
    </div>

    <!-- Recent Pending Reservations -->
    <?php if (!empty($pendingReservations)): ?>
    <div style="background: #FFF5E1;" class="rounded-lg shadow-lg border" style="border-color: #C19A6B;">
        <div class="px-6 py-4 border-b" style="border-color: #C19A6B;">
            <h3 class="text-lg font-medium text-[#5C4033]">
                <i class="fas fa-clock text-[#C19A6B] mr-2"></i>Recent Pending Reservations
            </h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y" style="border-color: #C19A6B;">
                    <thead style="background: #D2B48C;">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Room</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y" style="border-color: #C19A6B;">
                        <?php foreach (array_slice($pendingReservations, 0, 5) as $reservation): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033]">
                                <?= htmlspecialchars($reservation['fname'] ?? 'Unknown') ?> <?= htmlspecialchars($reservation['lname'] ?? '') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033]">
                                Room #<?= htmlspecialchars($reservation['room_number'] ?? 'Unknown') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033] opacity-70">
                                <?= date('M j, Y', strtotime($reservation['reserved_at'] ?? 'now')) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <form method="POST" action="<?= site_url('admin/reservations/approve/'.$reservation['id']) ?>" class="inline">
                                    <button type="submit" class="text-green-600 hover:text-green-900 px-3 py-1 bg-green-100 rounded">
                                        <i class="fa-solid fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" action="<?= site_url('admin/reservations/reject/'.$reservation['id']) ?>" class="inline">
                                    <button type="submit" class="text-red-600 hover:text-red-900 px-3 py-1 bg-red-100 rounded">
                                        <i class="fa-solid fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="<?= site_url('admin/reservations') ?>" class="text-[#C19A6B] hover:text-[#5C4033] font-medium">
                    View All Bookings <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-8 text-center border" style="border-color: #C19A6B;">
        <i class="fa-solid fa-inbox text-4xl text-[#C19A6B] mb-4 opacity-50"></i>
        <h3 class="text-lg font-medium text-[#5C4033] mb-2">No Pending Reservations</h3>
        <p class="text-[#5C4033] opacity-70">All reservations have been processed.</p>
    </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>