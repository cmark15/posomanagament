// public/js/issueTicket.js — deploy-ready, online+offline with idempotency

// ---- notify helpers (Swal fallback) ----
const Notify = {
  toast(o={}){ if (window.Swal) Swal.fire({toast:true,position:'top-end',timer:o.timer||2000,showConfirmButton:false,...o});
               else alert(o.title||o.text||'Notice'); },
  modal(o={}){ if (window.Swal) return Swal.fire(o);
               const ok = confirm(`${o.title||'Confirm'}\n${o.text||o.html||''}`); return Promise.resolve({isConfirmed:ok}); },
  info(m){this.toast({icon:'info',title:m})}, ok(m){this.toast({icon:'success',title:m})},
  warn(m){this.toast({icon:'warning',title:m})}, err(m){this.toast({icon:'error',title:m})}
};

/* one-time guard */
if (window.__ISSUE_TICKET_WIRED__) {
  console.warn('[issueTicket] duplicate include — skipping');
} else {
  window.__ISSUE_TICKET_WIRED__ = true;

  /* ---- constants ---- */
  const SYNC_ENDPOINT = '/pwa/sync/ticket';
  const FORM_ENDPOINT = '/enforcerTicket';
  const SYNC_TAG      = 'sync-tickets';

  // Dexie config (MUST match serviceworker.js)
  const DB_NAME    = 'ticketDB';
  const DB_VERSION = 221;                          // <— match your SW
  const DB_SCHEMA  = { tickets: '++id,client_uuid,created_at' };

  /* ---- Dexie (with localStorage fallback) ---- */
  const hasDexie = !!window.Dexie;
  if (hasDexie && !window.ticketDB) {
    try {
      window.ticketDB = new Dexie(DB_NAME);
      window.ticketDB.version(DB_VERSION).stores(DB_SCHEMA).upgrade(()=>{});
    } catch (e) {
      console.warn('[Dexie init failed]', e);
    }
  }
  const LS_KEY = 'offline_tickets';
  const lsRead  = ()=>{ try { return JSON.parse(localStorage.getItem(LS_KEY) || '[]'); } catch { return []; } };
  const lsWrite = (a)=>{ try { localStorage.setItem(LS_KEY, JSON.stringify(a)); } catch {} };

  /* ---- small helpers ---- */
  const byId  = (id)=>document.getElementById(id);
  const sleep = (ms)=>new Promise(r=>setTimeout(r,ms));
  const getCSRF = ()=> document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value || '';

  async function isReallyOnline(){
    if (!navigator.onLine) return false;
    try { const res = await fetch('/ping', {cache:'no-store'}); return res.ok; }
    catch { return false; }
  }

  function ensureClientUuid(formEl){
    let hidden = formEl.querySelector('input[name="client_uuid"]');
    if (!hidden) { hidden = document.createElement('input'); hidden.type='hidden'; hidden.name='client_uuid'; formEl.appendChild(hidden); }
    hidden.value = hidden.value || (crypto?.randomUUID?.() || (Date.now()+'-'+Math.random().toString(16).slice(2)));
    return hidden.value;
  }

  function buildJsonPayloadFromFormData(fd){
    const arr = (k)=>fd.getAll(k) || [];
    const val = (k)=>fd.get(k) ?? '';
    return {
      first_name: val('first_name'),
      middle_name: val('middle_name'),
      last_name: val('last_name'),
      address: val('address'),
      birthdate: val('birthdate') || null,
      license_number: val('license_num'),
      plate_number: val('plate_num'),
      vehicle_type: val('vehicle_type'),
      is_owner: !!val('is_owner'),
      owner_name: val('owner_name'),
      violations: arr('violations[]'),
      location: val('location'),
      latitude: val('latitude') || null,
      longitude: val('longitude') || null,
      confiscation_type_id: val('confiscation_type_id') || null,
      enforcer_id: val('enforcer_id') || null,
      client_uuid: val('client_uuid') || null,
    };
  }

  /* ---- badge ---- */
  async function refreshQueueBadge(){
    const el = byId('queueCount');
    if (!el) return;

    let dexieCount = 0, lsCount = 0;
    try { dexieCount = await window.ticketDB?.tickets?.count() ?? 0; } catch {}
    try { lsCount = lsRead().length; } catch {}
    el.textContent = String(dexieCount + lsCount);
  }
  window.refreshQueueBadge = refreshQueueBadge;
  window.addEventListener('DOMContentLoaded', refreshQueueBadge);
  window.addEventListener('online', refreshQueueBadge);

  /* ---- queue add ---- */
  async function enqueueTicket(payload){
    const uuid = payload.client_uuid;

    if (hasDexie && window.ticketDB?.tickets) {
      const exists = await window.ticketDB.tickets.where('client_uuid').equals(uuid).count();
      if (!exists) await window.ticketDB.tickets.add({ client_uuid: uuid, payload, created_at: Date.now() });
    } else {
      const list = lsRead();
      if (!list.some(r => r.client_uuid === uuid)) {
        list.push({ id: Date.now(), client_uuid: uuid, payload, created_at: Date.now() });
        lsWrite(list);
      }
    }

    if ('serviceWorker' in navigator && 'SyncManager' in window) {
      try { const reg = await navigator.serviceWorker.ready; await reg.sync.register(SYNC_TAG); } catch {}
    }

    Notify.info('Saved offline');
    byId('ticketForm')?.reset();
    refreshQueueBadge();
  }

  /* ---- manual / fallback sync ---- */
  window.syncOfflineTickets = async function(){
    if (!navigator.onLine) { Notify.warn('Go online to sync.'); return; }

    if (hasDexie && window.ticketDB?.tickets) {
      const all = await window.ticketDB.tickets.toArray();
      for (const rec of all) {
        try {
          const uuid = rec.client_uuid || rec.payload?.client_uuid || String(Date.now());
          const res = await fetch(SYNC_ENDPOINT, {
            method:'POST', credentials:'omit',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-Idempotency-Key':uuid},
            body: JSON.stringify(rec.payload)
          });
          if (res.ok) await window.ticketDB.tickets.delete(rec.id);
        } catch { /* keep for later */ }
      }
    } else {
      const list = lsRead(); const keep = [];
      for (const rec of list) {
        try {
          const uuid = rec.client_uuid || rec.payload?.client_uuid || String(Date.now());
          const res = await fetch(SYNC_ENDPOINT, {
            method:'POST', credentials:'omit',
            headers:{'Content-Type':'application/json','Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-Idempotency-Key':uuid},
            body: JSON.stringify(rec.payload)
          });
          if (!res.ok) keep.push(rec);
        } catch { keep.push(rec); }
      }
      lsWrite(keep);
    }

    await refreshQueueBadge();
    Notify.ok('Offline tickets synced');
  };

  /* ---- wire topbar buttons ---- */
  byId('syncNowBtn')?.addEventListener('click', async ()=>{
    if (!navigator.onLine) return Notify.warn('Offline — connect to sync.');
    await window.syncOfflineTickets();
  });
  byId('queueInfoBtn')?.addEventListener('click', async ()=>{
    const n = await (window.ticketDB?.tickets?.count?.() ?? lsRead().length);
    Notify.modal({icon:'info',title:'Offline queue', html:`${n||0} ticket(s) waiting to sync.`});
  });

  /* ---- network toasts ---- */
  window.addEventListener('offline', ()=> Notify.warn('Offline — tickets will be saved locally.'));
  window.addEventListener('online',  ()=> { Notify.ok('Back online'); window.syncOfflineTickets?.(); });

  /* ---- geolocation autofill ---- */
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(pos=>{
      const lat = byId('latitude'), lng = byId('longitude');
      if (lat) lat.value = pos.coords.latitude;
      if (lng) lng.value = pos.coords.longitude;
    }, err=>console.warn('Geolocation failed:', err?.message), {enableHighAccuracy:true,timeout:5000,maximumAge:0});
  }

  /* ---- violations category render ---- */
  (function(){
    const selectEl = byId('categorySelect');
    const container = byId('violationsContainer');
    const selected = new Set();
    function renderCategory(){
      if (!selectEl || !container) return;
      container.innerHTML = '';
      const list = (window.violationGroups?.[selectEl.value]) || [];
      for (const v of list) {
        const wrap = document.createElement('div');
        wrap.className = 'form-check mb-2';
        wrap.innerHTML = `
          <input class="form-check-input" type="checkbox" name="violations[]" id="v-${v.id}" value="${v.violation_code}" ${selected.has(v.violation_code)?'checked':''}>
          <label class="form-check-label" for="v-${v.id}">${v.violation_name} — ₱${parseFloat(v.fine_amount).toFixed(2)}</label>`;
        const chk = wrap.querySelector('input');
        chk.addEventListener('change', ()=> chk.checked ? selected.add(chk.value) : selected.delete(chk.value));
        container.appendChild(wrap);
      }
    }
    selectEl?.addEventListener('change', renderCategory);
  })();

  /* ---- license duplicate warning ---- */
  (function(){
    const licEl = byId('license_num'); if (!licEl) return;
    const first = byId('first_name'), middle = byId('middle_name'), last = byId('last_name');
    let warnedFor = null;
    const typedFullName = () => [first?.value, middle?.value, last?.value].map(s=>(s||'').trim()).filter(Boolean).join(' ');
    async function warnIfRegistered(license){
      if (!license || !(await isReallyOnline())) return false;
      const base = byId('ticketForm')?.dataset?.checkLicenseUrl || '/violators/check-license';
      try {
        const res = await fetch(`${base}?license=${encodeURIComponent(license)}`, {headers:{'Accept':'application/json'},credentials:'same-origin'});
        if (!res.ok) return false;
        const info = await res.json();
        if (!info.exists) return false;
        if (warnedFor === license) return true;
        warnedFor = license;
        await Notify.modal({icon:'warning', title:'License already registered',
          html:`This license belongs to <b>${info.name}</b>.<br><small>You typed: <b>${typedFullName()||'(none)'}</b></small>`, width:460});
        return true;
      } catch { return false; }
    }
    licEl.addEventListener('blur', ()=> warnIfRegistered((licEl.value||'').trim()));
    byId('ticketForm')?.addEventListener('submit', ()=>{
      const lic = (licEl.value||'').trim(); if (lic && warnedFor !== lic) warnIfRegistered(lic);
    });
  })();

  /* ---- owner name auto-fill ---- */
  (function(){
    const ownerChk = byId('is_owner'), ownerIn = byId('owner_name');
    const f=byId('first_name'), m=byId('middle_name'), l=byId('last_name');
    function syncOwner(){
      if (!ownerChk || !ownerIn) return;
      if (ownerChk.checked){
        let full=(f?.value||'').trim(); if (m?.value?.trim()) full+=' '+m.value.trim(); if (l?.value?.trim()) full+=' '+l.value.trim();
        ownerIn.value=full; ownerIn.readOnly=true;
      } else ownerIn.readOnly=false;
    }
    ownerChk?.addEventListener('change', syncOwner);
    [f,m,l].forEach(el=>el?.addEventListener('input', ()=> ownerChk?.checked && syncOwner()));
    syncOwner();
  })();

  /* ---- form submit: online vs offline ---- */
  byId('ticketForm')?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const form = e.target;
    const fd   = new FormData(form);
    const uuid = ensureClientUuid(form);

    if (!(await isReallyOnline())) {
      const payload = buildJsonPayloadFromFormData(fd);
      if (!payload.client_uuid) payload.client_uuid = uuid;
      await enqueueTicket(payload);
      return;
    }

    try {
      const res = await fetch(FORM_ENDPOINT, { method:'POST', headers:{'X-CSRF-TOKEN':getCSRF(),'Accept':'application/json'}, body:fd });
      if (!res.ok) { return Notify.err(await res.text() || 'Failed to submit'); }
      const p = await res.json();

      let html = `
        <strong>Ticket #:</strong> ${p.ticket.ticket_number}<br>
        <strong>Enforcer:</strong> ${p.enforcer.name}<br>
        <strong>Violator:</strong> ${[p.violator.first_name,p.violator.middle_name,p.violator.last_name].filter(Boolean).join(' ')}<br>
        <strong>License No.:</strong> ${p.violator.license_number||''}<br>
        <strong>Plate:</strong> ${p.vehicle.plate_number||''}<br>
        <strong>Type:</strong> ${p.vehicle.vehicle_type||''}<br>
        <strong>Owner:</strong> ${p.vehicle.is_owner}<br>
        <strong>Owner Name:</strong> ${p.vehicle.owner_name||''}<br>
        <strong>Resident:</strong> ${p.ticket.is_resident?'Yes':'No'}<br>
        <strong>Location:</strong> ${p.ticket.location||''}<br>
        <strong>Confiscated:</strong> ${p.ticket.confiscated||'None'}<br>
        <strong>Impounded:</strong> ${p.ticket.is_impounded?'Yes':'No'}<br>
        <strong>Last Apprehended:</strong> ${p.last_apprehended_at||'Never'}<br>
        <strong>Username:</strong> ${p.credentials.username}<br>
        <strong>Password:</strong> ${p.credentials.password}<br>
        <strong>Violations:</strong><ul>`;
      (p.violations||[]).forEach(v=>{ html+=`<li>${v.name} — Php${v.fine}</li>`; });
      html += `</ul>`;

      const {isConfirmed} = await Notify.modal({ title:'Confirm Details', html, width:600, showCancelButton:true, confirmButtonText:'Save & Print' });
      if (!isConfirmed) return;

      // === BLE printing (your working logic kept) ===
      await (async function printTwoCopies(p) {
        const S_MAIN='49535343-fe7d-4ae5-8fa9-9fafd205e455', C_MAIN='49535343-8841-43f4-a8d4-ecbe34729bb3';
        const S_FFE0='0000ffe0-0000-1000-8000-00805f9b34fb', C_FFE1='0000ffe1-0000-1000-8000-00805f9b34fb';

        const dev = await navigator.bluetooth.requestDevice({ acceptAllDevices: true, optionalServices: [S_MAIN, S_FFE0] });
        let server = await dev.gatt.connect();
        let svcUUID=S_MAIN, chrUUID=C_MAIN, ch;
        try { ch = await (await server.getPrimaryService(S_MAIN)).getCharacteristic(C_MAIN); }
        catch { svcUUID=S_FFE0; chrUUID=C_FFE1; ch = await (await server.getPrimaryService(S_FFE0)).getCharacteristic(C_FFE1); }

        const enc=new TextEncoder(), NL='\x0A';
        const ALIGN=(n)=>Uint8Array.of(0x1B,0x61,n), FEED=(n)=>Uint8Array.of(0x1B,0x64,n);
        const write = async (u8) => { if (ch.writeValueWithoutResponse) await ch.writeValueWithoutResponse(u8); else await ch.writeValue(u8); await sleep(60); };
        const send = async (s) => {
          const b = enc.encode(s);
          for (let i=0;i<b.length;i+=20) { await write(b.slice(i,i+20)); }
        };
        const safe = (s)=>String(s??'').normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/₱/g,'Php').replace(/[–—]/g,'-').replace(/[“”]/g,'"').replace(/[‘’]/g,"'");
        const L = async (k,v='')=>send(k + safe(v) + NL);
        const nameLine=[p.violator.first_name,p.violator.middle_name,p.violator.last_name].filter(Boolean).join(' ');

        // Header
        await write(Uint8Array.of(0x1B,0x40)); // init
        await write(ALIGN(1));
        await send('City of San Carlos'+NL);
        await send('Public Order and Safety Office'+NL);
        await send('(POSO)'+NL+NL);
        await send('Traffic Citation Ticket'+NL);
        await write(ALIGN(0));

        // Copy 1
        await L('Ticket #: ', p.ticket.ticket_number);
        await L('Date issued: ', p.ticket.issued_at);
        await write(FEED(1));
        await L('Violator: ', nameLine);
        await L('Birthdate: ', p.violator.birthdate);
        await L('Address: ', p.violator.address);
        await L('License No.: ', p.violator.license_number);
        await write(FEED(1));
        await L('Plate: ', p.vehicle.plate_number);
        await L('Type: ', p.vehicle.vehicle_type);
        await L('Owner: ', p.vehicle.is_owner);
        await L('Owner Name: ', p.vehicle.owner_name);
        await write(FEED(1));
        await send('Violations:'+NL);
        for (const v of (p.violations||[])) await send('- '+safe(v.name)+' (Php'+safe(v.fine)+')'+NL);
        await write(FEED(1));
        await L('Username: ', p.credentials.username);
        await L('Password: ', p.credentials.password);
        await write(FEED(1));
        if (p.ticket.is_impounded) { await send('*** VEHICLE IMPOUNDED ***'+NL); await write(FEED(1)); }
        await L('Badge No: ', p.enforcer.badge_num);
        await write(FEED(3));

        // Copy 2 (reprint header + summary)
        await write(ALIGN(1));
        await send('City of San Carlos'+NL);
        await send('Public Order and Safety Office'+NL);
        await send('(POSO)'+NL+NL);
        await send('Traffic Citation Ticket'+NL);
        await write(ALIGN(0));
        await L('Ticket #: ', p.ticket.ticket_number);
        await L('Date issued: ', p.ticket.issued_at);
        await write(FEED(1));
        await L('Violator: ', nameLine);
        await L('License No.: ', p.violator.license_number);
        await L('Plate: ', p.vehicle.plate_number);
        await write(FEED(5));
        try { dev.gatt.disconnect(); } catch {}
      })(p);

      Notify.ok('Ticket Submitted.');
      form.reset();
    } catch (err) {
      console.error(err);
      Notify.err(err.message || String(err));
    }
  });
}
