// ensure CSRF for jQuery
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});

let detailUrl;

// 1) Load Violator Details into modal
$(document).on('click', '.view-tickets-btn', function(e) {
  e.preventDefault();
  detailUrl = $(this).data('url');
  $('#violatorModalBody').html('Loading…');
  new bootstrap.Modal(document.getElementById('violatorModal')).show();
  $.get(detailUrl, html => {
    $('#violatorModalBody').html(html);
  });
});

// 2) Prepare the three modals
const violatorModalEl = document.getElementById('violatorModal');
const refModalEl      = document.getElementById('refModal');
const pwdModalEl      = document.getElementById('pwdModal');

if (!violatorModalEl) console.error('#violatorModal not found');
if (!refModalEl)      console.error('#refModal not found');
if (!pwdModalEl)      console.error('#pwdModal not found');

const refModal = new bootstrap.Modal(refModalEl);
const pwdModal = new bootstrap.Modal(pwdModalEl);

let _lastSelect, _lastValue, _paidConfirmed;

// 3) Intercept status changes
$(document).on('change', '.status-select', function() {
  const $sel      = $(this);
  const ticketId  = Number($sel.data('ticket-id'));
  const oldStatus = Number($sel.data('current-status-id'));
  const newStatus = Number($sel.val());
  const paidId    = window.PAID_STATUS_ID;

  // stash for potential revert
  _lastSelect    = $sel;
  _lastValue     = oldStatus;
  _paidConfirmed = false;

  // a) marking Paid → hide details, show ref-modal
  if (newStatus === paidId) {
    bootstrap.Modal.getInstance(violatorModalEl).hide();
    $('#ref_ticket_id').val(ticketId);
    refModal.show();
  }
  // b) un-marking Paid → hide details, show pwd-modal
  else if (oldStatus === paidId && newStatus !== paidId) {
    bootstrap.Modal.getInstance(violatorModalEl).hide();
    $('#pwd_ticket_id').val(ticketId);
    $('#pwd_new_status').val(newStatus);
    pwdModal.show();
  }
  // c) all else → direct update
  else {
    updateStatus(ticketId, { status_id: newStatus });
  }
});

// 4) If ref-modal closes without submitting, revert the dropdown & clear input
refModalEl.addEventListener('hidden.bs.modal', () => {
  $('#reference_number').val('');
  if (!_paidConfirmed && _lastSelect) {
    // revert the dropdown
    _lastSelect.val(_lastValue);
    // re-open Violator modal because they CANCELLED
    new bootstrap.Modal(violatorModalEl).show();
  }
});

// 5) Reference-number form submit
$('#refForm').on('submit', function(e) {
  e.preventDefault();
  _paidConfirmed = true; // they’ve confirmed

  const ticketId = $('#ref_ticket_id').val();
  const refNo    = $('#reference_number').val().trim();

  updateStatus(
    ticketId,
    {
      status_id: window.PAID_STATUS_ID,
      reference_number: refNo
    },
    () => {
      refModal.hide();
      // now that it succeeded, re-open details
      new bootstrap.Modal(violatorModalEl).show();
    }
  );
});

// 6) If pwd-modal closes without submitting, revert the dropdown & clear input
pwdModalEl.addEventListener('hidden.bs.modal', () => {
  $('#admin_password').val('');
  if (!_paidConfirmed && _lastSelect) {
    // revert the dropdown
    _lastSelect.val(_lastValue);
    // re-open Violator modal because they CANCELLED
    new bootstrap.Modal(violatorModalEl).show();
  }
});
// 7) Password form submit
$('#pwdForm').on('submit', function(e) {
  e.preventDefault();
  _paidConfirmed = true; // they’ve confirmed

  const ticketId = $('#pwd_ticket_id').val();
  const statusId = $('#pwd_new_status').val();
  const pw       = $('#admin_password').val();

  updateStatus(
    ticketId,
    {
      status_id:      statusId,
      admin_password: pw
    },
    () => {
      pwdModal.hide();
      // re-open details on success
      new bootstrap.Modal(violatorModalEl).show();
    }
  );
});

// 8) Common AJAX + reload helper
function updateStatus(ticketId, data, onSuccess) {
  data._token = $('meta[name="csrf-token"]').attr('content');

  $.post(
    `${window.STATUS_UPDATE_URL}/${ticketId}/status`,
    data
  )
    .done(json => {
      _paidConfirmed = true; // ensure flag for safety
      onSuccess?.();

      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: json.message,
        timer: 1500,
        showConfirmButton: false
      });

      // reload the details partial
      $.get(detailUrl, html => {
        $('#violatorModalBody').html(html);
      });
    })
    .fail(xhr => {
      console.error('🚨 updateStatus error:', xhr.responseJSON);
      Swal.fire(
        'Error',
        xhr.responseJSON?.message || 'Update failed',
        'error'
      );
    });
}
