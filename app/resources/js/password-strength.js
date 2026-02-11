/**
 * Frontend-only password strength analyzer for login/registration mockup.
 * Used on login page for UX; actual validation is server-side (Phase 1).
 * Strength is informational only — does not block submit on login.
 */

/**
 * @param {string} password
 * @returns {{ score: number, label: string, percent: number, checks: { length: boolean, lower: boolean, upper: boolean, number: boolean, special: boolean } }}
 */
export function analyzePasswordStrength(password) {
  if (!password || password.length === 0) {
    return { score: 0, label: '', percent: 0, checks: { length: false, lower: false, upper: false, number: false, special: false } };
  }

  const checks = {
    length: password.length >= 8,
    lower: /[a-z]/.test(password),
    upper: /[A-Z]/.test(password),
    number: /\d/.test(password),
    special: /[^A-Za-z0-9]/.test(password),
  };

  const met = Object.values(checks).filter(Boolean).length;
  const score = Math.min(5, met + (password.length >= 12 ? 1 : 0));
  const percent = (score / 5) * 100;
  const labels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very strong'];
  const label = labels[score];

  return { score, label, percent, checks };
}

/**
 * Attach strength UI to a password input (e.g. #password).
 * Shows a bar and optional checklist below the input. For login we only show bar + label.
 *
 * @param {HTMLInputElement} input
 * @param {{ showChecklist?: boolean }} options
 * @returns {() => void} teardown function
 */
export function attachPasswordStrength(input, options = {}) {
  const { showChecklist = false } = options;

  const container = document.createElement('div');
  container.id = 'password-strength';
  container.className = 'mt-2 space-y-1';
  container.setAttribute('aria-live', 'polite');

  const barWrap = document.createElement('div');
  barWrap.className = 'flex items-center gap-2';
  const bar = document.createElement('div');
  bar.className = 'h-2 flex-1 rounded-full bg-base-300 overflow-hidden';
  bar.setAttribute('role', 'progressbar');
  bar.setAttribute('aria-valuemin', '0');
  bar.setAttribute('aria-valuemax', '100');
  const fill = document.createElement('div');
  fill.className = 'h-full rounded-full transition-all duration-200 ease-out';
  bar.appendChild(fill);
  const labelSpan = document.createElement('span');
  labelSpan.className = 'text-xs font-medium text-base-content/70 min-w-[4rem]';
  barWrap.appendChild(bar);
  barWrap.appendChild(labelSpan);
  container.appendChild(barWrap);

  let checklistEl = null;
  if (showChecklist) {
    checklistEl = document.createElement('ul');
    checklistEl.className = 'text-xs space-y-0.5 text-base-content/70';
    container.appendChild(checklistEl);
  }

  input.parentElement?.appendChild(container);

  function update() {
    const value = input.value;
    const result = analyzePasswordStrength(value);

    if (value.length === 0) {
      fill.style.width = '0%';
      fill.className = 'h-full rounded-full transition-all duration-200 ease-out bg-base-300';
      labelSpan.textContent = '';
      bar.setAttribute('aria-valuenow', '0');
      bar.setAttribute('aria-valuetext', 'No password');
      if (checklistEl) checklistEl.innerHTML = '';
      return;
    }

    fill.style.width = `${result.percent}%`;
    const colorMap = ['bg-error', 'bg-error', 'bg-warning', 'bg-info', 'bg-success', 'bg-success'];
    fill.className = `h-full rounded-full transition-all duration-200 ease-out ${colorMap[result.score]}`;
    labelSpan.textContent = result.label;
    bar.setAttribute('aria-valuenow', String(Math.round(result.percent)));
    bar.setAttribute('aria-valuetext', result.label || 'Weak');

    if (checklistEl) {
      const items = [
        [result.checks.length, 'At least 8-12 characters'],
        [result.checks.lower, 'One lowercase letter'],
        [result.checks.upper, 'One uppercase letter'],
        [result.checks.number, 'One number'],
        [result.checks.special, 'One special character'],
      ];
      checklistEl.innerHTML = items
        .map(([ok, text]) => `<li class="${ok ? 'text-success' : ''}">${ok ? '✓' : '○'} ${text}</li>`)
        .join('');
    }
  }

  input.addEventListener('input', update);
  input.addEventListener('focus', update);
  update();

  return () => {
    input.removeEventListener('input', update);
    input.removeEventListener('focus', update);
    container.remove();
  };
}
