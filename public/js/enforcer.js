/* public/js/enforcer.js — SPA + SweetAlert edit form (no Bootstrap modals) */
(function () {
  if (window.__enforcerBound) return;
  window.__enforcerBound = true;

  // ----- helpers -----
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const $ = (sel, root = document) => root.querySelector(sel);
  const $all = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function root() { return document.getElementById('enforcerContainer'); }
  function partialUrl() { return root()?.dataset.partialUrl || '/enforcer/partial'; }

  function setLoading(on) {
    const wrap = $('#table-container');
    if (wrap) wrap.classList.toggle('is-loading', !!on);
  }

  function injectTable(html, targetUrlForHistory) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const partial = doc.querySelector('#table-container');
    const wrap = $('#table-container');
    if (!wrap) return;
    wrap.innerHTML = partial ? partial.innerHTML : html;
    if (targetUrlForHistory) history.pushState({}, '', targetUrlForHistory);
  }

  function currentQuery() {
    const url = new URL(location.href);
    const show  = url.searchParams.get('show') || 'active';
    const sort  = $('#sort_table')?.value || url.searchParams.get('sort_option') || 'date_desc';
    const search = $('#search_input')?.value ?? url.searchParams.get('search') ?? '';

    const q = new URLSearchParams();
    q.set('show', show);
    q.set('sort_option', sort);
    if (search) q.set('search', search);
    return q;
  }

  function loadTable(pushState = true) {
    const q = currentQuery();
    const url = '/enforcer?' + q.toString();

    setLoading(true);
    fetch(partialUrl() + '?' + q.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.text())
      .then(html => injectTable(html, pushState ? url : null))
      .catch(console.error)
      .finally(() => setLoading(false));
  }

  function toastOk(msg) {
    if (!window.Swal) return;
    Swal.fire({ toast:true, position:'top-end', icon:'success', title: msg || 'Saved', timer:1500, showConfirmButton:false });
  }

  // ----- page init (hard load + SPA) -----
  function initPage(){
    if (!root()) return;
    // if server already rendered the table, no need to reload now
    // else fetch initial list with current params
    if (!$('#table-container table')) loadTable(false);
  }
  document.addEventListener('DOMContentLoaded', initPage);
  document.addEventListener('page:loaded', initPage);

  // ----- Filters -----
  document.addEventListener('click', (e) => {
    if (e.target.closest('#enforcerContainer #search_btn')) { e.preventDefault(); loadTable(true); }
  });
  document.addEventListener('keydown', (e) => {
    if (e.target && e.target.matches('#enforcerContainer #search_input') && e.key === 'Enter') {
      e.preventDefault(); loadTable(true);
    }
  });
  document.addEventListener('change', (e) => {
    if (e.target && e.target.matches('#enforcerContainer #sort_table')) loadTable(true);
  });

  // ----- Pagination -----
  document.addEventListener('click', (e) => {
    const a = e.target.closest('#enforcerContainer .pagination a');
    if (!a) return;
    e.preventDefault();
    setLoading(true);
    fetch(a.href.replace('/enforcer', partialUrl()), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.text())
      .then(html => {
        // keep history in sync with the real URL (not the partial)
        injectTable(html, a.href);
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  });



  // Back/forward
  window.addEventListener('popstate', () => { if (root()) loadTable(false); });

  // =========================================
  // EDIT via SweetAlert (no Bootstrap modal)
  // =========================================
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('#enforcerContainer .editBtn');
    if (!btn) return;
    e.preventDefault();

    const data = {
      url: btn.getAttribute('data-url'),
      badge_num: btn.getAttribute('data-badge') || '',
      fname: btn.getAttribute('data-fname') || '',
      mname: btn.getAttribute('data-mname') || '',
      lname: btn.getAttribute('data-lname') || '',
      phone: btn.getAttribute('data-phone') || '',
      ticket_start: btn.getAttribute('data-ticket-start') || '',
      ticket_end: btn.getAttribute('data-ticket-end') || '',
    };

    const formHtml = `
      <div class="text-start">
        <div class="mb-2">
          <label class="form-label">Badge #</label>
          <input id="sw_badge" class="form-control" maxlength="4" value="${data.badge_num}" disabled>
        </div>
        <div class="row g-2">
          <div class="col-md-4 mb-2">
            <label class="form-label">First Name</label>
            <input id="sw_fname" class="form-control" value="${data.fname}">
          </div>
          <div class="col-md-4 mb-2">
            <label class="form-label">Middle Name</label>
            <input id="sw_mname" class="form-control" value="${data.mname}">
          </div>
          <div class="col-md-4 mb-2">
            <label class="form-label">Last Name</label>
            <input id="sw_lname" class="form-control" value="${data.lname}">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Phone (11 digits)</label>
          <input id="sw_phone" class="form-control" maxlength="11" value="${data.phone}">
        </div>
        <div class="row g-2">
          <div class="col-md-6 mb-2">
            <label class="form-label">Ticket Start</label>
            <input id="sw_tstart" class="form-control" value="${data.ticket_start}" disabled>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Ticket End</label>
            <input id="sw_tend" class="form-control" value="${data.ticket_end}" disabled>
          </div>
        </div>
        <div class="mb-1">
          <label class="form-label">Reset Password (optional)</label>
          <div class="input-group">
            <input id="sw_pwd" class="form-control" placeholder="Leave blank to keep current">
            <button class="btn btn-outline-secondary" type="button" id="sw_gen">Generate</button>
          </div>
        </div>
      </div>
    `;

    Swal.fire({
      title: 'Edit Enforcer',
      html: formHtml,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Save',
      didOpen: () => {
        const gen = document.getElementById('sw_gen');
        gen?.addEventListener('click', () => {
          const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
          const len = 12;
          let out = '';
          if (crypto?.getRandomValues) {
            crypto.getRandomValues(new Uint32Array(len)).forEach(n => out += chars[n % chars.length]);
          } else {
            for (let i=0;i<len;i++) out += chars[Math.floor(Math.random()*chars.length)];
          }
          document.getElementById('sw_pwd').value = out;
        });
      },
      preConfirm: async () => {
        // basic validation
        const badge = document.getElementById('sw_badge').value.trim();
        const fname = document.getElementById('sw_fname').value.trim();
        const mname = document.getElementById('sw_mname').value.trim();
        const lname = document.getElementById('sw_lname').value.trim();
        const phone = document.getElementById('sw_phone').value.trim();
        const tstart= document.getElementById('sw_tstart').value.trim();
        const tend  = document.getElementById('sw_tend').value.trim();
        const pwd   = document.getElementById('sw_pwd').value.trim();

        if (!fname || !lname) { Swal.showValidationMessage('First and Last name are required'); return false; }
        if (phone && !/^\d{11}$/.test(phone)) { Swal.showValidationMessage('Phone must be 11 digits'); return false; }

        // submit
        const fd = new FormData();
        fd.set('_method','PUT');
        if (badge)  fd.set('badge_num', badge);
        if (fname)  fd.set('fname', fname);
        if (mname)  fd.set('mname', mname);
        if (lname)  fd.set('lname', lname);
        if (phone)  fd.set('phone', phone);
        if (tstart) fd.set('ticket_start', tstart);
        if (tend)   fd.set('ticket_end', tend);
        if (pwd)    fd.set('password', pwd);

        try {
          const res = await fetch(data.url, {
            method: 'POST',
            headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: fd
          });
          if (!res.ok) {
            let msg = 'Update failed';
            try { const j = await res.json(); msg = j.message || msg; } catch { msg = await res.text() || msg; }
            throw new Error(msg);
          }
          const payload = await res.json().catch(()=>({}));
          return payload;
        } catch (err) {
          Swal.showValidationMessage(String(err.message || err));
          return false;
        }
      },
      allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
      if (!result.isConfirmed) return;
      toastOk('Updated');
      loadTable(false);
      if (result.value?.raw_password) {
        Swal.fire({
          icon:'info',
          title:'Password Reset',
          html:`<div class="text-start"><strong>New Password:</strong> ${result.value.raw_password}</div>`
        });
      }
    });
  });
  // ====== CREATE (Add Enforcer) with "Add another?" ======
document.addEventListener('submit', async (e) => {
  const form = e.target.closest('#addEnforcerForm');
  if (!form) return;

  e.preventDefault();

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const fd = new FormData(form);

  try {
    const res = await fetch(form.action, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
      body: fd
    });

    // handle validation errors
    if (res.status === 422) {
      let msg = 'Please check your inputs.';
      try {
        const j = await res.json();
        if (j.errors) msg = Object.values(j.errors).flat().join('\n');
        else if (j.message) msg = j.message;
      } catch {}
      await Swal.fire({ icon:'error', title:'Validation error', text: msg });
      return;
    }

    if (!res.ok) {
      const t = await res.text().catch(()=> '');
      throw new Error(t || 'Request failed.');
    }

    const payload = await res.json().catch(()=> ({}));

    // success + ask if user wants to add another
    const ask = await Swal.fire({
      icon: 'success',
      title: 'Enforcer added',
      html: payload.raw_password
        ? `<div class="text-start"><strong>Temporary Password:</strong> ${payload.raw_password}</div>
           <div class="mt-2">Add another?</div>`
        : `Add another?`,
      showCancelButton: true,
      confirmButtonText: 'Add another',
      cancelButtonText: 'Go to list',
      reverseButtons: true
    });

    if (ask.isConfirmed) {
      // OPTION A: quick reset (instant)
      form.reset();

      // Try to refresh auto-generated numbers (badge/ticket range) from server.
      // Your controller returns a partial on AJAX for /enforcer/create.
      try {
        const html = await fetch('/enforcer/create', {
          headers: { 'X-Requested-With':'XMLHttpRequest' }
        }).then(r => r.text());

        const doc = new DOMParser().parseFromString(html, 'text/html');
        const freshForm = doc.querySelector('#addEnforcerForm');
        if (freshForm) {
          // Replace the whole form so next badge / ticket range update correctly
          form.outerHTML = freshForm.outerHTML;
        } else {
          // fallback: just focus the first input
          (document.querySelector('#addEnforcerForm input, #addEnforcerForm select') || {}).focus?.();
        }
      } catch {
        // fallback already handled by form.reset()
      }
    } else {
      // Go back to list (use partial reload if list is present)
      const listRoot = document.getElementById('enforcerContainer');
      if (listRoot && typeof loadTable === 'function') {
        history.pushState({}, '', '/enforcer');
        loadTable(false);
      } else {
        location.href = '/enforcer';
      }
    }

  } catch (err) {
    console.error(err);
    await Swal.fire({ icon:'error', title:'Error', text: err.message || String(err) });
  }
});


  // =========================================
  // Activate / Inactivate (already SweetAlert)
  // =========================================
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('#enforcerContainer .status-btn');
    if (!btn) return;
    e.preventDefault();

    const action = btn.getAttribute('data-action');
    const method = (btn.getAttribute('data-method') || 'POST').toUpperCase();

    const { isConfirmed, value: adminPwd } = await Swal.fire({
      title: method === 'DELETE' ? 'Confirm Inactivate' : 'Confirm Activate',
      text: 'Enter admin password to proceed.',
      input: 'password',
      inputAttributes: { autocapitalize:'off', autocomplete:'current-password', maxlength:128 },
      showCancelButton: true,
      confirmButtonText: 'Confirm',
      cancelButtonText: 'Cancel',
      preConfirm: (val) => { if (!val) { Swal.showValidationMessage('Admin password is required'); return false; } return val; }
    });
    if (!isConfirmed) return;

    const fd = new FormData();
    if (method !== 'POST') fd.set('_method', method);
    fd.set('admin_password', adminPwd);

    setLoading(true);
    try {
      const res = await fetch(action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
        body: fd
      });
      if (!res.ok) {
        let msg = 'Action failed.';
        try { const j = await res.json(); msg = j.message || msg; } catch { msg = await res.text() || msg; }
        await Swal.fire({ icon:'error', title:'Error', text: msg }); return;
      }
      await Swal.fire({ icon:'success', title: method === 'DELETE' ? 'Enforcer Inactivated' : 'Enforcer Activated', timer:1200, showConfirmButton:false });
      loadTable(false);
    } catch (err) {
      console.error(err);
      await Swal.fire({ icon:'error', title:'Network Error', text:'Please try again.' });
    } finally { setLoading(false); }
  });

})();
