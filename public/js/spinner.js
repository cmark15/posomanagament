document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('global-loader');
  
    // 1) Hide the loader now that the page has loaded:
    loader.style.display = 'none';
  
    // 2) Whenever any link or submit‐button is clicked, show it:
    document.addEventListener('click', e => {
      const tag = e.target.closest('a, button[type="submit"]');
      if (tag) loader.style.display = 'flex';
    });
  
    // 3) Also handle any form submit (for non-button submits):
    document.querySelectorAll('form').forEach(form =>
      form.addEventListener('submit', () => { loader.style.display = 'flex'; })
    );
  });
  
  // 4) In case of browser navigation (reload/back/forward), show it:
  window.addEventListener('beforeunload', () => {
    document.getElementById('global-loader').style.display = 'flex';
  });