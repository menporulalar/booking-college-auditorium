<?php
$pageTitle  = 'My Bookings';
$activePage = 'my-bookings';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Models/Booking.php';
Auth::startSession();
include __DIR__ . '/../layouts/staff-header.php';
?>

<div class="page-header">
  <div>
    <h1>My Bookings</h1>
    <p>Track the status of your booking requests</p>
  </div>
  <a href="<?= APP_URL ?>/bookings/new" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Booking
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

<!-- Status filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
  <?php
  $tabs = [
    ''                 => 'All',
    'pending'          => 'Pending',
    'pending_conflict' => 'Conflict Review',
    'approved'         => 'Approved',
    'rejected'         => 'Rejected',
    'cancelled'        => 'Cancelled',
  ];
  foreach ($tabs as $key => $label):
    $active = ($_GET['status'] ?? '') === $key;
  ?>
  <a href="<?= APP_URL ?>/bookings<?= $key ? '?status=' . $key : '' ?>"
     class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-secondary' ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Bookings table -->
<div class="card">
  <div class="card-body" style="padding:0;">
    <?php if ($bookings): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Event</th>
            <th>Auditorium</th>
            <th>Date &amp; Time</th>
            <th>Status</th>
            <th>Recurring</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td>
              <a href="<?= APP_URL ?>/bookings/<?= $b['id'] ?>" style="color:var(--navy);font-weight:600;text-decoration:none;">
                <?= htmlspecialchars($b['event_name']) ?>
              </a>
            </td>
            <td><?= htmlspecialchars($b['auditorium_name']) ?></td>
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
            <td>
              <a href="<?= APP_URL ?>/bookings/<?= $b['id'] ?>" class="btn btn-secondary btn-sm">View</a>
              <?php if (in_array($b['status'], ['pending', 'pending_conflict'])): ?>
                <form method="POST" action="<?= APP_URL ?>/bookings/<?= $b['id'] ?>/cancel" style="display:inline;margin:0;">
                  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                  <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this booking?')">Cancel</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div style="display:flex;justify-content:center;gap:6px;padding:16px;border-top:1px solid var(--border);">
      <?php for ($i = 1; $i <= $pages; $i++):
        $qs = array_merge($_GET, ['page' => $i]);
      ?>
        <a href="<?= APP_URL ?>/bookings?<?= http_build_query($qs) ?>"
           class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:60px 20px;color:var(--muted);">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:.4;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <p style="font-size:15px;margin-bottom:16px;">No bookings found<?= ($_GET['status'] ?? '') ? ' for this filter' : '' ?>.</p>
      <a href="<?= APP_URL ?>/bookings/new" class="btn btn-primary">Create Your First Booking</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../layouts/staff-footer.php'; ?>
