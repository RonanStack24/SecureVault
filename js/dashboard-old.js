/**
 * SecureVault Dashboard JavaScript
 */

// Mobile sidebar toggle
function toggleMobileSidebar() {
  const sidebar = document.getElementById("sidebar");
  if (sidebar) {
    sidebar.classList.toggle("-translate-x-full");
    sidebar.classList.toggle("translate-x-0");
  }
}

// Close mobile sidebar when a link is clicked
document.addEventListener("DOMContentLoaded", function () {
  const sidebarLinks = document.querySelectorAll("#sidebar a");
  const sidebar = document.getElementById("sidebar");

  sidebarLinks.forEach((link) => {
    link.addEventListener("click", function () {
      if (window.innerWidth < 768) {
        sidebar.classList.add("-translate-x-full");
        sidebar.classList.remove("translate-x-0");
      }
    });
  });

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", function (event) {
    const sidebar = document.getElementById("sidebar");
    const toggleBtn = document.querySelector(
      '[onclick="toggleMobileSidebar()"]',
    );

    if (window.innerWidth < 768 && sidebar && toggleBtn) {
      if (
        !sidebar.contains(event.target) &&
        !toggleBtn.contains(event.target)
      ) {
        if (!sidebar.classList.contains("-translate-x-full")) {
          sidebar.classList.add("-translate-x-full");
          sidebar.classList.remove("translate-x-0");
        }
      }
    }
  });
});

// Modal Functions
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("hidden");
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add("hidden");
  }
}

// Account Modal
function openAddAccountModal() {
  document.getElementById("modalTitle").textContent = "Add New Account";
  document.getElementById("accountForm").reset();
  document.getElementById("accountId").value = "";
  document.getElementById("masterPassword").value = "";
  document.getElementById("accountStrengthBar").style.width = "0%";
  document.getElementById("accountStrengthText").textContent =
    "Password strength: Weak";
  openModal("accountModal");
}

function editAccount(accountId) {
  const card = document.querySelector(`[data-account-id="${accountId}"]`);
  if (!card) return;

  const masterPassword = prompt(
    "Enter your master password to edit this account:",
  );
  if (!masterPassword) return;

  fetch(
    `api/accounts.php?action=get_account&id=${accountId}&master_password=${encodeURIComponent(masterPassword)}`,
  )
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const account = data.data.account;
        document.getElementById("modalTitle").textContent = "Edit Account";
        document.getElementById("accountId").value = account.id;
        document.getElementById("serviceName").value = account.service_name;
        document.getElementById("category").value = account.category_id;
        document.getElementById("accountUsername").value = account.username;
        document.getElementById("accountPassword").value = account.password;
        document.getElementById("website").value = account.website_url || "";
        document.getElementById("notes").value = account.notes || "";
        document.getElementById("masterPassword").value = masterPassword;
        openModal("accountModal");
      } else {
        showAlert(data.message, "error");
      }
    })
    .catch((error) => {
      showAlert("Failed to load account: " + error, "error");
    });
}

function deleteAccount(accountId) {
  if (!confirm("Are you sure you want to delete this account?")) {
    return;
  }

  const masterPassword = prompt(
    "Enter your master password to confirm deletion:",
  );
  if (!masterPassword) return;

  const formData = new FormData();
  formData.append("id", accountId);
  formData.append("master_password", masterPassword);

  fetch("api/accounts.php?action=delete_account", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Account deleted successfully", "success");
        setTimeout(() => location.reload(), 1500);
      } else {
        showAlert(data.message, "error");
      }
    })
    .catch((error) => {
      showAlert("Failed to delete account: " + error, "error");
    });
}

// Account Form Submission
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
        setTimeout(() => location.reload(), 1500);
      } else {
        showAlert(data.message, "error");
      }
    })
    .catch((error) => {
      showAlert("Failed to save account: " + error, "error");
    });
}

// Settings Modal
function openSettingsModal() {
  document.getElementById("settingsForm").reset();
  openModal("settingsModal");
}

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
        showAlert("Master password updated successfully", "success");
        closeModal("settingsModal");
        setTimeout(() => location.reload(), 1500);
      } else {
        showAlert(data.message, "error");
      }
    })
    .catch((error) => {
      showAlert("Failed to update password: " + error, "error");
    });
}

// Password visibility toggle
function togglePasswordVisibility(button) {
  const passwordDisplay = button.parentElement;
  const passwordValue = passwordDisplay.querySelector("span.password-masked");

  if (!passwordValue) {
    console.error("Password element not found!");
    showAlert("Error: Could not find password display element", "error");
    return;
  }

  const masterPassword = prompt(
    "Enter your master password to reveal password:",
  );

  if (!masterPassword) return;

  const accountCard = button.closest("[data-account-id]");
  if (!accountCard) {
    console.error("Account card not found!");
    showAlert("Error: Could not find account", "error");
    return;
  }

  const accountId = accountCard.getAttribute("data-account-id");

  fetch(
    `api/accounts.php?action=get_account&id=${accountId}&master_password=${encodeURIComponent(masterPassword)}`,
  )
    .then((response) => response.json())
    .then((data) => {
      console.log("API Response:", data);
      if (data.success) {
        const password = data.data.account.password;
        console.log("Password value:", password);
        if (!password) {
          showAlert("Password is empty - database may need reset", "error");
          return;
        }
        if (passwordValue.textContent === "••••••••••••••••") {
          passwordValue.textContent = password;
          passwordDisplay.classList.remove("password-masked");
          button.textContent = "◯";

          // Auto-hide after 30 seconds
          setTimeout(() => {
            passwordValue.textContent = "••••••••••••••••";
            passwordDisplay.classList.add("password-masked");
            button.textContent = "Show";
          }, 30000);
        } else {
          passwordValue.textContent = "••••••••••••••••";
          passwordDisplay.classList.add("password-masked");
          button.textContent = "◯";
        }
      } else {
        showAlert("Invalid master password", "error");
      }
    })
    .catch((error) => {
      showAlert("Failed to retrieve password: " + error, "error");
    });
}

// Password strength checker
document.addEventListener("DOMContentLoaded", function () {
  const passwordInput = document.getElementById("accountPassword");
  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      updatePasswordStrength(this.value);
    });
  }
});

function updatePasswordStrength(password) {
  const strengthBar = document.getElementById("accountStrengthBar");
  const strengthText = document.getElementById("accountStrengthText");

  let strength = 0;

  if (password.length >= 8) strength += 20;
  if (password.length >= 12) strength += 10;
  if (/[a-z]/.test(password)) strength += 20;
  if (/[A-Z]/.test(password)) strength += 20;
  if (/[0-9]/.test(password)) strength += 15;
  if (/[@$!%*?&]/.test(password)) strength += 15;

  strengthBar.style.width = strength + "%";

  if (strength < 30) {
    strengthBar.style.backgroundColor = "#dc3545";
    strengthText.textContent = "Password strength: Weak";
  } else if (strength < 60) {
    strengthBar.style.backgroundColor = "#ffc107";
    strengthText.textContent = "Password strength: Fair";
  } else if (strength < 85) {
    strengthBar.style.backgroundColor = "#28a745";
    strengthText.textContent = "Password strength: Good";
  } else {
    strengthBar.style.backgroundColor = "#20c997";
    strengthText.textContent = "Password strength: Strong";
  }
}

// Generate strong password
function generatePassword() {
  const length = 16;
  const charset =
    "ABCDEFGHIJKLMNOPQRSTUVWXYZ" +
    "abcdefghijklmnopqrstuvwxyz" +
    "0123456789" +
    "@$!%*?&";
  let password = "";

  // Ensure at least one of each character type
  password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)];
  password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)];
  password += "0123456789"[Math.floor(Math.random() * 10)];
  password += "@$!%*?&"[Math.floor(Math.random() * 7)];

  for (let i = 4; i < length; i++) {
    password += charset[Math.floor(Math.random() * charset.length)];
  }

  // Shuffle password
  password = password
    .split("")
    .sort(() => Math.random() - 0.5)
    .join("");

  document.getElementById("accountPassword").value = password;
  updatePasswordStrength(password);
}

// Toggle password visibility
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  input.type = input.type === "password" ? "text" : "password";
}

// Filter accounts
function filterAccounts() {
  const searchTerm = document.getElementById("searchInput").value.toLowerCase();
  const cards = document.querySelectorAll(".account-card");

  cards.forEach((card) => {
    const serviceName = card.getAttribute("data-service").toLowerCase();
    if (serviceName.includes(searchTerm)) {
      card.style.display = "";
    } else {
      card.style.display = "none";
    }
  });
}

// Alert notification
function showAlert(message, type = "success") {
  const container = document.getElementById("alertContainer");
  const alertDiv = document.createElement("div");
  const bgClass =
    type === "error"
      ? "bg-red-900/20 border-red-500 text-red-400"
      : "bg-emerald-900/20 border-emerald-500 text-emerald-400";
  alertDiv.className = `p-4 rounded-lg border-l-4 ${bgClass} border animate-slide-in`;
  alertDiv.style.animation = "slideIn 0.3s ease";
  alertDiv.textContent = message;

  container.appendChild(alertDiv);

  setTimeout(() => {
    alertDiv.style.opacity = "0";
    alertDiv.style.transition = "opacity 0.3s ease";
    setTimeout(() => alertDiv.remove(), 300);
  }, 5000);
}

// Logout
function logout() {
  if (confirm("Are you sure you want to logout?")) {
    fetch("api/auth.php?action=logout", {
      method: "POST",
    }).then(() => {
      window.location.href = "index.php";
    });
  }
}

// Close modals when clicking outside
window.addEventListener("click", function (event) {
  const modals = document.querySelectorAll(".modal.active");
  modals.forEach((modal) => {
    if (event.target === modal) {
      modal.classList.remove("active");
    }
  });
});

// Copy to clipboard
function copyToClipboard(text) {
  navigator.clipboard
    .writeText(text)
    .then(() => {
      showAlert("Copied to clipboard", "success");
    })
    .catch(() => {
      showAlert("Failed to copy", "error");
    });
}

// Add animation styles
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
