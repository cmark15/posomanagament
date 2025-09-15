// update-modal.js

/**
 * Delegate handling of the Edit modal to the document,
 * so it works even after AJAX content swaps.
 */

// Populate the modal inputs any time it is shown
// public/js/update-modal.js
document.addEventListener('show.bs.modal', event => {
  // Only handle the edit modal
  if (event.target.id !== 'editModal') return;

  const btn  = event.relatedTarget;                   // the clicked Edit button
  const form = document.getElementById('editEnforcerForm');
  if (!btn || !form) return;                          // guard

  // 1) Set the form action URL
  const id = btn.getAttribute('data-id');
  form.action = `/enforcer/${id}`;

  // 2) Field mapping: data-attr → form field name
  const fields = {
    badge:     'badge_num',
    fname:     'fname',
    mname:     'mname',
    lname:     'lname',
    phone:     'phone',
  };

  // 3) Populate each input (if it exists)
  Object.entries(fields).forEach(([attrKey, fieldName]) => {
    // Grab the raw data-xxx value (e.g. data-badge, data-fname, etc.)
    const val = btn.getAttribute(`data-${attrKey}`) || '';

    // Try an ID matching edit_FIELDNAME (e.g. edit_badge_num)
    let input = form.querySelector(`#edit_${fieldName}`);

    // Fallback to name selector if no ID
    if (!input) {
      input = form.querySelector(`[name="${fieldName}"]`);
    }

    // If found, set its value
    if (input) {
      input.value = val;
    }
  });
  window.generatePassword = function(inputId) {
    const prefix = 'posoenforcer_';
    const rnd    = Math.floor(100 + Math.random() * 900);
    const el     = document.getElementById(inputId);
    if (el) el.value = `${prefix}${rnd}`;
};
});

//Generate Password

// Intercept submission of the Edit form anywhere in the document
document.body.addEventListener('submit', async e => {
    const form = e.target;
    if (form.id !== 'editEnforcerForm') return;
    e.preventDefault();
    const modalEl = document.getElementById('editModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    try {
      const resp = await fetch(form.action, {
        method: 'POST',
        headers: { 
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: new FormData(form)
      });
  
      const json = await resp.json();
  
      // Success toast
      if(json.raw_password){
          await Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'New Enforcer Password',
          timer: 8000,
          showConfirmButton: false
        });
      }
      else{  
          Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Updated Successfully',
          showConfirmButton: false,
          timer: 1500
        });
      }
  
  
    } catch (errors) {
      if (typeof errors === 'object') {
        // Validation errors: display under inputs
        Object.entries(errors).forEach(([field, msgs]) => {
          const input = form.querySelector(`[name="${field}"]`);
          if (!input) return;
          input.classList.add('is-invalid');
          let fb = input.nextElementSibling;
          if (!fb || !fb.classList.contains('invalid-feedback')) {
            fb = document.createElement('div');
            fb.classList.add('invalid-feedback');
            input.after(fb);
          }
          fb.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
        });
      } else {
        Swal.fire({ icon: 'error', title: 'Update failed', text: errors.message || errors });
      }
    }
    // — HIDE + CLEANUP —
  modalInstance.hide();
  modalEl.addEventListener('hidden.bs.modal', () => {
    // 1) Dispose Bootstrap’s JS instance
    modalInstance.dispose();

    // 2) Remove any leftover backdrops
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

    // 3) Restore <body> scrollability
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    /// 4) Reload the updated table—or the page if your loader fn isn’t there
    if (typeof loadContent === 'function') {
      loadContent(window.location.pathname);
    } else {
      // fallback: full reload ensures nothing freezes
      window.location.reload();
    }
    }, { once: true });
  });
  