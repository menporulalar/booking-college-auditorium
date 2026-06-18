<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../app/Models/Booking.php';
require_once __DIR__ . '/../../app/Models/Auditorium.php';
Auth::startSession();
Auth::check();

$user = Auth::user();

// Upcoming approved bookings
$upcoming = getDB()->prepare(
    "SELECT b.*, a.name AS auditorium_name FROM bookings b
     JOIN auditoriums a ON a.id = b.auditorium_id
     WHERE b.user_id = ? AND b.status = 'approved' AND b.start_datetime >= NOW()
     ORDER BY b.start_datetime ASC LIMIT 5"
);
$upcoming->execute([$user['id']]);
$upcomingBookings = $upcoming->fetchAll();

$pendingCount  = Booking::countForUser($user['id'], 'pending') + Booking::countForUser($user['id'], 'pending_conflict');
$approvedCount = Booking::countForUser($user['id'], 'approved');
$totalCount    = Booking::countForUser($user['id']);

include __DIR__ . '/../layouts/staff-header.php';
?>

<div class="page-header">
  <div>
    <h1>Welcome, <?= htmlspecialchars($user['name']) ?></h1>
    <p>Here's an overview of your bookings</p>
  </div>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
  <div class="card"><div class="card-body">
    <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Total Bookings</div>
    <div style="font-size:28px;font-weight:700;color:var(--navy);margin:4px 0;"><?= $totalCount ?></div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Pending</div>
    <div style="font-size:28px;font-weight:700;color:var(--amber);margin:4px 0;"><?= $pendingCount ?></div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Approved</div>
    <div style="font-size:28px;font-weight:700;color:var(--green);margin:4px 0;"><?= $approvedCount ?></div>
  </div></div>
</div>

<!-- Quick actions -->
<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
  <a href="<?= APP_URL ?>/bookings/new" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Booking
  </a>
  <a href="<?= APP_URL ?>/calendar" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    View Calendar
  </a>
  <a href="<?= APP_URL ?>/bookings" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    My Bookings
  </a>
</div>

<!-- Upcoming bookings -->
<div class="card">
  <div class="card-header">
    <h2>Upcoming Approved Bookings</h2>
    <a href="<?= APP_URL ?>/bookings?status=approved" class="btn btn-secondary btn-sm">View All</a>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if ($upcomingBookings): ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Event</th><th>Auditorium</th><th>Date</th><th>Time</th></tr></thead>
        <tbody>
          <?php foreach ($upcomingBookings as $b): ?>
          <tr>
            <td>
              <a href="<?= APP_URL ?>/bookings/<?= $b['id'] ?>" style="color:var(--navy);font-weight:600;text-decoration:none;">
                <?= htmlspecialchars($b['event_name']) ?>
              </a>
            </td>
            <td><?= htmlspecialchars($b['auditorium_name']) ?></td>
            <td><?= date('d M Y', strtotime($b['start_datetime'])) ?></td>
            <td><?= date('g:i A', strtotime($b['start_datetime'])) ?> – <?= date('g:i A', strtotime($b['end_datetime'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">
        No upcoming approved bookings yet.
      </p>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../layouts/staff-footer.php'; ?>
