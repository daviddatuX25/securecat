import './bootstrap';
import { attachPasswordStrength } from './password-strength';

document.addEventListener('DOMContentLoaded', () => {
  const registerForm = document.querySelector('form[action*="register"]');
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('password_confirmation');
  const confirmFeedback = document.getElementById('confirm-password-feedback');

  if (registerForm && passwordInput) {
    attachPasswordStrength(passwordInput, { showChecklist: true });
  }

  if (confirmInput && confirmFeedback) {
    const pwInput = document.getElementById('password');
    function updateConfirmFeedback() {
      const password = pwInput?.value ?? '';
      const confirm = confirmInput.value;
      if (confirm.length === 0) {
        confirmFeedback.textContent = '';
        confirmFeedback.className = 'text-xs mt-1 min-h-5';
        return;
      }
      if (password === confirm) {
        confirmFeedback.textContent = 'Passwords match';
        confirmFeedback.className = 'text-xs mt-1 min-h-5 text-success';
      } else {
        confirmFeedback.textContent = 'Passwords do not match';
        confirmFeedback.className = 'text-xs mt-1 min-h-5 text-error';
      }
    }
    pwInput?.addEventListener('input', updateConfirmFeedback);
    confirmInput.addEventListener('input', updateConfirmFeedback);
  }
});