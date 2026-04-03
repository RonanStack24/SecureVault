// Master Key Modal Logic
let activeMasterKeyAction = null;
let activeMasterKeyAccountId = null;
let activeMasterKeyButton = null;

function promptMasterKey(action, accountId, button) {
    activeMasterKeyAction = action;
    activeMasterKeyAccountId = accountId;
    activeMasterKeyButton = button;
    
    document.getElementById('promptMasterKey').value = '';
    
    const title = document.getElementById('masterKeyModalTitle');
    const message = document.getElementById('masterKeyModalMessage');
    const submitBtn = document.getElementById('masterKeySubmitBtn');
    
    if (action === 'reveal') {
        title.innerHTML = '<span class="text-primary">🔓</span> View Password';
        message.textContent = 'Enter your Master Key to decrypt this specific vault shard.';
        submitBtn.textContent = 'Decrypt';
        submitBtn.className = 'flex-1 px-4 py-2 bg-primary hover:bg-primary-dark text-dark-bg font-bold rounded-lg transition-all';
    } else if (action === 'delete') {
        title.innerHTML = '<span class="text-red-500">⚠️</span> Delete Account';
        message.textContent = 'Enter your Master Key to confirm permanent deletion.';
        submitBtn.textContent = 'Delete Forever';
        submitBtn.className = 'flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-all';
    }
    
    openModal('masterKeyModal');
    setTimeout(() => document.getElementById('promptMasterKey').focus(), 100);
}

function handleMasterKeySubmit(e) {
    e.preventDefault();
    const masterPassword = document.getElementById('promptMasterKey').value;
    if (!masterPassword) return;

    closeModal('masterKeyModal');

    if (activeMasterKeyAction === 'reveal') {
        executeRevealPassword(activeMasterKeyAccountId, activeMasterKeyButton, masterPassword);
    } else if (activeMasterKeyAction === 'delete') {
        executeDeleteAccount(activeMasterKeyAccountId, masterPassword);
    }
}

// Toggle Sidebar
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  const backdrop = document.getElementById("sidebarBackdrop");

  sidebar.classList.toggle("active");
  backdrop.classList.toggle("hidden");
}

function closeSidebar() {
  const sidebar = document.getElementById("sidebar");
  const backdrop = document.getElementById("sidebarBackdrop");

  sidebar.classList.remove("active");
  backdrop.classList.add("hidden");
}

// Modal Functions
function openModal(modalId) {
  document.getElementById(modalId).classList.remove("hidden");
  document.body.style.overflow = "hidden";
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.add("hidden");
  document.body.style.overflow = "auto";
}

function openAddAccountModal() {
  document.getElementById("accountId").value = "";
  document.getElementById("accountForm").reset();
  document.getElementById("modalTitle").textContent = "Add New Account";
  openModal("accountModal");
}

function openSettingsModal() {
  document.getElementById("settingsForm").reset();
  openModal("settingsModal");
}

// Alert System
function showAlert(message, type = "info") {
  const alertBox = document.getElementById("alertBox");

  const colors = {
    success: "bg-green-900/20 border-green-700 text-green-400",
    error: "bg-red-900/20 border-red-700 text-red-400",
    warning: "bg-yellow-900/20 border-yellow-700 text-yellow-400",
    info: "bg-blue-900/20 border-blue-700 text-blue-400",
  };

  const alert = document.createElement("div");
  alert.className = `border rounded-lg p-4 mb-3 ${colors[type]} animate-fade-in`;
  alert.innerHTML = `
        <div class="flex items-center gap-3">
            <span>${type === "success" ? "✓" : type === "error" ? "✕" : type === "warning" ? "!" : "ℹ"}</span>
            <span class="text-sm">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-lg hover:opacity-70">×</button>
        </div>
    `;

  alertBox.appendChild(alert);
  setTimeout(() => alert.remove(), 5000);
}

// Add/Edit Account
function openAddAccountModal() {
  document.getElementById("accountId").value = "";
  document.getElementById("accountForm").reset();
  document.getElementById("modalTitle").textContent = "Add New Account";
  closeSidebar();
  openModal("accountModal");
}

function editAccount(accountId) {
  const card = document.querySelector(`[data-account-id="${accountId}"]`);
  if (!card) {
    showAlert("Account not found", "error");
    return;
  }

  const serviceName = card.querySelector("h3").textContent.trim();
  const usernameDiv = card.querySelector("div[onclick*='copyUserClipboard']");
  const username = usernameDiv ? usernameDiv.querySelector("span:first-child").textContent.trim() : "";
  const website = card.querySelector("a")?.href || "";
  const notesText = card.querySelector("p.line-clamp-2")?.textContent || "";

  document.getElementById("accountId").value = accountId;
  document.getElementById("serviceName").value = serviceName;
  document.getElementById("accountUsername").value = username;
  document.getElementById("website").value = website;
  document.getElementById("notes").value = notesText;
  document.getElementById("modalTitle").textContent = "Edit Account";

  closeSidebar();
  openModal("accountModal");
}

function handleAccountSubmit(event) {
  event.preventDefault();

  const accountId = document.getElementById("accountId").value;
  const serviceName = document.getElementById("serviceName").value;
  const categoryId = document.getElementById("category").value;
  const username = document.getElementById("accountUsername").value;
  const password = document.getElementById("accountPassword").value;
  const website = document.getElementById("website").value;
  const notes = document.getElementById("notes").value;
  const masterPassword = document.getElementById("masterPassword").value;

  if (!masterPassword) {
    showAlert("Master password is required", "error");
    return;
  }

  const formData = new FormData();
  formData.append("service_name", serviceName);
  formData.append("username", username);
  formData.append("password", password);
  formData.append("category_id", categoryId);
  formData.append("website_url", website);
  formData.append("notes", notes);
  formData.append("master_password", masterPassword);

  let action = "add_account";
  if (accountId) {
    formData.append("id", accountId);
    action = "update_account";
  }

  fetch(`api/accounts.php?action=${action}`, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert(data.message, "success");
        closeModal("accountModal");
        setTimeout(() => location.reload(), 1200);
      } else {
        showAlert(data.message, "error");
      }
    })
    .catch((error) => {
      showAlert("Failed to save account: " + error, "error");
    });
}

// Delete Account
function deleteAccount(accountId) {
    promptMasterKey('delete', accountId, null);
}

function executeDeleteAccount(accountId, masterPasswordPrompt) {
  const formData = new FormData();
  formData.append("id", accountId);
  formData.append("master_password", masterPasswordPrompt);

  fetch(`api/accounts.php?action=delete_account`, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert(data.message, "success");
        setTimeout(() => location.reload(), 1200);
      } else {
        showAlert(data.message, "error");
      }
    })
    .catch((error) => {
      showAlert("Failed to delete account: " + error, "error");
    });
}

// Password Visibility
function togglePasswordVisibility(button) {
  const passwordDisplay = button.closest(".flex");
  if (!passwordDisplay) return;

  const passwordValue = passwordDisplay.querySelector("span");
  if (!passwordValue) {
    showAlert("Error: Could not find password element", "error");
    return;
  }

  if (button.textContent.startsWith("Hide")) {
    passwordValue.textContent = "••••••••••••••••";
    passwordDisplay.classList.add("password-masked");
    button.textContent = "Show";
    if (button.countdownInterval) clearInterval(button.countdownInterval);
    if (button.hideTimeout) clearTimeout(button.hideTimeout);
    return;
  }

  const accountCard = button.closest("[data-account-id]");
  if (!accountCard) {
    showAlert("Error: Could not find account", "error");
    return;
  }

  const accountId = accountCard.getAttribute("data-account-id");
  promptMasterKey('reveal', accountId, button);
}

function executeRevealPassword(accountId, button, masterPasswordPrompt) {
  const passwordDisplay = button.closest(".flex");
  const passwordValue = passwordDisplay.querySelector("span");

  const formData = new FormData();
  formData.append('master_password', masterPasswordPrompt);

  fetch(
    `api/accounts.php?action=get_account&id=${accountId}`, {
      method: "POST",
      body: formData
    }
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const password = data.data.account.password;
        if (!password) {
          showAlert("Password is empty", "error");
          return;
        }

        passwordValue.textContent = password;
        passwordDisplay.classList.remove("password-masked");

        let secondsLeft = 30;
        button.textContent = `Hide (${secondsLeft}s)`;

        if (button.countdownInterval) clearInterval(button.countdownInterval);
        if (button.hideTimeout) clearTimeout(button.hideTimeout);

        button.countdownInterval = setInterval(() => {
          secondsLeft--;
          if (secondsLeft > 0) {
            button.textContent = `Hide (${secondsLeft}s)`;
          } else {
            clearInterval(button.countdownInterval);
          }
        }, 1000);

        button.hideTimeout = setTimeout(() => {
          passwordValue.textContent = "••••••••••••••••";
          passwordDisplay.classList.add("password-masked");
          button.textContent = "Show";
          clearInterval(button.countdownInterval);
        }, 30000);
      } else {
        showAlert(data.message || "Failed to retrieve password", "error");
      }
    })
    .catch((error) => {
      showAlert("Error: " + error, "error");
    });
}

// Search/Filter
function filterAccounts() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const cards = document.querySelectorAll("[data-account-id]");

  cards.forEach((card) => {
    const service = card.getAttribute("data-service");
    const matches = service.includes(searchTerm);
    card.style.display = matches ? "" : "none";
  });
}

// Settings
function handleSettingsSubmit(event) {
  event.preventDefault();

  const oldPassword = document.getElementById("oldPassword").value;
  const newPassword = document.getElementById("newPassword").value;
  const confirmNewPassword =
    document.getElementById("confirmNewPassword").value;

  if (newPassword !== confirmNewPassword) {
    showAlert("New passwords do not match", "error");
    return;
  }

  const formData = new FormData();
  formData.append("old_password", oldPassword);
  formData.append("new_password", newPassword);

  fetch("api/auth.php?action=update_master_password", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert(data.message, "success");
        closeModal("settingsModal");
      } else {
        showAlert(data.message, "error");
      }
    })
    .catch((error) => {
      showAlert("Failed to update password: " + error, "error");
    });
}

// Logout
function logout() {
  openModal("logoutModal");
}

function executeLogout() {
  fetch("api/auth.php?action=logout", { method: "POST" })
    .then(() => (window.location.href = "index.php"))
    .catch(() => (window.location.href = "index.php"));
}

// Close modals on Escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    const modal = document.querySelector(".fixed:not(.hidden)");
    if (modal) {
      const modalId = modal.id;
      if (modalId) closeModal(modalId);
    }
  }
});

// Add animation styles
const style = document.createElement("style");
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
`;
document.head.appendChild(style);

// Copy Username to Clipboard
function copyUserClipboard(text) {
    // Fallback for non-HTTPS connections (like testing on local Network IPs)
    if (!navigator.clipboard) {
        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.style.position = "fixed"; // Prevent scrolling to bottom
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand("copy");
            showAlert("Username copied to clipboard", "success");
        } catch (err) {
            showAlert("Failed to copy username on this device", "error");
        }
        document.body.removeChild(textarea);
        return;
    }
    
    navigator.clipboard.writeText(text).then(() => {
        showAlert("Username copied to clipboard", "success");
    }).catch(err => {
        showAlert("Failed to copy username", "error");
    });
}
