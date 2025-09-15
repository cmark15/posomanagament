// resources/js/analytics.js

window.pie = window.pie || null;
window.bar = window.bar || null;
window.map = window.map || null;
window.heat = window.heat || null;
window.hotspotMarkers = window.hotspotMarkers || null;
window._analyticsModal = window._analyticsModal || null;

function initAnalytics() {
  const pieEl = document.getElementById('pieChart');
  const barEl = document.getElementById('barChart');
  const mapEl = document.getElementById('map');
  if (!pieEl || !barEl || !mapEl) return; // not on analytics page

  // render violation filter list
  renderViolationFilterList();

  // destroy old instances if any (due to AJAX swap)
  try { if (window.pie) { window.pie.destroy(); window.pie = null; } } catch(_) {}
  try { if (window.bar) { window.bar.destroy(); window.bar = null; } } catch(_) {}
  try { if (window.map) { window.map.remove(); window.map = null; window.heat = null; } } catch(_) {}

  // Charts
  window.pie = new Chart(pieEl, {
    type: 'pie',
    data: { labels: ['Paid', 'Unpaid'], datasets: [{ data: [0, 0] }] },
    options: { plugins: { legend: { position: 'bottom' } } }
  });

  window.bar = new Chart(barEl, {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Violations', data: [] }] },
    options: {
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      plugins: { legend: { display: false } }
    }
  });

  // Map
  const lat = Number(window.DEFAULT_LAT ?? 15.9285);
  const lng = Number(window.DEFAULT_LNG ?? 120.3487);

  window.map = L.map('map', { zoomControl: true }).setView([lat, lng], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(window.map);

  window.heat = L.heatLayer([], { radius: 25, blur: 15, maxZoom: 17 }).addTo(window.map);
  window.hotspotMarkers = L.layerGroup().addTo(window.map);

  setTimeout(() => window.map.invalidateSize(), 250);

  // Modal
  const modalEl = document.getElementById('hotspotModal');
  window._analyticsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

  // Bind buttons
  document.getElementById('applyFilters')?.addEventListener('click', fetchStats);
  document.getElementById('resetFilters')?.addEventListener('click', resetFilters);

  // init default filters (last 3 months)
  initDefaultMonths();
  fetchStats();
}

function initDefaultMonths() {
  const to = new Date();
  const from = new Date(to.getFullYear(), to.getMonth() - 2, 1);
  document.getElementById('fromMonth').value = `${from.getFullYear()}-${String(from.getMonth()+1).padStart(2,'0')}`;
  document.getElementById('toMonth').value   = `${to.getFullYear()}-${String(to.getMonth()+1).padStart(2,'0')}`;
}

function resetFilters() {
  initDefaultMonths();
  document.querySelectorAll('#violationFilterList input[type="checkbox"]').forEach(cb => cb.checked = false);
  document.querySelector('input[name="statusFilter"][value=""]')?.click();
  fetchStats();
}

function getFilters() {
  const fromMonth = document.getElementById('fromMonth').value; // YYYY-MM
  const toMonth   = document.getElementById('toMonth').value;   // YYYY-MM
  const status    = document.querySelector('input[name="statusFilter"]:checked')?.value || '';

  const violations = Array.from(
    document.querySelectorAll('#violationFilterList input[type="checkbox"]:checked')
  ).map(cb => cb.value);

  // convert month to date bounds on server; we just pass YYYY-MM
  return { from: fromMonth, to: toMonth, status, violations };
}

function buildQuery(params) {
  const usp = new URLSearchParams();
  if (params.from) usp.set('from', params.from);
  if (params.to) usp.set('to', params.to);
  if (params.status) usp.set('status', params.status);
  (params.violations || []).forEach(v => usp.append('violations[]', v));
  return usp.toString();
}

async function fetchStats() {
  const loadingEl = document.getElementById('loadingIndicator');
  if (loadingEl) loadingEl.style.display = 'block';

  try {
    const q = buildQuery(getFilters());
    const res = await fetch(`/dataAnalytics/latest?${q}`, { headers: { 'Accept': 'application/json' }});
    const e = await res.json();

    // Charts
    window.pie.data.datasets[0].data = [e.paid || 0, e.unpaid || 0];
    window.pie.update();

    const months = Object.keys(e.monthlyCounts || {});
    const values = Object.values(e.monthlyCounts || {});
    window.bar.data.labels = months;
    window.bar.data.datasets[0].data = values;
    window.bar.update();

    // Heat + Markers
    const pts = (e.hotspots || []).map(h => [Number(h.latitude), Number(h.longitude), Number(h.c)]);
    window.heat.setLatLngs(pts);

    window.hotspotMarkers.clearLayers();
    const top = [...(e.hotspots || [])].sort((a,b)=>b.c-a.c).slice(0,10);

    top.forEach(h => {
      const radius = Math.min(60, 18 + Number(h.c) * 1.6);
      const marker = L.circleMarker([h.latitude, h.longitude], {
        radius, weight: 1, opacity: 0.9, fillOpacity: 0.35
      }).bindTooltip(`${h.area ?? 'Hotspot'}: ${h.c} tickets`, { direction: 'top' });

      marker.on('click', () => openHotspotModal(h.latitude, h.longitude, h.area));
      window.hotspotMarkers.addLayer(marker);
    });

  } catch (err) {
    console.error('Fetch failed:', err);
  } finally {
    if (loadingEl) loadingEl.style.display = 'none';
    setTimeout(()=> window.map && window.map.invalidateSize(), 200);
  }
}

function renderViolationFilterList() {
  const box = document.getElementById('violationFilterList');
  if (!box) return;
  const opts = Array.isArray(window.VIOLATION_OPTIONS) ? window.VIOLATION_OPTIONS : [];
  box.innerHTML = opts.map(v => `
    <div class="form-check">
      <input class="form-check-input" type="checkbox" value="${v.id}" id="vio_${v.id}">
      <label class="form-check-label" for="vio_${v.id}">${escapeHtml(v.violation_name || 'Violation')}</label>
    </div>
  `).join('') || '<div class="text-muted">No violations available</div>';
}

function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

async function openHotspotModal(lat, lng, area) {
  const body = document.getElementById('hotspotModalBody');
  const title = document.getElementById('hotspotModalLabel');
  if (title) title.textContent = `Hotspot Tickets — ${area ?? 'Location'}`;
  if (body) body.innerHTML = `<div class="text-center py-4 text-muted">Loading…</div>`;

  const q = buildQuery(getFilters());
  try {
    const res = await fetch(`/dataAnalytics/hotspotTickets?lat=${lat}&lng=${lng}&${q}`);
    const json = await res.json();
    const rows = json.tickets || [];
    if (!rows.length) {
      body.innerHTML = `<div class="text-center py-4 text-muted">No tickets found for this spot in the selected range.</div>`;
    } else {
      body.innerHTML = `
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr><th>ID</th><th>Issued At</th><th>Location</th><th>Status</th></tr>
            </thead>
            <tbody>
              ${rows.map(r => `
                <tr>
                  <td>${r.id}</td>
                  <td>${r.issued_at}</td>
                  <td>${escapeHtml(r.location)}</td>
                  <td><span class="badge ${r.status==='Paid'?'bg-success':'bg-warning text-dark'}">${r.status}</span></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>`;
    }
  } catch (e) {
    body.innerHTML = `<div class="text-danger">Failed to load tickets.</div>`;
  }
  window._analyticsModal?.show();
}

// Run on initial load and after AJAX swaps
document.addEventListener('DOMContentLoaded', initAnalytics);
document.addEventListener('page:loaded', initAnalytics);
