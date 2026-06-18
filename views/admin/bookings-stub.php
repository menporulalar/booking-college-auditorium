<?php
$pageTitle  = $pageTitle ?? 'Bookings';
$activePage = $activePage ?? 'bookings';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
Auth::startSession();
Auth::requireRole('admin', 'superadmin');
include __DIR__ . '/../layouts/admin-header.php';
?>

<div class="page-header">
  <div>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <p>This panel will be available in Module 6 (Admin Approval Workflow)</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 20px;color:var(--muted);">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:.4;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    <p style="font-size:15px;">Coming in Module 6 — Admin Approval Workflow</p>
    <p style="font-size:13px;margin-top:6px;">You'll be able to review, approve, reject, and resolve conflicts for staff bookings here.</p>
  </div>
</div>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
