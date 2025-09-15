// update-modal.js

/**
 * Delegate handling of the Edit modal to the document,
 * so it works even after AJAX content swaps.
 */

// Populate the modal inputs any time it is shown
document.addEventListener('show.bs.modal', event => {
    // Only care about our Edit modal
    if (event.target.id !== 'editModal') return;
  
    const btn  = event.relatedTarget;
    const form = document.getElementById('editViolationForm');
    if (!btn || !form) return;
  
    const id = btn.getAttribute('data-id');
    form.action = `/violation/${id}`; // ensure your route matches
  
    form.querySelector('#edit_violation_code').value = btn.getAttribute('data-violation_code') || '';
    form.querySelector('#edit_violation_name').value     = btn.getAttribute('data-violation_name') || '';
    form.querySelector('#edit_fine_amount').value     = btn.getAttribute('data-fine_amount') || '';
    form.querySelector('#edit_category').value     = btn.getAttribute('data-category') || '';
  });
  
  // Intercept submission of the Edit form anywhere in the document
  document.body.addEventListener('submit', async e => {
    const form = e.target;
    if (form.id !== 'editViolationForm') return;
    e.preventDefault();
  
    const modalEl = document.getElementById('editModal');
  
    try {
      const resp = await fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin', body: new FormData(form)
      });
  
      if (resp.status === 422) {
        const json = await resp.json();
        throw json.errors;
      }
      if (!resp.ok) throw new Error(resp.statusText);
      await resp.json();
  
      // Success toast
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Violation Updated',
        showConfirmButton: false,
        timer: 1500
      });
  
      // Hide the modal
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      modalInstance.hide();
  
      // When fully hidden, clean up and reload the table
      modalEl.addEventListener('hidden.bs.modal', () => {
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(el=>el.remove());
        loadContent(window.location.pathname);
      }, { once: true });
  
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
  });
  