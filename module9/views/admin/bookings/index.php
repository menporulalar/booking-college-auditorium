<?php
$pageTitle  = 'All Bookings';
$activePage = 'bookings';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../../app/Models/Booking.php';
Auth::startSession();
include __DIR__ . '/../../layouts/admin-header.php';
?>

<div class="page-header">
  <div>
    <h1>All Bookings</h1>
    <p><?= $total ?> total booking(s)</p>
  </div>
  <a href="<?= APP_URL ?>/admin/bookings/pending" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    Pending Approvals
  </a>
</div>

<?php if ($flash['success']): ?>
<div class="alert alert-success">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
  <span><?= $flash['success'] ?></span>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-body">
    <form method="GET" action="<?= APP_URL ?>/admin/bookings" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">

      <div style="flex:1;min-width:160px;">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Event name or staff name…" value="<?= htmlspecialchars($filters['search']) ?>">
      </div>

      <div style="min-width:160px;">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach (Booking::STATUSES as $key => $info): ?>
            <option value="<?= $key ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= $info['label'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="min-width:160px;">
        <label class="form-label">Auditorium</label>
        <select name="auditorium_id" class="form-control">
          <option value="">All Auditoriums</option>
          <?php foreach ($auditoriums as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $filters['auditorium_id'] == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="min-width:140px;">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filters['from']) ?>">
      </div>

      <div style="min-width:140px;">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filters['to']) ?>">
      </div>

      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="<?= APP_URL ?>/admin/bookings" class="btn btn-secondary">Reset</a>
    </form>
  </div>
</div>

<!-- Results table -->
<div class="card">
  <div class="card-body" style="padding:0;">
    <?php if ($bookings): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Event</th>
            <th>Auditorium</th>
            <th>Requested by</th>
            <th>Date &amp; Time</th>
            <th>Status</th>
            <th>Recurring</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td><strong><?= htmlspecialchars($b['event_name']) ?></strong></td>
            <td><?= htmlspecialchars($b['auditorium_name']) ?></td>
            <td><?= htmlspecialchars($b['user_name']) ?></td>
            <td style="white-space:nowrap;">
              <?= date('d M Y', strtotime($b['start_datetime'])) ?><br>
              <span style="color:var(--muted);font-size:12px;">
                <?= date('g:i A', strtotime($b['start_datetime'])) ?> – <?= date('g:i A', strtotime($b['end_datetime'])) ?>
              </span>
            </td>
            <td><?= Booking::statusBadge($b['status']) ?></td>
            <td>
              <?php if ($b['recurrence_group_id']): ?>
                <span class="facility-tag" style="background:#EBF3FA;color:#2E75B6;">Series</span>
              <?php else: ?>
                <span style="color:var(--muted);">—</span>
              <?php endif; ?>
            </td>
            <td><a href="<?= APP_URL ?>/admin/bookings/<?= $b['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
    <div style="display:flex;justify-content:center;gap:6px;padding:16px;border-top:1px solid var(--border);flex-wrap:wrap;">
      <?php for ($i = 1; $i <= $pages; $i++):
        $qs = array_merge($_GET, ['page' => $i]);
      ?>
        <a href="<?= APP_URL ?>/admin/bookings?<?= http_build_query($qs) ?>"
           class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:60px 20px;color:var(--muted);">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:.4;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <p style="font-size:15px;">No bookings match the selected filters.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../../layouts/admin-footer.php'; ?>
