<?php
/**
 * SecureVault Dashboard - Clean & Responsive
 */

require_once 'config.php';
require_once 'auth.php';
require_once 'accounts.php';

requireLogin();

$user = getCurrentUser();
$userId = getCurrentUserId();
$categories = getUserCategories($userId);
$stats = getVaultStats($userId);

$categoryId = isset($_GET['category']) && $_GET['category'] !== 'all' ? (int)$_GET['category'] : 0;
$accounts = $categoryId ? getAccountsByCategory($userId, $categoryId) : getUserAccounts($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault - Dashboard</title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { color: #ffffff; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        @media (max-width: 768px) {
            .sidebar-translate { transform: translateX(-100%); }
            .sidebar-translate.active { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-dark-bg text-white">
    <div class="flex h-screen bg-dark-bg">
        <!-- Mobile Menu Toggle -->
        <button id="mobileMenuToggle" class="fixed top-4 left-4 z-40 md:hidden p-2 bg-dark-border rounded-lg hover:bg-primary/20 transition-all" onclick="toggleSidebar()">☰</button>

        <!-- Backdrop for Mobile -->
        <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-20 md:hidden hidden" onclick="closeSidebar()"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar-translate fixed md:relative md:translate-x-0 left-0 top-0 w-72 h-screen bg-dark-darker border-r border-dark-border flex flex-col z-30 transition-transform duration-300">
            <!-- Header -->
            <div class="p-5 border-b border-dark-border">
                <div class="flex items-center gap-3 mb-1">
                    <img src="logo.svg" alt="SecureVault" class="w-10 h-10">
                    <div>
                        <div class="font-bold text-base">SecureVault</div>
                        <small class="text-gray-500 text-xs">v1.0</small>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto hide-scrollbar py-4">
                <!-- All Items -->
                <a href="dashboard.php" class="mx-2 px-4 py-3 rounded-lg mb-2 flex items-center gap-3 text-gray-300 hover:bg-primary/10 hover:text-primary transition-all <?php echo !$categoryId ? 'bg-primary/15 text-primary' : ''; ?>">
                    <span class="text-lg">📁</span>
                    <span class="font-medium text-sm">All Items</span>
                    <span class="ml-auto text-xs bg-dark-border px-2 py-1 rounded-full text-gray-400"><?php echo $stats['total']; ?></span>
                </a>

                <!-- Categories -->
                <div class="mt-6 mb-3 px-5">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Categories</span>
                </div>
                
                <?php foreach ($categories as $cat): 
                    $count = 0;
                    foreach ($stats['by_category'] as $s) {
                        if ($s['name'] === $cat['name']) {
                            $count = $s['count'];
                            break;
                        }
                    }
                ?>
                    <a href="?category=<?php echo $cat['id']; ?>" class="mx-2 px-4 py-3 rounded-lg mb-2 flex items-center gap-3 text-gray-300 hover:bg-primary/10 hover:text-primary transition-all <?php echo $categoryId === $cat['id'] ? 'bg-primary/15 text-primary' : ''; ?>">
                        <span class="text-lg"><?php echo getCategoryEmoji($cat['name']); ?></span>
                        <span class="font-medium text-sm flex-1"><?php echo htmlspecialchars($cat['name']); ?></span>
                        <span class="text-xs bg-dark-border px-2 py-1 rounded-full text-gray-400"><?php echo $count; ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- User Section -->
            <div class="p-4 border-t border-dark-border space-y-3">
                <div class="bg-dark-card/50 rounded-lg p-3">
                    <div class="text-xs font-bold text-gray-500 uppercase mb-1">Logged in as</div>
                    <div class="text-sm font-semibold truncate"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="flex gap-2">
                    <button onclick="openSettingsModal()" class="flex-1 px-3 py-2 bg-dark-border hover:bg-dark-border/80 rounded-lg text-sm font-medium transition-all">⚙️ Settings</button>
                    <button onclick="logout()" class="flex-1 px-3 py-2 bg-dark-border hover:bg-red-900/20 hover:text-red-400 rounded-lg text-sm font-medium transition-all">🚪 Logout</button>
                </div>
                <div class="text-center pt-2 pb-1">
                    <span class="text-[10px] text-gray-500 uppercase tracking-wider">Developed by Ronan Antoque</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Header Bar -->
            <header class="bg-dark-darker border-b border-dark-border px-4 md:px-8 py-4 flex items-center justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl md:text-3xl font-bold">Vault</h1>
                    <p class="text-sm text-gray-400">Manage your <?php echo $stats['total']; ?> secure accounts</p>
                </div>
                <button onclick="openAddAccountModal()" class="px-4 md:px-6 py-2 bg-primary hover:bg-primary-dark text-dark-bg font-semibold rounded-lg transition-all whitespace-nowrap text-sm md:text-base">
                    + Add
                </button>
            </header>

            <!-- Content Area -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Toolbar -->
                <div class="px-4 md:px-8 py-3 md:py-4 bg-dark-darker/50 border-b border-dark-border">
                    <input type="text" id="searchInput" placeholder="Search accounts..." onkeyup="filterAccounts()" 
                        class="w-full max-w-md px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:bg-primary/10 focus:border-primary transition-all">
                </div>

                <!-- Accounts Grid -->
                <div class="flex-1 overflow-y-auto">
                    <div class="px-4 md:px-8 py-6">
                        <?php if (empty($accounts)): ?>
                            <div class="flex flex-col items-center justify-center py-20 text-center">
                                <div class="text-7xl mb-4 opacity-50">📭</div>
                                <h2 class="text-2xl font-bold mb-2">No Accounts Yet</h2>
                                <p class="text-gray-400 mb-6">Start by adding your first secured account</p>
                                <button onclick="openAddAccountModal()" class="px-6 py-3 bg-primary hover:bg-primary-dark text-dark-bg font-bold rounded-lg transition-all">
                                    Add Account
                                </button>
                            </div>
                        <?php else: ?>
                            <div id="accountsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($accounts as $index => $account): ?>
                                    <div class="bg-dark-card border border-dark-border rounded-lg overflow-hidden hover:border-primary hover:shadow-lg hover:shadow-primary/20 transition-all transform hover:-translate-y-1 animate-fade-in"
                                        style="opacity: 0; animation-delay: <?php echo min($index * 0.05, 0.5); ?>s; animation-fill-mode: forwards;"
                                        data-account-id="<?php echo $account['id']; ?>" 
                                        data-service="<?php echo strtolower($account['service_name']); ?>">
                                        
                                        <!-- Card Header -->
                                        <div class="px-5 py-4 border-b border-dark-border/50 flex justify-between items-start">
                                            <div class="flex-1 min-w-0">
                                                <h3 class="font-bold text-base truncate px-2 -ml-2"><?php echo htmlspecialchars($account['service_name']); ?></h3>
                                                <div class="cursor-pointer hover:bg-white/5 active:bg-white/10 rounded-lg p-3 md:p-2 -ml-3 md:-ml-2 mt-1 flex items-center gap-2 group w-max transition-colors" 
                                                   title="Click to copy username"
                                                   onclick="copyUserClipboard('<?php echo htmlspecialchars(addslashes($account['username']), ENT_QUOTES); ?>')">
                                                    <span class="text-sm md:text-xs text-gray-400 group-hover:text-white truncate"><?php echo htmlspecialchars($account['username']); ?></span>
                                                    <span class="opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity text-base md:text-sm">📋</span>
                                                </div>
                                            </div>
                                            <div class="flex gap-1 ml-2 flex-shrink-0">
                                                <button onclick="editAccount(<?php echo $account['id']; ?>)" class="p-2 hover:bg-dark-border rounded-lg transition-all" title="Edit">✏️</button>
                                                <button onclick="deleteAccount(<?php echo $account['id']; ?>)" class="p-2 hover:bg-red-900/20 hover:text-red-400 rounded-lg transition-all" title="Delete">🗑️</button>
                                            </div>
                                        </div>

                                        <!-- Card Content -->
                                        <div class="px-5 py-4 space-y-4">
                                            <!-- Password -->
                                            <div>
                                                <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Password</label>
                                                <div class="flex items-center gap-2 p-3 bg-black/30 rounded-lg">
                                                    <span class="flex-1 font-mono text-sm password-masked password-display-<?php echo $account['id']; ?>">••••••••••••••••</span>
                                                    <button onclick="togglePasswordVisibility(this)" class="px-3 py-1 text-xs font-semibold bg-dark-border hover:bg-primary/20 hover:text-primary rounded transition-all whitespace-nowrap">Show</button>
                                                </div>
                                            </div>

                                            <!-- Website URL -->
                                            <?php if (isset($account['website_url']) && $account['website_url']): ?>
                                                <div>
                                                    <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Website</label>
                                                    <a href="<?php 
                                                        $url = trim($account['website_url']);
                                                        if (stripos($url, 'javascript:') === 0 || stripos($url, 'data:') === 0 || stripos($url, 'vbscript:') === 0) {
                                                            echo '#';
                                                        } else {
                                                            if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
                                                                $url = "https://" . ltrim($url, '/');
                                                            }
                                                            echo htmlspecialchars($url);
                                                        }
                                                    ?>" target="_blank" class="text-sm text-primary hover:underline break-all">
                                                        <?php echo htmlspecialchars($account['website_url']); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Notes -->
                                            <?php if (isset($account['notes']) && $account['notes']): ?>
                                                <div>
                                                    <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Notes</label>
                                                    <p class="text-sm text-gray-300 line-clamp-2"><?php echo htmlspecialchars($account['notes']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Card Footer -->
                                        <div class="px-5 py-3 bg-black/20 border-t border-dark-border/50">
                                            <small class="text-xs text-gray-500">
                                                Updated: <?php echo date('M d, Y', strtotime($account['updated_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Account Modal -->
    <div id="accountModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
        <div class="bg-dark-card border border-dark-border rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fade-in">
            <!-- Modal Header -->
            <div class="p-5 border-b border-dark-border sticky top-0 bg-dark-card flex justify-between items-center">
                <h2 id="modalTitle" class="text-xl md:text-2xl font-bold">Add New Account</h2>
                <button onclick="closeModal('accountModal')" class="text-2xl text-gray-400 hover:text-white transition-all">&times;</button>
            </div>

            <!-- Modal Form -->
            <form id="accountForm" onsubmit="handleAccountSubmit(event)" class="p-5 space-y-4">
                <input type="hidden" id="accountId" name="id">

                <div>
                    <label for="serviceName" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Service Name *</label>
                    <input type="text" id="serviceName" placeholder="e.g., Gmail, GitHub, AWS" 
                        class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Category *</label>
                        <select id="category" class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" class="bg-dark-card"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?> 
                        </select>
                    </div>
                    <div>
                        <label for="accountUsername" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Username / Email *</label>
                        <input type="text" id="accountUsername" placeholder="your@email.com"
                            class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="accountPassword" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Password *</label>
                        <input type="password" id="accountPassword" placeholder="••••••••"
                            class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                    </div>
                    <div>
                        <label for="website" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Website</label>
                        <input type="url" id="website" placeholder="https://example.com"
                            class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all">
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Notes</label>
                    <textarea id="notes" placeholder="Add any notes..." rows="3"
                        class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all resize-none"></textarea>
                </div>

                <div>
                    <label for="masterPassword" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Master Password (required to save) *</label>
                    <input type="password" id="masterPassword" placeholder="Enter your master key"
                        class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary hover:bg-primary-dark text-dark-bg font-bold rounded-lg transition-all">
                        Save Account
                    </button>
                    <button type="button" onclick="closeModal('accountModal')" class="flex-1 px-4 py-2 bg-dark-border hover:bg-dark-border/80 rounded-lg transition-all">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
        <div class="bg-dark-card border border-dark-border rounded-xl w-full max-w-md shadow-2xl animate-fade-in">
            <div class="p-5 border-b border-dark-border flex justify-between items-center">
                <h2 class="text-xl font-bold">Settings</h2>
                <button onclick="closeModal('settingsModal')" class="text-2xl text-gray-400 hover:text-white transition-all">&times;</button>
            </div>

            <form id="settingsForm" onsubmit="handleSettingsSubmit(event)" class="p-5 space-y-4">
                <div>
                    <label for="oldPassword" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Current Master Password *</label>
                    <input type="password" id="oldPassword" placeholder="••••••••"
                        class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                </div>

                <div>
                    <label for="newPassword" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">New Master Password *</label>
                    <input type="password" id="newPassword" placeholder="••••••••"
                        class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                </div>

                <div>
                    <label for="confirmNewPassword" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Confirm New Password *</label>
                    <input type="password" id="confirmNewPassword" placeholder="••••••••"
                        class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary hover:bg-primary-dark text-dark-bg font-bold rounded-lg transition-all">
                        Update Password
                    </button>
                    <button type="button" onclick="closeModal('settingsModal')" class="flex-1 px-4 py-2 bg-dark-border hover:bg-dark-border/80 rounded-lg transition-all">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alerts -->
    <div id="alertBox" class="fixed top-4 right-4 z-50 max-w-xs"></div>

    <!-- Master Key Prompt Modal -->
    <div id="masterKeyModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('masterKeyModal')"></div>
        <div class="bg-dark-card border border-dark-border rounded-xl w-full max-w-sm relative z-10 shadow-2xl animate-fade-in">
            <div class="p-5 border-b border-dark-border flex justify-between items-center">
                <h2 id="masterKeyModalTitle" class="text-xl md:text-2xl font-bold flex items-center gap-2"><span class="text-primary">🔓</span> Authenticate</h2>
                <button type="button" onclick="closeModal('masterKeyModal')" class="text-2xl text-gray-400 hover:text-white transition-all">&times;</button>
            </div>
            <form id="masterKeyForm" onsubmit="handleMasterKeySubmit(event)" class="p-5 space-y-4">
                <p id="masterKeyModalMessage" class="text-sm text-gray-400">Enter your Master Key to perform this action.</p>
                <div>
                    <label for="promptMasterKey" class="block text-xs font-bold text-primary uppercase tracking-wider mb-2">Master Key</label>
                    <input type="password" id="promptMasterKey" placeholder="••••••••"
                        class="w-full px-4 py-2 bg-white/5 border border-dark-border rounded-lg text-white placeholder-gray-500 text-sm focus:outline-none focus:bg-primary/10 focus:border-primary transition-all" required>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" id="masterKeySubmitBtn" class="flex-1 px-4 py-2 bg-primary hover:bg-primary-dark text-dark-bg font-bold rounded-lg transition-all">
                        Confirm
                    </button>
                    <button type="button" onclick="closeModal('masterKeyModal')" class="px-4 py-2 bg-dark-border hover:bg-dark-border/80 rounded-lg transition-all">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('logoutModal')"></div>
        <div class="bg-dark-card border border-dark-border rounded-xl w-full max-w-sm relative z-10 shadow-2xl animate-fade-in text-center p-6">
            <div class="mb-4 text-4xl">👋</div>
            <h2 class="text-xl md:text-2xl font-bold mb-2">Secure Logout</h2>
            <p class="text-sm text-gray-400 mb-6">Are you sure you want to lock your vault and end your secure session?</p>
            <div class="flex gap-3">
                <button onclick="executeLogout()" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-all">
                    Lock & Logout
                </button>
                <button onclick="closeModal('logoutModal')" class="flex-1 px-4 py-2 bg-dark-border hover:bg-dark-border/80 rounded-lg transition-all">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js?v=4"></script>
</body>
</html>
