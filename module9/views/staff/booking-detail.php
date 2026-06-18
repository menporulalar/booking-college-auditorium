<?php
$pageTitle  = 'Booking Details';
$activePage = 'my-bookings';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Models/Booking.php';
Auth::startSession();
include __DIR__ . '/../layouts/staff-header.php';
?>

<div class="page-header">
  <div>
    <h1><?= htmlspecialchars($booking['event_name']) ?></h1>
    <p>Booking #<?= $booking['id'] ?> · Submitted <?= date('d M Y', strtotime($booking['created_at'])) ?></p>
  </div>
  <a href="<?= APP_URL ?>/bookings" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to My Bookings
  </a>
</div>

<?php if ($flash['success']): ?>
<div class="alert alert-success">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
  <span><?= $flash['success'] ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;" id="detail-grid">

  <!-- Left -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Status banner -->
    <?php
    $statusInfo = [
      'pending'          => ['icon' => 'clock',   'title' => 'Pending Review',    'desc' => 'Your booking is awaiting admin approval.'],
      'pending_conflict' => ['icon' => 'alert',   'title' => 'Under Conflict Review', 'desc' => 'This time slot overlaps with another booking. An admin is reviewing it.'],
      'approved'         => ['icon' => 'check',   'title' => 'Approved',          'desc' => 'Your booking has been confirmed.'],
      'rejected'         => ['icon' => 'x',       'title' => 'Rejected',          'desc' => 'This booking request was not approved.'],
      'cancelled'        => ['icon' => 'x',       'title' => 'Cancelled',         'desc' => 'This booking was cancelled.'],
    ];
    $s = $statusInfo[$booking['status']] ?? $statusInfo['pending'];
    $colors = Booking::STATUSES[$booking['status']] ?? Booking::STATUSES['pending'];
    ?>
    <div class="card" style="border-left:4px solid <?= $colors['color'] ?>;">
      <div class="card-body" style="display:flex;align-items:center;gap:14px;">
        <div style="width:40px;height:40px;border-radius:50%;background:<?= $colors['bg'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <?php if ($s['icon'] === 'clock'): ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?= $colors['color'] ?>" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <?php elseif ($s['icon'] === 'alert'): ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="<?= $colors['color'] ?>"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          <?php elseif ($s['icon'] === 'check'): ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="<?= $colors['color'] ?>"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
          <?php else: ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?= $colors['color'] ?>" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          <?php endif; ?>
        </div>
        <div>
          <p style="font-weight:700;color:<?= $colors['color'] ?>;font-size:15px;"><?= $s['title'] ?></p>
          <p style="font-size:13px;color:var(--muted);margin-top:2px;"><?= $s['desc'] ?></p>
        </div>
      </div>
      <?php if ($booking['admin_note']): ?>
      <div style="padding:0 20px 20px;">
        <div style="background:#F9FAFB;border-radius:8px;padding:12px 14px;font-size:13px;color:var(--text);">
          <strong>Admin note:</strong> <?= htmlspecialchars($booking['admin_note']) ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Event details -->
    <div class="card">
      <div class="card-header"><h2>Event Details</h2></div>
      <div class="card-body">
        <table style="width:100%;border-collapse:collapse;">
          <?php $rows = [
            'Auditorium'  => $booking['auditorium_name'],
            'Date'        => date('l, d F Y', strtotime($booking['start_datetime'])),
            'Time'        => date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])),
            'Attendees'   => $booking['attendee_count'] ? number_format($booking['attendee_count']) : '—',
            'Description' => $booking['event_description'] ?: '—',
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
      <div class="card-header"><h2>Requested Equipment</h2></div>
      <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php foreach ($equipment as $e): ?>
            <span class="facility-tag" style="font-size:13px;padding:6px 12px;background:#EBF3FA;color:#2E75B6;">
              <?= htmlspecialchars(Booking::EQUIPMENT_OPTIONS[$e['equipment_name']] ?? $e['equipment_name']) ?>
              × <?= $e['quantity'] ?>
              <?php if ($e['status'] === 'unavailable'): ?>
                <span style="color:var(--red);font-weight:700;"> (Unavailable)</span>
              <?php endif; ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Right: actions -->
  <div style="display:flex;flex-direction:column;gap:20px;">
    <div class="card">
      <div class="card-header"><h2>Actions</h2></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">

        <?php if (in_array($booking['status'], ['pending', 'pending_conflict'])): ?>
          <form method="POST" action="<?= APP_URL ?>/bookings/<?= $booking['id'] ?>/cancel">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <button type="submit" class="btn btn-danger" style="width:100%;" onclick="return confirm('Cancel this booking?')">
              Cancel Booking
            </button>
          </form>

          <?php if ($booking['recurrence_group_id']): ?>
          <form method="POST" action="<?= APP_URL ?>/bookings/<?= $booking['id'] ?>/cancel">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="cancel_series" value="1">
            <button type="submit" class="btn btn-danger" style="width:100%;" onclick="return confirm('Cancel the entire recurring series? This will cancel all pending occurrences.')">
              Cancel Entire Series
            </button>
          </form>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($booking['status'] === 'approved'): ?>
          <a href="<?= APP_URL ?>/bookings/<?= $booking['id'] ?>/ical" class="btn btn-secondary" style="width:100%;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download .ics
          </a>
          <p class="form-hint" style="margin:0;">Calendar export available from Module 8.</p>
        <?php endif; ?>

        <?php if ($booking['recurrence_group_id']): ?>
        <div class="alert alert-info" style="margin:0;">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
          <span style="font-size:12.5px;">This is part of a recurring series.</span>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script>
function adjustGrid() {
  const grid = document.getElementById('detail-grid');
  grid.style.gridTemplateColumns = window.innerWidth < 900 ? '1fr' : '1fr 320px';
}
window.addEventListener('resize', adjustGrid);
adjustGrid();
</script>

<?php include __DIR__ . '/../layouts/staff-footer.php'; ?>
