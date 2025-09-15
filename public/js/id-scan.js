// public/js/id-scan.js  (OCR-only, direct /vendor paths)
(() => {
  console.log('%cID-SCAN v2025-08-30-final','color:#0a0');

  // ---------- Utils ----------
  const notify = (title, text, icon='info') => {
    if (window.Swal) Swal.fire({ icon, title, text });
    else alert(`${title}\n${text||''}`);
  };
  async function closeScanModal() {
    const m = document.getElementById('scanIdModal');
    try {
      if (m && window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(m).hide();
      }
    } catch (_) {}

    // manual fallback cleanup
    if (m) {
      m.classList.remove('show');
      m.setAttribute('aria-hidden','true');
      m.style.display='none';
    }
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
  }

  // Modal lifecycle
  const modalEl = document.getElementById('scanIdModal');
  if (modalEl) {
    if (window.bootstrap?.Modal) {
      modalEl.addEventListener('shown.bs.modal', () => { startCamera(); getWorker().catch(()=>{}); });
      modalEl.addEventListener('hidden.bs.modal', async () => { await stopCamera(); });
    } else {
      // fallback if Bootstrap JS isn’t present
      document.getElementById('openScanId')?.addEventListener('click', () => { startCamera(); getWorker().catch(()=>{}); });
      document.getElementById('scan-close')?.addEventListener('click', async () => { await stopCamera(); closeScanModal(); });
    }
  }
  const setValSmart = (id, name, val) => {
    if (val == null) return;
    const v = String(val).trim();
    let el = document.getElementById(id);
    if (!el) el = document.querySelector(`[name="${name}"]`);
    if (el && 'value' in el) el.value = v;
  };
  function fillForm({ firstName, middleName, lastName, address, licenseNumber, birthdate }) {
    setValSmart('first_name','first_name',firstName);
    setValSmart('middle_name','middle_name',middleName);
    setValSmart('last_name','last_name',lastName);
    setValSmart('address','address',address);
    setValSmart('license_num','license_num',licenseNumber);   // your field
    if (birthdate) setValSmart('birthdate','birthdate',birthdate);
  }

  // ---------- Tesseract worker (DIRECT vendor paths) ----------
  // These are your actual files under /public/vendor/tesseract
  const TESS = {
    workerPath: "/vendor/tesseract/worker.min.js",
    corePath:   "/vendor/tesseract/tesseract-core.wasm",     // <-- DIRECT
    langPath:   "/vendor/tesseract",                          // <-- DIRECT
    gzip:       true
  };
  let worker = null, workerReady = null;
  const ocrStatus = (msg) => {
    const el = document.getElementById('ocr-status');
    if (el) el.innerHTML = `<small class="text-muted">${msg||''}</small>`;
  };
  async function getWorker() {
    if (worker) return worker;
    if (workerReady) return workerReady;
    ocrStatus('Loading OCR…');
    if (window.Tesseract && Tesseract.setLogging) Tesseract.setLogging(true); // verbose

    workerReady = (async () => {
      const w = await Tesseract.createWorker({
        workerPath: TESS.workerPath,
        corePath:   TESS.corePath,
        langPath:   TESS.langPath,
        gzip:       TESS.gzip
      });
      await w.loadLanguage('eng');
      await w.initialize('eng');
      await w.setParameters({
        tessedit_pageseg_mode: '6',       // single uniform text block
        preserve_interword_spaces: '1',
        user_defined_dpi: '300'
      });
      worker = w;
      ocrStatus('Ready');
      return worker;
    })();

    return workerReady;
  }

  // ---------- Camera ----------
  let stream = null, devices = [], currentIdx = -1;
  async function listCameras() {
    try {
      const all = await navigator.mediaDevices.enumerateDevices();
      return all.filter(d => d.kind === 'videoinput');
    } catch { return []; }
  }
  function constraintsFor(deviceId) {
    const v = {
      width:  { ideal: 1920 },
      height: { ideal: 1080 },
      facingMode: 'environment',
      focusMode: 'continuous'
    };
    return deviceId ? { audio:false, video:{ ...v, deviceId:{ exact: deviceId } } }
                    : { audio:false, video:v };
  }
  async function startCamera(next=false) {
    await stopCamera();
    if (!devices.length) devices = await listCameras();

    if (devices.length) {
      if (currentIdx === -1) {
        const back = devices.findIndex(d => /back|rear|environment/i.test(d.label));
        currentIdx = back >= 0 ? back : 0;
      } else if (next) {
        currentIdx = (currentIdx + 1) % devices.length;
      }
    }

    const deviceId = devices[currentIdx]?.deviceId;
    try {
      stream = await navigator.mediaDevices.getUserMedia(constraintsFor(deviceId));
      const v = document.getElementById('ocr-video');
      v.srcObject = stream;
      await new Promise(res => (v.onloadedmetadata = () => res()));
      await v.play();
    } catch (e) {
      console.warn('camera error', e);
      try {
        stream = await navigator.mediaDevices.getUserMedia({ audio:false, video:true });
        const v = document.getElementById('ocr-video');
        v.srcObject = stream; await v.play();
      } catch (e2) {
        notify('Camera error','Could not open camera. Check HTTPS & permissions.','warning');
      }
    }
  }
  async function stopCamera() {
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
  }

  // ---------- Preprocess (contrast + threshold) ----------
  function preprocess(canvas) {
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const img = ctx.getImageData(0,0,canvas.width,canvas.height);
    const d = img.data;
    let min=255,max=0;
    const gray = new Uint8ClampedArray(d.length/4);
    for (let i=0,j=0;i<d.length;i+=4,j++){
      const g = 0.2126*d[i] + 0.7152*d[i+1] + 0.0722*d[i+2];
      gray[j]=g; if(g<min)min=g; if(g>max)max=g;
    }
    const span=Math.max(1,max-min), gamma=0.9;
    const hist=new Uint32Array(256);
    for (let j=0;j<gray.length;j++){
      let v=(gray[j]-min)*(255/span);
      v=255*Math.pow(v/255,gamma); hist[v|0]++;
    }
    let sum=0,sumB=0,wB=0,wF=0,maxBetween=0,th=127,total=gray.length;
    for(let t=0;t<256;t++) sum+=t*hist[t];
    for(let t=0;t<256;t++){
      wB+=hist[t]; if(!wB)continue;
      wF=total-wB; if(!wF)break;
      sumB+=t*hist[t];
      const mB=sumB/wB,mF=(sum-sumB)/wF,between=wB*wF*(mB-mF)*(mB-mF);
      if(between>maxBetween){maxBetween=between; th=t;}
    }
    for (let i=0;i<d.length;i+=4){
      const v = d[i]*0.2126 + d[i+1]*0.7152 + d[i+2]*0.0722;
      const t = v>th?255:0; d[i]=d[i+1]=d[i+2]=t;
    }
    ctx.putImageData(img,0,0);
  }

  // ---------- PH ID parsing ----------
  function parseOCRText(raw) {
    const text=(raw||'').replace(/\u00A0/g,' ').replace(/[|]/g,' ');
    const lines=text.split('\n').map(s=>s.replace(/\s+/g,' ').trim()).filter(Boolean);

    let firstName='', middleName='', lastName='', address='', birthdate='', licenseNumber='', anyId='';

    const grabAfter=(label)=>{
      const i=lines.findIndex(l=>new RegExp(`^${label}\\b`,'i').test(l));
      if(i>=0) return lines[i].replace(new RegExp(`^${label}\\s*[:#-]*\\s*`,'i'), '').trim();
      return '';
    };

    lastName   = grabAfter('(LAST NAME|SURNAME|APELYIDO)');
    firstName  = grabAfter('(FIRST NAME|GIVEN NAME|GIVEN NAMES|PANGALAN)');
    middleName = grabAfter('(MIDDLE NAME|MIDDLE INITIAL|GITNANG (APELYIDO|PANGALAN)|M\\.I\\.)');

    if (!lastName && !firstName) {
      const nameLine=lines.find(l=>l.includes(',') && /^[A-Z ,.'-]+$/i.test(l));
      if(nameLine){
        const [l,rest]=nameLine.split(',',2);
        lastName=(l||'').trim();
        const t=(rest||'').trim().split(/\s+/);
        firstName=t[0]||''; middleName=t.slice(1).join(' ');
      }
    }

    const dobRaw = grabAfter('(BIRTHDATE|DATE OF BIRTH|DOB|PETSANG KAPANGANAKAN|KAARAWAN|KAPANGANAKAN)');
    if (dobRaw){
      const m1=dobRaw.match(/(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/);
      const m2=dobRaw.match(/(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2,4})/);
      const m3=dobRaw.match(/([A-Za-z]{3,})\s+(\d{1,2}),?\s+(\d{4})/);
      if(m1){ const[_,y,mo,d]=m1; birthdate=`${y}-${String(mo).padStart(2,'0')}-${String(d).padStart(2,'0')}`; }
      else if(m2){ let[_,d,mo,y]=m2; if(y.length===2) y=`20${y}`; birthdate=`${y}-${String(mo).padStart(2,'0')}-${String(d).padStart(2,'0')}`; }
      else if(m3){ const mon={jan:1,feb:2,mar:3,apr:4,may:5,jun:6,jul:7,aug:8,sep:9,oct:10,nov:11,dec:12};
                   const mo=mon[m3[1].slice(0,3).toLowerCase()]||1; birthdate=`${m3[3]}-${String(mo).padStart(2,'0')}-${String(m3[2]).padStart(2,'0')}`; }
    }

    const addrIdx=lines.findIndex(l=>/^ADDRESS\b|^TIRAHAN\b/i.test(l));
    if(addrIdx>=0) address=lines.slice(addrIdx,addrIdx+2).join(', ').replace(/^(ADDRESS|TIRAHAN)\s*[:#-]*/i,'').trim();
    else {
      const guess=lines.find(l=>/\d+.*[A-Za-z]/.test(l) && !/LICENSE|DL\s*NO|BIRTH|DOB|KAARAWAN/i.test(l));
      if(guess) address=guess;
    }

    for(const l of lines){
      const mDL=l.match(/(LICENSE\s*NO\.?|DL\s*NO\.?|DRIVER'?S\s*LICENSE(?:\s*NO\.?)?)\s*[:#-]*\s*([A-Z0-9\-]{7,20})/i);
      if(mDL){ licenseNumber=mDL[2]; break; }
      const mID=l.match(/(ID\s*NO\.?|PIN|CRN|PSN|PHILSYS(?:\s*NO\.?)?|PHILHEALTH(?:\s*NO\.?)?|UMID|SSS|GSIS|TIN)\s*[:#-]*\s*([A-Z0-9\-]{7,20})/i);
      if(!anyId && mID) anyId=mID[2];
    }
    if(!licenseNumber && anyId) licenseNumber=anyId;

    if(!firstName || !lastName){
      const cands=lines.filter(l=>/^[A-Z .,'-]+$/i.test(l)).sort((a,b)=>b.length-a.length);
      const c=cands[0];
      if(c){ const p=c.split(/\s+/); if(p.length>=3){ firstName=p[0]; middleName=p.slice(1,-1).join(' '); lastName=p.at(-1);} }
    }

    return { firstName, middleName, lastName, address, licenseNumber, birthdate };
  }

  // ---------- Capture & OCR ----------
  async function captureAndOCR() {
    const v=document.getElementById('ocr-video');
    if(!v || !v.videoWidth) return notify('Hold up','Camera not ready yet.','info');

    const c=document.getElementById('ocr-canvas');
    c.width=v.videoWidth; c.height=v.videoHeight;
    const ctx=c.getContext('2d'); ctx.drawImage(v,0,0,c.width,c.height);
    preprocess(c);

    ocrStatus('Reading…');
    const w=await getWorker();
    let text='';
    try { const r=await w.recognize(c); text=r?.data?.text||''; }
    catch(e){ console.error('Tesseract error',e); notify('OCR error','Could not read text.', 'warning'); ocrStatus(''); return; }
    ocrStatus('');

    const dbg=document.getElementById('ocr-debug'); if(dbg) dbg.textContent=text;
    const parsed=parseOCRText(text);
    fillForm(parsed);
    notify('Captured!','Fields have been auto-filled from the ID.','success');

    await stopCamera();
    await closeScanModal();
  }

  // ---------- Modal / events ----------
  const modal=document.getElementById('scanIdModal');
  if(modal){
    modal.addEventListener('shown.bs.modal', ()=>{ startCamera(); getWorker().catch(()=>{}); });
    modal.addEventListener('hidden.bs.modal', async ()=>{ await stopCamera(); closeScanModal(); });
  }
  document.getElementById('ocr-capture')?.addEventListener('click', captureAndOCR);
  document.getElementById('ocr-switch')?.addEventListener('click', async ()=>{ await startCamera(true); });

  // console smoke (await in console: await window.ocrSmoke())
  window.ocrSmoke = async () => {
    const w = await getWorker();
    const tiny="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAPElEQVQYV2P8z8Dwn4EIwDiQkJAFGJgYjKRDUMiEQg0QW0gGmQYQ0jQYBzYkCqCw0QBrYwBgaQkAE3AigAAbJjB5nJ8l1QAAAABJRU5ErkJggg==";
    const r=await w.recognize(tiny);
    return (r?.data?.text||'').trim(); // expect "TEST"
  };
})();
