<?php
$pageTitle  = 'New Booking';
$activePage = 'new-booking';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
Auth::startSession();
include __DIR__ . '/../layouts/staff-header.php';

$val = function(string $key, $default = '') use ($old) {
    return htmlspecialchars($old[$key] ?? $default);
};
$selectedEquipment = $old['equipment'] ?? [];
?>

<div class="page-header">
  <div>
    <h1>New Booking Request</h1>
    <p>Fill in the details below. Your request will be sent to the admin for approval.</p>
  </div>
  <a href="<?= APP_URL ?>/calendar" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Calendar
  </a>
</div>

<?php if (!empty($errors)): ?>
<ul class="error-list">
  <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
</ul>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/bookings/new" id="booking-form" novalidate>
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

  <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;" id="form-grid">

    <!-- Left column -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Event details -->
      <div class="card">
        <div class="card-header"><h2>Event Details</h2></div>
        <div class="card-body">

          <div class="form-group">
            <label class="form-label">Event Name <span>*</span></label>
            <input type="text" name="event_name" class="form-control" placeholder="e.g. Annual Cultural Fest" value="<?= $val('event_name') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="event_description" class="form-control" placeholder="Brief description of the event…"><?= $val('event_description') ?></textarea>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Auditorium <span>*</span></label>
              <select name="auditorium_id" id="auditorium_id" class="form-control" required>
                <option value="">Select hall…</option>
                <?php foreach ($auditoriums as $a): ?>
                  <option value="<?= $a['id'] ?>"
                    data-capacity="<?= $a['capacity'] ?>"
                    data-start="<?= substr($a['operational_start'], 0, 5) ?>"
                    data-end="<?= substr($a['operational_end'], 0, 5) ?>"
                    <?= ($val('auditorium_id', $old['auditorium_id'] ?? '')) == $a['id'] ? 'selected' : '' ?>
                  >
                    <?= htmlspecialchars($a['name']) ?> (capacity <?= $a['capacity'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="form-hint" id="hall-hours"></p>
            </div>

            <div class="form-group" style="margin:0;">
              <label class="form-label">Expected Attendees</label>
              <input type="number" name="attendee_count" id="attendee_count" class="form-control" min="1" placeholder="e.g. 150" value="<?= $val('attendee_count') ?>">
              <p class="form-hint" id="capacity-hint"></p>
            </div>
          </div>

        </div>
      </div>

      <!-- Date & time -->
      <div class="card">
        <div class="card-header"><h2>Date &amp; Time</h2></div>
        <div class="card-body">

          <div class="form-group">
            <label class="form-label">Date <span>*</span></label>
            <input type="date" name="start_date" id="start_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= $val('start_date') ?>" required>
          </div>

          <div class="form-row form-row-2">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Start Time <span>*</span></label>
              <input type="time" name="start_time" id="start_time" class="form-control" value="<?= $val('start_time', '09:00') ?>" required>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">End Time <span>*</span></label>
              <input type="time" name="end_time" id="end_time" class="form-control" value="<?= $val('end_time', '11:00') ?>" required>
            </div>
          </div>

          <!-- Live conflict warning -->
          <div id="conflict-warning" style="display:none;" class="alert alert-error" >
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            <div id="conflict-warning-text"></div>
          </div>
          <div id="available-notice" style="display:none;" class="alert alert-success">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            <span>This slot looks available.</span>
          </div>

          <!-- Recurrence toggle -->
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-top:8px;">
            <input type="checkbox" name="is_recurring" id="is_recurring" value="1"
              style="width:18px;height:18px;accent-color:var(--accent);"
              <?= !empty($old['is_recurring']) ? 'checked' : '' ?>>
            <span style="font-size:13.5px;font-weight:600;">This is a recurring event</span>
          </label>

          <!-- Recurrence options -->
          <div id="recurrence-options" style="display:<?= !empty($old['is_recurring']) ? 'block' : 'none' ?>;margin-top:16px;padding:16px;background:#F9FAFB;border-radius:8px;border:1px solid var(--border);">

            <div class="form-group">
              <label class="form-label">Recurrence Pattern</label>
              <select name="recurrence_pattern" id="recurrence_pattern" class="form-control">
                <option value="daily"   <?= ($old['recurrence_pattern'] ?? '') === 'daily'   ? 'selected' : '' ?>>Daily</option>
                <option value="weekly"  <?= ($old['recurrence_pattern'] ?? 'weekly') === 'weekly'  ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= ($old['recurrence_pattern'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="custom"  <?= ($old['recurrence_pattern'] ?? '') === 'custom'  ? 'selected' : '' ?>>Custom Dates</option>
              </select>
            </div>

            <div class="form-row form-row-2" id="interval-group">
              <div class="form-group" style="margin:0;">
                <label class="form-label">Repeat Every</label>
                <div style="display:flex;align-items:center;gap:8px;">
                  <input type="number" name="recurrence_interval" id="recurrence_interval" class="form-control" min="1" max="12" value="<?= $val('recurrence_interval', '1') ?>" style="width:80px;">
                  <span id="interval-unit" style="font-size:13px;color:var(--muted);">week(s)</span>
                </div>
              </div>
              <div class="form-group" style="margin:0;">
                <label class="form-label">End Date</label>
                <input type="date" name="recurrence_end_date" id="recurrence_end_date" class="form-control" value="<?= $val('recurrence_end_date') ?>">
              </div>
            </div>

            <div class="form-group" id="custom-dates-group" style="display:none;margin-bottom:0;">
              <label class="form-label">Custom Dates</label>
              <input type="text" name="custom_dates" class="form-control" placeholder="2026-07-10, 2026-07-17, 2026-07-24" value="<?= $val('custom_dates') ?>">
              <p class="form-hint">Enter comma-separated dates (YYYY-MM-DD). Each will use the same time slot above.</p>
            </div>

            <p class="form-hint" style="margin-top:10px;margin-bottom:0;">
              Each occurrence is created as a separate booking. Admin can approve the entire series or individual occurrences.
            </p>
          </div>

        </div>
      </div>

      <!-- Equipment -->
      <div class="card">
        <div class="card-header">
          <h2>Equipment Requirements</h2>
          <span style="font-size:12px;color:var(--muted);">Optional — specify quantities</span>
        </div>
        <div class="card-body">
          <div class="form-row" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
            <?php foreach ($equipment as $key => $label): ?>
            <div class="form-group" style="margin:0;">
              <label class="form-label"><?= htmlspecialchars($label) ?></label>
              <input
                type="number"
                name="equipment[<?= $key ?>]"
                class="form-control"
                min="0"
                max="20"
                placeholder="0"
                value="<?= htmlspecialchars($selectedEquipment[$key] ?? '') ?>"
              >
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Special requirements -->
      <div class="card">
        <div class="card-header"><h2>Special Requirements</h2></div>
        <div class="card-body">
          <textarea name="special_requirements" class="form-control" placeholder="e.g. Special seating arrangement, additional power outlets, accessibility needs…"><?= $val('special_requirements') ?></textarea>
        </div>
      </div>

    </div>

    <!-- Right column: summary + submit -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <div class="card" style="position:sticky;top:76px;">
        <div class="card-header"><h2>Summary</h2></div>
        <div class="card-body">
          <div id="summary-content" style="font-size:13px;color:var(--muted);line-height:1.8;">
            Fill in the form to see a summary of your request.
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:20px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Submit Booking Request
          </button>
          <a href="<?= APP_URL ?>/calendar" class="btn btn-secondary" style="width:100%;margin-top:8px;">Cancel</a>

          <div class="alert alert-info" style="margin-top:16px;margin-bottom:0;">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            <span style="font-size:12.5px;">Your request status will show as <strong>Pending</strong> until reviewed by an admin. You'll receive an email once it's approved or rejected.</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</form>

<script>
const APP_URL = <?= json_encode(APP_URL) ?>;

const hallSelect      = document.getElementById('auditorium_id');
const hallHours       = document.getElementById('hall-hours');
const capacityHint    = document.getElementById('capacity-hint');
const attendeeInput   = document.getElementById('attendee_count');
const startDate       = document.getElementById('start_date');
const startTime       = document.getElementById('start_time');
const endTime         = document.getElementById('end_time');
const isRecurring     = document.getElementById('is_recurring');
const recurrenceBox   = document.getElementById('recurrence-options');
const recurrencePattern = document.getElementById('recurrence_pattern');
const intervalGroup   = document.getElementById('interval-group');
const intervalUnit    = document.getElementById('interval-unit');
const customDatesGroup = document.getElementById('custom-dates-group');
const conflictWarning = document.getElementById('conflict-warning');
const conflictText    = document.getElementById('conflict-warning-text');
const availableNotice = document.getElementById('available-notice');
const summaryContent  = document.getElementById('summary-content');

function updateHallInfo() {
  const opt = hallSelect.options[hallSelect.selectedIndex];
  if (!opt || !opt.value) {
    hallHours.textContent = '';
    return;
  }
  const start = opt.dataset.start, end = opt.dataset.end, cap = opt.dataset.capacity;
  hallHours.textContent = `Operational hours: ${formatTime(start)} – ${formatTime(end)}`;
  capacityHint.textContent = `Hall capacity: ${cap}`;
  checkConflict();
  updateSummary();
}

function formatTime(t) {
  const [h, m] = t.split(':').map(Number);
  const period = h >= 12 ? 'PM' : 'AM';
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  return `${hour12}:${String(m).padStart(2,'0')} ${period}`;
}

function toggleRecurrence() {
  recurrenceBox.style.display = isRecurring.checked ? 'block' : 'none';
  updateSummary();
}

function updateRecurrenceUI() {
  const pattern = recurrencePattern.value;
  if (pattern === 'custom') {
    intervalGroup.style.display = 'none';
    customDatesGroup.style.display = 'block';
  } else {
    intervalGroup.style.display = 'grid';
    customDatesGroup.style.display = 'none';
    intervalUnit.textContent = { daily: 'day(s)', weekly: 'week(s)', monthly: 'month(s)' }[pattern] || '';
  }
  updateSummary();
}

let conflictTimeout;
function checkConflict() {
  clearTimeout(conflictTimeout);
  conflictWarning.style.display = 'none';
  availableNotice.style.display = 'none';

  const hallId = hallSelect.value;
  const date   = startDate.value;
  const st     = startTime.value;
  const et     = endTime.value;
  if (!hallId || !date || !st || !et) return;
  if (et <= st) return;

  conflictTimeout = setTimeout(() => {
    const params = new URLSearchParams({
      auditorium_id: hallId,
      start: `${date} ${st}:00`,
      end:   `${date} ${et}:00`
    });
    fetch(`${APP_URL}/api/check-conflict?${params}`)
      .then(r => r.json())
      .then(data => {
        if (data.conflict) {
          const list = data.conflicts.map(c =>
            `<strong>${c.eventName}</strong> by ${c.bookedBy} (${c.start} – ${c.end})`
          ).join('<br>');
          conflictText.innerHTML = `This slot may overlap with an existing booking:<br>${list}<br><br>You can still submit — an admin will review this conflict.`;
          conflictWarning.style.display = 'flex';
        } else {
          availableNotice.style.display = 'flex';
        }
      })
      .catch(() => {});
  }, 400);
}

function updateSummary() {
  const hallOpt = hallSelect.options[hallSelect.selectedIndex];
  const hallName = hallOpt && hallOpt.value ? hallOpt.text : '—';
  const eventName = document.querySelector('[name="event_name"]').value || '—';
  const date = startDate.value || '—';
  const st = startTime.value, et = endTime.value;
  const timeStr = (st && et) ? `${formatTime(st)} – ${formatTime(et)}` : '—';
  const attendees = attendeeInput.value || '—';

  let html = `
    <p><strong>Event:</strong> ${escapeHtml(eventName)}</p>
    <p><strong>Hall:</strong> ${escapeHtml(hallName)}</p>
    <p><strong>Date:</strong> ${date}</p>
    <p><strong>Time:</strong> ${timeStr}</p>
    <p><strong>Attendees:</strong> ${attendees}</p>
  `;

  if (isRecurring.checked) {
    const pattern = recurrencePattern.value;
    const patternLabel = { daily: 'Daily', weekly: 'Weekly', monthly: 'Monthly', custom: 'Custom dates' }[pattern];
    const endDate = document.getElementById('recurrence_end_date').value;
    html += `<p style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--border);"><strong>Recurrence:</strong> ${patternLabel}</p>`;
    if (pattern !== 'custom' && endDate) {
      html += `<p><strong>Until:</strong> ${endDate}</p>`;
    }
  }

  summaryContent.innerHTML = html;
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// Event listeners
hallSelect.addEventListener('change', updateHallInfo);
startDate.addEventListener('change', () => { checkConflict(); updateSummary(); });
startTime.addEventListener('change', () => { checkConflict(); updateSummary(); });
endTime.addEventListener('change', () => { checkConflict(); updateSummary(); });
isRecurring.addEventListener('change', toggleRecurrence);
recurrencePattern.addEventListener('change', updateRecurrenceUI);
document.querySelector('[name="event_name"]').addEventListener('input', updateSummary);
attendeeInput.addEventListener('input', updateSummary);
document.getElementById('recurrence_end_date').addEventListener('change', updateSummary);

// Capacity warning
attendeeInput.addEventListener('input', function() {
  const opt = hallSelect.options[hallSelect.selectedIndex];
  if (!opt || !opt.dataset.capacity) return;
  const cap = parseInt(opt.dataset.capacity);
  if (parseInt(this.value) > cap) {
    capacityHint.textContent = `⚠ Exceeds hall capacity (${cap})`;
    capacityHint.style.color = 'var(--red)';
  } else {
    capacityHint.textContent = `Hall capacity: ${cap}`;
    capacityHint.style.color = 'var(--muted)';
  }
});

// Responsive grid
function adjustGrid() {
  const grid = document.getElementById('form-grid');
  grid.style.gridTemplateColumns = window.innerWidth < 900 ? '1fr' : '1fr 360px';
}
window.addEventListener('resize', adjustGrid);

// Init
updateHallInfo();
toggleRecurrence();
updateRecurrenceUI();
updateSummary();
adjustGrid();
</script>

<?php include __DIR__ . '/../layouts/staff-footer.php'; ?>
