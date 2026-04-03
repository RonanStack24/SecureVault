<?php
/**
 * SecureVault - Master Gateway (Login/Register)
 */

require_once 'config.php';
require_once 'auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn() && verifySession()) {
    header('Location: dashboard.php');
    exit;
}

$flash = getFlashMessage();
$message = $flash['message'];
$messageType = $flash['type'];
$showRegister = isset($_GET['register']) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault - Master Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#1dd1a1',
                        'primary-dark': '#10ac84',
                        'dark-bg': '#0f1419',
                        'dark-darker': '#0a0d10',
                        'dark-card': '#1a1f26',
                        'dark-border': '#2a3038',
                    }
                }
            }
        }
    </script>
    <style>
        body { color: #ffffff; }
        @keyframes slideFade {
            0% { opacity: 0; transform: translateY(10px) scale(0.99); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .form-animate {
            animation: slideFade 0.4s ease-out forwards;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .animate-edge {
            background-size: 200% 200%;
            animation: gradientMove 4s ease infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-dark-darker to-dark-bg min-h-screen flex items-center justify-center px-2 sm:px-4 py-4 sm:py-8">
    <div class="w-full max-w-md relative group">
        <!-- Animated Flowing Edge -->
        <div class="absolute -inset-[2px] rounded-xl bg-gradient-to-r from-primary via-transparent to-primary-dark opacity-80 blur-[2px] animate-edge"></div>
        <div class="absolute -inset-[1px] rounded-xl bg-gradient-to-r from-primary via-[#0f1419] to-primary-dark opacity-100 animate-edge"></div>
        
        <div class="bg-dark-card rounded-xl p-4 sm:p-6 md:p-10 shadow-2xl relative z-10">
            <div class="text-center mb-6 sm:mb-8 md:mb-10">
                <img src="logo.svg" alt="Secure Vault Logo" class="w-12 sm:w-16 md:w-20 h-12 sm:h-16 md:h-20 mx-auto mb-3 sm:mb-4 md:mb-5">
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold mb-2">Master Gateway</h1>
                <p class="text-gray-400 text-xs sm:text-sm">Authentication required to decrypt local vault shards.</p>
            </div>

            <?php if ($message): ?>
                <div class="mb-4 sm:mb-6 p-2 sm:p-3 rounded-lg border-l-4 text-xs sm:text-sm md:text-base <?php echo $messageType === 'error' ? 'bg-red-900/20 border-red-500 text-red-400' : 'bg-emerald-900/20 border-emerald-500 text-emerald-400'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="loginForm" class="form-animate" style="display: <?php echo $showRegister ? 'none' : 'block'; ?>">
                <form method="POST" action="api/auth.php?action=login" id="loginFormElement" class="space-y-3 sm:space-y-4 md:space-y-5">
                    <div>
                        <label for="login-username" class="text-xs font-bold text-primary uppercase tracking-wider block mb-1 sm:mb-2">USERNAME / EMAIL</label>
                        <input type="text" id="login-username" name="username" placeholder="Enter username or email" class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-xs sm:text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                    </div>

                    <div>
                        <label for="login-password" class="text-xs font-bold text-primary uppercase tracking-wider block mb-1 sm:mb-2">MASTER KEY</label>
                        <div class="relative">
                            <input type="password" id="login-password" name="password" placeholder="Enter your private sequence..." class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-xs sm:text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                            <button type="button" class="absolute right-2 sm:right-3 top-1.5 sm:top-2 md:top-3 text-gray-400 hover:text-white text-xs" onclick="togglePassword('login-password')">Show</button>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-4 sm:mt-5 md:mt-6 px-4 sm:px-6 py-2 sm:py-3 bg-primary hover:bg-primary-dark text-dark-bg font-bold rounded-lg transition-all transform hover:scale-105 text-xs sm:text-sm md:text-base">Unlock Vault</button>
                </form>

                <div class="border-t border-dark-border mt-4 sm:mt-6 pt-4 sm:pt-6">
                    <p class="text-center text-gray-400 text-xs sm:text-sm">Don't have an account? <a href="javascript:void(0)" onclick="toggleForm()" class="text-primary hover:underline cursor-pointer font-bold">Create one</a></p>
                </div>
            </div>

            <!-- Register Form -->
            <div id="registerForm" class="form-animate" style="display: <?php echo $showRegister ? 'block' : 'none'; ?>">
                <form method="POST" action="api/auth.php?action=register" id="registerFormElement" class="space-y-3 sm:space-y-4 md:space-y-5">
                    <div>
                        <label for="register-username" class="text-xs font-bold text-primary uppercase tracking-wider block mb-1 sm:mb-2">USERNAME</label>
                        <input type="text" id="register-username" name="username" placeholder="Choose a username" class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-xs sm:text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" minlength="3" required>
                    </div>

                    <div>
                        <label for="register-email" class="text-xs font-bold text-primary uppercase tracking-wider block mb-1 sm:mb-2">EMAIL</label>
                        <input type="email" id="register-email" name="email" placeholder="your@email.com" class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-xs sm:text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                    </div>

                    <div>
                        <label for="register-password" class="text-xs font-bold text-primary uppercase tracking-wider block mb-1 sm:mb-2">MASTER KEY</label>
                        <div class="relative mb-2">
                            <input type="password" id="register-password" name="password" placeholder="Create a strong sequence..." class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-xs sm:text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                            <button type="button" class="absolute right-2 sm:right-3 top-1.5 sm:top-2 md:top-3 text-gray-400 hover:text-white text-xs" onclick="togglePassword('register-password')">Show</button>
                        </div>
                        <div class="mt-1.5 h-1 bg-dark-border rounded-full overflow-hidden">
                            <div class="strength-bar h-full bg-red-500 transition-all" id="strengthBar" style="width: 0%"></div>
                        </div>
                        <small id="strengthText" class="text-gray-400 text-xs mt-1 block">Password strength: Weak</small>
                    </div>

                    <div>
                        <label for="register-confirm" class="text-xs font-bold text-primary uppercase tracking-wider block mb-1 sm:mb-2">CONFIRM KEY</label>
                        <div class="relative">
                            <input type="password" id="register-confirm" name="confirm_password" placeholder="Confirm your sequence..." class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-3 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-xs sm:text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                            <button type="button" class="absolute right-2 sm:right-3 top-1.5 sm:top-2 md:top-3 text-gray-400 hover:text-white text-xs" onclick="togglePassword('register-confirm')">Show</button>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-4 sm:mt-5 md:mt-6 px-4 sm:px-6 py-2 sm:py-3 bg-primary hover:bg-primary-dark text-dark-bg font-bold rounded-lg transition-all transform hover:scale-105 text-xs sm:text-sm md:text-base">Secure Account</button>
                </form>

                <div class="border-t border-dark-border mt-4 sm:mt-6 pt-4 sm:pt-6">
                    <p class="text-center text-gray-400 text-xs sm:text-sm">Already have an account? <a href="javascript:void(0)" onclick="toggleForm()" class="text-primary hover:underline cursor-pointer font-bold">Sign in</a></p>
                </div>
            </div>

            <div class="bg-red-900/20 border border-red-500 rounded-lg p-2 sm:p-3 md:p-4 mt-6 sm:mt-8 text-red-400 text-xs sm:text-sm text-center">
                ⚠️ Your Master Key is never stored on our servers. Lose it, and you lose your data. No recovery is possible.
            </div>

            <div class="text-center mt-6 sm:mt-8 space-y-1">
                <small class="text-gray-500 uppercase tracking-widest text-xs block">ENCRYPTED PERSONAL VAULT</small>
                <small class="text-primary/70 uppercase tracking-widest text-[10px] block">Developed by Ronan Antoque</small>
            </div>
        </div>
    </div>

    <script src="js/auth.js"></script>
    <script>
        // Handle form submission
        document.getElementById('loginFormElement').addEventListener('submit', handleLoginSubmit);
        document.getElementById('registerFormElement').addEventListener('submit', handleRegisterSubmit);

        function handleLoginSubmit(e) {
            e.preventDefault();
            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;

            fetch('api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    username: username,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Login failed: ' + error, 'error');
            });
        }

        function handleRegisterSubmit(e) {
            e.preventDefault();
            const username = document.getElementById('register-username').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm').value;

            fetch('api/auth.php?action=register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    username: username,
                    email: email,
                    password: password,
                    confirm_password: confirmPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Registration successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = '?';
                    }, 1500);
                } else {
                    const errors = data.errors || (data.data && data.data.errors);
                    const message = errors ? errors.join(', ') : (data.message || 'Registration failed');
                    showAlert(message, 'error');
                }
            })
            .catch(error => {
                showAlert('Registration failed: ' + error, 'error');
            });
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            const bgClass = type === 'error' ? 'bg-red-900/20 border-red-500 text-red-400' : 'bg-emerald-900/20 border-emerald-500 text-emerald-400';
            alertDiv.className = `p-3 rounded-lg border-l-4 ${bgClass} mb-6`;
            alertDiv.textContent = message;
            
            const card = document.querySelector('.bg-dark-card');
            const firstChild = card.querySelector('div:first-child');
            if (firstChild) {
                card.insertBefore(alertDiv, firstChild.nextSibling);
            } else {
                card.insertBefore(alertDiv, card.firstChild);
            }
            
            setTimeout(() => alertDiv.remove(), 5000);
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        // Password strength checker
        document.getElementById('register-password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            const feedback = [];

            if (password.length >= 8) strength += 20;
            if (password.length >= 12) strength += 10;
            if (/[a-z]/.test(password)) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^A-Za-z0-9]/.test(password)) strength += 15;

            strengthBar.style.width = strength + '%';
            
            if (strength < 30) {
                strengthBar.style.backgroundColor = '#dc3545';
                strengthText.textContent = 'Password strength: Weak';
            } else if (strength < 60) {
                strengthBar.style.backgroundColor = '#ffc107';
                strengthText.textContent = 'Password strength: Fair';
            } else if (strength < 85) {
                strengthBar.style.backgroundColor = '#28a745';
                strengthText.textContent = 'Password strength: Good';
            } else {
                strengthBar.style.backgroundColor = '#20c997';
                strengthText.textContent = 'Password strength: Strong';
            }
        });
    </script>
</body>
</html>
