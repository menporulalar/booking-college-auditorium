<?php
$pageTitle  = 'Availability Calendar';
$activePage = 'calendar';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Helpers/Auth.php';
Auth::startSession();
include __DIR__ . '/../layouts/staff-header.php';
?>

<div class="page-header">
  <div>
    <h1>Availability Calendar</h1>
    <p>View bookings across all auditoriums and check availability before requesting a slot</p>
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

<!-- Filter + legend bar -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">

    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <label class="form-label" style="margin:0;white-space:nowrap;">Auditorium:</label>
      <select id="hall-filter" class="form-control" style="min-width:200px;width:auto;">
        <option value="0">All Auditoriums</option>
        <?php foreach ($auditoriums as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $selectedHall === $a['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['name']) ?> (<?= $a['capacity'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Legend -->
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--muted);">
      <span style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:3px;background:#FEF3C7;border:1.5px solid #D97706;display:inline-block;"></span> Pending</span>
      <span style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:3px;background:#FEF2F2;border:1.5px solid #DC2626;display:inline-block;"></span> Conflict</span>
      <span style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:3px;background:#F0FDF4;border:1.5px solid #16A34A;display:inline-block;"></span> Approved</span>
      <span style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:3px;background:#F3F4F6;border:1.5px solid #9CA3AF;display:inline-block;"></span> Blackout</span>
    </div>
  </div>
</div>

<!-- Calendar -->
<div class="card">
  <div class="card-body">
    <div id="calendar"></div>
  </div>
</div>

<!-- Event detail modal -->
<div id="event-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center;padding:16px;">
  <div style="background:#fff;border-radius:12px;max-width:400px;width:100%;padding:24px;box-shadow:0 10px 40px rgba(0,0,0,.2);">
    <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:16px;">
      <h3 id="modal-title" style="font-size:16px;font-weight:700;color:var(--navy);"></h3>
      <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;color:var(--muted);padding:0;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div id="modal-body" style="font-size:13.5px;color:var(--text);line-height:1.8;"></div>
    <button onclick="closeModal()" class="btn btn-secondary" style="width:100%;margin-top:16px;">Close</button>
  </div>
</div>

<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<style>
  /* FullCalendar overrides */
  .fc { font-family:'Inter',sans-serif; font-size:13px; }
  .fc .fc-toolbar-title { font-size:16px; font-weight:700; color:var(--navy); }
  .fc .fc-button { background:var(--navy); border:none; font-size:12.5px; font-weight:600; text-transform:capitalize; padding:6px 12px; }
  .fc .fc-button:hover { background:var(--navy-d); }
  .fc .fc-button-active { background:var(--navy-d) !important; }
  .fc .fc-button-primary:disabled { background:#9CA3AF; }
  .fc-daygrid-event { border-radius:4px; padding:1px 4px; font-weight:500; cursor:pointer; }
  .fc-event-title { font-size:11px; }
  .fc-col-header-cell { background:#F9FAFB; }
  .fc-day-today { background:#EFF6FF !important; }

  @media (max-width: 600px) {
    .fc .fc-toolbar { flex-direction:column; gap:8px; align-items:stretch; }
    .fc .fc-toolbar-chunk { display:flex; justify-content:center; }
  }
</style>

<script>
const APP_URL = <?= json_encode(APP_URL) ?>;
let currentHall = <?= (int)$selectedHall ?>;

document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const isMobile = window.innerWidth < 768;

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: isMobile ? 'listWeek' : 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: isMobile ? 'dayGridMonth,listWeek' : 'dayGridMonth,timeGridWeek,listWeek'
    },
    height: 'auto',
    navLinks: true,
    nowIndicator: true,
    dayMaxEvents: 3,
    eventDisplay: 'block',

    events: function(info, success, failure) {
      const params = new URLSearchParams({
        start: info.startStr,
        end: info.endStr,
        hall: currentHall
      });
      fetch(`${APP_URL}/api/calendar-events?${params}`)
        .then(r => r.json())
        .then(success)
        .catch(failure);
    },

    eventClick: function(info) {
      const props = info.event.extendedProps;
      if (props.type === 'blackout') return; // background events, no modal

      document.getElementById('modal-title').textContent = info.event.title.split(' — ')[0];
      document.getElementById('modal-body').innerHTML = `
        <p><strong>Auditorium:</strong> ${props.auditorium || '—'}</p>
        <p><strong>Booked by:</strong> ${props.bookedBy || '—'}</p>
        <p><strong>Time:</strong> ${info.event.start.toLocaleString('en-US', {dateStyle:'medium', timeStyle:'short'})} – ${info.event.end ? info.event.end.toLocaleTimeString('en-US', {timeStyle:'short'}) : ''}</p>
        <p><strong>Status:</strong> <span style="color:${info.event.borderColor};font-weight:600;">${props.statusLabel || ''}</span></p>
      `;
      document.getElementById('event-modal').style.display = 'flex';
    },

    // Click on empty date slot → pre-fill new booking form
    dateClick: function(info) {
      const params = new URLSearchParams({
        start_date: info.dateStr.substring(0, 10),
        auditorium_id: currentHall || ''
      });
      window.location.href = `${APP_URL}/bookings/new?${params}`;
    }
  });

  calendar.render();

  // Hall filter change
  document.getElementById('hall-filter').addEventListener('change', function() {
    currentHall = parseInt(this.value) || 0;
    calendar.refetchEvents();
  });

  // Resize: switch views on mobile
  window.addEventListener('resize', function() {
    const mobile = window.innerWidth < 768;
    if (mobile && calendar.view.type !== 'listWeek' && calendar.view.type !== 'dayGridMonth') {
      calendar.changeView('listWeek');
    }
  });
});

function closeModal() {
  document.getElementById('event-modal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../layouts/staff-footer.php'; ?>
