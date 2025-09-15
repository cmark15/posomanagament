// Geolocation (optional on desktop)
if ('geolocation' in navigator) {
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude, longitude } = pos.coords;
    const lat = document.getElementById('latitude');
    const lng = document.getElementById('longitude');
    if (lat) lat.value = latitude;
    if (lng) lng.value = longitude;
  });
}

// Violations category & checklist
const selectEl    = document.getElementById('categorySelect');
const containerEl = document.getElementById('violationsContainer');
const selected    = new Set();

function renderCategory() {
  containerEl.innerHTML = '';
  const list = (window.violationGroups || {})[selectEl.value] || [];
  list.forEach(v => {
    const wrap = document.createElement('div');
    wrap.className = 'form-check mb-2';
    wrap.innerHTML = `
      <input class="form-check-input"
             type="checkbox"
             name="violations[]"
             id="v-${v.id}"
             value="${v.violation_code}"
             ${selected.has(v.violation_code)?'checked':''}>
      <label class="form-check-label" for="v-${v.id}">
        ${v.violation_name} — ₱${parseFloat(v.fine_amount).toFixed(2)}
      </label>`;
    const chk = wrap.querySelector('input');
    chk.addEventListener('change', () => {
      chk.checked ? selected.add(chk.value) : selected.delete(chk.value);
    });
    containerEl.appendChild(wrap);
  });
}
selectEl.addEventListener('change', renderCategory);

// Auto-fill owner name
const ownerChk  = document.getElementById('is_owner');
const ownerIn   = document.getElementById('owner_name');
const f = document.getElementById('first_name');
const m = document.getElementById('middle_name');
const l = document.getElementById('last_name');
function syncOwner() {
  if (!ownerChk || !ownerIn) return;
  if (ownerChk.checked) {
    let full = (f?.value || '').trim();
    if (m?.value?.trim()) full += ' ' + m.value.trim();
    if (l?.value?.trim()) full += ' ' + l.value.trim();
    ownerIn.value    = full;
    ownerIn.readOnly = true;
  } else {
    ownerIn.value    = '';
    ownerIn.readOnly = false;
  }
}
ownerChk?.addEventListener('change', syncOwner);
[f,m,l].forEach(el => el?.addEventListener('input', () => ownerChk.checked && syncOwner()));
syncOwner();

// Submit + Confirm + Print (same shape as Enforcer)
document.getElementById('ticketForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const data = new FormData(form);

  try {
    const res = await fetch(form.action, {
      method: form.method,
      headers: {
        'X-CSRF-TOKEN': form._token.value,
        'Accept': 'application/json'
      },
      body: data
    });
    if (!res.ok) {
      const msg = await res.text();
      return Swal.fire('Error', msg, 'error');
    }
    const p = await res.json();
    console.log('ADMIN ticket →', p);

    let html = `
      <strong>Ticket #:</strong> ${p.ticket.ticket_number}<br>
      <strong>Enforcer:</strong> ${p.enforcer.name} (${p.enforcer.badge_num})<br>
      <strong>Violator:</strong> ${p.violator.first_name} ${p.violator.middle_name ?? ''} ${p.violator.last_name}<br>
      <strong>License No.:</strong> ${p.violator.license_number || ''}<br>
      <strong>Plate:</strong> ${p.vehicle.plate_number}<br>
      <strong>Type:</strong> ${p.vehicle.vehicle_type}<br>
      <strong>Owner:</strong> ${p.vehicle.is_owner}<br>
      <strong>Owner Name:</strong> ${p.vehicle.owner_name || ''}<br>
      <strong>Location:</strong> ${p.ticket.location || ''}<br>
      <strong>Confiscated:</strong> ${p.ticket.confiscated}<br>
      <strong>Impounded:</strong> ${p.ticket.flags.includes('is_impounded') ? 'Yes' : 'No'}<br>
      <strong>Resident:</strong> ${p.ticket.flags.includes('is_resident') ? 'Yes' : 'No'}<br>
      <strong>Last Apprehended:</strong> ${p.last_apprehended_at || 'Never'}<br>
      <strong>Username:</strong> ${p.credentials.username}<br>
      <strong>Password:</strong> ${p.credentials.password}<br>
      <strong>Violations:</strong>
      <ul>`;
    p.violations.forEach(v => {
      html += `<li>${v.name} — Php${v.fine}</li>`;
    });
    html += `</ul>`;

    const { isConfirmed } = await Swal.fire({
      title: 'Confirm Details',
      html,
      width: 600,
      showCancelButton: true,
      confirmButtonText: 'Save & Print',
    });
    if (!isConfirmed) return;

    // Bluetooth print (same ESC/POS as Enforcer)
    const S = '49535343-fe7d-4ae5-8fa9-9fafd205e455';
    const C = '49535343-8841-43f4-a8d4-ecbe34729bb3';
    const dev = await navigator.bluetooth.requestDevice({
      acceptAllDevices: true,
      optionalServices: [S]
    });
    const srv = await dev.gatt.connect();
    const svc = await srv.getPrimaryService(S);
    const ch  = await svc.getCharacteristic(C);

    const ESC = '\x1B', GS = '\x1D', NL = '\x0A';

    // Optional QR (short URL)
    function getQrCmd(data) {
      const storeLen = data.length + 3;
      const pL = storeLen % 256, pH = Math.floor(storeLen / 256);
      return '\x1D\x28\x6B\x04\x00\x31\x41\x32\x00' + // model 2
             '\x1D\x28\x6B\x03\x00\x31\x43\x06'   +   // size 6
             '\x1D\x28\x6B\x03\x00\x31\x45\x30'   +   // EC level L
             '\x1D\x28\x6B' + String.fromCharCode(pL,pH) + '\x31\x50\x30' + data +
             '\x1D\x28\x6B\x03\x00\x31\x51\x30';      // print
    }
    const qrCmd = new TextEncoder().encode(getQrCmd('https://poso.gov.ph/t'));
    for (let i = 0; i < qrCmd.length; i += 20) {
      await ch.writeValue(qrCmd.slice(i, i + 20));
      await new Promise(r => setTimeout(r, 40));
    }

    let txt = '';
    txt += '\tCity of San Carlos' + NL;
    txt += 'Public Order and Safety Office' + NL;
    txt += '\t(POSO)' + NL + NL;
    txt += 'Traffic Citation Ticket' + NL;
    txt += '\tENFORCER' + NL;
    txt += 'Ticket #: ' + p.ticket.ticket_number + NL;
    txt += 'Date issued: ' + p.ticket.issued_at + NL + NL;
    txt += 'Violator: ' + p.violator.first_name + ' ' + (p.violator.middle_name ?? '') + ' ' + p.violator.last_name + NL;
    txt += 'Birthdate: ' + (p.violator.birthdate ?? '') + NL;
    txt += 'Address: ' + (p.violator.address ?? '') + NL;
    txt += 'License No.: ' + (p.violator.license_number ?? '') + NL + NL;
    txt += 'Plate: ' + p.vehicle.plate_number + NL;
    txt += 'Type: ' + p.vehicle.vehicle_type + NL;
    txt += 'Owner: ' + p.vehicle.is_owner + NL;
    txt += 'Owner Name: ' + (p.vehicle.owner_name ?? '') + NL + NL;
    txt += 'Violations:' + NL;
    p.violations.forEach(v => { txt += `- ${v.name} (Php${v.fine})` + NL; });
    txt += NL;
    txt += 'Username: ' + p.credentials.username + NL;
    txt += 'Password: ' + p.credentials.password + NL + NL;
    txt += 'Last Apprehended: ' + (p.last_apprehended_at || 'Never') + NL + NL;
    if (p.ticket.flags.includes('is_impounded')) txt += '*** VEHICLE IMPOUNDED ***' + NL + NL;
    txt += 'Badge No: ' + p.enforcer.badge_num + NL + NL;
    txt += NL + NL + NL + NL;
    txt += '__________________________' + NL;
    txt += 'Signature of Violator' + NL + NL;
    txt += '- - - - - - - - - - - - - - - -' + NL + NL;

    txt += '\tCity of San Carlos' + NL;
    txt += 'Public Order and Safety Office' + NL;
    txt += '\t(POSO)' + NL + NL;
    txt += 'Traffic Citation Ticket' + NL;
    txt += '\tVIOLATOR' + NL;
    txt += 'Ticket #: ' + p.ticket.ticket_number + NL;
    txt += 'Date issued: ' + p.ticket.issued_at + NL + NL;
    txt += 'Violator: ' + p.violator.first_name + ' ' + (p.violator.middle_name ?? '') + ' ' + p.violator.last_name + NL;
    txt += 'Birthdate: ' + (p.violator.birthdate ?? '') + NL;
    txt += 'Address: ' + (p.violator.address ?? '') + NL;
    txt += 'License No.: ' + (p.violator.license_number ?? '') + NL + NL;
    txt += 'Plate: ' + p.vehicle.plate_number + NL;
    txt += 'Type: ' + p.vehicle.vehicle_type + NL;
    txt += 'Owner: ' + p.vehicle.is_owner + NL;
    txt += 'Owner Name: ' + (p.vehicle.owner_name ?? '') + NL + NL;
    txt += 'Violations:' + NL;
    p.violations.forEach(v => { txt += `- ${v.name} (Php${v.fine})` + NL; });
    txt += NL;
    txt += 'Username: ' + p.credentials.username + NL;
    txt += 'Password: ' + p.credentials.password + NL + NL;
    txt += 'Last Apprehended: ' + (p.last_apprehended_at || 'Never') + NL + NL;
    if (p.ticket.flags.includes('is_impounded')) txt += '*** VEHICLE IMPOUNDED ***' + NL + NL;
    txt += NL + ESC + 'd' + '\x03' + GS + 'V' + '\x00';

    const bytes = new TextEncoder().encode(txt);
    for (let i = 0; i < bytes.length; i += 20) {
      await ch.writeValue(bytes.slice(i, i + 20));
      await new Promise(r => setTimeout(r, 40));
    }

    await Swal.fire('Success', 'Ticket Submitted.', 'success');
    form.reset();
    selected.clear();
    containerEl.innerHTML = '';
  } catch (err) {
    console.error(err);
    Swal.fire('Error', err.message || err, 'error');
  }
});
