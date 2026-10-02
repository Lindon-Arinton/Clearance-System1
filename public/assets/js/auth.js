/* Sign-in / sign-up pages: motion, password helpers and the sign-up wizard. */
(function () {
  'use strict';

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const card = document.querySelector('.auth-card');

  function shake() {
    if (!card || reduceMotion) return;
    card.classList.remove('is-shaking');
    void card.offsetWidth; // restart the animation
    card.classList.add('is-shaking');
  }

  // Shake the card once it has settled when the server returned an error.
  if (card && card.hasAttribute('data-shake')) {
    setTimeout(shake, reduceMotion ? 0 : 1400);
  }

  // ---- Rotating word in the story panel ---------------------------------
  const word = document.querySelector('[data-rotating-word]');
  if (word && !reduceMotion) {
    const words = ['signature.', 'completion.', 'next semester.'];
    let w = 0;
    setInterval(() => {
      w = (w + 1) % words.length;
      word.classList.remove('auth-word');
      void word.offsetWidth;
      word.textContent = words[w];
      word.classList.add('auth-word');
    }, 4500);
  }

  // ---- Clearance journey: steps complete one by one, then loop ----------
  const journey = document.querySelector('[data-journey]');
  if (journey) {
    const steps = Array.from(journey.children);
    const bar = document.querySelector('[data-journey-bar]');
    const count = document.querySelector('[data-journey-count]');
    const paint = (done) => {
      steps.forEach((li, i) => {
        li.classList.toggle('is-done', i < done);
        li.classList.toggle('is-active', i === done);
      });
      if (bar) bar.style.width = (done / steps.length) * 100 + '%';
      if (count) count.textContent = done + ' / ' + steps.length;
    };
    if (reduceMotion) {
      paint(steps.length);
    } else {
      let done = 0;
      paint(0);
      const tick = () => {
        done += 1;
        paint(done);
        setTimeout(done >= steps.length ? () => { done = 0; paint(0); setTimeout(tick, 1600); } : tick, done >= steps.length ? 4200 : 2200);
      };
      setTimeout(tick, 2000);
    }
  }

  // ---- Show / hide password ---------------------------------------------
  document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.querySelector(btn.dataset.togglePassword);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
      input.focus();
    });
  });

  // ---- Caps Lock warning ------------------------------------------------
  document.querySelectorAll('[data-caps-hint]').forEach((input) => {
    const hint = document.querySelector(input.dataset.capsHint);
    if (!hint) return;
    const check = (e) => { if (e.getModifierState) hint.hidden = !e.getModifierState('CapsLock'); };
    input.addEventListener('keydown', check);
    input.addEventListener('keyup', check);
    input.addEventListener('blur', () => { hint.hidden = true; });
  });

  // ---- Password strength meter ------------------------------------------
  document.querySelectorAll('[data-strength]').forEach((input) => {
    const box = document.querySelector(input.dataset.strength);
    const bar = box && box.querySelector('[data-strength-bar]');
    const label = box && box.querySelector('[data-strength-label]');
    if (!bar) return;
    const levels = [
      ['Too short', '#c2412f'], ['Weak', '#c2412f'], ['Fair', '#c8871a'], ['Good', '#3b7d5b'], ['Strong', '#1d5a3f'], ['Very strong', '#133b2a'],
    ];
    const update = () => {
      const v = input.value;
      let score = 0;
      if (v.length >= 8) score++;
      if (v.length >= 12) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      const level = v.length < 8 ? 0 : Math.max(1, score); // 0 = too short … 5 = very strong
      const [text, color] = levels[level];
      bar.style.width = v ? Math.max(10, (level / 5) * 100) + '%' : '0%';
      bar.style.backgroundColor = color;
      if (label) { label.textContent = v ? text : ''; label.style.color = color; }
    };
    input.addEventListener('input', update);
    update();
  });

  // ---- Password confirmation match --------------------------------------
  const syncMatch = () => {
    document.querySelectorAll('[data-match]').forEach((input) => {
      const other = document.querySelector(input.dataset.match);
      const ok = input.value !== '' && other && input.value === other.value;
      input.setCustomValidity(input.value && !ok ? 'Passwords do not match.' : '');
      const icon = input.parentElement.querySelector('[data-match-ok]');
      if (icon) icon.style.opacity = ok ? '1' : '0';
    });
  };
  document.querySelectorAll('[data-match]').forEach((input) => {
    input.addEventListener('input', syncMatch);
    const other = document.querySelector(input.dataset.match);
    if (other) other.addEventListener('input', syncMatch);
  });

  // Clear server-side error styling as soon as the user edits a field.
  document.querySelectorAll('.auth-card .form-control, .auth-card .form-select, .auth-card .form-check-input').forEach((el) => {
    el.addEventListener(el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input', () => {
      el.classList.remove('is-invalid');
      const fb = el.closest('div:not(.auth-field)')?.querySelector('.invalid-feedback');
      if (fb && !el.closest('.form-check')) fb.remove();
    });
  });

  // ---- Sign-up wizard -----------------------------------------------------
  const form = document.querySelector('[data-wizard]');
  if (form) {
    const steps = Array.from(form.querySelectorAll('[data-step]'));
    const indicators = Array.from(form.querySelectorAll('[data-step-indicator]'));
    const progress = form.querySelector('[data-wizard-progress]');
    const status = form.querySelector('[data-wizard-status]');
    const back = form.querySelector('[data-wizard-back]');
    const next = form.querySelector('[data-wizard-next]');
    const last = steps.length - 1;
    const fieldsOf = (step) => Array.from(step.querySelectorAll('input, select, textarea')).filter((el) => el.type !== 'hidden');

    form.classList.add('is-wizard');
    let current = 0;

    // Jump to the first step that has a server-side error.
    let errorFields = [];
    try { errorFields = JSON.parse(form.dataset.errorFields || '[]'); } catch (e) { errorFields = []; }
    if (errorFields.length) {
      const idx = steps.findIndex((s) => errorFields.some((f) => s.querySelector('[name="' + f + '"]')));
      if (idx >= 0) current = idx;
    }

    const show = (i, dir) => {
      steps.forEach((s, idx) => {
        s.hidden = idx !== i;
        s.classList.remove('step-in-right', 'step-in-left');
      });
      if (dir && !reduceMotion) {
        void steps[i].offsetWidth;
        steps[i].classList.add(dir > 0 ? 'step-in-right' : 'step-in-left');
      }
      indicators.forEach((el, idx) => {
        el.classList.toggle('is-current', idx === i);
        el.classList.toggle('is-done', idx < i);
        el.querySelector('[data-dot-number]').innerHTML = idx < i ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : String(idx + 1);
      });
      if (progress) progress.style.width = ((i + 1) / steps.length) * 100 + '%';
      back.style.visibility = i === 0 ? 'hidden' : 'visible';
      next.hidden = i === last;
      form.classList.toggle('on-last-step', i === last);
      const legend = steps[i].querySelector('legend');
      if (status) status.textContent = 'Step ' + (i + 1) + ' of ' + steps.length + (legend ? ': ' + legend.textContent.trim() : '');
      current = i;
    };

    const validateStep = (step) => {
      syncMatch();
      for (const el of fieldsOf(step)) {
        if (!el.checkValidity()) {
          el.classList.add('is-invalid');
          el.reportValidity();
          el.focus();
          shake();
          return false;
        }
      }
      return true;
    };

    const focusFirst = (step) => {
      const el = fieldsOf(step).find((f) => f.classList.contains('is-invalid')) || fieldsOf(step)[0];
      if (el) el.focus({ preventScroll: true });
    };

    // ---- Live duplicate check: student ID number, email and name ----------
    const checkUrl = form.dataset.checkUrl;
    const signInUrl = (document.querySelector('#tab-login') || {}).href || '';
    const field = (name) => form.querySelector('[name="' + name + '"]');
    const nameInputs = ['first_name', 'middle_name', 'last_name'].map(field).filter(Boolean);
    let pending = Promise.resolve();
    const timers = {};

    // Show (or clear) a "taken" message under a field and block the step while it is taken.
    const setTaken = (el, message) => {
      if (!el) return;
      const anchor = el.closest('.auth-field') || el;
      let fb = anchor.parentElement.querySelector('[data-live-feedback]');
      el.setCustomValidity(message || '');
      el.classList.toggle('is-invalid', !!message);
      if (!message) {
        if (fb) fb.remove();
        return;
      }
      if (!fb) {
        fb = document.createElement('div');
        fb.className = 'invalid-feedback d-block';
        fb.setAttribute('data-live-feedback', '');
        fb.setAttribute('role', 'alert');
        anchor.insertAdjacentElement('afterend', fb);
      }
      fb.textContent = message + ' ';
      if (signInUrl) {
        const a = document.createElement('a');
        a.href = signInUrl;
        a.dataset.authSwitch = 'login';
        a.className = 'font-semibold text-brand-700 underline';
        a.textContent = 'Sign in';
        fb.appendChild(a);
      }
    };

    const runCheck = (what) => {
      if (!checkUrl) return pending;
      const params = new URLSearchParams();
      if (what === 'student_number' && field('student_number').value.trim()) params.set('student_number', field('student_number').value.trim());
      if (what === 'email' && field('email').value.trim()) params.set('email', field('email').value.trim());
      if (what === 'name' && field('first_name').value.trim() && field('last_name').value.trim()) {
        nameInputs.forEach((el) => params.set(el.name, el.value.trim()));
      }
      if (![...params.keys()].length) return pending;

      pending = fetch(checkUrl + '?' + params.toString(), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then((r) => (r.ok ? r.json() : {}))
        .then((data) => {
          if ('student_number' in data) setTaken(field('student_number'), data.student_number);
          if ('email' in data) setTaken(field('email'), data.email);
          if ('name' in data) {
            setTaken(field('last_name'), data.name);
            field('first_name').classList.toggle('is-invalid', !!data.name);
          }
        })
        .catch(() => { /* offline or rate-limited: the server checks again on submit */ });
      return pending;
    };

    const watch = (el, what) => {
      if (!el) return;
      el.addEventListener('change', () => { clearTimeout(timers[what]); runCheck(what); });
      el.addEventListener('input', () => {
        // Clear the old verdict immediately so the student isn't stuck, then re-check.
        (what === 'name' ? [field('last_name')] : [el]).forEach((f) => setTaken(f, ''));
        if (what === 'name') field('first_name').classList.remove('is-invalid');
        clearTimeout(timers[what]);
        timers[what] = setTimeout(() => runCheck(what), 700);
      });
    };
    watch(field('student_number'), 'student_number');
    watch(field('email'), 'email');
    nameInputs.forEach((el) => watch(el, 'name'));

    next.addEventListener('click', async () => {
      // Wait for a duplicate check that is still running (e.g. triggered by leaving the field).
      next.disabled = true;
      await pending;
      next.disabled = false;
      if (!validateStep(steps[current])) return;
      show(current + 1, 1);
      focusFirst(steps[current]);
    });
    back.addEventListener('click', () => {
      show(current - 1, -1);
      focusFirst(steps[current]);
    });

    // Enter moves forward instead of submitting half-filled data.
    form.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && current < last && e.target.tagName === 'INPUT' && e.target.type !== 'checkbox') {
        e.preventDefault();
        next.click();
      }
    });

    form.addEventListener('submit', (e) => {
      for (let i = 0; i < steps.length; i++) {
        if (i !== current) steps[i].hidden = false; // make fields focusable for reporting
        const bad = (syncMatch(), fieldsOf(steps[i]).find((el) => !el.checkValidity()));
        if (i !== current) steps[i].hidden = true;
        if (bad) {
          e.preventDefault();
          e.stopImmediatePropagation();
          show(i, i < current ? -1 : 1);
          validateStep(steps[i]);
          return;
        }
      }
    });

    show(current, 0);
    if (errorFields.length) focusFirst(steps[current]);
  }

  // ---- Sign in <-> Create account on the same page -------------------------
  const portal = document.querySelector('[data-auth-portal]');
  if (portal) {
    const box = portal.querySelector('[data-auth-panels]');
    const panels = {
      login: portal.querySelector('[data-panel="login"]'),
      signup: portal.querySelector('[data-panel="signup"]'),
    };
    const tabs = Array.from(portal.querySelectorAll('.auth-toggle-tab'));
    const urls = {};
    tabs.forEach((t) => { urls[t.dataset.authSwitch] = t.href; });
    const baseTitle = document.title.replace(/^[^·]*·\s*/, '');
    let mode = portal.dataset.mode;
    let busy = false;

    const firstField = (panel) => panel.querySelector('.is-invalid, input:not([type=hidden]):not([disabled]), select');
    const focusPanel = (panel) => {
      if (!window.matchMedia('(pointer: fine)').matches) return; // don't pop the keyboard on phones
      const el = firstField(panel);
      if (el && el.offsetParent !== null) el.focus({ preventScroll: true });
    };

    const setMode = (next) => {
      mode = next;
      portal.dataset.mode = next;
      tabs.forEach((t) => t.setAttribute('aria-selected', t.dataset.authSwitch === next ? 'true' : 'false'));
      document.title = panels[next].dataset.title + ' · ' + baseTitle;
    };

    const switchTo = (next, push) => {
      if (next === mode || busy || !panels[next]) return;
      const from = panels[mode];
      const to = panels[next];
      const forward = next === 'signup';

      if (push) history.pushState({ authMode: next }, '', urls[next]);
      setMode(next);

      if (reduceMotion) {
        from.hidden = true; from.setAttribute('inert', '');
        to.hidden = false; to.removeAttribute('inert');
        focusPanel(to);
        return;
      }

      busy = true;
      box.style.height = from.offsetHeight + 'px';
      box.classList.add('is-switching');

      // Place the incoming panel off to the side, stacked over the outgoing one.
      to.classList.remove('is-entering', 'is-out-left', 'is-out-right');
      to.classList.add(forward ? 'is-out-right' : 'is-out-left');
      to.hidden = false;
      to.removeAttribute('inert');
      from.setAttribute('inert', '');
      void to.offsetWidth;

      requestAnimationFrame(() => {
        box.style.height = to.offsetHeight + 'px';
        from.classList.remove('is-entering');
        from.classList.add(forward ? 'is-out-left' : 'is-out-right');
        to.classList.remove('is-out-left', 'is-out-right');
        to.classList.add('is-entering');
      });

      setTimeout(() => {
        from.hidden = true;
        from.classList.remove('is-out-left', 'is-out-right');
        box.classList.remove('is-switching');
        box.style.height = '';
        busy = false;
        focusPanel(to);
      }, 850);
    };

    document.addEventListener('click', (e) => {
      const link = e.target.closest('[data-auth-switch]');
      if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
      e.preventDefault();
      switchTo(link.dataset.authSwitch, true);
    });

    // Arrow keys move between the two tabs (tablist pattern).
    portal.querySelector('.auth-toggle').addEventListener('keydown', (e) => {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      e.preventDefault();
      const next = mode === 'login' ? 'signup' : 'login';
      switchTo(next, true);
      tabs.find((t) => t.dataset.authSwitch === next).focus();
    });

    // Browser back/forward between /login and /signup.
    window.addEventListener('popstate', () => {
      const next = /\/signup\/?$/.test(location.pathname) ? 'signup' : 'login';
      switchTo(next, false);
    });
    history.replaceState({ authMode: mode }, '', location.href);

    setTimeout(() => focusPanel(panels[mode]), reduceMotion ? 0 : 900);
  }

  // ---- Loading state on submit -------------------------------------------
  document.querySelectorAll('[data-loading-form]').forEach((f) => {
    f.addEventListener('submit', (e) => {
      if (e.defaultPrevented) return;
      const b = f.querySelector('button[type="submit"]');
      if (!b) return;
      b.disabled = true;
      b.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ' + f.dataset.loadingForm;
    });
  });
})();
