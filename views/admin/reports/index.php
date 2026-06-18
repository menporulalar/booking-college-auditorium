<?php
$pageTitle  = 'Reports';
$activePage = 'reports';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../app/Helpers/Auth.php';
require_once __DIR__ . '/../../../app/Models/Booking.php';
Auth::startSession();
include __DIR__ . '/../../../layouts/admin-header.php';

$reportTabs = [
    'summary'       => ['label' => 'Booking Summary',     'icon' => 'list'],
    'utilization'   => ['label' => 'Utilization Rate',    'icon' => 'bar'],
    'heatmap'       => ['label' => 'Peak Times',          'icon' => 'grid'],
    'equipment'     => ['label' => 'Equipment Usage',     'icon' => 'box'],
    'overrides'     => ['label' => 'Override Log',        'icon' => 'shield'],
    'notifications' => ['label' => 'Notification Log',    'icon' => 'bell'],
];

$exportQuery = http_build_query(array_merge($filters, ['report' => $report]));
?>

<div class="page-header">
  <div>
    <h1>Reports &amp; Analytics</h1>
    <p>Insights into bookings, utilization, equipment, and system activity</p>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= APP_URL ?>/admin/reports/export/pdf?<?= $exportQuery ?>" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Export PDF
    </a>
    <a href="<?= APP_URL ?>/admin/reports/export/excel?<?= $exportQuery ?>" class="btn btn-secondary">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="19"/><line x1="15" y1="13" x2="9" y2="19"/></svg>
      Export Excel
    </a>
  </div>
</div>

<!-- Report tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
  <?php foreach ($reportTabs as $key => $tab):
    $active = $report === $key;
    $qs = array_merge($filters, ['report' => $key]);
  ?>
  <a href="<?= APP_URL ?>/admin/reports?<?= http_build_query($qs) ?>"
     class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-secondary' ?>">
    <?= $tab['label'] ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-body">
    <form method="GET" action="<?= APP_URL ?>/admin/reports" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <input type="hidden" name="report" value="<?= htmlspecialchars($report) ?>">

      <div style="min-width:160px;">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filters['from']) ?>">
      </div>
      <div style="min-width:160px;">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filters['to']) ?>">
      </div>

      <?php if (in_array($report, ['summary','utilization','heatmap','equipment'])): ?>
      <div style="min-width:180px;">
        <label class="form-label">Auditorium</label>
        <select name="auditorium_id" class="form-control">
          <option value="">All Auditoriums</option>
          <?php foreach ($auditoriums as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $filters['auditorium_id'] == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <?php if ($report === 'summary'): ?>
      <div style="min-width:160px;">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <?php foreach (Booking::STATUSES as $key => $info): ?>
            <option value="<?= $key ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= $info['label'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary">Apply</button>

      <!-- Quick presets -->
      <div style="display:flex;gap:6px;margin-left:auto;">
        <?php
        $presets = [
          'This Month' => [date('Y-m-01'), date('Y-m-t')],
          'Last Month' => [date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month'))],
          'This Year'  => [date('Y-01-01'), date('Y-12-31')],
        ];
        foreach ($presets as $label => [$pFrom, $pTo]):
          $pq = array_merge($filters, ['report' => $report, 'from' => $pFrom, 'to' => $pTo]);
        ?>
          <a href="<?= APP_URL ?>/admin/reports?<?= http_build_query($pq) ?>" class="btn btn-secondary btn-sm"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
    </form>
  </div>
</div>

<!-- ════════════ REPORT CONTENT ════════════ -->

<?php if ($report === 'summary'): ?>
  <?php $stats = $data['stats']; ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;margin-bottom:20px;">
    <div class="card"><div class="card-body">
      <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;">Total</div>
      <div style="font-size:24px;font-weight:700;color:var(--navy);"><?= $stats['total'] ?></div>
    </div></div>
    <div class="card"><div class="card-body">
      <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;">Approved</div>
      <div style="font-size:24px;font-weight:700;color:var(--green);"><?= $stats['approved'] ?></div>
    </div></div>
    <div class="card"><div class="card-body">
      <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;">Pending</div>
      <div style="font-size:24px;font-weight:700;color:var(--amber);"><?= $stats['pending'] ?></div>
    </div></div>
    <div class="card"><div class="card-body">
      <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;">Rejected</div>
      <div style="font-size:24px;font-weight:700;color:var(--red);"><?= $stats['rejected'] ?></div>
    </div></div>
    <div class="card"><div class="card-body">
      <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;">Cancelled</div>
      <div style="font-size:24px;font-weight:700;color:var(--muted);"><?= $stats['cancelled'] ?></div>
    </div></div>
    <div class="card"><div class="card-body">
      <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;">Approved Hours</div>
      <div style="font-size:24px;font-weight:700;color:var(--blue);"><?= round($stats['total_hours'], 1) ?></div>
    </div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2>Bookings</h2>
      <span style="font-size:13px;color:var(--muted);"><?= count($data['rows']) ?> result(s)</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($data['rows']): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Event</th><th>Auditorium</th><th>Requested by</th><th>Department</th><th>Date &amp; Time</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($data['rows'] as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['event_name']) ?></strong></td>
              <td><?= htmlspecialchars($r['auditorium_name']) ?></td>
              <td><?= htmlspecialchars($r['user_name']) ?></td>
              <td><?= htmlspecialchars($r['user_department'] ?? '—') ?></td>
              <td style="white-space:nowrap;">
                <?= date('d M Y', strtotime($r['start_datetime'])) ?><br>
                <span style="color:var(--muted);font-size:12px;"><?= date('g:i A', strtotime($r['start_datetime'])) ?> – <?= date('g:i A', strtotime($r['end_datetime'])) ?></span>
              </td>
              <td><?= Booking::statusBadge($r['status']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">No bookings found for the selected filters.</p>
      <?php endif; ?>
    </div>
  </div>

<?php elseif ($report === 'utilization'): ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:20px;">
    <?php foreach ($data['rows'] as $r): ?>
    <div class="card">
      <div class="card-body">
        <h3 style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:8px;"><?= htmlspecialchars($r['auditorium_name']) ?></h3>
        <div style="display:flex;align-items:baseline;gap:6px;margin-bottom:8px;">
          <span style="font-size:32px;font-weight:700;color:<?= $r['utilization_pct'] > 60 ? 'var(--green)' : ($r['utilization_pct'] > 30 ? 'var(--amber)' : 'var(--red)') ?>;">
            <?= $r['utilization_pct'] ?>%
          </span>
          <span style="font-size:12px;color:var(--muted);">utilization</span>
        </div>
        <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;margin-bottom:10px;">
          <div style="height:100%;width:<?= min(100, $r['utilization_pct']) ?>%;background:<?= $r['utilization_pct'] > 60 ? 'var(--green)' : ($r['utilization_pct'] > 30 ? 'var(--amber)' : 'var(--red)') ?>;"></div>
        </div>
        <p style="font-size:12px;color:var(--muted);">
          <?= number_format($r['booked_hours'], 1) ?> hrs booked of <?= number_format($r['available_hours'], 1) ?> hrs available
          (<?= $r['booking_count'] ?> bookings)
        </p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header"><h2>Detail</h2></div>
    <div class="card-body" style="padding:0;">
      <?php if ($data['rows']): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Auditorium</th><th>Capacity</th><th>Available Hours</th><th>Booked Hours</th><th>Bookings</th><th>Utilization</th></tr></thead>
          <tbody>
            <?php foreach ($data['rows'] as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['auditorium_name']) ?></strong></td>
              <td><?= number_format($r['capacity']) ?></td>
              <td><?= number_format($r['available_hours'], 1) ?></td>
              <td><?= number_format($r['booked_hours'], 1) ?></td>
              <td><?= $r['booking_count'] ?></td>
              <td><strong><?= $r['utilization_pct'] ?>%</strong></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">No data available.</p>
      <?php endif; ?>
    </div>
  </div>

<?php elseif ($report === 'heatmap'): ?>

  <div class="card">
    <div class="card-header">
      <h2>Peak Booking Times</h2>
      <span style="font-size:13px;color:var(--muted);">Approved bookings, count per hour</span>
    </div>
    <div class="card-body">
      <?php
      $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
      $grid = $data['grid'];
      $max = 1;
      foreach ($grid as $row) foreach ($row as $v) if ($v > $max) $max = $v;
      ?>
      <div style="overflow-x:auto;">
        <table style="border-collapse:collapse;font-size:11px;min-width:900px;">
          <thead>
            <tr>
              <th style="padding:4px 8px;text-align:left;color:var(--muted);">Day</th>
              <?php for ($h = 0; $h < 24; $h++): ?>
                <th style="padding:4px 2px;text-align:center;color:var(--muted);font-weight:500;width:32px;"><?= $h ?></th>
              <?php endfor; ?>
            </tr>
          </thead>
          <tbody>
            <?php for ($d = 0; $d < 7; $d++): ?>
            <tr>
              <td style="padding:4px 8px;font-weight:600;color:var(--text);"><?= $days[$d] ?></td>
              <?php for ($h = 0; $h < 24; $h++):
                $count = $grid[$d][$h];
                $intensity = $count / $max;
                $bg = $count === 0 ? '#FFFFFF' : 'rgba(220,38,38,' . (0.1 + $intensity * 0.7) . ')';
              ?>
                <td style="padding:6px 2px;text-align:center;background:<?= $bg ?>;border:1px solid #F3F4F6;color:<?= $count > 0 ? '#7F1D1D' : '#D1D5DB' ?>;font-weight:<?= $count > 0 ? '700' : '400' ?>;">
                  <?= $count ?: '·' ?>
                </td>
              <?php endfor; ?>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
      <p style="font-size:12px;color:var(--muted);margin-top:12px;">
        Darker cells indicate more frequently booked hour slots across the date range. Hours shown in 24-hour format.
      </p>
    </div>
  </div>

<?php elseif ($report === 'equipment'): ?>

  <div class="card">
    <div class="card-header">
      <h2>Equipment Usage</h2>
      <span style="font-size:13px;color:var(--muted;"><?= count($data['rows']) ?> item type(s)</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($data['rows']): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Equipment</th><th>Total Quantity Requested</th><th>Number of Bookings</th><th>Marked Unavailable</th><th>Usage</th></tr></thead>
          <tbody>
            <?php
            $maxQty = max(array_column($data['rows'], 'total_qty')) ?: 1;
            foreach ($data['rows'] as $r):
              $pct = round(($r['total_qty'] / $maxQty) * 100);
            ?>
            <tr>
              <td><strong><?= htmlspecialchars(Booking::EQUIPMENT_OPTIONS[$r['equipment_name']] ?? $r['equipment_name']) ?></strong></td>
              <td><?= $r['total_qty'] ?></td>
              <td><?= $r['booking_count'] ?></td>
              <td>
                <?php if ($r['unavailable_count'] > 0): ?>
                  <span style="color:var(--red);font-weight:600;"><?= $r['unavailable_count'] ?></span>
                <?php else: ?>
                  <span style="color:var(--muted);">0</span>
                <?php endif; ?>
              </td>
              <td style="min-width:140px;">
                <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;">
                  <div style="height:100%;width:<?= $pct ?>%;background:var(--blue);"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">No equipment requests found for the selected filters.</p>
      <?php endif; ?>
    </div>
  </div>

<?php elseif ($report === 'overrides'): ?>

  <div class="card">
    <div class="card-header">
      <h2>Admin Override Log</h2>
      <span style="font-size:13px;color:var(--muted);"><?= count($data['rows']) ?> override(s)</span>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($data['rows']): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Event</th><th>Auditorium</th><th>Requested by</th><th>Date &amp; Time</th><th>Approved by</th><th>Override Reason</th></tr></thead>
          <tbody>
            <?php foreach ($data['rows'] as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['event_name']) ?></strong></td>
              <td><?= htmlspecialchars($r['auditorium_name']) ?></td>
              <td><?= htmlspecialchars($r['user_name']) ?></td>
              <td style="white-space:nowrap;">
                <?= date('d M Y', strtotime($r['start_datetime'])) ?><br>
                <span style="color:var(--muted);font-size:12px;"><?= date('g:i A', strtotime($r['start_datetime'])) ?> – <?= date('g:i A', strtotime($r['end_datetime'])) ?></span>
              </td>
              <td><?= htmlspecialchars($r['override_by_name'] ?? '—') ?></td>
              <td style="max-width:280px;"><?= htmlspecialchars($r['override_reason'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">No conflict overrides found for the selected date range.</p>
      <?php endif; ?>
    </div>
  </div>

<?php elseif ($report === 'notifications'): ?>

  <div class="card">
    <div class="card-header">
      <h2>Notification Delivery Report</h2>
      <a href="<?= APP_URL ?>/admin/notifications" class="btn btn-secondary btn-sm">Full Log</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($data['rows']): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Event Type</th><th>Total</th><th>Sent</th><th>Failed</th><th>Success Rate</th></tr></thead>
          <tbody>
            <?php foreach ($data['rows'] as $r):
              $rate = $r['total'] > 0 ? round(($r['sent'] / $r['total']) * 100) : 0;
            ?>
            <tr>
              <td><span class="facility-tag"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $r['trigger_event']))) ?></span></td>
              <td><?= $r['total'] ?></td>
              <td style="color:var(--green);font-weight:600;"><?= $r['sent'] ?></td>
              <td style="color:<?= $r['failed'] > 0 ? 'var(--red)' : 'var(--muted)' ?>;font-weight:600;"><?= $r['failed'] ?></td>
              <td><?= $rate ?>%</td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="padding:32px 20px;text-align:center;color:var(--muted);font-size:13px;">No notification activity found for the selected date range.</p>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/../../../layouts/admin-footer.php'; ?>
