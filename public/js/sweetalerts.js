document.body.addEventListener('click', e => {
  const btn = e.target.closest('.status-btn');
  if (!btn) return;
  e.preventDefault();

  // stash the URL + method on the modal form
  const action = btn.dataset.action;
  const method = btn.dataset.method;

  const form = document.getElementById('confirmPasswordForm');
  form.action = action;
  document.getElementById('confirmMethod').value = method;

  // clear any previous error
  document.getElementById('confirmError').style.display = 'none';
  form.reset();

  // show the modal
  new bootstrap.Modal(document.getElementById('confirmPasswordModal')).show();
});
document
  .getElementById('confirmPasswordForm')
  .addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const data = new FormData(form);

    // send via fetch so we can catch a 422 if password fails
    const resp = await fetch(form.action, {
      method: data.get('_method') === 'DELETE' ? 'DELETE' : 'POST',
      headers: {
        'X-CSRF-TOKEN': data.get('_token'),
        'Accept': 'application/json'
      },
      body: data
    });

    if (resp.status === 422) {
      // show the error div
      document.getElementById('confirmError').style.display = '';
      return;
    }

    // on success, reload the table via your existing AJAX
    // (or simply reload the page)
    location.reload();
  });
    // Back-button confirmation
    document.body.addEventListener('click', e => {
      const btn = e.target.closest('#previousBtn');
      if (!btn) return;
      e.preventDefault();
  
      const inputs = document.querySelectorAll('#enforcerForm input');
      const empty  = Array.from(inputs).filter(i => !i.value.trim());
      empty.forEach(i=> i.classList.add('border','border-danger'));
  
      const swalOpts = empty.length === inputs.length
        ? {
            title: 'All fields are empty!',
            text: 'Please fill out the form or click Leave.',
          }
        : {
            title: 'Form is not complete!',
            text: 'Some fields are still empty. Leave anyway?',
          };
  
      Swal.fire(Object.assign({
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, leave',
        cancelButtonText: 'Stay'
      }, swalOpts)).then(res => {
        if (res.isConfirmed) {
          // go back via AJAX
          loadContent(btn.dataset.back);
        }
      });
    });
  