<div class="modal fade" id="editModaL" tabindex="-1">
  <div class="modal-dialog">
    <form id="editEnforcerForm" method="POST" action="">
      @csrf
      @method('PUT')

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Enforcer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Badge Number</label>
            <input type="text" class="form-control" name="badge_num" id="edit_badge_num" readonly>
          </div>

          <div class="row g-2">
            <div class="col-md-4 mb-3">
              <label class="form-label">First Name</label>
              <input type="text" class="form-control" name="fname" id="edit_fname">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Middle Name</label>
              <input type="text" class="form-control" name="mname" id="edit_mname">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Last Name</label>
              <input type="text" class="form-control" name="lname" id="edit_lname">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" id="edit_phone">
          </div>

          <div class="row g-2">
            <div class="col-md-6 mb-3">
              <label class="form-label">Ticket Start</label>
              <input type="number" class="form-control" name="ticket_start" id="edit_ticket_start" disabled>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Ticket End</label>
              <input type="number" class="form-control" name="ticket_end" id="edit_ticket_end" disabled>
            </div>
          </div>

          <div class="mb-1">
            <label for="password" class="form-label">New Password (optional)</label>
            <div class="input-group">
              <input type="text" class="form-control" name="password" id="edit_password" placeholder="Leave blank to keep current">
              <button type="button" class="btn btn-outline-secondary" id="btnGenPass">Generate</button>
            </div>
            <div class="form-text">If left blank, the password is unchanged.</div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
