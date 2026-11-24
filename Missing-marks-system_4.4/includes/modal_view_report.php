<!-- ================= VIEW REPORT MODAL (read-only, student side) ================= -->
<div class="modal fade kt-modal" id="viewReportModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-file-lines me-2"></i>Report <span data-field="ref"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <span class="kt-muted small">Submitted on <span data-field="created"></span></span>
          <span data-field="statusbadge" class="badge-status-pending">Pending</span>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">Student Name</div>
              <div class="fw-semibold" data-field="student"></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">Admission No.</div>
              <div class="fw-semibold" data-field="admission"></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">Trainer</div>
              <div class="fw-semibold" data-field="trainer"></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">Semester</div>
              <div class="fw-semibold" data-field="semester"></div>
            </div>
          </div>
          <div class="col-md-8">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">Unit</div>
              <div class="fw-semibold" data-field="unit"></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">Unit Code</div>
              <div class="fw-semibold" data-field="unitcode"></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">Academic Year</div>
              <div class="fw-semibold" data-field="year"></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">Assessment Type</div>
              <div class="fw-semibold" data-field="examtype"></div>
              <div class="small kt-muted mt-2">CAT 1 / CAT 2</div>
              <div class="fw-semibold" data-field="catmarks">—</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">CAT Grade</div>
              <div class="fw-semibold" data-field="catgrade"></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="kt-card-flat p-3">
              <div class="small kt-muted">End Term Grade</div>
              <div class="fw-semibold" data-field="examgrade"></div>
            </div>
          </div>
        </div>

        <div class="kt-card-flat p-3 mb-3">
          <div class="small kt-muted mb-1">Description</div>
          <div data-field="description"></div>
        </div>

        <div class="kt-card-flat p-3" style="background:#f0f6f3;">
          <div class="small kt-muted mb-1"><i class="fa-solid fa-comment-dots me-1"></i>Registrar's Remarks</div>
          <div data-field="remarks"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-kt-soft" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
