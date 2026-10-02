/* School Clearance Management System — shared UI behaviour. */
(function () {
  'use strict';

  const bs = window.bootstrap;

  // ---- Toasts ------------------------------------------------------------
  function showToast(message, type) {
    const wrap = document.getElementById('toastStack');
    if (!wrap || !message) return;
    const tone = {
      success: ['bi-check-circle-fill', 'text-[#1f6a3f]', 'Done'],
      error: ['bi-exclamation-octagon-fill', 'text-[#a3362a]', 'Action needed'],
      info: ['bi-info-circle-fill', 'text-[#285f94]', 'Notice'],
    }[type] || ['bi-info-circle-fill', 'text-[#285f94]', 'Notice'];

    const el = document.createElement('div');
    el.className = 'toast align-items-center';
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
    el.innerHTML =
      '<div class="flex items-start gap-3 p-3">' +
      '<i class="bi ' + tone[0] + ' ' + tone[1] + ' mt-0.5 text-lg" aria-hidden="true"></i>' +
      '<div class="flex-1"><div class="text-sm font-bold text-ink">' + tone[2] + '</div><div class="toast-msg text-sm text-ink-soft"></div></div>' +
      '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    el.querySelector('.toast-msg').textContent = message;
    wrap.appendChild(el);
    const t = new bs.Toast(el, { delay: type === 'error' ? 8000 : 5000 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
  }
  window.appToast = showToast;

  document.querySelectorAll('[data-flash-toast]').forEach((n) => showToast(n.dataset.message, n.dataset.flashToast));

  // ---- Confirmation dialogs for important actions -------------------------
  const confirmEl = document.getElementById('confirmModal');
  const confirmModal = confirmEl ? new bs.Modal(confirmEl) : null;
  let pendingForm = null;

  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.confirm && form.dataset.confirmed !== '1' && confirmModal) {
      e.preventDefault();
      pendingForm = form;
      confirmEl.querySelector('[data-confirm-title]').textContent = form.dataset.confirmTitle || 'Please confirm';
      confirmEl.querySelector('[data-confirm-body]').textContent = form.dataset.confirm;
      const btn = confirmEl.querySelector('[data-confirm-ok]');
      btn.textContent = form.dataset.confirmButton || 'Confirm';
      btn.className = 'btn ' + (form.dataset.confirmVariant === 'danger' ? 'btn-danger' : 'btn-primary');
      // A modal-hosted form: hide it first so the two dialogs don't stack.
      const host = form.closest('.modal');
      if (host) bs.Modal.getInstance(host)?.hide();
      confirmModal.show();
      return;
    }

    // Prevent double submissions.
    if (form.dataset.submitting === '1') {
      e.preventDefault();
      return;
    }
    form.dataset.submitting = '1';
    form.querySelectorAll('button[type="submit"]').forEach((b) => {
      b.disabled = true;
      b.dataset.originalHtml = b.innerHTML;
      b.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Working…';
    });
  });

  confirmEl?.querySelector('[data-confirm-ok]')?.addEventListener('click', () => {
    if (!pendingForm) return;
    pendingForm.dataset.confirmed = '1';
    confirmModal.hide();
    if (pendingForm.requestSubmit) pendingForm.requestSubmit();
    else pendingForm.submit();
  });

  // Re-enable forms when navigating back via the browser cache.
  window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting="1"]').forEach((f) => {
      f.dataset.submitting = '';
      f.dataset.confirmed = '';
      f.querySelectorAll('button[type="submit"]').forEach((b) => {
        b.disabled = false;
        if (b.dataset.originalHtml) b.innerHTML = b.dataset.originalHtml;
      });
    });
  });

  // ---- Receipt / document preview modal -----------------------------------
  const previewEl = document.getElementById('previewModal');
  if (previewEl) {
    previewEl.addEventListener('show.bs.modal', (e) => {
      const trigger = e.relatedTarget;
      if (!trigger) return;
      const url = trigger.dataset.previewUrl;
      const isPdf = (trigger.dataset.previewType || '').includes('pdf');
      previewEl.querySelector('.modal-title').textContent = trigger.dataset.previewTitle || 'Document preview';
      previewEl.querySelector('[data-preview-download]').href = url + (url.includes('?') ? '&' : '?') + 'download=1';
      const body = previewEl.querySelector('[data-preview-body]');
      body.innerHTML = '';
      if (isPdf) {
        const frame = document.createElement('iframe');
        frame.src = url;
        frame.title = 'PDF preview';
        frame.className = 'h-[70vh] w-full rounded-xl border border-line bg-white';
        body.appendChild(frame);
      } else {
        const img = document.createElement('img');
        img.src = url;
        img.alt = trigger.dataset.previewTitle || 'Uploaded receipt';
        img.className = 'mx-auto max-h-[70vh] rounded-xl border border-line bg-white object-contain';
        body.appendChild(img);
      }
    });
    previewEl.addEventListener('hidden.bs.modal', () => {
      previewEl.querySelector('[data-preview-body]').innerHTML = '';
    });
  }

  // ---- Local file preview before upload -----------------------------------
  document.querySelectorAll('[data-file-preview]').forEach((input) => {
    const target = document.querySelector(input.dataset.filePreview);
    const meta = input.dataset.fileMeta ? document.querySelector(input.dataset.fileMeta) : null;
    const maxBytes = parseInt(input.dataset.maxBytes || '0', 10);
    let lastUrl = null;

    input.addEventListener('change', () => {
      if (!target) return;
      if (lastUrl) URL.revokeObjectURL(lastUrl);
      target.innerHTML = '';
      const file = input.files && input.files[0];
      if (!file) {
        target.innerHTML = '<div class="empty-state"><i class="bi bi-image"></i><span>No file selected yet.</span></div>';
        if (meta) meta.textContent = '';
        return;
      }
      const okTypes = ['image/jpeg', 'image/png', 'application/pdf'];
      if (!okTypes.includes(file.type)) {
        showToast('Only JPG, JPEG, PNG or PDF files are allowed.', 'error');
        input.value = '';
        return;
      }
      if (maxBytes && file.size > maxBytes) {
        showToast('That file is larger than ' + Math.round(maxBytes / 1048576) + ' MB.', 'error');
        input.value = '';
        return;
      }
      lastUrl = URL.createObjectURL(file);
      if (file.type === 'application/pdf') {
        const frame = document.createElement('iframe');
        frame.src = lastUrl;
        frame.title = 'Selected PDF preview';
        frame.className = 'h-[360px] w-full rounded-xl border border-line bg-white';
        target.appendChild(frame);
      } else {
        const img = document.createElement('img');
        img.src = lastUrl;
        img.alt = 'Selected receipt preview';
        img.className = 'mx-auto max-h-[360px] rounded-xl object-contain';
        target.appendChild(img);
      }
      if (meta) meta.textContent = file.name + ' · ' + (file.size >= 1048576 ? (file.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(file.size / 1024)) + ' KB');
    });
  });

  // ---- Sidebar: offcanvas on mobile, collapsible on desktop ----------------
  const sidebar = document.getElementById('appSidebar');
  const storageKey = 'ncr.sidebarCollapsed';
  try {
    if (localStorage.getItem(storageKey) === '1') document.body.classList.add('sidebar-collapsed');
  } catch (err) { /* storage unavailable */ }

  document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (window.matchMedia('(min-width: 992px)').matches) {
        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (err) { /* ignore */ }
      } else if (sidebar) {
        bs.Offcanvas.getOrCreateInstance(sidebar).toggle();
      }
    });
  });

  // ---- Tooltips ------------------------------------------------------------
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bs.Tooltip(el));

  // ---- Client-side list filter (progressive enhancement) --------------------
  document.querySelectorAll('[data-filter-input]').forEach((input) => {
    const list = document.querySelector(input.dataset.filterInput);
    const counter = input.dataset.filterCount ? document.querySelector(input.dataset.filterCount) : null;
    if (!list) return;
    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      let shown = 0;
      list.querySelectorAll('[data-filter-text]').forEach((row) => {
        const match = row.dataset.filterText.toLowerCase().includes(q);
        row.classList.toggle('hidden', !match);
        if (match) shown++;
      });
      if (counter) counter.textContent = shown + ' shown';
    });
  });

  // ---- Re-upload reason presets ---------------------------------------------
  document.querySelectorAll('[data-reason-select]').forEach((select) => {
    const textarea = document.querySelector(select.dataset.reasonSelect);
    select.addEventListener('change', () => {
      const opt = select.options[select.selectedIndex];
      if (textarea && opt && opt.dataset.template && (!textarea.value.trim() || textarea.dataset.autofilled === '1')) {
        textarea.value = opt.dataset.template;
        textarea.dataset.autofilled = '1';
      }
    });
    textarea?.addEventListener('input', () => { textarea.dataset.autofilled = '0'; });
  });

  // ---- Checklist gate: approve button enabled only when all boxes checked ---
  document.querySelectorAll('[data-checklist]').forEach((group) => {
    const boxes = group.querySelectorAll('input[type="checkbox"]');
    const button = document.querySelector(group.dataset.checklist);
    const counter = group.dataset.checklistCount ? document.querySelector(group.dataset.checklistCount) : null;
    const sync = () => {
      const checked = Array.from(boxes).filter((b) => b.checked).length;
      if (button) button.disabled = checked !== boxes.length;
      if (counter) counter.textContent = checked + ' of ' + boxes.length + ' checked';
    };
    boxes.forEach((b) => b.addEventListener('change', sync));
    sync();
  });

  // ---- Decision cards (teacher): reveal fields for the chosen outcome -------
  document.querySelectorAll('[data-decision-form]').forEach((form) => {
    const radios = form.querySelectorAll('input[name="decision"]');
    const sync = () => {
      const value = (Array.from(radios).find((r) => r.checked) || {}).value || '';
      form.querySelectorAll('[data-show-for]').forEach((el) => {
        el.classList.toggle('hidden', !el.dataset.showFor.split(',').includes(value));
      });
      form.querySelectorAll('[data-decision-card]').forEach((card) => {
        card.classList.toggle('is-selected', card.dataset.decisionCard === value);
      });
      const submit = form.querySelector('[data-decision-submit]');
      if (submit) {
        submit.disabled = !value;
        const label = { PASSED: 'Save & sign approval', INC: 'Save as INC', FAILED: 'Save as FAILED' }[value] || 'Save decision';
        const sign = form.querySelector('input[name="sign_now"]');
        submit.querySelector('span').textContent = value === 'PASSED' && sign && !sign.checked ? 'Save as PASSED' : label;
        form.dataset.confirm = {
          PASSED: 'Mark this subject PASSED? Your e-signature will be added to the student\'s clearance automatically. Signed approvals cannot be changed.',
          INC: 'Mark this subject INC? The student will be notified of the missing requirement(s).',
          FAILED: 'Mark this subject FAILED? The student will be required to re-enroll. This decision cannot be undone.',
        }[value] || '';
        form.dataset.confirmVariant = value === 'FAILED' ? 'danger' : '';
        form.dataset.confirmButton = submit.querySelector('span').textContent;
      }
    };
    radios.forEach((r) => r.addEventListener('change', sync));
    form.querySelector('input[name="sign_now"]')?.addEventListener('change', sync);
    sync();

    // Dynamic INC requirement rows.
    const addBtn = form.querySelector('[data-add-requirement]');
    const list = form.querySelector('[data-requirement-list]');
    addBtn?.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'flex items-center gap-2';
      row.innerHTML = '<input type="text" name="requirements[]" maxlength="255" class="form-control" placeholder="e.g. Final laboratory activity" aria-label="Missing requirement">' +
        '<button type="button" class="btn btn-light px-3" aria-label="Remove requirement"><i class="bi bi-x-lg"></i></button>';
      row.querySelector('button').addEventListener('click', () => row.remove());
      list.appendChild(row);
      row.querySelector('input').focus();
    });
  });
})();
