$(function(){
    const $cont = $('#ticketContainer');
  
    // Inline status change
    $cont.on('change','.status-select',function(){
      const $row=$(this).closest('tr');
      const id = $row.data('id');
      const status=$(this).val();
      $.ajax({
        url:`/ticket/${id}`,
        method:'PATCH',
        data:{status},
        headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success(){ Swal.fire({toast:true,position:'top-end',icon:'success',title:'Status updated',timer:1200}); },
        error(){ Swal.fire('Error','Could not update status','error'); }
      });
    });
  
    // Quick view
    $cont.on('click','.view-btn',function(){
      const id=$(this).data('id');
      $.ajax({
        url:`/ticket/${id}`,
        headers:{'X-Requested-With':'XMLHttpRequest'},
        success(html){
          $('#ticketQuickView .modal-body').html(html);
          var m=new bootstrap.Modal($('#ticketQuickView'));
          m.show();
        }
      });
    });
  
    // Bulk actions
    $(document).on('change','#select-all',function(){
      $('.row-select').prop('checked',this.checked).trigger('change');
    });
    $cont.on('change','.row-select',function(){
      const any=$('.row-select:checked').length>0;
      $('#bulk-pay,#bulk-cancel').prop('disabled',!any);
    });
  
    $('#bulk-pay').click(()=>{ /* gather ids, send AJAX to mark paid */ });
    $('#bulk-cancel').click(()=>{ /* gather ids, send AJAX to cancel */ });
  });