// public/js/enforcerTableFilter.js
(function($){
  const $sort   = $('#sort_table');
  const $search = $('#search_input');
  const $btn    = $('#search_btn');
  const $cont   = $('#enforcerContainer');

  function getUrlParams() {
    const p = new URLSearchParams(window.location.search);
    return {
      sort:   p.get('sort_option') || 'date_desc',
      search: p.get('search')      || '',
      page:   p.get('page')        || '1',
    };
  }

  function updateUrl(sort, search, page) {
    const p = new URLSearchParams();
    if (sort   && sort!=='date_desc') p.set('sort_option', sort);
    if (search && search!=='')        p.set('search', search);
    if (page   && page!=='1')         p.set('page', page);
    history.pushState(null, '', `${window.location.pathname}?${p}`);
  }

  function loadPage(page='1', push=true) {
    const sort   = $sort.val();
    const search = $search.val().trim();

    $.ajax({
      url: '/enforcer/partial',
      data: { sort_option: sort, search, page },
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success(html) {
        $cont.html(html);
        if (push) updateUrl(sort, search, page);
      },
      error(err) {
        console.error('Error loading enforcers:', err);
      }
    });
  }

  $(function(){
    const { sort, search, page } = getUrlParams();
    $sort.val(sort);
    $search.val(search);
    loadPage(page, /*push=*/false);
  });

  $sort.on('change', ()=> loadPage('1'));
  $btn.on('click', ()=> loadPage('1'));
  $search.on('keypress', e=> {
    if (e.which===13) {
      e.preventDefault();
      loadPage('1');
    }
  });

  $cont.on('click', '.pagination a', function(e){
    e.preventDefault();
    const newPage = new URL(this.href).searchParams.get('page') || '1';
    loadPage(newPage);
  });

  window.addEventListener('popstate', () => {
    const { sort, search, page } = getUrlParams();
    $sort.val(sort);
    $search.val(search);
    loadPage(page, /*push=*/false);
  });
})(jQuery);
