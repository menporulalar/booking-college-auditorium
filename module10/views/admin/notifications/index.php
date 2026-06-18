<?php
$pageTitle  = 'Notifications';
$activePage = 'notifications';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
Auth::startSession();
include __DIR__ . '/../../layouts/admin-header.php';

$eventLabels = [
    'booking_submitted'    => 'New Booking Submitted',
    'booking_approved'     => 'Booking Approved',
    'booking_rejected'     => 'Booking Rejected',
    'booking_conflict'     => 'Conflict Flagged',
    'booking_override'     => 'Admin Override Approved',
    'booking_alternate'    => 'Alternate Time Suggested',
    'booking_reminder'     => 'Reminder (24hrs Before)',
    'booking_cancelled'    => 'Booking Cancelled',
    'equipment_unavailable'=> 'Equipment Unavailable',
    'series_approved'      => 'Series Approved',
    'series_rejected'      => 'Series Rejected',
    'test_email'           => 'Test Email',
];
?>

<div class="page-header">
  <div>
    <h1>Notifications</h1>
    <p>Configure email notification toggles and view delivery history</p>
  </div>
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

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
  <div class="card"><div class="card-body">
    <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Total Sent</div>
    <div style="font-size:28px;font-weight:700;color:var(--navy);margin:4px 0;"><?= $stats['total'] ?></div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Successful</div>
    <div style="font-size:28px;font-weight:700;color:var(--green);margin:4px 0;"><?= $stats['sent'] ?></div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Failed</div>
    <div style="font-size:28px;font-weight:700;color:var(--red);margin:4px 0;"><?= $stats['failed'] ?></div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Today</div>
    <div style="font-size:28px;font-weight:700;color:var(--blue);margin:4px 0;"><?= $stats['today'] ?></div>
  </div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;" id="notif-grid">

  <!-- Left: log -->
  <div>

    <!-- Filter -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-body">
        <form method="GET" action="<?= APP_URL ?>/admin/notifications" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div style="flex:1;min-width:160px;">
            <label class="form-label">Event Type</label>
            <select name="event" class="form-control">
              <option value="">All Events</option>
              <?php foreach ($eventLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($_GET['event'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="min-width:140px;">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="">All</option>
              <option value="sent"   <?= ($_GET['status'] ?? '') === 'sent'   ? 'selected' : '' ?>>Sent</option>
              <option value="failed" <?= ($_GET['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>
          </div>
          <div style="min-width:140px;">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
          </div>
          <div style="min-width:140px;">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">
          </div>
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="<?= APP_URL ?>/admin/notifications" class="btn btn-secondary">Reset</a>
        </form>
      </div>
    </div>

    <!-- Log table -->
    <div class="card">
      <div class="card-header">
        <h2>Delivery Log</h2>
        <span style="font-size:13px;color:var(--muted);">Last 50 entries</span>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if ($logs): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Event</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Sent</th></tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $l): ?>
              <tr>
                <td><span class="facility-tag"><?= htmlspecialchars($eventLabels[$l['trigger_event']] ?? $l['trigger_event']) ?></span></td>
                <td>
                  <?= htmlspecialchars($l['recipient_name'] ?? '') ?><br>
                  <span style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($l['recipient_email']) ?></span>
                </td>
                <td style="max-width:240px;"><?= htmlspecialchars($l['subject']) ?></td>
                <td>
                  <?php if ($l['status'] === 'sent'): ?>
                    <span class="status-badge badge-active">Sent</span>
                  <?php else: ?>
                    <span class="status-badge" style="background:#FEF2F2;color:var(--red);">Failed</span>
                  <?php endif; ?>
                </td>
                <td style="color:var(--muted);white-space:nowrap;"><?= date('d M, g:i A', strtotime($l['sent_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">No notifications match the selected filters.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: settings -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Toggle settings -->
    <div class="card">
      <div class="card-header"><h2>Email Triggers</h2></div>
      <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/admin/notifications">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

          <div style="display:flex;flex-direction:column;gap:4px;">
            <?php foreach ($settings as $s): ?>
            <label style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);cursor:pointer;">
              <span>
                <span style="font-size:13px;font-weight:600;display:block;"><?= htmlspecialchars($s['label']) ?></span>
                <span style="font-size:11.5px;color:var(--muted);"><?= htmlspecialchars($s['description']) ?></span>
              </span>
              <input
                type="checkbox"
                name="enabled[]"
                value="<?= htmlspecialchars($s['event_key']) ?>"
                <?= $s['enabled'] ? 'checked' : '' ?>
                style="width:18px;height:18px;accent-color:var(--accent);margin-top:2px;flex-shrink:0;"
              >
            </label>
            <?php endforeach; ?>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px;">Save Settings</button>
        </form>
      </div>
    </div>

    <!-- Test email -->
    <div class="card">
      <div class="card-header"><h2>Test SMTP</h2></div>
      <div class="card-body">
        <p style="font-size:13px;color:var(--muted);margin-bottom:12px;">
          Send a test email to your own address (<?= htmlspecialchars(Auth::user()['email']) ?>) to verify SMTP configuration.
        </p>
        <form method="POST" action="<?= APP_URL ?>/admin/notifications/test">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <button type="submit" class="btn btn-secondary" style="width:100%;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            Send Test Email
          </button>
        </form>
        <div class="alert alert-info" style="margin-top:14px;margin-bottom:0;">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
          <span style="font-size:12.5px;">SMTP host, port, and credentials are configured in <code>config/app.php</code>.</span>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function adjustGrid() {
  const grid = document.getElementById('notif-grid');
  grid.style.gridTemplateColumns = window.innerWidth < 900 ? '1fr' : '1fr 360px';
}
window.addEventListener('resize', adjustGrid);
adjustGrid();
</script>

<?php include __DIR__ . '/../../layouts/admin-footer.php'; ?>
