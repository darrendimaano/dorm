<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Room - Dormitory Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="background: #FFF5E1;" class="min-h-screen">

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-6 mb-6 border" style="border-color: #C19A6B;">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-[#5C4033]">
                        <i class="fa-solid fa-edit text-[#C19A6B] mr-3"></i>Update Dormitory Room
                    </h1>
                    <p class="text-[#5C4033] opacity-75 mt-2">Edit room information and availability</p>
                </div>
                <a href="<?= 
                    strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/rooms') !== false ? 
                    site_url('admin/rooms') : 
                    site_url('rooms') 
                ?>" class="inline-flex items-center px-4 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition duration-300">
                    <i class="fa-solid fa-arrow-left mr-2"></i>Back to Rooms
                </a>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 text-center shadow border border-red-200">
                <i class="fa-solid fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 text-center shadow border border-green-200">
                <i class="fa-solid fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Update Form -->
        <div style="background: #FFF5E1;" class="rounded-lg shadow-lg p-6 border" style="border-color: #C19A6B;">
            <?php 
            $current_url = $_SERVER['REQUEST_URI'] ?? '';
            $form_action = site_url(trim($current_url, '/'));
            ?>
            <form method="POST" action="<?= $form_action ?>" enctype="multipart/form-data" class="max-w-2xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Room Number -->
                    <div class="md:col-span-1">
                        <label for="room_number" class="block text-sm font-medium text-[#5C4033] mb-2">
                            <i class="fa-solid fa-door-open text-[#C19A6B] mr-1"></i>Room Number
                        </label>
                        <input type="text" id="room_number" name="room_number" required
                               value="<?= htmlspecialchars($room['room_number']); ?>"
                               class="w-full px-3 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]"
                               placeholder="e.g. 101, A-12, Room 5">
                    </div>

                    <!-- Number of Beds -->
                    <div class="md:col-span-1">
                        <label for="beds" class="block text-sm font-medium text-[#5C4033] mb-2">
                            <i class="fa-solid fa-bed text-[#C19A6B] mr-1"></i>Number of Beds
                        </label>
                        <input type="number" id="beds" name="beds" min="1" required
                               value="<?= htmlspecialchars($room['beds']); ?>"
                               class="w-full px-3 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]"
                               placeholder="1">
                    </div>

                    <!-- Available Slots -->
                    <div class="md:col-span-1">
                        <label for="available" class="block text-sm font-medium text-[#5C4033] mb-2">
                            <i class="fa-solid fa-users text-[#C19A6B] mr-1"></i>Available Slots
                        </label>
                        <input type="number" id="available" name="available" min="0" required
                               value="<?= htmlspecialchars($room['available']); ?>"
                               class="w-full px-3 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]"
                               placeholder="0">
                    </div>

                    <!-- Payment Amount -->
                    <div class="md:col-span-1">
                        <label for="payment" class="block text-sm font-medium text-[#5C4033] mb-2">
                            <i class="fa-solid fa-peso-sign text-[#C19A6B] mr-1"></i>Monthly Payment (PHP)
                        </label>
                        <input type="number" id="payment" name="payment" min="0" step="0.01" required
                               value="<?= htmlspecialchars($room['payment']); ?>"
                               class="w-full px-3 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]"
                               placeholder="5000.00">
                    </div>
                    
                    <!-- Picture Upload -->
                    <div class="md:col-span-2">
                        <label for="picture" class="block text-sm font-medium text-[#5C4033] mb-2">
                            <i class="fa-solid fa-image text-[#C19A6B] mr-1"></i>Room Picture (Optional)
                        </label>
                        <input type="file" id="picture" name="picture" accept="image/*"
                               class="w-full px-3 py-2 border border-[#C19A6B] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C19A6B] bg-[#FFF5E1]">
                        <input type="hidden" name="existing_picture" value="<?= htmlspecialchars($room['picture'] ?? ''); ?>">
                        <?php if (!empty($room['picture'])): ?>
                            <p class="text-sm text-[#5C4033] opacity-75 mt-1">Current: <?= basename($room['picture']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4 mt-8">
                    <a href="<?= 
                        strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/rooms') !== false ? 
                        site_url('admin/rooms') : 
                        site_url('rooms') 
                    ?>" 
                       class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition duration-300">
                        <i class="fa-solid fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-2 bg-[#C19A6B] text-white rounded-lg hover:bg-[#5C4033] transition duration-300">
                        <i class="fa-solid fa-save mr-2"></i>Update Room
                    </button>
                </div>
            </form>
        </div>
    </div>

<script>
// Handle form submission with loading state
document.addEventListener('DOMContentLoaded', function() {
  const updateForm = document.querySelector('form');
  if (updateForm) {
    updateForm.addEventListener('submit', function(e) {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating Room...';
        submitButton.disabled = true;
        
        // Re-enable button after timeout as fallback
        setTimeout(() => {
          submitButton.innerHTML = originalText;
          submitButton.disabled = false;
        }, 15000);
      }
    });
  }
});
</script>

</body>
</html>
