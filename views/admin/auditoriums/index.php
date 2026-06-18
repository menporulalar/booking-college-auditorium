<?php
$pageTitle  = 'Auditoriums';
$activePage = 'auditoriums';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
Auth::startSession();
include __DIR__ . '/../../../layouts/admin-header.php';
?>

<!-- Page header -->
<div class="page-header">
  <div>
    <h1>Auditoriums</h1>
    <p>Manage halls, facilities, and operational hours</p>
  </div>
  <a href="<?= APP_URL ?>/admin/auditoriums/create" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Auditorium
  </a>
</div>

<!-- Flash messages -->
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

<!-- Stats row -->
<div class="stat-grid">
  <?php foreach ($auditoriums as $a): ?>
  <div class="stat-card">
    <div class="stat-label"><?= htmlspecialchars($a['name']) ?></div>
    <div class="stat-value"><?= number_format($a['capacity']) ?></div>
    <div class="stat-sub">
      Max capacity &nbsp;·&nbsp;
      <span class="status-badge <?= $a['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
        <?= ucfirst($a['status']) ?>
      </span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Auditorium cards grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;">
  <?php foreach ($auditoriums as $a): ?>
  <div class="card" style="overflow:hidden;">

    <!-- Hall photo -->
    <?php if ($a['photo']): ?>
      <img
        src="<?= APP_URL ?>/uploads/auditoriums/<?= htmlspecialchars($a['photo']) ?>"
        alt="<?= htmlspecialchars($a['name']) ?>"
        style="width:100%;height:180px;object-fit:cover;"
      >
    <?php else: ?>
      <div style="width:100%;height:140px;background:linear-gradient(135deg,#1E3A5F,#2E75B6);
                  display:flex;align-items:center;justify-content:center;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="rgba(255,255,255,.3)">
          <path d="M3 19h18v2H3v-2zm2-6h2v5H5v-5zm4 0h2v5H9v-5zm4 0h2v5h-2v-5zm4 0h2v5h-2v-5zM3 9l9-7 9 7H3zm9-4.9L6.3 9h11.4L12 4.1z"/>
        </svg>
      </div>
    <?php endif; ?>

    <div class="card-body">
      <!-- Header row -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px;">
        <div>
          <h3 style="font-size:16px;font-weight:700;color:var(--navy);"><?= htmlspecialchars($a['name']) ?></h3>
          <p style="font-size:12px;color:var(--muted);margin-top:2px;">
            Capacity: <strong><?= number_format($a['capacity']) ?></strong> &nbsp;·&nbsp;
            <?= date('g:i A', strtotime($a['operational_start'])) ?> –
            <?= date('g:i A', strtotime($a['operational_end'])) ?>
          </p>
        </div>
        <span class="status-badge <?= $a['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>" style="flex-shrink:0;">
          <?= ucfirst($a['status']) ?>
        </span>
      </div>

      <!-- Description -->
      <?php if ($a['description']): ?>
      <p style="font-size:13px;color:#374151;margin-bottom:12px;line-height:1.5;">
        <?= htmlspecialchars(mb_strimwidth($a['description'], 0, 120, '…')) ?>
      </p>
      <?php endif; ?>

      <!-- Facilities -->
      <?php if ($a['facilities']): ?>
      <div style="margin-bottom:14px;">
        <?php foreach (array_slice($a['facilities'], 0, 6) as $f): ?>
          <span class="facility-tag"><?= htmlspecialchars(Auditorium::facilitiesLabel($f)) ?></span>
        <?php endforeach; ?>
        <?php if (count($a['facilities']) > 6): ?>
          <span class="facility-tag">+<?= count($a['facilities']) - 6 ?> more</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Floor plan indicator -->
      <?php if ($a['floor_plan']): ?>
      <p style="font-size:12px;color:var(--blue);margin-bottom:12px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Floor plan uploaded
      </p>
      <?php endif; ?>

      <!-- Actions -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= APP_URL ?>/admin/auditoriums/<?= $a['id'] ?>" class="btn btn-secondary btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          View
        </a>
        <a href="<?= APP_URL ?>/admin/auditoriums/<?= $a['id'] ?>/edit" class="btn btn-secondary btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit
        </a>
        <form method="POST" action="<?= APP_URL ?>/admin/auditoriums/<?= $a['id'] ?>/toggle" style="margin:0;">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <button type="submit" class="btn btn-sm <?= $a['status'] === 'active' ? 'btn-danger' : 'btn-success' ?>"
            onclick="return confirm('<?= $a['status'] === 'active' ? 'Deactivate' : 'Activate' ?> this auditorium?')">
            <?= $a['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($auditoriums)): ?>
  <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--muted);">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:.4;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    <p style="font-size:15px;">No auditoriums found.</p>
    <a href="<?= APP_URL ?>/admin/auditoriums/create" class="btn btn-primary" style="margin-top:16px;">Add First Auditorium</a>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../layouts/admin-footer.php'; ?>
