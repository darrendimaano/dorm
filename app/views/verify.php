<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Dormitory Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #FFF5E1 0%, #F5E6D3 100%);
        }
        .pin-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #D2B48C;
            border-radius: 8px;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        .pin-input:focus {
            border-color: #C19A6B;
            box-shadow: 0 0 0 3px rgba(193, 154, 107, 0.2);
            outline: none;
        }
        .pin-input.filled {
            border-color: #5C4033;
            background-color: #FFF5E1;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="bg-[#C19A6B] w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-envelope text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-[#5C4033] mb-2">Email Verification</h1>
            <p class="text-[#5C4033] opacity-75">We sent a verification PIN to your email</p>
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
                <div class="mb-6">
                    <label class="block text-[#5C4033] font-semibold mb-4 text-center">
                        Enter 6-Digit Verification PIN
                    </label>
                    
                    <!-- PIN Input Fields -->
                    <div class="flex justify-center mb-4">
                        <input type="text" maxlength="1" class="pin-input" data-index="0" autocomplete="off">
                        <input type="text" maxlength="1" class="pin-input" data-index="1" autocomplete="off">
                        <input type="text" maxlength="1" class="pin-input" data-index="2" autocomplete="off">
                        <input type="text" maxlength="1" class="pin-input" data-index="3" autocomplete="off">
                        <input type="text" maxlength="1" class="pin-input" data-index="4" autocomplete="off">
                        <input type="text" maxlength="1" class="pin-input" data-index="5" autocomplete="off">
                    </div>
                    
                    <!-- Hidden input for form submission -->
                    <input type="hidden" name="pin" id="hiddenPin">
                </div>

                <button type="submit" 
                        class="w-full bg-[#C19A6B] hover:bg-[#5C4033] text-white font-semibold py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center gap-2"
                        id="verifyBtn" disabled>
                    <i class="fa-solid fa-check-circle"></i>
                    Verify Email
                </button>
            </form>

            <!-- Resend Section -->
            <div class="mt-6 text-center">
                <p class="text-gray-600 mb-3">Didn't receive the email?</p>
                <div class="flex flex-col gap-3">
                    <a href="<?= site_url('auth/resend_verification') ?>" 
                       class="text-[#C19A6B] hover:text-[#5C4033] font-semibold transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i>
                        Resend Verification PIN
                    </a>
                    <a href="<?= site_url('auth/register') ?>" 
                       class="text-gray-500 hover:text-gray-700 text-sm transition">
                        ← Back to Registration
                    </a>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-[#FFF5E1] border border-[#E5D3B3] rounded-lg p-4 mt-6">
            <h3 class="font-semibold text-[#5C4033] mb-2 flex items-center gap-2">
                <i class="fa-solid fa-info-circle"></i>
                Help & Support
            </h3>
            <div class="text-sm text-[#5C4033] opacity-75 space-y-2">
                <p>• Check your spam/junk folder if you don't see the email</p>
                <p>• The PIN expires in 1 hour for security</p>
                <p>• Contact us if you need assistance: 09517394938</p>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pinInputs = document.querySelectorAll('.pin-input');
    const hiddenPin = document.getElementById('hiddenPin');
    const verifyBtn = document.getElementById('verifyBtn');
    
    // Auto-focus first input
    pinInputs[0].focus();
    
    pinInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Update visual state
            if (this.value) {
                this.classList.add('filled');
                // Move to next input
                if (index < pinInputs.length - 1) {
                    pinInputs[index + 1].focus();
                }
            } else {
                this.classList.remove('filled');
            }
            
            updateHiddenPin();
            updateButtonState();
        });
        
        input.addEventListener('keydown', function(e) {
            // Handle backspace
            if (e.key === 'Backspace' && !this.value && index > 0) {
                pinInputs[index - 1].focus();
                pinInputs[index - 1].value = '';
                pinInputs[index - 1].classList.remove('filled');
                updateHiddenPin();
                updateButtonState();
            }
            
            // Handle arrow keys
            if (e.key === 'ArrowLeft' && index > 0) {
                pinInputs[index - 1].focus();
            }
            if (e.key === 'ArrowRight' && index < pinInputs.length - 1) {
                pinInputs[index + 1].focus();
            }
        });
        
        // Handle paste
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
            
            if (pastedData.length === 6) {
                pinInputs.forEach((inp, i) => {
                    inp.value = pastedData[i] || '';
                    if (inp.value) {
                        inp.classList.add('filled');
                    }
                });
                updateHiddenPin();
                updateButtonState();
                pinInputs[5].focus();
            }
        });
    });
    
    function updateHiddenPin() {
        const pin = Array.from(pinInputs).map(input => input.value).join('');
        hiddenPin.value = pin;
    }
    
    function updateButtonState() {
        const allFilled = Array.from(pinInputs).every(input => input.value !== '');
        verifyBtn.disabled = !allFilled;
        
        if (allFilled) {
            verifyBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            verifyBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
    
    // Initialize button state
    updateButtonState();
});
</script>

</body>
</html>