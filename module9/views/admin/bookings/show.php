<?php
$pageTitle  = 'Review Booking';
$activePage = 'pending';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../../app/Models/Booking.php';
Auth::startSession();
include __DIR__ . '/../../layouts/admin-header.php';

$isConflict = $booking['status'] === 'pending_conflict';
$isPending  = in_array($booking['status'], ['pending', 'pending_conflict']);
?>

<div class="page-header">
  <div>
    <h1><?= htmlspecialchars($booking['event_name']) ?></h1>
    <p>Booking #<?= $booking['id'] ?> · Submitted <?= date('d M Y, g:i A', strtotime($booking['created_at'])) ?></p>
  </div>
  <a href="<?= APP_URL ?>/admin/bookings/pending" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Pending
  </a>
</div>

<?php if ($flash['success']): ?>
<div class="alert alert-success">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
  <span><?= $flash['success'] ?></span>
</div>
<?php endif; ?>
<?php if ($flash['error']): ?>
<div class="alert alert-error">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
  <span><?= $flash['error'] ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;" id="review-grid">

  <!-- Left column -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Status -->
    <div class="card">
      <div class="card-body" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span style="font-size:13px;font-weight:600;color:var(--muted);">Status:</span>
        <?= Booking::statusBadge($booking['status']) ?>
        <?php if ($booking['recurrence_group_id']): ?>
          <span class="facility-tag" style="background:#EBF3FA;color:#2E75B6;">
            Part of recurring series (<?= count($seriesBookings) ?> total occurrences)
          </span>
        <?php endif; ?>
      </div>
      <?php if ($booking['admin_note']): ?>
      <div style="padding:0 20px 20px;">
        <div style="background:#F9FAFB;border-radius:8px;padding:12px 14px;font-size:13px;">
          <strong>Admin note:</strong> <?= htmlspecialchars($booking['admin_note']) ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($booking['override_reason']): ?>
      <div style="padding:0 20px 20px;">
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;font-size:13px;">
          <strong>Override reason:</strong> <?= htmlspecialchars($booking['override_reason']) ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Conflict panel -->
    <?php if ($isConflict && $conflicts): ?>
    <div class="card" style="border-left:4px solid #DC2626;">
      <div class="card-header">
        <h2 style="color:#DC2626;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-2px;margin-right:4px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          Scheduling Conflict Detected
        </h2>
      </div>
      <div class="card-body">
        <p style="font-size:13px;color:var(--muted);margin-bottom:12px;">
          This booking overlaps with the following existing booking(s) for <strong><?= htmlspecialchars($booking['auditorium_name']) ?></strong>:
        </p>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Event</th><th>Requested by</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($conflicts as $c): ?>
              <tr>
                <td><strong><?= htmlspecialchars($c['event_name']) ?></strong></td>
                <td><?= htmlspecialchars($c['user_name']) ?></td>
                <td><?= date('d M, g:i A', strtotime($c['start_datetime'])) ?> – <?= date('g:i A', strtotime($c['end_datetime'])) ?></td>
                <td><?= Booking::statusBadge($c['status']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Booking details -->
    <div class="card">
      <div class="card-header">
        <h2>Booking Details</h2>
        <?php if ($isPending): ?>
        <a href="<?= APP_URL ?>/admin/bookings/<?= $booking['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit Details</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <table style="width:100%;border-collapse:collapse;">
          <?php $rows = [
            'Requested by' => $booking['user_name'] . ' (' . $booking['user_email'] . ')',
            'Department'   => $booking['user_department'] ?? '—',
            'Auditorium'   => $booking['auditorium_name'],
            'Date'         => date('l, d F Y', strtotime($booking['start_datetime'])),
            'Time'         => date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])),
            'Attendees'    => $booking['attendee_count'] ? number_format($booking['attendee_count']) . ' (Hall capacity: ' . number_format($hall['capacity']) . ')' : '—',
            'Description'  => $booking['event_description'] ?: '—',
            'Special requirements' => $booking['special_requirements'] ?: '—',
          ];
          foreach ($rows as $label => $value): ?>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:10px 0;font-size:13px;font-weight:600;color:var(--muted);width:35%;vertical-align:top;"><?= $label ?></td>
            <td style="padding:10px 0;font-size:13.5px;color:var(--text);"><?= htmlspecialchars($value) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>

    <!-- Equipment -->
    <?php if ($equipment): ?>
    <div class="card">
      <div class="card-header"><h2>Equipment Requests</h2></div>
      <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/admin/bookings/<?= $booking['id'] ?>/equipment">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <div class="table-wrap">
            <table>
              <thead><tr><th>Item</th><th>Qty</th><th>Availability</th></tr></thead>
              <tbody>
                <?php foreach ($equipment as $e): ?>
                <tr>
                  <td><?= htmlspecialchars(Booking::EQUIPMENT_OPTIONS[$e['equipment_name']] ?? $e['equipment_name']) ?></td>
                  <td><?= $e['quantity'] ?></td>
                  <td>
                    <select name="equipment_status[<?= $e['id'] ?>]" class="form-control" style="max-width:160px;">
                      <option value="requested"   <?= $e['status'] === 'requested'   ? 'selected' : '' ?>>Requested</option>
                      <option value="confirmed"   <?= $e['status'] === 'confirmed'   ? 'selected' : '' ?>>Confirmed</option>
                      <option value="unavailable" <?= $e['status'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <button type="submit" class="btn btn-secondary btn-sm" style="margin-top:12px;">Update Equipment Status</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Series occurrences -->
    <?php if ($seriesBookings): ?>
    <div class="card">
      <div class="card-header"><h2>Series Occurrences</h2></div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($seriesBookings as $s): ?>
              <tr style="<?= $s['id'] == $booking['id'] ? 'background:#EFF6FF;' : '' ?>">
                <td><?= date('d M Y', strtotime($s['start_datetime'])) ?></td>
                <td><?= date('g:i A', strtotime($s['start_datetime'])) ?> – <?= date('g:i A', strtotime($s['end_datetime'])) ?></td>
                <td><?= Booking::statusBadge($s['status']) ?></td>
                <td><a href="<?= APP_URL ?>/admin/bookings/<?= $s['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Right column: actions -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <?php if ($isPending): ?>

    <!-- Approve -->
    <div class="card">
      <div class="card-header"><h2>Approve</h2></div>
      <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/admin/bookings/<?= $booking['id'] ?>/approve">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="return_to" value="detail">

          <?php if ($isConflict): ?>
          <div class="form-group">
            <label class="form-label">Override Reason <span style="color:var(--red)">*</span></label>
            <textarea name="override_reason" class="form-control" required placeholder="Explain why this booking is approved despite the conflict (e.g. other booking will be relocated)…"></textarea>
            <p class="form-hint">Required — this will be permanently logged.</p>
          </div>
          <?php endif; ?>

          <div class="form-group" style="margin-bottom:12px;">
            <label class="form-label">Note to staff (optional)</label>
            <textarea name="admin_note" class="form-control" placeholder="Any additional info for the requester…"></textarea>
          </div>

          <button type="submit" class="btn btn-success" style="width:100%;"
            onclick="return confirm('<?= $isConflict ? 'Approve despite the conflict? This will be logged as an override.' : 'Approve this booking?' ?>')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            <?= $isConflict ? 'Approve with Override' : 'Approve Booking' ?>
          </button>
        </form>
      </div>
    </div>

    <!-- Reject -->
    <div class="card">
      <div class="card-header"><h2>Reject</h2></div>
      <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/admin/bookings/<?= $booking['id'] ?>/reject">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="return_to" value="detail">
          <div class="form-group" style="margin-bottom:12px;">
            <label class="form-label">Reason <span style="color:var(--red)">*</span></label>
            <textarea name="admin_note" class="form-control" required placeholder="Explain why this booking is being rejected…"></textarea>
          </div>
          <button type="submit" class="btn btn-danger" style="width:100%;" onclick="return confirm('Reject this booking? The requester will be notified.')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Reject Booking
          </button>
        </form>
      </div>
    </div>

    <!-- Suggest alternate -->
    <div class="card">
      <div class="card-header"><h2>Suggest Alternate Time</h2></div>
      <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/admin/bookings/<?= $booking['id'] ?>/suggest-alternate">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

          <div class="form-group">
            <label class="form-label">Suggested Date</label>
            <input type="date" name="suggested_date" class="form-control" min="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-row form-row-2">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Start</label>
              <input type="time" name="suggested_start" class="form-control">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">End</label>
              <input type="time" name="suggested_end" class="form-control">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" placeholder="Additional notes for the requester…"></textarea>
          </div>

          <button type="submit" class="btn btn-secondary" style="width:100%;"
            onclick="return confirm('This will mark the booking as rejected with your suggestion noted. Continue?')">
            Suggest Alternate &amp; Reject
          </button>
          <p class="form-hint">The booking is marked rejected with your suggestion. The requester can submit a new booking for the suggested slot.</p>
        </form>
      </div>
    </div>

    <?php else: ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:32px 20px;color:var(--muted);">
        <p style="font-size:13px;">This booking has already been <?= $booking['status'] ?> and no further action is needed.</p>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
function adjustGrid() {
  const grid = document.getElementById('review-grid');
  grid.style.gridTemplateColumns = window.innerWidth < 900 ? '1fr' : '1fr 360px';
}
window.addEventListener('resize', adjustGrid);
adjustGrid();
</script>

<?php include __DIR__ . '/../../layouts/admin-footer.php'; ?>
