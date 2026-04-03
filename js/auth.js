/**
 * SecureVault Authentication JavaScript
 */

// Toggle between login and register forms
function toggleForm() {
  document.getElementById("loginForm").style.display =
    document.getElementById("loginForm").style.display === "none"
      ? "block"
      : "none";
  document.getElementById("registerForm").style.display =
    document.getElementById("registerForm").style.display === "none"
      ? "block"
      : "none";
}

// Keep the forms synchronized with URL parameter
if (window.location.search.includes("register=true")) {
  document.getElementById("loginForm").style.display = "none";
  document.getElementById("registerForm").style.display = "block";
} else {
  document.getElementById("loginForm").style.display = "block";
  document.getElementById("registerForm").style.display = "none";
}
