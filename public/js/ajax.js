// public/js/ajax.js
;(function(){
  const contentEl = document.getElementById('app-body');
  const overlay   = document.getElementById('ajaxLoading');

  function showLoading(on){ if(overlay) overlay.classList.toggle('d-none', !on); }

  // highlight active link in sidebar
  function markActive(url){
    document.querySelectorAll('.sidebar .nav-link').forEach(a=>{
      const same = a.href === (location.origin + url.replace(location.origin,''));
      a.classList.toggle('active', same);
    });
  }

  async function loadContent(url, push=true){
    try{
      showLoading(true);
      const res  = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
      const html = await res.text();
      const doc  = new DOMParser().parseFromString(html, 'text/html');
      const fresh= doc.querySelector('#app-body') || doc.body;

      contentEl.innerHTML = fresh.innerHTML;

      const title = doc.querySelector('title')?.textContent?.trim();
      if (title) document.title = title;
      if (push) history.pushState({}, '', url);

      markActive(url);
      window.scrollTo({ top: 0, behavior: 'instant' });

      // Let page scripts (e.g., ticketTable.js) react after swap
      document.dispatchEvent(new CustomEvent('page:loaded', { detail: { url } }));
    }catch(err){
      console.error(err);
      if (window.Swal) Swal.fire('Error','Failed to load page.','error');
    }finally{
      showLoading(false);
    }
  }

  // Intercept only links that have data-ajax (or links inside sidebar)
  document.addEventListener('click', e=>{
    const a = e.target.closest('a');
    if (!a) return;

    if (a.hasAttribute('data-no-ajax')) return; // let normal submit happen
    if (!a.hasAttribute('data-ajax') && !a.closest('.sidebar')) return;

    // make sidebar links SPA by default
    if (!a.hasAttribute('data-ajax') && a.closest('.sidebar')) {
      a.setAttribute('data-ajax','');
    }

    e.preventDefault();
    loadContent(a.getAttribute('href'), true);
  });

  // Back/forward
  window.addEventListener('popstate', ()=> loadContent(location.pathname + location.search, false));

  // Initial boot
  document.addEventListener('DOMContentLoaded', ()=>{
    markActive(location.pathname + location.search);
    document.dispatchEvent(new CustomEvent('page:loaded', { detail: { url: location.pathname + location.search }}));
  });
})();
