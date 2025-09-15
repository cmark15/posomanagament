// resources/js/violator.js
document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('violator-dashboard');
  if (!root) return;

  // --- Ask Notification permission early (old behavior) ---
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().catch(() => {});
  }

  // --- Greeting ---
  const greetEl = document.getElementById('greeting-text');
  if (greetEl) {
    const hr = new Date().getHours();
    const greet = hr < 12 ? 'Good morning' : hr < 18 ? 'Good afternoon' : 'Good evening';
    greetEl.textContent = `${greet}, `;
  }

  // --- Office hours hint on weekends ---
  const officeHoursSpan = document.getElementById('officeHours');
  if (officeHoursSpan) {
    const day = new Date().getDay(); // 0 = Sun, 6 = Sat
    if (day === 0 || day === 6) {
      officeHoursSpan.textContent = 'Mon–Fri, 8:00 AM–5:00 PM (Closed today)';
    }
  }

  // --- Login/Overdue reminders (old flow restored) ---
  const loginOk   = root.dataset.loginSuccess  === '1';
  const overdueOk = root.dataset.ticketOverdue === '1';

  const showLoginReminder = () => {
    if (!loginOk) return Promise.resolve();
    return Swal.fire({
      title: 'Reminder',
      text:  'Your unpaid ticket must be settled within 3 weekdays. Failure to pay within this period may result in forwarding your ticket to the LTO.',
      icon:  'info',
      confirmButtonText: 'Okay',
      customClass: { confirmButton: 'btn btn-success' },
      buttonsStyling: false
    });
  };

  const notifyOverdueNativeOrSW = () => {
    const payload = {
      title: 'Ticket Overdue Reminder',
      options: { body: 'Your ticket is overdue. Pay within 3 weekdays or it will be forwarded to the LTO.' }
    };

    if ('Notification' in window && Notification.permission === 'granted') {
      // Native notification
      try { new Notification(payload.title, payload.options); } catch {}
      return;
    }

    // Service Worker fallback (try existing, then /serviceworker.js, then /sw.js)
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.getRegistration().then(reg => {
        if (reg && reg.active) {
          reg.active.postMessage(payload);
          return;
        }
        // Try your app SW first
        navigator.serviceWorker.register('/serviceworker.js')
          .then(r => { if (r.active) r.active.postMessage(payload); })
          .catch(() => {
            // Legacy fallback path used in older snippet
            navigator.serviceWorker.register('/sw.js')
              .then(r => { if (r.active) r.active.postMessage(payload); })
              .catch(() => {});
          });
      }).catch(() => {});
    }
  };

  const showOverdueWarning = () => {
    if (!overdueOk) return Promise.resolve();
    return Swal.fire({
      title: 'Warning!',
      text:  'You have an unpaid ticket that has passed its due date. Please settle it immediately to avoid forwarding to the LTO.',
      icon:  'warning',
      confirmButtonText: 'Understood',
      customClass: { confirmButton: 'btn btn-danger' },
      buttonsStyling: false
    }).then(() => notifyOverdueNativeOrSW());
  };

  showLoginReminder()
    .then(showOverdueWarning)
    .catch(err => console.error(err));

  // ===== Help FAB helpers (old behavior) =====
  const copyBtn = document.getElementById('copyPayInfo');
  const helpModalEl = document.getElementById('helpModal');
  const openHelpDeepLink = document.getElementById('openHelpDeepLink');

  // Copy key payment info to clipboard
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      const text = [
        'POSO Office, City Hall compound, San Carlos City, Pangasinan',
        'Hours: Mon–Fri, 8:00 AM–5:00 PM',
        'Bring: Ticket #, Valid ID, exact amount'
      ].join('\n');

      try {
        await navigator.clipboard.writeText(text);
        copyBtn.innerHTML = '<i class="bi bi-check2-circle"></i> Copied';
        setTimeout(() => (copyBtn.innerHTML = '<i class="bi bi-clipboard-check"></i> Copy payment info'), 1600);
      } catch {
        alert('Unable to copy. Please copy manually.');
      }
    });
  }

  // Deep link: /violator/dashboard#help opens modal
  if (location.hash === '#help' && helpModalEl && window.bootstrap && bootstrap.Modal) {
    const modal = new bootstrap.Modal(helpModalEl);
    modal.show();
  }

  // Replace hash with #help when user clicks "Open full guide"
  if (openHelpDeepLink && helpModalEl) {
    openHelpDeepLink.addEventListener('click', (e) => {
      e.preventDefault();
      if (window.history && history.replaceState) {
        history.replaceState({}, '', '#help');
      }
      if (window.bootstrap && bootstrap.Modal) {
        const modal = new bootstrap.Modal(helpModalEl);
        modal.show();
      }
    });
  }

  // Keyboard shortcut: press "?" to open Help
  document.addEventListener('keydown', (e) => {
    if ((e.key === '?' || (e.shiftKey && e.key === '/')) && helpModalEl && window.bootstrap && bootstrap.Modal) {
      const modal = new bootstrap.Modal(helpModalEl);
      modal.show();
    }
  });

  // --- Simple search + "show N rows" for Paid table ---
  const paidTable  = document.getElementById('paidTable');
  const paidSearch = document.getElementById('paidSearch');
  const paidShow   = document.getElementById('paidShow');

  const filterPaid = () => {
    if (!paidTable) return;
    const q = (paidSearch?.value || '').toLowerCase().trim();
    const maxRows = parseInt(paidShow?.value || '10', 10);
    let shown = 0;

    Array.from(paidTable.querySelectorAll('tbody tr')).forEach(tr => {
      const text = tr.innerText.toLowerCase();
      const match = !q || text.includes(q);
      if (match && shown < maxRows) {
        tr.style.display = '';
        shown++;
      } else {
        tr.style.display = 'none';
      }
    });
  };

  paidSearch?.addEventListener('input', filterPaid);
  paidShow?.addEventListener('change', filterPaid);
  filterPaid();
});
