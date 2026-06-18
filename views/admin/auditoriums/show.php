<?php
$pageTitle  = htmlspecialchars($auditorium['name']);
$activePage = 'auditoriums';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
Auth::startSession();
include __DIR__ . '/../../../layouts/admin-header.php';
?>

<!-- Page header -->
<div class="page-header">
  <div>
    <h1><?= htmlspecialchars($auditorium['name']) ?></h1>
    <p>Auditorium details, facilities and upcoming blackout dates</p>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= APP_URL ?>/admin/auditoriums/<?= $auditorium['id'] ?>/edit" class="btn btn-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit
    </a>
    <a href="<?= APP_URL ?>/admin/auditoriums" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
  </div>
</div>

<!-- Flash -->
<?php if ($flash['success']): ?>
<div class="alert alert-success">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
  <span><?= $flash['success'] ?></span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;" id="detail-grid">

  <!-- Left -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Info card -->
    <div class="card">
      <div class="card-header">
        <h2>Details</h2>
        <span class="status-badge <?= $auditorium['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
          <?= ucfirst($auditorium['status']) ?>
        </span>
      </div>
      <div class="card-body">
        <table style="width:100%;border-collapse:collapse;">
          <?php $rows = [
            'Capacity'         => number_format($auditorium['capacity']) . ' people',
            'Opening time'     => date('g:i A', strtotime($auditorium['operational_start'])) . ' – ' . date('g:i A', strtotime($auditorium['operational_end'])),
            'Description'      => $auditorium['description'] ?? '—',
            'Created'          => $auditorium['created_at'] ? date('d M Y', strtotime($auditorium['created_at'])) : '—',
          ];
          foreach ($rows as $label => $value): ?>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:10px 0;font-size:13px;font-weight:600;color:var(--muted);width:40%;vertical-align:top;"><?= $label ?></td>
            <td style="padding:10px 0;font-size:13.5px;color:var(--text);"><?= htmlspecialchars($value) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>

    <!-- Facilities card -->
    <div class="card">
      <div class="card-header"><h2>Facilities</h2></div>
      <div class="card-body">
        <?php if ($auditorium['facilities']): ?>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($auditorium['facilities'] as $f): ?>
              <span class="facility-tag" style="font-size:13px;padding:6px 12px;">
                <?= htmlspecialchars(Auditorium::facilitiesLabel($f)) ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="color:var(--muted);font-size:13px;">No facilities listed.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Upcoming blackout dates -->
    <div class="card">
      <div class="card-header">
        <h2>Upcoming Blackout Dates</h2>
        <a href="<?= APP_URL ?>/admin/blackout-dates?auditorium_id=<?= $auditorium['id'] ?>" class="btn btn-secondary btn-sm">Manage</a>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if ($blackouts): ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Scope</th>
                <th>Reason</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($blackouts as $b): ?>
              <tr>
                <td><strong><?= date('d M Y', strtotime($b['blackout_date'])) ?></strong></td>
                <td>
                  <?php if ($b['auditorium_id']): ?>
                    <span class="facility-tag"><?= htmlspecialchars($auditorium['name']) ?></span>
                  <?php else: ?>
                    <span class="facility-tag" style="background:#FEF3C7;color:#92400E;">All Halls</span>
                  <?php endif; ?>
                </td>
                <td style="color:var(--muted);"><?= htmlspecialchars($b['reason'] ?? '—') ?></td>
                <td>
                  <form method="POST" action="<?= APP_URL ?>/admin/blackout-dates/<?= $b['id'] ?>/delete" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this blackout date?')">Remove</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <p style="color:var(--muted);font-size:13px;padding:16px 20px;">No upcoming blackout dates.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Right: images -->
  <div style="display:flex;flex-direction:column;gap:20px;">
    <?php if ($auditorium['photo']): ?>
    <div class="card">
      <div class="card-header"><h2>Hall Photo</h2></div>
      <div class="card-body" style="padding:12px;">
        <img
          src="<?= APP_URL ?>/uploads/auditoriums/<?= htmlspecialchars($auditorium['photo']) ?>"
          alt="Hall photo"
          style="width:100%;border-radius:var(--radius);object-fit:cover;max-height:260px;"
        >
      </div>
    </div>
    <?php endif; ?>

    <?php if ($auditorium['floor_plan']): ?>
    <div class="card">
      <div class="card-header"><h2>Floor Plan</h2></div>
      <div class="card-body" style="padding:12px;">
        <img
          src="<?= APP_URL ?>/uploads/auditoriums/<?= htmlspecialchars($auditorium['floor_plan']) ?>"
          alt="Floor plan"
          style="width:100%;border-radius:var(--radius);object-fit:contain;max-height:300px;background:#f9fafb;"
        >
      </div>
    </div>
    <?php endif; ?>

    <?php if (!$auditorium['photo'] && !$auditorium['floor_plan']): ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:40px 20px;color:var(--muted);">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:8px;opacity:.4;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        <p style="font-size:13px;">No images uploaded</p>
        <a href="<?= APP_URL ?>/admin/auditoriums/<?= $auditorium['id'] ?>/edit" class="btn btn-secondary btn-sm" style="margin-top:12px;">Add Images</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
function adjustGrid() {
  const grid = document.getElementById('detail-grid');
  grid.style.gridTemplateColumns = window.innerWidth < 900 ? '1fr' : '1fr 340px';
}
window.addEventListener('resize', adjustGrid);
adjustGrid();
</script>

<?php include __DIR__ . '/../../../layouts/admin-footer.php'; ?>
