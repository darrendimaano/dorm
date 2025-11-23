<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
$darkModeEnabled = false;
?>

<!DOCTYPE html>
<html lang="en" class="<?= $darkModeEnabled ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users - Dormitory Admin</title>
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
<body class="bg-white font-sans flex<?= $darkModeEnabled ? ' dark' : '' ?>">

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-64 transition-all duration-300" id="mainContent">
  <div class="bg-[#FFF5E1] shadow-md flex items-center justify-between px-4 py-3 md:ml-0">
    <button id="menuBtn" class="md:hidden text-[#5C4033] text-xl">
      <i class="fa-solid fa-bars"></i>
    </button>
    <h1 class="font-bold text-lg text-[#5C4033]">Users</h1>
  </div>

  <div class="w-full mt-4 px-3">

    <!-- Management Links Section -->
    <div class="mb-6 rounded-lg p-6 shadow-lg border border-[#C19A6B]" style="background: #FFF5E1;">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <i class="fas fa-users text-2xl mr-3 text-[#C19A6B]"></i>
          <div>
            <h3 class="text-xl font-bold text-[#5C4033]">User Management</h3>
            <p class="text-[#5C4033] opacity-75">Manage registered users and current tenants</p>
          </div>
        </div>
        <div class="flex gap-3">
          <a href="<?= site_url('users/tenants'); ?>" class="text-white px-4 py-2 rounded-lg font-semibold transition duration-300 flex items-center hover:bg-[#B07A4B]" style="background: #C19A6B;">
            <i class="fas fa-bed mr-2"></i>View Tenants/Occupants
          </a>
        </div>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mb-4 mx-auto max-w-3xl rounded-lg border border-green-400 bg-green-100 px-4 py-3 text-sm text-green-800 text-center shadow" data-flash-success role="alert" aria-live="assertive">
        <i class="fa-solid fa-circle-check mr-2"></i><?= html_escape($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mb-4 mx-auto max-w-3xl rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-sm text-red-700 text-center shadow" data-flash-error role="alert" aria-live="assertive">
        <i class="fa-solid fa-triangle-exclamation mr-2"></i><?= html_escape($error) ?>
      </div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div id="addUserForm" class="mb-6 bg-[#FFF5E1] shadow-lg rounded-2xl p-6 border border-[#C19A6B] hidden mx-auto max-w-2xl">
      <h2 class="text-xl font-bold mb-4 text-[#5C4033]">Add New User</h2>
      <form method="POST" action="<?=site_url('users/create')?>">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <input type="text" name="lname" placeholder="Lastname" class="border p-2 rounded w-full" required>
          <input type="text" name="fname" placeholder="Firstname" class="border p-2 rounded w-full" required>
          <input type="email" name="email" placeholder="Email" class="border p-2 rounded w-full" required>
        </div>
        <button type="submit" class="mt-4 bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-5 py-2 rounded-full shadow-md transition-all duration-300">Add User</button>
      </form>
    </div>

    <div class="flex justify-end mb-6">
      <button id="showAddFormBtn" class="inline-flex items-center gap-2 bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-5 py-2 rounded-full shadow-md transition-all duration-300">
        <i class="fa-solid fa-user-plus"></i> Add User
      </button>
    </div>

    <!-- Users Table -->
    <div class="overflow-x-auto rounded-2xl border border-[#C19A6B] shadow bg-[#FFF5E1]">
      <table class="w-full text-center border-collapse">
        <thead>
          <tr class="bg-[#C19A6B] text-white text-sm uppercase tracking-wide">
            <th class="py-3 px-4">ID</th>
            <th class="py-3 px-4">Lastname</th>
            <th class="py-3 px-4">Firstname</th>
            <th class="py-3 px-4">Email</th>
            <th class="py-3 px-4">Action</th>
          </tr>
        </thead>
        <tbody class="text-[#5C4033] text-sm">
          <?php foreach ($users as $user): ?>
          <?php
              $userId = (int) ($user['id'] ?? 0);
              $fname = isset($user['fname']) ? html_escape($user['fname']) : '';
              $lname = isset($user['lname']) ? html_escape($user['lname']) : '';
              $email = isset($user['email']) ? html_escape($user['email']) : '';
              $fullName = trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? ''));
              $fullNameEscaped = html_escape($fullName);
          ?>
          <tr class="hover:bg-[#FFEFD5] transition duration-200" data-user-row="<?= $userId; ?>">
            <td class="py-3 px-4 font-medium" data-col="id"><?= $userId; ?></td>
            <td class="py-3 px-4" data-col="lname"><?= $lname; ?></td>
            <td class="py-3 px-4" data-col="fname"><?= $fname; ?></td>
            <td class="py-3 px-4" data-col="email"><?= $email; ?></td>
            <td class="py-3 px-4 flex justify-center gap-3">
                <a href="#"
                  data-user-id="<?= $userId; ?>"
                  data-user-full="<?= $fullNameEscaped; ?>"
                  data-user-email="<?= $email; ?>"
                 class="js-edit-user bg-[#C19A6B] hover:bg-[#B07A4B] text-white px-3 py-1 rounded-lg shadow flex items-center gap-1 transition duration-200">
                <i class="fa-solid fa-pen-to-square"></i> Update
              </a>
                  <a href="#"
                    data-user-id="<?= $userId; ?>"
                    data-user-name="<?= $fullNameEscaped; ?>"
                    data-delete-url="<?= site_url('users/delete'); ?>"
                    class="js-delete-user bg-red-400 hover:bg-red-500 text-white px-3 py-1 rounded-lg shadow flex items-center gap-1 transition duration-200">
                <i class="fa-solid fa-trash"></i> Delete
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- Update Modal -->
<div id="updateModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40 px-4">
  <div class="relative w-full max-w-lg rounded-2xl border border-[#C19A6B] bg-white p-6 shadow-xl">
    <button type="button" id="closeUpdateModal" class="absolute right-4 top-4 text-[#5C4033] transition hover:text-[#B07A4B]">
      <i class="fa-solid fa-xmark text-xl"></i>
    </button>

    <div class="mb-4 flex items-center gap-2 text-[#5C4033]">
      <i class="fa-solid fa-user-pen text-2xl"></i>
      <h2 class="text-xl font-semibold">Update User</h2>
    </div>

    <form id="updateUserForm" method="POST" class="space-y-4">
      <input type="hidden" name="from_modal" value="1">
      <input type="hidden" name="id" value="">
      <div>
        <label class="mb-1 block font-medium text-[#5C4033]">Full Name</label>
        <input type="text" name="full_name" required class="w-full rounded-xl border border-[#C19A6B] bg-[#FFF5E1] px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
      </div>
      <div>
        <label class="mb-1 block font-medium text-[#5C4033]">Email Address</label>
        <input type="email" name="email" required class="w-full rounded-xl border border-[#C19A6B] bg-[#FFF5E1] px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" id="cancelUpdateBtn" class="rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-200">Cancel</button>
        <button type="submit" class="rounded-full bg-[#C19A6B] px-5 py-2 text-sm font-semibold text-white shadow transition hover:bg-[#B07A4B]">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-40 px-4">
  <div class="relative w-full max-w-md rounded-2xl border border-red-200 bg-white p-6 shadow-xl">
    <button type="button" id="closeDeleteModal" class="absolute right-4 top-4 text-red-500 transition hover:text-red-400">
      <i class="fa-solid fa-xmark text-xl"></i>
    </button>

    <div class="mb-4 flex items-center gap-3 text-red-600">
      <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
        <i class="fa-solid fa-triangle-exclamation text-xl"></i>
      </div>
      <div>
        <h2 class="text-lg font-semibold">Delete User</h2>
        <p class="text-sm text-red-500">This action cannot be undone.</p>
      </div>
    </div>

    <p class="mb-6 text-sm text-[#5C4033]">Are you sure you want to remove <span class="font-semibold" id="deleteUserName"></span> from the system?</p>

    <form id="deleteUserForm" method="POST" class="flex justify-end gap-3">
      <input type="hidden" name="id" value="">
      <button type="button" id="cancelDeleteBtn" class="rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-200">Cancel</button>
      <button type="submit" id="confirmDeleteBtn" class="rounded-full bg-red-500 px-5 py-2 text-sm font-semibold text-white shadow transition hover:bg-red-600">Delete</button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const menuBtn = document.getElementById('menuBtn');
  if (sidebar && menuBtn) {
    menuBtn.addEventListener('click', () => sidebar.classList.toggle('-translate-x-full'));
  }

  const showAddBtn = document.getElementById('showAddFormBtn');
  const addForm = document.getElementById('addUserForm');
  if (showAddBtn && addForm) {
    showAddBtn.addEventListener('click', () => addForm.classList.toggle('hidden'));
  }

  const userForm = document.querySelector('#addUserForm form');
  if (userForm) {
    userForm.addEventListener('submit', function() {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding User...';
        submitButton.disabled = true;

        setTimeout(() => {
          submitButton.innerHTML = originalText;
          submitButton.disabled = false;
        }, 10000);
      }
    });
  }

  const updateModal = document.getElementById('updateModal');
  const updateForm = document.getElementById('updateUserForm');
  const fullNameInput = updateForm ? updateForm.querySelector('input[name="full_name"]') : null;
  const emailInput = updateForm ? updateForm.querySelector('input[name="email"]') : null;
  const idInput = updateForm ? updateForm.querySelector('input[name="id"]') : null;
  const cancelUpdateBtn = document.getElementById('cancelUpdateBtn');
  const closeUpdateModalBtn = document.getElementById('closeUpdateModal');
  const updateEndpoint = '<?= site_url('users/update'); ?>';

  document.querySelectorAll('[data-flash-success], [data-flash-error]').forEach(element => {
    element.classList.add('transition', 'duration-500', 'ease-out');
    setTimeout(() => {
      element.classList.add('opacity-0', 'translate-y-1');
    }, 3500);
    setTimeout(() => {
      element.remove();
    }, 4200);
  });

  const showToast = (message, type = 'success') => {
    const toast = document.createElement('div');
    const baseClasses = 'fixed top-6 left-1/2 z-50 -translate-x-1/2 rounded-full px-6 py-3 shadow-lg text-sm font-semibold flex items-center gap-2 transition duration-300';
    const successClasses = 'bg-green-500 text-white';
    const errorClasses = 'bg-red-500 text-white';
    toast.className = `${baseClasses} ${type === 'success' ? successClasses : errorClasses}`;
    toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'}"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('opacity-0', 'translate-y-3');
    }, 2500);
    setTimeout(() => {
      toast.remove();
    }, 3200);
  };

  if (updateForm) {
    updateForm.action = updateEndpoint;
    updateForm.addEventListener('submit', event => {
      event.preventDefault();

      if (!updateForm.action) {
        showToast('Missing update endpoint.', 'error');
        return;
      }

      if (!idInput || !idInput.value.trim()) {
        showToast('No user selected for update.', 'error');
        return;
      }

      const formData = new FormData(updateForm);
      const submitBtn = updateForm.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Saving...';
      }

      fetch(updateForm.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        let data = null;

        if (contentType.includes('application/json')) {
          data = await response.json();
        } else {
          const text = await response.text();
          throw new Error(text || 'Unexpected response format');
        }

        if (!response.ok) {
          const message = data && data.message ? data.message : 'Unable to update user.';
          throw new Error(message);
        }

        return data;
      })
      .then(data => {
        if (data.status === 'success' && data.data) {
          const { id, fname, lname, email, full_name } = data.data;
          const targetRow = document.querySelector(`[data-user-row="${id}"]`);

          if (targetRow) {
            const fnameCell = targetRow.querySelector('[data-col="fname"]');
            const lnameCell = targetRow.querySelector('[data-col="lname"]');
            const emailCell = targetRow.querySelector('[data-col="email"]');
            const editButton = targetRow.querySelector('.js-edit-user');

            if (fnameCell) fnameCell.textContent = fname;
            if (lnameCell) lnameCell.textContent = lname;
            if (emailCell) emailCell.textContent = email;
            if (editButton) {
              editButton.dataset.userFull = full_name;
              editButton.dataset.userEmail = email;
              editButton.dataset.userId = String(id);
            }
          }

          showToast(data.message || 'User updated successfully.', 'success');
          closeUpdateModal();
        } else {
          const message = data && data.message ? data.message : 'Unable to update user.';
          showToast(message, 'error');
        }
      })
      .catch(error => {
        const message = error instanceof Error ? error.message : 'Network error while updating user.';
        showToast(message, 'error');
      })
      .finally(() => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
      });
    });
  }

  const closeUpdateModal = () => {
    if (!updateModal) return;
    updateModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  };

  if (updateModal && updateForm && fullNameInput && emailInput && idInput) {
    document.querySelectorAll('.js-edit-user').forEach(button => {
      button.addEventListener('click', event => {
        event.preventDefault();
        const userFull = button.dataset.userFull || '';
        const userEmail = button.dataset.userEmail || '';
        const userId = button.dataset.userId || '';

        updateForm.action = updateEndpoint;
        fullNameInput.value = userFull.trim();
        emailInput.value = userEmail.trim();
        idInput.value = userId;

        updateModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        fullNameInput.focus();
      });
    });
  }

  if (cancelUpdateBtn) {
    cancelUpdateBtn.addEventListener('click', event => {
      event.preventDefault();
      closeUpdateModal();
    });
  }

  if (closeUpdateModalBtn) {
    closeUpdateModalBtn.addEventListener('click', event => {
      event.preventDefault();
      closeUpdateModal();
    });
  }

  if (updateModal) {
    updateModal.addEventListener('click', event => {
      if (event.target === updateModal) {
        closeUpdateModal();
      }
    });
  }

  document.addEventListener('keyup', event => {
    if (event.key === 'Escape' && updateModal && !updateModal.classList.contains('hidden')) {
      closeUpdateModal();
    }
  });

  const deleteModal = document.getElementById('deleteModal');
  const deleteForm = document.getElementById('deleteUserForm');
  const deleteName = document.getElementById('deleteUserName');
  const deleteConfirmBtn = document.getElementById('confirmDeleteBtn');
  const deleteCancelBtn = document.getElementById('cancelDeleteBtn');
  const deleteCloseBtn = document.getElementById('closeDeleteModal');
  const deleteIdInput = deleteForm ? deleteForm.querySelector('input[name="id"]') : null;

  const closeDeleteModal = () => {
    if (!deleteModal) return;
    deleteModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    if (deleteForm) {
      deleteForm.action = '';
    }
    if (deleteIdInput) {
      deleteIdInput.value = '';
    }
    if (deleteConfirmBtn) {
      deleteConfirmBtn.disabled = false;
      deleteConfirmBtn.innerHTML = 'Delete';
    }
  };

  if (deleteModal && deleteForm && deleteName && deleteConfirmBtn) {
    document.querySelectorAll('.js-delete-user').forEach(button => {
      button.addEventListener('click', event => {
        event.preventDefault();
        const targetUrl = button.dataset.deleteUrl || '';
        const targetId = button.dataset.userId || '';
        const targetName = button.dataset.userName ? button.dataset.userName.trim() : 'this user';

        if (!targetUrl) {
          showToast('Missing delete endpoint.', 'error');
          return;
        }

        if (!targetId || !deleteIdInput) {
          showToast('Missing user information.', 'error');
          return;
        }

        deleteForm.action = targetUrl;
        deleteIdInput.value = targetId;
        deleteName.textContent = targetName;
        deleteConfirmBtn.disabled = false;
        deleteModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
      });
    });
  }

  if (deleteCancelBtn) {
    deleteCancelBtn.addEventListener('click', event => {
      event.preventDefault();
      closeDeleteModal();
    });
  }

  if (deleteCloseBtn) {
    deleteCloseBtn.addEventListener('click', event => {
      event.preventDefault();
      closeDeleteModal();
    });
  }

  if (deleteModal) {
    deleteModal.addEventListener('click', event => {
      if (event.target === deleteModal) {
        closeDeleteModal();
      }
    });
  }

  if (deleteForm && deleteConfirmBtn) {
    deleteForm.addEventListener('submit', event => {
      event.preventDefault();

      if (!deleteForm.action) {
        showToast('Missing delete endpoint.', 'error');
        return;
      }

      deleteConfirmBtn.disabled = true;
      deleteConfirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Deleting...';

      const formData = new FormData(deleteForm);

      fetch(deleteForm.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        let data = null;

        if (contentType.includes('application/json')) {
          data = await response.json();
        } else {
          const text = await response.text();
          throw new Error(text || 'Unexpected response format');
        }

        if (!response.ok) {
          const message = data && data.message ? data.message : 'Unable to delete user.';
          throw new Error(message);
        }

        return data;
      })
      .then(data => {
        if (data.status === 'success' && data.data) {
          const { id } = data.data;
          const targetRow = document.querySelector(`[data-user-row="${id}"]`);
          if (targetRow) {
            targetRow.remove();
          }

          showToast(data.message || 'User deleted successfully.', 'success');
          closeDeleteModal();
        } else {
          const message = data && data.message ? data.message : 'Unable to delete user.';
          showToast(message, 'error');
        }
      })
      .catch(error => {
        const message = error instanceof Error ? error.message : 'Network error while deleting user.';
        showToast(message, 'error');
      })
      .finally(() => {
        deleteConfirmBtn.disabled = false;
        deleteConfirmBtn.innerHTML = 'Delete';
      });
    });
  }
});
</script>

</body>
</html>
