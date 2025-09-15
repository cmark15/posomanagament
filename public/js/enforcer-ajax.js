// public/js/enforcer.js
(function($){
  const container      = $('#enforcerContainer'); // main wrapper
  const tableContainer = $('#table-container');   // where the <table> lives
  const partialRoute   = '/enforcer/partial';


  //
  // ——— SPA‐style navigation: Add / Back / popstate —————————————————————————
  //
  $('#add-btn').on('click', function(e){
    e.preventDefault();
    $.get('/enforcer/create', html => {
      container.html(html);
      history.pushState(null,'','/enforcer/create');
    });
  });

  $(document).on('click','#back-btn', function(e){
    e.preventDefault();
    $.get('/enforcer', html => {
      container.html(html);
      history.pushState(null,'','/enforcer');
    });
  });

  window.addEventListener('popstate', () => {
    const url = location.pathname + location.search;
    $.get(url, html => {
      container.html(html);
    });
  });
  history.replaceState(null,'',location.pathname + location.search);


  //
  // ——— Form submission with SweetAlert —————————————————————————————————
  //
  $(document).on('submit','#enforcerForm', function(e){
    e.preventDefault();
    const $f = $(this);

    $.post($f.attr('action'), $f.serialize(), tableHtml => {
      Swal.fire({
        icon: 'success',
        title: 'Enforcer added!',
        text: 'Add another?',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
      }).then(res => {
        if (res.isConfirmed) {
          $('#add-btn').click();
        } else {
          tableContainer.html(tableHtml);
          history.pushState(null,'','/enforcer');
        }
      });
    }).fail(xhr => {
      let errs = {};
      if (xhr.status===422 && xhr.responseJSON.errors) {
        errs = xhr.responseJSON.errors;
      } else {
        errs.general = ['Something went wrong'];
      }
      let list = '<ul class="text-start">';
      $.each(errs,(k,v)=> list+=`<li>${v[0]||v}</li>`);
      list += '</ul>';
      Swal.fire({ icon:'error', title:'Error', html:list });
    });
  });


  //
  // ——— Table loading with safe defaults —————————————————————————————————
  //
  function loadTable(page = '1', push = true) {
    // grab current controls *each time*, so they exist even after a DOM swap
    const sortEl   = $('#sort_table');
    const searchEl = $('#search_input');

    const sort   = sortEl.length   ? sortEl.val()               : 'date_desc';
    const search = searchEl.length ? (searchEl.val() ?? '').trim() : '';

    $.ajax({
      url: partialRoute,
      data: { sort_option: sort, search, page },
      headers: { 'X-Requested-With':'XMLHttpRequest' },
      success(html) {
        tableContainer.html(html);

        if (push) {
          const p = new URLSearchParams();
          if (sort   !== 'date_desc') p.set('sort_option', sort);
          if (search !== '')           p.set('search', search);
          if (page   !== '1')          p.set('page', page);
          history.pushState(null,'', `${window.location.pathname}?${p}`);
        }
      },
      error(err) {
        console.error('Enforcer table load error', err);
      }
    });
  }


  //
  // ——— Initial load (on document ready) ————————————————————————————————
  //
  $(function(){
    const params = new URLSearchParams(window.location.search);
    const sort   = params.get('sort_option') || 'date_desc';
    const search = params.get('search')      || '';
    const page   = params.get('page')        || '1';

    // seed the controls with URL values
    $('#sort_table').val(sort);
    $('#search_input').val(search);

    loadTable(page, false);
  });


  //
  // ——— Delegated event bindings —————————————————————————————————————
  //
  // sort dropdown change
  $(document).on('change', '#sort_table',      () => loadTable('1'));

  // Go button click
  $(document).on('click',  '#search_btn',      () => loadTable('1'));

  // Enter key in search input
  $(document).on('keypress','#search_input', e => {
    if (e.which === 13) {
      e.preventDefault();
      loadTable('1');
    }
  });

  // pagination link click
  $(document).on('click', '#table-container .pagination a', function(e){
    e.preventDefault();
    const newPage = new URL(this.href).searchParams.get('page') || '1';
    loadTable(newPage);
  });

})(jQuery);
