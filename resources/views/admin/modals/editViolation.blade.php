<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
      <form id="editViolationForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Edit Violation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
              <div class="mb-3">
                  <label>Violation Code</label>
                  <input type="text" class="form-control" name="violation_code" id="edit_violation_code">
              </div>
              <div class="mb-3">
                  <label>Violation Name</label>
                  <input type="text" class="form-control" name="violation_name" id="edit_violation_name">
              </div>
              <div class="mb-3">
                <label>Fine Amount</label>
                <input type="number" class="form-control" name="fine_amount" id="edit_fine_amount" step="0.01">
              </div>
              <div class="mb-3">
                  <label>Category</label>
                  <select name="category" class="form-select" id="edit_category">
                        <option value="" disabled selected>Choose category…</option>
                        <option value="Moving Violations">Moving Violations</option>
                        <option value="Non-Moving Violations">Non-Moving Violations</option>
                        <option value="Safety Violations">Safety Violations</option>
                        <option value="Parking Violations">Parking Violations</option>
                  </select>
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