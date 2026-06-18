<?php
$pageTitle  = 'Pending Approvals';
$activePage = 'pending';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../../app/Models/Booking.php';
Auth::startSession();
include __DIR__ . '/../../layouts/admin-header.php';

// Group recurring bookings together for series display
$grouped = [];
$singles = [];
foreach ($bookings as $b) {
    if ($b['recurrence_group_id']) {
        $grouped[$b['recurrence_group_id']][] = $b;
    } else {
        $singles[] = $b;
    }
}
?>

<div class="page-header">
  <div>
    <h1>Pending Approvals</h1>
    <p><?= count($bookings) ?> booking(s) awaiting review — conflicts shown first</p>
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

<?php if (empty($bookings)): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 20px;color:var(--muted);">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:.4;"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <p style="font-size:15px;">All caught up! No pending bookings.</p>
  </div>
</div>
<?php else: ?>

<!-- Bulk action bar -->
<form method="POST" action="<?= APP_URL ?>/admin/bookings/bulk-action" id="bulk-form">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

  <div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;cursor:pointer;">
        <input type="checkbox" id="select-all" style="width:16px;height:16px;accent-color:var(--accent);">
        Select all (non-conflict)
      </label>
      <button type="submit" name="bulk_action" value="approve" class="btn btn-success btn-sm" onclick="return confirm('Approve selected bookings?')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        Bulk Approve
      </button>
      <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('bulk-reject-modal').style.display='flex'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Bulk Reject
      </button>
      <span style="font-size:12px;color:var(--muted);margin-left:auto;">
        Bookings with conflicts must be resolved individually.
      </span>
    </div>
  </div>

  <!-- Series groups -->
  <?php foreach ($grouped as $groupId => $items): ?>
    <?php $hasConflictInSeries = array_filter($items, fn($i) => $i['status'] === 'pending_conflict'); ?>
    <div class="card" style="margin-bottom:16px;border-left:4px solid #2E75B6;">
      <div class="card-header">
        <h2>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M21 12a9 9 0 11-2.64-6.36L21 8"/><path d="M21 3v5h-5"/></svg>
          Recurring Series: <?= htmlspecialchars($items[0]['event_name']) ?>
        </h2>
        <span class="facility-tag" style="background:#EBF3FA;color:#2E75B6;font-size:12px;"><?= count($items) ?> occurrence(s)</span>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrap">
          <table>
            <thead><tr><th></th><th>Date</th><th>Time</th><th>Auditorium</th><th>Requested by</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($items as $b): ?>
              <tr>
                <td><input type="checkbox" name="booking_ids[]" value="<?= $b['id'] ?>" class="row-check" <?= $b['status'] === 'pending_conflict' ? 'disabled' : '' ?> style="width:16px;height:16px;accent-color:var(--accent);"></td>
                <td><strong><?= date('d M Y', strtotime($b['start_datetime'])) ?></strong></td>
                <td><?= date('g:i A', strtotime($b['start_datetime'])) ?> – <?= date('g:i A', strtotime($b['end_datetime'])) ?></td>
                <td><?= htmlspecialchars($b['auditorium_name']) ?></td>
                <td><?= htmlspecialchars($b['user_name']) ?></td>
                <td><?= Booking::statusBadge($b['status']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;">
          <a href="<?= APP_URL ?>/admin/bookings/<?= $items[0]['id'] ?>" class="btn btn-secondary btn-sm">View First Occurrence</a>
          <?php if (empty($hasConflictInSeries)): ?>
          <button type="button" class="btn btn-success btn-sm" onclick="approveSeries(<?= $groupId ?>)">Approve Entire Series</button>
          <?php else: ?>
          <span style="font-size:12px;color:var(--red);align-self:center;">⚠ Some occurrences have conflicts — review individually before approving series</span>
          <?php endif; ?>
          <button type="button" class="btn btn-danger btn-sm" onclick="rejectSeries(<?= $groupId ?>)">Reject Entire Series</button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Single bookings -->
  <?php foreach ($singles as $b): ?>
  <?php $isConflict = $b['status'] === 'pending_conflict'; ?>
  <div class="card" style="margin-bottom:16px;<?= $isConflict ? 'border-left:4px solid #DC2626;' : '' ?>">
    <div class="card-body">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">

        <div style="display:flex;gap:12px;align-items:flex-start;">
          <input type="checkbox" name="booking_ids[]" value="<?= $b['id'] ?>" class="row-check" <?= $isConflict ? 'disabled' : '' ?> style="width:18px;height:18px;accent-color:var(--accent);margin-top:4px;flex-shrink:0;">
          <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
              <h3 style="font-size:15px;font-weight:700;color:var(--navy);"><?= htmlspecialchars($b['event_name']) ?></h3>
              <?= Booking::statusBadge($b['status']) ?>
              <?php if ($isConflict): ?>
                <span class="facility-tag" style="background:#FEF2F2;color:#DC2626;font-weight:700;">⚠ CONFLICT</span>
              <?php endif; ?>
            </div>
            <p style="font-size:13px;color:var(--muted);">
              <strong><?= htmlspecialchars($b['auditorium_name']) ?></strong> ·
              <?= date('D, d M Y', strtotime($b['start_datetime'])) ?> ·
              <?= date('g:i A', strtotime($b['start_datetime'])) ?> – <?= date('g:i A', strtotime($b['end_datetime'])) ?>
            </p>
            <p style="font-size:13px;color:var(--muted);margin-top:2px;">
              Requested by <strong><?= htmlspecialchars($b['user_name']) ?></strong>
              (<?= htmlspecialchars($b['user_department'] ?? '—') ?>) ·
              <?= $b['attendee_count'] ? number_format($b['attendee_count']) . ' attendees' : 'No attendee count' ?>
            </p>

            <?php if ($b['equipment']): ?>
            <div style="margin-top:8px;">
              <?php foreach ($b['equipment'] as $e): ?>
                <span class="facility-tag"><?= htmlspecialchars(Booking::EQUIPMENT_OPTIONS[$e['equipment_name']] ?? $e['equipment_name']) ?> × <?= $e['quantity'] ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Conflict details -->
            <?php if ($isConflict && $b['conflicts']): ?>
            <div style="margin-top:10px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;">
              <p style="font-size:12.5px;font-weight:700;color:#DC2626;margin-bottom:6px;">Conflicting booking(s):</p>
              <?php foreach ($b['conflicts'] as $c): ?>
                <p style="font-size:12.5px;color:#7F1D1D;">
                  <strong><?= htmlspecialchars($c['event_name']) ?></strong> by <?= htmlspecialchars($c['user_name']) ?> —
                  <?= date('d M, g:i A', strtotime($c['start_datetime'])) ?> – <?= date('g:i A', strtotime($c['end_datetime'])) ?>
                  (<?= Booking::statusBadge($c['status']) ?>)
                </p>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:8px;min-width:140px;">
          <a href="<?= APP_URL ?>/admin/bookings/<?= $b['id'] ?>" class="btn btn-secondary btn-sm" style="justify-content:center;">Review</a>

          <?php if (!$isConflict): ?>
          <button type="button" class="btn btn-success btn-sm" onclick="quickApprove(<?= $b['id'] ?>)" style="justify-content:center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            Approve
          </button>
          <?php else: ?>
          <span style="font-size:11px;color:var(--muted);text-align:center;">Open "Review" to resolve</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

</form>

<!-- Bulk reject modal -->
<div id="bulk-reject-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;padding:16px;">
  <div style="background:#fff;border-radius:12px;max-width:420px;width:100%;padding:24px;">
    <h3 style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:12px;">Reject Selected Bookings</h3>
    <form method="POST" action="<?= APP_URL ?>/admin/bookings/bulk-action" id="bulk-reject-form">
      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
      <div class="form-group">
        <label class="form-label">Reason <span style="color:var(--red)">*</span></label>
        <textarea name="bulk_note" class="form-control" required placeholder="Reason for rejection (sent to all selected staff)"></textarea>
      </div>
      <div style="display:flex;gap:10px;">
        <button type="submit" name="bulk_action" value="reject" class="btn btn-danger" style="flex:1;">Reject Selected</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulk-reject-modal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Quick approve form (hidden, populated via JS) -->
<form method="POST" id="quick-approve-form" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="return_to" value="pending">
</form>

<!-- Series approve/reject forms -->
<form method="POST" id="series-approve-form" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
</form>
<form method="POST" id="series-reject-form" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="admin_note" value="Rejected as part of series rejection.">
</form>

<script>
const APP_URL = <?= json_encode(APP_URL) ?>;

document.getElementById('select-all').addEventListener('change', function() {
  document.querySelectorAll('.row-check:not(:disabled)').forEach(cb => cb.checked = this.checked);
});

function quickApprove(id) {
  if (!confirm('Approve this booking?')) return;
  const form = document.getElementById('quick-approve-form');
  form.action = `${APP_URL}/admin/bookings/${id}/approve`;
  form.submit();
}

function approveSeries(groupId) {
  if (!confirm('Approve all pending occurrences in this series?')) return;
  const form = document.getElementById('series-approve-form');
  form.action = `${APP_URL}/admin/bookings/series/${groupId}/approve`;
  form.submit();
}

function rejectSeries(groupId) {
  const reason = prompt('Reason for rejecting the entire series:');
  if (!reason) return;
  const form = document.getElementById('series-reject-form');
  form.action = `${APP_URL}/admin/bookings/series/${groupId}/reject`;
  form.querySelector('[name="admin_note"]').value = reason;
  form.submit();
}
</script>

<?php endif; ?>

<?php include __DIR__ . '/../../layouts/admin-footer.php'; ?>
