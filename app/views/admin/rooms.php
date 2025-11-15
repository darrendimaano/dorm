<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Room Management - Dormitory Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; color: #5C4033 !important; }
            .bg-gradient-to-br { background: white !important; }
            .shadow-lg { box-shadow: none !important; }
            .rounded-lg { border-radius: 0 !important; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #C19A6B !important; padding: 8px !important; color: #5C4033 !important; }
            th { background: #e6ddd4 !important; font-weight: bold; }
            * { color: #5C4033 !important; border-color: #C19A6B !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body class="bg-white font-sans flex">

<!-- Sidebar -->
<div class="text-[#5C4033] w-64 min-h-screen p-6 fixed left-0 top-0 z-50 shadow-lg" style="background: #D2B48C;">
  <h2 class="text-2xl font-bold mb-8">🏨</h2>
  <nav class="flex flex-col gap-4">
    <a href="<?= site_url('dashboard') ?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
    </a>
    <a href="<?=site_url('users')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-user"></i> <span>Users</span>
    </a>
    <a href="<?=site_url('rooms')?>" class="flex items-center gap-2 px-4 py-2 rounded bg-[#C19A6B] text-white font-semibold">
      <i class="fa-solid fa-bed"></i> <span>Rooms</span>
    </a>
    <a href="<?=site_url('admin/reservations')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
      <i class="fa-solid fa-list-check"></i> <span>Reservations</span>
    </a>
    <a href="<?=site_url('admin/reports')?>" class="flex items-center gap-2 px-4 py-2 rounded hover:bg-[#C19A6B] transition">
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

<!-- Main content -->
<div class="flex-1 ml-64 transition-all duration-300">
  <div style="background: #FFF5E1;" class="shadow-md flex items-center justify-between px-4 py-3 md:ml-0">
    <h1 class="font-bold text-lg text-[#5C4033]">Advanced Room Management</h1>
  </div>
  <div class="w-full mt-4 px-3">
        <!-- Header -->
        <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-6 mb-6 border" style="border-color: #C19A6B;">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-[#5C4033]">
                        <i class="fas fa-cogs text-[#C19A6B] mr-3"></i>Advanced Room Management
                    </h1>
                    <p class="text-[#5C4033] mt-2 opacity-80">Professional interface for comprehensive room administration</p>
                </div>
                <div class="flex gap-4">
                    <a href="<?= site_url('rooms'); ?>" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-300 no-print">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Rooms
                    </a>
                    <div class="flex gap-2">
                        <button onclick="printTable()" class="group inline-flex items-center px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition-all duration-300 no-print shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hover:scale-102" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
                            <i class="fas fa-print mr-2 text-sm group-hover:animate-pulse"></i>
                            <span class="font-medium">Print</span>
                        </button>
                        <button onclick="exportToExcel()" class="group inline-flex items-center px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition-all duration-300 no-print shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hover:scale-102" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
                            <i class="fas fa-file-excel mr-2 text-sm group-hover:animate-bounce"></i>
                            <span class="font-medium">Excel</span>
                        </button>
                        <button onclick="exportToCSV()" class="group inline-flex items-center px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition-all duration-300 no-print shadow-md hover:shadow-lg transform hover:-translate-y-0.5 hover:scale-102" style="background: linear-gradient(135deg, #C19A6B 0%, #B07A4B 100%);">
                            <i class="fas fa-file-csv mr-2 text-sm group-hover:animate-pulse"></i>
                            <span class="font-medium">CSV</span>
                        </button>
                    </div>
                    
                    <!-- Confirmation Message Container -->
                    <div id="confirmationBox" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 no-print">
                        <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-question-circle text-[#C19A6B] text-2xl mr-3"></i>
                                <h3 class="text-lg font-semibold text-[#5C4033]">Confirm Action</h3>
                            </div>
                            <p id="confirmationMessage" class="text-[#5C4033] mb-6"></p>
                            <div class="flex justify-end gap-3">
                                <button onclick="cancelAction()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition duration-300">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </button>
                                <button id="confirmButton" class="px-4 py-2 text-white rounded-lg hover:bg-[#B07A4B] transition duration-300" style="background: #C19A6B;">
                                    <i class="fas fa-check mr-2"></i>Confirm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- Rooms Management Table -->
        <div style="background: #FFF5E1;" class="rounded-lg shadow-lg overflow-hidden border" style="border-color: #C19A6B;">
            <!-- Print Header (only visible when printing) -->
            <div class="print-only p-6 text-center border-b">
                <h1 class="text-2xl font-bold text-[#5C4033]">Dormitory Room Management Report</h1>
                <p class="text-[#5C4033]">Generated on <?= date('F d, Y \a\t g:i A'); ?></p>
                <p class="text-[#5C4033]">Total Rooms: <?= count($rooms); ?></p>
            </div>
            
            <div class="p-6 border-b" style="border-color: #C19A6B;">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-[#5C4033]">
                        <i class="fas fa-list mr-2 text-[#C19A6B]"></i>All Dormitory Rooms - Professional Management
                    </h2>
                    <div class="text-sm text-[#5C4033] no-print opacity-80">
                        <span style="background: #e6f2ff; color: #5C4033; border-color: #C19A6B;" class="border px-2 py-1 rounded">
                            <i class="fas fa-info-circle mr-1"></i>
                            Use export buttons above to download or print this data
                        </span>
                    </div>
                </div>
            </div>

            <?php if (empty($rooms)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-bed text-6xl text-[#C19A6B] mb-4"></i>
                    <p class="text-[#5C4033] text-lg">No dormitory rooms found in the system.</p>
                    <p class="text-[#5C4033] opacity-70 text-sm mt-2">Rooms can be managed through the main rooms interface.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full" id="roomsTable">
                        <thead style="background: #e6ddd4;">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Room Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Beds</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Available</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Payment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#5C4033] uppercase tracking-wider no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y" style="border-color: #C19A6B;">
                            <?php foreach ($rooms as $room): ?>
                                <tr class="hover:bg-[#FFF5E1] transition duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-door-open text-[#C19A6B] mr-2 no-print"></i>
                                            <span class="text-sm font-medium text-[#5C4033]"><?= htmlspecialchars($room['room_number']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033]">
                                        <i class="fas fa-bed text-[#C19A6B] mr-1 no-print"></i>
                                        <?= htmlspecialchars($room['beds']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033]">
                                        <?= htmlspecialchars($room['available']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4033]">
                                        <i class="fas fa-peso-sign text-[#C19A6B] mr-1 no-print"></i>
                                        ₱<?= number_format($room['payment'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($room['available'] > 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1 no-print"></i>
                                                Available
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times mr-1 no-print"></i>
                                                Full
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium no-print">
                                        <div class="flex space-x-2">
                                            <a href="<?= site_url('admin/rooms/update/' . $room['id']); ?>" 
                                               class="inline-flex items-center px-3 py-1 text-white rounded hover:bg-[#B07A4B] transition duration-300" style="background: #C19A6B;">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <a href="<?= site_url('admin/rooms/delete/' . $room['id']); ?>" 
                                               onclick="return confirm('Are you sure you want to delete Room #<?= htmlspecialchars($room['room_number']); ?>?')"
                                               class="inline-flex items-center px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition duration-300">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
            <div style="background: #FFF5E1;" class="rounded-lg shadow p-6 border" style="border-color: #C19A6B;">
                <div class="flex items-center">
                    <div class="p-3 rounded-full mr-4" style="background: #e6ddd4; color: #C19A6B;">
                        <i class="fas fa-bed text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#5C4033] opacity-80">Total Rooms</p>
                        <p class="text-2xl font-bold text-[#5C4033]"><?= count($rooms); ?></p>
                    </div>
                </div>
            </div>

            <div style="background: #FFF5E1;" class="rounded-lg shadow p-6 border" style="border-color: #C19A6B;">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                        <i class="fas fa-check text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#5C4033] opacity-80">Available</p>
                        <p class="text-2xl font-bold text-[#5C4033]">
                            <?= array_reduce($rooms, function($carry, $room) { return $carry + ($room['available'] > 0 ? 1 : 0); }, 0); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div style="background: #FFF5E1;" class="rounded-lg shadow p-6 border" style="border-color: #C19A6B;">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                        <i class="fas fa-times text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#5C4033] opacity-80">Full</p>
                        <p class="text-2xl font-bold text-[#5C4033]">
                            <?= array_reduce($rooms, function($carry, $room) { return $carry + ($room['available'] == 0 ? 1 : 0); }, 0); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div style="background: #FFF5E1;" class="rounded-lg shadow p-6 border" style="border-color: #C19A6B;">
                <div class="flex items-center">
                    <div class="p-3 rounded-full mr-4" style="background: #e6ddd4; color: #C19A6B;">
                        <i class="fas fa-peso-sign text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#5C4033] opacity-80">Avg. Rate</p>
                        <p class="text-2xl font-bold text-[#5C4033]">
                            ₱<?= count($rooms) > 0 ? number_format(array_sum(array_column($rooms, 'payment')) / count($rooms), 0) : '0'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Confirmation dialog functions (define first)
function showConfirmation(message, callback) {
    document.getElementById('confirmationMessage').textContent = message;
    document.getElementById('confirmationBox').classList.remove('hidden');
    
    document.getElementById('confirmButton').onclick = function() {
        hideConfirmation();
        callback();
    };
}

function cancelAction() {
    hideConfirmation();
}

function hideConfirmation() {
    document.getElementById('confirmationBox').classList.add('hidden');
}

// Print functionality
function printTable() {
    showConfirmation('Are you sure you want to print the rooms report?', function() {
        // Hide no-print elements and show print-only elements
        const noPrintElements = document.querySelectorAll('.no-print');
        const printOnlyElements = document.querySelectorAll('.print-only');
        
        noPrintElements.forEach(el => el.style.display = 'none');
        printOnlyElements.forEach(el => el.style.display = 'block');
        
        // Print the page
        window.print();
        
        // Restore original visibility after printing
    setTimeout(() => {
        noPrintElements.forEach(el => el.style.display = '');
        printOnlyElements.forEach(el => el.style.display = 'none');
    }, 1000);
    });
}

// Export to Excel
function exportToExcel() {
    showConfirmation('Are you sure you want to download the rooms data as Excel file?', function() {
        const table = document.getElementById('roomsTable');
        const data = [];
    
    // Get headers (excluding Actions column)
    const headers = [];
    const headerRow = table.querySelector('thead tr');
    const headerCells = headerRow.querySelectorAll('th:not(.no-print)');
    headerCells.forEach(cell => {
        headers.push(cell.textContent.trim());
    });
    data.push(headers);
    
    // Get data rows
    const bodyRows = table.querySelectorAll('tbody tr');
    bodyRows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td:not(.no-print)');
        cells.forEach(cell => {
            // Clean text content (remove icons and extra whitespace)
            let text = cell.textContent.trim();
            // Handle payment formatting
            if (text.includes('₱')) {
                text = text.replace('₱', '').trim();
            }
            rowData.push(text);
        });
        data.push(rowData);
    });
    
    // Create workbook and worksheet
    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Rooms');
    
    // Auto-size columns
    const colWidths = [];
    for (let i = 0; i < headers.length; i++) {
        let maxLength = headers[i].length;
        for (let j = 1; j < data.length; j++) {
            if (data[j][i] && data[j][i].length > maxLength) {
                maxLength = data[j][i].length;
            }
        }
        colWidths.push({ width: Math.min(maxLength + 2, 50) });
    }
    ws['!cols'] = colWidths;
    
    // Generate filename with current date
    const now = new Date();
    const filename = `Dormitory_Rooms_${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}.xlsx`;
    
    // Save file
    XLSX.writeFile(wb, filename);
    
    // Show success message
    showExportMessage('Excel file downloaded successfully!', 'success');
    });
}

// Export to CSV
function exportToCSV() {
    showConfirmation('Are you sure you want to download the rooms data as CSV file?', function() {
        const table = document.getElementById('roomsTable');
        const rows = [];
    
    // Get headers (excluding Actions column)
    const headerRow = table.querySelector('thead tr');
    const headerCells = headerRow.querySelectorAll('th:not(.no-print)');
    const headers = Array.from(headerCells).map(cell => cell.textContent.trim());
    rows.push(headers.join(','));
    
    // Get data rows
    const bodyRows = table.querySelectorAll('tbody tr');
    bodyRows.forEach(row => {
        const cells = row.querySelectorAll('td:not(.no-print)');
        const rowData = Array.from(cells).map(cell => {
            let text = cell.textContent.trim();
            // Handle payment formatting and escape commas
            if (text.includes('₱')) {
                text = text.replace('₱', '').trim();
            }
            // Escape commas and quotes in CSV
            if (text.includes(',') || text.includes('"')) {
                text = `"${text.replace(/"/g, '""')}"`;
            }
            return text;
        });
        rows.push(rowData.join(','));
    });
    
    // Create and download CSV
    const csvContent = rows.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    
    // Generate filename with current date
    const now = new Date();
    const filename = `Dormitory_Rooms_${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}.csv`;
    
    // Create download link
    const link = document.createElement('a');
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Show success message
    showExportMessage('CSV file downloaded successfully!', 'success');
    });
}

// Show export success message
function showExportMessage(message, type = 'success') {
    // Remove existing messages
    const existingMessage = document.getElementById('exportMessage');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.id = 'exportMessage';
    messageDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 ${
        type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'
    }`;
    messageDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
            ${message}
        </div>
    `;
    
    document.body.appendChild(messageDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        messageDiv.style.opacity = '0';
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 300);
    }, 3000);
}

// Add some animations for better UX
document.addEventListener('DOMContentLoaded', function() {
    // Add loading animation to export buttons
    const exportButtons = document.querySelectorAll('[onclick^="export"], [onclick="printTable()"]');
    exportButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.getAttribute('onclick') !== 'printTable()') {
                // Add loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exporting...';
                this.disabled = true;
                
                // Restore after delay
                setTimeout(() => {
                    this.innerHTML = originalContent;
                    this.disabled = false;
                }, 1500);
            }
        });
    });
});
</script>

</body>
</html>