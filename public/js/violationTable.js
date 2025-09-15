// public/js/violationTable.js — SPA filters/pagination + SweetAlert edit + admin-password archive + Add Violation
(function ($) {
  if (window.__violationBound) return;
  window.__violationBound = true;

  // ---------- helpers ----------
  function absUrl(u){
    try { return new URL(u, location.origin).href; }
    catch { return u; }
  }
  // escape helper (replaces lodash _.escape)
  function esc(s){
    return String(s ?? '').replace(/[&<>"']/g, m => (
      {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]
    ));
  }
  // SPA navigate using your global a[data-ajax] handler
  function navigateAjax(url){
    const a = document.createElement('a');
    a.href = url;
    a.setAttribute('data-ajax','');
    document.body.appendChild(a);
    a.click();
    a.remove();
  }
  // Increment codes like V001 -> V002 (keeps alpha prefix + zero padding)
  function incCode(code){
    const m = String(code).trim().match(/^([A-Za-z]*)(\d+)$/);
    if (!m) return code;
    const prefix = m[1], num = m[2];
    const next = String(parseInt(num, 10) + 1).padStart(num.length, '0');
    return prefix + next;
  }

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  function $wrap(){ return $('#violationContainer'); }

  function root() { return document.getElementById('violations-page'); }
  function cfg() {
    const r = root();
    return { partialUrl: r?.dataset.partialUrl || window.violationPartialUrl || '/violation/partial' };
  }
  function setLoading(on){
    const ov = document.getElementById('vioLoading');
    if (ov) ov.style.display = on ? 'flex' : 'none';
  }

  // ---- URL helpers
  function getParams(){
    const p = new URLSearchParams(location.search);
    return {
      category: p.get('category') || $('#category_filter').val() || 'all',
      search:   p.get('search')   || $('#search_input').val()   || '',
      page:     p.get('page')     || '1'
    };
  }
  function pushUrl(category, search, page){
    const p = new URLSearchParams();
    if (category && category !== 'all') p.set('category', category);
    if (search) p.set('search', search);
    if (page && page !== '1') p.set('page', page);
    history.pushState(null, '', `${location.pathname}?${p}`);
  }

  // ---- rendering
 function inject(html, pushTo){
  const doc = new DOMParser().parseFromString(html, 'text/html');
  const inner = doc.querySelector('#table-container')?.innerHTML || html;

  const $w = $wrap();
  if ($w.length) {
    $w.html(inner);
  } else {
    // fallback: update current table container in place
    const tc = document.querySelector('#table-container');
    if (tc) tc.innerHTML = inner;
  }

  if (pushTo) pushUrl(pushTo.category, pushTo.search, pushTo.page);
}

  function loadPage(page='1', push=true){
    const C = cfg();
    const category = $('#category_filter').val() || 'all';
    const search   = ($('#search_input').val() || '').trim();

    setLoading(true);
    $.ajax({
      url: C.partialUrl,
      data: { category, search, page },
      headers: { 'X-Requested-With':'XMLHttpRequest' }
    })
    .done(html => inject(html, push ? {category, search, page} : null))
    .fail(err => console.error('AJAX error:', err))
    .always(()=> setLoading(false));
  }

  // ---- init
  function initPage(){
    if (!root()) return;
    const {category, search, page} = getParams();
    $('#category_filter').val(category);
    $('#search_input').val(search);
    const hasTable = !!document.querySelector('#violationContainer table');
    if (!hasTable) loadPage(page, /*push=*/false);
  }
  $(initPage);
  document.addEventListener('DOMContentLoaded', initPage);
  document.addEventListener('page:loaded', initPage);

  // ---- filters
  $(document).on('change', '#category_filter', () => loadPage('1'));
  $(document).on('click',  '#search_btn',      () => loadPage('1'));
  $(document).on('keypress','#search_input', (e) => { if (e.which === 13){ e.preventDefault(); loadPage('1'); }});

  // ---- pagination
  $(document).on('click', '#violationContainer .pagination a, #table-container .pagination a', function(e){
    e.preventDefault();
    e.stopImmediatePropagation(); // win against other delegates
    const href = this.getAttribute('href') || '';
    const newPage = new URL(href, location.origin).searchParams.get('page') || '1';
    loadPage(newPage);
  });

  // =========================
  // SweetAlert: EDIT (no modal)
  // =========================
  $(document).on('click', '.edit-btn', async function(){
    const $btn = $(this);
    const url  = absUrl($btn.data('url'));

    const init = {
      code: $btn.data('code') || '',
      name: $btn.data('name') || '',
      fine: $btn.data('fine') || '',
      category: $btn.data('category') || '',
      desc: $btn.data('desc') || '',
    };

    const html = `
      <div class="text-start">
        <div class="mb-2">
          <label class="form-label">Violation Code</label>
          <input id="swal-code" class="form-control" value="${esc(init.code)}" disabled>
        </div>
        <div class="mb-2">
          <label class="form-label">Violation Name</label>
          <input id="swal-name" class="form-control" value="${esc(init.name)}">
        </div>
        <div class="mb-2">
          <label class="form-label">Fine Amount</label>
          <input id="swal-fine" type="number" min="0" step="0.01" class="form-control" value="${esc(String(init.fine))}">
        </div>
        <div class="mb-2">
          <label class="form-label">Category</label>
          <input id="swal-cat" class="form-control" value="${esc(init.category)}">
        </div>
      </div>
    `;

    const { value, isConfirmed } = await Swal.fire({
      title: 'Edit Violation',
      html,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Save',
      preConfirm: () => {
        const code = document.getElementById('swal-code').value.trim();
        const name = document.getElementById('swal-name').value.trim();
        const fine = document.getElementById('swal-fine').value.trim();
        const cat  = document.getElementById('swal-cat').value.trim();

        if (!code || !name) {
          Swal.showValidationMessage('Code and Name are required.');
          return false;
        }
        const payload = {};
        if (code !== init.code) payload.violation_code = code;
        if (name !== init.name) payload.violation_name = name;
        if (fine !== '' && String(fine) !== String(init.fine)) payload.fine_amount = fine;
        if (cat !== init.category) payload.category = cat;
        if (Object.keys(payload).length === 0) {
          Swal.showValidationMessage('No changes detected.');
          return false;
        }
        return payload;
      }
    });

    if (!isConfirmed) return;

    setLoading(true);
    $.ajax({
      url: url,
      method: 'POST',
      data: { ...value, _method:'PUT' },
      headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
    })
    .done(() => {
      Swal.fire({ icon:'success', title:'Updated', timer:1200, showConfirmButton:false });
      const {page} = getParams(); loadPage(page);
    })
    .fail(xhr => {
      let msg = 'Update failed';
      if (xhr?.responseJSON?.message) msg = xhr.responseJSON.message;
      if (xhr?.responseJSON?.errors) {
        const flat = Object.values(xhr.responseJSON.errors).flat().join('\n');
        msg = flat || msg;
      }
      Swal.fire({ icon:'error', title:'Error', text: msg });
    })
    .always(()=> setLoading(false));
  });

  // ====================================
  // Archive with admin password
  // ====================================
  $(document).on('click', '.archive-btn', async function(e){
    e.preventDefault();
    const name   = $(this).data('name');
    const action = absUrl($(this).data('action'));

    const { isConfirmed, value: adminPwd } = await Swal.fire({
      title: `Archive “${name}”?`,
      text: 'Enter admin password to proceed.',
      input: 'password',
      inputAttributes: { autocapitalize:'off', autocomplete:'current-password', maxlength:128 },
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Archive',
      cancelButtonText: 'Cancel',
      preConfirm: (val) => {
        if (!val){ Swal.showValidationMessage('Admin password is required'); return false; }
        return val;
      }
    });
    if (!isConfirmed) return;

    const fd = new FormData();
    fd.append('_method', 'DELETE');
    fd.append('admin_password', adminPwd);

    setLoading(true);
    $.ajax({
      url: action,
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
    })
    .done(() => {
      Swal.fire({ icon:'success', title:'Archived', timer:1200, showConfirmButton:false });
      const {page} = getParams(); loadPage(page);
    })
    .fail((xhr)=>{
      let msg = 'Could not archive.';
      try{ msg = xhr.responseJSON?.message || msg; }catch(_){}
      Swal.fire({ icon:'error', title:'Error', text: msg });
    })
    .always(()=> setLoading(false));
  });

  // =========================
  // ADD VIOLATION (AJAX)
  // =========================
  $(document).on('submit', '#violationForm', function(e){
    e.preventDefault();
    const form = this;
    const action = absUrl(form.getAttribute('action') || '/violation');
    const fd = new FormData(form);

    setLoading(true);
    $.ajax({
      url: action,
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
    })
    .done(async (data) => {
      // Ask user what to do next
      const res = await Swal.fire({
        icon: 'success',
        title: 'Violation Added',
        showCancelButton: true,
        confirmButtonText: 'Add another',
        cancelButtonText: 'Go to list'
      });

      if (res.isConfirmed) {
        // Reset form and advance code like V001 -> V002 (if matches the pattern)
        form.reset();
        const codeEl = document.getElementById('violation_code');
        if (codeEl && codeEl.defaultValue) {
          // If the defaultValue came from server, prefer incrementing current value
          codeEl.value = incCode(codeEl.value || codeEl.defaultValue);
        } else if (codeEl && codeEl.value) {
          codeEl.value = incCode(codeEl.value);
        }
        document.getElementById('violation_name')?.focus();
      } else {
        // Go back to list via SPA
        navigateAjax('/violation');
      }
    })
    .fail((xhr) => {
      let msg = 'Failed to add violation.';
      try {
        const r = xhr.responseJSON;
        if (r?.message) msg = r.message;
        if (r?.errors) msg = Object.values(r.errors).flat().join('\n') || msg;
      } catch {}
      Swal.fire({ icon:'error', title:'Error', text: msg });
    })
    .always(() => setLoading(false));
  });

})(jQuery);
