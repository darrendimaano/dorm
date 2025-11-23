<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Verification - Dormitory Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-to-br from-[#FFF5E1] to-[#F5E6D3] min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="bg-[#C19A6B] w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-shield-check text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-[#5C4033] mb-2">Account Verification</h1>
            <p class="text-[#5C4033] opacity-75">Complete the security check below</p>
            <p class="text-[#C19A6B] font-semibold"><?= htmlspecialchars($email) ?></p>
        </div>

        <!-- Verification Form -->
        <div class="bg-white rounded-lg shadow-lg p-6 border border-[#E5D3B3]">
            <!-- Success/Error Messages -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-lg mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-lg mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= site_url('auth/verify') ?>" id="verificationForm">
                <!-- PIN Verification Section -->
                <div class="mb-6">
                    <label class="block text-[#5C4033] font-semibold mb-4 text-center">
                        <i class="fa-solid fa-key mr-2"></i>
                        Enter Verification PIN
                    </label>
                    <div class="bg-[#FFF5E1] border-2 border-[#C19A6B] rounded-lg p-6 text-center mb-4">
                        <p class="text-[#5C4033] text-lg mb-2">A 6-digit PIN was sent to your email address.</p>
                        <p class="text-[#5C4033] text-3xl font-bold font-mono">Check your inbox for the PIN.</p>
                    </div>
                    <input type="text"
                           name="pin"
                           id="pinInput"
                           maxlength="6"
                           pattern="\d{6}"
                           class="w-full px-4 py-3 rounded-lg border-2 border-[#E5D3B3] focus:border-[#C19A6B] focus:outline-none text-center text-2xl font-mono"
                           placeholder="Enter 6-digit PIN"
                           required
                           autocomplete="off">
                </div>

                <button type="submit"
                        class="w-full bg-[#C19A6B] hover:bg-[#5C4033] text-white font-semibold py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center gap-2"
                        id="verifyBtn">
                    <i class="fa-solid fa-check-circle"></i>
                    Verify Account
                </button>
            </form>

            <!-- Help Section -->
            <div class="mt-6 p-4 bg-[#FFF5E1] rounded-lg border border-[#E5D3B3]">
                <h4 class="text-[#5C4033] font-semibold mb-2">
                    <i class="fa-solid fa-lightbulb mr-2"></i>
                    Verification Note
                </h4>
                <div class="text-sm text-[#5C4033] opacity-75 space-y-1">
                    <p>• Enter the PIN sent to your email to verify your account.</p>
                    <p>• The PIN is valid for 1 hour. You can request a new one if expired.</p>
                    <p>• After verification, you can immediately log in to your account.</p>
                    <p>• Contact support if you need assistance: 09517394938</p>
                </div>
            </div>

            <!-- Back to Registration -->
            <div class="text-center mt-6">
                <p class="text-sm text-[#5C4033] opacity-75 mb-2">Need to register a different email?</p>
                <a href="<?= site_url('auth/register') ?>" 
                   class="text-[#C19A6B] hover:text-[#5C4033] font-semibold transition duration-300">
                    <i class="fa-solid fa-arrow-left mr-1"></i>
                    Back to Registration
                </a>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pinInput = document.getElementById('pinInput');
    if (pinInput) {
        pinInput.focus();
        pinInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('verificationForm').submit();
            }
        });
    }
});
</script>

</body>
</html>