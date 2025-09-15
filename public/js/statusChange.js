// base URL for your detail-partial route
  const detailUrlBase = "{{ route('ticket.partial') }}";

  // 2) Handle status change
  $(document).on('change', '.status-select', function() {
    const ticketId = $(this).data('ticket-id');
    const statusId = $(this).val();

    $.post(`/ticket/${ticketId}/status`, { 
        _token: $('meta[name="csrf-token"]').attr('content'),
        status_id: statusId 
      })
      .done(() => {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Status updated',
          timer: 1500,
          showConfirmButton: false
        });
        // re-load the same partial into your modal/body
        $.get(`${detailUrlBase}?ticket_id=${ticketId}`, html => {
          $('#violatorModalBody').html(html);
        });
      })
      .fail(() => {
        Swal.fire('Error','Could not update status','error');
      });
  });