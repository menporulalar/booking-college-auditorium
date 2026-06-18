<?php
$pageTitle  = 'Edit Booking';
$activePage = 'pending';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../../app/Models/Booking.php';
Auth::startSession();
include __DIR__ . '/../../layouts/admin-header.php';
?>

<div class="page-header">
  <div>
    <h1>Edit Booking</h1>
    <p>Admin override — booking #<?= $booking['id'] ?> for <?= htmlspecialchars($booking['user_name'] ?? '') ?></p>
  </div>
  <a href="<?= APP_URL ?>/admin/bookings/<?= $booking['id'] ?>" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </a>
</div>

<?php if ($flash['error']): ?>
<div class="alert alert-error">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
  <span><?= $flash['error'] ?></span>
</div>
<?php endif; ?>

<div class="alert alert-info">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
  <span style="font-size:12.5px;">Editing this booking on behalf of the staff member. Changes are logged. If the booking is still pending, conflict status will be re-checked after saving.</span>
</div>

<form method="POST" action="<?= APP_URL ?>/admin/bookings/<?= $booking['id'] ?>/edit" novalidate>
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

  <div class="card" style="max-width:640px;">
    <div class="card-header"><h2>Booking Details</h2></div>
    <div class="card-body">

      <div class="form-group">
        <label class="form-label">Event Name <span style="color:var(--red)">*</span></label>
        <input type="text" name="event_name" class="form-control" value="<?= htmlspecialchars($booking['event_name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="event_description" class="form-control"><?= htmlspecialchars($booking['event_description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Date <span style="color:var(--red)">*</span></label>
        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d', strtotime($booking['start_datetime'])) ?>" required>
      </div>

      <div class="form-row form-row-2">
        <div class="form-group" style="margin:0;">
          <label class="form-label">Start Time <span style="color:var(--red)">*</span></label>
          <input type="time" name="start_time" class="form-control" value="<?= date('H:i', strtotime($booking['start_datetime'])) ?>" required>
        </div>
        <div class="form-group" style="margin:0;">
          <label class="form-label">End Time <span style="color:var(--red)">*</span></label>
          <input type="time" name="end_time" class="form-control" value="<?= date('H:i', strtotime($booking['end_datetime'])) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Attendee Count</label>
        <input type="number" name="attendee_count" class="form-control" min="1" value="<?= htmlspecialchars((string)($booking['attendee_count'] ?? '')) ?>">
      </div>

      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Special Requirements</label>
        <textarea name="special_requirements" class="form-control"><?= htmlspecialchars($booking['special_requirements'] ?? '') ?></textarea>
      </div>

    </div>
  </div>

  <div style="display:flex;gap:10px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
      Save Changes
    </button>
    <a href="<?= APP_URL ?>/admin/bookings/<?= $booking['id'] ?>" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/../../layouts/admin-footer.php'; ?>
