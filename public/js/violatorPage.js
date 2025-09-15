/* public/js/violator.js — SPA list, Bootstrap modal for history, SweetAlert prompts for Paid/Admin */
(function ($) {
  if (window.__violatorBound) return;
  window.__violatorBound = true;

  const csrf = $('meta[name="csrf-token"]').attr('content') || '';

  /* ======== CONFIG ======== */
    function cfg() {
    const p = document.getElementById('violatorPage');
    return {
        partialUrl:  p?.dataset.partialUrl || '/violatorTable/partial',
        statusUrl:   p?.dataset.statusUrl  || '/paid',  // base, we'll append /{id}/status
        paidId:      Number(p?.dataset.paidId || 0),
        pendingId:   Number(p?.dataset.pendingId || 0),
        unpaidId:    Number(p?.dataset.unpaidId || 0),
        cancelledId: Number(p?.dataset.cancelledId || 0),
        csrf:        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    };
    }
  // Hide the violator modal while running a SweetAlert to avoid focus trap.
    async function withModalHidden(run) {
    const modalEl = document.getElementById('violatorModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) modal = new bootstrap.Modal(modalEl, { backdrop: true, focus: true });

    // Hide and wait until fully hidden so focus trap is released
    const waitHidden = new Promise(resolve => {
        const once = () => { modalEl.removeEventListener('hidden.bs.modal', once); resolve(); };
        modalEl.addEventListener('hidden.bs.modal', once, { once: true });
    });
    modal.hide();
    await waitHidden;

    try {
        return await run();     // run SweetAlert (or anything)
    } finally {
        modal.show();           // restore modal afterwards
    }
    }


  // ---------- SPA list ----------
  function getParamsFromUI() {
    return {
      sort_option:  $('#sort_table').val()   || 'date_desc',
      vehicle_type: $('#vehicle_type').val() || 'all',
      search:       $('#search_input').val() || '',
    };
  }
  function getParamsFromURL() {
    const u = new URL(location.href);
    return {
      sort_option:  u.searchParams.get('sort_option')  || 'date_desc',
      vehicle_type: u.searchParams.get('vehicle_type') || 'all',
      search:       u.searchParams.get('search')       || '',
      page:         u.searchParams.get('page')         || '1',
    };
  }
  function pushUrl(q) {
    const p = new URLSearchParams();
    if (q.sort_option !== 'date_desc') p.set('sort_option', q.sort_option);
    if (q.vehicle_type !== 'all')      p.set('vehicle_type', q.vehicle_type);
    if ((q.search || '').trim() !== '')p.set('search', q.search.trim());
    if (q.page && q.page !== '1')      p.set('page', q.page);
    history.pushState({}, '', location.pathname + (p.toString() ? '?'+p.toString() : ''));
  }
  function setLoading(on) {
    const wrap = document.getElementById('violatorContainer');
    if (!wrap) return;
    wrap.classList.toggle('is-loading', !!on);
    if (on) {
      wrap.style.position = 'relative';
      wrap.insertAdjacentHTML('beforeend',
        '<div class="ajax-overlay" data-vtr-loader style="position:absolute;inset:0;background:rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center;"><div class="spinner-border" role="status"></div></div>');
    } else {
      wrap.querySelector('[data-vtr-loader]')?.remove();
    }
  }
  function injectTable(html, newUrlParams) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const partial = doc.querySelector('#vtr-table-wrap');
    const wrap = document.getElementById('violatorContainer');
    if (wrap) wrap.innerHTML = partial ? partial.outerHTML : html;
    if (newUrlParams) pushUrl(newUrlParams);
  }
  function loadPage(page='1', push=true) {
    const c = cfg();
    const base = getParamsFromUI();
    const q = Object.assign({}, base, { page });
    setLoading(true);
    $.ajax({
      url: c.partialUrl,
      data: q,
      headers: { 'X-Requested-With':'XMLHttpRequest' }
    })
    .done(html => injectTable(html, push ? q : null))
    .always(() => setLoading(false));
  }

  // init (hard load + SPA swap)
  function initList() {
    if (!$('#violatorContainer').length) return;

    // If table not rendered yet (after SPA swap), sync UI from URL then load
    if (!$('#vtr-table-wrap').length) {
      const state = getParamsFromURL();
      $('#sort_table').val(state.sort_option);
      $('#vehicle_type').val(state.vehicle_type);
      $('#search_input').val(state.search);
      loadPage(state.page, /*push=*/false);
    }
  }
  document.addEventListener('DOMContentLoaded', initList);
  document.addEventListener('page:loaded', initList);

  // filters
  $(document).on('click',   '#search_btn',     () => loadPage('1'));
  $(document).on('keydown', '#search_input',   (e)=>{ if (e.key==='Enter'){ e.preventDefault(); loadPage('1'); }});
  $(document).on('change',  '#sort_table',     () => loadPage('1'));
  $(document).on('change',  '#vehicle_type',   () => loadPage('1'));

  // pagination
  $(document).on('click', '#violatorContainer .pagination a', function(e){
    e.preventDefault();
    const newPage = new URL(this.href).searchParams.get('page') || '1';
    loadPage(newPage);
  });

  // back/forward
  window.addEventListener('popstate', () => {
    if (!$('#violatorContainer').length) return;
    const s = getParamsFromURL();
    $('#sort_table').val(s.sort_option);
    $('#vehicle_type').val(s.vehicle_type);
    $('#search_input').val(s.search);
    loadPage(s.page, /*push=*/false);
  });

  // ---------- History modal (Bootstrap) ----------
  let currentDetailUrl = null;
  function openHistory(url) {
    currentDetailUrl = url;

    const modalEl = document.getElementById('violatorModal');
    // Always move modal under <body> to avoid stacking-context issues
    if (modalEl.parentElement !== document.body) document.body.appendChild(modalEl);

    // Clean old backdrops to prevent perma-dim
    document.querySelectorAll('.modal-backdrop').forEach(bd => bd.remove());

    $('#violatorModalBody').html('<div class="py-5 text-center"><div class="spinner-border" role="status"></div></div>');
    const modal = new bootstrap.Modal(modalEl, { backdrop: true, focus: true });
    modal.show();

    // fetch details
    $.get(url, html => {
      $('#violatorModalBody').html(html);

      // Ensure only one backdrop and correct z-index
      const backs = Array.from(document.querySelectorAll('.modal-backdrop'));
      backs.slice(0, backs.length - 1).forEach(b => b.remove());
      backs.at(-1)?.style && (backs.at(-1).style.zIndex = '1990');
      modalEl.style.zIndex = '2000';
    });
  }

  $(document).on('click', '.view-tickets-btn', function(e){
    e.preventDefault();
    const url = this.getAttribute('data-url');
    if (url) openHistory(url);
  });

  // Clear on hide; remove stray backdrops
  document.addEventListener('hidden.bs.modal', (ev) => {
    if (ev.target.id !== 'violatorModal') return;
    $('#violatorModalBody').empty();
    document.querySelectorAll('.modal-backdrop').forEach(bd => bd.remove());
  });

  // ---------- Status updates inside modal (SweetAlert prompts) ----------
  function postStatus(ticketId, data) {
    const c = cfg();
    return $.ajax({
        url: `${c.statusUrl}/${ticketId}/status`,
        method: 'POST',
        data: Object.assign({ _token: c.csrf }, data),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    }
    /* ======== SWEETALERT HELPERS (render INSIDE modal to avoid focus trap) ======== */
    async function askReferenceForLeavingPaid(ticketId) {
    const target = document.getElementById('violatorModal'); // render inside modal
    const { isConfirmed } = await Swal.fire({
        target,
        title: 'Reference Number',
        input: 'text',
        inputLabel: 'Enter 8-character reference #',
        inputPlaceholder: 'e.g., ABCD1234',
        inputAttributes: { maxlength: 8, minlength: 8, autocapitalize:'off', autocorrect:'off', autocomplete:'off' },
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        showLoaderOnConfirm: true,
        preConfirm: async (ref) => {
        ref = (ref || '').trim();
        if (ref.length !== 8) { Swal.showValidationMessage('Reference must be exactly 8 characters.'); return false; }
        try {
            await postStatus(ticketId, { status_id: 'LEAVING_PAID', reference_number: ref }); // marker; controller will handle
        } catch (xhr) {
            Swal.showValidationMessage(xhr?.responseJSON?.message || 'Could not update status.');
            return false;
        }
        return true;
        }
    });
    return isConfirmed;
    }

  async function askAdminPassword(ticketId, newStatus) {
    const target = document.getElementById('violatorModal');
    const { isConfirmed } = await Swal.fire({
        target,
        title: 'Admin Password Required',
        input: 'password',
        inputLabel: 'Enter admin password to confirm',
        inputAttributes: { autocapitalize:'off', autocomplete:'current-password', maxlength:128 },
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        showLoaderOnConfirm: true,
        preConfirm: async (pw) => {
        pw = (pw || '').trim();
        if (!pw) { Swal.showValidationMessage('Password is required.'); return false; }
        try {
            await postStatus(ticketId, { status_id: newStatus, admin_password: pw });
        } catch (xhr) {
            Swal.showValidationMessage(xhr?.responseJSON?.message || 'Password check failed.');
            return false;
        }
        return true;
        }
    });
    return isConfirmed;
    }

    /* ======== STATUS CHANGE (GUARDED, SINGLE PROMPT) ======== */
    let _changing = false;
    $(document).on('change', '#violatorModal .status-select', async function () {
    if (_changing) return;
    _changing = true;

    const c = cfg();
    const $sel      = $(this);
    const ticketId  = Number($sel.data('ticket-id'));
    const oldStatus = Number($sel.data('current-status-id')); // from data- attr
    const newStatus = Number($sel.val());

    // Always revert UI immediately; apply after success only
    const revert = () => $sel.val(oldStatus);

    try {
        let ok = false;

        // Rule 1: FROM PAID → (Pending/Unpaid/Cancelled) => Ask REFERENCE (8 chars)
        if (oldStatus === c.paidId && newStatus !== c.paidId) {
        ok = await askReferenceForLeavingPaid(ticketId);
        }
        // Rule 2: FROM PENDING → (Unpaid/Cancelled) => Ask ADMIN PASSWORD
        else if (oldStatus === c.pendingId && (newStatus === c.unpaidId || newStatus === c.cancelledId)) {
        ok = await askAdminPassword(ticketId, newStatus);
        }
        // All other transitions → straight update
        else {
        await postStatus(ticketId, { status_id: newStatus });
        ok = true;
        }

        if (!ok) { revert(); return; }

        // Success → update marker and reload the modal body content
        $sel.attr('data-current-status-id', newStatus);

        Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Status updated', timer:1200, showConfirmButton:false });

        if (window.currentDetailUrl) {
        $('#violatorModalBody').html('<div class="py-5 text-center"><div class="spinner-border" role="status"></div></div>');
        $.get(window.currentDetailUrl, html => $('#violatorModalBody').html(html));
        }
    } catch (err) {
        revert();
        Swal.fire('Error', String(err?.responseJSON?.message || err?.message || err || 'Update failed'), 'error');
    } finally {
        _changing = false;
    }
    });


})(jQuery);
