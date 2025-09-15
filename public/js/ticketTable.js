/* ticketTable.js — SweetAlert-only status changes, AJAX-safe, no Bootstrap modals */
(function ($) {
  // Avoid double-binding when page is swapped via AJAX
  if (window.__ticketTableBound) return;
  window.__ticketTableBound = true;

  /* ---------------- config + helpers ---------------- */
  function getRoot() { return document.getElementById('ticketContainer'); }

  function cfg() {
    const root = getRoot();
    return {
      paidStatusId: Number(root?.dataset.paidStatusId || window.PAID_STATUS_ID || 0),
      statusUpdateUrl: root?.dataset.statusUpdateUrl || window.STATUS_UPDATE_URL || '/ticket',
      ticketPartialUrl: root?.dataset.ticketPartialUrl || window.ticketPartialUrl || null
    };
  }


  /* ---------------- helpers ---------------- */
  const csrfToken = () => $('meta[name="csrf-token"]').attr('content') || '';
  const setLoading = (on) => {
    const ov = document.getElementById('ticketLoading');
    if (ov) ov.style.display = on ? 'flex' : 'none';
  };
  const getSort = () => ($('#ticket-sort').val() || 'date_desc');

  function loadTable(opts, push = true) {
    const C = cfg();
    if (!C.ticketPartialUrl) return;
    setLoading(true);
    $.get(C.ticketPartialUrl + '?' + $.param(opts))
      .done(html => {
        $('#ticket-table').html(html);
        if (push) history.pushState(null, '', '/ticket?' + $.param(opts));
      })
      .always(() => setLoading(false));
  }
  // Post status as a Promise (for Swal preConfirm)
  function postStatusPromise(ticketId, data) {
    const C = cfg();
    return new Promise((resolve, reject) => {
      $.ajax({
        url: `${C.statusUpdateUrl}/${ticketId}/status`,
        method: 'POST',
        data: Object.assign({ _token: csrfToken() }, data),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .done(resolve)
      .fail(xhr => reject(xhr?.responseJSON?.message || 'Could not update status.'));
    });
  }

  function toastOk(msg) {
    if (!window.Swal) return;
    Swal.fire({ toast:true, position:'top-end', icon:'success', title: msg || 'Updated', timer:1500, showConfirmButton:false });
  }

  // Maintain previous state so we can revert on cancel/error
  let _lastSelect = null;
  let _lastValue  = null;

  function revertSelect() {
    if (_lastSelect && _lastValue != null) _lastSelect.val(_lastValue);
    _lastSelect = null; _lastValue = null;
  }

  // Ask reference via SweetAlert
  async function promptReference(ticketId) {
    if (window.Swal) {
      const { isConfirmed, value } = await Swal.fire({
        title: 'Enter Reference Number',
        input: 'text',
        inputLabel: 'Reference # (required for Paid)',
        inputPlaceholder: 'e.g., OR-123456',
        inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
        showCancelButton: true,
        confirmButtonText: 'Submit',
        showLoaderOnConfirm: true,
        preConfirm: async (refNo) => {
          refNo = (refNo || '').trim();
          if (!refNo) {
            Swal.showValidationMessage('Reference number is required.');
            return false;
          }
          try {
            await postStatusPromise(ticketId, { status_id: cfg().paidStatusId, reference_number: refNo });
          } catch (errMsg) {
            Swal.showValidationMessage(errMsg);
            return false;
          }
          return true;
        },
        allowOutsideClick: () => !Swal.isLoading()
      });
      return isConfirmed;
    } else {
      // Fallback if SweetAlert is missing
      const ref = (prompt('Enter Reference Number:') || '').trim();
      if (!ref) return false;
      await postStatusPromise(ticketId, { status_id: window.PAID_STATUS_ID, reference_number: ref });
      return true;
    }
  }

  // Ask admin password via SweetAlert
  async function promptPassword(ticketId, newStatus) {
    if (window.Swal) {
      const { isConfirmed, value } = await Swal.fire({
        title: 'Admin Password Required',
        input: 'password',
        inputLabel: 'Enter password to confirm',
        inputPlaceholder: 'Password',
        inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        showLoaderOnConfirm: true,
        preConfirm: async (pw) => {
          pw = (pw || '').trim();
          if (!pw) {
            Swal.showValidationMessage('Password is required.');
            return false;
          }
          try {
            await postStatusPromise(ticketId, { status_id: newStatus, admin_password: pw });
          } catch (errMsg) {
            Swal.showValidationMessage(errMsg);
            return false;
          }
          return true;
        },
        allowOutsideClick: () => !Swal.isLoading()
      });
      return isConfirmed;
    } else {
      // Fallback if SweetAlert is missing
      const pw = (prompt('Enter admin password:') || '').trim();
      if (!pw) return false;
      await postStatusPromise(ticketId, { status_id: newStatus, admin_password: pw });
      return true;
    }
  }

  /* ---------------- sort & pagination ---------------- */
  $(document).on('change', '#ticket-sort', function () {
    loadTable({ sort_option: $(this).val(), page: 1 });
  });

  $(document).on('click', '#ticket-table .pagination a', function (e) {
    e.preventDefault();
    const page = new URL(this.href).searchParams.get('page') || 1;
    loadTable({ sort_option: getSort(), page });
  });

  // Initial table load when landing (and for AJAX nav)
  function initialLoadIfNeeded() {
    if (!document.getElementById('ticket-table')) return;
    const hasTable = !!document.querySelector('#ticket-table table');
    if (!hasTable) {
      const params = new URLSearchParams(location.search);
      loadTable({
        sort_option: params.get('sort_option') || 'date_desc',
        page:        params.get('page')        || 1
      }, /*push=*/false);
      $('#ticket-sort').val(params.get('sort_option') || 'date_desc');
      history.replaceState(null, '', location.pathname + location.search);
    }
  }
  $(initialLoadIfNeeded);
  document.addEventListener('page:loaded', initialLoadIfNeeded);

  /* ---------------- status change (SweetAlert) ---------------- */
  $(document).on('change', '.status-select', async function () {
    const $sel      = $(this);
    const ticketId  = +$sel.data('ticket-id');
    const oldStatus = +$sel.data('current-status-id');
    const newStatus = +$sel.val();
    const paidId    = Number(cfg().paidStatusId);

    _lastSelect = $sel;
    _lastValue  = oldStatus;

    if (oldStatus === newStatus) return;

    try {
      let ok = false;
      if (newStatus === paidId) {
        // To PAID: Ask reference number only
        ok = await promptReference(ticketId);
      } else {
        // Other change: Ask admin password only
        ok = await promptPassword(ticketId, newStatus);
      }

      if (!ok) {
        // canceled or failed validation
        revertSelect();
        return;
      }

      toastOk('Status updated');
      // reload table keeping current page/sort
      const page = new URLSearchParams(location.search).get('page') || 1;
      loadTable({ sort_option: getSort(), page }, /*push=*/false);
      _lastSelect = null; _lastValue = null;

    } catch (err) {
      if (window.Swal) Swal.fire('Error', String(err), 'error');
      revertSelect();
    }
  });
})(jQuery);
