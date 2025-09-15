
;(function () {
  // Helper: fetch HTML then replace only the impounded table wrapper
  console.log('[impounded] js loaded');
  async function loadImpoundedPage(url) {
    const wrap = document.getElementById('impoundedTableWrap');
    if (!wrap) return;
    wrap.classList.add('is-loading');

    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const html = await res.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const fresh = doc.getElementById('impoundedTableWrap');
    if (fresh) {
      wrap.innerHTML = fresh.innerHTML;
    }
    wrap.classList.remove('is-loading');
  }

  // Helper: build a released table row (safer than clone)
  function buildReleasedRow({ ticketNo, refNo, releasedAt, violator, vehicle, plate, location }) {
    return `
      <tr>
        <td class="text-center fw-semibold">${ticketNo}</td>
        <td class="text-center"><span class="badge text-bg-success">${refNo}</span></td>
        <td class="text-center">${releasedAt}</td>
        <td>${violator}</td>
        <td><span class="badge text-bg-secondary">${vehicle}</span></td>
        <td class="text-center"><span class="badge text-bg-dark">${plate}</span></td>
        <td class="text-truncate" style="max-width:240px;" title="${location}">${location}</td>
      </tr>
    `.trim();
  }

  // CSRF + URLs
  function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }
  function getResolveUrl() {
    const el = document.getElementById('impound-page');
    return el?.dataset.resolveUrl || '/impounded/resolve';
  }
  function getIndexUrl() {
    const el = document.getElementById('impound-page');
    return el?.dataset.indexUrl || location.pathname;
  }

  // Delegated click for pagination inside the impounded card only
  document.addEventListener('click', (e) => {
    const a = e.target.closest('#impoundedTableWrap .pagination a');
    if (!a) return;
    // keep the whole page, just refresh the table block:
    e.preventDefault();
    loadImpoundedPage(a.href);
  });

  // Delegated click: Resolve button → SweetAlert prompt
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-resolve');
    if (!btn) return;

    const tr = btn.closest('tr');
    if (!tr) return;

    const ticketId = tr.dataset.ticketId;
    const ticketNo = tr.dataset.ticketNumber;
    const violator = tr.dataset.violator;
    const vehicle  = tr.dataset.vehicle;
    const plate    = tr.dataset.plate;
    const location = tr.dataset.location;

    const { value: refNo } = await Swal.fire({
      title: 'Enter Reference Number',
      html: `
        <div class="text-start">
          <div class="small text-muted mb-2">
            Ticket <strong>#${ticketNo}</strong> • ${violator}
          </div>
          <input id="swal-ref" class="swal2-input" placeholder="8 digits" maxlength="8" inputmode="numeric" />
          <div class="form-text">Required: exactly 8 digits.</div>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Confirm',
      preConfirm: () => {
        const v = (document.getElementById('swal-ref')?.value || '').trim();
        if (!/^\d{8}$/.test(v)) {
          Swal.showValidationMessage('Reference number must be exactly 8 digits.');
          return false;
        }
        return v;
      }
    });

    if (!refNo) return; // cancelled

    // POST to resolve
    try {
      const res = await fetch(getResolveUrl(), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': getCsrf(),
          'Accept': 'application/json'
        },
        body: new URLSearchParams({
          ticket_id: ticketId,
          reference_number: refNo
        })
      });

      const body = await res.json();

      if (res.ok && body.status === 'success') {
        // Remove from impounded table
        tr.remove();

        // Add to released table (prepend)
        const releasedTbody = document.querySelector('#releasedTable tbody');
        if (releasedTbody) {
          const now = new Date().toLocaleString();
          const rowHtml = buildReleasedRow({
            ticketNo,
            refNo,
            releasedAt: now,
            violator,
            vehicle,
            plate,
            location
          });
            const emptyRow = document.querySelector('#releasedTable .released-empty');
            if (emptyRow) emptyRow.remove();
            // now insert the real row
            releasedTbody.insertAdjacentHTML('afterbegin', buildReleasedRow({
              ticketNo, refNo, releasedAt: now, violator, vehicle, plate, location
            }));

          releasedTbody.insertAdjacentHTML('afterbegin', rowHtml);
        }

        Swal.fire({
          icon: 'success',
          title: 'Vehicle Released',
          text: body.message,
          timer: 1600,
          showConfirmButton: false
        });

        // Optional: reload impounded pagination counts to keep footer correct
        // (only if current page may be empty)
        if (!document.querySelector('#impoundedTable tbody tr')) {
          loadImpoundedPage(getIndexUrl());
        }
      } else {
        throw new Error(body.message || 'Something went wrong.');
      }
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: err.message || 'Request failed.'
      });
    }
  });

})();
