<?php
$pageTitle  = 'Blackout Dates';
$activePage = 'blackout';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
Auth::startSession();
include __DIR__ . '/../../layouts/admin-header.php';
?>

<!-- Page header -->
<div class="page-header">
  <div>
    <h1>Blackout Dates</h1>
    <p>Block dates when auditoriums are unavailable for booking</p>
  </div>
</div>

<!-- Flash -->
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

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;" id="blackout-grid">

  <!-- Left: list -->
  <div>

    <!-- Filter form -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-body">
        <form method="GET" action="<?= APP_URL ?>/admin/blackout-dates" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div style="flex:1;min-width:160px;">
            <label class="form-label">Auditorium</label>
            <select name="auditorium_id" class="form-control">
              <option value="">All Auditoriums</option>
              <?php foreach ($auditoriums as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ($filter['auditorium_id'] == $a['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($a['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="flex:1;min-width:140px;">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filter['from']) ?>">
          </div>
          <div style="flex:1;min-width:140px;">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filter['to']) ?>">
          </div>
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="<?= APP_URL ?>/admin/blackout-dates" class="btn btn-secondary">Reset</a>
        </form>
      </div>
    </div>

    <!-- Blackout list -->
    <div class="card">
      <div class="card-header">
        <h2>Blocked Dates</h2>
        <span style="font-size:13px;color:var(--muted);"><?= count($blackouts) ?> result<?= count($blackouts) !== 1 ? 's' : '' ?></span>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if ($blackouts): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Auditorium</th>
                <th>Reason</th>
                <th>Added by</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($blackouts as $b): ?>
              <tr>
                <td>
                  <strong><?= date('d M Y', strtotime($b['blackout_date'])) ?></strong>
                </td>
                <td style="color:var(--muted);">
                  <?= date('l', strtotime($b['blackout_date'])) ?>
                </td>
                <td>
                  <?php if ($b['auditorium_id']): ?>
                    <span class="facility-tag"><?= htmlspecialchars($b['auditorium_name']) ?></span>
                  <?php else: ?>
                    <span class="facility-tag" style="background:#FEF3C7;color:#92400E;">All Halls</span>
                  <?php endif; ?>
                </td>
                <td style="color:var(--muted);max-width:200px;">
                  <?= htmlspecialchars($b['reason'] ?? '—') ?>
                </td>
                <td style="color:var(--muted);">
                  <?= htmlspecialchars($b['created_by_name'] ?? '—') ?>
                </td>
                <td>
                  <form method="POST" action="<?= APP_URL ?>/admin/blackout-dates/<?= $b['id'] ?>/delete" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <button type="submit" class="btn btn-danger btn-sm"
                      onclick="return confirm('Remove blackout for <?= date('d M Y', strtotime($b['blackout_date'])) ?>?')">
                      Remove
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">
            No blackout dates found for the selected filters.
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: add form -->
  <div>
    <div class="card">
      <div class="card-header"><h2>Add Blackout Date</h2></div>
      <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/admin/blackout-dates/create" novalidate>
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

          <div class="form-group">
            <label class="form-label">Date <span style="color:var(--red)">*</span></label>
            <input type="date" name="blackout_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Auditorium</label>
            <select name="auditorium_id" class="form-control">
              <option value="">All Auditoriums</option>
              <?php foreach ($auditoriums as $a): ?>
                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="form-hint">Leave as "All Auditoriums" to block every hall on this date (e.g. public holiday)</p>
          </div>

          <div class="form-group">
            <label class="form-label">Reason</label>
            <input type="text" name="reason" class="form-control" placeholder="e.g. Public holiday, Maintenance…">
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Blackout Date
          </button>
        </form>

        <div class="alert alert-info" style="margin-top:16px;margin-bottom:0;">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
          <span style="font-size:12.5px;">Blackout dates prevent staff from submitting bookings. Existing approved bookings are not affected.</span>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function adjustGrid() {
  const grid = document.getElementById('blackout-grid');
  grid.style.gridTemplateColumns = window.innerWidth < 900 ? '1fr' : '1fr 340px';
}
window.addEventListener('resize', adjustGrid);
adjustGrid();
</script>

<?php include __DIR__ . '/../../layouts/admin-footer.php'; ?>
