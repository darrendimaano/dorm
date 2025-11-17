<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Room - Dormitory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-plus text-blue-500 mr-3"></i>Add New Dormitory Room
                    </h1>
                    <p class="text-gray-600 mt-2">Create a new room in the dormitory system</p>
                </div>
                <a href="<?= site_url('rooms'); ?>" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Rooms
                </a>
            </div>
        </div>

        <!-- Create Form -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <form method="POST" action="<?= site_url('rooms/create'); ?>" class="w-full px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Room Number -->
                    <div class="md:col-span-1">
                        <label for="room_number" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-door-open text-blue-500 mr-1"></i>Room Number
                        </label>
                        <input type="text" id="room_number" name="room_number" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g. 101, A-12, Room 5">
                    </div>

                    <!-- Number of Beds -->
                    <div class="md:col-span-1">
                        <label for="beds" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-bed text-blue-500 mr-1"></i>Number of Beds
                        </label>
                        <input type="number" id="beds" name="beds" min="1" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="1">
                    </div>

                    <!-- Available Slots -->
                    <div class="md:col-span-1">
                        <label for="available" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-users text-blue-500 mr-1"></i>Available Slots
                        </label>
                        <input type="number" id="available" name="available" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="1">
                    </div>

                    <!-- Payment Amount -->
                    <div class="md:col-span-1">
                        <label for="payment" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-peso-sign text-green-500 mr-1"></i>Monthly Payment (PHP)
                        </label>
                        <input type="number" id="payment" name="payment" min="0" step="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="5000.00">
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4 mt-8">
                    <a href="<?= site_url('rooms'); ?>" 
                       class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition duration-300">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-300">
                        <i class="fas fa-save mr-2"></i>Create Room
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
