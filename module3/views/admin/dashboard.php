<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Models/Auditorium.php';
require_once __DIR__ . '/../../app/Models/BlackoutDate.php';
Auth::startSession();
Auth::requireRole('admin', 'superadmin');

$auditoriums     = Auditorium::all(true);
$totalAuditoriums = count($auditoriums);
$activeCount      = count(array_filter($auditoriums, fn($a) => $a['status'] === 'active'));
$upcomingBlackouts = BlackoutDate::all(['from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+30 days'))]);

include __DIR__ . '/../../views/layouts/admin-header.php';
?>

<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars(Auth::user()['name']) ?>. Here's your system overview.</p>
  </div>
</div>

<!-- Stat cards -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));">
  <div class="stat-card">
    <div class="stat-label">Total Auditoriums</div>
    <div class="stat-value"><?= $totalAuditoriums ?></div>
    <div class="stat-sub"><?= $activeCount ?> active</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Pending Approvals</div>
    <div class="stat-value" style="color:var(--amber);">—</div>
    <div class="stat-sub">Available in Module 6</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Today's Bookings</div>
    <div class="stat-value" style="color:var(--blue);">—</div>
    <div class="stat-sub">Available in Module 3</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Upcoming Blackouts</div>
    <div class="stat-value" style="color:var(--red);"><?= count($upcomingBlackouts) ?></div>
    <div class="stat-sub">Next 30 days</div>
  </div>
</div>

<!-- Auditoriums quick view -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h2>Auditoriums</h2>
    <a href="<?= APP_URL ?>/admin/auditoriums" class="btn btn-secondary btn-sm">Manage</a>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Capacity</th>
            <th>Hours</th>
            <th>Facilities</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($auditoriums as $a): ?>
          <tr>
            <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
            <td><?= number_format($a['capacity']) ?></td>
            <td style="color:var(--muted);">
              <?= date('g:ia', strtotime($a['operational_start'])) ?> –
              <?= date('g:ia', strtotime($a['operational_end'])) ?>
            </td>
            <td>
              <?php foreach (array_slice($a['facilities'], 0, 3) as $f): ?>
                <span class="facility-tag"><?= htmlspecialchars(Auditorium::facilitiesLabel($f)) ?></span>
              <?php endforeach; ?>
              <?php if (count($a['facilities']) > 3): ?>
                <span class="facility-tag">+<?= count($a['facilities']) - 3 ?></span>
              <?php endif; ?>
            </td>
            <td>
              <span class="status-badge <?= $a['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                <?= ucfirst($a['status']) ?>
              </span>
            </td>
            <td>
              <a href="<?= APP_URL ?>/admin/auditoriums/<?= $a['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Upcoming blackouts -->
<?php if ($upcomingBlackouts): ?>
<div class="card">
  <div class="card-header">
    <h2>Upcoming Blackout Dates <span style="font-size:12px;color:var(--muted);font-weight:400;">(next 30 days)</span></h2>
    <a href="<?= APP_URL ?>/admin/blackout-dates" class="btn btn-secondary btn-sm">Manage</a>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Day</th><th>Hall</th><th>Reason</th></tr></thead>
        <tbody>
          <?php foreach ($upcomingBlackouts as $b): ?>
          <tr>
            <td><strong><?= date('d M Y', strtotime($b['blackout_date'])) ?></strong></td>
            <td style="color:var(--muted);"><?= date('l', strtotime($b['blackout_date'])) ?></td>
            <td>
              <?php if ($b['auditorium_id']): ?>
                <span class="facility-tag"><?= htmlspecialchars($b['auditorium_name']) ?></span>
              <?php else: ?>
                <span class="facility-tag" style="background:#FEF3C7;color:#92400E;">All Halls</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--muted);"><?= htmlspecialchars($b['reason'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../views/layouts/admin-footer.php'; ?>
