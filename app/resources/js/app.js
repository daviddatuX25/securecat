import './bootstrap';
import { attachPasswordStrength } from './password-strength';

// Register page: attach password strength meter (bar + checklist)
document.addEventListener('DOMContentLoaded', () => {
  const registerForm = document.querySelector('form[action*="register"]');
  const passwordInput = document.getElementById('password');
  if (registerForm && passwordInput) {
    attachPasswordStrength(passwordInput, { showChecklist: true });
  }
});
