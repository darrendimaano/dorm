<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if (session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;
$selectedMonth = $selectedMonth ?? '';
$selectedUser = $selectedUser ?? 0;
$monthOptions = $monthOptions ?? [];
$userOptions = $userOptions ?? [];
$filtersActive = ($selectedMonth !== '' || !empty($selectedUser));
$selectedUserLabel = '';
if (!empty($selectedUser) && !empty($userOptions)) {
    foreach ($userOptions as $option) {
        if ((int) $option['id'] === (int) $selectedUser) {
            $selectedUserLabel = trim($option['fname'] . ' ' . $option['lname']);
            break;
        }
    }
}
$filterQueryParams = [];
if ($selectedMonth !== '') {
    $filterQueryParams['month'] = $selectedMonth;
}
if (!empty($selectedUser)) {
    $filterQueryParams['user'] = (int) $selectedUser;
}
$filterQueryString = http_build_query($filterQueryParams);
$downloadCsvUrl = site_url('admin/reports/payment-history/download-csv') . ($filterQueryString ? '?' . $filterQueryString : '');
$downloadPdfUrl = site_url('admin/reports/payment-history/download-pdf') . ($filterQueryString ? '?' . $filterQueryString : '');
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
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
<body class="bg-[#FFF5E1] font-sans<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Sidebar Navigation -->
<div class="flex">
        <!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
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
                        <p class="text-[#5C4033] text-sm opacity-75">Paying Tenants</p>
                        <p class="text-3xl font-bold text-purple-600"><?= number_format($summary['unique_tenants']) ?></p>
                    </div>
                    <i class="fa-solid fa-users text-purple-500 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-4 mb-8 no-print">
            <a href="<?= $downloadCsvUrl ?>" 
               class="flex items-center gap-2 px-6 py-3 bg-[#C19A6B] text-white rounded-xl hover:bg-[#A67C52] transition-all duration-200 shadow-lg font-semibold">
                <i class="fa-solid fa-download"></i>
                <span>Download CSV</span>
            </a>
            
            <a href="<?= $downloadPdfUrl ?>" 
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

        <div class="bg-white rounded-xl border border-[#E5D3B3] shadow-lg mb-8 p-6 no-print">
            <form method="GET" action="<?= site_url('admin/reports/payment-history') ?>" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col">
                    <label for="month" class="text-sm font-semibold text-[#5C4033] mb-1">Filter by Month</label>
                    <select id="month" name="month" class="px-4 py-2 border border-[#E5D3B3] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] min-w-[180px]">
                        <option value="">All Months</option>
                        <?php foreach ($monthOptions as $option): ?>
                            <?php 
                                $optionValue = $option['month_year'];
                                $displayDate = DateTime::createFromFormat('Y-m', $optionValue);
                                $optionLabel = $displayDate ? $displayDate->format('F Y') : $optionValue;
                            ?>
                            <option value="<?= htmlspecialchars($optionValue) ?>" <?= $selectedMonth === $optionValue ? 'selected' : '' ?>>
                                <?= htmlspecialchars($optionLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex flex-col">
                    <label for="user" class="text-sm font-semibold text-[#5C4033] mb-1">Filter by Tenant</label>
                    <select id="user" name="user" class="px-4 py-2 border border-[#E5D3B3] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] min-w-[220px]">
                        <option value="">All Tenants</option>
                        <?php foreach ($userOptions as $option): ?>
                            <?php $fullName = trim($option['fname'] . ' ' . $option['lname']); ?>
                            <option value="<?= (int) $option['id'] ?>" <?= (int) $selectedUser === (int) $option['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fullName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="px-6 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200 font-semibold">
                    Apply Filters
                </button>

                <?php if ($filtersActive): ?>
                    <a href="<?= site_url('admin/reports/payment-history') ?>" class="px-6 py-2 border border-[#C19A6B] text-[#5C4033] rounded-lg hover:bg-[#F6EDE0] transition-all duration-200 font-semibold">
                        Reset Filters
                    </a>
                <?php endif; ?>
            </form>

            <?php if ($filtersActive): ?>
                <div class="mt-4 text-sm text-[#5C4033]">
                    <span class="font-semibold">Active filters:</span>
                    <span>
                        <?= $selectedMonth ? htmlspecialchars(($displayDate = DateTime::createFromFormat('Y-m', $selectedMonth)) ? $displayDate->format('F Y') : $selectedMonth) : 'All Months' ?>
                        •
                        <?= $selectedUserLabel !== '' ? htmlspecialchars($selectedUserLabel) : 'All Tenants' ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <?php if(!empty($pendingPayments)): ?>
        <div class="bg-white rounded-xl shadow-lg border border-[#E5D3B3] overflow-hidden mb-8">
            <div class="px-6 py-4 flex items-center justify-between" style="background: linear-gradient(135deg, #8B7355 0%, #B07A4B 100%);">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-hourglass-half"></i>
                    Pending Payment Approvals
                </h2>
                <p class="text-sm text-white text-opacity-80">Click ✅ to approve or ❌ to reject.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead style="background: #FFF5E1; color: #5C4033;" class="border-b border-[#E5D3B3]">
                        <tr>
                            <th class="py-3 px-4 text-left font-semibold">Submitted</th>
                            <th class="py-3 px-4 text-left font-semibold">Tenant</th>
                            <th class="py-3 px-4 text-left font-semibold">Room</th>
                            <th class="py-3 px-4 text-left font-semibold">Amount</th>
                            <th class="py-3 px-4 text-left font-semibold">Method</th>
                            <th class="py-3 px-4 text-left font-semibold">Notes</th>
                            <th class="py-3 px-4 text-left font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-[#FFF5E1]">
                        <?php foreach($pendingPayments as $payment): ?>
                        <tr class="border-b border-[#E5D3B3] hover:bg-[#F5F0E8] transition-all duration-200">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-[#5C4033]"><?= date('M j, Y', strtotime($payment['payment_date'])) ?></p>
                                <p class="text-xs text-[#5C4033] opacity-70">Submitted <?= date('g:i A', strtotime($payment['created_at'])) ?></p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-[#5C4033]"><?= htmlspecialchars($payment['fname'] . ' ' . $payment['lname']) ?></p>
                                <p class="text-xs text-[#5C4033] opacity-70"><?= htmlspecialchars($payment['email']) ?></p>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-door-closed text-[#C19A6B]"></i>
                                    <div>
                                        <p class="font-semibold text-[#5C4033]">Room #<?= htmlspecialchars($payment['room_number']) ?></p>
                                        <p class="text-xs text-[#5C4033] opacity-70">Rate: ₱<?= number_format($payment['room_rate'], 2) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-green-600 text-lg">₱<?= number_format($payment['amount'], 2) ?></span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-[#E5D3B3] text-[#5C4033]">
                                    <?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?>
                                </span>
                                <?php if (!empty($payment['payment_for'])): ?>
                                    <p class="text-xs text-[#5C4033] opacity-75 mt-1">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_for']))) ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4">
                                <?php if (!empty($payment['notes'])): ?>
                                    <p class="text-sm text-[#5C4033] payment-note truncate max-w-[160px]" title="<?= htmlspecialchars($payment['notes']) ?>">
                                        <?= htmlspecialchars($payment['notes']) ?>
                                    </p>
                                <?php else: ?>
                                    <span class="text-xs text-[#5C4033] opacity-60">No notes</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="approvePayment(<?= (int) $payment['id'] ?>)"
                                            class="w-10 h-10 rounded-full border border-green-300 text-green-700 bg-green-100 hover:bg-green-200 transition"
                                            title="Approve payment">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </button>
                                    <button type="button" onclick="rejectPayment(<?= (int) $payment['id'] ?>)"
                                            class="w-10 h-10 rounded-full border border-red-300 text-red-600 bg-red-100 hover:bg-red-200 transition"
                                            title="Reject payment">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                    </button>
                                    <?php if(!empty($payment['notes'])): ?>
                                    <button onclick="showNotes('<?= htmlspecialchars($payment['notes']) ?>')" 
                                            class="px-3 py-1 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200 text-sm">
                                        <i class="fa-solid fa-note-sticky mr-1"></i>
                                        Notes
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

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
            
            <?php if(!empty($approvedPayments)): ?>
            <div class="overflow-x-auto">
                <table class="w-full" id="paymentTable">
                    <thead style="background: #FFF5E1; color: #5C4033;" class="border-b border-[#E5D3B3]">
                        <tr>
                            <th class="py-4 px-4 text-left font-semibold">Date</th>
                            <th class="py-4 px-4 text-left font-semibold">Tenant</th>
                            <th class="py-4 px-4 text-left font-semibold">Room</th>
                            <th class="py-4 px-4 text-left font-semibold">Amount</th>
                            <th class="py-4 px-4 text-left font-semibold">Method</th>
                            <th class="py-4 px-4 text-left font-semibold">Notes</th>
                            <th class="py-4 px-4 text-left font-semibold no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-[#FFF5E1]" id="paymentTableBody">
                        <?php foreach($approvedPayments as $payment): ?>
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
                                <div class="flex flex-col gap-1">
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold bg-[#E5D3B3] text-[#5C4033] payment-method">
                                        <?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?>
                                    </span>
                                    <?php if (!empty($payment['payment_for'])): ?>
                                        <span class="text-xs text-[#5C4033] opacity-75">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['payment_for']))) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($payment['transaction_reference'])): ?>
                                        <span class="text-xs font-mono text-[#5C4033]">
                                            Ref: <?= htmlspecialchars($payment['transaction_reference']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <?php if (!empty($payment['notes'])): ?>
                                    <p class="text-sm text-[#5C4033] payment-note truncate max-w-[200px]" title="<?= htmlspecialchars($payment['notes']) ?>">
                                        <?= htmlspecialchars($payment['notes']) ?>
                                    </p>
                                <?php else: ?>
                                    <span class="text-xs text-[#5C4033] opacity-60">No notes</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 no-print">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-full border text-green-600 bg-green-100 border-green-300 shadow-sm" title="Approved">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </span>
                                    <span class="flex items-center justify-center w-9 h-9 rounded-full border text-[#8B7355] bg-[#F6EDE0] border-[#E5D3B3] opacity-70" title="Rejected / Declined">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                    </span>
                                    <?php if(!empty($payment['notes'])): ?>
                                    <button onclick="showNotes('<?= htmlspecialchars($payment['notes']) ?>')" 
                                            class="px-3 py-1 bg-[#C19A6B] text-white rounded-lg hover:bg-[#A67C52] transition-all duration-200 text-sm">
                                        <i class="fa-solid fa-note-sticky mr-1"></i>
                                        Notes
                                    </button>
                                    <?php endif; ?>
                                </div>
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

function approvePayment(id) {
    fetch('<?= site_url('admin/reports/payment-history/approve') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Failed to approve payment.');
        }
    })
    .catch(err => alert('Failed to approve payment: ' + err.message));
}

function rejectPayment(id) {
    const reason = prompt('Optional: provide a reason for rejecting this payment. Leave blank to skip.');
    fetch('<?= site_url('admin/reports/payment-history/reject') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id, reason})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Failed to reject payment.');
        }
    })
    .catch(err => alert('Failed to reject payment: ' + err.message));
}

// Search functionality
function filterPayments() {
    const searchInput = document.getElementById('searchInput');
    const filter = searchInput.value.toLowerCase();
    const tableBody = document.getElementById('paymentTableBody');
    if (!tableBody) {
        return;
    }
    const rows = tableBody.getElementsByClassName('payment-row');
    const noResults = document.getElementById('noResults');
    let visibleRows = 0;
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const tenantName = row.querySelector('.tenant-name').textContent.toLowerCase();
        const roomNumber = row.querySelector('.room-number').textContent.toLowerCase();
        const paymentAmount = row.querySelector('.payment-amount').textContent.toLowerCase();
        const paymentMethod = row.querySelector('.payment-method').textContent.toLowerCase();
        const paymentNote = row.querySelector('.payment-note') ? row.querySelector('.payment-note').textContent.toLowerCase() : '';
        
        // Check if any of the searchable fields contain the filter text
        if (tenantName.includes(filter) || 
            roomNumber.includes(filter) || 
            paymentAmount.includes(filter) || 
            paymentMethod.includes(filter) ||
            paymentNote.includes(filter)) {
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