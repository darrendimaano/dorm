<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#FFF5E1] flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md border border-[#C19A6B]">
    <h1 class="text-2xl font-bold mb-6 text-center text-[#5C4033]">Login</h1>

    <?php if(!empty($success)): ?>
        <div class="p-3 rounded mb-4 border" style="background: #FFF5E1; border-color: #C19A6B; color: #5C4033;">
            <i class="fas fa-check-circle mr-2" style="color: #C19A6B;"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($error)): ?>
        <div class="p-3 rounded mb-4 border" style="background: #FFF0F0; border-color: #DC6B6B; color: #B91C1C;">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            <?php if (strpos($error, 'verify your email') !== false): ?>
                <div class="mt-2 pt-2 border-t border-red-200">
                    <a href="<?= site_url('auth/register') ?>" class="text-sm text-red-600 hover:text-red-800 underline">
                        <i class="fa-solid fa-envelope"></i> Resend verification email
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="flex flex-col gap-4">
        <input type="email" name="email" placeholder="Email" required 
               class="px-4 py-2 border rounded border-[#C19A6B] focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
        <input type="password" name="password" placeholder="Password" required 
               class="px-4 py-2 border rounded border-[#C19A6B] focus:outline-none focus:ring-2 focus:ring-[#C19A6B]">
        <button type="submit" 
                class="bg-[#C19A6B] text-white py-2 rounded hover:bg-[#B07A4B] transition">
            Login
        </button>
    </form>

    <p class="text-center text-sm text-[#5C4033] mt-4">
        Don’t have an account?
        <a href="<?= site_url('auth/register') ?>" 
           class="text-[#C19A6B] hover:underline font-semibold">
           Register here
        </a>
    </p>
</div>

</body>
</html>
