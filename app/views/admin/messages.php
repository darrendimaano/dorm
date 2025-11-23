<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
if(session_status() === PHP_SESSION_NONE) session_start();
$darkModeEnabled = false;
$maintenanceRequests = $maintenanceRequests ?? [];
$maintenanceSummary = [
  'total' => count($maintenanceRequests),
  'pending' => 0,
  'in_progress' => 0,
  'completed' => 0,
  'cancelled' => 0
];

foreach ($maintenanceRequests as $maintenanceRequest) {
  $statusKey = $maintenanceRequest['status'] ?? 'pending';
  if (isset($maintenanceSummary[$statusKey])) {
    $maintenanceSummary[$statusKey]++;
  }
}
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages - Dormitory Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-[#FFF5E1] font-sans flex<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-[#5C4033] mb-2">Messages</h1>
                    <p class="text-[#5C4033] opacity-75">Manage tenant messages and inquiries</p>
                </div>
                <div class="flex items-center gap-4 flex-wrap justify-end">
                  <button id="maintenanceToggleButton" class="bg-[#C19A6B] hover:bg-[#5C4033] text-white px-4 py-2 rounded-full font-semibold transition text-sm">
                    <i class="fa-solid fa-wrench"></i> View Maintenance
                  </button>
                  <div class="text-right">
                    <p class="text-sm text-[#5C4033] opacity-75">Last updated</p>
                    <p class="text-[#5C4033] font-semibold"><?= date('M j, Y g:i A') ?></p>
                  </div>
                </div>
            </div>
        </div>

        <div class="w-full px-3">
    
    <!-- Success / Error Messages -->
    <?php if(!empty($success)): ?>
      <div id="flash-success-banner" class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 text-center shadow border border-green-200">
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
      </div>
    <?php else: ?>
      <div id="flash-success-banner" class="hidden bg-green-100 text-green-700 p-4 rounded-lg mb-6 text-center shadow border border-green-200">
        <i class="fa-solid fa-check-circle"></i> <span id="flash-success-text"></span>
      </div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
      <div id="flash-error-banner" class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 text-center shadow border border-red-200">
        <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php else: ?>
      <div id="flash-error-banner" class="hidden bg-red-100 text-red-700 p-4 rounded-lg mb-6 text-center shadow border border-red-200">
        <i class="fa-solid fa-exclamation-circle"></i> <span id="flash-error-text"></span>
      </div>
    <?php endif; ?>

    <?php if(!empty($messages)): ?>
      <!-- Messages List -->
      <div class="space-y-6">
        <?php foreach($messages as $message): ?>
          <?php
            $status = $message['status'] ?? 'unread';
              $statusMap = [
                'replied' => ['classes' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200', 'icon' => 'reply', 'label' => 'Replied'],
                'read' => ['classes' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200', 'icon' => 'eye', 'label' => 'Read'],
                'unread' => ['classes' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200', 'icon' => 'envelope', 'label' => 'Unread'],
                'archived' => ['classes' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200', 'icon' => 'archive', 'label' => 'Archived']
            ];
            $statusMeta = $statusMap[$status] ?? ['classes' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200', 'icon' => 'envelope', 'label' => ucfirst($status)];
          ?>
          <div class="bg-[#FFF5E1] border border-[#C19A6B] rounded-2xl p-6 shadow-lg">
            
            <!-- Message Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 pb-4 border-b border-[#E5D3B3]">
              <div class="flex items-center gap-4 mb-2 md:mb-0">
                <div class="bg-[#C19A6B] text-white w-12 h-12 rounded-full flex items-center justify-center">
                  <i class="fa-solid fa-user text-xl"></i>
                </div>
                <div>
                  <h3 class="font-bold text-[#5C4033]"><?= htmlspecialchars($message['fname'] . ' ' . $message['lname']) ?></h3>
                  <p class="text-gray-600 text-sm"><?= htmlspecialchars($message['email']) ?></p>
                </div>
              </div>
              
              <div class="flex items-center gap-4">
                <span id="status-badge-<?= $message['id'] ?>" class="status-badge inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold <?= $statusMeta['classes'] ?>">
                  <i class="fa-solid fa-<?= $statusMeta['icon'] ?>"></i>
                  <?= $statusMeta['label'] ?>
                </span>
                <span class="text-sm text-gray-500">#<?= $message['id'] ?></span>
              </div>
            </div>

            <!-- Message Content -->
            <div class="mb-4">
              <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-tag text-[#C19A6B]"></i>
                <span class="font-semibold text-[#5C4033]">Subject:</span>
                <span class="text-gray-700"><?= htmlspecialchars($message['subject']) ?></span>
              </div>
              
              <div class="bg-white p-4 rounded-lg border border-[#E5D3B3]">
                <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
              </div>
            </div>

            <!-- Admin Reply Section -->
            <div id="admin-reply-container-<?= $message['id'] ?>">
            <?php if (!empty($message['admin_reply'])): ?>
              <div class="bg-green-50 border border-green-200 p-4 rounded-lg mb-4">
                <div class="flex items-center gap-2 mb-2">
                  <i class="fa-solid fa-reply text-green-600"></i>
                  <span class="font-semibold text-green-800">Admin Reply:</span>
                </div>
                <p class="text-green-700"><?= nl2br(htmlspecialchars($message['admin_reply'])) ?></p>
              </div>
            <?php else: ?>
              <p class="text-sm italic text-gray-500 dark:text-gray-300">Awaiting admin response.</p>
            <?php endif; ?>
            </div>

            <!-- Reply Form -->
            <?php if ($message['status'] !== 'replied'): ?>
              <div class="border-t border-[#E5D3B3] pt-4">
                <form id="reply-form-<?= $message['id'] ?>" data-message-id="<?= $message['id'] ?>" method="POST" action="<?= site_url('admin/messages/reply') ?>">
                  <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                  <div class="mb-3">
                    <label class="block text-sm font-medium text-[#5C4033] mb-2">Reply to Tenant:</label>
                    <textarea name="reply" rows="3" 
                              class="w-full px-4 py-2 border border-[#C19A6B] rounded-lg focus:ring-2 focus:ring-[#C19A6B] focus:border-[#C19A6B]" 
                              placeholder="Type your reply here..." required></textarea>
                  </div>
                  <div class="flex gap-2 flex-wrap">
                    <button type="button" data-form-id="reply-form-<?= $message['id'] ?>" class="send-reply-btn bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-4 py-2 rounded-full font-semibold transition text-sm">
                      <i class="fa-solid fa-paper-plane"></i> Send Reply
                    </button>
                    <?php if (strtolower($message['status'] ?? '') === 'unread'): ?>
                        <button
                          type="button"
                          id="mark-read-btn-<?= $message['id'] ?>"
                          data-message-id="<?= $message['id'] ?>"
                          data-mark-read-url="<?= site_url('admin/messages/read') ?>"
                          class="js-mark-read bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-full font-semibold transition text-sm">
                        <i class="fa-solid fa-eye"></i> Mark as Read
                      </button>
                    <?php endif; ?>
                  </div>
                </form>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- No Messages -->
      <div class="bg-[#FFF5E1] border border-[#C19A6B] p-12 rounded-2xl text-center shadow-lg">
        <div class="text-[#C19A6B] mb-6">
          <i class="fa-solid fa-envelope-open text-6xl"></i>
        </div>
        <h3 class="text-2xl font-semibold text-[#5C4033] mb-4">No Messages Yet</h3>
        <p class="text-gray-600">No tenant messages have been received.</p>
      </div>
    <?php endif; ?>

    <div id="maintenancePanel" class="hidden mt-10">
      <div class="bg-[#FFF5E1] border border-[#C19A6B] rounded-2xl shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between p-6 border-b border-[#E5D3B3]">
          <div>
            <h2 class="text-2xl font-bold text-[#5C4033]">Maintenance Requests</h2>
            <p class="text-[#5C4033] opacity-75 text-sm">Review tenant reported issues without leaving this page.</p>
          </div>
          <a href="<?= site_url('admin/maintenance') ?>" class="mt-4 md:mt-0 bg-[#C19A6B] hover:bg-[#5C4033] text-white px-4 py-2 rounded-full font-semibold transition text-sm">
            <i class="fa-solid fa-up-right-from-square"></i> Open Full Maintenance View
          </a>
        </div>

        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-[#E5D3B3] rounded-xl p-4 text-center">
              <p class="text-sm text-[#5C4033] opacity-75">Total Requests</p>
              <p class="text-2xl font-bold text-[#5C4033] mt-1"><?= $maintenanceSummary['total'] ?></p>
            </div>
            <div class="bg-white border border-[#E5D3B3] rounded-xl p-4 text-center">
              <p class="text-sm text-[#5C4033] opacity-75">Pending</p>
              <p class="text-2xl font-bold text-[#5C4033] mt-1"><?= $maintenanceSummary['pending'] ?></p>
            </div>
            <div class="bg-white border border-[#E5D3B3] rounded-xl p-4 text-center">
              <p class="text-sm text-[#5C4033] opacity-75">In Progress</p>
              <p class="text-2xl font-bold text-[#5C4033] mt-1"><?= $maintenanceSummary['in_progress'] ?></p>
            </div>
            <div class="bg-white border border-[#E5D3B3] rounded-xl p-4 text-center">
              <p class="text-sm text-[#5C4033] opacity-75">Completed</p>
              <p class="text-2xl font-bold text-[#5C4033] mt-1"><?= $maintenanceSummary['completed'] ?></p>
            </div>
          </div>

          <?php if (!empty($maintenanceRequests)): ?>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead style="background: #e6ddd4;">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Tenant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Room</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Issue</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-[#5C4033] uppercase tracking-wider">Updated</th>
                  </tr>
                </thead>
                <tbody class="divide-y" style="divide-color: #C19A6B;">
                  <?php foreach ($maintenanceRequests as $request): ?>
                    <?php
                      $priorityColors = [
                          'low' => 'bg-gray-100 text-gray-800',
                          'medium' => 'bg-blue-100 text-blue-800',
                          'high' => 'bg-orange-100 text-orange-800',
                          'urgent' => 'bg-red-100 text-red-800'
                      ];
                      $statusColors = [
                          'pending' => 'bg-yellow-100 text-yellow-800',
                          'in_progress' => 'bg-blue-100 text-blue-800',
                          'completed' => 'bg-green-100 text-green-800',
                          'cancelled' => 'bg-gray-200 text-gray-700'
                      ];
                      $priorityClass = $priorityColors[$request['priority'] ?? 'medium'] ?? 'bg-gray-100 text-gray-800';
                      $statusClass = $statusColors[$request['status'] ?? 'pending'] ?? 'bg-yellow-100 text-yellow-800';
                    ?>
                    <tr>
                      <td class="px-4 py-4 text-sm text-[#5C4033]">
                        <div class="font-semibold"><?= htmlspecialchars(trim(($request['fname'] ?? '') . ' ' . ($request['lname'] ?? '')) ?: 'Unknown Tenant') ?></div>
                        <div class="text-xs opacity-75"><?= htmlspecialchars($request['email'] ?? 'No email') ?></div>
                      </td>
                      <td class="px-4 py-4 text-sm text-[#5C4033]">#<?= htmlspecialchars($request['room_number'] ?? 'N/A') ?></td>
                      <td class="px-4 py-4 text-sm text-[#5C4033]">
                        <div class="font-medium"><?= htmlspecialchars($request['title'] ?? 'Maintenance Issue') ?></div>
                        <div class="text-xs opacity-75"><?= htmlspecialchars(substr($request['description'] ?? '', 0, 60)) ?><?= strlen($request['description'] ?? '') > 60 ? '…' : '' ?></div>
                      </td>
                      <td class="px-4 py-4 text-sm text-[#5C4033]">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $priorityClass ?>"><?= ucfirst($request['priority'] ?? 'medium') ?></span>
                      </td>
                      <td class="px-4 py-4 text-sm text-[#5C4033]">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $request['status'] ?? 'pending')) ?></span>
                      </td>
                      <td class="px-4 py-4 text-sm text-[#5C4033]">
                        <?= isset($request['updated_at']) ? date('M j, Y g:i A', strtotime($request['updated_at'])) : '—' ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="bg-white border border-dashed border-[#E5D3B3] p-10 rounded-xl text-center text-[#5C4033] opacity-75">
              <i class="fa-solid fa-wrench text-4xl mb-4 text-[#C19A6B]"></i>
              <p>No maintenance requests on file right now.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Send Reply Confirmation Modal -->
<div id="sendReplyModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-[#C19A6B] mx-4">
    <div class="p-6">
      <div class="flex items-center gap-3 mb-4">
        <div class="bg-[#C19A6B] text-white w-12 h-12 rounded-full flex items-center justify-center">
          <i class="fa-solid fa-paper-plane text-xl"></i>
        </div>
        <div>
          <h2 class="text-lg font-semibold text-[#5C4033]">Send this reply?</h2>
          <p class="text-sm text-[#5C4033] opacity-75">Confirm to deliver your response to the tenant.</p>
        </div>
      </div>
      <div class="flex justify-end gap-3">
        <button id="sendReplyCancel" type="button" class="px-4 py-2 rounded-full border border-[#D2B48C] text-[#5C4033] hover:bg-[#F5E6D3] transition text-sm font-semibold">Cancel</button>
        <button id="sendReplyConfirm" type="button" class="px-4 py-2 rounded-full bg-[#C19A6B] hover:bg-[#B07A4B] text-white transition text-sm font-semibold">
          <i class="fa-solid fa-paper-plane"></i> Send
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Sidebar toggle functionality
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

if (menuBtn && sidebar && mainContent) {
  menuBtn.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    if (sidebar.classList.contains('collapsed')) {
      mainContent.classList.remove('ml-64');
      mainContent.classList.add('ml-16');
    } else {
      mainContent.classList.remove('ml-16');
      mainContent.classList.add('ml-64');
    }
  });
}

const statusBadgeStyles = {
  unread: {
    classes: 'status-badge inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
    icon: 'envelope',
    label: 'Unread'
  },
  read: {
    classes: 'status-badge inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    icon: 'eye',
    label: 'Read'
  },
  replied: {
    classes: 'status-badge inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    icon: 'reply',
    label: 'Replied'
  }
};

const markReadEndpoint = '<?= site_url('admin/messages/read') ?>';
const replyEndpointBase = '<?= site_url('admin/messages/reply') ?>';

function applyStatusToBadge(badge, status) {
  const meta = statusBadgeStyles[status] || statusBadgeStyles.read;
  badge.className = meta.classes;
  badge.innerHTML = `<i class="fa-solid fa-${meta.icon}"></i> ${meta.label}`;
}

function updateSidebarUnreadCount(count) {
  const badge = document.getElementById('sidebar-messages-badge');
  if (!badge) {
    return;
  }

  const numericCount = parseInt(count, 10);
  const safeCount = Number.isFinite(numericCount) ? Math.max(0, numericCount) : 0;
  badge.dataset.count = safeCount;
  badge.textContent = safeCount;

  if (safeCount > 0) {
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
}

function markAsRead(messageId) {
  const numericId = parseInt(messageId, 10);
  if (!Number.isFinite(numericId) || numericId <= 0) {
    showErrorBanner('Invalid message identifier.');
    return;
  }

  const formData = new FormData();
  formData.append('message_id', numericId);

  const button = document.getElementById(`mark-read-btn-${numericId}`);
  const endpoint = markReadEndpoint;

  fetch(endpoint, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData
  })
  .then(async response => {
    const rawText = await response.text();
    try {
      return JSON.parse(rawText);
    } catch (parseError) {
      return {
        parseError: true,
        rawText: rawText.trim(),
        status: response.status
      };
    }
  })
  .then(data => {
    if (data && data.parseError) {
      const statusText = typeof data.status !== 'undefined' ? ` (HTTP ${data.status})` : '';
      const details = data.rawText ? `: ${data.rawText}` : '';
      showErrorBanner(`Unable to update message status${statusText}${details}`.trim());
      return;
    }

    if (data && data.success) {
      const badge = document.getElementById(`status-badge-${numericId}`);
      if (badge) {
        applyStatusToBadge(badge, data.status || 'read');
      }

      const button = document.getElementById(`mark-read-btn-${numericId}`);
      if (button && (data.status || 'read') !== 'unread') {
        button.remove();
      }

      if (typeof data.unreadCount !== 'undefined') {
        updateSidebarUnreadCount(data.unreadCount);
      }

      showSuccessBanner('Message marked as read.');
      return;
    }

    if (data && data.error) {
      const message = data.errorDetails ? `${data.error} (${data.errorDetails})` : data.error;
      showErrorBanner(message);
      return;
    }

    showErrorBanner('Unable to update message status right now.');
  })
  .catch(() => {
    showErrorBanner('Unable to update message status right now.');
  });
}

document.querySelectorAll('.js-mark-read').forEach(button => {
  button.addEventListener('click', () => {
    const targetId = button.dataset.messageId;
    markAsRead(targetId);
  });
});

document.querySelectorAll('textarea').forEach(textarea => {
  textarea.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = `${this.scrollHeight}px`;
  });
});

// Send Reply confirmation handling
let activeReplyForm = null;
const sendReplyModal = document.getElementById('sendReplyModal');
const sendReplyConfirm = document.getElementById('sendReplyConfirm');
const sendReplyCancel = document.getElementById('sendReplyCancel');
const flashSuccessBanner = document.getElementById('flash-success-banner');
const flashSuccessText = document.getElementById('flash-success-text');
const flashErrorBanner = document.getElementById('flash-error-banner');
const flashErrorText = document.getElementById('flash-error-text');

function openSendReplyModal(formId) {
  activeReplyForm = document.getElementById(formId);
  if (!activeReplyForm) {
    return;
  }
  sendReplyModal.classList.remove('hidden');
}

function closeSendReplyModal() {
  sendReplyModal.classList.add('hidden');
  activeReplyForm = null;
}

document.querySelectorAll('.send-reply-btn').forEach(button => {
  button.addEventListener('click', () => {
    const formId = button.getAttribute('data-form-id');
    openSendReplyModal(formId);
  });
});

const maintenanceToggleButton = document.getElementById('maintenanceToggleButton');
const maintenancePanel = document.getElementById('maintenancePanel');

if (maintenanceToggleButton && maintenancePanel) {
  maintenanceToggleButton.addEventListener('click', () => {
    const isHidden = maintenancePanel.classList.contains('hidden');
    if (isHidden) {
      maintenancePanel.classList.remove('hidden');
      maintenanceToggleButton.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Hide Maintenance';
    } else {
      maintenancePanel.classList.add('hidden');
      maintenanceToggleButton.innerHTML = '<i class="fa-solid fa-wrench"></i> View Maintenance';
    }
  });
}

if (sendReplyCancel) {
  sendReplyCancel.addEventListener('click', closeSendReplyModal);
}

if (sendReplyConfirm) {
  sendReplyConfirm.addEventListener('click', () => {
    if (activeReplyForm) {
      const targetForm = activeReplyForm;
      const textarea = targetForm.querySelector('textarea[name="reply"]');
      if (textarea && textarea.value.trim() === '') {
        textarea.focus();
        return;
      }

      const replyText = textarea ? textarea.value : '';
      const formData = new FormData(targetForm);
      const messageId = targetForm.dataset.messageId;
      if (messageId && !formData.has('message_id')) {
        formData.append('message_id', messageId);
      }
      const action = targetForm.getAttribute('action') || replyEndpointBase;
      console.log('Sending admin reply', { action, messageId });

      sendReplyConfirm.disabled = true;

      fetch(action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      })
      .then(async response => {
        const rawText = await response.text();
        try {
          return JSON.parse(rawText);
        } catch (parseError) {
          console.error('Reply request returned non-JSON response:', rawText);
          return {
            parseError: true,
            rawText: rawText.trim(),
            status: response.status
          };
        }
      })
      .then(data => {
        if (data && data.parseError) {
          const statusText = typeof data.status !== 'undefined' ? ` (HTTP ${data.status})` : '';
          const message = data.rawText ? `Server returned an unexpected response${statusText}: ${data.rawText}` : `Server returned an unexpected response${statusText}.`;
          showErrorBanner(message);
          return;
        }

        if (data && data.success) {
          showSuccessBanner(data.message || 'Message sent!');

          const badge = document.getElementById(`status-badge-${messageId}`);
          if (badge) {
            applyStatusToBadge(badge, data.status || 'replied');
          }

          const markButton = document.getElementById(`mark-read-btn-${messageId}`);
          if (markButton) {
            markButton.remove();
          }

          if (typeof data.unreadCount !== 'undefined') {
            updateSidebarUnreadCount(data.unreadCount);
          }

          const replyContainer = document.getElementById(`admin-reply-container-${messageId}`);
          if (replyContainer) {
            replyContainer.innerHTML = `
              <div class="bg-green-50 border border-green-200 p-4 rounded-lg mb-4">
                <div class="flex items-center gap-2 mb-2">
                  <i class="fa-solid fa-reply text-green-600"></i>
                  <span class="font-semibold text-green-800">Admin Reply:</span>
                </div>
                <p class="text-green-700">${formatReplyHtml(data.admin_reply || replyText)}</p>
              </div>
            `;
          }

          targetForm.reset();
          if (targetForm.parentElement) {
            targetForm.parentElement.classList.add('hidden');
          }
        } else if (data && data.error) {
          const errorMessage = data.errorDetails ? `${data.error} (${data.errorDetails})` : data.error;
          showErrorBanner(errorMessage);
        } else {
          showErrorBanner('Unable to send the reply right now.');
        }
      })
      .catch(() => {
        showErrorBanner('Unable to send the reply right now.');
      })
      .finally(() => {
        sendReplyConfirm.disabled = false;
      });
    }

    closeSendReplyModal();
  });
}

sendReplyModal.addEventListener('click', event => {
  if (event.target === sendReplyModal) {
    closeSendReplyModal();
  }
});

function formatReplyHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML.replace(/\n/g, '<br>');
}

function showSuccessBanner(message) {
  if (!flashSuccessBanner) return;

  if (flashSuccessText) {
    flashSuccessText.textContent = message;
  } else {
    flashSuccessBanner.innerHTML = `<i class="fa-solid fa-check-circle"></i> ${message}`;
  }

  flashSuccessBanner.classList.remove('hidden');
  setTimeout(() => {
    flashSuccessBanner.classList.add('hidden');
  }, 4000);
}

function showErrorBanner(message) {
  if (!flashErrorBanner) {
    alert(message);
    return;
  }

  if (flashErrorText) {
    flashErrorText.textContent = message;
  } else {
    flashErrorBanner.innerHTML = `<i class="fa-solid fa-exclamation-circle"></i> ${message}`;
  }

  flashErrorBanner.classList.remove('hidden');
  setTimeout(() => {
    flashErrorBanner.classList.add('hidden');
  }, 5000);
}
</script>

</body>
</html>