<?php
$pageTitle  = 'Calendar Export';
$activePage = 'calendar-export';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Models/Booking.php';
Auth::startSession();
include __DIR__ . '/../layouts/admin-header.php';
?>

<div class="page-header">
  <div>
    <h1>Calendar Export</h1>
    <p>Export approved bookings as an .ics file for Outlook, Apple Calendar, or Google Calendar</p>
  </div>
</div>

<?php if ($flash['error']): ?>
<div class="alert alert-error">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
  <span><?= $flash['error'] ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;" id="export-grid">

  <!-- Left: results -->
  <div>

    <!-- Filter -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-body">
        <form method="GET" action="<?= APP_URL ?>/admin/calendar-export" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div style="flex:1;min-width:180px;">
            <label class="form-label">Auditorium</label>
            <select name="auditorium_id" class="form-control">
              <option value="">All Auditoriums</option>
              <?php foreach ($auditoriums as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $filters['auditorium_id'] == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="min-width:160px;">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filters['from']) ?>">
          </div>
          <div style="min-width:160px;">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filters['to']) ?>">
          </div>
          <button type="submit" name="preview" value="1" class="btn btn-secondary">Preview</button>
        </form>
      </div>
    </div>

    <!-- Preview table -->
    <div class="card">
      <div class="card-header">
        <h2>Preview</h2>
        <?php if (!empty($_GET['preview'])): ?>
          <span style="font-size:13px;color:var(--muted);"><?= count($preview) ?> approved booking(s)</span>
        <?php endif; ?>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($_GET['preview'])): ?>
          <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">
            Select filters and click <strong>Preview</strong> to see which approved bookings will be included.
          </p>
        <?php elseif (empty($preview)): ?>
          <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">
            No approved bookings found for the selected filters.
          </p>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Event</th><th>Auditorium</th><th>Date &amp; Time</th><th>Requested by</th></tr></thead>
            <tbody>
              <?php foreach ($preview as $b): ?>
              <tr>
                <td><strong><?= htmlspecialchars($b['event_name']) ?></strong></td>
                <td><?= htmlspecialchars($b['auditorium_name']) ?></td>
                <td style="white-space:nowrap;">
                  <?= date('d M Y', strtotime($b['start_datetime'])) ?><br>
                  <span style="color:var(--muted);font-size:12px;">
                    <?= date('g:i A', strtotime($b['start_datetime'])) ?> – <?= date('g:i A', strtotime($b['end_datetime'])) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($b['user_name']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Right: download + info -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <div class="card">
      <div class="card-header"><h2>Export</h2></div>
      <div class="card-body">
        <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">
          Downloads an <code>.ics</code> file containing all <strong>approved</strong> bookings matching your filters.
          This file can be imported into Outlook, Apple Calendar, or Google Calendar.
        </p>
        <a
          href="<?= APP_URL ?>/admin/calendar-export/download?<?= http_build_query($filters) ?>"
          class="btn btn-primary"
          style="width:100%;"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download .ics File
        </a>
      </div>
    </div>

    <div class="alert alert-info" style="margin:0;">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
      <div style="font-size:12.5px;">
        <strong>How to import:</strong><br>
        <strong>Outlook:</strong> File → Open &amp; Export → Import/Export → iCalendar (.ics)<br>
        <strong>Apple Calendar:</strong> File → Import…<br>
        <strong>Google Calendar:</strong> Settings → Import &amp; Export → Import
      </div>
    </div>

  </div>
</div>

<script>
function adjustGrid() {
  const grid = document.getElementById('export-grid');
  grid.style.gridTemplateColumns = window.innerWidth < 900 ? '1fr' : '1fr 320px';
}
window.addEventListener('resize', adjustGrid);
adjustGrid();
</script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
