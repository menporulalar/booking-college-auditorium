<?php
$isEdit     = isset($auditorium);
$pageTitle  = $isEdit ? 'Edit Auditorium' : 'Add Auditorium';
$activePage = 'auditoriums';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
Auth::startSession();
include __DIR__ . '/../../../layouts/admin-header.php';

// Merge old POST values for re-population
$val = function(string $key, $default = '') use ($old) {
    return htmlspecialchars($old[$key] ?? $default);
};
$selectedFacilities = $old['facilities'] ?? ($isEdit ? ($auditorium['facilities'] ?? []) : []);
?>

<!-- Page header -->
<div class="page-header">
  <div>
    <h1><?= $pageTitle ?></h1>
    <p><?= $isEdit ? 'Update details, facilities, and images' : 'Fill in details to add a new auditorium' ?></p>
  </div>
  <a href="<?= APP_URL ?>/admin/auditoriums" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </a>
</div>

<!-- Validation errors -->
<?php if (!empty($errors)): ?>
<ul class="error-list">
  <?php foreach ($errors as $e): ?>
    <li><?= htmlspecialchars($e) ?></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<form
  method="POST"
  action="<?= APP_URL ?>/admin/auditoriums/<?= $isEdit ? $auditorium['id'] . '/edit' : 'create' ?>"
  enctype="multipart/form-data"
  novalidate
>
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

  <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

    <!-- Left column -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Basic details card -->
      <div class="card">
        <div class="card-header"><h2>Basic Details</h2></div>
        <div class="card-body">

          <div class="form-group">
            <label class="form-label">Auditorium Name <span>*</span></label>
            <input
              type="text"
              name="name"
              class="form-control"
              placeholder="e.g. Arts Auditorium"
              value="<?= $val('name', $isEdit ? $auditorium['name'] : '') ?>"
              required
            >
          </div>

          <div class="form-row form-row-2">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Capacity <span>*</span></label>
              <input
                type="number"
                name="capacity"
                class="form-control"
                placeholder="e.g. 400"
                min="1"
                max="5000"
                value="<?= $val('capacity', $isEdit ? $auditorium['capacity'] : '') ?>"
                required
              >
              <p class="form-hint">Maximum number of people</p>
            </div>

            <div class="form-group" style="margin:0;">
              <label class="form-label">Status</label>
              <select name="status" class="form-control">
                <option value="active"   <?= ($old['status'] ?? ($isEdit ? $auditorium['status'] : 'active')) === 'active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($old['status'] ?? ($isEdit ? $auditorium['status'] : ''))       === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Opening Time <span>*</span></label>
              <input
                type="time"
                name="operational_start"
                class="form-control"
                value="<?= $val('operational_start', $isEdit ? substr($auditorium['operational_start'], 0, 5) : '08:00') ?>"
                required
              >
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Closing Time <span>*</span></label>
              <input
                type="time"
                name="operational_end"
                class="form-control"
                value="<?= $val('operational_end', $isEdit ? substr($auditorium['operational_end'], 0, 5) : '20:00') ?>"
                required
              >
            </div>
          </div>

          <div class="form-group" style="margin:0;">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" placeholder="Brief description of the hall and its uses…"><?= $val('description', $isEdit ? $auditorium['description'] : '') ?></textarea>
          </div>

        </div>
      </div>

      <!-- Facilities card -->
      <div class="card">
        <div class="card-header">
          <h2>Available Facilities</h2>
          <span style="font-size:12px;color:var(--muted);">Check all that apply</span>
        </div>
        <div class="card-body">
          <div class="checkbox-grid">
            <?php foreach ($facilities as $key => $label): ?>
            <label class="checkbox-item">
              <input
                type="checkbox"
                name="facilities[<?= $key ?>]"
                value="1"
                <?= in_array($key, $selectedFacilities) ? 'checked' : '' ?>
              >
              <span><?= htmlspecialchars($label) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>

    <!-- Right column -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Hall photo card -->
      <div class="card">
        <div class="card-header"><h2>Hall Photo</h2></div>
        <div class="card-body">
          <?php if ($isEdit && !empty($auditorium['photo'])): ?>
            <img
              src="<?= APP_URL ?>/uploads/auditoriums/<?= htmlspecialchars($auditorium['photo']) ?>"
              alt="Hall photo"
              class="img-preview"
            >
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--red);cursor:pointer;margin-bottom:12px;">
              <input type="checkbox" name="remove_photo" value="1">
              Remove current photo
            </label>
          <?php endif; ?>

          <label class="upload-zone" for="photo_upload">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <p>Click to upload photo</p>
            <p style="font-size:11px;margin-top:4px;">JPEG, PNG or WebP · Max 5 MB</p>
          </label>
          <input type="file" id="photo_upload" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewImage(this,'photo_preview')">
          <img id="photo_preview" style="display:none;" class="img-preview" alt="Preview">
        </div>
      </div>

      <!-- Floor plan card -->
      <div class="card">
        <div class="card-header"><h2>Floor Plan</h2></div>
        <div class="card-body">
          <?php if ($isEdit && !empty($auditorium['floor_plan'])): ?>
            <img
              src="<?= APP_URL ?>/uploads/auditoriums/<?= htmlspecialchars($auditorium['floor_plan']) ?>"
              alt="Floor plan"
              class="img-preview"
            >
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--red);cursor:pointer;margin-bottom:12px;">
              <input type="checkbox" name="remove_floor_plan" value="1">
              Remove current floor plan
            </label>
          <?php endif; ?>

          <label class="upload-zone" for="fp_upload">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>Click to upload floor plan</p>
            <p style="font-size:11px;margin-top:4px;">JPEG, PNG or WebP · Max 5 MB</p>
          </label>
          <input type="file" id="fp_upload" name="floor_plan" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="previewImage(this,'fp_preview')">
          <img id="fp_preview" style="display:none;" class="img-preview" alt="Preview">
        </div>
      </div>

      <!-- Submit card -->
      <div class="card">
        <div class="card-body" style="display:flex;gap:10px;">
          <button type="submit" class="btn btn-primary" style="flex:1;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <?= $isEdit ? 'Update Auditorium' : 'Create Auditorium' ?>
          </button>
          <a href="<?= APP_URL ?>/admin/auditoriums" class="btn btn-secondary">Cancel</a>
        </div>
      </div>

    </div>
  </div>
</form>

<script>
function previewImage(input, previewId) {
  const preview = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Responsive: stack columns on mobile
function adjustLayout() {
  const grid = document.querySelector('form > div[style*="grid-template-columns"]');
  if (window.innerWidth < 900) {
    grid.style.gridTemplateColumns = '1fr';
  } else {
    grid.style.gridTemplateColumns = '1fr 360px';
  }
}
window.addEventListener('resize', adjustLayout);
adjustLayout();
</script>

<?php include __DIR__ . '/../../../layouts/admin-footer.php'; ?>
