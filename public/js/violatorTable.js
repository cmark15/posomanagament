(function($){
  const $category = $('#category_filter');
  const $search   = $('#search_input');
  const $btn      = $('#search_btn');
  const $cont     = $('#violatorContainer');

  function getUrlParams() {
    const p = new URLSearchParams(window.location.search);
    return {
      category: p.get('category') || '',
      search:   p.get('search')   || '',
      page:     p.get('page')     || '1'
    };
  }

  function updateUrl(category, search, page) {
    const p = new URLSearchParams();
    if (category) p.set('category', category);
    if (search)   p.set('search',   search);
    if (page && page !== '1') p.set('page', page);
    history.pushState(null, '', `${window.location.pathname}?${p}`);
  }

  function loadPage(page = '1', push = true) {
    const category = $category.val() || '';
    const search   = ($search.val() || '').trim();

    $.ajax({
      url: '/violatorTable/partial',      // ← confirm this route exists
      data: { category, search, page },
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success(html) {
        $cont.html(html);
        if (push) updateUrl(category, search, page);
      },
      error(err) {
        console.error('AJAX Error:', err);
      }
    });
  }

  $(function(){
    const { category, search, page } = getUrlParams();
    if (category) $category.val(category);
    if (search)   $search.val(search);
    loadPage(page, /*push=*/false);
  });

  $category.on('change', () => loadPage('1'));
  $btn.attr('type','button').on('click', () => loadPage('1'));
  $search.on('keypress', e => {
    if (e.which === 13) {
      e.preventDefault();
      loadPage('1');
    }
  });

  // Delegate *inside* #violationContainer to .pagination a
  $cont.on('click', '.pagination a', function(e) {
    e.preventDefault();
    const newPage = new URL(this.href).searchParams.get('page') || '1';
    loadPage(newPage);
  });

  window.addEventListener('popstate', () => {
    const { category, search, page } = getUrlParams();
    $category.val(category);
    $search.val(search);
    loadPage(page, /*push=*/false);
  });
})(jQuery);
