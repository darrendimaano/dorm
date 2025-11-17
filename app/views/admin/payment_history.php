<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-[#FFF5E1] font-sans">

<!-- Sidebar Navigation -->
<div class="flex">
    <!-- Sidebar -->
<div class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-50 shadow-lg" style="background: #D2B48C;">
  <h2 class="text-2xl font-bold mb-8">🏨 Dormitory Admin</h2>
  <nav class="flex flex-col gap-4">
    <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
    </a>
    <a href="<?=site_url('users')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-user"></i> <span>Users</span>
    </a>
    <a href="<?=site_url('rooms')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-bed"></i> <span>Rooms</span>
    </a>
    <a href="<?=site_url('admin/reservations')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
    </a>
    <a href="<?=site_url('admin/reports')?>" class="flex items-center gap-2 px-4 py-2 rounded bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-file-chart-line"></i> <span>Tenant Reports</span>
    </a>
    <a href="<?=site_url('settings')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-cog"></i> <span>Settings</span>
    </a>
    <a href="<?=site_url('auth/logout')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-red-300 transition mt-6">
      <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
    </a>
  </nav>
</div>

    <!-- Main Content -->
    <div class="ml-64 flex-1 p-6">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-[#5C4033] mb-2">Payment History</h1>
                    <p class="text-[#5C4033] opacity-75">Complete record of all payments received</p>
                </div>
                <div class="flex items-center gap-4 no-print">
                    <div class="text-right">
                        <p class="text-sm text-[#5C4033] opacity-75">Last updated</p>
                        <p class="text-[#5C4033] font-semibold" id="currentTime"><?= date('M j, Y g:i A') ?></p>
                    </div>
                    <a href="<?= site_url('admin/reports') ?>" class="px-4 py-2 border border-[#C19A6B] text-[#5C4033] rounded-lg hover:bg-[#C19A6B] hover:text-white transition-all duration-200">
                        <i class="fa-solid fa-arrow-left mr-2"></i>Back to Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <?php if(isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
                <i class="fa-solid fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-lg border border-[#E5D3B3]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#5C4033] text-sm opacity-75">Total Payments</p>
                        <p class="text-3xl font-bold text-[#C19A6B]"><?= number_format($summary['total_payments']) ?></p>
                    </div>
                    <i class="fa-solid fa-receipt text-[#C19A6B] text-3xl"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-lg border border-[#E5D3B3]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#5C4033] text-sm opacity-75">Total Collected</p>
                        <p class="text-2xl font-bold text-green-600">₱<?= number_format($summary['total_collected'], 2) ?></p>
                    </div>
                    <i class="fa-solid fa-peso-sign text-green-500 text-3xl"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-lg border border-[#E5D3B3]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#5C4033] text-sm opacity-75">Average Payment</p>
                        <p class="text-2xl font-bold text-blue-600">₱<?= number_format($summary['average_payment'], 2) ?></p>
                    </div>
                    <i class="fa-solid fa-calculator text-blue-500 text-3xl"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-lg border border-[#E5D3B3]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[#5C4033] text-sm opacity-75">Paying Tenants</p>
                        <p class="text-3xl font-bold text-purple-600"><?= number_format($summary['unique_tenants']) ?></p>
                    </div>
                    <i class="fa-solid fa-users text-purple-500 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-4 mb-8 no-print">
            <a href="<?= site_url('admin/reports/payment-history/download-csv') ?>" 
               class="flex items-center gap-2 px-6 py-3 bg-[#C19A6B] text-white rounded-xl hover:bg-[#A67C52] transition-all duration-200 shadow-lg font-semibold">
                <i class="fa-solid fa-download"></i>
                <span>Download CSV</span>
            </a>
            
            <a href="<?= site_url('admin/reports/payment-history/download-pdf') ?>" 
               class="flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all duration-200 shadow-lg font-semibold">
                <i class="fa-solid fa-file-pdf"></i>
                <span>Download PDF</span>
            </a>
            
            <button onclick="window.print()" 
                    class="flex items-center gap-2 px-6 py-3 border-2 border-[#C19A6B] text-[#5C4033] rounded-xl hover:bg-[#C19A6B] hover:text-white transition-all duration-200 font-semibold">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>

        <!-- Payment History Table -->
        <div class="bg-white rounded-xl shadow-lg border border-[#E5D3B3] overflow-hidden">
            <div class="px-6 py-4" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-history"></i>
                        Payment Records
                    </h2>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <input type="text" 
                                   id="searchInput" 
                                   placeholder="Search tenant, room, or amount..." 
                                   class="px-4 py-2 pl-10 pr-4 rounded-lg border border-[#E5D3B3] focus:border-white focus:ring-2 focus:ring-white focus:ring-opacity-50 text-[#5C4033] w-64"
                                   onkeyup="filterPayments()">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-[#5C4033] opacity-50"></i>
                        </div>
                        <button onclick="clearSearch()" 
                                class="px-3 py-2 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-all duration-200 text-sm">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($paymentHistory)): ?>
            <div class="overflow-x-auto">
                <table class="w-full" id="paymentTable">
                    <thead style="background: #FFF5E1; color: #5C4033;" class="border-b border-[#E5D3B3]">
                        <tr>
                            <th class="py-4 px-4 text-left font-semibold">Date</th>
                            <th class="py-4 px-4 text-left font-semibold">Tenant</th>
                            <th class="py-4 px-4 text-left font-semibold">Room</th>
                            <th class="py-4 px-4 text-left font-semibold">Amount</th>
                            <th class="py-4 px-4 text-left font-semibold">Method</th>
                            <th class="py-4 px-4 text-left font-semibold no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-[#FFF5E1]" id="paymentTableBody">
                        <?php foreach($paymentHistory as $payment): ?>
                        <tr class="payment-row border-b border-[#E5D3B3] hover:bg-[#F5F0E8] transition-all duration-200">
                            <td class="py-4 px-4">
                                <div>
                                    <p class="font-semibold text-[#5C4033]"><?= date('M j, Y', strtotime($payment['payment_date'])) ?></p>
                                    <p class="text-sm text-[#5C4033] opacity-75"><?= date('g:i A', strtotime($payment['created_at'])) ?></p>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div>
                                    <p class="font-semibold text-[#5C4033] tenant-name"><?= htmlspecialchars($payment['fname'] . ' ' . $payment['lname']) ?></p>
                                    <p class="text-sm text-[#5C4033] opacity-75"><?= htmlspecialchars($payment['email']) ?></p>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-door-open text-[#C19A6B]"></i>
                                    <div>
                                        <p class="font-semibold text-[#5C4033] room-number">Room #<?= htmlspecialchars($payment['room_number']) ?></p>
                                        <p class="text-sm text-[#5C4033] opacity-75">Rate: ₱<?= number_format($payment['room_rate'], 2) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-xl text-green-600 payment-amount">₱<?= number_format($payment['amount'], 2) ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-[#E5D3B3] text-[#5C4033] payment-method">
                                    <?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 no-print">
                                <?php if($payment['notes']): ?>
                                <button onclick="showNotes('<?= htmlspecialchars($payment['notes']) ?>')" 
                                        class="px-3 py-1 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200 text-sm">
                                    <i class="fa-solid fa-note-sticky mr-1"></i>
                                    Notes
                                </button>
                                <?php else: ?>
                                <span class="text-[#5C4033] opacity-50 text-sm">No notes</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- No Results Found Message -->
                <div id="noResults" class="hidden p-8 text-center text-[#5C4033] opacity-60">
                    <i class="fa-solid fa-search text-4xl mb-3"></i>
                    <h3 class="text-lg font-semibold mb-2">No matching payments found</h3>
                    <p class="text-sm">Try adjusting your search terms</p>
                </div>
            </div>
            <?php else: ?>
                <div class="p-12 text-center text-[#5C4033] opacity-60">
                    <i class="fa-solid fa-receipt text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2">No Payment Records</h3>
                    <p>No payments have been recorded yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div id="notesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-[#FFF5E1] rounded-lg shadow-xl border-2 border-[#C19A6B] p-6 w-full max-w-md mx-4">
        <div class="mb-4 pb-3 border-b border-[#E5D3B3]">
            <h3 class="text-lg font-bold text-[#5C4033] flex items-center gap-2">
                <i class="fa-solid fa-note-sticky text-[#C19A6B]"></i>
                Payment Notes
            </h3>
        </div>
        <div class="mb-6">
            <p id="notesContent" class="text-[#5C4033]"></p>
        </div>
        <div class="text-right">
            <button onclick="closeNotes()" class="px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function showNotes(notes) {
    document.getElementById('notesContent').textContent = notes;
    document.getElementById('notesModal').classList.remove('hidden');
}

function closeNotes() {
    document.getElementById('notesModal').classList.add('hidden');
}

// Search functionality
function filterPayments() {
    const searchInput = document.getElementById('searchInput');
    const filter = searchInput.value.toLowerCase();
    const tableBody = document.getElementById('paymentTableBody');
    const rows = tableBody.getElementsByClassName('payment-row');
    const noResults = document.getElementById('noResults');
    let visibleRows = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const tenantName = row.querySelector('.tenant-name').textContent.toLowerCase();
        const roomNumber = row.querySelector('.room-number').textContent.toLowerCase();
        const paymentAmount = row.querySelector('.payment-amount').textContent.toLowerCase();
        const paymentMethod = row.querySelector('.payment-method').textContent.toLowerCase();
        
        // Check if any of the searchable fields contain the filter text
        if (tenantName.includes(filter) || 
            roomNumber.includes(filter) || 
            paymentAmount.includes(filter) || 
            paymentMethod.includes(filter)) {
            row.style.display = '';
            visibleRows++;
        } else {
            row.style.display = 'none';
        }
    }
    
    // Show/hide no results message
    if (visibleRows === 0 && filter !== '') {
        noResults.classList.remove('hidden');
    } else {
        noResults.classList.add('hidden');
    }
    
    // Update results count (optional)
    updateResultsCount(visibleRows, rows.length);
}

function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.value = '';
    filterPayments(); // This will show all rows again
    searchInput.focus();
}

function updateResultsCount(visible, total) {
    // You can add a results counter here if needed
    // For example, display "Showing X of Y results" somewhere in the UI
}

// Add real-time search as user types
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        // Add event listener for Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterPayments();
            }
        });
        
        // Optional: Add clear button functionality when pressing Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSearch();
            }
        });
    }
});
</script>

</body>
</html>